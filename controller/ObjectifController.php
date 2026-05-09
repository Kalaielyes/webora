<?php
/**
 * ObjectifController — Independent goals with validation and multi-currency support.
 */
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/ObjectifFinancier.php';
require_once __DIR__ . '/../models/CompteBancaire.php';
require_once __DIR__ . '/../models/Mailer.php';
require_once __DIR__ . '/CompteController.php';

class ObjectifController {

    public static function fromRow(array $row): ObjectifFinancier {
        return new ObjectifFinancier(
            (int)$row['id_objectif'],
            (int)$row['id_utilisateur'],
            (int)($row['id_compte'] ?? 0),
            $row['titre'],
            (float)$row['montant_objectif'],
            (float)($row['montant_actuel'] ?? 0.0),
            $row['devise'] ?? 'TND',
            $row['date_debut'],
            $row['date_fin'],
            $row['statut'],
            $row['created_at']
        );
    }

    public static function save(ObjectifFinancier $obj): bool {
        $db = Config::getConnexion();
        $stmt = $db->prepare("
            INSERT INTO objectifs_financiers 
            (id_utilisateur, id_compte, titre, montant_objectif, montant_actuel, devise, date_debut, date_fin, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $obj->getIdUtilisateur(),
            $obj->getIdCompte() ?: null,
            $obj->getTitre(),
            $obj->getMontantObjectif(),
            $obj->getMontantActuel(),
            $obj->getDevise(),
            $obj->getDateDebut(),
            $obj->getDateFin(),
            $obj->getStatut()
        ]);
    }

