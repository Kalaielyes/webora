<?php
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/chequier.php';

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
            return true;
        } catch (PDOException $e) {
            throw $e;
        }
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
}
