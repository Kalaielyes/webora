<?php
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/MailService.php';
Session::start();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';
    $errors = [];
    if (empty($email)) {
        $errors['email'] = "L'adresse e-mail est requise.";
    } elseif (strpos($email, '@') === false) {
        $errors['email'] = "L'adresse e-mail doit contenir le symbole '@'.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'e-mail invalide (exemple: nom@domaine.com).";
    }
    if (empty($mdp)) {
        $errors['mdp'] = "Le mot de passe est requis.";
    }
    if (!empty($errors)) {
        Session::set('login_errors', $errors);
        Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
        Session::set('old_login', ['email' => $email]);
        header('Location: ../view/frontoffice/login.php'); 
        exit;
    }
    $m    = new Utilisateur();
    $user = $m->findByEmail($email);
    if (!$user || !$m->verifyPassword($mdp, $user['mdp'])) {
        $errors['general'] = 'Email ou mot de passe incorrect.';
        Session::set('login_errors', $errors);
        Session::setFlash('error', 'Email ou mot de passe incorrect.');
        Session::set('old_login', ['email' => $email]);
        header('Location: ../view/frontoffice/login.php'); 
        exit;
    }
    if ($user['status'] !== 'ACTIF') {
        if (isset($user['is_verified']) && $user['is_verified'] == 0) {
            $errors['general'] = 'Votre adresse e-mail n\'est pas encore vérifiée. Veuillez vérifier vos e-mails.';
            Session::set('temp_verify_user_id', $user['id']);
            Session::set('temp_verify_email', $user['email']);
            Session::setFlash('error', 'Veuillez vérifier votre e-mail pour activer votre compte.');
            header('Location: ../view/frontoffice/verify_email.php');
            exit;
        }
        $errors['general'] = 'Compte suspendu ou inactif. Contactez le support.';
        Session::set('login_errors', $errors);
        Session::setFlash('error', 'Compte suspendu ou inactif. Contactez le support.');
        Session::set('old_login', ['email' => $email]);
        header('Location: ../view/frontoffice/login.php'); 
        exit;
    }
    Session::remove('old_login');
    Session::remove('login_errors');

    // 2FA Check
    if (isset($user['two_factor_enabled']) && $user['two_factor_enabled'] == 1) {
        Session::set('temp_2fa_user_id', $user['id']);
        header('Location: ../view/frontoffice/2fa_challenge.php');
        exit;
    }

    Session::set('user_id',      $user['id']);
    Session::set('user_nom',     $user['nom']);
    Session::set('user_prenom',  $user['prenom']);
    Session::set('user_email',   $user['email']);
    Session::set('user_cin',     $user['cin']);
    Session::set('status_kyc',   $user['status_kyc']);
    Session::set('status_aml',   $user['status_aml']);
    Session::set('role',         $user['role']);
    Session::set('niveau_acces', $user['niveau_acces']);
    Session::set('user',         $user);
    $m->updateDerniereConnexion($user['id']);
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($ip === '::1' || $ip === '127.0.0.1') $ip = '196.203.221.129'; 
    $geoJson = @file_get_contents("http://ip-api.com/json/{$ip}");
    if ($geoJson) {
        $geo = json_decode($geoJson, true);
        if ($geo && $geo['status'] === 'success') {
            $m->updateGeoLocation($user['id'], $ip, $geo['city'], $geo['lat'], $geo['lon']);
        }
    }
    if (in_array($user['role'], ['ADMIN','SUPER_ADMIN'])) {
        header('Location: ../view/backoffice/backoffice_utilisateur.php');
    } else {
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php');
    }
    exit;
    exit;
}

