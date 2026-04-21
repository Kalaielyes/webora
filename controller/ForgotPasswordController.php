<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../vendor/autoload.php';   // Twilio SDK (Composer)
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/Session.php';
require_once __DIR__ . '/../model/Utilisateur.php';
require_once __DIR__ . '/../model/PasswordReset.php';

use Twilio\Rest\Client;

Session::start();

// ─── Credentials Twilio ───────────────────────────────────────
define('TWILIO_SID',   'TWILIO_SID_HERE');
define('TWILIO_TOKEN', 'TWILIO_TOKEN_HERE');
define('TWILIO_FROM',  'whatsapp:+14155238886');            // ← numéro sandbox Twilio

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/web');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';


if ($action === 'forgot_password') {

    $email  = strtolower(trim($_POST['email'] ?? ''));
    $errors = [];

    // Validation email
    if (empty($email)) {
        $errors['email'] = "L'adresse e-mail est requise.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'e-mail invalide.";
    }

    if (!empty($errors)) {
        Session::set('forgot_errors', $errors);
        Session::set('old_forgot', ['email' => $email]);
        header('Location: ../view/FrontOffice/forgot_password.php');
        exit;
    }

    $m    = new Utilisateur();
    $pr   = new PasswordReset();
    $user = $m->findByEmail($email);

    // Toujours afficher le même message (sécurité anti-énumération)
    $successMsg = 'Si cet email est enregistré, vous recevrez un lien sur WhatsApp dans quelques secondes.';

    if ($user) {

        // Vérifier que l'utilisateur a un numéro de téléphone
        if (empty($user['numTel'])) {
            Session::setFlash('success', $successMsg);
            header('Location: ../view/FrontOffice/forgot_password.php');
            exit;
        }

        // Générer le token (brut → lien, hash → DB)
        $rawToken = $pr->createToken((int) $user['id']);

        // Construire le lien de réinitialisation
        $resetUrl = BASE_URL . '/view/FrontOffice/reset_password.php?token=' . urlencode($rawToken);

        // Construire le message WhatsApp
        $nom     = $user['nom'] . ' ' . $user['prenom'];
       $message = "Bonjour {$nom} 👋\n\n"
         . "Vous avez demande la reinitialisation de votre mot de passe LegalFin.\n\n"
         . "🔐 Cliquez sur ce lien pour choisir un nouveau mot de passe :\n"
         . $resetUrl . "\n\n"
         . "⏰ Ce lien est valide pendant 30 minutes.\n"
         . "⚠️ Si vous n'avez pas fait cette demande, ignorez ce message.\n\n"
         . "-- Equipe LegalFin";

        // Formater le numéro et envoyer via Twilio
        $phone = formatPhoneForWhatsApp($user['numTel']);
        $sent  = sendWhatsAppTwilio($phone, $message);

        if (!$sent) {
            error_log("[NexaBank] Twilio send failed for user ID {$user['id']} — phone: $phone");
        }
    }

    Session::setFlash('success', $successMsg);
    header('Location: ../view/FrontOffice/forgot_password.php');
    exit;
}


if ($action === 'reset_password') {

    $rawToken   = trim($_POST['token']       ?? '');
    $mdp        = $_POST['mdp']              ?? '';
    $mdpConfirm = $_POST['mdp_confirm']      ?? '';
    $errors     = [];

    // Valider le token AVANT tout
    $pr     = new PasswordReset();
    $userId = $pr->validateToken($rawToken);

    if (!$userId) {
        Session::setFlash('error', 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.');
        header('Location: ../view/FrontOffice/forgot_password.php');
        exit;
    }

    // Validation mot de passe
    if (empty($mdp)) {
        $errors['mdp'] = "Le mot de passe est requis.";
    } else {
        if (strlen($mdp) < 8) {
            $errors['mdp'] = "Au moins 8 caractères.";
        } elseif (!preg_match('/[A-Z]/', $mdp)) {
            $errors['mdp'] = "Au moins une lettre majuscule.";
        } elseif (!preg_match('/[0-9]/', $mdp)) {
            $errors['mdp'] = "Au moins un chiffre.";
        }
    }

    if ($mdp !== $mdpConfirm) {
        $errors['mdp_confirm'] = "Les mots de passe ne correspondent pas.";
    }

    if (!empty($errors)) {
        Session::set('reset_errors', $errors);
        header('Location: ../view/FrontOffice/reset_password.php?token=' . urlencode($rawToken));
        exit;
    }

    // Changer le mot de passe
    $m = new Utilisateur();
    try {
        $m->resetPassword($userId, $mdp);
        $pr->markAsUsed($rawToken);

        Session::setFlash('success', 'Mot de passe modifié avec succès. Connectez-vous.');
        header('Location: ../view/FrontOffice/login.php');
    } catch (Exception $e) {
        error_log('[NexaBank] Reset password error: ' . $e->getMessage());
        Session::setFlash('error', 'Une erreur est survenue. Réessayez.');
        header('Location: ../view/FrontOffice/reset_password.php?token=' . urlencode($rawToken));
    }
    exit;
}

// Fallback
header('Location: ../view/FrontOffice/login.php');
exit;




function formatPhoneForWhatsApp(string $phone): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);

    if (str_starts_with($clean, '216') && strlen($clean) === 11) return $clean;
    if (str_starts_with($clean, '00216'))                        return ltrim($clean, '0');
    if (strlen($clean) === 8)                                    return '216' . $clean;

    return $clean;
}


function sendWhatsAppTwilio(string $phone, string $message): bool {

    $sid      = TWILIO_SID;
    $token    = TWILIO_TOKEN;
    $fromNum  = TWILIO_FROM; 

    if (empty($sid) || empty($token) || empty($fromNum)) {
        error_log('[LegalFin] Twilio credentials manquants dans .env');
        return false;
    }

    $toNum = 'whatsapp:+' . $phone;

    try {
        $client = new Client($sid, $token);
        $client->messages->create($toNum, [
            'from' => $fromNum,
            'body' => $message,
        ]);
        error_log("[LegalFin] WhatsApp envoyé avec succès à $toNum");
        return true;

    } catch (Exception $e) {
        error_log('[LegalFin] Twilio error: ' . $e->getMessage());
        return false;
    }
}
