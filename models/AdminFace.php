<?php
/**
 * AdminFace Model - Face Recognition & Biometric Authentication
 * Manages admin face descriptors and PIN codes for enhanced security
 */

class AdminFace {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Config::getConnexion();
    }

    // ── Face Descriptor Management ───────────────────────────────────────────
    public function saveFaceDescriptor(int $admin_id, array $descriptor): bool {
        try {
            // Delete existing descriptor for this admin
            $stmt = $this->pdo->prepare(
                "DELETE FROM admin_faces WHERE admin_id = ?"
            );
            $stmt->execute([$admin_id]);

            // Insert new descriptor
            $stmt = $this->pdo->prepare(
                "INSERT INTO admin_faces (admin_id, face_descriptor) VALUES (?, ?)"
            );
            return $stmt->execute([$admin_id, json_encode($descriptor)]);

        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::saveFaceDescriptor - ' . $e->getMessage());
            return false;
        }
    }

    public function getAllDescriptors(): array {
        try {
            $stmt = $this->pdo->query(
                "SELECT admin_id, face_descriptor FROM admin_faces WHERE face_descriptor IS NOT NULL"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::getAllDescriptors - ' . $e->getMessage());
            return [];
        }
    }

    public function getFaceDescriptor(int $admin_id): ?array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT face_descriptor FROM admin_faces WHERE admin_id = ?"
            );
            $stmt->execute([$admin_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? json_decode($row['face_descriptor'], true) : null;
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::getFaceDescriptor - ' . $e->getMessage());
            return null;
        }
    }

    // ── Security Logging ────────────────────────────────────────────────────
    public function logFailedAttempt(string $ip, string $event_type = 'FACE_AUTH_FAILED'): void {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO security_log (ip, event, created_at) VALUES (?, ?, NOW())"
            );
            $stmt->execute([$ip, $event_type]);
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::logFailedAttempt - ' . $e->getMessage());
        }
    }

    public function logSuccessfulAuth(int $admin_id, string $ip): void {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO security_log (admin_id, ip, event, created_at) VALUES (?, ?, 'FACE_AUTH_SUCCESS', NOW())"
            );
            $stmt->execute([$admin_id, $ip]);
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::logSuccessfulAuth - ' . $e->getMessage());
        }
    }

    // ── PIN Management ─────────────────────────────────────────────────────
    public function savePin(int $admin_id, string $pin): bool {
        try {
            $hashed = password_hash($pin, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare(
                "UPDATE admin_faces SET pin_code = ? WHERE admin_id = ?"
            );
            return $stmt->execute([$hashed, $admin_id]);
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::savePin - ' . $e->getMessage());
            return false;
        }
    }

    public function verifyPin(int $admin_id, string $pin): bool {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT pin_code FROM admin_faces WHERE admin_id = ?"
            );
            $stmt->execute([$admin_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['pin_code'])) return false;
            return password_verify($pin, $row['pin_code']);

        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::verifyPin - ' . $e->getMessage());
            return false;
        }
    }

    public function hasPinSet(int $admin_id): bool {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT pin_code FROM admin_faces WHERE admin_id = ? AND pin_code IS NOT NULL"
            );
            $stmt->execute([$admin_id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::hasPinSet - ' . $e->getMessage());
            return false;
        }
    }

    // ── Utility Methods ─────────────────────────────────────────────────────
    public function getFirstAdminId(): int {
        try {
            $stmt = $this->pdo->query("SELECT admin_id FROM admin_faces LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['admin_id'] : 1;
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::getFirstAdminId - ' . $e->getMessage());
            return 1;
        }
    }

    public function deleteFaceDescriptor(int $admin_id): bool {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE admin_faces SET face_descriptor = NULL WHERE admin_id = ?"
            );
            return $stmt->execute([$admin_id]);
        } catch (Exception $e) {
            error_log('[LegaFin] AdminFace::deleteFaceDescriptor - ' . $e->getMessage());
            return false;
        }
    }
}
