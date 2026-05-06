<?php

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/investissement.php';
require_once __DIR__ . '/../model/score.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

if (isset($_GET['debug_user_id']) && is_numeric($_GET['debug_user_id'])) {
    $_SESSION['user_id'] = (int)$_GET['debug_user_id'];
}

$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

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
        // ignore and show error below
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

function recalculateScoreForInvestmentStakeholders(?int $investmentId = null, ?int $projectId = null, ?int $investorId = null): void
{
    try {
        $pdo = Config::getConnexion();
        if ($investorId !== null && $investorId > 0) {
            Score::recalculateForUser($investorId);
        }

        if ($projectId === null && $investmentId !== null && $investmentId > 0) {
            $q = $pdo->prepare("SELECT id_projet, id_investisseur FROM investissement WHERE id_investissement = :id LIMIT 1");
            $q->execute(['id' => $investmentId]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $projectId = (int)$row['id_projet'];
                if ($investorId === null) {
                    $investorId = (int)$row['id_investisseur'];
                    Score::recalculateForUser($investorId);
                }
            }
        }

        if ($projectId !== null && $projectId > 0) {
            $q2 = $pdo->prepare("SELECT id_createur FROM projet WHERE id_projet = :pid LIMIT 1");
            $q2->execute(['pid' => $projectId]);
            $row2 = $q2->fetch(PDO::FETCH_ASSOC);
            if ($row2 && !empty($row2['id_createur'])) {
                Score::recalculateForUser((int)$row2['id_createur']);
            }
        }
    } catch (Exception $e) {
        // Non-blocking: score recalculation should never block CRUD flow.
    }
}

// Admin actions (no user check required)
if ($action === 'admin_list_investments') {
    echo json_encode(['success' => true, 'data' => Investissement::getAllInvestments()]);
    exit;
}

if ($action === 'admin_update_investment_status' && !empty($_POST['investment_id']) && !empty($_POST['new_status'])) {
    $investmentId = (int)$_POST['investment_id'];
    $newStatus = $_POST['new_status'];
    
    if (!in_array($newStatus, ['EN_ATTENTE', 'VALIDE', 'REFUSE', 'ANNULE'])) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide.']);
        exit;
    }
    
    try {
        if (Investissement::updateInvestmentStatus($investmentId, $newStatus)) {
            recalculateScoreForInvestmentStakeholders($investmentId, null, null);
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour le statut.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        exit;
    }
}

if ($userId === null) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

if ($method === 'GET') {
    if ($action === 'list_investments') {
        echo json_encode(['success' => true, 'data' => Investissement::getInvestmentsByUser($userId)]);
        exit;
    }

    if ($action === 'get_investment' && !empty($_GET['id'])) {
        $investment = Investissement::getInvestmentById((int)$_GET['id']);
        if ($investment === null) {
            echo json_encode(['success' => false, 'message' => 'Investissement introuvable.']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $investment]);
        exit;
    }
}

if ($method === 'POST') {
    if ($action === 'submit_investment' || $action === 'update_investment') {
        $projectId = $_POST['project_id'] ?? '';
        $amount = $_POST['montant'] ?? '';
        $status = $_POST['status'] ?? 'EN_ATTENTE';
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($projectId === '' || $amount === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
            exit;
        }

        if (!is_numeric($projectId) || !is_numeric($amount) || $amount < 500 || $amount > 100000) {
            echo json_encode(['success' => false, 'message' => 'Les valeurs de projet et de montant doivent être valides (min: 500, max: 100 000).']);
            exit;
        }

        try {
            if ($action === 'submit_investment') {
                $investmentId = Investissement::createInvestment([
                    'project_id' => (int)$projectId,
                    'investisseur_id' => $userId,
                    'montant' => $amount,
                    'status' => $status,
                    'commentaire' => $commentaire,
                ]);
                recalculateScoreForInvestmentStakeholders($investmentId, (int)$projectId, $userId);
                echo json_encode(['success' => true, 'message' => 'Investissement ajouté.', 'investment_id' => $investmentId]);
                exit;
            }

            if ($action === 'update_investment' && !empty($_POST['investment_id'])) {
                $investmentId = (int)$_POST['investment_id'];
                if (Investissement::updateInvestment($investmentId, $userId, [
                    'project_id' => (int)$projectId,
                    'montant' => $amount,
                    'status' => $status,
                    'commentaire' => $commentaire,
                ])) {
                    recalculateScoreForInvestmentStakeholders($investmentId, (int)$projectId, $userId);
                    echo json_encode(['success' => true, 'message' => 'Investissement mis à jour.']);
                    exit;
                }
                echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour l\'investissement.']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'delete_investment' && !empty($_POST['investment_id'])) {
        $investmentId = (int)$_POST['investment_id'];
        $projectIdForScore = null;
        try {
            $pdo = Config::getConnexion();
            $s = $pdo->prepare("SELECT id_projet FROM investissement WHERE id_investissement = :id LIMIT 1");
            $s->execute(['id' => $investmentId]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            if ($r) $projectIdForScore = (int)$r['id_projet'];
        } catch (Exception $e) {
            $projectIdForScore = null;
        }
        if (Investissement::deleteInvestment($investmentId, $userId)) {
            recalculateScoreForInvestmentStakeholders(null, $projectIdForScore, $userId);
            echo json_encode(['success' => true, 'message' => 'Investissement supprimé.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer l\'investissement.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Action invalide.']);
