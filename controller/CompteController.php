<?php
/**
 * CompteController — Fat Controller for CompteBancaire.
 * Handles DB interactions and POST requests.
 */
// Load config inside the class methods when needed to avoid issues if included from views
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/CompteBancaire.php';
require_once __DIR__ . '/../models/CarteBancaire.php'; 
require_once __DIR__ . '/../models/Security.php';
require_once __DIR__ . '/../models/Mailer.php';
require_once __DIR__ . '/ObjectifController.php';

class CompteController
{
    public static function getUser(int $id): ?array {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM utilisateur WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    // ── IBAN generator ────────────────────────────────────────
    public static function generateIban(): string
    {
        return 'TN59' . str_pad((string)random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
    }

    // ── DB: Static factory ────────────────────────────────────
    public static function fromRow(array $row): CompteBancaire
    {
        $compte = new CompteBancaire(
            (int)$row['id_compte'],
            (int)$row['id_utilisateur'],
            $row['iban'],
            $row['type_compte'],
            (float)$row['solde'],
            $row['devise'],
            (float)$row['plafond_virement'],
            $row['statut'],
            $row['date_ouverture'] ?? null,
            $row['date_fermeture'] ?? null,
            $row['derniere_interet'] ?? null,
            (float)($row['taux_interet'] ?? 7.50)
        );
        return $compte;
    }

    // ── DB: INSERT & UPDATE ───────────────────────────────────
    public static function save(CompteBancaire $compte): void
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("
            INSERT INTO comptebancaire
                (id_utilisateur, iban, type_compte, solde, devise,
                 plafond_virement, statut, date_ouverture)
            VALUES
                (:id_utilisateur, :iban, :type_compte, :solde, :devise,
                 :plafond_virement, :statut, :date_ouverture)
        ");
        $stmt->execute([
            'id_utilisateur'   => $compte->getIdUtilisateur(),
            'iban'             => $compte->getIban(),
            'type_compte'      => $compte->getTypeCompte(),
            'solde'            => $compte->getSolde(),
            'devise'           => $compte->getDevise(),
            'plafond_virement' => $compte->getPlafondVirement(),
            'statut'           => $compte->getStatut(),
            'date_ouverture'   => $compte->getDateOuverture(),
        ]);
        $compte->setIdCompte((int)$db->lastInsertId());
    }

    public static function update(CompteBancaire $compte): void
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("
            UPDATE comptebancaire SET
                type_compte       = :type_compte,
                solde             = :solde,
                devise            = :devise,
                plafond_virement  = :plafond_virement,
                statut            = :statut,
                date_fermeture    = :date_fermeture,
                derniere_interet  = :derniere_interet,
                taux_interet      = :taux_interet
            WHERE id_compte = :id_compte
        ");
        $stmt->execute([
            'type_compte'      => $compte->getTypeCompte(),
            'solde'            => $compte->getSolde(),
            'devise'           => $compte->getDevise(),
            'plafond_virement' => $compte->getPlafondVirement(),
            'statut'           => $compte->getStatut(),
            'date_fermeture'   => $compte->getDateFermeture(),
            'derniere_interet' => $compte->getDerniereInteret(),
            'taux_interet'     => $compte->getTauxInteret(),
            'id_compte'        => $compte->getIdCompte(),
        ]);
    }

    // ── DB: Static finders ────────────────────────────────────
    public static function findByUtilisateur(int $idUser): array
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM comptebancaire WHERE id_utilisateur = ? ORDER BY id_compte DESC");
        $stmt->execute([$idUser]);
        $comptes = array_map([self::class, 'fromRow'], $stmt->fetchAll());
        // Auto-apply annual interest for savings accounts
        self::applyAnnualInterest($comptes, $idUser);
        return $comptes;
    }

    // ── Annual Interest Auto-Application ─────────────────────
    public static function applyAnnualInterest(array &$comptes, int $idUser): void
    {
        $today = new DateTime();
        foreach ($comptes as $compte) {
            // Only active savings accounts
            if ($compte->getTypeCompte() !== 'epargne' || $compte->getStatut() !== 'actif') continue;
            if ($compte->getSolde() <= 0) continue;

            // Determine reference date: last interest applied, or opening date
            $refDateStr = $compte->getDerniereInteret() ?? $compte->getDateOuverture();
            if (!$refDateStr) continue;

            $refDate  = new DateTime($refDateStr);
            $diffDays = (int)$refDate->diff($today)->days;

            // Apply if 365+ days have passed since last application
            if ($diffDays < 365) continue;

            $taux     = $compte->getTauxInteret() / 100;
            $interet  = round($compte->getSolde() * $taux, 3);

            // Update balance and mark date
            $compte->setSolde($compte->getSolde() + $interet);
            $compte->setDerniereInteret(date('Y-m-d'));
            self::update($compte);

            // Log to SheetDB
            self::logToSheetDB([
                'id_utilisateur' => $idUser,
                'date'           => date('Y-m-d H:i:s'),
                'montant'        => $interet,
                'devise'         => $compte->getDevise(),
                'type'           => 'interet',
                'libelle'        => 'Intérêts annuels (' . $compte->getTauxInteret() . '%) — Compte Épargne',
                'source'         => 'LEGALFIN_BANK',
                'destination'    => $compte->getIban(),
            ]);
        }
    }

    public static function findById(int $id): ?CompteBancaire
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM comptebancaire WHERE id_compte = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function findAll(): array
    {
        $db = Config::getConnexion();
        $stmt = $db->query("
            SELECT c.*, u.nom, u.prenom, u.email
            FROM comptebancaire c
            LEFT JOIN utilisateur u ON u.id = c.id_utilisateur
            ORDER BY c.id_compte DESC
        ");
        return $stmt->fetchAll();
    }

    // ── DB: KPI aggregates ────────────────────────────────────
    public static function countByStatut(string $statut): int
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT COUNT(*) FROM comptebancaire WHERE statut = ?");
        $stmt->execute([$statut]);
        return (int)$stmt->fetchColumn();
    }

    public static function totalSolde(): float
    {
        $db = Config::getConnexion();
        return (float)$db->query("SELECT COALESCE(SUM(solde), 0) FROM comptebancaire WHERE statut != 'cloture'")->fetchColumn();
    }

    // ── Business Actions ──────────────────────────────────────
    public static function demanderCompte(int $idUser, string $type, string $devise, float $plafond, bool $actifDirectement = false): CompteBancaire
    {
        $statut = $actifDirectement ? 'actif' : 'en_attente';
        $compte = new CompteBancaire(
            0,
            $idUser,
            self::generateIban(),
            $type,
            0.0,
            $devise,
            $plafond,
            $statut,
            date('Y-m-d')
        );
        self::save($compte);
        return $compte;
    }

    public static function bloquer(int $id): void
    {
        $compte = self::findById($id);
        if ($compte) { $compte->setStatut('bloque'); self::update($compte); }
    }

    public static function debloquer(int $id): void
    {
        $compte = self::findById($id);
        if ($compte) { $compte->setStatut('actif'); self::update($compte); }
    }

    public static function demanderCloture(int $id): void
    {
        $compte = self::findById($id);
        if ($compte) { $compte->setStatut('demande_cloture'); self::update($compte); }
    }

    public static function demanderSuppression(int $id): void
    {
        $compte = self::findById($id);
        if ($compte) { $compte->setStatut('demande_suppression'); self::update($compte); }
    }

    public static function cloturer(int $id): void
    {
        $compte = self::findById($id);
        if ($compte) {
            $compte->setStatut('cloture');
            $compte->setDateFermeture(date('Y-m-d'));
            self::update($compte);
        }
    }

    public static function refuserCloture(int $id): void
    {
        $compte = self::findById($id);
        if ($compte) { $compte->setStatut('actif'); self::update($compte); }
    }

    public static function refuserSuppression(int $id): void
    {
        $compte = self::findById($id);
        if ($compte) { $compte->setStatut('actif'); self::update($compte); }
    }

    public static function demanderConversionCourant(int $id): void
    {
        $compte = self::findById($id);
        if ($compte && $compte->getTypeCompte() === 'epargne') {
            $compte->setStatut('demande_activation_courant');
            self::update($compte);
        }
    }

    public static function activerConversionCourant(int $id): void
    {
        $compte = self::findById($id);
        if ($compte && $compte->getStatut() === 'demande_activation_courant') {
            $compte->setTypeCompte('courant');
            $compte->setStatut('actif');
            self::update($compte);
        }
    }

    public static function delete(int $id): void
    {
        $db = Config::getConnexion();

        // Fetch IBAN before deleting so we can purge SheetDB history
        $stmt = $db->prepare("SELECT iban FROM comptebancaire WHERE id_compte = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $iban = $row['iban'] ?? null;

        // Delete cards first
        $db->prepare("DELETE FROM cartebancaire WHERE id_compte = ?")->execute([$id]);
        // Delete account
        $db->prepare("DELETE FROM comptebancaire WHERE id_compte = ?")->execute([$id]);

        // Purge SheetDB transaction history for this IBAN
        if ($iban) {
            $base = 'https://sheetdb.io/api/v1/2eyctn6m5yzmz';
            foreach (['source', 'destination'] as $col) {
                $ch = curl_init($base . '/' . $col . '/' . urlencode($iban));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }

    // ── SheetDB: Logging ──────────────────────────────────────
    public static function logToSheetDB(array $data): void
    {
        $apiUrl = "https://sheetdb.io/api/v1/2eyctn6m5yzmz";
        $payload = json_encode(['data' => [$data]]);
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }

    // ── Request Handler ───────────────────────────────────────
    public static function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        require_once __DIR__ . '/../models/Session.php';
        Session::start();

        // ── CSRF CHECK ───────────────────────────────────────
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($token)) {
            error_log("[Security] CSRF Violation attempted.");
            die('Access denied: Invalid CSRF token.');
        }

        $action   = trim($_POST['action']   ?? '');
        $idCompte = (int)($_POST['id_compte'] ?? 0);

        switch ($action) {
            case 'add': {
                $idUser  = (int)Session::get('user_id');
                $kyc     = trim($_SESSION['user']['status_kyc'] ?? '');

                if ($kyc !== 'VERIFIE') {
                    header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?error=kyc_required');
                    exit;
                }

                $type    = trim($_POST['type_compte']      ?? 'courant');
                $devise  = trim($_POST['devise']           ?? 'TND');
                $plafond = (float)($_POST['plafond_virement'] ?? 5000);

                $errors = [];
                if ($plafond < 10) {
                    $errors['plafond_virement'] = "Le plafond doit être d'au moins 10.";
                }

                if (!empty($errors)) {
                    $_SESSION['form_errors'] = $errors;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?form=compte');
                    exit;
                }

                self::demanderCompte($idUser, $type, $devise, $plafond, false);
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?ok=compte_demande');
                exit;
            }

            case 'virement': {
                $idUser     = (int)Session::get('user_id');
                $idSource   = (int)($_POST['id_compte_source'] ?? 0);
                $destType   = trim($_POST['dest_type'] ?? 'interne');
                $montant    = (float)($_POST['montant'] ?? 0);
                $deviseInput= trim($_POST['montant_devise'] ?? 'TND');
                
                $source = self::findById($idSource);
                if (!$source || $source->getIdUtilisateur() !== $idUser || $source->getStatut() !== 'actif' || $source->getTypeCompte() === 'epargne') {
                    header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?form=virement&error=invalid_source');
                    exit;
                }

                $rates = [
                    'TND' => ['EUR' => 0.33, 'USD' => 0.32, 'TND' => 1],
                    'EUR' => ['TND' => 3.00, 'USD' => 1.08, 'EUR' => 1],
                    'USD' => ['TND' => 3.12, 'EUR' => 0.92, 'USD' => 1],
                ];

                $rateToSource = $rates[$deviseInput][$source->getDevise()] ?? 1.0;
                $montantDeduction = $montant * $rateToSource;

                if ($montant <= 0 || $source->getSolde() < $montantDeduction) {
                    $_SESSION['form_errors'] = ['montant' => "Solde insuffisant."];
                    header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?form=virement');
                    exit;
                }
                
                $logData = [
                    'id_utilisateur' => $idUser,
                    'date'           => date('Y-m-d H:i:s'),
                    'montant'        => $montant,
                    'devise'         => $deviseInput,
                    'type'           => $destType,
                    'libelle'        => "Virement " . $destType,
                    'source'         => $source->getIban(),
                    'destination'    => ""
                ];

                if ($destType === 'interne') {
                    $idDest = (int)($_POST['id_compte_dest'] ?? 0);
                    if ($idSource === $idDest) {
                        header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?form=virement&error=same_account');
                        exit;
                    }

                    $dest = self::findById($idDest);
                    if ($dest && $dest->getStatut() === 'actif' && $dest->getIdUtilisateur() === $idUser) {
                        $rateToDest = $rates[$deviseInput][$dest->getDevise()] ?? 1.0;
                        $montantCredit = $montant * $rateToDest;
                        $source->setSolde($source->getSolde() - $montantDeduction);
                        $dest->setSolde($dest->getSolde() + $montantCredit);
                        self::update($source);
                        self::update($dest);
                        $logData['destination'] = $dest->getIban();
                        self::logToSheetDB($logData);
                    }
                } elseif ($destType === 'objectif') {
                    $idObj = (int)($_POST['id_objectif_dest'] ?? 0);
                    $obj = ObjectifController::findById($idObj);
                    if ($obj && $obj->getIdUtilisateur() === $idUser) {
                        $res = ObjectifController::alimenter($idUser, $idObj, $idSource, $montant); 
                        if (!$res['success']) {
                            header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?form=virement&error=' . urlencode($res['message']));
                            exit;
                        }
                        $logData['destination'] = "OBJECTIF: " . $obj->getTitre();
                        self::logToSheetDB($logData);
                    }
                } else {
                    $ibanExt = trim($_POST['iban_dest'] ?? 'EXTERNE');
                    $source->setSolde($source->getSolde() - $montantDeduction);
                    self::update($source);
                    $logData['destination'] = $ibanExt;
                    self::logToSheetDB($logData);
                }
                
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?form=virement&ok=1');
                exit;
            }

            case 'activer': {
                $compte = self::findById($idCompte);
                if ($compte) { 
                    $compte->setStatut('actif'); 
                    self::update($compte); 
                    
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre compte a été activé", "#16a34a", "Actif");
                }
                break;
            }

            case 'bloquer':
                self::bloquer($idCompte);
                $compte = self::findById($idCompte);
                if ($compte) {
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre compte a été bloqué", "#dc2626", "Bloqué");
                }
                break;
            case 'debloquer':
                self::debloquer($idCompte);
                $compte = self::findById($idCompte);
                if ($compte) {
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre compte a été débloqué", "#16a34a", "Actif");
                }
                break;
            case 'demander_cloture': self::demanderCloture($idCompte); break;
            case 'demande_suppression': self::demanderSuppression($idCompte); break;
            case 'cloturer':
                self::cloturer($idCompte);
                $compte = self::findById($idCompte);
                if ($compte) {
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre compte a été clôturé", "#4b5563", "Clôturé");
                }
                break;
            case 'refuser_cloture':
                self::refuserCloture($idCompte);
                $compte = self::findById($idCompte);
                if ($compte) {
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre demande de clôture a été refusée", "#ea580c", "Actif");
                }
                break;
            case 'refuser_suppression':
                self::refuserSuppression($idCompte);
                $compte = self::findById($idCompte);
                if ($compte) {
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre demande de suppression a été refusée", "#ea580c", "Actif");
                }
                break;
            case 'demande_activation_courant':
                self::demanderConversionCourant($idCompte);
                break;
            case 'activer_conversion_courant':
                self::activerConversionCourant($idCompte);
                $compte = self::findById($idCompte);
                if ($compte) {
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre compte a été activé en tant que compte courant", "#16a34a", "Actif");
                }
                break;
            case 'delete':
                $compte = self::findById($idCompte);
                if ($compte) {
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Votre compte a été définitivement supprimé", "#dc2626", "Supprimé");
                    self::delete($idCompte);
                }
                break;

            case 'update': {
                $compte = self::findById($idCompte);
                if ($compte) {
                    $errors = [];
                    if (isset($_POST['solde']) && (float)$_POST['solde'] < 0) {
                        $errors['solde'] = "Le solde ne peut pas être négatif.";
                    }
                    if (isset($_POST['plafond_virement']) && (float)$_POST['plafond_virement'] < 10) {
                        $errors['plafond_virement'] = "Le plafond doit être d'au moins 10.";
                    }

                    if (!empty($errors)) {
                        $_SESSION['form_errors'] = $errors;
                        $_SESSION['form_data'] = $_POST;
                        header('Location: ' . APP_URL . '/view/backoffice/backoffice_compte.php?tab=comptes&id_compte=' . $idCompte . '&edit=compte');
                        exit;
                    }

                    if (isset($_POST['type_compte']))      $compte->setTypeCompte(trim($_POST['type_compte']));
                    if (isset($_POST['solde']))            $compte->setSolde((float)$_POST['solde']);
                    if (isset($_POST['devise']))           $compte->setDevise(trim($_POST['devise']));
                    if (isset($_POST['plafond_virement'])) $compte->setPlafondVirement((float)$_POST['plafond_virement']);
                    if (isset($_POST['statut']))           $compte->setStatut(trim($_POST['statut']));
                    if (!empty($_POST['date_fermeture']))  $compte->setDateFermeture($_POST['date_fermeture']);
                    if (isset($_POST['taux_interet']))     $compte->setTauxInteret((float)$_POST['taux_interet']);
                    self::update($compte);
                    
                    $u = self::getUser($compte->getIdUtilisateur());
                    sendCompteNotification($u, $compte, "Les informations de votre compte ont été modifiées", "#2563eb", $compte->getStatut());
                }
                break;
            }
        }

        $back = !empty($_SERVER['HTTP_REFERER'])
            ? $_SERVER['HTTP_REFERER']
            : APP_URL . '/view/backoffice/backoffice_compte.php';
        header('Location: ' . $back);
        exit;
    }
}

if (basename($_SERVER["SCRIPT_FILENAME"]) === basename(__FILE__)) {
    CompteController::handleRequest();
}
?>

