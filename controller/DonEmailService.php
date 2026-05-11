<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../models/EnvLoader.php';

/**
 * DonEmailService — handles donation notification emails (merged from don/EmailService).
 * Uses root EnvLoader and vendor PHPMailer.
 */
class DonEmailService
{
    private array $config;

    public function __construct()
    {
        // Load root .env — if not yet loaded, load it now
        $rootEnv = dirname(__DIR__) . '/.env';
        if (is_file($rootEnv)) {
            EnvLoader::load($rootEnv);
        }
        $this->config = $_ENV;
    }

    /**
     * Send a donation notification email to the association that owns the cagnotte.
     *
     * @param array      $don           Row from the `don` table
     * @param array      $cagnotte      Row from the `cagnotte` table
     * @param array      $association   Row from `utilisateur` (cagnotte creator)
     * @param array|null $donateur      Optional — donor row (nullable)
     * @param float      $totalCollecte Current confirmed total after this donation
     */
    public function sendDonationNotification(
        array  $don,
        array  $cagnotte,
        array  $association,
        ?array $donateur,
        float  $totalCollecte
    ): bool {
        $toEmail = filter_var(trim((string)($association['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        if ($toEmail === false) {
            error_log('[DonEmailService] Invalid association email for don #' . ($don['id_don'] ?? '?'));
            return false;
        }

        $toName        = trim(($association['prenom'] ?? '') . ' ' . ($association['nom'] ?? ''));
        $cagnotteTitre = htmlspecialchars($cagnotte['titre'] ?? 'Votre cagnotte', ENT_QUOTES, 'UTF-8');
        $objectif      = number_format((float)($cagnotte['objectif_montant'] ?? 0), 2, ',', ' ');
        $collected     = number_format($totalCollecte, 2, ',', ' ');
        $donMontant    = number_format((float)($don['montant'] ?? 0), 2, ',', ' ');

        $donorName = 'Anonyme';
        if ($donateur) {
            $name = trim(($donateur['prenom'] ?? '') . ' ' . ($donateur['nom'] ?? ''));
            if ($name !== '') {
                $donorName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            }
        }

        $subject = "🎉 Objectif atteint ! Votre cagnotte « {$cagnotteTitre} » est complète";
        $body    = $this->buildEmailBody($cagnotteTitre, $donMontant, $collected, $objectif, $donorName);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet    = 'UTF-8';
            $mail->Host       = $this->config['MAIL_HOST']       ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['MAIL_USERNAME']   ?? '';
            $mail->Password   = $this->config['MAIL_PASSWORD']   ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($this->config['MAIL_PORT'] ?? 587);

            $fromName  = $this->config['MAIL_FROM_NAME']    ?? 'LegalFin Donations';
            $fromEmail = $this->config['MAIL_FROM_ADDRESS'] ?? ($this->config['MAIL_USERNAME'] ?? 'no-reply@legalfin.local');

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName ?: 'Association');
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $body));

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('[DonEmailService] Mail error for don #' . ($don['id_don'] ?? '?') . ': ' . $e->getMessage());
            return false;
        }
    }

    private function buildEmailBody(
        string $titre,
        string $donMontant,
        string $collected,
        string $objectif,
        string $donorName
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"/><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
.container{background:#fff;max-width:560px;margin:0 auto;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.08);}
.header{background:linear-gradient(135deg,#2DD4BF,#4F8EF7);padding:30px 24px;color:#fff;text-align:center;}
.header h1{margin:0;font-size:22px;}
.body{padding:24px;}
.stat{background:#f8fffe;border:1px solid #b2f5ea;border-radius:8px;padding:12px 16px;margin:12px 0;}
.stat-label{font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.05em;}
.stat-value{font-size:20px;font-weight:700;color:#2DD4BF;font-family:monospace;}
.footer{background:#f8f8f8;padding:14px 24px;font-size:11px;color:#999;text-align:center;}
</style></head>
<body>
<div class="container">
  <div class="header"><h1>🎉 Objectif atteint !</h1><p>Votre cagnotte a été intégralement financée</p></div>
  <div class="body">
    <p>Félicitations ! La cagnotte <strong>«&nbsp;{$titre}&nbsp;»</strong> vient d'atteindre son objectif grâce au dernier don de <strong>{$donorName}</strong>.</p>
    <div class="stat"><div class="stat-label">Dernier don reçu</div><div class="stat-value">{$donMontant} €</div></div>
    <div class="stat"><div class="stat-label">Total collecté</div><div class="stat-value">{$collected} €</div></div>
    <div class="stat"><div class="stat-label">Objectif initial</div><div class="stat-value">{$objectif} €</div></div>
    <p style="margin-top:20px;font-size:14px;color:#444;">Connectez-vous à votre espace pour gérer les fonds collectés.</p>
  </div>
  <div class="footer">LegalFin — Plateforme de collecte solidaire · Ce message est automatique</div>
</div>
</body></html>
HTML;
    }
}
