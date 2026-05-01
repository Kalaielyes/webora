<?php
ob_start();
session_start();

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/AdminFace.php';

class FaceAuthController {
    private AdminFace $model;

    public function __construct() {
        $this->model = new AdminFace();
    }

    public function getDescriptors(): void {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->model->getAllDescriptors());
    }

    public function saveFace(): void {
        ob_end_clean();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['admin_id'], $data['descriptor'])) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            return;
        }

        $success = $this->model->saveFaceDescriptor(
            (int)$data['admin_id'],
            $data['descriptor']
        );
        echo json_encode(['success' => $success]);
    }

    public function verifyAndLogin(): void {
        ob_end_clean();
        header('Content-Type: application/json');
        $data     = json_decode(file_get_contents('php://input'), true);
        $admin_id = (int)($data['admin_id'] ?? 0);

        $_SESSION['admin_id']      = $admin_id;
        $_SESSION['face_verified'] = true;

        echo json_encode(['success' => true]);
    }

    public function logFail(): void {
        ob_end_clean();
        $this->model->logFailedAttempt($_SERVER['REMOTE_ADDR']);
        header('Content-Type: application/json');
        echo json_encode(['logged' => true]);
    }

    // Sauvegarder le PIN
    public function savePin(): void {
        ob_end_clean();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['admin_id'], $data['pin']) || strlen($data['pin']) !== 4) {
            echo json_encode(['success' => false, 'message' => 'PIN invalide']);
            return;
        }

        if (!ctype_digit($data['pin'])) {
            echo json_encode(['success' => false, 'message' => 'PIN doit contenir uniquement des chiffres']);
            return;
        }

        $success = $this->model->savePin((int)$data['admin_id'], $data['pin']);
        echo json_encode(['success' => $success]);
    }

    // Vérifier le PIN
    public function verifyPin(): void {
        ob_end_clean();
        header('Content-Type: application/json');
        $data     = json_decode(file_get_contents('php://input'), true);
        $admin_id = (int)($data['admin_id'] ?? 1);
        $pin      = $data['pin'] ?? '';

        if ($this->model->verifyPin($admin_id, $pin)) {
            $_SESSION['admin_id']      = $admin_id;
            $_SESSION['face_verified'] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'PIN incorrect']);
        }
    }
}

$controller = new FaceAuthController();
$action     = $_GET['action'] ?? '';

match($action) {
    'getDescriptors' => $controller->getDescriptors(),
    'saveFace'       => $controller->saveFace(),
    'verifyAndLogin' => $controller->verifyAndLogin(),
    'logFail'        => $controller->logFail(),
    'savePin'        => $controller->savePin(),
    'verifyPin'      => $controller->verifyPin(),
    default          => (function() {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['error' => 'Action inconnue']);
    })()
};