if ($action === 'verify_2fa') {
    $code = trim($_POST['code'] ?? '');
    $tempUserId = Session::get('temp_2fa_user_id');

    if (!$tempUserId) {
        header('Location: ../view/frontoffice/login.php');
        exit;
    }

    $m = new Utilisateur();
    $user = $m->findById($tempUserId);

    if (!$user || $user['two_factor_enabled'] != 1) {
        header('Location: ../view/frontoffice/login.php');
        exit;
    }

    require_once __DIR__ . '/../models/GoogleAuthenticator.php';
    $ga = new GoogleAuthenticator();

    if ($ga->verifyCode($user['two_factor_secret'], $code, 2)) {
        // Success, log them in
        Session::remove('temp_2fa_user_id');
        Session::remove('login_errors');
        
        Session::set('user_id',      $user['id']);
        Session::set('user_nom',     $user['nom']);
        Session::set('user_prenom',  $user['prenom']);
        Session::set('user_email',   $user['email']);
        Session::set('user_cin',     $user['cin']);
        Session::set('status_kyc',   $user['status_kyc']);
        Session::set('status_aml',   $user['status_aml']);
        Session::set('role',         $user['role']);
        Session::set('niveau_acces', $user['niveau_acces']);
        Session::set('user',         $user);
        
        $m->updateDerniereConnexion($user['id']);
        
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip === '::1' || $ip === '127.0.0.1') $ip = '196.203.221.129'; 
        $geoJson = @file_get_contents("http://ip-api.com/json/{$ip}");
        if ($geoJson) {
            $geo = json_decode($geoJson, true);
            if ($geo && $geo['status'] === 'success') {
                $m->updateGeoLocation($user['id'], $ip, $geo['city'], $geo['lat'], $geo['lon']);
            }
        }
        
        if (in_array($user['role'], ['ADMIN','SUPER_ADMIN'])) {
            header('Location: ../view/backoffice/backoffice_utilisateur.php');
        } else {
            header('Location: ../view/frontoffice/frontoffice_utilisateur.php');
        }
        exit;
    } else {
        Session::set('login_errors', ['code' => 'Code incorrect. Veuillez réessayer.']);
        header('Location: ../view/frontoffice/2fa_challenge.php');
        exit;
    }
}

if ($action === 'verify_otp') {
    $code = trim($_POST['code'] ?? '');
    $userId = Session::get('temp_verify_user_id');

    if (!$userId) {
        header('Location: ../view/frontoffice/login.php');
        exit;
    }

    $m = new Utilisateur();
    $user = $m->findById($userId);

    if ($user && $user['verification_code'] === $code) {
        $m->verifyEmail($userId);
        Session::remove('temp_verify_user_id');
        Session::remove('temp_verify_email');
        Session::setFlash('success', 'Votre adresse e-mail a été vérifiée. Vous pouvez maintenant vous connecter.');
        header('Location: ../view/frontoffice/login.php');
    } else {
        Session::set('verify_errors', ['code' => 'Code de vérification incorrect.']);
        header('Location: ../view/frontoffice/verify_email.php');
    }
    exit;
}

if ($action === 'resend_otp') {
    $userId = Session::get('temp_verify_user_id');
    $email = Session::get('temp_verify_email');

    if (!$userId || !$email) {
        header('Location: ../view/frontoffice/login.php');
        exit;
    }

    $m = new Utilisateur();
    $user = $m->findById($userId);
    
    if ($user) {
        $otp = sprintf("%06d", mt_rand(0, 999999));
        $m->updateVerificationCode($userId, $otp);
        MailService::sendOTP($email, $user['prenom'] . ' ' . $user['nom'], $otp);
        Session::setFlash('success', 'Un nouveau code a été envoyé.');
    }
    header('Location: ../view/frontoffice/verify_email.php');
    exit;
}

