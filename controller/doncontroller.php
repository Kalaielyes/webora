<?php
require_once __DIR__ . "/../model/don.php";
require_once __DIR__ . "/../model/config.php";
require_once __DIR__ . "/cagnottecontroller.php";
require_once __DIR__ . "/AchievementService.php";

class doncontroller {

    /**
     * Last error message for the controller (kept for callers to inspect)
     * @var string|null
     */
    private $lastError = null;

    private const ALLOWED_STATUSES = ['en_attente', 'confirme', 'refuse'];

    private function normalizeDonStatus($status) {
        $s = strtolower(trim((string)$status));
        if ($s === 'en attente') return 'en_attente';
        if ($s === 'confirmé') return 'confirme';
        if ($s === 'refusé') return 'refuse';
        if ($s === 'remboursé' || $s === 'rembourse') return 'refuse';
        return $s;
    }

    private function sanitizeDonStatus($status) {
        $normalized = $this->normalizeDonStatus($status);
        return in_array($normalized, self::ALLOWED_STATUSES, true) ? $normalized : 'en_attente';
    }

    public function getDonStatusLabel($status) {
        $normalized = $this->sanitizeDonStatus($status);
        $labels = [
            'en_attente' => 'En attente',
            'confirme' => 'Confirmé',
            'refuse' => 'Refusé'
        ];
        return $labels[$normalized];
    }

    public function getDonStatusBadgeClass($status) {
        $normalized = $this->sanitizeDonStatus($status);
        $classes = [
            'en_attente' => 'b-attente',
            'confirme' => 'b-confirme',
            'refuse' => 'b-danger'
        ];
        return $classes[$normalized];
    }

    private function normalizeDonRows($rows) {
        $out = [];
        foreach ($rows as $r) {
            if (isset($r['statut'])) {
                $r['statut'] = $this->sanitizeDonStatus($r['statut']);
            }
            $out[] = $r;
        }
        return $out;
    }

    public function buildAdminFilters($filters = []) {
        $status = trim((string)($filters['status'] ?? ''));
        $query = trim((string)($filters['query'] ?? ''));
        $cagnotteQuery = trim((string)($filters['cagnotte_query'] ?? ''));
        $donorQuery = trim((string)($filters['donor_query'] ?? ''));
        $payment = strtolower(trim((string)($filters['payment_method'] ?? '')));
        $dateStart = trim((string)($filters['date_start'] ?? ''));
        $dateEnd = trim((string)($filters['date_end'] ?? ''));

        if (!in_array($payment, ['carte', 'virement'], true)) {
            $payment = '';
        }

        return [
            'status' => $status === '' ? '' : $this->sanitizeDonStatus($status),
            'query' => $query,
            'cagnotte_query' => $cagnotteQuery,
            'donor_query' => $donorQuery,
            'payment_method' => $payment,
            'date_start' => $dateStart,
            'date_end' => $dateEnd
        ];
    }

    private function buildDonFiltersSql($filters, &$params) {
        $filters = $this->buildAdminFilters($filters);
        $where = [];
        $params = [];

        if ($filters['status'] !== '') {
            $where[] = "d.statut = :statut";
            $params['statut'] = $filters['status'];
        }
        if ($filters['query'] !== '') {
            $where[] = "(c.titre LIKE :global_query_titre OR CONCAT(COALESCE(u.nom, ''), ' ', COALESCE(u.prenom, '')) LIKE :global_query_donateur)";
            $globalQueryValue = '%' . $filters['query'] . '%';
            $params['global_query_titre'] = $globalQueryValue;
            $params['global_query_donateur'] = $globalQueryValue;
        }
        if ($filters['cagnotte_query'] !== '') {
            $where[] = "c.titre LIKE :cagnotte_query";
            $params['cagnotte_query'] = '%' . $filters['cagnotte_query'] . '%';
        }
        if ($filters['donor_query'] !== '') {
            $where[] = "CONCAT(COALESCE(u.nom, ''), ' ', COALESCE(u.prenom, '')) LIKE :donor_query";
            $params['donor_query'] = '%' . $filters['donor_query'] . '%';
        }
        if ($filters['payment_method'] !== '') {
            $where[] = "d.moyen_paiement = :payment_method";
            $params['payment_method'] = $filters['payment_method'];
        }
        if ($filters['date_start'] !== '') {
            $where[] = "DATE(d.date_don) >= :date_start";
            $params['date_start'] = $filters['date_start'];
        }
        if ($filters['date_end'] !== '') {
            $where[] = "DATE(d.date_don) <= :date_end";
            $params['date_end'] = $filters['date_end'];
        }

        return $where;
    }

