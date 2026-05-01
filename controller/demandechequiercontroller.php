<?php
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/demandechequier.php';
require_once 'smscontroller.php';

class DemandeChequierController {

    public function listDemandes() {
        $db = Config::getConnexion();
        $sql = "SELECT d.*, c.iban 
                FROM demande_chequier d 
                LEFT JOIN comptebancaire c ON d.id_compte = c.id_Compte 
                ORDER BY d.date_demande DESC";
        try {
            return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function deleteDemande($id) {
        $db = Config::getConnexion();
        $sql = "DELETE FROM demande_chequier WHERE id_demande = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function addDemande(DemandeChequier $demande) {
        try {
            $db = Config::getConnexion();
            $sql = "INSERT INTO demande_chequier 
            (`nom et prenom`, id_compte, motif, type_chequier, nombre_cheques, 
            montant_max_par_cheque, mode_reception, adresse_agence, 
            telephone, email, commentaire, statut) 
            VALUES (:nom, :compte, :motif, :type, :nb, :montant, 
            :mode, :adresse, :tel, :email, :comm, :statut)";
            
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':nom' => $demande->getNomEtPrenom(),
                ':compte' => $demande->getIdCompte(),
                ':motif' => $demande->getMotif(),
                ':type' => $demande->getTypeChequier(),
                ':nb' => $demande->getNombreCheques(),
                ':montant' => $demande->getMontantMaxParCheque(),
                ':mode' => $demande->getModeReception(),
                ':adresse' => $demande->getAdresseAgence(),
                ':tel' => $demande->getTelephone(),
                ':email' => $demande->getEmail(),
                ':comm' => $demande->getCommentaire(),
                ':statut' => $demande->getStatut()
            ]);
        } catch (PDOException $e) {
            error_log("Erreur addDemande: " . $e->getMessage());
            return false;
        }
    }

    public function updateDemande(DemandeChequier $demande, $id) {
        try {
            $db = Config::getConnexion();
            $sql = "UPDATE demande_chequier SET 
                    `nom et prenom` = :nom,
                    id_compte = :compte,
                    motif = :motif,
                    type_chequier = :type,
                    nombre_cheques = :nb,
                    montant_max_par_cheque = :montant,
                    mode_reception = :mode,
                    adresse_agence = :adresse,
                    telephone = :tel,
                    email = :email,
                    commentaire = :comm
                    WHERE id_demande = :id";
            
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':nom' => $demande->getNomEtPrenom(),
                ':compte' => $demande->getIdCompte(),
                ':motif' => $demande->getMotif(),
                ':type' => $demande->getTypeChequier(),
                ':nb' => $demande->getNombreCheques(),
                ':montant' => $demande->getMontantMaxParCheque(),
                ':mode' => $demande->getModeReception(),
                ':adresse' => $demande->getAdresseAgence(),
                ':tel' => $demande->getTelephone(),
                ':email' => $demande->getEmail(),
                ':comm' => $demande->getCommentaire(),
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Erreur updateDemande: " . $e->getMessage());
            return false;
        }
    }

    public function getDemandeById($id) {
        $db = Config::getConnexion();
        $sql = "SELECT * FROM demande_chequier WHERE id_demande = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ UPDATE STATUS + SMS
    public function updateStatus($id, $status) {

        $db = Config::getConnexion();

        try {
            $sql = "UPDATE demande_chequier 
                    SET statut = :status 
                    WHERE id_demande = :id";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);
            if ($result && strtolower(trim($status)) === "acceptée") {

                $sql2 = "SELECT telephone 
                         FROM demande_chequier 
                         WHERE id_demande = :id";

                $stmt2 = $db->prepare($sql2);
                $stmt2->execute([':id' => $id]);

                $demande = $stmt2->fetch(PDO::FETCH_ASSOC);

                if ($demande && !empty($demande['telephone'])) {

                    $numero = $demande['telephone'];

                    if (strpos($numero, '+216') !== 0) {
                        $numero = "+216" . $numero;
                    }

                    $message = "Votre demande de chéquier a été acceptée ✅";

                    SmsController::sendSMS($numero, $message);
                }
            }

            return $result;

        } catch (PDOException $e) {
            throw $e;
        }
    }
}