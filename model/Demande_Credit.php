<?php
require_once __DIR__ . '/config.php';

class DemandeCredit
{

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = config::getConnexion();
    }

    public function getAll(): array
    {
        return $this->pdo
            ->query("SELECT * FROM DemandeCredit ORDER BY date_demande DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM DemandeCredit WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        $all = $this->getAll();
        return [
            'total' => count($all),
            'attente' => count(array_filter($all, fn($d) => $d['resultat'] === 'en_attente')),
            'approuvee' => count(array_filter($all, fn($d) => $d['resultat'] === 'approuvee')),
            'refusee' => count(array_filter($all, fn($d) => $d['resultat'] === 'refusee')),
            'encours' => array_sum(
                array_column(
                    array_filter($all, fn($d) => $d['resultat'] === 'approuvee'),
                    'montant'
                )
            ),
        ];
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO DemandeCredit
    (montant, duree_mois, taux_interet, statut, resultat, motif_resultat, 
     date_demande, client_id, compte_id, country_code, ip_client)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['montant'],
            $data['duree_mois'],
            $data['taux_interet'],
            $data['statut'],
            $data['resultat'],
            $data['motif_resultat'],
            $data['date_demande'],
            $data['client_id'],
            $data['compte_id'],
            $data['country_code'] ?? 'TN',
            $data['ip_client'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE DemandeCredit SET
                montant = ?, duree_mois = ?, taux_interet = ?,
                statut = ?, resultat = ?, motif_resultat = ?, date_traitement = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['montant'],
            $data['duree_mois'],
            $data['taux_interet'],
            $data['statut'],
            $data['resultat'],
            $data['motif_resultat'],
            $data['date_traitement'] ?: null,
            $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM DemandeCredit WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ── Validation serveur ────────────────────────────────────────────────────
    // Retourne tableau associatif champ => message. Vide = données valides.
    public function validate(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Montant
        $montant = $data['montant'] ?? '';
        if (!is_numeric($montant) || (float) $montant < 1 || (float) $montant > 1_000_000)
            $errors['montant'] = 'Le montant doit être compris entre 1 et 1 000 000 TND.';

        // Durée
        $duree = $data['duree_mois'] ?? '';
        if (!is_numeric($duree) || (int) $duree < 6 || (int) $duree > 360 || (float) $duree != (int) $duree)
            $errors['duree_mois'] = 'La durée doit être un entier entre 6 et 360 mois.';

        // Taux
        $taux = $data['taux_interet'] ?? '';
        if (!is_numeric($taux) || (float) $taux < 0 || (float) $taux > 30)
            $errors['taux_interet'] = "Le taux d'intérêt doit être compris entre 0 et 30 %.";

        // Statut
        if (!in_array($data['statut'] ?? '', ['en_cours', 'traitee', 'annulee'], true))
            $errors['statut'] = 'Statut invalide. Valeurs acceptées : en_cours, traitee, annulee.';

        // Résultat
        if (!in_array($data['resultat'] ?? '', ['approuvee', 'refusee', 'en_attente'], true))
            $errors['resultat'] = 'Résultat invalide. Valeurs acceptées : approuvee, refusee, en_attente.';

        // Date de demande (création uniquement)
        if (!$isUpdate) {
            $date = trim($data['date_demande'] ?? '');
            if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date))
                $errors['date_demande'] = 'La date de demande est obligatoire (format AAAA-MM-JJ).';
            elseif (strtotime($date) > strtotime(date('Y-m-d')))
                $errors['date_demande'] = 'La date de demande ne peut pas être dans le futur.';
        }

        // Motif obligatoire si décision finale
        $resultat = $data['resultat'] ?? 'en_attente';
        if ($resultat !== 'en_attente' && empty(trim($data['motif_resultat'] ?? '')))
            $errors['motif_resultat'] = 'Le motif de décision est obligatoire lorsque le résultat est approuvé ou refusé.';

        // Longueur motif
        if (!empty($data['motif_resultat']) && mb_strlen($data['motif_resultat']) > 500)
            $errors['motif_resultat'] = 'Le motif ne peut pas dépasser 500 caractères.';

        // IDs client / compte (création uniquement)
        if (!$isUpdate) {
            if (empty($data['client_id']) || (int) $data['client_id'] <= 0)
                $errors['client_id'] = 'Client introuvable. Veuillez vous reconnecter.';
            if (empty($data['compte_id']) || (int) $data['compte_id'] <= 0)
                $errors['compte_id'] = 'Compte bancaire introuvable.';
        }

        return $errors;
    }
}