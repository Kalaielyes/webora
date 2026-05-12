<?php
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/AchievementService.php';

class AchievementController
{
    private PDO $pdo;
    private string $lastError = '';

    private const ALLOWED_ROLES = ['donor', 'association'];
    private const ALLOWED_CONDITIONS = [
        'amount_total',
        'donation_count',
        'supported_campaign_count',
        'campaign_count',
        'raised_amount_total',
        'funded_campaign_count',
    ];

    public function __construct()
    {
        $this->pdo = Config::getConnexion();
    }

    private function sanitizePayload(array $data): array
    {
        $title          = trim((string)($data['title'] ?? ''));
        $description    = trim((string)($data['description'] ?? ''));
        $icon           = trim((string)($data['icon'] ?? 'fa-solid fa-star'));
        $roleType       = strtolower(trim((string)($data['role_type'] ?? '')));
        $conditionType  = strtolower(trim((string)($data['condition_type'] ?? '')));
        $conditionValue = isset($data['condition_value']) ? (float)$data['condition_value'] : 0;
        $points         = isset($data['points']) ? (int)$data['points'] : 0;
        $isEnabled      = isset($data['is_enabled']) ? (int)(bool)$data['is_enabled'] : 1;

        // Validate title: 3-120 characters
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est requis');
        }
        $titleLen = mb_strlen($title);
        if ($titleLen < 3) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 3 caractères');
        }
        if ($titleLen > 120) {
            throw new \InvalidArgumentException('Le titre ne doit pas dépasser 120 caractères');
        }

        // Validate description: 10-255 characters
        if ($description === '') {
            throw new \InvalidArgumentException('La description est requise');
        }
        $descLen = mb_strlen($description);
        if ($descLen < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères');
        }
        if ($descLen > 255) {
            throw new \InvalidArgumentException('La description ne doit pas dépasser 255 caractères');
        }

        // Validate icon: non-empty, max 120 characters
        if ($icon === '') {
            throw new \InvalidArgumentException('L\'icône est requise');
        }
        if (mb_strlen($icon) > 120) {
            throw new \InvalidArgumentException('L\'icône ne doit pas dépasser 120 caractères');
        }

        // Validate role type
        if (!in_array($roleType, self::ALLOWED_ROLES, true)) {
            throw new \InvalidArgumentException('Le rôle sélectionné est invalide');
        }

        // Validate condition type
        if (!in_array($conditionType, self::ALLOWED_CONDITIONS, true)) {
            throw new \InvalidArgumentException('Le type de condition sélectionné est invalide');
        }

        // Validate condition value: must be non-negative
        if ($conditionValue < 0) {
            throw new \InvalidArgumentException('La valeur de condition doit être un nombre positif');
        }

        // Validate points: must be non-negative
        if ($points < 0) {
            throw new \InvalidArgumentException('Les points doivent être un nombre entier positif');
        }

        return [
            'title'           => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'description'     => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'icon'            => htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
            'role_type'       => $roleType,
            'condition_type'  => $conditionType,
            'condition_value' => $conditionValue,
            'points'          => $points,
            'is_enabled'      => $isEnabled,
        ];
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getAllWithUnlockCount(): array
    {
        $sql  = "SELECT a.*, COUNT(ua.id) AS unlocked_users_count
                 FROM achievements a
                 LEFT JOIN user_achievements ua ON ua.achievement_id = a.id
                 GROUP BY a.id
                 ORDER BY a.created_at DESC, a.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM achievements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): bool
    {
        try {
            $safe = $this->sanitizePayload($data);
            $sql  = "INSERT INTO achievements (title, description, icon, role_type, condition_type, condition_value, points, is_enabled)
                     VALUES (:title, :description, :icon, :role_type, :condition_type, :condition_value, :points, :is_enabled)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'title'           => $safe['title'],
                'description'     => $safe['description'],
                'icon'            => $safe['icon'],
                'role_type'       => $safe['role_type'],
                'condition_type'  => $safe['condition_type'],
                'condition_value' => $safe['condition_value'],
                'points'          => $safe['points'],
                'is_enabled'      => $safe['is_enabled'],
            ]);
            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $safe = $this->sanitizePayload($data);
            $sql  = "UPDATE achievements
                     SET title = :title,
                         description = :description,
                         icon = :icon,
                         role_type = :role_type,
                         condition_type = :condition_type,
                         condition_value = :condition_value,
                         points = :points,
                         is_enabled = :is_enabled,
                         updated_at = NOW()
                     WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'id'              => $id,
                'title'           => $safe['title'],
                'description'     => $safe['description'],
                'icon'            => $safe['icon'],
                'role_type'       => $safe['role_type'],
                'condition_type'  => $safe['condition_type'],
                'condition_value' => $safe['condition_value'],
                'points'          => $safe['points'],
                'is_enabled'      => $safe['is_enabled'],
            ]);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM achievements WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function toggle(int $id, bool $enabled): bool
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE achievements SET is_enabled = :enabled, updated_at = NOW() WHERE id = :id');
            return $stmt->execute(['enabled' => $enabled ? 1 : 0, 'id' => $id]);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getUserAchievementsData(int $userId, string $roleType): array
    {
        $service     = new AchievementService();
        $all         = $this->listAllForRole($roleType, true);
        $unlockedIds = $this->getUserUnlockedAchievementIds($userId);
        $unlockedMap = array_flip($unlockedIds);
        $progress    = $service->getUserProgress($userId, $roleType);

        return [
            'all'          => $all,
            'unlocked_ids' => $unlockedIds,
            'unlocked_map' => $unlockedMap,
            'progress'     => $progress,
            'total_points' => $this->getTotalPointsForUser($userId),
        ];
    }

    private function listAllForRole(string $roleType, bool $enabledOnly = true): array
    {
        if (!in_array($roleType, self::ALLOWED_ROLES, true)) {
            return [];
        }
        $sql = 'SELECT * FROM achievements WHERE role_type = :role_type';
        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }
        $sql .= ' ORDER BY condition_type ASC, condition_value ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['role_type' => $roleType]);
        return $stmt->fetchAll() ?: [];
    }

    private function getUserUnlockedAchievementIds(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT achievement_id FROM user_achievements WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int)$row['achievement_id'];
        }
        return $ids;
    }

    private function getTotalPointsForUser(int $userId): int
    {
        $sql  = "SELECT COALESCE(SUM(a.points), 0) AS total_points
                 FROM user_achievements ua
                 INNER JOIN achievements a ON a.id = ua.achievement_id
                 WHERE ua.user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $row  = $stmt->fetch();
        return (int)($row['total_points'] ?? 0);
    }
}

