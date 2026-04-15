<?php


require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/Session.php';
require_once __DIR__ . '/../model/Utilisateur.php';

Session::start();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$m      = new Utilisateur();


// Store error flash and previous form values
function setErrors(array $errors, string $oldKey, array $old): void {
    Session::setFlash('error', implode('<br>', $errors));
    Session::set($oldKey, $old);
}


if ($action === 'update_profil') {
    Session::requireLogin('../view/FrontOffice/login.php');
    $id = (int) Session::get('user_id');

    $nom     = trim($_POST['nom']     ?? '');
    $prenom  = trim($_POST['prenom']  ?? '');
    $numTel  = trim($_POST['numTel']  ?? '');
    $adresse = trim($_POST['adresse'] ?? '');

    $old    = compact('nom','prenom','numTel','adresse');
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

    
    if (empty($adresse)) {
        $errors['adresse'] = "L'adresse est requise.";
    } elseif (strlen($adresse) < 5) {
        $errors['adresse'] = "L'adresse doit comporter au moins 5 caractères.";
    } elseif (strlen($adresse) > 255) {
        $errors['adresse'] = "L'adresse ne peut pas dépasser 255 caractères.";
    }

    if (!empty($errors)) {
        Session::set('profil_errors', $errors);
        Session::set('old_profil', $old);
        Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
        header('Location: ../view/FrontOffice/frontoffice_utilisateur.php'); 
        exit;
    }

    try {
        $m->updateProfil($id, compact('nom','prenom','numTel','adresse'));
        Session::set('user_nom',    $nom);
        Session::set('user_prenom', $prenom);
        Session::remove('old_profil');
        Session::remove('profil_errors');
        Session::setFlash('success', 'Profil mis à jour.');
    } catch (Exception $e) {
        $errors['general'] = $e->getMessage();
        Session::set('profil_errors', $errors);
        Session::set('old_profil', $old);
        Session::setFlash('error', 'Erreur lors de la mise à jour.');
    }
    header('Location: ../view/FrontOffice/frontoffice_utilisateur.php'); 
    exit;
}


if ($action === 'update_password') {
    Session::requireLogin('../view/FrontOffice/login.php');
    $id          = (int) Session::get('user_id');
    $ancien_mdp  = $_POST['ancien_mdp']  ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirm_mdp = $_POST['confirm_mdp'] ?? '';

    $errors = [];

    if (empty($ancien_mdp)) {
        $errors[] = "L'ancien mot de passe est requis.";
    }

    if (empty($nouveau_mdp)) {
        $errors[] = "Le nouveau mot de passe est requis.";
    } else {
        if (strlen($nouveau_mdp) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }
        if (!preg_match('/[A-Z]/', $nouveau_mdp)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins une lettre majuscule.";
        }
        if (!preg_match('/[0-9]/', $nouveau_mdp)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins un chiffre.";
        }
    }

    if ($nouveau_mdp !== $confirm_mdp) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!empty($errors)) {
        Session::set('pwd_errors', $errors);
        Session::setFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
        Session::set('pwd_error', true);
        header('Location: ../view/FrontOffice/frontoffice_utilisateur.php'); 
        exit;
    }

    try {
        $m->updatePassword($id, $ancien_mdp, $nouveau_mdp);
        Session::remove('pwd_errors');
        Session::setFlash('success', 'Mot de passe modifié.');
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        Session::set('pwd_errors', $errors);
        Session::setFlash('error', $e->getMessage());
        Session::set('pwd_error', true);
    }
    header('Location: ../view/FrontOffice/frontoffice_utilisateur.php'); 
    exit;
}




