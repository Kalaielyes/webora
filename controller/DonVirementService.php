<?php
require_once __DIR__ . '/../models/config.php';

/**
 * DonVirementService — handles banking operations when a donation is made via bank transfer.
 * - Checks that account is active and of type 'courant' (not 'epargne')
 * - Converts donation amount to account currency using static rates
 * - Deducts from account balance atomically (PDO transaction + FOR UPDATE)
 * - Inserts a virement record linked to the donation
 */
class DonVirementService
{
    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name"
        );
        $stmt->execute(['table_name' => $tableName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Static exchange rates (indicative, update periodically).
     * rates[from][to]
     */
    private static array $rates = [
        'TND' => ['TND' => 1.000, 'EUR' => 0.297, 'USD' => 0.323, 'GBP' => 0.254],
        'EUR' => ['TND' => 3.366, 'EUR' => 1.000, 'USD' => 1.087, 'GBP' => 0.856],
        'USD' => ['TND' => 3.096, 'EUR' => 0.920, 'USD' => 1.000, 'GBP' => 0.788],
        'GBP' => ['TND' => 3.935, 'EUR' => 1.168, 'USD' => 1.269, 'GBP' => 1.000],
    ];

    /**
     * Convert an amount from one currency to another.
     *
     * @throws \InvalidArgumentException if currencies are unsupported
     */
    public static function convert(float $amount, string $from, string $to): float
    {
        $from = strtoupper(trim($from));
        $to   = strtoupper(trim($to));
        if ($from === $to) return $amount;
        if (!isset(self::$rates[$from][$to])) {
            throw new \InvalidArgumentException("Conversion {$from}→{$to} non supportée");
        }
        return round($amount * self::$rates[$from][$to], 3);
    }

    /**
     * Process a donation virement:
     * 1. Lock and validate the account
     * 2. Convert donation currency to account currency
     * 3. Check sufficient balance
     * 4. Deduct balance atomically
     * 5. Update don row with banking info + set status 'confirme'
     * 6. Insert virement record
     *
     * NOTE: Caller is responsible for wrapping this in a PDO transaction.
     *       DonController::ajouterDon() calls this inside beginTransaction().
     *
     * @param PDO    $pdo          Active PDO connection (already in transaction)
     * @param int    $idDon        The don row just inserted (status = en_attente)
    * @param int    $idCompte     FK to comptebancaire
     * @param float  $montantDon   Donation amount in the donation currency
     * @param string $deviseDon    Donation currency (e.g. 'TND')
     *
     * @throws \RuntimeException on any business rule or DB failure
     */
    public static function processVirementDon(
        PDO    $pdo,
        int    $idDon,
        int    $idDonateur,
        int    $idCompte,
        float  $montantDon,
        string $deviseDon
    ): void {
        // 1. Lock account row
        $stmtCompte = $pdo->prepare(
            "SELECT id_compte, id_utilisateur, solde, devise, type_compte, statut
             FROM comptebancaire
             WHERE id_compte = :id_compte
             FOR UPDATE"
        );
        $stmtCompte->execute(['id_compte' => $idCompte]);
        $compte = $stmtCompte->fetch();

        if (!$compte) {
            throw new \RuntimeException("Compte introuvable (id={$idCompte})");
        }
        if ((int)($compte['id_utilisateur'] ?? 0) !== $idDonateur) {
            throw new \RuntimeException("Le compte sélectionné n'appartient pas à cet utilisateur");
        }

        // 2. Validate account state
        if (strtolower(trim($compte['statut'] ?? '')) !== 'actif') {
            throw new \RuntimeException("Le compte sélectionné n'est pas actif");
        }
        if (strtolower(trim($compte['type_compte'] ?? '')) === 'epargne') {
            throw new \RuntimeException("Les comptes épargne ne peuvent pas être utilisés pour un don");
        }

        // 3. Currency rules
        $deviseCompte = strtoupper(trim($compte['devise'] ?? 'TND'));
        $deviseDon    = strtoupper(trim($deviseDon));
        $typeCompte   = strtolower(trim($compte['type_compte'] ?? ''));

        // Hard rule: if account currency is TND, debit exactly X (no conversion)
        if ($deviseCompte === 'TND') {
            $deviseDon      = 'TND';
            $montantDeduire = round($montantDon, 3);
        }
        // Rule requested earlier: devise account => no conversion
        elseif ($typeCompte === 'devise') {
            if ($deviseDon !== $deviseCompte) {
                throw new \RuntimeException("Compte devise: la devise du don doit correspondre à la devise du compte");
            }
            $montantDeduire = round($montantDon, 3);
        } else {
            try {
                $montantDeduire = self::convert($montantDon, $deviseDon, $deviseCompte);
            } catch (\InvalidArgumentException $e) {
                throw new \RuntimeException("Conversion impossible : " . $e->getMessage());
            }
        }

        if ($montantDeduire <= 0) {
            throw new \RuntimeException("Montant converti invalide (montantDeduire={$montantDeduire})");
        }

        // 4. Balance check
        $solde = (float)($compte['solde'] ?? 0);
        if ($solde < $montantDeduire) {
            throw new \RuntimeException(
                sprintf("Solde insuffisant (disponible %.3f %s, requis %.3f %s)",
                    $solde, $deviseCompte, $montantDeduire, $deviseCompte)
            );
        }

        // 5. Atomic deduction — race-condition-safe via conditional WHERE
        $stmtDeduct = $pdo->prepare(
            "UPDATE comptebancaire
             SET solde = solde - :montant
             WHERE id_compte = :id_compte
               AND solde >= :montant_check
               AND statut = 'actif'
               AND type_compte != 'epargne'"
        );
        $stmtDeduct->execute([
            'montant'       => $montantDeduire,
            'id_compte'     => $idCompte,
            'montant_check' => $montantDeduire,
        ]);
        if ($stmtDeduct->rowCount() !== 1) {
            throw new \RuntimeException("La mise à jour du solde a échoué (race condition ou compte invalide)");
        }

        // 6. Update don row: link to account, store converted amount, mark confirmed
        $stmtDon = $pdo->prepare(
            "UPDATE don
             SET id_compte        = :id_compte,
                 devise_don       = :devise_don,
                 montant_converti = :montant_converti,
                 statut           = 'confirme'
             WHERE id_don = :id_don
               AND statut = 'en_attente'"
        );
        $stmtDon->execute([
            'id_compte'        => $idCompte,
            'devise_don'       => $deviseDon,
            'montant_converti' => $montantDeduire,
            'id_don'           => $idDon,
        ]);

        // 7. Also update cagnotte's montant_collecte
        $stmtCag = $pdo->prepare(
            "UPDATE cagnotte c
             INNER JOIN don d ON d.id_cagnotte = c.id_cagnotte
             SET c.montant_collecte = COALESCE(c.montant_collecte, 0) + :montant
             WHERE d.id_don = :id_don"
        );
        $stmtCag->execute(['montant' => $montantDon, 'id_don' => $idDon]);

        // 8. Insert virement record only if the optional transfer-log table exists.
        if (self::tableExists($pdo, 'virement')) {
            $stmtVir = $pdo->prepare(
                "INSERT INTO virement
                 (id_compte, montant, devise, type_virement, date_virement, description, id_don)
                 VALUES
                 (:id_compte, :montant, :devise, 'don', NOW(), :description, :id_don)"
            );
            $stmtVir->execute([
                'id_compte'   => $idCompte,
                'montant'     => $montantDeduire,
                'devise'      => $deviseCompte,
                'description' => "Don #" . $idDon . " (" . number_format($montantDon, 3) . " {$deviseDon})",
                'id_don'      => $idDon,
            ]);
        }
    }
}
