<?php
require_once __DIR__ . "/../model/don.php";
require_once __DIR__ . "/../model/config.php";
require_once __DIR__ . "/cagnottecontroller.php";

class doncontroller {

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

    public function ajouterDon($data, $donateurId = null) {
        if (!isset($data['montant']) || !is_numeric($data['montant']) || $data['montant'] <= 0) {
            die("Montant invalide");
        }
        if (!isset($data['id_cagnotte']) || !is_numeric($data['id_cagnotte'])) {
            die("Cagnotte invalide");
        }
        
        $moyen_paiement = isset($data['moyen_paiement']) && in_array($data['moyen_paiement'], ['carte', 'virement']) ? $data['moyen_paiement'] : 'carte';
        $est_anonyme = 0;
        $message = isset($data['message']) ? htmlspecialchars($data['message']) : null;

        $cagnotteCtrl = new cagnottecontroller();
        $id_donateur = is_numeric($donateurId) ? (int)$donateurId : (int)$cagnotteCtrl->ensureDefaultUser();

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
        if (!is_numeric($id)) die("ID invalide");
        if (!isset($data['montant']) || $data['montant'] <= 0) die("Montant invalide");

        $pdo = Config::getConnexion();
        $sql = "UPDATE don SET montant = :montant WHERE id_don = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'montant' => $data['montant'],
            'id' => $id
        ]);
    }
    
    public function getAllDons() {
        $pdo = Config::getConnexion();
        $sql = "SELECT d.*, c.titre as cagnotte_titre, u.nom, u.prenom 
                FROM don d
                INNER JOIN cagnotte c ON d.id_cagnotte = c.id_cagnotte
                LEFT JOIN utilisateur u ON d.id_donateur = u.id
                ORDER BY d.date_don DESC";
        $stmt = $pdo->query($sql);
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
}
?>