if ($action === 'upload_file') {
    Session::requireLogin('../view/FrontOffice/login.php');
    $id = (int) Session::get('user_id');

    
    $user = $m->findById($id);
    if (!empty($user['id_file_path'])) {
        Session::setFlash('error', 'Vous avez déjà déposé un fichier ID. Contactez l\'administration pour modifications.');
        header('Location: ../view/FrontOffice/frontoffice_utilisateur.php');
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        Session::setFlash('error', 'Erreur lors du dépôt de l\'ID.');
        header('Location: ../view/FrontOffice/frontoffice_utilisateur.php');
        exit;
    }

    $file = $_FILES['file'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; 

    if (!in_array($file['type'], $allowedTypes)) {
        Session::setFlash('error', 'Type de fichier non autorisé. Seuls JPEG, PNG, GIF et PDF sont acceptés pour l\'ID.');
        header('Location: ../view/FrontOffice/frontoffice_utilisateur.php');
        exit;
    }

    if ($file['size'] > $maxSize) {
        Session::setFlash('error', 'L\'ID est trop volumineux. Taille maximale : 5MB.');
        header('Location: ../view/FrontOffice/frontoffice_utilisateur.php');
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    $relativePath = 'uploads/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        
        try {
            $m->updateFilePath($id, $relativePath);
            Session::setFlash('success', 'ID déposé avec succès.');
        } catch (Exception $e) {
            
            unlink($filePath);
            Session::setFlash('error', 'Erreur lors de la sauvegarde en base de données.');
        }
    } else {
        Session::setFlash('error', 'Erreur lors de la sauvegarde de l\'ID.');
    }

    header('Location: ../view/FrontOffice/frontoffice_utilisateur.php');
    exit;
}




if ($action === 'admin_add') {
    Session::requireAdmin('../view/FrontOffice/login.php');

    $nom            = trim($_POST['nom']            ?? '');
    $prenom         = trim($_POST['prenom']         ?? '');
    $email          = trim($_POST['email']          ?? '');
    $mdp            = $_POST['mdp']                 ?? '';
    $numTel         = trim($_POST['numTel']         ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $adresse        = trim($_POST['adresse']        ?? '');
    $cin            = trim($_POST['cin']            ?? '');
    $role           = trim($_POST['role']           ?? 'CLIENT');

    $old    = compact('nom','prenom','email','numTel','date_naissance','adresse','cin','role');
    $errors = [];

    if (empty($nom) || !preg_match('/^[\p{L}\s\-\']{2,50}$/u', $nom)) {
        $errors[] = "Nom invalide (lettres uniquement, 2–50 caracteres).";
    }
    if (empty($prenom) || !preg_match('/^[\p{L}\s\-\']{2,50}$/u', $prenom)) {
        $errors[] = "Prenom invalide (lettres uniquement, 2–50 caracteres).";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail est invalide.";
    }
    if (empty($mdp) || strlen($mdp) < 8) {
        $errors[] = "Le mot de passe doit comporter au moins 8 caracteres.";
    }
    if (empty($numTel) || !preg_match('/^[\+\d\s\-\(\)]{7,20}$/', $numTel)) {
        $errors[] = "Le numero de telephone est invalide.";
    }
    if (empty($date_naissance)) {
        $errors[] = "La date de naissance est requise.";
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $date_naissance);
        if (!$dt || $dt->format('Y-m-d') !== $date_naissance) {
            $errors[] = "La date de naissance est invalide.";
        } else {
            $age = (new DateTime())->diff($dt)->y;
            if ($age < 18 || $age > 120) {
                $errors[] = "La date de naissance n'est pas plausible (min. 18 ans).";
            }
        }
    }
    if (empty($adresse) || strlen($adresse) < 5) {
        $errors[] = "L'adresse doit comporter au moins 5 caracteres.";
    }
    if (!preg_match('/^\d{8}$/', $cin)) {
        $errors[] = "Le CIN doit contenir exactement 8 chiffres.";
    }
    if (!in_array($role, ['CLIENT','ADMIN','SUPER_ADMIN'])) {
        $errors[] = "Le role selectionne est invalide.";
    }

    if (!empty($errors)) {
        setErrors($errors, 'old_admin_add', $old);
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    try {
        if ($m->emailExiste($email)) {
            setErrors(['Cette adresse e-mail est deja utilisee.'], 'old_admin_add', $old);
            header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
        }
        if ($m->cinExiste($cin)) {
            setErrors(['Ce numero de CIN est deja enregistre.'], 'old_admin_add', $old);
            header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
        }

        $m->setNom($nom);       $m->setPrenom($prenom);
        $m->setEmail($email);   $m->setMdp($mdp);
        $m->setNumTel($numTel); $m->setDateNaissance($date_naissance);
        $m->setAdresse($adresse); $m->setCin($cin);
        $m->setRole($role);
        $m->create();

        Session::remove('old_admin_add');
        Session::setFlash('success', 'Utilisateur ajoute.');
    } catch (Exception $e) {
        setErrors([$e->getMessage()], 'old_admin_add', $old);
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}




if ($action === 'admin_edit') {
    Session::requireAdmin('../view/FrontOffice/login.php');
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    $nom        = trim($_POST['nom']     ?? '');
    $prenom     = trim($_POST['prenom']  ?? '');
    $numTel     = trim($_POST['numTel']  ?? '');
    $adresse    = trim($_POST['adresse'] ?? '');
    $status     = trim($_POST['status']     ?? 'ACTIF');
    $status_kyc = trim($_POST['status_kyc'] ?? 'EN_ATTENTE');
    $status_aml = trim($_POST['status_aml'] ?? 'EN_ATTENTE');
    $role       = trim($_POST['role']       ?? 'CLIENT');

    $old    = compact('id','nom','prenom','numTel','adresse','status','status_kyc','status_aml','role');
    $errors = [];

    if (empty($nom) || !preg_match('/^[\p{L}\s\-\']{2,50}$/u', $nom)) {
        $errors[] = "Nom invalide (lettres uniquement, 2–50 caracteres).";
    }
    if (empty($prenom) || !preg_match('/^[\p{L}\s\-\']{2,50}$/u', $prenom)) {
        $errors[] = "Prenom invalide (lettres uniquement, 2–50 caracteres).";
    }
    if (empty($numTel) || !preg_match('/^[\+\d\s\-\(\)]{7,20}$/', $numTel)) {
        $errors[] = "Le numero de telephone est invalide.";
    }
    if (empty($adresse) || strlen($adresse) < 5) {
        $errors[] = "L'adresse doit comporter au moins 5 caracteres.";
    }
    if (!in_array($status, ['ACTIF','INACTIF','SUSPENDU'])) {
        $errors[] = "Statut invalide.";
    }
    if (!in_array($status_kyc, ['EN_ATTENTE','VERIFIE','REJETE'])) {
        $errors[] = "Statut KYC invalide.";
    }
    if (!in_array($status_aml, ['EN_ATTENTE','CONFORME','ALERTE'])) {
        $errors[] = "Statut AML invalide.";
    }
    if (!in_array($role, ['CLIENT','ADMIN','SUPER_ADMIN'])) {
        $errors[] = "Role invalide.";
    }

    if (!empty($errors)) {
        setErrors($errors, 'old_admin_edit', $old);
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    try {
        $m->updateProfil($id, compact('nom','prenom','numTel','adresse'));
        $m->updateStatuts($id, $status, $status_kyc, $status_aml, $role);
        Session::remove('old_admin_edit');
        Session::setFlash('success', 'Utilisateur modifie.');
    } catch (Exception $e) {
        setErrors([$e->getMessage()], 'old_admin_edit', $old);
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}




if ($action === 'admin_reset_pwd') {
    Session::requireAdmin('../view/FrontOffice/login.php');
    $id     = (int)($_POST['id'] ?? 0);
    $newMdp = trim($_POST['new_mdp'] ?? '');

    $errors = [];
    if ($id <= 0) {
        $errors[] = "ID utilisateur invalide.";
    }
    if (empty($newMdp)) {
        $errors[] = "Le nouveau mot de passe est requis.";
    } elseif (strlen($newMdp) < 8) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caracteres.";
    }

    if (!empty($errors)) {
        Session::setFlash('error', implode('<br>', $errors));
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    try {
        $m->resetPassword($id, $newMdp);
        Session::setFlash('success', "Mot de passe reinitialise : $newMdp");
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}




if ($action === 'admin_delete') {
    Session::requireAdmin('../view/FrontOffice/login.php');
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    if ($id === (int)Session::get('user_id')) {
        Session::setFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    $m->delete($id);
    Session::setFlash('success', 'Utilisateur supprime.');
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}




if ($action === 'valider_kyc') {
    Session::requireAdmin('../view/FrontOffice/login.php');
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    $m->updateStatuts($id, 'ACTIF', 'VERIFIE', 'CONFORME', $_POST['role'] ?? 'CLIENT');
    Session::setFlash('success', 'KYC valide.');
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}




if ($action === 'bloquer') {
    Session::requireAdmin('../view/FrontOffice/login.php');
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    if ($id === (int)Session::get('user_id')) {
        Session::setFlash('error', 'Vous ne pouvez pas bloquer votre propre compte.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    $m->updateStatuts($id, 'SUSPENDU', $_POST['kyc'] ?? 'EN_ATTENTE', $_POST['aml'] ?? 'EN_ATTENTE', $_POST['role'] ?? 'CLIENT');
    Session::setFlash('success', 'Compte bloque.');
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}




if ($action === 'admin_set_association') {
    Session::requireAdmin('../view/FrontOffice/login.php');
    $id = (int)($_POST['id'] ?? 0);
    $assoc = isset($_POST['association']) && $_POST['association'] === '1';

    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    try {
        $m->updateAssociation($id, $assoc);
        Session::setFlash('success', 'Association mise à jour.');
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}




if ($action === 'admin_delete_file') {
    Session::requireAdmin('../view/FrontOffice/login.php');
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    $user = $m->findById($id);
    if (!$user) {
        Session::setFlash('error', 'Utilisateur non trouvé.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }

    if (!empty($user['id_file_path'])) {
        $fullPath = __DIR__ . '/../' . $user['id_file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        try {
            $m->updateFilePath($id, '');
            Session::setFlash('success', 'Fichier supprimé.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }
    } else {
        Session::setFlash('error', 'Aucun fichier à supprimer.');
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}

header('Location: ../view/FrontOffice/login.php'); exit;