    public static function findById(int $id): ?ObjectifFinancier {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM objectifs_financiers WHERE id_objectif = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function delete(int $id, int $userId): bool {
        $db = Config::getConnexion();
        // The table references comptebancaire.id_utilisateur which is utilisateur.id
        // So we delete by id_objectif and id_utilisateur
        $stmt = $db->prepare("DELETE FROM objectifs_financiers WHERE id_objectif = ? AND id_utilisateur = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function findAllByUtilisateur(int $userId): array {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM objectifs_financiers WHERE id_utilisateur = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        $objectifs = [];

        foreach ($rows as $row) {
            $obj = self::fromRow($row);
            if ($obj->getStatut() === 'en_cours') {
                $analysis = self::analyzeObjectif($obj);
                $newStatut = 'en_cours';
                if ($analysis['progress_pct'] >= 100) {
                    $newStatut = 'atteint';
                    $u = CompteController::getUser($userId);
                    $subject = "Félicitations ! Objectif atteint";
                    $msg = "Votre objectif '" . $obj->getTitre() . "' est maintenant terminé ! Vous avez atteint " . number_format($obj->getMontantActuel(), 2) . " " . $obj->getDevise() . ".";
                    sendMail($u['email'], $u['prenom'], $subject, _getBaseEmailLayout($subject, $msg));
                    
                    // Flag for Browser NOTIF
                    $_SESSION['pending_desktop_notif'][] = ['title' => "Objectif Atteint !", 'body' => $msg];
                } elseif ($analysis['is_delayed']) {
                    $newStatut = 'en_retard';
                    $u = CompteController::getUser($userId);
                    $subject = "Alerte : Objectif en retard";
                    $msg = "Votre objectif '" . $obj->getTitre() . "' a dépassé son échéance.";
                    sendMail($u['email'], $u['prenom'], $subject, _getBaseEmailLayout($subject, $msg));

                    $_SESSION['pending_desktop_notif'][] = ['title' => "Retard sur Objectif", 'body' => $msg];
                }

                if ($newStatut !== 'en_cours') {
                    $obj->setStatut($newStatut);
                    $db->prepare("UPDATE objectifs_financiers SET statut = ? WHERE id_objectif = ?")
                       ->execute([$newStatut, $obj->getIdObjectif()]);
                }
            }
            $objectifs[] = $obj;
        }
        return $objectifs;
    }

    public static function analyzeObjectif(ObjectifFinancier $obj): array {
        $goal = $obj->getMontantObjectif();
        $current = $obj->getMontantActuel();
        if ($goal <= 0) return ['progress_pct' => 0, 'ideal_pct' => 0, 'status_hint' => '---', 'is_delayed' => false];

        $progressPct = min(100, max(0, ($current / $goal) * 100));
        $start = strtotime($obj->getDateDebut());
        $end   = strtotime($obj->getDateFin());
        $now   = time();

        $totalDays = ($end - $start) / 86400;
        $passedDays = ($now - $start) / 86400;
        $idealPct = ($totalDays <= 0) ? 100 : min(100, max(0, ($passedDays / $totalDays) * 100));

        $diff = $progressPct - $idealPct;
        if ($progressPct >= 100) $statusHint = "Atteint";
        elseif ($diff > 5) $statusHint = "En avance";
        elseif ($diff < -5) $statusHint = "En retard";
        else $statusHint = "À temps";

        return [
            'progress_pct' => round($progressPct, 1),
            'ideal_pct'    => round($idealPct, 1),
            'status_hint'  => $statusHint,
            'is_delayed'   => ($now > $end && $progressPct < 100)
        ];
    }

    /**
     * Fund a goal with multi-currency support
     */
    public static function alimenter(int $userId, int $idObj, int $idSrc, float $montantSaisie): array {
        $db = Config::getConnexion();
        $obj = self::findById($idObj);
        $src = CompteController::findById($idSrc);

        if (!$obj || !$src || $obj->getIdUtilisateur() !== $userId || $src->getIdUtilisateur() !== $userId) {
            return ['success'=>false, 'message'=>'Données invalides.'];
        }

        if ($src->getTypeCompte() === 'epargne') {
            return ['success'=>false, 'message'=>'Impossible d\'utiliser un compte épargne.'];
        }

        // Amount requested to add to goal (in goal's currency)
        $remaining = $obj->getMontantObjectif() - $obj->getMontantActuel();
        if ($montantSaisie > $remaining + 0.001) {
            return ['success'=>false, 'message'=>'Le montant dépasse le besoin de l\'objectif.'];
        }

        // Exchange rates
        $rates = [
            'TND' => ['EUR' => 0.33, 'USD' => 0.32, 'TND' => 1],
            'EUR' => ['TND' => 3.00, 'USD' => 1.08, 'EUR' => 1],
            'USD' => ['TND' => 3.12, 'EUR' => 0.92, 'USD' => 1],
        ];

        // Convert goal's currency to account's currency to deduct correctly
        // We want to add $montantSaisie (Goal Currency) to the goal.
        // So we need to deduct X (Account Currency) such that X * rate(Acc->Goal) = $montantSaisie
        // Or X = $montantSaisie * rate(Goal->Acc)
        $rateGoalToAcc = $rates[$obj->getDevise()][$src->getDevise()] ?? 1.0;
        $montantDeduction = $montantSaisie * $rateGoalToAcc;

        if ($src->getSolde() < $montantDeduction) {
            return ['success'=>false, 'message'=>'Solde insuffisant sur le compte source.'];
        }

        try {
            $db->beginTransaction();
            $src->setSolde($src->getSolde() - $montantDeduction);
            CompteController::update($src);
            $newVal = $obj->getMontantActuel() + $montantSaisie;
            $db->prepare("UPDATE objectifs_financiers SET montant_actuel = ? WHERE id_objectif = ?")->execute([$newVal, $idObj]);
            $db->commit();
            
            if ($newVal >= $obj->getMontantObjectif() - 0.001) {
                // UPDATE STATUT IN DB IMMEDIATELY
                $db->prepare("UPDATE objectifs_financiers SET statut = 'atteint' WHERE id_objectif = ?")->execute([$idObj]);
                
                $statusMsg = "Félicitations ! Votre objectif '" . $obj->getTitre() . "' est désormais terminé.";
                $_SESSION['pending_desktop_notif'][] = ['title' => "Objectif Atteint !", 'body' => $statusMsg];
                
                $u = CompteController::getUser($userId);
                sendMail($u['email'], $u['prenom'] ?? '', "Objectif Atteint !", _getBaseEmailLayout("Félicitations !", $statusMsg));
            } else {
                $_SESSION['pending_desktop_notif'][] = [
                    'title' => "Dépôt Réussi",
                    'body' => "Vous avez versé ".number_format($montantSaisie, 2)." ".$obj->getDevise()." vers l'objectif '".$obj->getTitre()."'."
                ];
            }

            return ['success'=>true, 'message'=>'Fonds versés avec succès.'];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success'=>false, 'message'=>'Erreur lors de la transaction.'];
        }
    }

    public static function handleRequest(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        require_once __DIR__ . '/../models/Session.php';
        Session::start();

        // ── CSRF CHECK ───────────────────────────────────────
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($token)) {
            error_log("[Security] CSRF Violation attempted (Objectif).");
            die('Access denied: Invalid CSRF token.');
        }

        $userId = (int)Session::get('user_id');
        $action = trim($_POST['action'] ?? '');

        if ($action === 'add_objectif') {
            $titre = trim($_POST['titre'] ?? '');
            $montant = (float)($_POST['montant_objectif'] ?? 0);
            $devise = trim($_POST['devise'] ?? 'TND');
            $debut = $_POST['date_debut'] ?? '';
            $fin = $_POST['date_fin'] ?? '';

            if (!$titre || $montant <= 0 || !$debut || !$fin) {
                $_SESSION['form_error'] = "Veuillez remplir tous les champs correctement.";
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?tab=objectifs');
                exit;
            }

            $obj = new ObjectifFinancier(0, $userId, null, $titre, $montant, 0, $devise, $debut, $fin);
            if (self::save($obj)) {
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?tab=objectifs&ok=1');
            } else {
                $_SESSION['form_error'] = "Erreur lors de la création.";
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?tab=objectifs');
            }
            exit;
        }

        if ($action === 'alimenter') {
            $res = self::alimenter($userId, (int)$_POST['id_objectif'], (int)$_POST['id_compte_source'], (float)$_POST['montant']);
            if ($res['success']) {
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?tab=objectifs&ok=1');
            } else {
                $_SESSION['form_error'] = $res['message'];
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?tab=objectifs');
            }
            exit;
        }

        if ($action === 'delete_objectif') {
            $id = (int)$_POST['id_objectif'];
            $res = self::delete($id, $userId);
            if ($res) {
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?tab=objectifs&ok=deleted');
            } else {
                $_SESSION['form_error'] = "Impossible de supprimer cet objectif.";
                header('Location: ' . APP_URL . '/view/frontoffice/frontoffice_compte.php?tab=objectifs');
            }
            exit;
        }
    }
}

if (basename($_SERVER["SCRIPT_FILENAME"]) === basename(__FILE__)) {
    ObjectifController::handleRequest();
}
?>