// ---- Helper functions (used by backoffice views) ----

function achievementRequireCsrf(): bool
{
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    $postedToken  = (string)($_POST['csrf_token'] ?? '');
    return $sessionToken !== '' && hash_equals($sessionToken, $postedToken);
}

function achievementIsAuthorizedAdmin(): bool
{
    if (!empty($_SESSION['is_backoffice_admin'])) {
        return true;
    }

    $userId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])
                ? (int)$_SESSION['user_id']
                : 0;

    if ($userId <= 0) {
        return false;
    }

    try {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare('SELECT role FROM utilisateur WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row  = $stmt->fetch();
        $role = strtolower(trim((string)($row['role'] ?? '')));
        return in_array($role, ['admin', 'super_admin'], true);
    } catch (Throwable $e) {
        return false;
    }
}

// ---- Self-executing AJAX endpoint ----
if (basename(__FILE__) === basename((string)($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    Session::start();
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!achievementIsAuthorizedAdmin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }

    $ctrl   = new AchievementController();
    $action = trim((string)($_POST['action'] ?? ''));

    try {
        if ($action === 'create') {
            $ok = $ctrl->create($_POST);
            echo json_encode(['ok' => $ok, 'message' => $ok ? 'Achievement created' : $ctrl->getLastError()]);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $ok = $id > 0 ? $ctrl->update($id, $_POST) : false;
            echo json_encode(['ok' => $ok, 'message' => $ok ? 'Achievement updated' : ($ctrl->getLastError() ?: 'Invalid achievement')]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $ok = $id > 0 ? $ctrl->delete($id) : false;
            echo json_encode(['ok' => $ok, 'message' => $ok ? 'Achievement deleted' : ($ctrl->getLastError() ?: 'Invalid achievement')]);
            exit;
        }

        if ($action === 'toggle') {
            $id      = (int)($_POST['id'] ?? 0);
            $enabled = (int)($_POST['enabled'] ?? 0) === 1;
            $ok      = $id > 0 ? $ctrl->toggle($id, $enabled) : false;
            echo json_encode(['ok' => $ok, 'message' => $ok ? 'Achievement status updated' : ($ctrl->getLastError() ?: 'Invalid achievement')]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
    }
}
