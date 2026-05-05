<?php

require_once __DIR__ . '/helpers/config.local.php';
require_once __DIR__ . '/../model/chequier.php';
require_once __DIR__ . '/helpers/mailer.php';

class ChequierController {

    public function listChequiers() {
        $db = Config::getConnexion();
        $sql = "SELECT ch.*, d.`nom et prenom`, c.iban 
                FROM chequier ch
                LEFT JOIN demande_chequier d ON ch.id_demande = d.id_demande
                LEFT JOIN comptebancaire c ON ch.id_Compte = c.id_Compte
                ORDER BY ch.id_chequier DESC";
        try {
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addChequier(Chequier $chequier) {
        $db = Config::getConnexion();
        $sql = "INSERT INTO chequier (numero_chequier, date_creation, date_expiration, statut, nombre_feuilles, id_demande, id_Compte) 
                VALUES (:numero, :creation, :expiration, :statut, :nb, :demande, :compte)";

        $stmt = $db->prepare($sql);
        try {
            $stmt->execute([
                ':numero'     => $chequier->getNumeroChequier(),
                ':creation'   => $chequier->getDateCreation(),
                ':expiration' => $chequier->getDateExpiration(),
                ':statut'     => $chequier->getStatut(),
                ':nb'         => $chequier->getNombreFeuilles(),
                ':demande'    => $chequier->getIdDemande(),
                ':compte'     => $chequier->getIdCompte()
            ]);

            // ✅ Email rappel uniquement si expiration <= 15 jours
            $dateExp = new DateTime($chequier->getDateExpiration());
            $today   = new DateTime();
            $diff    = $today->diff($dateExp)->days;

            if ($diff <= 15 && $dateExp >= $today) {
                $this->envoyerEmailRappelExpiration($chequier->getIdDemande());
            }

            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    private function envoyerEmailRappelExpiration($idDemande) {
        $db = Config::getConnexion();

        $sql = "SELECT 
                    ch.numero_chequier,
                    ch.date_expiration,
                    ch.nombre_feuilles,
                    d.`nom et prenom` AS nom_et_prenom,
                    d.email
                FROM chequier ch
                LEFT JOIN demande_chequier d ON ch.id_demande = d.id_demande
                WHERE ch.id_demande = :id
                AND d.email IS NOT NULL
                ORDER BY ch.id_chequier DESC
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $idDemande]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data || empty($data['email'])) return;

        $html = buildChequierEmail(
            $data['nom_et_prenom'],
            $data['numero_chequier'],
            $data['date_expiration'],
            $data['nombre_feuilles'],
            'rappel'
        );

        sendMail(
            $data['email'],
            $data['nom_et_prenom'],
            "⚠️ Rappel expiration chéquier",
            $html
        );
    }

    public function deleteChequier($id) {
        $db = Config::getConnexion();
        $sql = "DELETE FROM chequier WHERE id_chequier = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function updateChequier(Chequier $chequier) {
        $db = Config::getConnexion();
        $sql = "UPDATE chequier 
                SET date_expiration = :expiration, 
                    statut = :statut, 
                    nombre_feuilles = :nb
                WHERE id_chequier = :id";

        $stmt = $db->prepare($sql);
        try {
            return $stmt->execute([
                ':expiration' => $chequier->getDateExpiration(),
                ':statut'     => $chequier->getStatut(),
                ':nb'         => $chequier->getNombreFeuilles(),
                ':id'         => $chequier->getIdChequier()
            ]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function getChequierById($id) {
        $db = Config::getConnexion();
        $sql = "SELECT * FROM chequier WHERE id_chequier = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getChequiersExpirantMoinsDe15Jours(): array {
        $db = Config::getConnexion();

        $sql = "SELECT 
                    ch.numero_chequier,
                    ch.date_expiration,
                    ch.nombre_feuilles,
                    d.`nom et prenom` AS nom_et_prenom,
                    d.email
                FROM chequier ch
                LEFT JOIN demande_chequier d ON ch.id_demande = d.id_demande
                WHERE ch.date_expiration BETWEEN CURDATE()
                    AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
                AND ch.statut = 'actif'
                AND d.email IS NOT NULL";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[Rappel] Erreur SQL: ' . $e->getMessage());
            return [];
        }
    }

    public function getChequiersExpirantDans15Jours() {
        $db = Config::getConnexion();
        $sql = "SELECT ch.*, d.`nom et prenom` AS nom, d.email, ch.date_expiration 
                FROM chequier ch
                LEFT JOIN demande_chequier d ON ch.id_demande = d.id_demande
                WHERE ch.date_expiration = DATE_ADD(CURDATE(), INTERVAL 15 DAY)";
        try {
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}