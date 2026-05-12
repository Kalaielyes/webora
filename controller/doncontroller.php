<?php
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Don.php';
require_once __DIR__ . '/CagnotteController.php';
require_once __DIR__ . '/AchievementService.php';
require_once __DIR__ . '/DonEmailService.php';
require_once __DIR__ . '/DonVirementService.php';

class DonController
{
    private ?string $lastError = null;

    private const ALLOWED_STATUSES = ['en_attente', 'confirme', 'refuse'];

    private function normalizeDonStatus(string $status): string
    {
        $s = strtolower(trim($status));
        if ($s === 'en attente') return 'en_attente';
        if ($s === 'confirmé')   return 'confirme';
        if ($s === 'refusé')     return 'refuse';
        if ($s === 'remboursé' || $s === 'rembourse') return 'refuse';
        return $s;
    }

    private function sanitizeDonStatus(string $status): string
    {
        $normalized = $this->normalizeDonStatus($status);
        return in_array($normalized, self::ALLOWED_STATUSES, true) ? $normalized : 'en_attente';
    }

    public function getDonStatusLabel(string $status): string
    {
        $labels = ['en_attente' => 'En attente', 'confirme' => 'Confirmé', 'refuse' => 'Refusé'];
        return $labels[$this->sanitizeDonStatus($status)] ?? 'En attente';
    }

    public function getDonStatusBadgeClass(string $status): string
    {
        $classes = ['en_attente' => 'b-attente', 'confirme' => 'b-confirme', 'refuse' => 'b-danger'];
        return $classes[$this->sanitizeDonStatus($status)] ?? 'b-attente';
    }

    private function normalizeDonRows(array $rows): array
    {
        return array_map(function ($r) {
            if (isset($r['statut'])) $r['statut'] = $this->sanitizeDonStatus($r['statut']);
            return $r;
        }, $rows);
    }

    public function buildAdminFilters(array $filters = []): array
    {
        $payment = strtolower(trim((string)($filters['payment_method'] ?? '')));
        if (!in_array($payment, ['carte', 'virement'], true)) $payment = '';
        return [
            'status'         => trim((string)($filters['status'] ?? '')) === ''
                                 ? '' : $this->sanitizeDonStatus((string)$filters['status']),
            'query'          => trim((string)($filters['query']          ?? '')),
            'cagnotte_query' => trim((string)($filters['cagnotte_query'] ?? '')),
            'donor_query'    => trim((string)($filters['donor_query']    ?? '')),
            'payment_method' => $payment,
            'date_start'     => trim((string)($filters['date_start']     ?? '')),
            'date_end'       => trim((string)($filters['date_end']       ?? '')),
        ];
    }

    private function buildDonFiltersSql(array $filters, array &$params): array
    {
        $filters = $this->buildAdminFilters($filters);
        $where   = [];
        $params  = [];

        if ($filters['status'] !== '') {
            $where[]          = "d.statut = :statut";
            $params['statut'] = $filters['status'];
        }
        if ($filters['query'] !== '') {
            $where[]                    = "(c.titre LIKE :gq_t OR CONCAT(COALESCE(u.nom,''),' ',COALESCE(u.prenom,'')) LIKE :gq_d)";
            $gv                         = '%' . $filters['query'] . '%';
            $params['gq_t']             = $gv;
            $params['gq_d']             = $gv;
        }
        if ($filters['cagnotte_query'] !== '') {
            $where[]                      = "c.titre LIKE :cq";
            $params['cq']                 = '%' . $filters['cagnotte_query'] . '%';
        }
        if ($filters['donor_query'] !== '') {
            $where[]                     = "CONCAT(COALESCE(u.nom,''),' ',COALESCE(u.prenom,'')) LIKE :dq";
            $params['dq']                = '%' . $filters['donor_query'] . '%';
        }
        if ($filters['payment_method'] !== '') {
            $where[]                        = "d.moyen_paiement = :pm";
            $params['pm']                   = $filters['payment_method'];
        }
        if ($filters['date_start'] !== '') {
            $where[]                    = "DATE(d.date_don) >= :ds";
            $params['ds']               = $filters['date_start'];
        }
        if ($filters['date_end'] !== '') {
            $where[]                  = "DATE(d.date_don) <= :de";
            $params['de']             = $filters['date_end'];
        }
        return $where;
    }

