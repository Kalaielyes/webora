<?php

// Load Composer autoloader from the workspace vendor directory.
$mailAutoloadPath = dirname(__DIR__) . '/models/vendor/autoload.php';
if (is_file($mailAutoloadPath)) {
    require_once $mailAutoloadPath;
}

class MailService {
    private static function ensureMailerLoaded(): bool {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return true;
        }

        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }

        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    /**
     * Envoi d'un e-mail via SMTP Gmail (PHPMailer).
     */
    public static function sendOTP(string $toEmail, string $userName, string $code): bool {
        // Check if PHPMailer is available
        if (!self::ensureMailerLoaded()) {
            error_log('[LegaFin] PHPMailer not installed. Using file-based fallback.');
            // Fallback: log to file for testing
            return self::logEmailToFile($toEmail, $userName, $code);
        }
        
        // Keep basic server checks as a safety net in addition to HTML form validation.
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

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Debug: Log start of email sending
            error_log('[LegaFin Email Debug] Starting email send process');
            error_log('[LegaFin Email Debug] Recipient: ' . $toEmail);
            
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME');
            $mail->Password   = getenv('MAIL_PASSWORD');
            $encryptionType   = strtoupper(getenv('MAIL_ENCRYPTION') ?: 'smtps');
            $mail->SMTPSecure = ($encryptionType === 'TLS') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port       = (int)(getenv('MAIL_PORT') ?: 465);
            $mail->CharSet    = 'UTF-8';
            
            // Enable SMTP debugging
            $mail->SMTPDebug = 2; // 1 = client, 2 = server, 3 = both
            $mail->Debugoutput = function($str, $level) {
                error_log('[PHPMailer Debug Level ' . $level . '] ' . $str);
            };
            
            error_log('[LegaFin Email Debug] SMTP configured');

            $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'aymenhamouda321@gmail.com';
            $fromName    = getenv('MAIL_FROM_NAME') ?: 'LegalFin Bank';
            $mail->setFrom($fromAddress, $fromName);
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
            error_log('[LegaFin Email Debug] About to send email');
            $result = $mail->send();
            error_log('[LegaFin Email Debug] Email sent successfully');
            return $result;
        } catch (Exception $e) {
            error_log('[LegaFin Email ERROR] Exception caught: ' . $e->getMessage());
            error_log('[LegaFin Email ERROR] Code: ' . $e->getCode());
            error_log('[LegaFin Email ERROR] File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            // Also log to file
            self::logErrorToFile($e, $toEmail);
            return false;
        }
    }

    /**
     * Fallback method: log email to file when PHPMailer is not installed
     * Used for development/testing
     */
    private static function logEmailToFile(string $toEmail, string $userName, string $code): bool {
        try {
            $logDir = __DIR__ . '/../email_logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $timestamp = date('Y-m-d H:i:s');
            $logFile = $logDir . '/email_' . date('Y-m-d') . '.log';
            $logContent = "
[$timestamp] EMAIL VERIFICATION LOG
To: $toEmail
Name: $userName
Code: $code
Status: Sent (file-based log)
-----------------------------------

";
            file_put_contents($logFile, $logContent, FILE_APPEND);
            error_log('[LegaFin] Email logged to file: ' . $logFile);
            return true;
        } catch (Exception $e) {
            error_log('[LegaFin] Email log error: ' . $e->getMessage());
            return true; // Don't fail, just log the attempt
        }
    }

    /**
     * Log SMTP errors to file for debugging
     */
    private static function logErrorToFile(Exception $e, string $toEmail): void {
        try {
            $logDir = __DIR__ . '/../email_logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $timestamp = date('Y-m-d H:i:s');
            $logFile = $logDir . '/email_errors_' . date('Y-m-d') . '.log';
            $logContent = "
[$timestamp] EMAIL ERROR
Recipient: $toEmail
Error Message: " . $e->getMessage() . "
Error Code: " . $e->getCode() . "
File: " . $e->getFile() . "
Line: " . $e->getLine() . "
-----------------------------------

";
            file_put_contents($logFile, $logContent, FILE_APPEND);
        } catch (Exception $logE) {
            // Silently fail
        }
    }

    public function send(string $toEmail, string $subject, string $htmlContent): bool {
        // Check if PHPMailer is available
        if (!self::ensureMailerLoaded()) {
            error_log('[LegaFin] PHPMailer not installed. Using file-based fallback.');
            return $this->logGenericEmailToFile($toEmail, $subject, $htmlContent);
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME');
            $mail->Password   = getenv('MAIL_PASSWORD');
            $encryptionType   = strtoupper(getenv('MAIL_ENCRYPTION') ?: 'smtps');
            $mail->SMTPSecure = ($encryptionType === 'TLS') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port       = (int)(getenv('MAIL_PORT') ?: 465);
            $mail->CharSet    = 'UTF-8';

            $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'aymenhamouda321@gmail.com';
            $fromName    = getenv('MAIL_FROM_NAME') ?: 'LegalFin Bank';
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;

            return $mail->send();
        } catch (Exception $e) {
            error_log('[LegaFin Email ERROR] ' . $e->getMessage());
            return false;
        }
    }

    private function logGenericEmailToFile(string $to, string $subject, string $content): bool {
        $logDir = __DIR__ . '/../email_logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        $logFile = $logDir . '/generic_email_' . date('Y-m-d') . '.log';
        $logContent = "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject\nCONTENT: " . strip_tags($content) . "\n---\n";
        file_put_contents($logFile, $logContent, FILE_APPEND);
        return true;
    }
}