    public function ajouterDon($data, $donateurId = null) {
        if (!isset($data['montant']) || !is_numeric($data['montant']) || $data['montant'] <= 0) {
            $this->lastError = 'Montant invalide';
            return false;
        }
        if (!isset($data['id_cagnotte']) || !is_numeric($data['id_cagnotte'])) {
            $this->lastError = 'Cagnotte invalide';
            return false;
        }
        
        $moyen_paiement = isset($data['moyen_paiement']) && in_array($data['moyen_paiement'], ['carte', 'virement']) ? $data['moyen_paiement'] : 'carte';
        $est_anonyme = 0;
        $message = isset($data['message']) ? htmlspecialchars($data['message']) : null;

        $cagnotteCtrl = new cagnottecontroller();
        $id_donateur = is_numeric($donateurId) ? (int)$donateurId : (int)$cagnotteCtrl->ensureDefaultUser();

        if ($cagnotteCtrl->isUserAssociation($id_donateur)) {
            $this->lastError = "Les associations ne peuvent pas faire un don";
            return false;
        }

        $pdo = Config::getConnexion();

        $sql = "INSERT INTO don (id_cagnotte, id_donateur, montant, est_anonyme, message, moyen_paiement, statut, date_don)
                VALUES (:id_cagnotte, :id_donateur, :montant, :est_anonyme, :message, :moyen_paiement, 'en_attente', NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_cagnotte' => $data['id_cagnotte'],
            'id_donateur' => $id_donateur,
            'montant' => $data['montant'],
            'est_anonyme' => $est_anonyme,
            'message' => $message,
            'moyen_paiement' => $moyen_paiement
        ]);

        // Auto-check hook on donation creation (currently a no-op by business rule).
        try {
            $achievementService = new AchievementService();
            $achievementService->runPostDonationCreated($id_donateur);
        } catch (Throwable $e) {
            // Keep donation flow resilient even if achievement checks fail.
        }
        