    /**
     * Create a new donation (pending state).
     * For virement: pass id_compte and devise_don in $data; this method will call DonVirementService.
     */
    public function ajouterDon(array $data, int $donateurId): bool
    {
        if (!isset($data['montant']) || !is_numeric($data['montant']) || $data['montant'] <= 0) {
            $this->lastError = 'Montant invalide'; return false;
        }
        if (!isset($data['id_cagnotte']) || !is_numeric($data['id_cagnotte'])) {
            $this->lastError = 'Cagnotte invalide'; return false;
        }

        $moyen_paiement = (isset($data['moyen_paiement']) && in_array($data['moyen_paiement'], ['carte', 'virement'], true))
            ? $data['moyen_paiement'] : 'carte';
        $message = isset($data['message']) ? htmlspecialchars(trim($data['message'])) : null;

        $cagCtrl = new CagnotteController();
        if ($cagCtrl->isUserAssociation($donateurId)) {
            $this->lastError = "Les associations ne peuvent pas faire un don";
            return false;
        }

        $pdo = Config::getConnexion();

        // For virement: deduct from account in a transaction
        if ($moyen_paiement === 'virement') {
            $idCompte   = isset($data['id_compte'])   ? (int)$data['id_compte']  : 0;
            $deviseDon  = strtoupper(trim((string)($data['devise_don'] ?? 'TND')));
            if (!in_array($deviseDon, ['TND', 'EUR', 'USD', 'GBP'], true)) {
                $deviseDon = 'TND';
            }

            if ($idCompte <= 0) {
                $this->lastError = "Veuillez sélectionner un compte bancaire pour le virement.";
                return false;
            }

            // Insert don in pending state first, then process virement transactionally
            $pdo->beginTransaction();
            try {
                $sql  = "INSERT INTO don (id_cagnotte, id_donateur, montant, est_anonyme, message, moyen_paiement, statut, date_don)
                         VALUES (:id_cagnotte, :id_donateur, :montant, 0, :message, 'virement', 'en_attente', NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id_cagnotte' => (int)$data['id_cagnotte'],
                    'id_donateur' => $donateurId,
                    'montant'     => $data['montant'],
                    'message'     => $message,
                ]);
                $idDon = (int)$pdo->lastInsertId();

                DonVirementService::processVirementDon(
                    $pdo,
                    $idDon,
                    $donateurId,
                    $idCompte,
                    (float)$data['montant'],
                    $deviseDon
                );

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $this->lastError = $e->getMessage();
                return false;
            }
        } else {
            // Card payment — just register as pending
            $sql  = "INSERT INTO don (id_cagnotte, id_donateur, montant, est_anonyme, message, moyen_paiement, statut, date_don)
                     VALUES (:id_cagnotte, :id_donateur, :montant, 0, :message, :moyen_paiement, 'en_attente', NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id_cagnotte'    => (int)$data['id_cagnotte'],
                'id_donateur'    => $donateurId,
                'montant'        => $data['montant'],
                'message'        => $message,
                'moyen_paiement' => $moyen_paiement,
            ]);
        }

        try {
            (new AchievementService())->runPostDonationCreated($donateurId);
        } catch (Throwable $e) {
            // Non-blocking
        }

        return true;
    }

    public function supprimerDon(int $id): bool
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare("DELETE FROM don WHERE id_don = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function modifierDon(int $id, array $data): bool
    {
        if (!isset($data['montant']) || $data['montant'] <= 0) {
            $this->lastError = 'Montant invalide'; return false;
        }
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare("UPDATE don SET montant = :montant WHERE id_don = :id");
        return $stmt->execute(['montant' => $data['montant'], 'id' => $id]);
    }

    public function getAllDons(): array
    {
        return $this->getFilteredDons();
    }

    public function getFilteredDons(array $filters = []): array
    {
        $pdo    = Config::getConnexion();
        $params = [];
        $where  = $this->buildDonFiltersSql($filters, $params);
        $sql    = "SELECT d.*, c.titre as cagnotte_titre, u.nom, u.prenom
                   FROM don d
                   INNER JOIN cagnotte c ON d.id_cagnotte = c.id_cagnotte
                   LEFT JOIN utilisateur u ON d.id_donateur = u.id ";
        if (!empty($where)) $sql .= "WHERE " . implode(' AND ', $where) . " ";
        $sql   .= "ORDER BY d.date_don DESC";
        $stmt   = $pdo->prepare($sql);
        $stmt->execute($params);
        return $this->normalizeDonRows($stmt->fetchAll());
    }

    public function getDonsByCagnotte(int $id_cagnotte): array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare(
            "SELECT d.*, u.nom, u.prenom
             FROM don d
             LEFT JOIN utilisateur u ON d.id_donateur = u.id
             WHERE d.id_cagnotte = :id_cagnotte
             ORDER BY d.date_don DESC"
        );
        $stmt->execute(['id_cagnotte' => $id_cagnotte]);
        return $this->normalizeDonRows($stmt->fetchAll());
    }

    public function getDonsByDonateur(int $id_donateur): array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare(
            "SELECT d.*, c.titre as cagnotte_titre
             FROM don d
             LEFT JOIN cagnotte c ON d.id_cagnotte = c.id_cagnotte
             WHERE d.id_donateur = :id_donateur
             ORDER BY d.date_don DESC"
        );
        $stmt->execute(['id_donateur' => $id_donateur]);
        return $this->normalizeDonRows($stmt->fetchAll());
    }

    public function getConfirmedStats(): array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->query("SELECT COUNT(*) as nb_conf, COALESCE(SUM(montant),0) as total_conf FROM don WHERE statut = 'confirme'");
        return $stmt->fetch() ?: ['nb_conf' => 0, 'total_conf' => 0];
    }

    public function getDonStatusCounts(): array
    {
        $pdo    = Config::getConnexion();
        $stmt   = $pdo->query("SELECT statut, COUNT(*) as total FROM don GROUP BY statut");
        $counts = ['en_attente' => 0, 'confirme' => 0, 'refuse' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $s          = $this->sanitizeDonStatus($row['statut'] ?? 'en_attente');
            $counts[$s] = (int)($row['total'] ?? 0);
        }
        return $counts;
    }

    public function getPaymentMethodStats(): array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->query("SELECT moyen_paiement, COUNT(*) as total, COALESCE(SUM(montant), 0) as montant_total
                              FROM don GROUP BY moyen_paiement ORDER BY total DESC");
        $out  = [];
        foreach ($stmt->fetchAll() as $row) {
            $method = trim((string)($row['moyen_paiement'] ?? '')) ?: 'inconnu';
            $out[]  = [
                'moyen_paiement' => $method,
                'total'          => (int)($row['total'] ?? 0),
                'montant_total'  => (float)($row['montant_total'] ?? 0),
            ];
        }
        return $out;
    }

    public function getTopDonCagnottes(int $limit = 5): array
    {
        $pdo   = Config::getConnexion();
        $limit = max(1, $limit);
        $stmt  = $pdo->prepare(
            "SELECT c.titre, COUNT(d.id_don) as nb_dons,
                    COALESCE(SUM(CASE WHEN d.statut = 'confirme' THEN d.montant ELSE 0 END), 0) as total_confirme
             FROM cagnotte c
             LEFT JOIN don d ON d.id_cagnotte = c.id_cagnotte
             GROUP BY c.id_cagnotte, c.titre
             ORDER BY total_confirme DESC, nb_dons DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getGlobalStats(): array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->query(
            "SELECT COUNT(*) as total_dons,
                    COALESCE(SUM(montant), 0) as montant_total,
                    COALESCE(SUM(CASE WHEN statut = 'confirme' THEN montant ELSE 0 END), 0) as montant_confirme
             FROM don"
        );
        $row = $stmt->fetch() ?: [];
        return [
            'total_dons'       => (int)($row['total_dons'] ?? 0),
            'montant_total'    => (float)($row['montant_total'] ?? 0),
            'montant_confirme' => (float)($row['montant_confirme'] ?? 0),
        ];
    }

    public function confirmerDon(int $id): bool
    {
        $pdo = Config::getConnexion();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT statut, montant, id_cagnotte FROM don WHERE id_don = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $don = $stmt->fetch();
            if (!$don) { $pdo->rollBack(); return false; }

            $currentStatus = $this->sanitizeDonStatus($don['statut']);
            if ($currentStatus === 'confirme') { $pdo->commit(); return true; }
            if ($currentStatus !== 'en_attente') { $pdo->rollBack(); return false; }

            $pdo->prepare("UPDATE don SET statut = 'confirme' WHERE id_don = :id")
                ->execute(['id' => $id]);

            $pdo->prepare("UPDATE cagnotte SET montant_collecte = COALESCE(montant_collecte,0) + :montant WHERE id_cagnotte = :id_cagnotte")
                ->execute(['montant' => $don['montant'], 'id_cagnotte' => $don['id_cagnotte']]);

            $pdo->commit();

            try {
                (new AchievementService())->runPostDonationConfirmed($id);
            } catch (Throwable $e) {
                // Non-blocking
            }

            $this->sendDonationConfirmedEmail($id, (int)$don['id_cagnotte'], (float)$don['montant']);

            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[DonController] confirmerDon error: ' . $e->getMessage());
            return false;
        }
    }

    private function sendDonationConfirmedEmail(int $donId, int $cagnotteId, float $montant): void
    {
        try {
            $pdo = Config::getConnexion();

            $chk = $pdo->prepare("SELECT email_sent FROM don WHERE id_don = :id LIMIT 1");
            $chk->execute(['id' => $donId]);
            $flag = $chk->fetch();
            if ($flag && (int)$flag['email_sent'] === 1) return;

            $stmtDon = $pdo->prepare("SELECT id_don, montant, date_don, id_cagnotte, id_donateur FROM don WHERE id_don = :id LIMIT 1");
            $stmtDon->execute(['id' => $donId]);
            $donRow = $stmtDon->fetch();
            if (!$donRow) return;

            $stmtCag = $pdo->prepare("SELECT id_cagnotte, titre, objectif_montant, montant_collecte, id_createur FROM cagnotte WHERE id_cagnotte = :id LIMIT 1");
            $stmtCag->execute(['id' => $cagnotteId]);
            $cagnotteRow = $stmtCag->fetch();
            if (!$cagnotteRow) return;

            $totalCollecte = (float)($cagnotteRow['montant_collecte'] ?? 0);
            $objectif      = (float)($cagnotteRow['objectif_montant'] ?? 0);
            if ($objectif <= 0 || $totalCollecte < $objectif) return;

            $stmtAssoc = $pdo->prepare("SELECT id, nom, prenom, email FROM utilisateur WHERE id = :id LIMIT 1");
            $stmtAssoc->execute(['id' => $cagnotteRow['id_createur']]);
            $associationRow = $stmtAssoc->fetch();
            if (!$associationRow) return;

            $donateurRow = null;
            if (!empty($donRow['id_donateur'])) {
                $stmtDon2 = $pdo->prepare("SELECT id, nom, prenom, email FROM utilisateur WHERE id = :id LIMIT 1");
                $stmtDon2->execute(['id' => $donRow['id_donateur']]);
                $donateurRow = $stmtDon2->fetch() ?: null;
            }

            $emailService = new DonEmailService();
            $sent         = $emailService->sendDonationNotification($donRow, $cagnotteRow, $associationRow, $donateurRow, $totalCollecte);

            if ($sent) {
                $pdo->prepare("UPDATE don SET email_sent = 1, email_sent_at = NOW() WHERE id_don = :id")
                    ->execute(['id' => $donId]);
            }
        } catch (Throwable $e) {
            error_log('[DonController] sendDonationConfirmedEmail error for don #' . $donId . ': ' . $e->getMessage());
        }
    }

    public function refuserDon(int $id): bool
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare("UPDATE don SET statut = 'refuse' WHERE id_don = :id AND statut = 'en_attente'");
        return $stmt->execute(['id' => $id]);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
