<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/demandechequier.php';

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
        $db = Config::getConnexion();
        $sql = "INSERT INTO demande_chequier (`nom et prenom`, id_compte, motif, type_chequier, nombre_cheques, montant_max_par_cheque, mode_reception, adresse_agence, telephone, email, commentaire, statut) 
                VALUES (:nom, :compte, :motif, :type, :nb, :montant, :mode, :adresse, :tel, :email, :comm, :statut)";
        
        $stmt = $db->prepare($sql);
        try {
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
            // Logique d'auto-réparation
            if (strpos($e->getMessage(), '1452') !== false) {
                try {
                    $db->exec("ALTER TABLE demande_chequier DROP FOREIGN KEY demande_chequier_ibfk_1");
                    $db->exec("ALTER TABLE demande_chequier MODIFY id_compte int(11) NULL");
                    
                    return $stmt->execute([
                        ':nom' => $demande->getNomEtPrenom(),
                        ':compte' => null,
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
                } catch (Exception $fixEx) { throw $e; }
            } else { throw $e; }
        }
    }

    public function updateStatus($id, $status) {
        $db = Config::getConnexion();
        try {
            $sql = "UPDATE demande_chequier SET statut = :status WHERE id_demande = :id";
            $stmt = $db->prepare($sql);
            return $stmt->execute([':status' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            // Si la colonne statut n'existe pas (erreur 1054)
            if ($e->getCode() == "42S22") { 
                try {
                    $db->exec("ALTER TABLE demande_chequier ADD COLUMN statut VARCHAR(20) DEFAULT 'En attente'");
                    // On retente l'update
                    $sql = "UPDATE demande_chequier SET statut = :status WHERE id_demande = :id";
                    return $db->prepare($sql)->execute([':status' => $status, ':id' => $id]);
                } catch (Exception $e2) { throw $e; }
            }
            throw $e;
        }
    }

    public function updateDemande(DemandeChequier $demande, $id) {
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
                commentaire = :comm,
                statut = :statut
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
            ':statut' => $demande->getStatut(),
            ':id' => $id
        ]);
    }
}
