<?php
/**
 * CompteController — Fat Controller for CompteBancaire.
 * Handles DB interactions and POST requests.
 */
// Load config inside the class methods when needed to avoid issues if included from views
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/CompteBancaire.php';
require_once __DIR__ . '/../models/CarteBancaire.php'; 

class CompteController
{
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
            $row['date_fermeture'] ?? null
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
                type_compte      = :type_compte,
                solde            = :solde,
                devise           = :devise,
                plafond_virement = :plafond_virement,
                statut           = :statut,
                date_fermeture   = :date_fermeture
            WHERE id_compte = :id_compte
        ");
        $stmt->execute([
            'type_compte'      => $compte->getTypeCompte(),
            'solde'            => $compte->getSolde(),
            'devise'           => $compte->getDevise(),
            'plafond_virement' => $compte->getPlafondVirement(),
            'statut'           => $compte->getStatut(),
            'date_fermeture'   => $compte->getDateFermeture(),
            'id_compte'        => $compte->getIdCompte(),
        ]);
    }

    // ── DB: Static finders ────────────────────────────────────
    public static function findByUtilisateur(int $idUser): array
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM comptebancaire WHERE id_utilisateur = ? ORDER BY id_compte DESC");
        $stmt->execute([$idUser]);
        return array_map([self::class, 'fromRow'], $stmt->fetchAll());
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
        return (float)$db->query("SELECT COALESCE(SUM(solde), 0) FROM comptebancaire")->fetchColumn();
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

    public static function delete(int $id): void
    {
        $db = Config::getConnexion();
        $db->prepare("DELETE FROM cartebancaire WHERE id_compte = ?")->execute([$id]);
        $db->prepare("DELETE FROM comptebancaire WHERE id_compte = ?")->execute([$id]);
    }

    // ── Request Handler ───────────────────────────────────────
    public static function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        // Only run config session startup if accessed as entry point
        // Using config::autoLogin as it starts session if not started
        Config::autoLogin();

        $action   = trim($_POST['action']   ?? '');
        $idCompte = (int)($_POST['id_compte'] ?? 0);

        switch ($action) {
            case 'add': {
                $idUser  = (int)($_SESSION['user']['id'] ?? 0);
                $kyc     = trim($_SESSION['user']['status_kyc'] ?? '');

                if ($kyc !== 'VERIFIE') {
                    header('Location: ' . APP_URL . '/views/frontoffice/frontoffice_compte.php?error=kyc_required');
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
                    header('Location: ' . APP_URL . '/views/frontoffice/frontoffice_compte.php?form=compte');
                    exit;
                }

                self::demanderCompte($idUser, $type, $devise, $plafond, false);
                header('Location: ' . APP_URL . '/views/frontoffice/frontoffice_compte.php?ok=compte_demande');
                exit;
            }

            case 'activer': {
                $compte = self::findById($idCompte);
                if ($compte) { $compte->setStatut('actif'); self::update($compte); }
                break;
            }

            case 'bloquer':          self::bloquer($idCompte);         break;
            case 'debloquer':        self::debloquer($idCompte);       break;
            case 'demander_cloture': self::demanderCloture($idCompte); break;
            case 'cloturer':         self::cloturer($idCompte);        break;
            case 'refuser_cloture':  self::refuserCloture($idCompte);  break;
            case 'delete':           self::delete($idCompte);          break;

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
                        header('Location: ' . APP_URL . '/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=' . $idCompte . '&edit=compte');
                        exit;
                    }

                    if (isset($_POST['type_compte']))      $compte->setTypeCompte(trim($_POST['type_compte']));
                    if (isset($_POST['solde']))            $compte->setSolde((float)$_POST['solde']);
                    if (isset($_POST['devise']))           $compte->setDevise(trim($_POST['devise']));
                    if (isset($_POST['plafond_virement'])) $compte->setPlafondVirement((float)$_POST['plafond_virement']);
                    if (isset($_POST['statut']))           $compte->setStatut(trim($_POST['statut']));
                    if (!empty($_POST['date_fermeture']))  $compte->setDateFermeture($_POST['date_fermeture']);
                    self::update($compte);
                }
                break;
            }
        }

        // Redirect: back to referer, or to backoffice by default
        $back = !empty($_SERVER['HTTP_REFERER'])
            ? $_SERVER['HTTP_REFERER']
            : APP_URL . '/views/backoffice/backoffice_compte.php';
        header('Location: ' . $back);
        exit;
    }
}

// Invoke the handler if accessed directly for POST actions
CompteController::handleRequest();
?>
