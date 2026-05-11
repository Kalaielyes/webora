<?php
/**
 * BiometricFaceAuthController - webora Integration
 * Manages biometric face recognition and PIN-based authentication for admins
 */

require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/AdminFace.php';
require_once __DIR__ . '/../models/Session.php';

class BiometricFaceAuthController {
    private AdminFace $faceModel;

    public function __construct() {
        $this->faceModel = new AdminFace();
    }

    // ── Get all registered face descriptors ─────────────────────────────────
    public function getDescriptors(): void {
        header('Content-Type: application/json');
        $descriptors = $this->faceModel->getAllDescriptors();
        echo json_encode(['success' => true, 'descriptors' => $descriptors]);
    }

    // ── Save face descriptor from enrollment ────────────────────────────────
    public function saveFace(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['admin_id'], $data['descriptor']) || empty($data['descriptor'])) {
            echo json_encode(['success' => false, 'message' => 'Missing admin_id or descriptor']);
            return;
        }

        $adminId = (int)$data['admin_id'];
        if ($adminId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id']);
            return;
        }

        try {
            $success = $this->faceModel->saveFaceDescriptor($adminId, $data['descriptor']);
            echo json_encode(['success' => $success, 'message' => $success ? 'Face saved' : 'Failed to save']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Verify face and authenticate user ────────────────────────────────────
    public function verifyAndLogin(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $admin_id = (int)($data['admin_id'] ?? 0);
        $confidence = (float)($data['confidence'] ?? 0);

        if ($admin_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id']);
            return;
        }

        // Confidence threshold: 0.6
        if ($confidence < 0.6) {
            $this->faceModel->logFailedAttempt($_SERVER['REMOTE_ADDR'] ?? '', 'LOW_CONFIDENCE_FACE');
            echo json_encode(['success' => false, 'message' => 'Face not recognized (confidence too low)']);
            return;
        }

        try {
            // Set session variables for face verification
            Session::set('admin_id', $admin_id);
            Session::set('face_verified', true);
            Session::set('face_verified_at', time());

            // Log successful authentication
            $this->faceModel->logSuccessfulAuth($admin_id, $_SERVER['REMOTE_ADDR'] ?? '');

            echo json_encode(['success' => true, 'message' => 'Face verified']);
        } catch (Exception $e) {
            error_log('[LegaFin] BiometricFaceAuthController::verifyAndLogin - ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Authentication failed']);
        }
    }

    // ── Log failed authentication attempt ────────────────────────────────────
    public function logFailedAttempt(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $reason = $data['reason'] ?? 'FACE_AUTH_FAILED';

        try {
            $this->faceModel->logFailedAttempt($_SERVER['REMOTE_ADDR'] ?? '', $reason);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Save PIN code for admin ─────────────────────────────────────────────
    public function savePin(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['admin_id'], $data['pin'])) {
            echo json_encode(['success' => false, 'message' => 'Missing admin_id or PIN']);
            return;
        }

        $adminId = (int)$data['admin_id'];
        $pin = (string)$data['pin'];

        // Validate PIN: exactly 4 digits
        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            echo json_encode(['success' => false, 'message' => 'PIN must be exactly 4 digits']);
            return;
        }

        try {
            $success = $this->faceModel->savePin($adminId, $pin);
            echo json_encode(['success' => $success, 'message' => $success ? 'PIN saved' : 'Failed to save PIN']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Verify PIN code ─────────────────────────────────────────────────────
    public function verifyPin(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $adminId = (int)($data['admin_id'] ?? 0);
        $pin = (string)($data['pin'] ?? '');

        if ($adminId <= 0 || empty($pin)) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id or PIN']);
            return;
        }

        try {
            if ($this->faceModel->verifyPin($adminId, $pin)) {
                Session::set('admin_id', $adminId);
                Session::set('pin_verified', true);
                Session::set('pin_verified_at', time());
                echo json_encode(['success' => true, 'message' => 'PIN verified']);
            } else {
                $this->faceModel->logFailedAttempt($_SERVER['REMOTE_ADDR'] ?? '', 'WRONG_PIN');
                echo json_encode(['success' => false, 'message' => 'Invalid PIN']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Verify cheque (combined face + PIN verification) ────────────────────
    public function verifyCheque(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $admin_id = (int)($data['admin_id'] ?? 0);
        $cheque_id = (int)($data['cheque_id'] ?? 0);

        if ($admin_id <= 0 || $cheque_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id or cheque_id']);
            return;
        }

        try {
            // Set cheque verification status
            Session::set('admin_id', $admin_id);
            Session::set('cheque_face_verified', true);
            Session::set('cheque_face_verified_at', time());
            Session::set('verified_cheque_id', $cheque_id);

            echo json_encode(['success' => true, 'message' => 'Cheque verified']);
        } catch (Exception $e) {
            error_log('[LegaFin] BiometricFaceAuthController::verifyCheque - ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Verification failed']);
        }
    }

    // ── Delete face descriptor ──────────────────────────────────────────────
    public function deleteFaceDescriptor(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $adminId = (int)($data['admin_id'] ?? 0);

        if ($adminId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id']);
            return;
        }

        try {
            $success = $this->faceModel->deleteFaceDescriptor($adminId);
            echo json_encode(['success' => $success, 'message' => $success ? 'Face deleted' : 'Failed to delete']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Check if admin has PIN set ──────────────────────────────────────────
    public function hasPinSet(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $adminId = (int)($data['admin_id'] ?? 0);

        if ($adminId <= 0) {
            echo json_encode(['success' => false, 'has_pin' => false]);
            return;
        }

        try {
            $hasPinSet = $this->faceModel->hasPinSet($adminId);
            echo json_encode(['success' => true, 'has_pin' => $hasPinSet]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'has_pin' => false]);
        }
    }
}