        return true;
    }

    public function supprimerDon($id) {
        if (!is_numeric($id)) return false;
        $pdo = Config::getConnexion();
        $sql = "DELETE FROM don WHERE id_don = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function modifierDon($id, $data) {
        if (!is_numeric($id)) { $this->lastError = 'ID invalide'; return false; }
        if (!isset($data['montant']) || $data['montant'] <= 0) { $this->lastError = 'Montant invalide'; return false; }

        $pdo = Config::getConnexion();
        $sql = "UPDATE don SET montant = :montant WHERE id_don = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'montant' => $data['montant'],
            'id' => $id
        ]);
    }
    
    public function getAllDons() {
        return $this->getFilteredDons();
    }

    public function getFilteredDons($filters = []) {
        $pdo = Config::getConnexion();
        $params = [];
        $where = $this->buildDonFiltersSql($filters, $params);
        $sql = "SELECT d.*, c.titre as cagnotte_titre, u.nom, u.prenom 
                FROM don d
                INNER JOIN cagnotte c ON d.id_cagnotte = c.id_cagnotte
                LEFT JOIN utilisateur u ON d.id_donateur = u.id ";
        if (!empty($where)) {
            $sql .= "WHERE " . implode(' AND ', $where) . " ";
        }
        $sql .= "ORDER BY d.date_don DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $this->normalizeDonRows($stmt->fetchAll());
    }
    
    public function getDonsByCagnotte($id_cagnotte) {
        $pdo = Config::getConnexion();
        $sql = "SELECT d.*, u.nom, u.prenom 
                FROM don d
                LEFT JOIN utilisateur u ON d.id_donateur = u.id
                WHERE d.id_cagnotte = :id_cagnotte
                ORDER BY d.date_don DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_cagnotte' => $id_cagnotte]);
        return $this->normalizeDonRows($stmt->fetchAll());
    }

    public function getDonsByDonateur($id_donateur) {
        $pdo = Config::getConnexion();
        $sql = "SELECT d.*, c.titre as cagnotte_titre
                FROM don d
                LEFT JOIN cagnotte c ON d.id_cagnotte = c.id_cagnotte
                WHERE d.id_donateur = :id_donateur
                ORDER BY d.date_don DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_donateur' => $id_donateur]);
        return $this->normalizeDonRows($stmt->fetchAll());
    }

    public function getConfirmedStats() {
        $pdo = Config::getConnexion();
        $sql = "SELECT COUNT(*) as nb_conf, COALESCE(SUM(montant),0) as total_conf FROM don WHERE statut = 'confirme'";
        $stmt = $pdo->query($sql);
        return $stmt->fetch();
    }

    public function getDonStatusCounts() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT statut, COUNT(*) as total FROM don GROUP BY statut");
        $counts = [
            'en_attente' => 0,
            'confirme' => 0,
            'refuse' => 0
        ];
        foreach ($stmt->fetchAll() as $row) {
            $status = $this->sanitizeDonStatus($row['statut'] ?? 'en_attente');
            $counts[$status] = (int)($row['total'] ?? 0);
        }
        return $counts;
    }

    public function getPaymentMethodStats() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT moyen_paiement, COUNT(*) as total, COALESCE(SUM(montant), 0) as montant_total FROM don GROUP BY moyen_paiement ORDER BY total DESC, moyen_paiement ASC");
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $method = trim((string)($row['moyen_paiement'] ?? ''));
            if ($method === '') {
                $method = 'inconnu';
            }
            $out[] = [
                'moyen_paiement' => $method,
                'total' => (int)($row['total'] ?? 0),
                'montant_total' => (float)($row['montant_total'] ?? 0)
            ];
        }
        return $out;
    }

    public function getTopDonCagnottes($limit = 5) {
        $pdo = Config::getConnexion();
        $limit = max(1, (int)$limit);
        $stmt = $pdo->prepare("SELECT c.titre, COUNT(d.id_don) as nb_dons, COALESCE(SUM(CASE WHEN d.statut = 'confirme' THEN d.montant ELSE 0 END), 0) as total_confirme
                FROM cagnotte c
                LEFT JOIN don d ON d.id_cagnotte = c.id_cagnotte
                GROUP BY c.id_cagnotte, c.titre
                ORDER BY total_confirme DESC, nb_dons DESC, c.titre ASC
                LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getGlobalStats() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT COUNT(*) as total_dons, COALESCE(SUM(montant), 0) as montant_total, COALESCE(SUM(CASE WHEN statut = 'confirme' THEN montant ELSE 0 END), 0) as montant_confirme FROM don");
        $row = $stmt->fetch() ?: [];
        return [
            'total_dons' => (int)($row['total_dons'] ?? 0),
            'montant_total' => (float)($row['montant_total'] ?? 0),
            'montant_confirme' => (float)($row['montant_confirme'] ?? 0)
        ];
    }
    
    public function confirmerDon($id) {
        if (!is_numeric($id)) return false;
        $pdo = Config::getConnexion();
        try {
            $pdo->beginTransaction();
            // lock the donation row and read current status/amount
            $stmt = $pdo->prepare("SELECT statut, montant, id_cagnotte FROM don WHERE id_don = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $don = $stmt->fetch();
            if (!$don) { $pdo->rollBack(); return false; }
            $currentStatus = $this->sanitizeDonStatus($don['statut']);
            if ($currentStatus === 'confirme') { $pdo->commit(); return true; }
            if ($currentStatus !== 'en_attente') { $pdo->rollBack(); return false; }

            // mark donation as confirmed
            $stmt = $pdo->prepare("UPDATE don SET statut = 'confirme' WHERE id_don = :id");
            $stmt->execute(['id' => $id]);

            // update cagnotte total in a safe way (montant_collecte column)
            $stmt2 = $pdo->prepare("UPDATE cagnotte SET montant_collecte = COALESCE(montant_collecte,0) + :montant WHERE id_cagnotte = :id_cagnotte");
            $stmt2->execute(['montant' => $don['montant'], 'id_cagnotte' => $don['id_cagnotte']]);

            $pdo->commit();

            try {
                $achievementService = new AchievementService();
                $achievementService->runPostDonationConfirmed((int)$id);
            } catch (Throwable $e) {
                // Keep confirmation successful even if achievement processing fails.
            }
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return false;
        }
    }

    public function refuserDon($id) {
        if (!is_numeric($id)) return false;
        $pdo = Config::getConnexion();
        $stmt = $pdo->prepare("UPDATE don SET statut = 'refuse' WHERE id_don = :id AND statut = 'en_attente'");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Return the last error message (if any) produced by this controller.
     * @return string|null
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }
}
?>