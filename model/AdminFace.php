<?php

class AdminFace {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Config::getConnexion();
    }

    public function saveFaceDescriptor(int $admin_id, array $descriptor): bool {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM admin_faces WHERE admin_id = ?"
            );
            $stmt->execute([$admin_id]);

            $stmt = $this->pdo->prepare(
                "INSERT INTO admin_faces (admin_id, face_descriptor) VALUES (?, ?)"
            );
            return $stmt->execute([$admin_id, json_encode($descriptor)]);

        } catch (Exception $e) {
            error_log('[NexaBank] AdminFace::saveFaceDescriptor - ' . $e->getMessage());
            return false;
        }
    }

    public function getAllDescriptors(): array {
        $stmt = $this->pdo->query(
            "SELECT admin_id, face_descriptor FROM admin_faces"
        );
        return $stmt->fetchAll();
    }

    public function logFailedAttempt(string $ip): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO security_log (ip, event, created_at) VALUES (?, 'FACE_AUTH_FAILED', NOW())"
        );
        $stmt->execute([$ip]);
    }

    // Sauvegarder le PIN hashé
    public function savePin(int $admin_id, string $pin): bool {
        try {
            $hashed = password_hash($pin, PASSWORD_BCRYPT);
            $stmt   = $this->pdo->prepare(
                "UPDATE admin_faces SET pin_code = ? WHERE admin_id = ?"
            );
            return $stmt->execute([$hashed, $admin_id]);
        } catch (Exception $e) {
            error_log('[NexaBank] AdminFace::savePin - ' . $e->getMessage());
            return false;
        }
    }

    // Vérifier le PIN
    public function verifyPin(int $admin_id, string $pin): bool {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT pin_code FROM admin_faces WHERE admin_id = ?"
            );
            $stmt->execute([$admin_id]);
            $row = $stmt->fetch();

            if (!$row || empty($row['pin_code'])) return false;
            return password_verify($pin, $row['pin_code']);

        } catch (Exception $e) {
            error_log('[NexaBank] AdminFace::verifyPin - ' . $e->getMessage());
            return false;
        }
    }

    // Récupérer le premier admin_id disponible
    public function getFirstAdminId(): int {
        $stmt = $this->pdo->query("SELECT admin_id FROM admin_faces LIMIT 1");
        $row  = $stmt->fetch();
        return $row ? (int)$row['admin_id'] : 1;
    }
}