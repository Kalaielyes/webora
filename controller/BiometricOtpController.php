<?php
/**
 * BiometricOtpController — Handles Face Recognition and OTP verification.
 */
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Mailer.php';
require_once __DIR__ . '/../models/whatsapp.php';
require_once __DIR__ . '/../models/FaceVerificationService.php';

class BiometricOtpController
{
    /**
     * Generate and send OTP to user email.
     */
    public static function sendOTP(int $userId): bool
    {
        $db = Config::getConnexion();
        $otp = (string)random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $db->prepare("UPDATE utilisateur SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $stmt->execute([$otp, $expiresAt, $userId]);

        $stmt = $db->prepare("SELECT email, prenom, nom, numTel FROM utilisateur WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) return false;

        $message = "Bonjour " . $user['prenom'] . ", votre code de vérification Webora est : " . $otp . ". Ce code est valable pendant 5 minutes.";
        
        return sendWhatsApp($user['numTel'], $message);
    }

    /**
     * Verify OTP.
     */
    public static function verifyOTP(int $userId, string $code): bool
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT otp_code, otp_expires_at FROM utilisateur WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) return false;
        if ($user['otp_code'] !== $code) return false;
        if (strtotime($user['otp_expires_at']) < time()) return false;

        // Clear OTP after successful verification
        $stmt = $db->prepare("UPDATE utilisateur SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);

        return true;
    }

    /**
     * Store face descriptor.
     */
    public static function storeFaceDescriptor(int $userId, string $descriptor): bool
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("UPDATE utilisateur SET face_descriptor = ? WHERE id = ?");
        return $stmt->execute([$descriptor, $userId]);
    }

    /**
     * Get face descriptor.
     */
    public static function getFaceDescriptor(int $userId): ?string
    {
        $db = Config::getConnexion();
        $stmt = $db->prepare("SELECT face_descriptor FROM utilisateur WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    }

    /**
     * AJAX Endpoint Handler
     */
    public static function handleAjax(): void
    {
        header('Content-Type: application/json');
        try {
            require_once __DIR__ . '/../models/Session.php';
            Session::start();
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            if (!$userId) { echo json_encode(['success' => false, 'message' => 'Non autorisé']); exit; }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $action = $input['action'] ?? '';

            switch ($action) {
            case 'send_otp':
                if (self::sendOTP($userId)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
                }
                break;

            case 'verify_otp':
                $code = $input['code'] ?? '';
                if (self::verifyOTP($userId, $code)) {
                    $_SESSION['otp_verified'] = true;
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Code invalide ou expiré']);
                }
                break;

            case 'store_face':
                $descriptor = $input['descriptor'] ?? '';
                if (self::storeFaceDescriptor($userId, $descriptor)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to store face data']);
                }
                break;

            case 'get_face':
                $descriptor = self::getFaceDescriptor($userId);
                echo json_encode(['success' => true, 'descriptor' => $descriptor]);
                break;
                
            case 'check_face_status':
                $db = Config::getConnexion();
                $stmt = $db->prepare("SELECT face_verified_at FROM utilisateur WHERE id = ?");
                $stmt->execute([$userId]);
                $status = (int)$stmt->fetchColumn();
                echo json_encode(['success' => true, 'status' => $status]);
                break;

            case 'set_face_verified':
                $db = Config::getConnexion();
                // Level 1: Face OK
                $stmt = $db->prepare("UPDATE utilisateur SET face_verified_at = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                // Send OTP now
                self::sendOTP($userId);
                echo json_encode(['success' => true]);
                break;

            case 'mobile_verify_otp':
                $code = $input['code'] ?? '';
                if (self::verifyOTP($userId, $code)) {
                    $db = Config::getConnexion();
                    // Level 2: All OK
                    $stmt = $db->prepare("UPDATE utilisateur SET face_verified_at = 2 WHERE id = ?");
                    $stmt->execute([$userId]);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Code incorrect']);
                }
                break;

            case 'reset_face_status':
                $db = Config::getConnexion();
                $stmt = $db->prepare("UPDATE utilisateur SET face_verified_at = 0 WHERE id = ?");
                $stmt->execute([$userId]);
                echo json_encode(['success' => true]);
                break;

            case 'verify_face_image':
                $imgData = $input['image'] ?? '';
                if (!$imgData) { echo json_encode(['success' => false, 'message' => 'Pas d\'image reçue']); exit; }

                // 1. Decode image data
                $imgData = str_replace('data:image/jpeg;base64,', '', $imgData);
                $imgData = str_replace(' ', '+', $imgData);
                $data = base64_decode($imgData);

                $uploadDir = __DIR__ . '/../view/assets/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $db = Config::getConnexion();
                $stmt = $db->prepare("SELECT selfie_path FROM utilisateur WHERE id = ?");
                $stmt->execute([$userId]);
                $storedPath = $stmt->fetchColumn();

                if (empty($storedPath)) {
                    // FIRST TIME: Store the image permanently and save path in DB
                    $idName = "id_{$userId}_" . time() . ".jpg";
                    $idPath = "view/assets/uploads/" . $idName;
                    file_put_contents(__DIR__ . '/../' . $idPath, $data);
                    
                    $stmt = $db->prepare("UPDATE utilisateur SET selfie_path = ? WHERE id = ?");
                    $stmt->execute([$idPath, $userId]);
                    
                    // Since it's the first time, we consider it verified
                    $stmt = $db->prepare("UPDATE utilisateur SET face_verified_at = 1 WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    // Send OTP ONLY if amount > 100 TND
                    $amountTnd = (float)($input['amountTnd'] ?? 0);
                    if ($amountTnd > 100) {
                        self::sendOTP($userId);
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Profil biométrique enregistré (100%)']);
                    exit;
                }

                // SUBSEQUENT TIMES: Use a temporary file for the current selfie
                $tempSelfieName = "temp_selfie_{$userId}.jpg";
                $tempSelfiePath = "view/assets/uploads/" . $tempSelfieName;
                file_put_contents(__DIR__ . '/../' . $tempSelfiePath, $data);

                // Call the service (storedPath is the reference ID)
                $service = new FaceVerificationService();
                $result = $service->compareFaces($storedPath, $tempSelfiePath);

                // Clean up the temporary selfie
                if (file_exists(__DIR__ . '/../' . $tempSelfiePath)) {
                    unlink(__DIR__ . '/../' . $tempSelfiePath);
                }

                if ($result['success'] && $result['score'] > 55) {
                    // Send OTP ONLY if amount > 100 TND
                    $amountTnd = (float)($input['amountTnd'] ?? 0);
                    if ($amountTnd > 100) {
                        self::sendOTP($userId);
                    }
                    echo json_encode([
                        'success' => true, 
                        'score' => $result['score'],
                        'message' => 'Visage reconnu à ' . $result['score'] . '%'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => ($result['error'] ?? 'Échec de reconnaissance') . ' (' . ($result['score'] ?? 0) . '%)',
                        'score' => $result['score'] ?? 0
                    ]);
                }
                break;

            }
        } catch (Exception $e) {
            error_log("[LegalFin] BiometricOtpController Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur interne: ' . $e->getMessage()]);
        }
        exit;
    }
}

if (basename($_SERVER["SCRIPT_FILENAME"]) === basename(__FILE__)) {
    BiometricOtpController::handleAjax();
}
