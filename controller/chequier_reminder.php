<?php

// Restore original paths
require_once __DIR__ . '/helpers/config.local.php';
require_once __DIR__ . '/chequiercontroller.php';
require_once __DIR__ . '/helpers/mailer.php';

$chequierC = new ChequierController();
$chequiers = $chequierC->getChequiersExpirantMoinsDe15Jours();

$envoyes = 0;
$echecs  = 0;
$ignores = 0;

if (empty($chequiers)) {
    echo "[" . date('Y-m-d H:i:s') . "] Aucun chéquier expirant dans 15 jours.\n";
    exit;
}

foreach ($chequiers as $chequier) {

    $email = trim($chequier['email'] ?? '');
    $nom   = $chequier['nom_et_prenom'] ?? 'Client';

    if (empty($email)) {
        $ignores++;
        continue;
    }

    $html = buildChequierEmail(
        $nom,
        $chequier['numero_chequier'],
        $chequier['date_expiration'],
        $chequier['nombre_feuilles']
    );

    $ok = sendMail(
        $email,
        $nom,
        "⚠️ Rappel expiration chéquier",
        $html
    );

    if ($ok) {
        $envoyes++;
        echo "[" . date('Y-m-d H:i:s') . "] ✅ Email envoyé à : $email ($nom)\n";
    } else {
        $echecs++;
        echo "[" . date('Y-m-d H:i:s') . "] ❌ Échec envoi à : $email ($nom)\n";
    }
}

echo "\n--- Résumé ---\n";
echo "✅ Envoyés : $envoyes\n";
echo "❌ Échecs  : $echecs\n";
echo "⏭️  Ignorés : $ignores\n";