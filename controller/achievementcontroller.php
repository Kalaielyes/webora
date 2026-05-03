<?php
require_once __DIR__ . '/../model/AchievementModel.php';
require_once __DIR__ . '/AchievementService.php';
require_once __DIR__ . '/../model/config.php';

class achievementcontroller
{
    private AchievementModel $model;
    private string $lastError = '';

    public function __construct()
    {
        $this->model = new AchievementModel();
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getAllWithUnlockCount(): array
    {
        return $this->model->listAllWithUnlockCount();
    }

    public function getById(int $id): ?array
    {
        return $this->model->getById($id);
    }

    public function create(array $data): bool
    {
        try {
            $safe = $this->model->sanitizePayload($data);
            $this->model->create($safe);
            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $safe = $this->model->sanitizePayload($data);
            return $this->model->update($id, $safe);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            return $this->model->delete($id);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function toggle(int $id, bool $enabled): bool
    {
        try {
            return $this->model->setEnabled($id, $enabled);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getUserAchievementsData(int $userId, string $roleType): array
    {
        $service = new AchievementService();
        $all = $this->model->listAllForRole($roleType, true);
        $unlockedIds = $this->model->getUserUnlockedAchievementIds($userId);
        $unlockedMap = array_flip($unlockedIds);

        $progress = $service->getUserProgress($userId, $roleType);

        return [
            'all' => $all,
            'unlocked_ids' => $unlockedIds,
            'unlocked_map' => $unlockedMap,
            'progress' => $progress,
            'total_points' => $this->model->getTotalPointsForUser($userId),
        ];
    }
}

function achievementRequireCsrf(): bool
{
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    return $sessionToken !== '' && hash_equals($sessionToken, $postedToken);
}

function achievementIsAuthorizedAdmin(): bool
{
    // Allow if the request came from a backoffice page that set the flag
    if (!empty($_SESSION['is_backoffice_admin'])) {
        return true;
    }

    if (!isset($_SESSION['frontoffice_user_id']) || !is_numeric($_SESSION['frontoffice_user_id'])) {
        return false;
    }

    $userId = (int)$_SESSION['frontoffice_user_id'];

    try {
        $pdo = Config::getConnexion();
        $stmt = $pdo->prepare('SELECT role FROM utilisateur WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        $role = strtolower(trim((string)($row['role'] ?? '')));
        return in_array($role, ['admin', 'super_admin'], true);
    } catch (Throwable $e) {
        return false;
    }
}

if (basename(__FILE__) === basename((string)($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!achievementIsAuthorizedAdmin()) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if (!achievementRequireCsrf()) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $ctrl = new achievementcontroller();
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
            $id = (int)($_POST['id'] ?? 0);
            $enabled = (int)($_POST['enabled'] ?? 0) === 1;
            $ok = $id > 0 ? $ctrl->toggle($id, $enabled) : false;
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
