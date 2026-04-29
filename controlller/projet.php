<?php

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/projet.php';
require_once __DIR__ . '/../model/investissement.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// For development: allow debug mode to set user_id via query param
if (isset($_GET['debug_user_id']) && is_numeric($_GET['debug_user_id'])) {
    $_SESSION['user_id'] = (int)$_GET['debug_user_id'];
}

$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// If no user, attempt to use the first available user (development fallback)
if ($userId === null) {
    try {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT id FROM utilisateur LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $userId = (int)$row['id'];
            $_SESSION['user_id'] = $userId;
        }
    } catch (Exception $e) {
        // Ignore errors, proceed with null check below
    }
}

if ($userId === null) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

function getConnexion(): PDO
{
    return Config::getConnexion();
}

function createProject(array $data): int
{
    $sql = "INSERT INTO projet (titre, description, montant_objectif, secteur, date_limite, date_creation, status, id_createur, request_code, taux_rentabilite, temps_retour_brut)
            VALUES (:titre, :description, :montant, :secteur, :date_limite, CURDATE(), :status, :idCreateur, '', :taux_rentabilite, :temps_retour_brut)";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute([
        'titre' => $data['titre'],
        'description' => $data['description'],
        'montant' => $data['montant'],
        'secteur' => $data['secteur'],
        'date_limite' => $data['date_limite'],
        'status' => $data['status'],
        'idCreateur' => $data['id_createur'],
        'taux_rentabilite' => $data['taux_rentabilite'] ?? 0,
        'temps_retour_brut' => $data['temps_retour_brut'] ?? 0,
    ]);
    $projectId = (int)getConnexion()->lastInsertId();
    $requestCode = sprintf('REQ-%s-%03d', date('Ymd'), $projectId);
    $update = getConnexion()->prepare("UPDATE projet SET request_code = :request_code WHERE id_projet = :projectId");
    $update->execute(['request_code' => $requestCode, 'projectId' => $projectId]);
    return $projectId;
}

function getProjectRequestsByUser(int $userId): array
{
    $sql = "SELECT id_projet, request_code, titre, description, montant_objectif, secteur, date_limite, date_creation, status, taux_rentabilite, temps_retour_brut
            FROM projet
            WHERE id_createur = :userId
            ORDER BY date_creation DESC";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute(['userId' => $userId]);
    $projects = $stmt->fetchAll();
    
    foreach ($projects as &$project) {
        $totalInvested = Investissement::getTotalInvestedForProject($project['id_projet']);
        $project['total_investi'] = $totalInvested;
        $project['montant_restant'] = max(0, $project['montant_objectif'] - $totalInvested);
        $project['progression'] = $project['montant_objectif'] > 0 ? round(($totalInvested / $project['montant_objectif']) * 100, 1) : 0;
    }
    
    return $projects;
}

function getAvailableProjects(): array
{
    $sql = "SELECT p.id_projet, p.request_code, p.titre, p.description, p.montant_objectif, p.secteur, p.date_limite, p.date_creation, p.status, p.taux_rentabilite, p.temps_retour_brut, u.nom AS createur_nom
            FROM projet p
            LEFT JOIN utilisateur u ON p.id_createur = u.id
            WHERE p.status = 'VALIDE'
            ORDER BY p.date_creation DESC";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute();
    $projects = $stmt->fetchAll();

    // Add total invested, remaining amount, and progress for each project
    foreach ($projects as &$project) {
        $totalInvested = Investissement::getTotalInvestedForProject($project['id_projet']);
        $project['total_investi'] = $totalInvested;
        $project['montant_restant'] = max(0, $project['montant_objectif'] - $totalInvested);
        $project['progression'] = $project['montant_objectif'] > 0 ? round(($totalInvested / $project['montant_objectif']) * 100, 1) : 0;
    }

    return $projects;
}

function getProjectById(int $projectId): ?array
{
    $sql = "SELECT p.id_projet, p.request_code, p.titre, p.description, p.montant_objectif, p.secteur, p.date_limite, p.date_creation, p.status, p.taux_rentabilite, p.temps_retour_brut, u.nom AS createur_nom
            FROM projet p
            LEFT JOIN utilisateur u ON p.id_createur = u.id
            WHERE p.id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute(['projectId' => $projectId]);
    $result = $stmt->fetch();
    return $result === false ? null : $result;
}

