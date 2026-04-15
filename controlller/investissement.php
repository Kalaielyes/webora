<?php

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/investissement.php';

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

        if (!is_numeric($projectId) || !is_numeric($amount) || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Les valeurs de projet et de montant doivent être valides.']);
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
        if (Investissement::deleteInvestment($investmentId, $userId)) {
            echo json_encode(['success' => true, 'message' => 'Investissement supprimé.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer l\'investissement.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Action invalide.']);
