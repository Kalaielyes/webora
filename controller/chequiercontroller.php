<?php
/**
 * ChequierController - webora Integration
 * Manages cheque books (chéquiers)
 */

require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Chequier.php';
require_once __DIR__ . '/../models/MailService.php';

class ChequierController {

    // ── List chequiers ──────────────────────────────────────────────────────
    public function listChequiers($userId = null) {
        $db = Config::getConnexion();
        $sql = "SELECT ch.*, d.`nom et prenom` AS nom_client, c.iban, c.id_utilisateur, u.selfie_path, u.cin as user_cin
                FROM chequier ch
                LEFT JOIN demande_chequier d ON ch.id_demande = d.id_demande
                LEFT JOIN comptebancaire c ON ch.id_compte = c.id_compte
                LEFT JOIN utilisateur u ON c.id_utilisateur = u.id ";
        
        if ($userId !== null) {
            $sql .= " WHERE c.id_utilisateur = :userId ";
        }

        $sql .= " ORDER BY ch.id_chequier DESC";
        
        try {
            $stmt = $db->prepare($sql);
            if ($userId !== null) {
                $stmt->execute([':userId' => $userId]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequierController::listChequiers - ' . $e->getMessage());
            return [];
        }
    }

    // ── Get chequier by ID ──────────────────────────────────────────────────
    public function getChequierById($id) {
        $db = Config::getConnexion();
        $sql = "SELECT ch.*, d.`nom et prenom` AS nom_client, c.iban, c.id_utilisateur, u.selfie_path, u.cin as user_cin
                FROM chequier ch
                LEFT JOIN demande_chequier d ON ch.id_demande = d.id_demande
                LEFT JOIN comptebancaire c ON ch.id_compte = c.id_compte
                LEFT JOIN utilisateur u ON c.id_utilisateur = u.id
                WHERE ch.id_chequier = :id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequierController::getChequierById - ' . $e->getMessage());
            return null;
        }
    }

    // ── Add new chequier ────────────────────────────────────────────────────
    public function addChequier(Chequier $chequier) {
        $db = Config::getConnexion();
        $sql = "INSERT INTO chequier (numero_chequier, date_creation, date_expiration, statut, nombre_feuilles, id_demande, id_compte) 
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

            $this->maybeSendExpirationReminder($chequier->getIdDemande(), $chequier->getDateExpiration());

            return true;
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequierController::addChequier - ' . $e->getMessage());
            throw $e;
        }
    }

    // ── Update chequier ────────────────────────────────────────────────────
    public function updateChequier(Chequier $chequier) {
        $db = Config::getConnexion();
        $sql = "UPDATE chequier 
                SET date_expiration = :expiration, 
                    statut = :statut, 
                    nombre_feuilles = :nb
                WHERE id_chequier = :id";

        $stmt = $db->prepare($sql);
        try {
            $result = $stmt->execute([
                ':expiration' => $chequier->getDateExpiration(),
                ':statut'     => $chequier->getStatut(),
                ':nb'         => $chequier->getNombreFeuilles(),
                ':id'         => $chequier->getIdChequier()
            ]);

            $this->maybeSendExpirationReminder($chequier->getIdDemande(), $chequier->getDateExpiration());

            return $result;
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequierController::updateChequier - ' . $e->getMessage());
            throw $e;
        }
    }

    // ── Delete chequier ────────────────────────────────────────────────────
    public function deleteChequier($id) {
        $db = Config::getConnexion();
        $sql = "DELETE FROM chequier WHERE id_chequier = :id";
        $stmt = $db->prepare($sql);
        try {
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequierController::deleteChequier - ' . $e->getMessage());
            return false;
        }
    }