function updateProject(int $projectId, int $userId, array $data): bool
{
    $sql = "UPDATE projet
            SET titre = :titre,
                description = :description,
                montant_objectif = :montant,
                secteur = :secteur,
                date_limite = :date_limite,
                status = :status,
                taux_rentabilite = :taux_rentabilite,
                temps_retour_brut = :temps_retour_brut
            WHERE id_projet = :projectId
              AND id_createur = :userId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute([
        'titre' => $data['titre'],
        'description' => $data['description'],
        'montant' => $data['montant'],
        'secteur' => $data['secteur'],
        'date_limite' => $data['date_limite'],
        'status' => $data['status'],
        'taux_rentabilite' => $data['taux_rentabilite'] ?? 0,
        'temps_retour_brut' => $data['temps_retour_brut'] ?? 0,
        'projectId' => $projectId,
        'userId' => $userId,
    ]);
}

function adminUpdateProject(int $projectId, array $data): bool
{
    $sql = "UPDATE projet
            SET titre = :titre,
                description = :description,
                montant_objectif = :montant,
                secteur = :secteur,
                date_limite = :date_limite,
                status = :status,
                taux_rentabilite = :taux_rentabilite,
                temps_retour_brut = :temps_retour_brut
            WHERE id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute([
        'titre' => $data['titre'],
        'description' => $data['description'],
        'montant' => $data['montant'],
        'secteur' => $data['secteur'],
        'date_limite' => $data['date_limite'],
        'status' => $data['status'],
        'taux_rentabilite' => $data['taux_rentabilite'] ?? 0,
        'temps_retour_brut' => $data['temps_retour_brut'] ?? 0,
        'projectId' => $projectId,
    ]);
}

function adminDeleteProject(int $projectId): bool
{
    $sql = "DELETE FROM projet WHERE id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute(['projectId' => $projectId]);
}

function changeProjectStatus(int $projectId, string $status): bool
{
    $allowedStatuses = ['EN_COURS', 'TERMINE', 'ANNULE', 'EN_ATTENTE', 'VALIDE', 'REFUSE'];
    if (!in_array($status, $allowedStatuses, true)) {
        return false;
    }
    $sql = "UPDATE projet SET status = :status WHERE id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute(['status' => $status, 'projectId' => $projectId]);
}

function deleteProject(int $projectId, int $userId): bool
{
    $sql = "DELETE FROM projet WHERE id_projet = :projectId AND id_createur = :userId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute(['projectId' => $projectId, 'userId' => $userId]);
}

$userId = $_SESSION['user_id'] ?? 1;
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'list_requests') {
        echo json_encode(['success' => true, 'data' => getProjectRequestsByUser($userId)]);
        exit;
    }

    if ($action === 'list_available_projects') {
        echo json_encode(['success' => true, 'data' => getAvailableProjects()]);
        exit;
    }

    if ($action === 'get_project' && !empty($_GET['id'])) {
        $project = getProjectById((int)$_GET['id']);
        if ($project === null) {
            echo json_encode(['success' => false, 'message' => 'Projet introuvable.']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $project]);
        exit;
    }
}

