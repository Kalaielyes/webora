<?php
require_once __DIR__ . '/../model/config.php';
require_once 'chequiercontroller.php';
require_once 'smscontroller.php';
// require_once '../vendor/autoload.php'; // Inclure PHPMailer si nécessaire

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

$chequierC = new ChequierController();

// Récupérer les chéquiers expirant dans 15 jours
$chequiers = $chequierC->getChequiersExpirantDans15Jours();

foreach ($chequiers as $chequier) {
    $email = $chequier['email'];
    $nom = $chequier['nom'];
    $date_expiration = $chequier['date_expiration'];

    // Envoyer un email de rappel
    // $mail = new PHPMailer(true);
    /*
    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; 
        // ... (code commenté pour éviter les erreurs suite à la suppression du dossier vendor vide)
    } catch (Exception $e) {
        echo "Erreur lors de l'envoi de l'email à $email : {$mail->ErrorInfo}<br>";
    }
    */
    echo "Rappel nécessaire pour $email (expire le $date_expiration). [Email désactivé : dossier vendor manquant]<br>";
}

/*
// Envoi d'un email de test à mounancib90@gmail.com
$mail = new PHPMailer(true);

try {
    // Configuration du serveur SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com'; 
    $mail->SMTPAuth = true;
    $mail->Username = 'votre_email@example.com'; 
    $mail->Password = 'votre_mot_de_passe'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Destinataire
    $mail->setFrom('votre_email@example.com', 'Votre Banque');
    $mail->addAddress('mounancib90@gmail.com', 'Mouna Ncib');

    // Contenu de l'email
    $mail->isHTML(true);
    $mail->Subject = 'Test de rappel automatique';
    $mail->Body = "<p>Bonjour Mouna Ncib,</p>
                    <p>Ceci est un email de test pour le système de rappel automatique.</p>
                    <p>Cordialement,<br>Votre Banque</p>";

    $mail->send();
    echo "Email de test envoyé avec succès à mounancib90@gmail.com.<br>";
} catch (Exception $e) {
    echo "Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}<br>";
}
*/