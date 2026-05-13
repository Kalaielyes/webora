<?php
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/AuditLog.php';
Session::start();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$m      = new Utilisateur();
function setErrors(array $errors, string $oldKey, array $old): void {
    Session::setFlash('error', implode('<br>', $errors));
    Session::set($oldKey, $old);
}
if ($action === 'update_profil') {
    Session::requireLogin('../view/frontoffice/login.php');
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
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); 
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
    header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); 
    exit;
}
if ($action === 'update_password') {
    Session::requireLogin('../view/frontoffice/login.php');
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
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); 
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
    header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); 
    exit;
}
if ($action === 'upload_file') {
    Session::requireLogin('../view/frontoffice/login.php');
    $id = (int) Session::get('user_id');
    $user = $m->findById($id);
    if (!empty($user['id_file_path'])) {
        Session::setFlash('error', 'Vous avez déjà déposé un fichier ID. Contactez l\'administration pour modifications.');
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php');
        exit;
    }
    // --- Validate ID document ---
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        Session::setFlash('error', 'Le document ID est requis.');
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php');
        exit;
    }
    // --- Validate selfie ---
    if (!isset($_FILES['selfie']) || $_FILES['selfie']['error'] !== UPLOAD_ERR_OK) {
        Session::setFlash('error', 'Le selfie est requis pour la vérification d\'identité.');
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php');
        exit;
    }
    $file   = $_FILES['file'];
    $selfie = $_FILES['selfie'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $allowedSelfieTypes = ['image/jpeg', 'image/png'];
    $maxSize = 5 * 1024 * 1024;
    if (!in_array($file['type'], $allowedTypes)) {
        Session::setFlash('error', 'Type de fichier ID non autorisé. Seuls JPEG, PNG, GIF et PDF sont acceptés.');
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); exit;
    }
    if ($file['size'] > $maxSize) {
        Session::setFlash('error', 'L\'ID est trop volumineux. Taille maximale : 5MB.');
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); exit;
    }
    if (!in_array($selfie['type'], $allowedSelfieTypes)) {
        Session::setFlash('error', 'Le selfie doit être au format JPEG ou PNG.');
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); exit;
    }
    if ($selfie['size'] > $maxSize) {
        Session::setFlash('error', 'Le selfie est trop volumineux. Taille maximale : 5MB.');
        header('Location: ../view/frontoffice/frontoffice_utilisateur.php'); exit;
    }
    $uploadDir = __DIR__ . '/../view/assets/uploads/';
    // Save ID document
    $cleanName    = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($file['name']));
    $fileName     = uniqid() . '_' . $cleanName;
    $filePath     = $uploadDir . $fileName;
    $relativePath = 'view/assets/uploads/' . $fileName;
    // Save selfie
    $selfieClean    = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($selfie['name']));
    $selfieFileName = 'selfie_' . uniqid() . '_' . $selfieClean;
    $selfieFullPath = $uploadDir . $selfieFileName;
    $selfieRelative = 'view/assets/uploads/' . $selfieFileName;

    if (move_uploaded_file($file['tmp_name'], $filePath) && move_uploaded_file($selfie['tmp_name'], $selfieFullPath)) {
        try {
            $m->updateFilePath($id, $relativePath);

            // --- OCR Scan ---
            require_once __DIR__ . '/../models/OcrApiService.php';
            $ocr = new OcrApiService();
            $ocrResult = $ocr->scanDocument($relativePath);
            if ($ocrResult['success']) {
                $m->updateOcrResult($id, $ocrResult);
            }

            // --- Face Verification ---
            require_once __DIR__ . '/../models/FaceVerificationService.php';
            $faceService = new FaceVerificationService();
            $faceResult  = $faceService->compareFaces($relativePath, $selfieRelative);

            if ($faceResult['success']) {
                $score = (float)$faceResult['score'];
                $m->updateSelfie($id, $selfieRelative, $score);

                $simNote = !empty($faceResult['simulated']) ? ' (mode simulation)' : '';

                if ($score >= 80) {
                    // Auto-validate KYC
                    $m->updateStatuts($id, 'ACTIF', 'VERIFIE', $user['status_aml'], $user['role']);
                    AuditLog::log((int)Session::get('user_id'), "KYC Auto-validé (Face++)", $id, "Score biométrique: {$score}/100{$simNote}");
                    Session::setFlash('success', "✅ Identité vérifiée avec succès (score: {$score}/100) ! Votre compte est maintenant actif{$simNote}.");
                } else {
                    AuditLog::log((int)Session::get('user_id'), "Vérification faciale échouée", $id, "Score biométrique insuffisant: {$score}/100{$simNote}");
                    Session::setFlash('error', "⚠️ Vérification biométrique insuffisante (score: {$score}/100). Votre dossier est en attente de validation manuelle.");
                }
                
                // Refresh session with updated data
                $updatedUser = $m->findById($id);
                if ($updatedUser) {
                    $_SESSION['user'] = $updatedUser;
                }
            } else {
                Session::setFlash('success', 'ID déposé. Vérification biométrique en attente : ' . ($faceResult['error'] ?? 'erreur inconnue'));
            }

        } catch (Exception $e) {
            if (file_exists($filePath)) unlink($filePath);
            if (file_exists($selfieFullPath)) unlink($selfieFullPath);
            Session::setFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }
    } else {
        Session::setFlash('error', 'Erreur lors de la sauvegarde des fichiers.');
    }
    header('Location: ../view/frontoffice/frontoffice_utilisateur.php');
    exit;
}

