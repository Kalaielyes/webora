<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

class MailService {
    /**
     * Envoi d'un e-mail via SMTP Gmail (PHPMailer).
     */
    public static function sendOTP(string $toEmail, string $userName, string $code): bool {
        // --- CONTRÔLE DE SAISIE PHP ---
        $toEmail = filter_var(trim($toEmail), FILTER_VALIDATE_EMAIL);
        if (!$toEmail) {
            return false;
        }
        $userName = htmlspecialchars(trim($userName), ENT_QUOTES, 'UTF-8');
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }
        // ------------------------------

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'aymenhamouda321@gmail.com'; 
            $mail->Password   = str_replace(' ', '', 'vqgk uqzg tdci bqjt'); 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port       = 465;                                    
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('no-reply@legalfin.tn', 'LegalFin Bank');
            $mail->addAddress($toEmail, $userName);

            $mail->isHTML(true);
            $mail->Subject = "Code de vérification - LegalFin Bank";
            
            $htmlContent = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 12px; overflow: hidden;'>
                <div style='background: linear-gradient(135deg, #4F8EF7, #2DD4BF); padding: 30px; text-align: center; color: white;'>
                    <h1 style='margin: 0; font-size: 24px;'>LegalFin Bank</h1>
                    <p style='margin: 10px 0 0; opacity: 0.8;'>Sécurisation de votre compte</p>
                </div>
                <div style='padding: 40px; background: white;'>
                    <h2 style='color: #1e293b; margin-top: 0;'>Bonjour $userName,</h2>
                    <p style='color: #475569; line-height: 1.6;'>Merci de vous être inscrit sur LegalFin Bank. Pour finaliser la création de votre compte et vérifier votre adresse e-mail, veuillez utiliser le code de vérification ci-dessous :</p>
                    
                    <div style='background: #f1f5f9; border-radius: 12px; padding: 20px; text-align: center; margin: 30px 0;'>
                        <span style='font-family: monospace; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #4F8EF7;'>$code</span>
                    </div>
                    
                    <p style='color: #475569; font-size: 14px; line-height: 1.6;'>Ce code est valable pendant 15 minutes. Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</p>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <p style='color: #94a3b8; font-size: 12px; text-align: center;'>&copy; 2026 LegalFin Bank. Tous droits réservés.</p>
                </div>
            </div>";

            $mail->Body = $htmlContent;
            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}
