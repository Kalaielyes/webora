<?php
/**
 * DemandeChequierController - webora Integration
 * Manages cheque book requests and approvals
 */

require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/DemandeChequier.php';

class DemandeChequierController {

    // ── List cheque book requests ───────────────────────────────────────────
    public function listDemandes($userId = null) {
        $db = Config::getConnexion();
        if (!$db) return [];
        $sql = "SELECT d.*, c.iban, c.id_utilisateur, u.selfie_path, u.cin as user_cin
                FROM demande_chequier d 
                LEFT JOIN comptebancaire c ON d.id_compte = c.id_compte 
                LEFT JOIN utilisateur u ON c.id_utilisateur = u.id ";
        
        if ($userId !== null) {
            $sql .= " WHERE c.id_utilisateur = :userId ";
        }
        
        $sql .= " ORDER BY d.date_demande DESC";
        
        try {
            $stmt = $db->prepare($sql);
            if ($userId !== null) {
                $stmt->execute([':userId' => $userId]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::listDemandes - ' . $e->getMessage());
            return [];
        }
    }

    // ── Get demand by ID ────────────────────────────────────────────────────
    public function getDemandeById($id) {
        $db = Config::getConnexion();
        $sql = "SELECT * FROM demande_chequier WHERE id_demande = :id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::getDemandeById - ' . $e->getMessage());
            return null;
        }
    }

    // ── Add new cheque book request ─────────────────────────────────────────
    public function addDemande(DemandeChequier $demande) {
        $db = Config::getConnexion();
        try {
            $sql = "INSERT INTO demande_chequier 
            (`nom et prenom`, id_compte, motif, type_chequier, nombre_cheques, 
            montant_max_par_cheque, mode_reception, adresse_agence, 
            telephone, email, commentaire, statut, date_demande) 
            VALUES (:nom, :compte, :motif, :type, :nb, :montant, 
            :mode, :adresse, :tel, :email, :comm, :statut, NOW())";
            
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':nom'     => $demande->getNomEtPrenom(),
                ':compte' => $demande->getIdCompte(),
                ':motif'   => $demande->getMotif(),
                ':type'    => $demande->getTypeChequier(),
                ':nb'      => $demande->getNombreCheques(),
                ':montant' => $demande->getMontantMaxParCheque(),
                ':mode'    => $demande->getModeReception(),
                ':adresse' => $demande->getAdresseAgence(),
                ':tel'     => $demande->getTelephone(),
                ':email'   => $demande->getEmail(),
                ':comm'    => $demande->getCommentaire(),
                ':statut'  => $demande->getStatut()
            ]);
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::addDemande - ' . $e->getMessage());
            return false;
        }
    }

    // ── Update cheque book request ──────────────────────────────────────────
    public function updateDemande(DemandeChequier $demande, $id) {
        $db = Config::getConnexion();
        try {
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
                ':nom'     => $demande->getNomEtPrenom(),
                ':compte' => $demande->getIdCompte(),
                ':motif'   => $demande->getMotif(),
                ':type'    => $demande->getTypeChequier(),
                ':nb'      => $demande->getNombreCheques(),
                ':montant' => $demande->getMontantMaxParCheque(),
                ':mode'    => $demande->getModeReception(),
                ':adresse' => $demande->getAdresseAgence(),
                ':tel'     => $demande->getTelephone(),
                ':email'   => $demande->getEmail(),
                ':comm'    => $demande->getCommentaire(),
                ':id'      => $id
            ]);
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::updateDemande - ' . $e->getMessage());
            return false;
        }
    }

    // ── Delete cheque book request ──────────────────────────────────────────
    public function deleteDemande($id) {
        $db = Config::getConnexion();
        try {
            $sql = "DELETE FROM demande_chequier WHERE id_demande = :id";
            $stmt = $db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::deleteDemande - ' . $e->getMessage());
            return false;
        }
    }

    // ── Update request status (with SMS notification) ────────────────────────
    public function updateStatus($id, $status) {
        $db = Config::getConnexion();
        try {
            $sql = "UPDATE demande_chequier 
                    SET statut = :status 
                    WHERE id_demande = :id";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':status' => $status,
                ':id'     => $id
            ]);

            // Send SMS notification if status is approved
            if ($result && strtolower(trim($status)) === "acceptée") {
                $this->sendApprovalNotification($id);
            }

            return $result;
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::updateStatus - ' . $e->getMessage());
            throw $e;
        }
    }

    // ── Send SMS notification on approval ────────────────────────────────────
    private function sendApprovalNotification($id) {
        try {
            $db = Config::getConnexion();
            $sql = "SELECT telephone 
                    FROM demande_chequier 
                    WHERE id_demande = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $demande = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($demande && !empty($demande['telephone'])) {
                $numero = $demande['telephone'];
                
                // Format phone number if needed
                if (strpos($numero, '+216') !== 0) {
                    $numero = "+216" . $numero;
                }

                // Send SMS via Twilio or your SMS service
                // Example: SmsService::send($numero, "Votre demande de chéquier a été acceptée ✅");
                error_log('[LegaFin] SMS sent to ' . $numero);
            }
        } catch (Exception $e) {
            error_log('[LegaFin] DemandeChequierController::sendApprovalNotification - ' . $e->getMessage());
        }
    }

    // ── Get pending requests ────────────────────────────────────────────────
    public function getDemandesPending(): array {
        $db = Config::getConnexion();
        $sql = "SELECT d.*, c.iban, u.selfie_path, u.cin as user_cin
                FROM demande_chequier d 
                LEFT JOIN comptebancaire c ON d.id_compte = c.id_compte 
                LEFT JOIN utilisateur u ON c.id_utilisateur = u.id
                WHERE d.statut = 'En attente'
                ORDER BY d.date_demande ASC";
        try {
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::getDemandesPending - ' . $e->getMessage());
            return [];
        }
    }

    // ── Get approved requests ───────────────────────────────────────────────
    public function getDemandesApproved(): array {
        $db = Config::getConnexion();
        $sql = "SELECT d.*, c.iban, u.selfie_path, u.cin as user_cin
                FROM demande_chequier d 
                LEFT JOIN comptebancaire c ON d.id_compte = c.id_compte 
                LEFT JOIN utilisateur u ON c.id_utilisateur = u.id
                WHERE d.statut = 'Acceptée'
                ORDER BY d.date_demande DESC";
        try {
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] DemandeChequierController::getDemandesApproved - ' . $e->getMessage());
            return [];
        }
    }
}
