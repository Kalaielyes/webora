<?php
// model/mailer.php
// Note: Credentials are now loaded from .env file for security
// Load EnvLoader if not already loaded
if (!function_exists('getenv') || empty(getenv('MAIL_USERNAME'))) {
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        require_once dirname(__DIR__) . '/models/EnvLoader.php';
        EnvLoader::load($envFile);
    }
}

if (!defined('GMAIL_USER')) {
    define('GMAIL_USER',      getenv('MAIL_USERNAME') ?: 'mounancib90@gmail.com');
}
if (!defined('GMAIL_PASS')) {
    define('GMAIL_PASS',      getenv('MAIL_PASSWORD') ?: 'gqpvitxushwlxbww');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME') ?: 'LegalFin Service Client');
}

/**
 * Send an HTML email via Gmail SMTP (SSL port 465).
 * Returns true on success, false on any failure.
 */
function sendMail(string $to, string $toName, string $subject, string $htmlBody): bool
{
    $sock = @stream_socket_client('ssl://smtp.gmail.com:465', $errno, $errstr, 20);
    if (!$sock) {
        error_log("[mailer] Cannot connect to smtp.gmail.com:465 — $errstr ($errno)");
        return false;
    }

    // Give the server time to respond
    stream_set_timeout($sock, 20);

    $greeting = _smtp_read($sock);
    if (strpos($greeting, '220') === false) {
        fclose($sock);
        error_log("[mailer] Bad greeting: $greeting");
        return false;
    }

    // EHLO
    $ehlo = _smtp_cmd($sock, 'EHLO localhost');
    if (strpos($ehlo, '250') === false) {
        fclose($sock);
        error_log("[mailer] EHLO failed: $ehlo");
        return false;
    }

    // AUTH LOGIN
    _smtp_cmd($sock, 'AUTH LOGIN');
    _smtp_cmd($sock, base64_encode(GMAIL_USER));
    $authResp = _smtp_cmd($sock, base64_encode(GMAIL_PASS));

    if (strpos($authResp, '235') === false) {
        fclose($sock);
        error_log("[mailer] AUTH failed: $authResp");
        return false;
    }

    // Envelope
    $fromResp = _smtp_cmd($sock, 'MAIL FROM:<' . GMAIL_USER . '>');
    if (strpos($fromResp, '250') === false) {
        fclose($sock);
        error_log("[mailer] MAIL FROM failed: $fromResp");
        return false;
    }

    $rcptResp = _smtp_cmd($sock, 'RCPT TO:<' . $to . '>');
    if (strpos($rcptResp, '250') === false && strpos($rcptResp, '251') === false) {
        fclose($sock);
        error_log("[mailer] RCPT TO failed: $rcptResp");
        return false;
    }

    // DATA command
    $dataResp = _smtp_cmd($sock, 'DATA');
    if (strpos($dataResp, '354') === false) {
        fclose($sock);
        error_log("[mailer] DATA command failed: $dataResp");
        return false;
    }

    // Build MIME message
    $boundary = md5(uniqid('', true));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $msg  = "From: " . MAIL_FROM_NAME . " <" . GMAIL_USER . ">\r\n";
    $msg .= "To: $toName <$to>\r\n";
    $msg .= "Subject: $encodedSubject\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $msg .= "\r\n";

    // Plain text part
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n";
    $msg .= "\r\n";
    $msg .= chunk_split(base64_encode(strip_tags($htmlBody))) . "\r\n";

    // HTML part
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n";
    $msg .= "\r\n";
    $msg .= chunk_split(base64_encode($htmlBody)) . "\r\n";

    $msg .= "--$boundary--\r\n";

    // Send message body terminated with \r\n.\r\n
    fwrite($sock, $msg . "\r\n.\r\n");
    $sendResp = _smtp_read($sock);

    if (strpos($sendResp, '250') === false) {
        fclose($sock);
        error_log("[mailer] Message rejected: $sendResp");
        return false;
    }

    _smtp_cmd($sock, 'QUIT');
    fclose($sock);
    return true;
}

function _smtp_read($sock): string
{
    $response = '';
    while ($line = fgets($sock, 512)) {
        $response .= $line;
        // A line with a space at position 3 is the last line of a response
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function _smtp_cmd($sock, string $cmd): string
{
    fwrite($sock, $cmd . "\r\n");
    return _smtp_read($sock);
}