if ($method === 'POST') {
    if ($action === 'submit_demande' || $action === 'update_project') {
        $titre = trim($_POST['titre'] ?? '');
        $secteur = trim($_POST['secteur'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $montant = $_POST['montant'] ?? '';
        $date_limite = $_POST['date_limite'] ?? '';
        $status = $_POST['status'] ?? 'EN_ATTENTE';
        $taux_rentabilite = $_POST['taux_rentabilite'] ?? 0;
        $temps_retour_brut = $_POST['temps_retour_brut'] ?? 0;

        if ($titre === '' || $secteur === '' || $description === '' || $montant === '' || $date_limite === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
            exit;
        }

        if (!is_numeric($montant) || $montant < 10000) {
            echo json_encode(['success' => false, 'message' => 'Le montant doit être une valeur numérique minimale de 10 000.']);
            exit;
        }

        try {
            if ($action === 'submit_demande') {
            if (!empty($_POST['project_id'])) {
                echo json_encode(['success' => false, 'message' => 'Impossible de créer une nouvelle demande pendant la modification d\'un projet existant.']);
                exit;
            }
            $projectId = createProject([
                'titre' => $titre,
                'description' => $description,
                'montant' => $montant,
                'secteur' => $secteur,
                'date_limite' => $date_limite,
                'status' => $status,
                'id_createur' => $userId,
                'taux_rentabilite' => $taux_rentabilite,
                'temps_retour_brut' => $temps_retour_brut,
            ]);

            $project = getProjectById($projectId);
            echo json_encode([
                'success' => true,
                'reference' => $project['request_code'] ?? sprintf('REQ-%05d', $projectId),
                'message' => 'Demande enregistrée.',
            ]);
            exit;
        }

            if ($action === 'update_project' && !empty($_POST['project_id'])) {
                $projectId = (int)$_POST['project_id'];
                if (updateProject($projectId, $userId, [
                    'titre' => $titre,
                    'description' => $description,
                    'montant' => $montant,
                    'secteur' => $secteur,
                    'date_limite' => $date_limite,
                    'status' => $status,
                    'taux_rentabilite' => $taux_rentabilite,
                    'temps_retour_brut' => $temps_retour_brut,
                ])) {
                    echo json_encode(['success' => true, 'message' => 'Demande mise à jour.']);
                    exit;
                }
                echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour la demande.']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
            exit;
        }
    }

    if ($action === 'admin_update_status' && !empty($_POST['project_id']) && !empty($_POST['new_status'])) {
        $projectId = (int)$_POST['project_id'];
        $newStatus = strtoupper(trim($_POST['new_status']));
        if (changeProjectStatus($projectId, $newStatus)) {
            echo json_encode(['success' => true, 'message' => 'Statut du projet mis à jour.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour le statut du projet.']);
        exit;
    }

    if ($action === 'delete_project' && !empty($_POST['project_id'])) {
        $projectId = (int)$_POST['project_id'];
        if (deleteProject($projectId, $userId)) {
            echo json_encode(['success' => true, 'message' => 'Demande supprimée.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer la demande.']);
        exit;
    }

    // ── Admin CRUD actions (no id_createur restriction) ──────────────────────
    if ($action === 'admin_create_project') {
        $titre       = trim($_POST['titre'] ?? '');
        $secteur     = trim($_POST['secteur'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $montant     = $_POST['montant'] ?? '';
        $date_limite = $_POST['date_limite'] ?? '';
        $status      = $_POST['status'] ?? 'VALIDE';
        $taux_rentabilite = $_POST['taux_rentabilite'] ?? 0;
        $temps_retour_brut = $_POST['temps_retour_brut'] ?? 0;

        if ($titre === '' || $secteur === '' || $description === '' || $montant === '' || $date_limite === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
            exit;
        }
        if (!is_numeric($montant) || (float)$montant < 1) {
            echo json_encode(['success' => false, 'message' => 'Le montant doit être une valeur numérique positive.']);
            exit;
        }
        try {
            $projectId = createProject([
                'titre'       => $titre,
                'description' => $description,
                'montant'     => $montant,
                'secteur'     => $secteur,
                'date_limite' => $date_limite,
                'status'      => $status,
                'id_createur' => $userId,
                'taux_rentabilite' => $taux_rentabilite,
                'temps_retour_brut' => $temps_retour_brut,
            ]);
            $project = getProjectById($projectId);
            echo json_encode(['success' => true, 'message' => 'Projet créé avec succès.', 'data' => $project]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'admin_update_project' && !empty($_POST['project_id'])) {
        $projectId   = (int)$_POST['project_id'];
        $titre       = trim($_POST['titre'] ?? '');
        $secteur     = trim($_POST['secteur'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $montant     = $_POST['montant'] ?? '';
        $date_limite = $_POST['date_limite'] ?? '';
        $status      = $_POST['status'] ?? 'EN_ATTENTE';
        $taux_rentabilite = $_POST['taux_rentabilite'] ?? 0;
        $temps_retour_brut = $_POST['temps_retour_brut'] ?? 0;

        if ($titre === '' || $secteur === '' || $description === '' || $montant === '' || $date_limite === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
            exit;
        }
        if (!is_numeric($montant) || (float)$montant < 1) {
            echo json_encode(['success' => false, 'message' => 'Le montant doit être une valeur numérique positive.']);
            exit;
        }
        try {
            if (adminUpdateProject($projectId, [
                'titre'       => $titre,
                'description' => $description,
                'montant'     => $montant,
                'secteur'     => $secteur,
                'date_limite' => $date_limite,
                'status'      => $status,
                'taux_rentabilite' => $taux_rentabilite,
                'temps_retour_brut' => $temps_retour_brut,
            ])) {
                $project = getProjectById($projectId);
                echo json_encode(['success' => true, 'message' => 'Projet mis à jour.', 'data' => $project]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour le projet.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'admin_delete_project' && !empty($_POST['project_id'])) {
        $projectId = (int)$_POST['project_id'];
        try {
            if (adminDeleteProject($projectId)) {
                echo json_encode(['success' => true, 'message' => 'Projet supprimé avec succès.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le projet.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Action invalide.']);
