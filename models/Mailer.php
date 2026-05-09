<?php
/**
 * Webora Mailer - Gmail SMTP via raw socket
 * No external libraries needed
 * EDIT YOUR CREDENTIALS BELOW
 */

// =============================================
//  YOUR GMAIL CREDENTIALS - EDIT THESE
// =============================================
require_once __DIR__ . '/config.php';
// GMAIL_USER and GMAIL_PASS are now loaded from .env via config.php
// =============================================

function sendMail($to, $toName, $subject, $htmlBody, $textBody = '') {
    $src = __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
    if (file_exists($src)) {
        return _sendWithPHPMailer($to, $toName, $subject, $htmlBody, $textBody);
    }
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

function _sendWithPHPMailer($to, $toName, $subject, $htmlBody, $textBody) {
    return false; // Fallback
}

function _sendWithSocket($to, $toName, $subject, $htmlBody, $textBody = '') {
    $user = $_ENV['SMTP_USER'] ?? '';
    $pass = $_ENV['SMTP_PASS'] ?? '';
    $host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
    $port = $_ENV['SMTP_PORT'] ?? 465;
    $fromName = $_ENV['MAIL_NAME'] ?? 'Webora Service Client';

    $sock = stream_socket_client('ssl://' . $host . ':' . $port, $errno, $errstr, 15);
    if (!$sock) return false;

    _smtp_read($sock);
    _smtp_cmd($sock, "EHLO localhost");
    
    // Auth
    _smtp_cmd($sock, "AUTH LOGIN");
    _smtp_cmd($sock, base64_encode($user));
    _smtp_cmd($sock, base64_encode($pass));
    
    _smtp_cmd($sock, "MAIL FROM:<" . $user . ">");
    _smtp_cmd($sock, "RCPT TO:<" . $to . ">");
    _smtp_cmd($sock, "DATA");
    
    $boundary = md5(time());
    
    $headers  = "From: =?UTF-8?B?".base64_encode($fromName)."?= <" . $user . ">\r\n";
    $headers .= "To: =?UTF-8?B?".base64_encode($toName)."?= <" . $to . ">\r\n";
    $headers .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    
    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode(strip_tags($textBody ?: $htmlBody))) . "\r\n";
    
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $message .= "--$boundary--\r\n";
    
    _smtp_cmd($sock, $headers . "\r\n" . $message . "\r\n.");
    _smtp_cmd($sock, "QUIT");
    
    fclose($sock);
    return true;
}

/**
 * ─────────────────────────────────────────────────────────────
 *  WEBORA CUSTOM MAILER TEMPLATES
 * ─────────────────────────────────────────────────────────────
 */

