<?php
/**
 * Webora Mailer — Gmail SMTP via raw socket
 * Professional email templates for LegalFin Banking
 */

// =============================================
//  GMAIL CREDENTIALS — EDIT THESE
// =============================================
define('GMAIL_USER', 'gympro984@gmail.com');
define('GMAIL_PASS', 'merfwzblorbevoyt123');
define('MAIL_FROM_NAME', 'LegalFin — Service Client');
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
    return false;
}

function _sendWithSocket($to, $toName, $subject, $htmlBody, $textBody = '') {
    $host = 'smtp.gmail.com';
    $port = 465;

    $sock = stream_socket_client('ssl://' . $host . ':' . $port, $errno, $errstr, 15);
    if (!$sock) return false;

    _smtp_read($sock);
    _smtp_cmd($sock, "EHLO localhost");
    _smtp_cmd($sock, "AUTH LOGIN");
    _smtp_cmd($sock, base64_encode(GMAIL_USER));
    _smtp_cmd($sock, base64_encode(GMAIL_PASS));
    _smtp_cmd($sock, "MAIL FROM:<" . GMAIL_USER . ">");
    _smtp_cmd($sock, "RCPT TO:<" . $to . ">");
    _smtp_cmd($sock, "DATA");

    $boundary = md5(uniqid('', true));

    $headers  = "From: =?UTF-8?B?" . base64_encode(MAIL_FROM_NAME) . "?= <" . GMAIL_USER . ">\r\n";
    $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <" . $to . ">\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

    $plain = $textBody ?: strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));

    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($plain)) . "\r\n";
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


// =============================================================================
//  BASE LAYOUT
// =============================================================================

function _getBaseEmailLayout($preheader, $content) {
    $year = date('Y');
    return '<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>LegalFin</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  table,td{mso-table-lspace:0;mso-table-rspace:0}
  body{margin:0!important;padding:0!important;background-color:#f0f2f5}
</style>
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:Segoe UI,Arial,sans-serif;">

<div style="display:none;font-size:1px;color:#f0f2f5;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">'
    . htmlspecialchars($preheader) . '</div>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f2f5;padding:40px 0 60px;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;">

  <!-- HEADER -->
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);border-radius:16px 16px 0 0;">
        <tr>
          <td style="padding:30px 40px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td valign="middle">
                  <table cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="background-color:#2563eb;border-radius:10px;padding:7px 14px;">
                        <span style="font-size:18px;font-weight:900;color:#fff;font-family:Segoe UI,Arial,sans-serif;">Legal<span style="color:#93c5fd;">Fin</span></span>
                      </td>
                    </tr>
                  </table>
                </td>
                <td align="right" valign="middle">
                  <span style="font-size:10px;color:#93c5fd;letter-spacing:2px;text-transform:uppercase;font-family:Segoe UI,Arial,sans-serif;">Banque en ligne</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- BODY -->
  <tr>
    <td style="background-color:#fff;border-radius:0 0 16px 16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:40px 40px 10px;">' . $content . '</td>
        </tr>
        <tr>
          <td style="padding:0 40px;">
            <div style="border-top:1px solid #e5e7eb;margin:10px 0 24px;"></div>
          </td>
        </tr>
        <!-- Security notice -->
        <tr>
          <td style="padding:0 40px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
              style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
              <tr>
                <td style="padding:14px 18px;">
                  <table cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td valign="top" style="padding-right:10px;font-size:18px;">&#x1F512;</td>
                      <td>
                        <p style="margin:0 0 3px;font-size:12px;font-weight:700;color:#1e293b;font-family:Segoe UI,Arial,sans-serif;">Avis de s&eacute;curit&eacute;</p>
                        <p style="margin:0;font-size:11px;color:#64748b;line-height:1.5;font-family:Segoe UI,Arial,sans-serif;">LegalFin ne vous demandera jamais votre mot de passe, code PIN ou informations confidentielles par e-mail. En cas de doute, contactez imm&eacute;diatement notre service client.</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- FOOTER -->
  <tr>
    <td align="center" style="padding-top:24px;">
      <p style="margin:0 0 4px;font-size:11px;color:#6b7280;font-family:Segoe UI,Arial,sans-serif;">Ce message est g&eacute;n&eacute;r&eacute; automatiquement &mdash; merci de ne pas y r&eacute;pondre.</p>
      <p style="margin:0 0 4px;font-size:11px;color:#6b7280;font-family:Segoe UI,Arial,sans-serif;">LegalFin, Soci&eacute;t&eacute; Anonyme agr&eacute;&eacute;e par la Banque Centrale &mdash; Capital&nbsp;: 50&nbsp;000&nbsp;000&nbsp;TND</p>
      <p style="margin:0;font-size:10px;color:#9ca3af;font-family:Segoe UI,Arial,sans-serif;">&copy; ' . $year . ' LegalFin. Tous droits r&eacute;serv&eacute;s.</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body></html>';
}