    // ── Get chequiers expiring within 15 days ────────────────────────────────
    public function getChequiersExpirantMoinsDe15Jours(): array {
        $db = Config::getConnexion();

        $sql = "SELECT 
                    ch.id_chequier,
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
                AND d.email IS NOT NULL
                ORDER BY ch.date_expiration ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequierController::getChequiersExpirantMoinsDe15Jours - ' . $e->getMessage());
            return [];
        }
    }

    // ── Send expiration reminder email ──────────────────────────────────────
    private function maybeSendExpirationReminder($idDemande, $dateExpiration): void {
        if (empty($idDemande) || empty($dateExpiration)) {
            return;
        }

        try {
            $exp = new DateTime((string)$dateExpiration);
            $today = new DateTime('today');
            $diffDays = (int)$today->diff($exp)->format('%r%a');

            if ($diffDays < 0 || $diffDays > 15) {
                return;
            }

            $this->envoyerEmailRappelExpiration((int)$idDemande);
        } catch (Exception $e) {
            error_log('[LegaFin] ChequierController::maybeSendExpirationReminder - ' . $e->getMessage());
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

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idDemande]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data || empty($data['email'])) return;

            $mailService = new MailService();
            $subject = "⚠️ Rappel expiration chéquier";
            $htmlContent = $this->buildChequierExpirationEmail(
                $data['nom_et_prenom'],
                $data['numero_chequier'],
                $data['date_expiration'],
                $data['nombre_feuilles']
            );

            $mailService->send($data['email'], $subject, $htmlContent);
        } catch (Exception $e) {
            error_log('[LegaFin] ChequierController::envoyerEmailRappelExpiration - ' . $e->getMessage());
        }
    }

    // ── Build expiration email HTML ─────────────────────────────────────────
    private function buildChequierExpirationEmail($nom, $numero, $expiration, $feuilles) {
        $formattedExp = date('d/m/Y', strtotime($expiration));
        return "
        <html>
        <body style='margin: 0; padding: 0; background-color: #f8fafc; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif;'>
            <div style='max-width: 650px; margin: 40px auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>
                <div style='display: flex; align-items: center; margin-bottom: 25px;'>
                    <h2 style='color: #c2410c; margin: 0; font-size: 22px; font-weight: 700; display: flex; align-items: center;'>
                        <span style='margin-right: 12px;'>⚠️</span> Rappel : Expiration de votre chéquier
                    </h2>
                </div>
                
                <p style='color: #475569; font-size: 16px; margin-bottom: 10px;'>Bonjour <strong>{$nom}</strong>,</p>
                <p style='color: #475569; font-size: 15px; margin-bottom: 25px;'>Votre chéquier arrive bientôt à expiration :</p>
                
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid #f1f5f9;'>
                    <tr style='background-color: #f8fafc;'>
                        <th style='text-align: left; padding: 15px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 14px; width: 40%;'>Numéro chéquier</th>
                        <td style='padding: 15px; border-bottom: 1px solid #f1f5f9; color: #475569; font-size: 14px;'>{$numero}</td>
                    </tr>
                    <tr>
                        <th style='text-align: left; padding: 15px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 14px;'>Date d'expiration</th>
                        <td style='padding: 15px; border-bottom: 1px solid #f1f5f9; color: #dc2626; font-size: 14px; font-weight: 600;'>{$formattedExp}</td>
                    </tr>
                    <tr style='background-color: #f8fafc;'>
                        <th style='text-align: left; padding: 15px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 14px;'>Nombre de feuilles</th>
                        <td style='padding: 15px; border-bottom: 1px solid #f1f5f9; color: #475569; font-size: 14px;'>{$feuilles}</td>
                    </tr>
                </table>
                
                <p style='color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 40px;'>
                    Veuillez vous rapprocher de votre agence pour le renouveler.
                </p>
                
                <div style='border-top: 1px solid #f1f5f9; padding-top: 20px;'>
                    <p style='color: #94a3b8; font-size: 13px; margin: 0;'>
                        LegalFin — Email automatique. Merci de ne pas répondre.
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }

    // ── Get chequiers by account ────────────────────────────────────────────
    public function getChequiersByAccount($idCompte): array {
        $db = Config::getConnexion();
        $sql = "SELECT * FROM chequier WHERE id_compte = :id ORDER BY date_creation DESC";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idCompte]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequierController::getChequiersByAccount - ' . $e->getMessage());
            return [];
        }
    }
}
