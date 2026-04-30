<?php
/**
 * CarteController — Fat Controller for CarteBancaire.
 * Handles DB interactions and POST requests.
 */
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/CarteBancaire.php';
require_once __DIR__ . '/../models/mailer.php';
require_once __DIR__ . '/CompteController.php';

class CarteController
{
    public static function getUserForCarte(int $idCarte): ?array {
        $db = Config::getConnexion();
        $stmt = $db->prepare("
            SELECT u.* 
            FROM utilisateur u
            JOIN comptebancaire c ON c.id_utilisateur = u.id
            JOIN cartebancaire cb ON cb.id_compte = c.id_compte
            WHERE cb.id_carte = ?
        ");
        $stmt->execute([$idCarte]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
  
    public static function fromRow(array $row): CarteBancaire
    {
        return new CarteBancaire(
            (int)$row['id_carte'],
            (int)$row['id_compte'],
            $row['numero_carte'],
            $row['type_carte'],
            $row['titulaire_nom'],
            $row['reseau'],
            $row['date_expiration'],
            $row['cvv_hash'] ?? '',
            (float)$row['plafond_paiement_jour'],
            (float)$row['plafond_retrait_jour'],
            $row['statut'],
            $row['style'] ?? 'standard',
            $row['date_emission'] ?? null,
            $row['date_activation'] ?? null,
            $row['motif_blocage'] ?? null,
            $row['cvv_display'] ?? ''
        );
    }

    public static function generateCardNumber(): string
    {
        return implode(' ', [
            str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT),
        ]);
    }

    public static function hashCvv(string $cvv): string
    {
        return password_hash($cvv, PASSWORD_BCRYPT);
    }

    public static function computeStyleFromPlafond(float $plafond): string
    {
        if ($plafond >= 1500) return 'titanium';
        if ($plafond >= 1000) return 'platinum';
        if ($plafond >= 500)  return 'gold';
        return 'standard';
    }

    // ── DB: INSERT & UPDATE ───────────────────────────────────
    public static function save(CarteBancaire $carte): void
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("
            INSERT INTO cartebancaire
                (id_compte, numero_carte, type_carte, titulaire_nom, reseau,
                 date_expiration, cvv_hash, plafond_paiement_jour,
                 plafond_retrait_jour, statut, style, date_emission, cvv_display)
            VALUES
                (:id_compte, :numero_carte, :type_carte, :titulaire_nom, :reseau,
                 :date_expiration, :cvv_hash, :plafond_paiement_jour,
                 :plafond_retrait_jour, :statut, :style, :date_emission, :cvv_display)
        ");
        $stmt->execute([
            'id_compte'             => $carte->getIdCompte(),
            'numero_carte'          => $carte->getNumeroCarte(),
            'type_carte'            => $carte->getTypeCarte(),
            'titulaire_nom'         => $carte->getTitulaireNom(),
            'reseau'                => $carte->getReseau(),
            'date_expiration'       => $carte->getDateExpiration(),
            'cvv_hash'              => $carte->getCvvHash(),
            'plafond_paiement_jour' => $carte->getPlafondPaiementJour(),
            'plafond_retrait_jour'  => $carte->getPlafondRetraitJour(),
            'statut'                => $carte->getStatut(),
            'style'                 => $carte->getStyle(),
            'date_emission'         => $carte->getDateEmission(),
            'cvv_display'           => $carte->getCvvDisplay(),
        ]);
        $carte->setIdCarte((int)$db->lastInsertId());
    }

    public static function update(CarteBancaire $carte): void
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("
            UPDATE cartebancaire SET
                type_carte            = :type_carte,
                titulaire_nom         = :titulaire_nom,
                reseau                = :reseau,
                date_expiration       = :date_expiration,
                plafond_paiement_jour = :plafond_paiement_jour,
                plafond_retrait_jour  = :plafond_retrait_jour,
                statut                = :statut,
                style                 = :style,
                date_activation       = :date_activation,
                motif_blocage         = :motif_blocage
            WHERE id_carte = :id_carte
        ");
        $stmt->execute([
            'type_carte'            => $carte->getTypeCarte(),
            'titulaire_nom'         => $carte->getTitulaireNom(),
            'reseau'                => $carte->getReseau(),
            'date_expiration'       => $carte->getDateExpiration(),
            'plafond_paiement_jour' => $carte->getPlafondPaiementJour(),
            'plafond_retrait_jour'  => $carte->getPlafondRetraitJour(),
            'statut'                => $carte->getStatut(),
            'style'                 => $carte->getStyle(),
            'date_activation'       => $carte->getDateActivation(),
            'motif_blocage'         => $carte->getMotifBlocage(),
            'id_carte'              => $carte->getIdCarte(),
        ]);
    }

    public static function deleteRecord(int $id): void
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("DELETE FROM cartebancaire WHERE id_carte = ?");
        $stmt->execute([$id]);
    }

    // ── DB: Finders ───────────────────────────────────────────
    public static function findByCompte(int $idCompte): array
    {
        self::syncExpiration();
        $db   = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM cartebancaire WHERE id_compte = ? ORDER BY id_carte DESC");
        $stmt->execute([$idCompte]);
        return array_map([self::class, 'fromRow'], $stmt->fetchAll());
    }

    public static function findById(int $id): ?CarteBancaire
    {
        self::syncExpiration();
        $db   = Config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM cartebancaire WHERE id_carte = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function findAll(): array
    {
        self::syncExpiration();
        $db   = Config::getConnexion();
        $stmt = $db->query("SELECT * FROM cartebancaire ORDER BY id_carte DESC");
        return array_map([self::class, 'fromRow'], $stmt->fetchAll());
    }

    // ── Business Actions ──────────────────────────────────────
    public static function demanderCarte(int $idCompte, string $type, string $titulaire, string $reseau, string $dateExp, string $cvv, float $plafondPay, float $plafondRet, string $style = ''): CarteBancaire
    {
        if ($style === '') {
            $style = self::computeStyleFromPlafond($plafondPay);
        }
        $carte = new CarteBancaire(
            0,
            $idCompte,
            self::generateCardNumber(),
            $type,
            $titulaire,
            $reseau,
            $dateExp ?: date('Y-m-t', strtotime('+4 years')),
            self::hashCvv($cvv),
            $plafondPay,
            $plafondRet,
            'inactive',
            $style,
            date('Y-m-d'),
            null,
            null,
            $cvv
        );
        self::save($carte);
        return $carte;
    }

    public static function activer(int $id): void
    {
        $carte = self::findById($id);
        if ($carte) {
            // If it was expired or pending reactivation, renew the expiration date
            if ($carte->getStatut() === 'expiree' || $carte->getStatut() === 'demande_reactivation') {
                $carte->setDateExpiration(date('Y-m-t', strtotime('+4 years')));
            }
            $carte->setStatut('active');
            $carte->setDateActivation(date('Y-m-d'));
            $carte->setMotifBlocage(null);
            self::update($carte);
        }
    }

    public static function bloquer(int $id, string $motif = ''): void
    {
        $carte = self::findById($id);
        if ($carte) {
            $carte->setStatut('bloquee');
            $carte->setMotifBlocage($motif);
            self::update($carte);
        }
    }

    public static function debloquer(int $id): void
    {
        $carte = self::findById($id);
        if ($carte) {
            $carte->setStatut('active');
            $carte->setMotifBlocage(null);
            self::update($carte);
        }
    }

    public static function demanderCloture(int $id): void
    {
        $carte = self::findById($id);
        if ($carte) {
            $carte->setStatut('demande_cloture');
            self::update($carte);
        }
    }

    public static function cloturer(int $id): void
    {
        self::deleteRecord($id);
    }

    public static function refuserClotureCarte(int $id): void
    {
        $carte = self::findById($id);
        if ($carte) {
            $carte->setStatut('inactive');
            self::update($carte);
        }
    }

    public static function demanderBlocage(int $id): void
    {
        $carte = self::findById($id);
        if ($carte) {
            $carte->setStatut('demande_blocage');
            self::update($carte);
        }
    }

    public static function demanderSuppression(int $id): void
    {
        $carte = self::findById($id);
        if ($carte) {
            $carte->setStatut('demande_suppression');
            self::update($carte);
        }
    }

    public static function demanderReactivation(int $id): void
    {
        $carte = self::findById($id);
        if ($carte) {
            $carte->setStatut('demande_reactivation');
            self::update($carte);
        }
    }

    public static function syncExpiration(): void
    {
        $db = Config::getConnexion();
        $db->exec("UPDATE cartebancaire SET statut = 'expiree' WHERE date_expiration < CURDATE() AND statut = 'active'");
    }

    // ── Request Handler ───────────────────────────────────────
    public static function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        Config::autoLogin();

        // ── CSRF CHECK ───────────────────────────────────────
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($token)) {
            error_log("[Security] CSRF Violation attempted (Carte).");
            die('Access denied: Invalid CSRF token.');
        }

        $action        = trim($_POST['action']             ?? '');
        $idCarte       = (int)($_POST['id_carte']          ?? 0);
        $origin        = trim($_POST['origin']             ?? ''); // 'front' ou 'back'
        $idCompteRedir = (int)($_POST['redirect_id_compte'] ?? 0);

        switch ($action) {
            case 'add': {
                $kyc        = trim($_SESSION['user']['status_kyc'] ?? '');
                $idCompte   = (int)($_POST['id_compte']               ?? 0);
                $compte     = CompteController::findById($idCompte);

                if ($kyc !== 'VERIFIE') {
                    header('Location: ' . APP_URL . '/views/frontoffice/frontoffice_compte.php' . ($idCompte ? '?id_compte='.$idCompte.'&error=kyc_required' : '?error=kyc_required'));
                    exit;
                }

                $type       = trim($_POST['type_carte']               ?? 'debit');
                $titulaire  = trim($_POST['titulaire_nom']            ?? '');
                $reseau     = trim($_POST['reseau']                   ?? 'visa');
                $dateExp    = ''; // Forced empty - user cannot modify this
                $cvv        = trim($_POST['cvv']                      ?? (string)rand(100, 999));
                $plafondPay = (float)($_POST['plafond_paiement_jour'] ?? 1000);
                $plafondRet = (float)($_POST['plafond_retrait_jour']  ?? 500);

                $errors = [];
                if ($idCompte <= 0 || !$compte) {
                    $errors['id_compte'] = "Veuillez sélectionner un compte valide.";
                } elseif ($compte->getTypeCompte() === 'epargne') {
                    $errors['id_compte'] = "Les comptes épargne ne sont pas éligibles aux cartes bancaires.";
                }
                if (!preg_match('/^[\p{L}\s]+$/u', $titulaire)) {
                    $errors['titulaire_nom'] = "Le nom ne doit contenir que des lettres et des espaces.";
                }
                if ($plafondPay < 10) {
                    $errors['plafond_paiement_jour'] = "Le plafond de paiement doit être d'au moins 10.";
                }
                if ($plafondRet < 10) {
                    $errors['plafond_retrait_jour'] = "Le plafond de retrait doit être d'au moins 10.";
                }

                if (!empty($errors)) {
                    $_SESSION['form_errors'] = $errors;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: ' . APP_URL . '/views/frontoffice/frontoffice_compte.php?form=carte' . ($idCompte ? '&id_compte='.$idCompte : ''));
                    exit;
                }

                self::demanderCarte($idCompte, $type, $titulaire, $reseau, $dateExp, $cvv, $plafondPay, $plafondRet, '');
                header('Location: ' . APP_URL . '/views/frontoffice/frontoffice_compte.php?id_compte=' . $idCompte . '&ok=carte_demande');
                exit;
            }

            case 'activer':
                self::activer($idCarte);
                $carte = self::findById($idCarte);
                if ($carte) {
                    $u = self::getUserForCarte($idCarte);
                    sendCarteNotification($u, $carte, "Votre carte a été activée !", "#16a34a", "Active");
                }
                break;
            case 'bloquer':
                self::bloquer($idCarte, trim($_POST['motif'] ?? 'Bloquée'));
                $carte = self::findById($idCarte);
                if ($carte) {
                    $u = self::getUserForCarte($idCarte);
                    sendCarteNotification($u, $carte, "Votre carte a été bloquée", "#dc2626", "Bloquée");
                }
                break;
            case 'debloquer':
                self::debloquer($idCarte);
                $carte = self::findById($idCarte);
                if ($carte) {
                    $u = self::getUserForCarte($idCarte);
                    sendCarteNotification($u, $carte, "Votre carte a été débloquée", "#16a34a", "Active");
                }
                break;
            case 'demander_blocage': self::demanderBlocage($idCarte);                             break;
            case 'demander_cloture': self::demanderCloture($idCarte);                             break;
            case 'demander_reactivation': self::demanderReactivation($idCarte);                   break;
            case 'demande_suppression': self::demanderSuppression($idCarte);                      break;
            case 'delete':
                $carte = self::findById($idCarte);
                if ($carte) {
                    $u = self::getUserForCarte($idCarte);
                    sendCarteNotification($u, $carte, "Votre carte a été supprimée", "#dc2626", "Supprimée");
                }
                self::cloturer($idCarte);
                break;
            case 'refuser_cloture':
                self::refuserClotureCarte($idCarte);
                $carte = self::findById($idCarte);
                if ($carte) {
                    $u = self::getUserForCarte($idCarte);
                    sendCarteNotification($u, $carte, "Votre demande de clôture de carte a été refusée", "#ea580c", "Status actuel conservé");
                }
                break;
            case 'refuser_blocage': {
                $carte = self::findById($idCarte);
                if ($carte) { 
                    $carte->setStatut('active'); 
                    self::update($carte); 
                    
                    $u = self::getUserForCarte($idCarte);
                    sendCarteNotification($u, $carte, "Votre demande de blocage de carte a été refusée", "#ea580c", "Active");
                }
                break;
            }

            case 'update': {
                $carte = self::findById($idCarte);
                if ($carte) {
                    $errors = [];
                    if (isset($_POST['titulaire_nom']) && !preg_match('/^[\p{L}\s]+$/u', trim($_POST['titulaire_nom']))) {
                        $errors['titulaire_nom'] = "Le nom ne doit contenir que des lettres et des espaces.";
                    }
                    if (isset($_POST['plafond_paiement_jour']) && (float)$_POST['plafond_paiement_jour'] < 10) {
                        $errors['plafond_paiement_jour'] = "Le plafond de paiement doit être d'au moins 10.";
                    }
                    if (isset($_POST['plafond_retrait_jour']) && (float)$_POST['plafond_retrait_jour'] < 10) {
                        $errors['plafond_retrait_jour'] = "Le plafond de retrait doit être d'au moins 10.";
                    }
                    
                    if (!empty($errors)) {
                        $_SESSION['form_errors'] = $errors;
                        $_SESSION['form_data'] = $_POST;
                        header('Location: ' . APP_URL . '/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=' . $idCompteRedir . '&edit_carte=' . $idCarte);
                        exit;
                    }

                    if (isset($_POST['type_carte']))             $carte->setTypeCarte(trim($_POST['type_carte']));
                    if (isset($_POST['titulaire_nom']))          $carte->setTitulaireNom(trim($_POST['titulaire_nom']));
                    if (isset($_POST['reseau']))                 $carte->setReseau(trim($_POST['reseau']));
                    if (isset($_POST['date_expiration']))        $carte->setDateExpiration(trim($_POST['date_expiration']));
                    if (isset($_POST['statut']))                 $carte->setStatut(trim($_POST['statut']));
                    if (isset($_POST['motif_blocage']))          $carte->setMotifBlocage(trim($_POST['motif_blocage']));
                    if (isset($_POST['style']))                  $carte->setStyle(trim($_POST['style']));
                    if (isset($_POST['plafond_paiement_jour']))  $carte->setPlafondPaiementJour((float)$_POST['plafond_paiement_jour']);
                    if (isset($_POST['plafond_retrait_jour']))   $carte->setPlafondRetraitJour((float)$_POST['plafond_retrait_jour']);
                    self::update($carte);
                    
                    $u = self::getUserForCarte($idCarte);
                    sendCarteNotification($u, $carte, "Les paramètres de votre carte ont été modifiés", "#2563eb", $carte->getStatut());
                }
                break;
            }
        }

        // Redirect to front or back based on origin field
        if ($origin === 'front' && $idCompteRedir) {
            header('Location: ' . APP_URL . '/views/frontoffice/frontoffice_compte.php?id_compte=' . $idCompteRedir);
        } elseif ($idCompteRedir) {
            header('Location: ' . APP_URL . '/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=' . $idCompteRedir);
        } else {
            $back = !empty($_SERVER['HTTP_REFERER'])
                ? $_SERVER['HTTP_REFERER']
                : APP_URL . '/views/backoffice/backoffice_compte.php';
            header('Location: ' . $back);
        }
        exit;
    }
}

// Invoke the handler only when this file is the entry point (not when included)
if (basename($_SERVER["SCRIPT_FILENAME"]) === basename(__FILE__)) {
    CarteController::handleRequest();
}
?>
