<?php
require_once __DIR__ . '/config.php';

class AuditLog {
    private $db;

    public function __construct() {
        $this->db = config::getConnexion();
    }

    /**
     * Logs an admin action.
     *
     * @param int $adminId
     * @param string $action
     * @param int|null $targetUserId
     * @param string|null $details
     */
    public static function log(int $adminId, string $action, ?int $targetUserId = null, ?string $details = null) {
        try {
            $db = config::getConnexion();
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $sql = "INSERT INTO audit_log (admin_id, action, target_user_id, details, ip_address) 
                    VALUES (:admin_id, :action, :target_user_id, :details, :ip_address)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':admin_id' => $adminId,
                ':action' => $action,
                ':target_user_id' => $targetUserId,
                ':details' => $details,
                ':ip_address' => $ip
            ]);
        } catch (PDOException $e) {
            // Silently fail or log to file in production so audit failure doesn't break app flow
            error_log("AuditLog error: " . $e->getMessage());
        }
    }

    /**
     * Retrieves filtered logs.
     *
     * @param array $filters
     * @return array
     */
    public function getFilteredLogs(array $filters = []): array {
        $conditions = [];
        $params = [];

        if (!empty($filters['admin_id'])) {
            $conditions[] = "a.admin_id = :admin_id";
            $params[':admin_id'] = $filters['admin_id'];
        }

        if (!empty($filters['action'])) {
            $conditions[] = "a.action LIKE :action";
            $params[':action'] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "a.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "a.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "SELECT a.*, 
                       u1.nom AS admin_nom, u1.prenom AS admin_prenom, u1.email AS admin_email,
                       u2.nom AS target_nom, u2.prenom AS target_prenom, u2.email AS target_email
                FROM audit_log a
                LEFT JOIN utilisateur u1 ON a.admin_id = u1.id
                LEFT JOIN utilisateur u2 ON a.target_user_id = u2.id
                $where
                ORDER BY a.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
