<?php
/**
 * LegalFin Mailer - Gmail SMTP via raw socket
 * Aucune dépendance externe requise
 */

define('GMAIL_USER', 'mounancib90@gmail.com');
define('GMAIL_PASS', 'gqpvitxushwlxbww');   // ← sans le tiret final (probablement une faute de frappe)
define('MAIL_FROM_NAME', 'LegalFin Service Client');

/**
 * Fonction principale d'envoi
 */
function sendMail($to, $toName, $subject, $htmlBody, $textBody = '') {
    return _sendWithSocket($to, $toName, $subject, $htmlBody, $textBody);
}

function _smtp_read($sock) {
    $r = '';
    while ($line = fgets($sock, 512)) {
        $r .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $r;
}

function _smtp_cmd($sock, $cmd) {
    fwrite($sock, $cmd . "\r\n");
    return _smtp_read($sock);
}

function _sendWithSocket($to, $toName, $subject, $htmlBody, $textBody = '') {
    $host = 'smtp.gmail.com';
    $port = 465;

    $sock = @stream_socket_client('ssl://' . $host . ':' . $port, $errno, $errstr, 15);
    if (!$sock) {
        error_log("[Mailer] Connexion SMTP échouée : $errstr ($errno)");
        return false;
    }

    _smtp_read($sock);
    _smtp_cmd($sock, "EHLO localhost");

    // Authentification
    _smtp_cmd($sock, "AUTH LOGIN");
    _smtp_cmd($sock, base64_encode(GMAIL_USER));
    $authResp = _smtp_cmd($sock, base64_encode(GMAIL_PASS));

    if (strpos($authResp, '235') === false) {
        error_log("[Mailer] Échec auth SMTP : $authResp");
        fclose($sock);
        return false;
    }

    _smtp_cmd($sock, "MAIL FROM:<" . GMAIL_USER . ">");
    _smtp_cmd($sock, "RCPT TO:<" . $to . ">");
    _smtp_cmd($sock, "DATA");

    $boundary = md5(uniqid(rand(), true));

    $headers  = "From: =?UTF-8?B?" . base64_encode(MAIL_FROM_NAME) . "?= <" . GMAIL_USER . ">\r\n";
    $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <" . $to . ">\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: LegalFin-PHP-Socket\r\n";

    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($textBody ?: strip_tags($htmlBody))) . "\r\n";

    $message .= "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $message .= "--$boundary--\r\n";

    $resp = _smtp_cmd($sock, $headers . "\r\n" . $message . "\r\n.");
    _smtp_cmd($sock, "QUIT");
    fclose($sock);

    // Code 250 = succès
    return strpos($resp, '250') !== false;
}

// ─── Templates Email ──────────────────────────────────────────