if ($action === 'register') {
    $account_type   = trim($_POST['account_type'] ?? 'personal');
    $nom            = trim($_POST['nom']            ?? '');
    $prenom         = trim($_POST['prenom']         ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $cin            = trim($_POST['cin']            ?? '');
    $email          = trim($_POST['email']          ?? '');
    $numTel         = trim($_POST['numTel']         ?? '');
    $gouvernorat    = trim($_POST['gouvernorat']    ?? '');
    $adresse        = trim($_POST['adresse']        ?? '');
    $mdp            = $_POST['mdp']         ?? '';
    $mdp_confirm    = $_POST['mdp_confirm'] ?? '';
    $terms          = isset($_POST['terms']);
    $kyc_consent    = isset($_POST['kyc_consent']);
    $association    = $account_type === 'association';
    $old = compact('account_type','nom','prenom','date_naissance','cin','email','numTel','gouvernorat','adresse');
    $errors = [];
    if (!$association) {
        if (empty($nom)) {
            $errors['nom'] = "Le nom est requis.";
        } elseif (!preg_match('/^[\p{L}\s\-\']{2,50}$/u', $nom)) {
            $errors['nom'] = "Le nom ne doit contenir que des lettres (2 à 50 caractères).";
        }
        if (empty($prenom)) {
            $errors['prenom'] = "Le prénom est requis.";
        } elseif (!preg_match('/^[\p{L}\s\-\']{2,50}$/u', $prenom)) {
            $errors['prenom'] = "Le prénom ne doit contenir que des lettres (2 à 50 caractères).";
        }
    } else {
        if (empty($nom)) {
            $errors['nom'] = "Le nom de l'association est requis.";
        } elseif (!preg_match('/^[\p{L}0-9\s\-\']{2,80}$/u', $nom)) {
            $errors['nom'] = "Le nom de l'association contient des caractères invalides.";
        }
    }
    if (empty($date_naissance)) {
        $errors['date_naissance'] = "La date de naissance est requise.";
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $date_naissance);
        if (!$dt || $dt->format('Y-m-d') !== $date_naissance) {
            $errors['date_naissance'] = "Format de date invalide (AAAA-MM-JJ).";
        } else {
            $age = (new DateTime())->diff($dt)->y;
            if ($age < 18) {
                $errors['date_naissance'] = "Vous devez avoir au moins 18 ans pour vous inscrire.";
            } elseif ($age > 120) {
                $errors['date_naissance'] = "La date de naissance n'est pas plausible.";
            }
        }
    }
    if (!$association) {
        if (empty($cin)) {
            $errors['cin'] = "Le numéro CIN est requis.";
        } elseif (!ctype_digit($cin)) {
            $errors['cin'] = "Le CIN ne doit contenir que des chiffres.";
        } elseif (strlen($cin) !== 8) {
            $errors['cin'] = "Le CIN doit contenir exactement 8 chiffres.";
        }
    } else {
        // For associations, if CIN is hidden and empty, generate a placeholder
        if (empty($cin)) {
            $cin = '9' . str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            $old['cin'] = $cin; // Update old value for consistency
        }
    }
    if (empty($email)) {
        $errors['email'] = "L'adresse e-mail est requise.";
    } elseif (strpos($email, '@') === false) {
        $errors['email'] = "L'adresse e-mail doit contenir le symbole '@'.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'e-mail invalide (exemple: nom@domaine.com).";
    } elseif (strlen($email) > 100) {
        $errors['email'] = "L'adresse e-mail ne peut pas dépasser 100 caractères.";
    }
    if (empty($numTel)) {
        $errors['numTel'] = "Le numéro de téléphone est requis.";
    } else {
        $numTelClean = preg_replace('/[^0-9]/', '', $numTel);
        if (strlen($numTelClean) < 8) {
            $errors['numTel'] = "Le numéro de téléphone doit contenir au moins 8 chiffres.";
        } elseif (strlen($numTelClean) > 15) {
            $errors['numTel'] = "Le numéro de téléphone ne peut pas dépasser 15 chiffres.";
        }
    }
    if (empty($gouvernorat)) {
        $errors['gouvernorat'] = "Le gouvernorat est requis.";
    }
    if (empty($adresse)) {
        $errors['adresse'] = "L'adresse complète est requise.";
    } elseif (strlen($adresse) < 5) {
        $errors['adresse'] = "L'adresse doit comporter au moins 5 caractères.";
    } elseif (strlen($adresse) > 255) {
        $errors['adresse'] = "L'adresse ne peut pas dépasser 255 caractères.";
    }
    if (empty($mdp)) {
        $errors['mdp'] = "Le mot de passe est requis.";
    } else {
        if (strlen($mdp) < 8) {
            $errors['mdp'] = "Le mot de passe doit contenir au moins 8 caractères.";
        }
        if (!preg_match('/[A-Z]/', $mdp)) {
            $errors['mdp'] = "Le mot de passe doit contenir au moins une lettre majuscule.";
        }
        if (!preg_match('/[0-9]/', $mdp)) {
            $errors['mdp'] = "Le mot de passe doit contenir au moins un chiffre.";
        }
    }
    if ($mdp !== $mdp_confirm) {
        $errors['mdp_confirm'] = "Les mots de passe ne correspondent pas.";
    }
    if (!$terms) {
        $errors['terms'] = "Vous devez accepter les CGU et la Politique de confidentialité.";
    }
    if (!$kyc_consent) {
        $errors['kyc_consent'] = "Vous devez consentir à la vérification KYC/AML.";
    }
    if (!empty($errors)) {
        Session::set('register_errors', $errors);
        Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
        Session::set('old_register', $old);
        header('Location: ../view/frontoffice/signup.php'); 
        exit;
    }
    $m = new Utilisateur();
    try {
        if ($m->emailExiste($email)) {
            $errors['email'] = "Cette adresse e-mail est déjà utilisée.";
            Session::set('register_errors', $errors);
            Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
            Session::set('old_register', $old);
            header('Location: ../view/frontoffice/signup.php'); 
            exit;
        }
        if ($m->cinExiste($cin)) {
            $errors['cin'] = "Ce numéro de CIN est déjà enregistré.";
            Session::set('register_errors', $errors);
            Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
            Session::set('old_register', $old);
            header('Location: ../view/frontoffice/signup.php'); 
            exit;
        }
        $m->setAssociation($association);
        if ($association) {
            $prenom = 'association';
        }
        $m->setNom($nom);
        $m->setPrenom($prenom);
        $m->setEmail($email);
        $m->setMdp($mdp);
        $m->setNumTel($numTel);
        $m->setDateNaissance($date_naissance);
        $m->setAdresse($adresse);
        $m->setCin($cin);
        
        // Generate OTP
        $otp = sprintf("%06d", mt_rand(0, 999999));
        $m->setVerificationCode($otp);
        $m->setIsVerified(0);
        $m->setStatus('INACTIF'); // Account is inactive until email verified
        
        $userId = $m->create();
        
        // Send Email
        if (!MailService::sendOTP($email, "$prenom $nom", $otp)) {
            Session::setFlash('warning', 'Compte créé, mais l\'envoi de l\'e-mail a échoué. Veuillez cliquer sur "Renvoyer le code".');
        } else {
            Session::setFlash('success', 'Un code de vérification a été envoyé à votre adresse e-mail.');
        }

        Session::remove('old_register');
        Session::remove('register_errors');
        Session::set('temp_verify_user_id', $userId);
        Session::set('temp_verify_email', $email);
        
        header('Location: ../view/frontoffice/verify_email.php');
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
        Session::set('old_register', $old);
        header('Location: ../view/frontoffice/signup.php');
    }
    exit;
}
if ($action === 'login_face') {
    $email = trim($_POST['email'] ?? '');
    $imageData = $_POST['image'] ?? ''; // Base64 image
    
    if (empty($email) || empty($imageData)) {
        echo json_encode(['success' => false, 'error' => 'Email et image requis.']);
        exit;
    }

    $m = new Utilisateur();
    $user = $m->findByEmail($email);

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé.']);
        exit;
    }

    if (empty($user['selfie_path'])) {
        echo json_encode(['success' => false, 'error' => 'Aucun Face ID configuré pour ce compte. Veuillez d\'abord valider votre KYC.']);
        exit;
    }

    if ($user['status'] !== 'ACTIF') {
        echo json_encode(['success' => false, 'error' => 'Compte suspendu ou inactif.']);
        exit;
    }

    // Save temporary login selfie
    $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $data = base64_decode($imageData);
    
    $tempFileName = 'login_' . uniqid() . '.jpg';
    $tempPath = __DIR__ . '/../view/assets/uploads/' . $tempFileName;
    file_put_contents($tempPath, $data);
    $relativeTempPath = 'view/assets/uploads/' . $tempFileName;

    require_once __DIR__ . '/../models/FaceVerificationService.php';
    $faceService = new FaceVerificationService();
    $faceResult  = $faceService->compareFaces($user['selfie_path'], $relativeTempPath);

    // Cleanup temp file
    if (file_exists($tempPath)) unlink($tempPath);

    if ($faceResult['success'] && $faceResult['score'] >= 80) {
        // Success, log them in
        Session::set('user_id',      $user['id']);
        Session::set('user_nom',     $user['nom']);
        Session::set('user_prenom',  $user['prenom']);
        Session::set('user_email',   $user['email']);
        Session::set('role',         $user['role']);
        Session::set('user',         $user);
        
        $m->updateDerniereConnexion($user['id']);
        
        echo json_encode(['success' => true, 'redirect' => (in_array($user['role'], ['ADMIN','SUPER_ADMIN']) ? '../backoffice/backoffice_utilisateur.php' : 'frontoffice_utilisateur.php')]);
    } else {
        $score = $faceResult['score'] ?? 0;
        echo json_encode(['success' => false, 'error' => "Reconnaissance faciale échouée (score: $score/100).", 'score' => $score]);
    }
    exit;
}
if ($action === 'logout') {
    Session::destroy();
    header('Location: ../view/frontoffice/login.php');
    exit;
}
header('Location: ../view/frontoffice/login.php');
exit;
