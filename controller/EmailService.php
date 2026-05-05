<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

/**
 * Loads key=value pairs from a .env file into an in-memory array.
 * Values wrapped in double-quotes have the quotes stripped.
 *
 * @param string $filePath Absolute path to the .env file.
 * @return array<string,string>
 */
function loadEnvFile(string $filePath): array
{
    $env = [];
    if (!is_file($filePath) || !is_readable($filePath)) {
        return $env;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key   = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        // Strip surrounding double-quotes
        if (strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
            $value = substr($value, 1, -1);
        }
        $env[$key] = $value;
    }
    return $env;
}

class EmailService
{
    /** @var array<string,string> */
    private array $config;

    public function __construct()
    {
        $envPath = __DIR__ . '/../.env';
        $this->config = loadEnvFile($envPath);
    }

    /**
     * Send a donation notification email to the association that owns the cagnotte.
     *
     * @param array $don     Row from the `don` table (must include id_don, id_cagnotte)
     * @param array $cagnotte  Row from the `cagnotte` table (titre, objectif_montant, montant_collecte …)
     * @param array $association  Row from `utilisateur` (nom, prenom, email of the cagnotte creator/association)
     * @param array|null $donateur  Unused — kept for signature compatibility
     * @param float $totalCollecte  Current confirmed total for the cagnotte after this donation
     * @return bool  True on success, false on failure (error is logged, donation is never affected)
     */
    public function sendDonationNotification(
        array  $don,
        array  $cagnotte,
        array  $association,
        ?array $donateur,
        float  $totalCollecte
    ): bool {
        // Validate association email
        $toEmail = filter_var(trim((string)($association['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        if ($toEmail === false) {
            error_log('[EmailService] Invalid or missing association email for don #' . ($don['id_don'] ?? '?'));
            return false;
        }

        $toName        = trim(($association['prenom'] ?? '') . ' ' . ($association['nom'] ?? ''));
        $cagnotteTitre = htmlspecialchars_decode((string)($cagnotte['titre'] ?? ''), ENT_QUOTES);
        $objectif      = (float)($cagnotte['objectif_montant'] ?? 0);

        $totalFormatted    = number_format($totalCollecte, 2, '.', ' ');
        $objectifFormatted = $objectif > 0 ? number_format($objectif, 2, '.', ' ') . ' TND' : 'Non défini';

        // ---- Build plain-text body ----
        $textBody = "Bonjour,\n\n"
            . "Félicitations ! Votre cagnotte a atteint son objectif de collecte.\n\n"
            . "- Cagnotte : {$cagnotteTitre}\n"
            . "- Montant collecté : {$totalFormatted} TND / {$objectifFormatted}\n\n"
            . "Cordialement,\n"
            . "L'équipe de la plateforme";

        // ---- Build HTML body ----
        $htmlBody = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Objectif atteint</title></head>
<body style="font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;padding:20px;">
  <div style="background:#4CAF50;padding:20px;border-radius:8px 8px 0 0;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🎉 Objectif de collecte atteint !</h1>
  </div>
  <div style="border:1px solid #ddd;border-top:none;padding:24px;border-radius:0 0 8px 8px;">
    <p>Bonjour <strong>' . htmlspecialchars($toName) . '</strong>,</p>
    <p>Félicitations ! Votre cagnotte <strong>' . htmlspecialchars($cagnotteTitre) . '</strong> a atteint son objectif de collecte.</p>

    <div style="background:#e8f5e9;padding:20px;border-radius:6px;margin-top:16px;text-align:center;">
      <p style="margin:0 0 8px 0;font-size:15px;color:#555;">Montant collecté</p>
      <p style="margin:0;font-size:28px;font-weight:bold;color:#2e7d32;">' . $totalFormatted . ' TND</p>
      <p style="margin:8px 0 0 0;font-size:14px;color:#777;">Objectif : ' . $objectifFormatted . '</p>
    </div>

    <p style="margin-top:24px;color:#666;font-size:13px;">
      Cordialement,<br>
      <strong>L\'équipe de la plateforme</strong>
    </p>
  </div>
</body>
</html>';

        try {
            $mail = new PHPMailer(true);

            // SMTP configuration from .env
            $mail->isSMTP();
            $mail->Host       = $this->config['SMTP_HOST']     ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['SMTP_USERNAME'] ?? '';
            $mail->Password   = $this->config['SMTP_PASSWORD'] ?? '';
            $mail->Port       = (int)($this->config['SMTP_PORT'] ?? 587);

            $encryption = strtolower($this->config['SMTP_ENCRYPTION'] ?? 'tls');
            $mail->SMTPSecure = ($encryption === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            $fromEmail = filter_var($this->config['SMTP_FROM_EMAIL'] ?? '', FILTER_VALIDATE_EMAIL);
            $fromName  = $this->config['SMTP_FROM_NAME'] ?? 'Plateforme Donations';
            if ($fromEmail === false) {
                error_log('[EmailService] Invalid SMTP_FROM_EMAIL in .env');
                return false;
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->CharSet = 'UTF-8';

            $mail->isHTML(true);
            $mail->Subject = 'Objectif de collecte atteint pour votre cagnotte';
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('[EmailService] Failed to send donation notification for don #'
                . ($don['id_don'] ?? '?') . ': ' . $e->getMessage());
            return false;
        }
    }
}