// =============================================================================
//  REUSABLE HELPERS
// =============================================================================

function _badgeHtml($text, $color) {
    return '<span style="display:inline-block;background-color:' . $color . '1a;color:' . $color . ';'
        . 'font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;'
        . 'border:1px solid ' . $color . '4d;font-family:Segoe UI,Arial,sans-serif;letter-spacing:0.3px;">'
        . htmlspecialchars($text) . '</span>';
}

function _infoRow($label, $value, $mono = false) {
    $ff = $mono ? 'Courier New,Courier,monospace' : 'Segoe UI,Arial,sans-serif';
    return '<tr>
      <td style="padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#64748b;font-family:Segoe UI,Arial,sans-serif;width:50%;">' . $label . '</td>
      <td style="padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:14px;font-weight:600;color:#1e293b;text-align:right;font-family:' . $ff . ';">' . $value . '</td>
    </tr>';
}

function _actionBanner($text, $icon, $color) {
    return '<table width="100%" cellpadding="0" cellspacing="0" border="0"
      style="background:linear-gradient(135deg,' . $color . '18 0%,' . $color . '08 100%);'
      . 'border:1px solid ' . $color . '33;border-radius:12px;overflow:hidden;margin-bottom:28px;">
      <tr>
        <td style="padding:18px 22px;">
          <table cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td valign="middle" style="padding-right:14px;font-size:26px;">' . $icon . '</td>
              <td valign="middle">
                <p style="margin:0;font-size:17px;font-weight:700;color:' . $color . ';font-family:Segoe UI,Arial,sans-serif;">' . htmlspecialchars($text) . '</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>';
}


// =============================================================================
//  COMPTE NOTIFICATION
// =============================================================================

