<?php

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/chequiercontroller.php';
require_once __DIR__ . '/../helpers/mailer.php';

$chequierC = new ChequierController();

$chequiers = $chequierC->getChequiersExpirantMoinsDe15Jours();

if (empty($chequiers)) {
    exit;
}

foreach ($chequiers as $chequier) {

    $email = $chequier['email'] ?? null;

    if (!$email) {
        continue;
    }

    $subject = "⚠️ Rappel expiration chéquier";

    $html = buildChequierEmail(
        $chequier['nom_et_prenom'],
        $chequier['numero_chequier'],
        $chequier['date_expiration'],
        $chequier['nombre_feuilles']
    );

    sendMail(
        $email,
        $chequier['nom_et_prenom'],
        $subject,
        $html
    );
}