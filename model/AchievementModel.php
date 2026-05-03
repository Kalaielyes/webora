<?php
require_once __DIR__ . '/config.php';

class AchievementModel
{
    private PDO $pdo;

    private const ALLOWED_ROLES = ['donor', 'association'];
    private const ALLOWED_CONDITIONS = [
        'amount_total',
        'donation_count',
        'supported_campaign_count',
        'campaign_count',
        'raised_amount_total',
        'funded_campaign_count'
    ];

    public function __construct()
    {
        $this->pdo = Config::getConnexion();
    }

    public function getAllowedRoles(): array
    {
        return self::ALLOWED_ROLES;
    }

    public function getAllowedConditions(): array
    {
        return self::ALLOWED_CONDITIONS;
    }

    public function listAllForRole(string $roleType, bool $enabledOnly = true): array
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

    public function listAllWithUnlockCount(): array
    {
        $sql = "SELECT a.*, COUNT(ua.id) AS unlocked_users_count
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
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO achievements (title, description, icon, role_type, condition_type, condition_value, points, is_enabled)
                VALUES (:title, :description, :icon, :role_type, :condition_type, :condition_value, :points, :is_enabled)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'],
            'icon' => $data['icon'],
            'role_type' => $data['role_type'],
            'condition_type' => $data['condition_type'],
            'condition_value' => $data['condition_value'],
            'points' => $data['points'],
            'is_enabled' => $data['is_enabled'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE achievements
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
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'],
            'icon' => $data['icon'],
            'role_type' => $data['role_type'],
            'condition_type' => $data['condition_type'],
            'condition_value' => $data['condition_value'],
            'points' => $data['points'],
            'is_enabled' => $data['is_enabled'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM achievements WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function setEnabled(int $id, bool $enabled): bool
    {
        $stmt = $this->pdo->prepare('UPDATE achievements SET is_enabled = :enabled, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['enabled' => $enabled ? 1 : 0, 'id' => $id]);
    }

    public function getUserUnlockedAchievementIds(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT achievement_id FROM user_achievements WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int)$row['achievement_id'];
        }
        return $ids;
    }

    public function getUserUnlockedForRole(int $userId, string $roleType): array
    {
        if (!in_array($roleType, self::ALLOWED_ROLES, true)) {
            return [];
        }

        $sql = "SELECT a.*, ua.unlocked_at
                FROM user_achievements ua
                INNER JOIN achievements a ON a.id = ua.achievement_id
                WHERE ua.user_id = :user_id
                  AND a.role_type = :role_type
                ORDER BY ua.unlocked_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'role_type' => $roleType,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    public function getTotalPointsForUser(int $userId): int
    {
        $sql = "SELECT COALESCE(SUM(a.points), 0) AS total_points
                FROM user_achievements ua
                INNER JOIN achievements a ON a.id = ua.achievement_id
                WHERE ua.user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total_points'] ?? 0);
    }

    public function unlockAchievement(int $userId, int $achievementId): bool
    {
        $sql = 'INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (:user_id, :achievement_id, NOW())';
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([
                'user_id' => $userId,
                'achievement_id' => $achievementId,
            ]);
        } catch (PDOException $e) {
            // Duplicate key on unique(user_id, achievement_id): already unlocked.
            if ((string)$e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function sanitizePayload(array $data): array
    {
        $title = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $icon = trim((string)($data['icon'] ?? 'fa-solid fa-star'));
        $roleType = strtolower(trim((string)($data['role_type'] ?? '')));
        $conditionType = strtolower(trim((string)($data['condition_type'] ?? '')));
        $conditionValue = isset($data['condition_value']) ? (float)$data['condition_value'] : 0;
        $points = isset($data['points']) ? (int)$data['points'] : 0;
        $isEnabled = isset($data['is_enabled']) ? (int)(bool)$data['is_enabled'] : 1;

        if ($title === '' || mb_strlen($title) > 120) {
            throw new InvalidArgumentException('Titre invalide');
        }
        if ($description === '' || mb_strlen($description) > 255) {
            throw new InvalidArgumentException('Description invalide');
        }
        if ($icon === '' || mb_strlen($icon) > 120) {
            throw new InvalidArgumentException('Icon invalide');
        }
        if (!in_array($roleType, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('role_type invalide');
        }
        if (!in_array($conditionType, self::ALLOWED_CONDITIONS, true)) {
            throw new InvalidArgumentException('condition_type invalide');
        }
        if ($conditionValue < 0) {
            throw new InvalidArgumentException('condition_value invalide');
        }
        if ($points < 0) {
            throw new InvalidArgumentException('points invalide');
        }

        return [
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'icon' => htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
            'role_type' => $roleType,
            'condition_type' => $conditionType,
            'condition_value' => $conditionValue,
            'points' => $points,
            'is_enabled' => $isEnabled,
        ];
    }
}