function sendCompteNotification($user, $compte, $actionText, $color = '#2563eb', $statusText = '') {
    if (!$user || !isset($user['email'])) return false;

    $fullName = htmlspecialchars(trim($user['prenom'] . ' ' . $user['nom']));
    $dateTime = date('d/m/Y \à H\hi');

    $icon = '&#x1F4CB;';
    if (strpos($actionText, 'activ') !== false)   $icon = '&#x2705;';
    elseif (strpos($actionText, 'bloqu') !== false) $icon = '&#x1F512;';
    elseif (strpos($actionText, 'd&eacute;bloqu') !== false || strpos($actionText, 'debloqu') !== false) $icon = '&#x1F513;';
    elseif (strpos($actionText, 'cl&ocirc;tur') !== false || strpos($actionText, 'clotur') !== false) $icon = '&#x1F6AB;';
    elseif (strpos($actionText, 'supprim') !== false) $icon = '&#x1F5D1;';
    elseif (strpos($actionText, 'refus') !== false) $icon = '&#x26A0;';
    elseif (strpos($actionText, 'modifi') !== false) $icon = '&#x270F;';

    $solde   = number_format((float)$compte->getSolde(), 2, ',', ' ');
    $devise  = strtoupper($compte->getDevise() ?: 'TND');
    $dateOuv = $compte->getDateOuverture()
        ? date('d/m/Y', strtotime($compte->getDateOuverture())) : '&mdash;';

    $statusBadge = $statusText ? _badgeHtml($statusText, $color) : '';

    $content = '
    <p style="margin:0 0 4px;font-size:14px;color:#64748b;font-family:Segoe UI,Arial,sans-serif;">Bonjour,</p>
    <h2 style="margin:0 0 24px;font-size:22px;font-weight:800;color:#0f172a;font-family:Segoe UI,Arial,sans-serif;">' . $fullName . '</h2>
    ' . _actionBanner($actionText, $icon, $color) . '
    <p style="margin:0 0 10px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;font-family:Segoe UI,Arial,sans-serif;">D&eacute;tails du compte concern&eacute;</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0"
      style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:28px;">
      <tr>
        <td style="padding:0 20px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0">'
          . _infoRow('Type de compte', ucfirst($compte->getTypeCompte()))
          . _infoRow('IBAN', htmlspecialchars($compte->getIban()), true)
          . _infoRow('Solde actuel', '<strong>' . $solde . ' ' . $devise . '</strong>')
          . _infoRow('Date d\'ouverture', $dateOuv)
          . ($statusText ? _infoRow('Statut', $statusBadge) : '')
          . _infoRow('Date de l\'op&eacute;ration', $dateTime)
          . '</table></td></tr></table>
    <p style="margin:0 0 28px;font-size:14px;line-height:1.7;color:#475569;font-family:Segoe UI,Arial,sans-serif;">Si vous n\'&ecirc;tes pas &agrave; l\'origine de cette action ou si vous avez des questions, veuillez contacter imm&eacute;diatement notre service client.</p>
    <p style="margin:0;font-size:14px;color:#1e293b;font-family:Segoe UI,Arial,sans-serif;">Cordialement,<br><strong style="color:#0f172a;">L\'&eacute;quipe LegalFin &mdash; Service Client</strong></p>
    ';

    $html = _getBaseEmailLayout($actionText . ' — ' . $user['prenom'] . ' ' . $user['nom'], $content);
    return sendMail($user['email'], $user['prenom'] . ' ' . $user['nom'], $actionText . ' — LegalFin', $html);
}


// =============================================================================
//  CARTE NOTIFICATION
// =============================================================================