function _getBaseEmailLayout($title, $content) {
    return '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin:0; padding:0; font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif; background-color:#f3f4f6; color:#1f2937; }
        .wrapper { width:100%; background-color:#f3f4f6; padding:40px 0; }
        .main { margin:0 auto; max-width:600px; background:#ffffff; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow:hidden; }
        .header { background-color:#0f172a; padding:35px 20px; text-align:center; }
        .header h1 { margin:0; color:#ffffff; font-size:28px; font-weight:800; }
        .header h1 span { color:#3b82f6; }
        .content { padding:35px 30px; }
        .title { color:#111827; font-size:20px; font-weight:700; margin:0 0 20px; }
        .text { font-size:15px; line-height:1.6; color:#4b5563; }
        .footer { text-align:center; padding:25px; font-size:12px; color:#6b7280; background:#f9fafb; border-top:1px solid #e5e7eb; }
    </style>
    </head>
    <body>
    <div class="wrapper">
      <div class="main">
        <div class="header"><h1>Legal<span>Fin</span></h1></div>
        <div class="content">
          <h2 class="title">' . $title . '</h2>
          <div class="text">' . $content . '</div>
        </div>
      </div>
      <div class="footer">
        <p>Ce message est généré automatiquement par LegalFin Banque.</p>
        <p>Veuillez ne pas y répondre.</p>
        <p>&copy; ' . date('Y') . ' LegalFin. Tous droits réservés.</p>
      </div>
    </div>
    </body>
    </html>';
}

function sendCompteNotification($user, $compte, $actionText, $color = "#3b82f6", $statusText = "") {
    if (!$user || !isset($user['email'])) return false;

    $title = "Information concernant votre compte";
    $content = '
        <p>Bonjour <strong>' . htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']) . '</strong>,</p>
        <p>Nous tenons à vous informer qu\'une récente opération a été effectuée sur votre compte bancaire.</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:30px 0; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
          <tr>
            <td style="background-color:' . $color . '; width:5px;"></td>
            <td style="padding:20px; background-color:#f9fafb;">
              <table width="100%" cellpadding="6" cellspacing="0">
                <tr>
                  <td style="font-size:14px; color:#6b7280;">Action réalisée :</td>
                  <td style="font-size:15px; font-weight:600; color:' . $color . '; text-align:right;">' . $actionText . '</td>
                </tr>
                <tr>
                  <td style="font-size:14px; color:#6b7280; border-top:1px solid #e5e7eb; padding-top:10px;">Type de compte :</td>
                  <td style="font-size:15px; font-weight:600; color:#1f2937; text-align:right; border-top:1px solid #e5e7eb; padding-top:10px;">' . ucfirst($compte->getTypeCompte()) . '</td>
                </tr>
                <tr>
                  <td style="font-size:14px; color:#6b7280; border-top:1px solid #e5e7eb; padding-top:10px;">IBAN :</td>
                  <td style="font-size:15px; font-family:monospace; font-weight:600; color:#1f2937; text-align:right; border-top:1px solid #e5e7eb; padding-top:10px;">' . htmlspecialchars($compte->getIban()) . '</td>
                </tr>
                ' . ($statusText ? '<tr><td style="font-size:14px; color:#6b7280; border-top:1px solid #e5e7eb; padding-top:10px;">Statut actuel :</td><td style="font-size:15px; font-weight:600; color:#1f2937; text-align:right; border-top:1px solid #e5e7eb; padding-top:10px;">' . $statusText . '</td></tr>' : '') . '
              </table>
            </td>
          </tr>
        </table>
        <p>L\'équipe de vos conseillers reste à votre entière disposition pour tout renseignement.</p>
        <p>Cordialement,<br><strong>Votre Service Client LegalFin</strong></p>';

    $html = _getBaseEmailLayout($title, $content);
    return sendMail($user['email'], $user['prenom'] . ' ' . $user['nom'], "Notification de compte - LegalFin", $html);
}

function sendCarteNotification($user, $carte, $actionText, $color = "#3b82f6", $statusText = "") {
    if (!$user || !isset($user['email'])) return false;

    $title = "Information concernant votre carte bancaire";

    $style = strtolower($carte->getStyle() ?: "classic");
    $bgStyle = "background-color:#1e293b; color:#ffffff;";
    if ($style === "gold")     $bgStyle = "background-color:#fbbf24; color:#451a03;";
    elseif ($style === "platinum") $bgStyle = "background-color:#e5e7eb; color:#111827;";
    elseif ($style === "titanium") $bgStyle = "background-color:#000000; color:#e2e8f0;";

    if (strtolower($carte->getReseau()) === 'visa') {
        $networkLogo = '<span style="font-weight:800; font-size:22px; font-style:italic; letter-spacing:-1px;">VISA</span>';
    } else {
        $networkLogo = '<span style="display:inline-flex; align-items:center;"><span style="display:inline-block; width:24px; height:24px; background:#ea001b; border-radius:50%;"></span><span style="display:inline-block; width:24px; height:24px; background:#ff9c00; border-radius:50%; margin-left:-10px; opacity:0.85;"></span></span>';
    }

    $pan   = $carte->getNumeroCarte();
    $parts = explode(" ", $pan);
    $maskedPan = (count($parts) === 4)
        ? "•••• •••• •••• " . $parts[3]
        : "•••• " . substr($pan, -4);

    $cardHtml = '
    <div style="text-align:center; margin:30px 0;">
      <div style="display:inline-block; width:340px; background:' . ($style === 'gold' ? 'linear-gradient(135deg,#fbbf24,#d97706)' : ($style === 'platinum' ? 'linear-gradient(135deg,#e5e7eb,#9ca3af)' : ($style === 'titanium' ? 'linear-gradient(135deg,#1e293b,#000)' : 'linear-gradient(135deg,#1e293b,#0f172a)'))) . '; border-radius:16px; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,0.25); ' . $bgStyle . '">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
          <div style="width:42px; height:30px; background:linear-gradient(135deg,#fef08a,#eab308); border-radius:4px;"></div>
          <span style="font-size:11px; font-weight:800; letter-spacing:2px; text-transform:uppercase; opacity:0.75;">' . ($style === 'standard' ? 'CLASSIC' : strtoupper($style)) . '</span>
        </div>
        <div style="font-family:\'Courier New\',monospace; font-size:20px; letter-spacing:3px; text-align:center; margin-bottom:24px;">' . $maskedPan . '</div>
        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
          <div>
            <div style="font-size:9px; text-transform:uppercase; letter-spacing:1px; opacity:0.6; margin-bottom:3px;">Titulaire</div>
            <div style="font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">' . htmlspecialchars(substr($carte->getTitulaireNom(), 0, 18)) . '</div>
          </div>
          <div>
            <div style="font-size:9px; text-transform:uppercase; letter-spacing:1px; opacity:0.6; margin-bottom:3px;">Expire le</div>
            <div style="font-size:13px; font-weight:700;">' . substr($carte->getDateExpiration(), 5, 2) . '/' . substr($carte->getDateExpiration(), 2, 2) . '</div>
          </div>
          <div>' . $networkLogo . '</div>
        </div>
      </div>
    </div>';

    $content = '
        <p>Bonjour <strong>' . htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']) . '</strong>,</p>
        <p>Nous vous informons qu\'une opération a été effectuée concernant votre carte bancaire.</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
          <tr>
            <td style="background-color:' . $color . '; width:5px;"></td>
            <td style="padding:15px 20px; background-color:#f9fafb;">
              <table width="100%" cellpadding="6" cellspacing="0">
                <tr>
                  <td style="font-size:14px; color:#6b7280;">Action :</td>
                  <td style="font-size:15px; font-weight:600; color:' . $color . '; text-align:right;">' . $actionText . '</td>
                </tr>
                ' . ($statusText ? '<tr><td style="font-size:14px; color:#6b7280; border-top:1px solid #e5e7eb; padding-top:8px;">Nouveau statut :</td><td style="font-size:15px; font-weight:600; color:#1f2937; text-align:right; border-top:1px solid #e5e7eb; padding-top:8px;">' . $statusText . '</td></tr>' : '') . '
                ' . ($carte->getMotifBlocage() ? '<tr><td style="font-size:14px; color:#6b7280; border-top:1px solid #e5e7eb; padding-top:8px;">Motif :</td><td style="font-size:14px; font-weight:600; color:#dc2626; text-align:right; border-top:1px solid #e5e7eb; padding-top:8px;">' . htmlspecialchars($carte->getMotifBlocage()) . '</td></tr>' : '') . '
              </table>
            </td>
          </tr>
        </table>
        ' . $cardHtml . '
        <p>L\'équipe de vos conseillers reste à votre entière disposition.</p>
        <p>Cordialement,<br><strong>Votre Service Client LegalFin</strong></p>';

    $html = _getBaseEmailLayout($title, $content);
    return sendMail($user['email'], $user['prenom'] . ' ' . $user['nom'], "Notification de carte - LegalFin", $html);
}