if ($action === 'admin_add') {
    Session::requireAdmin('../view/frontoffice/login.php');
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
        
        $newId = (int)config::getConnexion()->lastInsertId();
        AuditLog::log((int)Session::get('user_id'), "Création d'utilisateur ($role)", $newId, "Email: $email, Nom: $nom $prenom");

        Session::remove('old_admin_add');
        Session::setFlash('success', 'Utilisateur ajoute.');
    } catch (Exception $e) {
        setErrors([$e->getMessage()], 'old_admin_add', $old);
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'admin_edit') {
    Session::requireAdmin('../view/frontoffice/login.php');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    $existingUser = $m->findById($id);
    if (!$existingUser) {
        Session::setFlash('error', 'Utilisateur non trouvé.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    $role = $existingUser['role']; 
    $nom        = trim($_POST['nom']     ?? '');
    $prenom     = trim($_POST['prenom']  ?? '');
    $numTel     = trim($_POST['numTel']  ?? '');
    $adresse    = trim($_POST['adresse'] ?? '');
    $status     = trim($_POST['status']     ?? 'ACTIF');
    $status_kyc = trim($_POST['status_kyc'] ?? 'EN_ATTENTE');
    $status_aml = trim($_POST['status_aml'] ?? 'EN_ATTENTE');
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
    if (!empty($errors)) {
        setErrors($errors, 'old_admin_edit', $old);
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    try {
        $m->updateProfil($id, compact('nom','prenom','numTel','adresse'));
        $m->updateStatuts($id, $status, $status_kyc, $status_aml, $role);

        // Build a specific diff log
        $changes = [];
        if ($existingUser['nom'] !== $nom)        $changes[] = "Nom: '{$existingUser['nom']}' → '$nom'";
        if ($existingUser['prenom'] !== $prenom)  $changes[] = "Prénom: '{$existingUser['prenom']}' → '$prenom'";
        if ($existingUser['numTel'] !== $numTel)  $changes[] = "Tél: '{$existingUser['numTel']}' → '$numTel'";
        if ($existingUser['adresse'] !== $adresse) $changes[] = "Adresse modifiée";
        if ($existingUser['status'] !== $status)   $changes[] = "Statut: '{$existingUser['status']}' → '$status'";
        if ($existingUser['status_kyc'] !== $status_kyc) $changes[] = "KYC: '{$existingUser['status_kyc']}' → '$status_kyc'";
        if ($existingUser['status_aml'] !== $status_aml) $changes[] = "AML: '{$existingUser['status_aml']}' → '$status_aml'";
        $details = !empty($changes) ? implode(' | ', $changes) : 'Aucune modification détectée';
        AuditLog::log((int)Session::get('user_id'), "Modification profil", $id, $details);

        Session::remove('old_admin_edit');
        Session::setFlash('success', 'Utilisateur modifie.');
    } catch (Exception $e) {
        setErrors([$e->getMessage()], 'old_admin_edit', $old);
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'admin_reset_pwd') {
    Session::requireAdmin('../view/frontoffice/login.php');
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
        AuditLog::log((int)Session::get('user_id'), "Réinitialisation mot de passe", $id);
        Session::setFlash('success', "Mot de passe reinitialise : $newMdp");
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'admin_delete') {
    Session::requireAdmin('../view/frontoffice/login.php');
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
    AuditLog::log((int)Session::get('user_id'), "Suppression d'utilisateur", $id);
    Session::setFlash('success', 'Utilisateur supprime.');
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'valider_kyc') {
    Session::requireAdmin('../view/frontoffice/login.php');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    $m->updateStatuts($id, 'ACTIF', 'VERIFIE', 'CONFORME', $_POST['role'] ?? 'CLIENT');
    AuditLog::log((int)Session::get('user_id'), "Validation KYC", $id);
    Session::setFlash('success', 'KYC valide.');
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'bloquer') {
    Session::requireAdmin('../view/frontoffice/login.php');
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
    AuditLog::log((int)Session::get('user_id'), "Blocage / Suspension de compte", $id);
    Session::setFlash('success', 'Compte bloque.');
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'admin_set_association') {
    Session::requireAdmin('../view/frontoffice/login.php');
    $id = (int)($_POST['id'] ?? 0);
    $assoc = isset($_POST['association']) && $_POST['association'] === '1';
    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    try {
        $m->updateAssociation($id, $assoc);
        AuditLog::log((int)Session::get('user_id'), "Modification association", $id, $assoc ? 'Associé' : 'Non associé');
        Session::setFlash('success', 'Association mise à jour.');
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'admin_delete_file') {
    Session::requireAdmin('../view/frontoffice/login.php');
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
            AuditLog::log((int)Session::get('user_id'), "Suppression document ID", $id);
            Session::setFlash('success', 'Fichier supprimé.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }
    } else {
        Session::setFlash('error', 'Aucun fichier à supprimer.');
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
}
if ($action === 'scan_aml') {
    Session::requireAdmin('../view/frontoffice/login.php');
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
    require_once __DIR__ . '/../models/AmlApiService.php';
    $amlService = new AmlApiService();
    $userData = [
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'email' => $user['email'],
        'cin' => $user['cin'],
        'date_naissance' => $user['date_naissance']
    ];
    $apiResult = $amlService->analyzeUser($userData);
    if (!$apiResult['success']) {
        Session::setFlash('error', $apiResult['error']);
        header('Location: ../view/backoffice/backoffice_utilisateur.php?page=utilisateurs&detail=' . $id); exit;
    }
    $score = $apiResult['aml_score'];
    $m->updateAmlScore($id, $score, $apiResult['aml_reasons']);
    
    AuditLog::log((int)Session::get('user_id'), "Scan AML", $id, "Score: $score/100");

    if ($score > 70) {
        $m->updateStatuts($id, 'SUSPENDU', $user['status_kyc'], 'ALERTE', $user['role']);
        Session::setFlash('error', "Alerte de fraude ! Score AML : $score/100. Le compte a été automatiquement suspendu.");
    } else {
        Session::setFlash('success', "Scan AML terminé. Score : $score/100.");
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php?page=utilisateurs&detail=' . $id); exit;
}
if ($action === 'scan_ocr') {
    Session::requireAdmin('../view/frontoffice/login.php');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        Session::setFlash('error', 'ID invalide.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php'); exit;
    }
    $user = $m->findById($id);
    if (empty($user['id_file_path'])) {
        Session::setFlash('error', 'Aucun document à scanner.');
        header('Location: ../view/backoffice/backoffice_utilisateur.php?page=utilisateurs&detail=' . $id); exit;
    }
    require_once __DIR__ . '/../models/OcrApiService.php';
    $ocr = new OcrApiService();
    $ocrResult = $ocr->scanDocument($user['id_file_path']);
    if ($ocrResult['success']) {
        $m->updateOcrResult($id, $ocrResult);
        AuditLog::log((int)Session::get('user_id'), "Scan OCR", $id, "Confiance: " . ($ocrResult['confiance'] ?? '0'));
        Session::setFlash('success', 'Analyse OCR terminée.');
    } else {
        Session::setFlash('error', 'Échec de l\'analyse OCR.');
    }
    header('Location: ../view/backoffice/backoffice_utilisateur.php?page=utilisateurs&detail=' . $id); exit;
}

if ($action === 'enable_2fa') {
    Session::requireLogin('../view/frontoffice/login.php');
    $id = (int) Session::get('user_id');
    $code = trim($_POST['code'] ?? '');
    $secret = trim($_POST['secret'] ?? '');

    require_once __DIR__ . '/../models/GoogleAuthenticator.php';
    $ga = new GoogleAuthenticator();

    if (empty($code) || empty($secret)) {
        Session::setFlash('error', 'Code invalide.');
        header('Location: ../view/frontoffice/2fa_setup.php'); exit;
    }

    if ($ga->verifyCode($secret, $code, 2)) {
        $m->update2FA($id, $secret, 1);
        Session::setFlash('success', 'Authentification à deux facteurs activée avec succès.');
        $redirect = (Session::isAdmin()) ? '../view/backoffice/backoffice_utilisateur.php?page=profil' : '../view/frontoffice/frontoffice_utilisateur.php';
        header("Location: $redirect"); exit;
    } else {
        Session::setFlash('error', 'Code incorrect. Veuillez réessayer.');
        header('Location: ../view/frontoffice/2fa_setup.php'); exit;
    }
}

if ($action === 'setup_face_id_biometric' || $action === 'setup_face_id_admin') {
    Session::requireLogin('../view/frontoffice/login.php');
    $id = (int) Session::get('user_id');
    $imageData = $_POST['image'] ?? '';
    
    if (empty($imageData)) {
        echo json_encode(['success' => false, 'error' => 'Image requise.']); exit;
    }

    $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $data = base64_decode($imageData);
    
    $fileName = 'selfie_' . $id . '_' . uniqid() . '.jpg';
    $path = __DIR__ . '/../view/assets/uploads/' . $fileName;
    file_put_contents($path, $data);
    $relative = 'view/assets/uploads/' . $fileName;

    try {
        $m->updateSelfie($id, $relative, 100); 
        AuditLog::log($id, "Configuration Face ID", $id);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_selfie_admin' || $action === 'delete_selfie_client') {
    Session::requireLogin('../view/frontoffice/login.php');
    $id = (int) Session::get('user_id');
    $user = $m->findById($id);
    if (!empty($user['selfie_path'])) {
        $fullPath = __DIR__ . '/../' . $user['selfie_path'];
        if (file_exists($fullPath)) unlink($fullPath);
        $m->updateSelfie($id, '', 0);
        AuditLog::log($id, "Suppression Face ID", $id);
        Session::setFlash('success', 'Face ID supprimé.');
    }
    $redirect = (in_array($user['role'], ['ADMIN','SUPER_ADMIN'])) ? '../view/backoffice/backoffice_utilisateur.php?page=profil' : '../view/frontoffice/frontoffice_utilisateur.php';
    header('Location: ' . $redirect); exit;
}

if ($action === 'disable_2fa') {
    Session::requireLogin('../view/frontoffice/login.php');
    $id = (int) Session::get('user_id');
    $mdp = $_POST['mdp'] ?? '';
    
    $user = $m->findById($id);
    $redirect = (Session::isAdmin()) ? '../view/backoffice/backoffice_utilisateur.php?page=profil' : '../view/frontoffice/frontoffice_utilisateur.php';

    if (!$m->verifyPassword($mdp, $user['mdp'])) {
        Session::setFlash('error', 'Mot de passe incorrect.');
        header("Location: $redirect"); exit;
    }
    
    $m->update2FA($id, null, 0);
    Session::setFlash('success', 'Authentification à deux facteurs désactivée.');
    header("Location: $redirect"); exit;
}

header('Location: ../view/frontoffice/login.php'); exit;
