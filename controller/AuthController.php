<?php
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/Session.php';
require_once __DIR__ . '/../model/Utilisateur.php';

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
        header('Location: ../view/FrontOffice/login.php'); 
        exit;
    }

    $m    = new Utilisateur();
    $user = $m->findByEmail($email);

    if (!$user || !$m->verifyPassword($mdp, $user['mdp'])) {
        $errors['general'] = 'Email ou mot de passe incorrect.';
        Session::set('login_errors', $errors);
        Session::setFlash('error', 'Email ou mot de passe incorrect.');
        Session::set('old_login', ['email' => $email]);
        header('Location: ../view/FrontOffice/login.php'); 
        exit;
    }
    
    if ($user['status'] !== 'ACTIF') {
        $errors['general'] = 'Compte suspendu ou inactif. Contactez le support.';
        Session::set('login_errors', $errors);
        Session::setFlash('error', 'Compte suspendu ou inactif. Contactez le support.');
        Session::set('old_login', ['email' => $email]);
        header('Location: ../view/FrontOffice/login.php'); 
        exit;
    }

    Session::remove('old_login');
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
    $m->updateDerniereConnexion($user['id']);

    if (in_array($user['role'], ['ADMIN','SUPER_ADMIN'])) {
        header('Location: ../view/backoffice/backoffice_utilisateur.php');
    } else {
        header('Location: ../view/FrontOffice/frontoffice_utilisateur.php');
    }
    exit;
}




if ($action === 'register') {
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

    $old = compact('nom','prenom','date_naissance','cin','email','numTel','gouvernorat','adresse');
    $errors = [];

    
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

    
    if (empty($cin)) {
        $errors['cin'] = "Le numéro CIN est requis.";
    } elseif (!ctype_digit($cin)) {
        $errors['cin'] = "Le CIN ne doit contenir que des chiffres.";
    } elseif (strlen($cin) !== 8) {
        $errors['cin'] = "Le CIN doit contenir exactement 8 chiffres.";
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
        header('Location: ../view/FrontOffice/signup.php'); 
        exit;
    }

    $m = new Utilisateur();
    try {
        if ($m->emailExiste($email)) {
            $errors['email'] = "Cette adresse e-mail est déjà utilisée.";
            Session::set('register_errors', $errors);
            Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
            Session::set('old_register', $old);
            header('Location: ../view/FrontOffice/signup.php'); 
            exit;
        }
        if ($m->cinExiste($cin)) {
            $errors['cin'] = "Ce numéro de CIN est déjà enregistré.";
            Session::set('register_errors', $errors);
            Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
            Session::set('old_register', $old);
            header('Location: ../view/FrontOffice/signup.php'); 
            exit;
        }

        $m->setNom($nom);
        $m->setPrenom($prenom);
        $m->setEmail($email);
        $m->setMdp($mdp);
        $m->setNumTel($numTel);
        $m->setDateNaissance($date_naissance);
        $m->setAdresse($adresse);
        $m->setCin($cin);
        $m->create();

        Session::remove('old_register');
        Session::remove('register_errors');
        Session::setFlash('success', 'Compte créé avec succès. Connectez-vous.');
        header('Location: ../view/FrontOffice/login.php');
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
        Session::set('old_register', $old);
        header('Location: ../view/FrontOffice/signup.php');
    }
    exit;
}




if ($action === 'logout') {
    Session::destroy();
    header('Location: ../view/FrontOffice/login.php');
    exit;
}

header('Location: ../view/FrontOffice/login.php');
exit;

