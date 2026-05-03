<?php
require_once __DIR__ . '/../model/AchievementModel.php';
require_once __DIR__ . '/../model/config.php';

class AchievementAdminController
{
    private $model;

    public function __construct()
    {
        $this->model = new AchievementModel();
    }

    public function listAll()
    {
        return $this->model->listAllWithUnlockCount();
    }

    public function getById($achievementId)
    {
        if (!is_numeric($achievementId)) {
            return null;
        }
        return $this->model->getById((int)$achievementId);
    }

    public function create($data)
    {
        if (!is_array($data)) {
            return false;
        }

        try {
            $validated = $this->model->sanitizePayload($data);
            $pdo = Config::getConnexion();

            $sql = "INSERT INTO achievements 
                    (title, description, icon, role_type, condition_type, condition_value, points, is_enabled) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                $validated['title'],
                $validated['description'],
                $validated['icon'],
                $validated['role_type'],
                $validated['condition_type'],
                $validated['condition_value'],
                $validated['points'],
                1
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function update($achievementId, $data)
    {
        if (!is_numeric($achievementId) || !is_array($data)) {
            return false;
        }

        try {
            $validated = $this->model->sanitizePayload($data);
            $pdo = Config::getConnexion();

            $sql = "UPDATE achievements 
                    SET title = ?, description = ?, icon = ?, 
                        role_type = ?, condition_type = ?, condition_value = ?, points = ? 
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                $validated['title'],
                $validated['description'],
                $validated['icon'],
                $validated['role_type'],
                $validated['condition_type'],
                $validated['condition_value'],
                $validated['points'],
                (int)$achievementId
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function delete($achievementId)
    {
        if (!is_numeric($achievementId)) {
            return false;
        }

        try {
            $pdo = Config::getConnexion();
            $sql = "DELETE FROM achievements WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([(int)$achievementId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function toggleEnabled($achievementId)
    {
        if (!is_numeric($achievementId)) {
            return false;
        }

        try {
            $achievement = $this->model->getById((int)$achievementId);
            if (!$achievement) {
                return false;
            }

            $newStatus = $achievement['is_enabled'] ? 0 : 1;
            $pdo = Config::getConnexion();

            $sql = "UPDATE achievements SET is_enabled = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$newStatus, (int)$achievementId]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
?>
