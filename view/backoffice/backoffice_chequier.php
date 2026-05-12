<?php
/**
 * backoffice_chequier.php - Advanced Cheque Management Integration
 * Unified UI with LegaFin Design System + Full feature set from legacy module
 */

require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../controller/ChequierController.php';
require_once __DIR__ . '/../../controller/DemandeChequierController.php';
require_once __DIR__ . '/../../models/Chequier.php';

// Ensure admin is logged in
Session::requireAdmin('../frontoffice/login.php');

$demandeC = new DemandeChequierController();
$chequierC = new ChequierController();

function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function sendJsonResponse(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function generateUniqueChequierNumber(PDO $db, string $preferredNumber = ''): string {
    $base = trim($preferredNumber);
    $checkSql = "SELECT COUNT(*) FROM chequier WHERE numero_chequier = :num";
    $checkStmt = $db->prepare($checkSql);

    if ($base !== '') {
        $checkStmt->execute([':num' => $base]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            return $base;
        }
    }

    do {
        $timestamp = date('YmdHis');
        $randomPart = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $candidate = 'CHQ-' . $timestamp . '-' . $randomPart;
        $checkStmt->execute([':num' => $candidate]);
    } while ((int)$checkStmt->fetchColumn() > 0);

    return $candidate;
}

// ── Handle Post Actions (Legacy Logic) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_chequier'])) {
    $id_demande = (int)$_POST['id_demande'];
    $id_Compte = (int)$_POST['id_Compte'];

    $db = Config::getConnexion();
    $numero_chequier = generateUniqueChequierNumber($db, $_POST['numero_chequier'] ?? '');
    
    $data = [
        'numero_chequier' => $numero_chequier,
        'date_creation' => $_POST['date_creation'],
        'date_expiration' => $_POST['date_expiration'],
        'statut' => $_POST['statut_chequier'],
        'nombre_feuilles' => (int)$_POST['nombre_feuilles'],
        'id_demande' => $id_demande,
        'id_Compte' => $id_Compte
    ];
    
    $newChequier = new Chequier($data);
    try {
        if ($chequierC->addChequier($newChequier)) {
            $demandeC->updateStatus($id_demande, 'Acceptée');

            if (isAjaxRequest()) {
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Chéquier créé avec succès et demande acceptée.',
                    'numero_chequier' => $numero_chequier
                ]);
            }

            header('Location: backoffice_chequier.php?success=1&action=dashboard');
            exit();
        }
    } catch (Exception $e) {
        if (isAjaxRequest()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création du chéquier.',
                'details' => $e->getMessage()
            ], 500);
        }

        $error_msg = "Erreur : " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_chequier'])) {
    $id_chequier = (int)$_POST['id_chequier'];
    $data = [
        'id_chequier' => $id_chequier,
        'id_demande' => (int)($_POST['id_demande'] ?? 0),
        'date_expiration' => $_POST['date_expiration'],
        'statut' => $_POST['statut_chequier'],
        'nombre_feuilles' => (int)$_POST['nombre_feuilles']
    ];
    // Create a partial Chequier object or use existing logic
    $chequier = new Chequier($data); 
    try {
        if ($chequierC->updateChequier($chequier)) {

            if (isAjaxRequest()) {
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Chéquier modifié avec succès.'
                ]);
            }

            header('Location: backoffice_chequier.php?success=1&action_type=edit&action=chequiers');
            exit();
        }
    } catch (Exception $e) {
        if (isAjaxRequest()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la modification du chéquier.',
                'details' => $e->getMessage()
            ], 500);
        }

        $error_msg = "Erreur : " . $e->getMessage();
    }
}

if (isset($_GET['op']) && isset($_GET['id'])) {
    $op = $_GET['op'];
    $id = (int)$_GET['id'];
    if ($op === 'refuser') {
        $demandeC->updateStatus($id, 'Refusée');
        header('Location: backoffice_chequier.php?success=0&action_type=refus&action=demandes');
        exit();
    } elseif ($op === 'delete_chq') {
        $chq = $chequierC->getChequierById($id);
        if ($chq) {
            $id_demande_reset = $chq['id_demande'];
            if ($chequierC->deleteChequier($id)) {
                $demandeC->updateStatus($id_demande_reset, 'En attente');

                if (isAjaxRequest()) {
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Chéquier supprimé et demande remise en attente.'
                    ]);
                }

                header('Location: backoffice_chequier.php?success=1&action_type=delete&action=chequiers');
                exit();
            }
        }

        if (isAjaxRequest()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Chéquier introuvable ou suppression impossible.'
            ], 404);
        }
    }
}

// ── Data Fetching ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'dashboard';
$demandes_db = $demandeC->listDemandes();
$chequiers_db = $chequierC->listChequiers();

$stats = ['en_attente' => 0, 'actifs' => 0, 'refusees' => 0];
foreach($demandes_db as $d) {
    $s = $d['statut'] ?? 'En attente';
    if($s === 'En attente') $stats['en_attente']++;
    if($s === 'Acceptée') $stats['actifs']++;
    if($s === 'Refusée') $stats['refusees']++;
}

$flashMessage = '';
$flashType = '';
if (isset($_GET['success'])) {
    $successFlag = (int)$_GET['success'];
    $actionType = strtolower(trim((string)($_GET['action_type'] ?? '')));

    if ($successFlag === 1) {
        if ($actionType === 'edit') {
            $flashMessage = 'Chéquier modifié avec succès.';
        } elseif ($actionType === 'delete') {
            $flashMessage = 'Chéquier supprimé avec succès.';
        } else {
            $flashMessage = 'Chéquier créé avec succès et demande acceptée.';
        }
        $flashType = 'success';
    } else {
        if ($actionType === 'refus') {
            $flashMessage = 'Demande refusée.';
        } else {
            $flashMessage = 'Opération non effectuée.';
        }
        $flashType = 'error';
    }
}

function demandInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach ($parts as $part) {
        if ($part === '') continue;
        $initials .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
        if (mb_strlen($initials, 'UTF-8') >= 2) break;
    }
    return $initials !== '' ? $initials : 'CL';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chèques - LegaFin</title>
    <link rel="stylesheet" href="../../assets/css/backoffice/Utilisateur.css">
    <link rel="stylesheet" href="../../assets/css/backoffice/chequier.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Ajustements pour intégration sidebar unifiée */
        .main { padding-top: 0; }
        .topbar { position: sticky; top: 0; z-index: 100; background: var(--bg1); }
        .modal-overlay { 
            display: none; 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.7); z-index: 10000; 
            align-items: center; justify-content: center; 
            backdrop-filter: blur(8px);
        }
        .modal-overlay.active { display: flex !important; }
        
        .premium-modal {
            background: #ffffff;
            width: 100%;
            max-width: 850px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalSlideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .visual-check-premium {
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            margin: 0 2.5rem 2rem;
            height: 240px;
            border-radius: 20px;
            padding: 2.2rem;
            color: white;
            position: relative;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25);
        }
        
        .premium-input {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            color: #0f172a !important;
            padding: 0.8rem 1rem 0.8rem 3rem !important;
            font-family: var(--fb) !important;
            font-size: 0.9rem !important;
            transition: all 0.2s !important;
            width: 100%;
        }
        .premium-input:focus {
            border-color: #3b82f6 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
            outline: none;
        }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 1.2rem; color: #94a3b8; font-size: 0.95rem; }

        .btn-cancel { background: #f1f5f9; color: #475569; border: none; border-radius: 12px; padding: 0.8rem 2rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-cancel:hover { background: #e2e8f0; }
        .btn-submit-premium { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; border-radius: 12px; padding: 0.8rem 2.5rem; font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3); transition: all 0.2s; }
        .btn-submit-premium:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4); }

        /* Requests block redesigned to match the reference mockup */
        .demand-card {
            background: #ffffff;
            border: 1px solid #e5e9f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .demand-card .table-toolbar {
            padding: 0.95rem 1.15rem;
            border-bottom: 1px solid #e8edf3;
        }
        .demand-card .table-toolbar-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #0f172a;
        }
        .demand-card .filters {
            gap: 0.55rem;
        }
        .demand-card .filter-btn {
            border-color: #d9e1ea;
            border-radius: 9px;
            padding: 0.34rem 0.95rem;
            background: #ffffff;
            color: #64748b;
        }
        .demand-card .filter-btn:hover,
        .demand-card .filter-btn.active {
            border-color: #8fb3ff;
            color: #2f5fff;
            background: #eef4ff;
        }
        .demand-card .table-scroll {
            overflow-x: auto;
        }
        .demand-card table {
            min-width: 760px;
        }
        .demand-card thead tr {
            background: #f7f9fc;
        }
        .demand-card th {
            padding: 0.9rem 1.15rem;
            color: #7b8794;
            font-size: 0.7rem;
            letter-spacing: 0.08em;
        }
        .demand-card td {
            padding: 1rem 1.15rem;
            border-top: 1px solid #edf1f6;
            vertical-align: middle;
        }
        .request-row {
            transition: transform 0.15s ease, background 0.15s ease;
        }
        .request-row:hover {
            transform: translateY(-1px);
        }
        .request-row:hover td {
            background: #fafcff;
        }
        .client-cell {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .client-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #0d9488);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .client-name {
            font-size: 0.86rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.15;
        }
        .client-sub {
            margin-top: 0.12rem;
            font-size: 0.67rem;
            color: #94a3b8;
            font-family: var(--fm);
        }
        .request-row .badge {
            font-size: 0.72rem;
            padding: 0.25rem 0.75rem;
        }
        .request-actions .act-btn {
            width: 31px;
            height: 31px;
            border-radius: 8px;
            border-color: #dbe3ee;
            color: #64748b;
        }
        .request-actions .act-btn:hover {
            border-color: #8fb3ff;
            color: #2f5fff;
            background: #eef4ff;
        }
        .request-actions .act-btn.success:hover {
            border-color: #86efac;
            color: #16a34a;
            background: #f0fdf4;
        }
        @media (max-width: 1180px) {
            .two-col-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/partials/sidebar_unified.php'; ?>

    <div class="main" id="main-content">
        <div class="topbar">
            <div class="tb-left">
                <div class="page-title"><?= $action === 'chequiers' ? 'Mes chéquiers' : 'Gestion des chéquiers' ?></div>
                <div class="breadcrumb">/ <?= $action === 'chequiers' ? 'Client' : 'Admin' ?> / Chéquiers / <?= ucfirst($action) ?></div>
            </div>
            <div class="tb-right">
                <div class="search-bar">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input id="searchInput" oninput="updateSearch()" placeholder="Rechercher n° chéquier, client..."/>
                </div>
                <button class="btn-primary" onclick="openPremiumModal()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Émettre un chéquier
                </button>
            </div>
        </div>

        <div class="content">
            <?php if ($action === 'chequiers'): ?>
                <!-- VIEW: MES CHEQUIERS (CARDS) -->
                <div class="chq-grid-bo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($chequiers_db as $chq): 
                        $st = trim(strtolower($chq['statut'] ?? 'actif'));
                        $isActif = ($st === 'actif');
                        $statusClass = $isActif ? 'b-actif' : 'b-bloque';
                        $dotColor = $isActif ? 'var(--green)' : 'var(--rose)';
                        $creationDate = new DateTime($chq['date_creation']);
                        $expDate = new DateTime($chq['date_expiration']);
                    ?>
                    <div class="chq-card-bo" data-name="<?= htmlspecialchars($chq['nom_client'] ?? 'Inconnu') ?>" data-num="<?= htmlspecialchars($chq['numero_chequier']) ?>" style="background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; display:flex; flex-direction:column; transition:transform 0.2s, box-shadow 0.2s; cursor:default;">
                        <div class="bo-chq-visual" style="height:140px; background:linear-gradient(135deg, <?= $isActif ? '#0f172a, #1e293b' : '#450a0a, #7f1d1d' ?>); padding:1.2rem; color:white; position:relative; overflow:hidden;">
                            <div style="font-family:var(--fm); font-size:0.75rem; opacity:0.6; letter-spacing:0.1em;"><?= htmlspecialchars($chq['numero_chequier']) ?></div>
                            <div style="position:absolute; bottom:1.2rem; left:1.2rem;">
                                <div style="font-size:0.6rem; text-transform:uppercase; opacity:0.5; margin-bottom:2px;">Détenteur</div>
                                <div style="font-weight:600; font-size:0.85rem; letter-spacing:0.02em;"><?= htmlspecialchars($chq['nom_client'] ?? 'Inconnu') ?></div>
                            </div>
                            <div style="position:absolute; bottom:1.2rem; right:1.2rem; text-align:right;">
                                <div style="font-size:1.2rem; font-weight:800; opacity:0.9;"><?= htmlspecialchars($chq['nombre_feuilles']) ?></div>
                                <div style="font-size:0.55rem; text-transform:uppercase; opacity:0.5;">Feuilles</div>
                            </div>
                        </div>
                        <div style="padding:1.2rem; flex:1; display:flex; flex-direction:column; gap:1rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:0.85rem; font-weight:700; color:var(--text);">Chéquier N° <?= htmlspecialchars($chq['numero_chequier']) ?></span>
                                <span class="badge <?= $statusClass ?>" style="font-size:0.65rem;"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= ucfirst($st) ?></span>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
                                <div class="ci-item-bo">
                                    <div style="font-size:0.65rem; color:var(--muted); margin-bottom:2px;">Création</div>
                                    <div style="font-size:0.78rem; font-weight:500;"><?= $creationDate->format('d M. Y') ?></div>
                                </div>
                                <div class="ci-item-bo">
                                    <div style="font-size:0.65rem; color:var(--muted); margin-bottom:2px;">Expiration</div>
                                    <div style="font-size:0.78rem; font-weight:500;"><?= $expDate->format('d M. Y') ?></div>
                                </div>
                                <div class="ci-item-bo" style="grid-column: span 2;">
                                    <div style="font-size:0.65rem; color:var(--muted); margin-bottom:2px;">Compte lié</div>
                                    <div style="font-size:0.72rem; font-family:var(--fm); color:var(--navy3);"><?= htmlspecialchars($chq['iban'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                            <div style="margin-top:auto; padding-top:1rem; border-top:1px solid var(--border); display:flex; gap:0.6rem;">
                                <?php 
                                $mockDem = [
                                    'id' => $chq['id_demande'],
                                    'idCompte' => $chq['id_Compte'] ?? $chq['id_compte'] ?? 0,
                                    'name' => $chq['nom_client'] ?? 'Inconnu',
                                    'iban' => $chq['iban'] ?? 'N/A'
                                ];
                                ?>
                                <button class="btn-primary" style="flex:1; justify-content:center; padding:0.5rem;" onclick='openPremiumModal(<?= json_encode($mockDem) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Modifier
                                </button>
                                <button class="filter-btn" style="padding:0.5rem 0.8rem;" onclick="window.location.href='../../controller/generate_attestation.php?id=<?= $chq['id_chequier'] ?>'">
                                    <i class="fa-solid fa-print"></i> Attestation
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- VIEW: DASHBOARD / DEMANDES -->
                <div class="kpi-row">
                    <div class="kpi" onclick="filterByStatus('en-attente')" style="cursor:pointer">
                        <div class="kpi-icon" style="background:var(--amber-light)">
                            <svg width="18" height="18" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        </div>
                        <div class="kpi-data">
                            <div class="kpi-val"><?= (int)$stats['en_attente'] ?></div>
                            <div class="kpi-label">Demandes en attente</div>
                        </div>
                    </div>
                    <div class="kpi" onclick="filterByStatus('acceptee')" style="cursor:pointer">
                        <div class="kpi-icon" style="background:var(--blue-light)">
                            <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="kpi-data">
                            <div class="kpi-val"><?= $stats['actifs'] ?></div>
                            <div class="kpi-label">Chéquiers actifs</div>
                        </div>
                    </div>
                    <div class="kpi" onclick="filterByStatus('refusee')" style="cursor:pointer">
                        <div class="kpi-icon" style="background:var(--rose-light)">
                            <svg width="18" height="18" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        </div>
                        <div class="kpi-data">
                            <div class="kpi-val"><?= $stats['refusees'] ?></div>
                            <div class="kpi-label">Chéquiers refusés</div>
                        </div>
                    </div>
                </div>

                <div class="two-col-layout">
                    <div id="tabDemandes" class="table-card demand-card">
                        <div class="table-toolbar">
                            <div class="table-toolbar-title">Demandes de chéquier</div>
                            <div class="filters">
                                <button class="filter-btn active" onclick="filterByStatus('tous')">Tous</button>
                                <button class="filter-btn" onclick="filterByStatus('en-attente')">En attente</button>
                            </div>
                        </div>
                        <div class="table-scroll">
                            <table id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>N° Demande</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="demandesTableBody">
                                    <?php foreach ($demandes_db as $dem): 
                                        $statut = $dem['statut'] ?? 'En attente';
                                        $sClass = ($statut === 'Acceptée') ? 'b-acceptee' : (($statut === 'Refusée') ? 'b-refusee' : 'b-attente');
                                        $fullName = trim((string)($dem['nom et prenom'] ?? 'Client'));
                                        $iban = trim((string)($dem['iban'] ?? ''));
                                    ?>
                                    <tr class="request-row" onclick="showDetail(this)" style="cursor:pointer;" data-status="<?= strtolower(str_replace('é', 'e', $statut)) ?>" data-id="<?= $dem['id_demande'] ?>" data-id-compte="<?= $dem['id_compte'] ?>" data-name="<?= htmlspecialchars($dem['nom et prenom']) ?>" data-iban="<?= htmlspecialchars($dem['iban']) ?>" data-date="<?= date('d/m/Y', strtotime($dem['date_demande'])) ?>" data-type="<?= ucfirst($dem['type_chequier'] ?? 'Standard') ?>" data-nb="<?= $dem['nombre_cheques'] ?>" data-montant="<?= $dem['montant_max_par_cheque'] ?>" data-mode="<?= $dem['mode_reception'] ?>" data-tel="<?= $dem['telephone'] ?>" data-email="<?= $dem['email'] ?>" data-statut="<?= $statut ?>">
                                        <td>
                                            <div class="client-cell">
                                                <div class="client-avatar"><?= htmlspecialchars(demandInitials($fullName)) ?></div>
                                                <div>
                                                    <div class="client-name"><?= htmlspecialchars($fullName) ?></div>
                                                    <div class="client-sub"><?= htmlspecialchars($iban !== '' ? $iban : 'Aucun IBAN renseigné') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="td-mono">DEM-<?= $dem['id_demande'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($dem['date_demande'])) ?></td>
                                        <td><span class="badge <?= $sClass ?>"><?= $statut ?></span></td>
                                        <td>
                                            <div class="action-group request-actions">
                                                <button class="act-btn" onclick="event.stopPropagation(); showDetail(this.closest('tr'))"><i class="fa-solid fa-eye"></i></button>
                                                <button class="act-btn success" onclick="event.stopPropagation(); openPremiumModal(this.closest('tr').dataset)"><i class="fa-solid fa-check"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="detail-panel" id="detailPanel">
                        <!-- HEADER WITH BADGES -->
                        <div class="dp-header" style="display: flex; align-items: center; gap: 1rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--border);">
                            <div class="dp-av" id="dpInitials" style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #2563EB, #0D9488); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem;">?</div>
                            <div style="flex: 1;">
                                <div class="dp-name" id="dpName" style="font-size: 1rem; font-weight: 700; color: var(--navy);">Sélectionner une demande</div>
                                <div id="dpCin" style="font-size: 0.72rem; color: var(--muted); margin-bottom: 4px;">CIN: —</div>
                                <div style="display: flex; gap: 4px;">
                                    <span class="badge b-actif" style="font-size: 0.55rem; padding: 1px 6px;">KYC VERIFIE</span>
                                    <span class="badge b-standard" style="font-size: 0.55rem; padding: 1px 6px;">CLIENT</span>
                                </div>
                            </div>
                        </div>

                        <!-- TABS SELECTION -->
                        <div class="panel-tabs" style="display: flex; gap: 0; border-bottom: 1px solid var(--border); margin-top: 1rem;">
                            <button class="panel-tab active" onclick="switchPanelTab('demande')" id="tabBtnDemande" style="flex:1; padding: 0.6rem; border:none; background:none; font-size: 0.8rem; font-weight: 600; cursor:pointer; color:var(--blue); border-bottom: 2px solid var(--blue);">Demande</button>
                            <button class="panel-tab" onclick="switchPanelTab('chequier')" id="tabBtnChequier" style="flex:1; padding: 0.6rem; border:none; background:none; font-size: 0.8rem; font-weight: 600; cursor:pointer; color:var(--muted); border-bottom: 2px solid transparent;">Chéquier</button>
                            <button class="panel-tab" onclick="switchPanelTab('historique')" id="tabBtnHist" style="flex:1; padding: 0.6rem; border:none; background:none; font-size: 0.8rem; font-weight: 600; cursor:pointer; color:var(--muted); border-bottom: 2px solid transparent;">Historique</button>
                        </div>

                        <div id="dpContent" style="display:none; padding-top: 1rem; overflow-y: auto; max-height: 500px;">
                            <!-- TAB: DEMANDE -->
                            <div id="paneDemande" class="tab-pane">
                                <div class="dp-section" style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 0.8rem;">Détails de la demande</div>
                                <div class="dp-row"><span class="dp-key">Date demande</span><span class="dp-val" id="dpDate">—</span></div>
                                <div class="dp-row"><span class="dp-key">Statut</span><span class="badge b-attente" id="dpStatutBadge" style="font-size:0.65rem;">En attente</span></div>
                                <div class="dp-row"><span class="dp-key">Type chéquiers</span><span class="badge b-standard" id="dpType" style="font-size:0.65rem;">Standard</span></div>
                                <div class="dp-row"><span class="dp-key">Nombre chèques</span><span class="dp-val" id="dpNb">—</span></div>
                                <div class="dp-row"><span class="dp-key">Montant max/chèque</span><span class="dp-val" id="dpMontant" style="font-weight:700;">—</span></div>
                                <div class="dp-row"><span class="dp-key">Mode réception</span><span class="dp-val" id="dpMode">Agence</span></div>
                                <div class="dp-row"><span class="dp-key">Téléphone</span><span class="dp-val" id="dpTel">—</span></div>
                                <div class="dp-row"><span class="dp-key">Email</span><span class="dp-val" id="dpEmail">—</span></div>
                                <div class="dp-row"><span class="dp-key">Compte lié</span><span class="dp-val-mono" id="dpIban" style="font-size:0.7rem;">—</span></div>
                                
                                <div style="margin-top:1.5rem; display:flex; flex-direction:column; gap:0.6rem;">
                                    <button class="da-success dp-action-btn" id="btnApproveDetail" style="border:1px solid #BBF7D0; background:#F0FDF4; color:#16A34A; padding:0.6rem; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.8rem;">Accepter la demande</button>
                                    <button class="da-danger dp-action-btn" id="btnRejectDetail" style="border:1px solid #FECACA; background:#FEF2F2; color:#DC2626; padding:0.6rem; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.8rem;">Refuser la demande</button>
                                </div>
                            </div>

                            <!-- TAB: CHEQUIER -->
                            <div id="paneChequier" class="tab-pane" style="display:none">
                                <div class="dp-section" style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 0.8rem;">Informations Personnelles</div>
                                <div class="dp-row"><span class="dp-key">Nom</span><span class="dp-val" id="dpLastName">—</span></div>
                                <div class="dp-row"><span class="dp-key">Prénom</span><span class="dp-val" id="dpFirstName">—</span></div>
                                <div class="dp-row"><span class="dp-key">Email</span><span class="dp-val" id="dpEmail2">—</span></div>
                                <div class="dp-row"><span class="dp-key">Téléphone</span><span class="dp-val" id="dpTel2">—</span></div>
                                
                                <div class="dp-section" style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 0.8rem; margin-top:1.5rem;">Compte & Statut</div>
                                <div class="dp-row"><span class="dp-key">KYC</span><span class="badge b-actif" style="font-size:0.65rem;">VERIFIE</span></div>
                                <div class="dp-row"><span class="dp-key">AML</span><span class="badge b-attente" style="font-size:0.65rem;">EN_ATTENTE</span></div>
                                <div class="dp-row"><span class="dp-key">Statut</span><span class="badge b-actif" style="font-size:0.65rem;">ACTIF</span></div>
                                
                                <div style="margin-top:1.5rem; background:#F8F9FB; border:1px solid var(--border); border-radius:12px; padding:1rem;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
                                        <div style="font-size:0.7rem; font-weight:700; color:var(--navy);">SCORE ANTI-FRAUDE (AML)</div>
                                        <span style="font-size:0.6rem; background:#F5F3FF; color:#7C3AED; padding:2px 6px; border-radius:4px;">Scan API</span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:1rem;">
                                        <div style="width:50px; height:50px; border-radius:50%; border:4px solid #16A34A; display:flex; align-items:center; justify-content:center; font-weight:800; color:#16A34A; font-size:0.8rem;">92%</div>
                                        <div style="flex:1; height:6px; background:#E2E8F0; border-radius:3px; position:relative;">
                                            <div style="position:absolute; left:0; top:0; height:100%; width:92%; background:#16A34A; border-radius:3px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: HISTORIQUE -->
                            <div id="paneHistorique" class="tab-pane" style="display:none">
                                <div class="dp-section" style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 0.8rem;">Historique d'activité</div>
                                <div class="history-list" id="dpHistory">
                                    <div class="hist-item">
                                        <div class="hist-dot" style="background:var(--blue)"></div>
                                        <div class="hist-content">
                                            <div class="hist-text">Demande de chéquier soumise</div>
                                            <div class="hist-date">Aujourd'hui, 14:20</div>
                                        </div>
                                    </div>
                                    <div class="hist-item">
                                        <div class="hist-dot" style="background:var(--green)"></div>
                                        <div class="hist-content">
                                            <div class="hist-text">KYC validé par le système</div>
                                            <div class="hist-date">Aujourd'hui, 14:25</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="dpPlaceholder" style="text-align:center; padding: 4rem 0; color:var(--muted)">
                            <div style="width:60px; height:60px; background:#F1F5F9; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                                <i class="fa-solid fa-user-gear" style="font-size:1.5rem; color:#94A3B8;"></i>
                            </div>
                            <p style="font-size:.85rem; font-weight:500;">Sélectionner un dossier</p>
                            <p style="font-size:.7rem; opacity:0.7;">Cliquez sur une ligne pour voir les détails</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PREMIUM ÉMISSION CHÈQUIER MODAL (LEGACY DESIGN) -->
    <div class="modal-overlay" id="premiumChequeModal">
        <div class="premium-modal">
            <div class="modal-header" style="padding: 2.2rem 2.5rem 1.5rem; display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 id="premiumModalTitle" style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0; font-family: var(--fh);">Émettre un nouveau chéquier</h1>
                    <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.3rem;">Demande associée : <span id="disp_id_demande_modal" style="color:#2563EB; font-weight:700;">DEM-114</span></div>
                </div>
                <button class="close-btn" id="premiumModalCloseBtn" style="background:#f1f5f9; border:none; width:36px; height:36px; border-radius:50%; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; transition: all 0.2s;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="visual-check-premium">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                        <div style="background: white; color: #1e3a8a; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.3rem;">LF</div>
                        <div>
                            <div style="font-weight: 700; font-size: 1.05rem; letter-spacing: 0.01em;">LegalFin Bank</div>
                            <div style="font-size: 0.65rem; opacity: 0.6; font-weight: 500;">Chéquier Privilège — Service Premium</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-family: var(--fm); font-size: 0.95rem; font-weight: 600; letter-spacing: 0.05em;" id="disp_chequier_num_preview">CHQ-2026-42006</div>
                        <div style="font-size: 0.62rem; opacity: 0.5; margin-top: 4px; text-transform: uppercase; font-weight: 600;">Créé le : <span id="preview_create_date" style="opacity: 1; color: white;"><?= date('d/m/Y') ?></span></div>
                        <div style="font-size: 0.62rem; opacity: 0.5; margin-top: 2px; text-transform: uppercase; font-weight: 600;">Expiration : <span id="preview_exp_date" style="opacity: 1; color: white;">09/05/2027</span></div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div style="flex: 1;">
                        <div style="font-size: 0.6rem; text-transform: uppercase; opacity: 0.5; letter-spacing: 0.1em; font-weight: 700; margin-bottom: 2px;">Titulaire du compte</div>
                        <div style="font-family: 'Dancing Script', cursive; font-size: 1.9rem; color: white; line-height: 1.2; margin-bottom: 2px;" id="preview_signature">mouna ncib</div>
                        <div style="font-family: var(--fm); font-size: 0.72rem; opacity: 0.6;" id="preview_iban">TN593013657184687458</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 2.2rem; font-weight: 800; color: white; line-height: 0.9;" id="preview_nb_feuilles">25</div>
                        <div style="font-size: 0.6rem; text-transform: uppercase; opacity: 0.5; letter-spacing: 0.1em; font-weight: 700;">Feuilles standard</div>
                    </div>
                </div>
            </div>

            <div style="padding: 0 2.5rem 2.5rem;">
                <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #94a3b8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    Informations du chéquier <span style="flex: 1; height: 1px; background: #f1f5f9;"></span>
                </div>

                <form id="formPremium" onsubmit="submitChequerForm(event)" novalidate>
                    <input type="hidden" name="id_demande" id="hidden_id_demande_modal">
                    <input type="hidden" name="id_Compte" id="hidden_id_compte_modal">
                    <input type="hidden" name="id_chequier" id="hidden_id_chequier_modal">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 0.5rem;">N° Chéquier (Référence officielle)</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-hashtag input-icon"></i>
                                <input type="text" name="numero_chequier" id="input_numero_chequier" class="premium-input" placeholder="CHQ-2026-XXXXX" oninput="updatePremiumPreview()">
                            </div>
                            <div style="font-size: 0.68rem; color: #2563EB; margin-top: 6px; font-weight: 500;">• Vous pouvez utiliser le numéro généré ou le saisir manuellement.</div>
                        </div>

                        <div class="form-group">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 0.5rem;">Date de création <span style="color:#EF4444;">*</span></label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-calendar-plus input-icon"></i>
                                <input type="date" name="date_creation" id="modal_date_creation" class="premium-input" value="<?= date('Y-m-d') ?>" oninput="updatePremiumPreview()">
                            </div>
                            <div style="font-size: 0.68rem; color: #94a3b8; margin-top: 6px; font-weight: 500;">• Date d'émission officielle</div>
                        </div>

                        <div class="form-group">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 0.5rem;">Nombre de feuilles <span style="color:#EF4444;">*</span></label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-layer-group input-icon"></i>
                                <select name="nombre_feuilles" id="modal_nb_feuilles" class="premium-input" style="appearance: none;" onchange="updatePremiumPreview()">
                                    <option value="25">25 feuilles (Standard)</option>
                                    <option value="50">50 feuilles (Professionnel)</option>
                                    <option value="100">100 feuilles (Grand Format)</option>
                                </select>
                                <i class="fa-solid fa-chevron-down" style="position: absolute; right: 1rem; color: #94a3b8; font-size: 0.8rem; pointer-events: none;"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 0.5rem;">Date d'expiration <span style="color:#EF4444;">*</span></label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-calendar-check input-icon"></i>
                                <input type="date" name="date_expiration" id="modal_date_exp" class="premium-input" oninput="updatePremiumPreview()">
                            </div>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 0.5rem;">Statut Initial <span style="color:#EF4444;">*</span></label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-shield-halved input-icon"></i>
                                <select name="statut_chequier" id="modal_statut" class="premium-input" style="appearance: none;">
                                    <option value="actif">Actif (Prêt à l'usage)</option>
                                    <option value="bloque">Bloqué (En suspens)</option>
                                    <option value="expire">Expiré (Ancien)</option>
                                </select>
                                <i class="fa-solid fa-chevron-down" style="position: absolute; right: 1rem; color: #94a3b8; font-size: 0.8rem; pointer-events: none;"></i>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 2.5rem; display: flex; justify-content: flex-end; gap: 1rem; align-items: center; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; flex-wrap: wrap;">
                        <button type="button" class="btn-cancel" onclick="closePremiumModal()">Annuler</button>
                        <button type="submit" name="modifier_chequier" id="btnModifierModal" class="btn-submit-premium" style="display:none;">
                            <i class="fa-solid fa-pen-to-square" style="margin-right:8px;"></i>Modifier
                        </button>
                        <button type="button" id="btnDeleteModal" class="btn-cancel" style="background:#fee2e2; color:#dc2626; display:none;">
                            <i class="fa-solid fa-trash-can" style="margin-right:8px;"></i>Supprimer
                        </button>
                        <button type="submit" name="creer_chequier" id="btnEmettreModal" class="btn-submit-premium">
                            <i class="fa-solid fa-plus" style="margin-right:8px;"></i>Émettre (Ajouter)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentStatusFilter = 'tous';
        const existingChequiers = <?= json_encode($chequiers_db) ?>;

        function updateSearch() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.request-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                const status = row.dataset.status;
                const matchesSearch = text.includes(query);
                const matchesStatus = (currentStatusFilter === 'tous' || status === currentStatusFilter);
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
            document.querySelectorAll('.chq-card-bo').forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function filterByStatus(status) {
            currentStatusFilter = status;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.innerText.toLowerCase().includes(status.replace('-', '')) || (status === 'tous' && btn.innerText === 'Tous'));
            });
            updateSearch();
        }

        function switchPanelTab(tab) {
            const tabs = ['demande', 'chequier', 'historique'];
            tabs.forEach(t => {
                const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1, 4 === t.length ? 4 : t.length === 8 ? 4 : 4));
                // Correction pour les ID de boutons
                let btnId = '';
                if(t === 'demande') btnId = 'tabBtnDemande';
                if(t === 'chequier') btnId = 'tabBtnChequier';
                if(t === 'historique') btnId = 'tabBtnHist';
                
                const b = document.getElementById(btnId);
                const p = document.getElementById('pane' + t.charAt(0).toUpperCase() + t.slice(1));
                
                if(b) {
                    b.style.color = (t === tab) ? 'var(--blue)' : 'var(--muted)';
                    b.style.borderBottom = (t === tab) ? '2px solid var(--blue)' : '2px solid transparent';
                }
                if(p) p.style.display = (t === tab) ? 'block' : 'none';
            });
        }

        function showDetail(data) {
            if(data instanceof HTMLElement) {
                const ds = data.dataset;
                data = {
                    'nom et prenom': ds.name,
                    date_demande: ds.date,
                    nombre_cheques: ds.nb,
                    type_chequier: ds.type,
                    montant_max_par_cheque: ds.montant,
                    telephone: ds.tel,
                    email: ds.email,
                    iban: ds.iban,
                    id_demande: ds.id,
                    id_compte: ds.idCompte,
                    statut: ds.statut
                };
            } else if(typeof data === 'string') data = JSON.parse(data);

            document.getElementById('dpPlaceholder').style.display = 'none';
            document.getElementById('dpContent').style.display = 'block';
            
            // Header
            document.getElementById('dpName').textContent = data['nom et prenom'];
            document.getElementById('dpInitials').textContent = data['nom et prenom'].split(' ').map(n => n[0]).join('').toUpperCase();
            document.getElementById('dpCin').textContent = 'CIN: 12345601'; // Mock or data[cin]
            
            // Tab Demande
            document.getElementById('dpDate').textContent = data.date_demande || data.date;
            document.getElementById('dpNb').textContent = data.nombre_cheques || data.nb;
            document.getElementById('dpType').textContent = data.type_chequier || data.type;
            document.getElementById('dpMontant').textContent = (data.montant_max_par_cheque || data.montant) + ' TND';
            document.getElementById('dpTel').textContent = data.telephone || data.tel;
            document.getElementById('dpEmail').textContent = data.email || '—';
            document.getElementById('dpIban').textContent = data.iban;
            
            // Tab Chequier (Personal info)
            const names = data['nom et prenom'].split(' ');
            document.getElementById('dpFirstName').textContent = names[0] || '—';
            document.getElementById('dpLastName').textContent = names.slice(1).join(' ') || '—';
            document.getElementById('dpEmail2').textContent = data.email || '—';
            document.getElementById('dpTel2').textContent = data.telephone || data.tel;

            // Statut Badge in panel
            const sBadge = document.getElementById('dpStatutBadge');
            const st = (data.statut || 'En attente').toLowerCase();
            sBadge.textContent = data.statut || 'En attente';
            sBadge.className = 'badge ' + (st.includes('acceptee') ? 'b-acceptee' : st.includes('refusee') ? 'b-refusee' : 'b-attente');

            document.getElementById('btnApproveDetail').onclick = () => openPremiumModal(data);
            document.getElementById('btnRejectDetail').onclick = () => {
                if(confirm('Refuser cette demande ?')) window.location.href = 'backoffice_chequier.php?op=refuser&id=' + (data.id_demande || data.id);
            };

            switchPanelTab('demande');
        }

        function openPremiumModal(d) {
            if(!d) {
                d = { 'nom et prenom': 'Client Inconnu', iban: 'N/A', id_demande: 0, id_compte: 0 };
            }
            if(typeof d === 'string') d = JSON.parse(d);
            
            const modal = document.getElementById('premiumChequeModal');
            modal.classList.add('active');
            
            const requestId = parseInt(d.id_demande || d.id || 0);
            const chq = existingChequiers.find(c => parseInt(c.id_demande) === requestId);
            const isEdit = !!chq;
            const titleEl = document.getElementById('premiumModalTitle');
            
            document.getElementById('hidden_id_demande_modal').value = d.id_demande || d.id;
            document.getElementById('hidden_id_compte_modal').value = d.id_compte || d.idCompte || (chq ? (chq.id_Compte || chq.id_compte || '') : '');
            document.getElementById('hidden_id_chequier_modal').value = isEdit ? chq.id_chequier : "";
            
            document.getElementById('preview_signature').textContent = d['nom et prenom'] || d.name;
            document.getElementById('preview_iban').textContent = d.iban;
            document.getElementById('disp_id_demande_modal').textContent = 'DEM-' + (d.id_demande || d.id);
            
            const btnModifier = document.getElementById('btnModifierModal');
            const btnEmettre = document.getElementById('btnEmettreModal');
            const btnDelete = document.getElementById('btnDeleteModal');

            if (isEdit) {
                if (titleEl) titleEl.textContent = "Modifier le chéquier";
                document.getElementById('input_numero_chequier').value = chq.numero_chequier;
                document.getElementById('modal_nb_feuilles').value = chq.nombre_feuilles;
                document.getElementById('modal_date_creation').value = chq.date_creation;
                document.getElementById('modal_date_exp').value = chq.date_expiration;
                document.getElementById('modal_statut').value = chq.statut;
                btnModifier.style.display = 'block';
                btnEmettre.style.display = 'none';
                btnDelete.style.display = 'block';
                btnDelete.onclick = () => {
                    if(confirm('Supprimer définitivement ce chéquier ?')) {
                        fetch('backoffice_chequier.php?op=delete_chq&id=' + chq.id_chequier, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(async response => {
                            const payload = await response.json().catch(() => null);
                            if (!payload) {
                                throw new Error('Réponse serveur invalide');
                            }
                            if (!response.ok || payload.success === false) {
                                throw new Error(payload.message || 'Suppression impossible');
                            }
                            return payload;
                        })
                        .then(payload => {
                            alert(payload.message || 'Chéquier supprimé.');
                            closePremiumModal();
                            location.reload();
                        })
                        .catch(error => {
                            alert('Erreur: ' + error.message);
                        });
                    }
                };
            } else {
                if (titleEl) titleEl.textContent = "Émettre un nouveau chéquier";
                // Generate unique number with timestamp: CHQ-YYYYMMDDHHmmss-XXX
                const now = new Date();
                const timestamp = now.getFullYear() + 
                                  String(now.getMonth() + 1).padStart(2, '0') + 
                                  String(now.getDate()).padStart(2, '0') + 
                                  String(now.getHours()).padStart(2, '0') + 
                                  String(now.getMinutes()).padStart(2, '0') + 
                                  String(now.getSeconds()).padStart(2, '0');
                const randomPart = String(Math.floor(Math.random() * 1000)).padStart(3, '0');
                const num = 'CHQ-' + timestamp + '-' + randomPart;
                document.getElementById('input_numero_chequier').value = num;
                document.getElementById('modal_nb_feuilles').value = "25";
                document.getElementById('modal_date_creation').value = new Date().toISOString().split('T')[0];
                document.getElementById('modal_date_exp').value = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0];
                document.getElementById('modal_statut').value = "actif";
                btnModifier.style.display = 'none';
                btnEmettre.style.display = 'block';
                btnDelete.style.display = 'none';
            }
            updatePremiumPreview();
        }

        function closePremiumModal() {
            document.getElementById('premiumChequeModal').classList.remove('active');
        }

        function updatePremiumPreview() {
            document.getElementById('disp_chequier_num_preview').textContent = document.getElementById('input_numero_chequier').value || 'CHQ-2026-XXXXX';
            document.getElementById('preview_nb_feuilles').textContent = document.getElementById('modal_nb_feuilles').value;
            
            const createVal = document.getElementById('modal_date_creation').value;
            const expVal = document.getElementById('modal_date_exp').value;
            
            document.getElementById('preview_create_date').textContent = createVal ? new Date(createVal).toLocaleDateString('fr-FR') : '—';
            document.getElementById('preview_exp_date').textContent = expVal ? new Date(expVal).toLocaleDateString('fr-FR') : '—';
        }

        function submitChequerForm(event) {
            event.preventDefault();
            
            const id_demande = document.getElementById('hidden_id_demande_modal').value;
            const id_Compte = document.getElementById('hidden_id_compte_modal').value;
            const id_chequier = document.getElementById('hidden_id_chequier_modal').value;
            const numero_chequier = document.getElementById('input_numero_chequier').value;
            const date_creation = document.getElementById('modal_date_creation').value;
            const date_expiration = document.getElementById('modal_date_exp').value;
            const statut_chequier = document.getElementById('modal_statut').value;
            const nombre_feuilles = document.getElementById('modal_nb_feuilles').value;
            
            if (!id_demande) {
                showMessage('❌ ERREUR: ID demande manquant!', 'error');
                return;
            }
            if (!id_Compte) {
                showMessage('❌ ERREUR: ID compte manquant!', 'error');
                return;
            }
            
            // Determine if creating or modifying
            const isModifying = !!id_chequier;
            const formData = new FormData();
            
            if (isModifying) {
                formData.append('modifier_chequier', '1');
                formData.append('id_chequier', id_chequier);
            } else {
                formData.append('creer_chequier', '1');
            }
            
            formData.append('id_demande', id_demande);
            formData.append('id_Compte', id_Compte);
            formData.append('numero_chequier', numero_chequier);
            formData.append('date_creation', date_creation);
            formData.append('date_expiration', date_expiration);
            formData.append('statut_chequier', statut_chequier);
            formData.append('nombre_feuilles', nombre_feuilles);
            
            console.log('📤 SUBMITTING FORM:', {
                isModifying,
                id_demande,
                id_Compte,
                numero_chequier,
                date_creation,
                date_expiration,
                statut_chequier,
                nombre_feuilles
            });
            
            fetch('backoffice_chequier.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async response => {
                const payload = await response.json().catch(() => null);

                if (!payload) {
                    throw new Error('Réponse serveur invalide');
                }

                if (!response.ok || payload.success === false) {
                    const details = payload.details ? ' (' + payload.details + ')' : '';
                    throw new Error((payload.message || 'Erreur serveur') + details);
                }

                return payload;
            })
            .then(payload => {
                console.log('✅ RESPONSE RECEIVED:', payload);
                alert(payload.message || ('Chéquier ' + (isModifying ? 'modifié' : 'créé') + ' avec succès!'));
                closePremiumModal();
                location.reload();
            })
            .catch(error => {
                console.error('❌ FETCH ERROR:', error);
                alert('Erreur réseau: ' + error.message);
            });
        }

        function showMessage(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 6px;
                font-size: 0.95rem;
                font-weight: 500;
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-width: 500px;
            `;
            
            if (type === 'error') {
                notification.style.background = '#fee2e2';
                notification.style.color = '#991b1b';
                notification.style.borderLeft = '4px solid #dc2626';
            } else if (type === 'success') {
                notification.style.background = '#dcfce7';
                notification.style.color = '#166534';
                notification.style.borderLeft = '4px solid #16a34a';
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        document.getElementById('premiumModalCloseBtn').onclick = closePremiumModal;
        window.onclick = (e) => { if(e.target == document.getElementById('premiumChequeModal')) closePremiumModal(); };

        const serverFlashMessage = <?= json_encode($flashMessage, JSON_UNESCAPED_UNICODE) ?>;
        if (serverFlashMessage) {
            window.setTimeout(() => {
                alert(serverFlashMessage);
            }, 100);
        }
    </script>
</body>
</html>