function _getBaseEmailLayout($title, $content) {
    return '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f3f4f6; padding-bottom: 60px; }
        .main { margin: 0 auto; width: 100%; max-width: 600px; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; margin-top: 40px; }
        .header { background-color: #0f172a; padding: 35px 20px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .header h1 span { color: #3b82f6; }
        .content { padding: 35px 30px; }
        .title { color: #111827; font-size: 20px; font-weight: 700; margin-top: 0; margin-bottom: 20px; }
        .text { font-size: 15px; line-height: 1.6; color: #4b5563; margin-bottom: 20px; }
        .footer { text-align: center; padding: 25px; font-size: 13px; color: #6b7280; }
        .footer p { margin: 5px 0; }
    </style>
    </head>
    <body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="main" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="header">
                            <h1>Legal<span>Fin</span></h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            <h2 class="title">' . $title . '</h2>
                            <div class="text">
                                ' . $content . '
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="footer">
                    <p>Ce message est généré automatiquement par LegalFin Banque.</p>
                    <p>Veuillez ne pas y répondre.</p>
                    <p>&copy; ' . date("Y") . ' LegalFin. Tous droits réservés.</p>
                </div>
            </td>
        </tr>
    </table>
    </body>
    </html>';
}

function sendCompteNotification($user, $compte, $actionText, $color = "#3b82f6", $statusText = "") {
    if (!$user || !isset($user['email'])) return false;
    
    $title = "Information concernant votre compte";
    $content = '
        <p>Bonjour <strong>' . htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']) . '</strong>,</p>
        <p>Nous tenons à vous informer qu\'une récente opération a été effectuée sur votre compte bancaire.</p>
        
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 30px 0; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
            <tr>
                <td style="background-color: ' . $color . '; width: 5px;"></td>
                <td style="padding: 20px; background-color: #f9fafb;">
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            <td style="padding-bottom: 10px; font-size: 14px; color: #6b7280;">Action réalisée :</td>
                            <td style="padding-bottom: 10px; font-size: 15px; font-weight: 600; color: ' . $color . '; text-align: right;">' . $actionText . '</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 10px; font-size: 14px; color: #6b7280;">Type de compte :</td>
                            <td style="padding-bottom: 10px; font-size: 15px; font-weight: 600; color: #1f2937; text-align: right;">' . ucfirst($compte->getTypeCompte()) . '</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 10px; font-size: 14px; color: #6b7280;">IBAN :</td>
                            <td style="padding-bottom: 10px; font-size: 15px; font-family: monospace; font-weight: 600; color: #1f2937; text-align: right;">' . htmlspecialchars($compte->getIban()) . '</td>
                        </tr>
                        ' . ($statusText ? '
                        <tr>
                            <td style="font-size: 14px; color: #6b7280;">Statut actuel :</td>
                            <td style="font-size: 15px; font-weight: 600; color: #1f2937; text-align: right;">' . $statusText . '</td>
                        </tr>' : '') . '
                    </table>
                </td>
            </tr>
        </table>
        
        <p>L\'équipe de vos conseillers reste à votre entière disposition pour tout renseignement complémentaire.</p>
        <p>Cordialement,<br><br><strong>Votre Service Client LegalFin</strong></p>
    ';
    
    $html = _getBaseEmailLayout($title, $content);
    return sendMail($user['email'], $user['prenom'] . ' ' . $user['nom'], "Notification de compte - LegalFin", $html);
}

function sendCarteNotification($user, $carte, $actionText, $color = "#3b82f6", $statusText = "") {
    if (!$user || !isset($user['email'])) return false;
    
    $title = "Information concernant votre carte bancaire";
    
    // Determine card styling exactly mapping the UI interface!
    $style = strtolower($carte->getStyle() ?: "classic");
    $bgStyle = "background-color: #1e293b; background-image: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;"; 
    
    if ($style === "gold") {
        $bgStyle = "background-color: #fbbf24; background-image: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); color: #451a03;";
    } elseif ($style === "platinum") {
        $bgStyle = "background-color: #e5e7eb; background-image: linear-gradient(135deg, #e5e7eb 0%, #9ca3af 100%); color: #111827;";
    } elseif ($style === "titanium") {
        $bgStyle = "background-color: #000000; background-image: linear-gradient(135deg, #1e293b 0%, #000000 100%); color: #e2e8f0;";
    }
    
    // Network rendering
    if (strtolower($carte->getReseau()) === 'visa') {
        $networkLogo = '<span style="font-weight: 800; font-size: 24px; font-style: italic; letter-spacing: -1px;">VISA</span>';
    } else {
        $networkLogo = '
        <table cellpadding="0" cellspacing="0" role="presentation" style="display: inline-block;">
            <tr>
                <td><div style="width:26px; height:26px; background-color:#ea001b; border-radius:50%;"></div></td>
                <td><div style="width:26px; height:26px; background-color:#ff9c00; border-radius:50%; margin-left:-10px; mix-blend-mode: multiply;"></div></td>
            </tr>
        </table>';
    }
    
    // Masked Card Number formatted nicely
    $pan = $carte->getNumeroCarte();
    $parts = explode(" ", $pan); // Our generated numbers have spaces
    if(count($parts) === 4) {
        $maskedPan = "&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; " . $parts[3];
    } else {
        $maskedPan = "&bull;&bull;&bull;&bull; " . substr($pan, -4);
    }
    
    // Render an email-safe table-based Card Graphic that looks premium
    $cardHtml = '
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 35px 0;">
        <tr>
            <td align="center">
                <table width="340" height="215" cellpadding="0" cellspacing="0" role="presentation" style="border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.2); ' . $bgStyle . '; overflow: hidden;">
                    <tr>
                        <td valign="top" style="padding: 25px;">
                            <!-- Top row: Chip and Tier Level -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="left" valign="middle">
                                        <table width="45" height="32" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffd700; background-image: linear-gradient(135deg, #fef08a 0%, #eab308 100%); border-radius: 5px; border: 1px solid rgba(0,0,0,0.1);">
                                            <tr><td></td></tr>
                                        </table>
                                    </td>
                                    <td align="right" valign="middle" style="font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; opacity: 0.8;">
                                        ' . ($style === 'standard' ? 'CLASSIC' : strtoupper($style)) . '
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Middle row: Card Number -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 35px; margin-bottom: 25px;">
                                <tr>
                                    <td align="center" style="font-family: \'Courier New\', Courier, monospace; font-size: 22px; letter-spacing: 3px; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                        ' . $maskedPan . '
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Bottom row: Holder, Expiry, Logo -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="left" valign="bottom" width="45%">
                                        <div style="font-size: 8px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; opacity: 0.7;">Titulaire</div>
                                        <div style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; text-shadow: 0 1px 1px rgba(0,0,0,0.2);">' . htmlspecialchars(substr($carte->getTitulaireNom(), 0, 18)) . '</div>
                                    </td>
                                    <td align="left" valign="bottom" width="25%">
                                        <div style="font-size: 8px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; opacity: 0.7;">Expire le</div>
                                        <div style="font-size: 13px; font-weight: 700; letter-spacing: 1px; text-shadow: 0 1px 1px rgba(0,0,0,0.2);">' . substr($carte->getDateExpiration(), 5, 2) . '/' . substr($carte->getDateExpiration(), 2, 2) . '</div>
                                    </td>
                                    <td align="right" valign="bottom" width="30%">
                                        ' . $networkLogo . '
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';

    $content = '
        <p>Bonjour <strong>' . htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']) . '</strong>,</p>
        <p>Nous vous informons qu\'une opération a été effectuée concernant votre carte bancaire rattachée à votre compte.</p>
        
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 20px 0; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
            <tr>
                <td style="background-color: ' . $color . '; width: 5px;"></td>
                <td style="padding: 15px 20px; background-color: #f9fafb;">
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            <td style="padding-bottom: 8px; font-size: 14px; color: #6b7280;">Action :</td>
                            <td style="padding-bottom: 8px; font-size: 15px; font-weight: 600; color: ' . $color . '; text-align: right;">' . $actionText . '</td>
                        </tr>
                        ' . ($statusText ? '
                        <tr>
                            <td style="padding-bottom: '.($carte->getMotifBlocage()?'8px':'0px').'; font-size: 14px; color: #6b7280;">Nouveau statut :</td>
                            <td style="padding-bottom: '.($carte->getMotifBlocage()?'8px':'0px').'; font-size: 15px; font-weight: 600; color: #1f2937; text-align: right;">' . $statusText . '</td>
                        </tr>' : '') . '
                        ' . ($carte->getMotifBlocage() ? '
                        <tr>
                            <td style="font-size: 14px; margin: 0; color: #6b7280;">Motif indiqué :</td>
                            <td style="font-size: 14px; font-weight: 600; color: #dc2626; text-align: right;">' . htmlspecialchars($carte->getMotifBlocage()) . '</td>
                        </tr>' : '') . '
                    </table>
                </td>
            </tr>
        </table>
        
        ' . $cardHtml . '
        
        <p>L\'équipe de vos conseillers reste à votre entière disposition pour tout renseignement complémentaire.</p>
        <p>Cordialement,<br><br><strong>Votre Service Client LegalFin</strong></p>
    ';
    
    $html = _getBaseEmailLayout($title, $content);
    return sendMail($user['email'], $user['prenom'] . ' ' . $user['nom'], "Notification de carte - LegalFin", $html);
}
?>
