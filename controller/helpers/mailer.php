<?php

// Mailer configuration is now handled via environment variables in .env

function sendMail($to, $toName, $subject, $htmlBody) {
    $user = $_ENV['SMTP_USER'] ?? ($_ENV['MAIL_USERNAME'] ?? '');
    $pass = $_ENV['SMTP_PASS'] ?? ($_ENV['MAIL_PASSWORD'] ?? '');
    $host = $_ENV['SMTP_HOST'] ?? ($_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
    $port = $_ENV['SMTP_PORT'] ?? ($_ENV['MAIL_PORT'] ?? 465);
    $fromName = $_ENV['MAIL_NAME'] ?? ($_ENV['MAIL_FROM_NAME'] ?? 'LegalFin Service Client');

    $sock = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 15);
    if (!$sock) return false;

    _smtp_read($sock);
    _smtp_cmd($sock, "EHLO localhost");
    _smtp_cmd($sock, "AUTH LOGIN");
    _smtp_cmd($sock, base64_encode($user));
    $auth = _smtp_cmd($sock, base64_encode($pass));

    if (strpos($auth, '235') === false) {
        fclose($sock);
        return false;
    }

    _smtp_cmd($sock, "MAIL FROM:<$user>");
    _smtp_cmd($sock, "RCPT TO:<$to>");
    _smtp_cmd($sock, "DATA");

    $boundary = md5(uniqid());
    $headers  = "From: $fromName <$user>\r\n";
    $headers .= "To: $toName <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $message .= strip_tags($htmlBody) . "\r\n\r\n";
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";
    $message .= "--$boundary--\r\n";

    _smtp_cmd($sock, $headers . $message . "\r\n.");
    _smtp_cmd($sock, "QUIT");
    fclose($sock);
    return true;
}

function _smtp_read($sock) {
    $r = '';
    while ($line = fgets($sock, 512)) {
        $r .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $r;
}

function _smtp_cmd($sock, $cmd) {
    fwrite($sock, $cmd . "\r\n");
    return _smtp_read($sock);
}

function buildChequierEmail($nom, $numero, $dateExp, $feuilles, $type = 'rappel') {
    $dateFormatee = date('d/m/Y', strtotime($dateExp));
    $titre   = "⚠️ Rappel : Expiration de votre chéquier";
    $couleur = "#c0392b";
    $message = "Votre chéquier arrive bientôt à expiration :";

    return "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
        <div style='max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 30px;'>
            <h2 style='color: $couleur;'>$titre</h2>
            <p>Bonjour <strong>$nom</strong>,</p>
            <p>$message</p>
            <table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
                <tr style='background: #f8f8f8;'>
                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Numéro chéquier</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>$numero</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Date d'expiration</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd; color: $couleur;'>$dateFormatee</td>
                </tr>
                <tr style='background: #f8f8f8;'>
                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Nombre de feuilles</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>$feuilles</td>
                </tr>
            </table>
            <p>Veuillez vous rapprocher de votre agence pour le renouveler.</p>
            <p style='margin-top: 30px; color: #888; font-size: 13px;'>LegalFin — Email automatique. Merci de ne pas répondre.</p>
        </div>
    </body>
    </html>";
}