function sendCarteNotification($user, $carte, $actionText, $color = '#2563eb', $statusText = '') {
    if (!$user || !isset($user['email'])) return false;

    $fullName = htmlspecialchars(trim($user['prenom'] . ' ' . $user['nom']));
    $dateTime = date('d/m/Y \à H\hi');

    $icon = '&#x1F4B3;';
    if (strpos($actionText, 'activ') !== false)    $icon = '&#x2705;';
    elseif (strpos($actionText, 'bloqu') !== false) $icon = '&#x1F512;';
    elseif (strpos($actionText, 'debloqu') !== false || strpos($actionText, 'd&eacute;bloqu') !== false) $icon = '&#x1F513;';
    elseif (strpos($actionText, 'supprim') !== false) $icon = '&#x1F5D1;';
    elseif (strpos($actionText, 'refus') !== false) $icon = '&#x26A0;';
    elseif (strpos($actionText, 'modifi') !== false) $icon = '&#x270F;';

    // ── Card styling (mirrors the UI) ─────────────────────────────────────────
    $style = strtolower($carte->getStyle() ?: 'standard');

    switch ($style) {
        case 'gold':
            $cardBg   = 'background:#b8860b;background:linear-gradient(135deg,#f5c518 0%,#b8860b 55%,#8b6508 100%)';
            $cardText = '#3b1a00';
            $cardRgb  = '59,26,0';
            $tier     = 'GOLD';
            break;
        case 'platinum':
            $cardBg   = 'background:#c8c8c8;background:linear-gradient(135deg,#e8e8e8 0%,#b0b0b0 55%,#888888 100%)';
            $cardText = '#1a1a2e';
            $cardRgb  = '26,26,46';
            $tier     = 'PLATINUM';
            break;
        case 'titanium':
            $cardBg   = 'background:#0a0a0a;background:linear-gradient(135deg,#1c1c1e 0%,#0a0a0a 55%,#000000 100%)';
            $cardText = '#e2e8f0';
            $cardRgb  = '226,232,240';
            $tier     = 'TITANIUM';
            break;
        default:
            $cardBg   = 'background:#1e293b;background:linear-gradient(135deg,#334155 0%,#1e293b 55%,#0f172a 100%)';
            $cardText = '#f1f5f9';
            $cardRgb  = '241,245,249';
            $tier     = 'CLASSIC';
            break;
    }
    $sub = 'rgba(' . $cardRgb . ',0.65)';

    // Display PAN
    $pan   = preg_replace('/\s+/', '', $carte->getNumeroCarte());
    $last4 = substr($pan, -4);
    $maskedPan = trim(chunk_split($pan, 4, '&nbsp;'));

    // Expiry MM/YY
    $exp = $carte->getDateExpiration();
    if (preg_match('/^(\d{4})-(\d{2})/', $exp, $m)) {
        $expFmt = $m[2] . '/' . substr($m[1], 2);
    } else {
        $expFmt = $exp;
    }

    // Holder
    $holder = strtoupper(substr($carte->getTitulaireNom(), 0, 22));

    // Network logo
    $network = strtolower($carte->getReseau());
    if ($network === 'visa') {
        $netHtml = '<span style="font-size:20px;font-weight:900;font-style:italic;letter-spacing:-1px;font-family:Arial,sans-serif;color:' . $cardText . ';">VISA</span>';
    } else {
        $netHtml = '
        <table cellpadding="0" cellspacing="0" border="0"><tr>
          <td><div style="width:22px;height:22px;background:#eb001b;border-radius:50%;display:inline-block;"></div></td>
          <td><div style="width:22px;height:22px;background:#f79e1b;border-radius:50%;margin-left:-8px;display:inline-block;opacity:0.9;"></div></td>
        </tr></table>';
    }

    // Holographic shimmer strip
    $shim = '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="height:3px;background:linear-gradient(90deg,rgba(255,255,255,0) 0%,rgba(255,255,255,0.5) 40%,rgba(255,255,255,0.8) 50%,rgba(255,255,255,0.5) 60%,rgba(255,255,255,0) 100%);"></td></tr></table>';

    // ── Card HTML ─────────────────────────────────────────────────────────────
    $cardHtml = '
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0;">
      <tr>
        <td align="center">
          <table cellpadding="0" cellspacing="0" border="0"
            style="border-radius:18px;box-shadow:0 20px 50px rgba(0,0,0,0.35),0 5px 12px rgba(0,0,0,0.2);">
            <tr>
              <td>
                <table width="340" cellpadding="0" cellspacing="0" border="0"
                  style="border-radius:18px;overflow:hidden;' . $cardBg . ';">
                  <tr><td>' . $shim . '</td></tr>
                  <tr>
                    <td style="padding:18px 22px 22px;">

                      <!-- Row 1: Chip + tier -->
                      <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                          <td valign="middle" width="50">
                            <table width="44" cellpadding="0" cellspacing="0" border="0"
                              style="background:linear-gradient(135deg,#fef08a 0%,#eab308 45%,#ca8a04 100%);border-radius:5px;height:30px;border:1px solid rgba(0,0,0,0.15);">
                              <tr><td style="padding:5px;">
                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                  <tr><td style="height:1px;background:rgba(0,0,0,0.18);border-radius:1px;"></td></tr>
                                  <tr><td style="height:3px;"></td></tr>
                                  <tr><td style="height:1px;background:rgba(0,0,0,0.14);border-radius:1px;"></td></tr>
                                  <tr><td style="height:3px;"></td></tr>
                                  <tr><td style="height:1px;background:rgba(0,0,0,0.18);border-radius:1px;"></td></tr>
                                </table>
                              </td></tr>
                            </table>
                          </td>
                          <td valign="middle">
                            <span style="font-size:14px;color:' . $sub . ';opacity:0.6;">&#x1F4F6;</span>
                          </td>
                          <td align="right" valign="middle">
                            <span style="font-size:10px;font-weight:800;letter-spacing:2.5px;text-transform:uppercase;color:' . $sub . ';font-family:Segoe UI,Arial,sans-serif;">' . $tier . '</span>
                          </td>
                        </tr>
                      </table>

                      <!-- Row 2: PAN -->
                      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;">
                        <tr>
                          <td align="center"
                            style="font-family:Courier New,Courier,monospace;font-size:20px;
                                   letter-spacing:4px;font-weight:700;color:' . $cardText . ';
                                   text-shadow:0 1px 4px rgba(0,0,0,0.4);">' . $maskedPan . '</td>
                        </tr>
                      </table>

                      <!-- Row 3: Holder / Expiry / Network -->
                      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:22px;">
                        <tr>
                          <td valign="bottom" width="44%">
                            <div style="font-size:8px;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:3px;color:' . $sub . ';font-family:Segoe UI,Arial,sans-serif;">Titulaire</div>
                            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:' . $cardText . ';font-family:Segoe UI,Arial,sans-serif;text-shadow:0 1px 2px rgba(0,0,0,0.25);">' . htmlspecialchars($holder) . '</div>
                          </td>
                          <td valign="bottom" width="26%" align="center">
                            <div style="font-size:8px;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:3px;color:' . $sub . ';font-family:Segoe UI,Arial,sans-serif;">Expire</div>
                            <div style="font-size:13px;font-weight:800;letter-spacing:1px;color:' . $cardText . ';font-family:Courier New,Courier,monospace;text-shadow:0 1px 2px rgba(0,0,0,0.25);">' . $expFmt . '</div>
                          </td>
                          <td valign="bottom" width="30%" align="right">' . $netHtml . '</td>
                        </tr>
                      </table>

                    </td>
                  </tr>
                  <tr><td>' . $shim . '</td></tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>';

    // ── Details rows ──────────────────────────────────────────────────────────
    $plafondPay  = number_format((float)$carte->getPlafondPaiementJour(), 2, ',', ' ');
    $plafondRet  = number_format((float)$carte->getPlafondRetraitJour(), 2, ',', ' ');
    $statusBadge = $statusText ? _badgeHtml($statusText, $color) : '';

    $rows = _infoRow('Type de carte',      ucfirst($carte->getTypeCarte() ?: '&mdash;'))
          . _infoRow('R&eacute;seau',                   strtoupper($carte->getReseau()))
          . _infoRow('Num&eacute;ro',                   trim(chunk_split($pan, 4, ' ')), true)
          . _infoRow('Date d\'expiration', $expFmt)
          . _infoRow('Plafond paiement/jour', $plafondPay . ' TND')
          . _infoRow('Plafond retrait/jour',  $plafondRet . ' TND')
          . ($statusText ? _infoRow('Nouveau statut', $statusBadge) : '')
          . ($carte->getMotifBlocage() ? _infoRow('Motif indiqu&eacute;', '<span style="color:#dc2626;font-weight:600;">' . htmlspecialchars($carte->getMotifBlocage()) . '</span>') : '')
          . _infoRow('Date de l\'op&eacute;ration', $dateTime);

    $content = '
    <p style="margin:0 0 4px;font-size:14px;color:#64748b;font-family:Segoe UI,Arial,sans-serif;">Bonjour,</p>
    <h2 style="margin:0 0 24px;font-size:22px;font-weight:800;color:#0f172a;font-family:Segoe UI,Arial,sans-serif;">' . $fullName . '</h2>
    ' . _actionBanner($actionText, $icon, $color) . '
    ' . $cardHtml . '
    <p style="margin:0 0 10px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;font-family:Segoe UI,Arial,sans-serif;">D&eacute;tails de la carte concern&eacute;e</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0"
      style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:28px;">
      <tr>
        <td style="padding:0 20px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0">' . $rows . '</table>
        </td>
      </tr>
    </table>
    <p style="margin:0 0 28px;font-size:14px;line-height:1.7;color:#475569;font-family:Segoe UI,Arial,sans-serif;">Si vous n\'&ecirc;tes pas &agrave; l\'origine de cette action ou si vous avez des questions, veuillez contacter imm&eacute;diatement notre service client.</p>
    <p style="margin:0;font-size:14px;color:#1e293b;font-family:Segoe UI,Arial,sans-serif;">Cordialement,<br><strong style="color:#0f172a;">L\'&eacute;quipe LegalFin &mdash; Service Client</strong></p>
    ';

    $html = _getBaseEmailLayout($actionText . ' — ' . $user['prenom'] . ' ' . $user['nom'], $content);
    return sendMail($user['email'], $user['prenom'] . ' ' . $user['nom'], $actionText . ' — LegalFin', $html);
}
?>
