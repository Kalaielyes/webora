<?php
session_start();

require_once __DIR__ . '/../../model/config.php';
require_once '../../controller/demandechequiercontroller.php';
require_once '../../controller/chequiercontroller.php';
require_once '../../model/chequier.php';

$demandeC = new DemandeChequierController();
$chequierC = new ChequierController();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_chequier'])) {
    $id_demande = (int)$_POST['id_demande'];
    $id_Compte = (int)$_POST['id_Compte'];
    
    $data = [
        'numero_chequier' => $_POST['numero_chequier'],
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
            header('Location: backoffice_chequier.php?success=1');
            exit();
        }
    } catch (Exception $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_chequier'])) {
    $id_chequier = (int)$_POST['id_chequier'];
    $data = [
        'id_chequier' => $id_chequier,
        'numero_chequier' => $_POST['numero_chequier'],
        'date_expiration' => $_POST['date_expiration'],
        'statut' => $_POST['statut_chequier'],
        'nombre_feuilles' => (int)$_POST['nombre_feuilles']
    ];
    $chequier = new Chequier($data);
    try {
        if ($chequierC->updateChequier($chequier)) {
            header('Location: backoffice_chequier.php?success=1&action_type=edit');
            exit();
        }
    } catch (Exception $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    if ($action === 'accepter') {
    } elseif ($action === 'refuser') {
        $demandeC->updateStatus($id, 'Refusée');
        header('Location: backoffice_chequier.php?success=0&action_type=refus');
        exit();
    } elseif ($action === 'delete_chq') {
        $chq = $chequierC->getChequierById($id);
        if ($chq) {
            $id_demande_reset = $chq['id_demande'];
            if ($chequierC->deleteChequier($id)) {
                $demandeC->updateStatus($id_demande_reset, 'En attente');
                header('Location: backoffice_chequier.php?success=1&action_type=delete');
                exit();
            }
        }
    }
}

$demandes_db = $demandeC->listDemandes();
$chequiers_db = $chequierC->listChequiers();
$stats = [
    'en_attente' => 0, 
    'actifs' => 0,
    'refusees' => 0
];
foreach($demandes_db as $d) {
    $s = $d['statut'] ?? 'En attente';
    if($s === 'En attente') $stats['en_attente']++;
    if($s === 'Refusée') $stats['refusees']++;
    if($s === 'Acceptée') $stats['actifs']++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin Admin — Gestion des Chéquiers</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap');
</style>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="chequier.css?v=<?= time() ?>">
<script src="../frontoffice/chequier.js?v=<?= time() ?>"></script>
</head>
<body>
<?php if (isset($_GET['success'])): ?>
<div class="toast active" id="successToast">
  <div class="toast-icon">
    <i class="fa-solid fa-check"></i>
  </div>
  <div class="toast-msg">
    <?php 
      if($_GET['success'] == '1') {
        if(isset($_GET['action_type'])) {
            if($_GET['action_type'] == 'edit') echo "Chéquier mis à jour avec succès !";
            else if($_GET['action_type'] == 'delete') echo "Chéquier supprimé avec succès !";
            else echo "Chéquier émis avec succès !";
        } else {
            echo "Chéquier émis avec succès !";
        }
      }
      else if(isset($_GET['action_type']) && $_GET['action_type'] == 'refus') echo "Demande refusée avec succès.";
      else echo "Opération réussie !";
    ?>
  </div>
  <div class="toast-close" onclick="closeToast()"><i class="fa-solid fa-xmark"></i></div>
</div>
<?php endif; ?>

<?php 
$current_view = $_GET['view'] ?? 'backoffice'; 
?>
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">BACK OFFICE</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av">MN</div>
    <div>
      <div class="sb-aname">Mouna Ncib</div>
      <div class="sb-arole">Agent bancaire</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">MON COMPTE</div>
    
    <a class="nav-item <?= $current_view === 'dashboard' ? 'active' : '' ?>" href="backoffice_chequier.php?view=dashboard">
      <i class="fa-solid fa-house" style="width:16px; margin-right:4px;"></i>
      Tableau de bord
    </a>

    <a class="nav-item <?= $current_view === 'mes_chequiers' ? 'active' : '' ?>" href="backoffice_chequier.php?view=mes_chequiers">
      <i class="fa-solid fa-paste" style="width:16px; margin-right:4px;"></i>
      Mes chéquiers
      <span class="nav-badge" style="background:rgba(220,38,38,0.25); color:#FCA5A5; min-width:18px; height:18px; display:flex; align-items:center; justify-content:center; border-radius:50%; padding:0; margin-left:auto; font-size:0.65rem;"><?= count($chequiers_db) ?></span>
    </a>

    <a class="nav-item <?= ($current_view === 'backoffice' || $current_view === '') ? 'active' : '' ?>" href="backoffice_chequier.php?view=backoffice">
      <i class="fa-solid fa-credit-card" style="width:16px; margin-right:4px;"></i>
      backoffice
      <span class="nav-badge"><?= (int)$stats['en_attente'] ?></span>
    </a>
    
    <div class="nav-section" style="margin-top:1rem;">Actions Rapides</div>
    <a class="nav-item" href="../frontoffice/frontoffice_chequier.php">
      <i class="fa-solid fa-arrow-right-to-bracket" style="width:16px; margin-right:4px;"></i>
      Aller au Frontoffice
    </a>
    <a class="nav-item" href="statistiques.php">
      <i class="fa-solid fa-chart-bar" style="width:16px; margin-right:4px;"></i>
      Statistique
    </a>
  </nav>
  <div class="sb-footer">
    <div class="sb-status"><span class="status-dot"></span>Système opérationnel</div>
  </div>
</div>


<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title"><?= $current_view === 'mes_chequiers' ? 'Mes chéquiers' : 'Gestion des chéquiers' ?></div>
      <div class="breadcrumb">/ <?= $current_view === 'mes_chequiers' ? 'Client' : 'Admin' ?> / Chéquiers / <?= ucfirst($current_view) ?></div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input id="searchInput" oninput="updateSearch()" placeholder="Rechercher n° chéquier, client..."/>
      </div>
      <button class="btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Émettre un chéquier
      </button>
    </div>
  </div>

  <div class="content">
    <?php if ($current_view === 'mes_chequiers'): ?>
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
        <div class="chq-card-bo" data-name="<?= htmlspecialchars($chq['nom et prenom'] ?? 'Inconnu') ?>" data-num="<?= htmlspecialchars($chq['numero_chequier']) ?>" style="background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; display:flex; flex-direction:column; transition:transform 0.2s, box-shadow 0.2s; cursor:default;">
           <div class="bo-chq-visual" style="height:140px; background:linear-gradient(135deg, <?= $isActif ? '#0f172a, #1e293b' : '#450a0a, #7f1d1d' ?>); padding:1.2rem; color:white; position:relative; overflow:hidden;">
              <div style="font-family:var(--fm); font-size:0.75rem; opacity:0.6; letter-spacing:0.1em;"><?= htmlspecialchars($chq['numero_chequier']) ?></div>
              <div style="position:absolute; bottom:1.2rem; left:1.2rem;">
                  <div style="font-size:0.6rem; text-transform:uppercase; opacity:0.5; margin-bottom:2px;">Détenteur</div>
                  <div style="font-weight:600; font-size:0.85rem; letter-spacing:0.02em;"><?= htmlspecialchars($chq['nom et prenom'] ?? 'Inconnu') ?></div>
              </div>
              <div style="position:absolute; bottom:1.2rem; right:1.2rem; text-align:right;">
                  <div style="font-size:1.2rem; font-weight:800; opacity:0.9;"><?= htmlspecialchars($chq['nombre_feuilles']) ?></div>
                  <div style="font-size:0.55rem; text-transform:uppercase; opacity:0.5;">Feuilles</div>
              </div>
              <div style="position:absolute; top:-20px; right:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.03);"></div>
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
                      'idCompte' => $chq['id_Compte'],
                      'name' => $chq['nom et prenom'] ?? 'Inconnu',
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
      <!-- VIEW: DASHBOARD / BACKOFFICE (KPIs + Table) -->
      <div class="kpi-row">
      <div class="kpi" onclick="filterByStatus('en-attente')" style="cursor:pointer">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val"><?= (int)$stats['en_attente'] ?></div>
          <div class="kpi-label">Demandes en attente</div>
          <div class="kpi-sub" style="color:var(--amber)">À traiter</div>
        </div>
      </div>
      <div class="kpi" onclick="filterByStatus('acceptee')" style="cursor:pointer">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val"><?= $stats['actifs'] ?></div>
          <div class="kpi-label">Chéquiers actifs</div>
          <div class="kpi-sub" style="color:var(--blue)">En circulation</div>
        </div>
      </div>
      <div class="kpi" onclick="filterByStatus('refusee')" style="cursor:pointer">
        <div class="kpi-icon" style="background:var(--rose-light)">
          <svg width="18" height="18" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val"><?= $stats['refusees'] ?></div>
          <div class="kpi-label">Chéquiers refusés</div>
          <div class="kpi-sub" style="color:var(--rose)">Demandes rejetées</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:#F1F5F9">
          <svg width="18" height="18" fill="none" stroke="#64748B" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Chéquiers expirés</div>
          <div class="kpi-sub" style="color:var(--muted)">Ce trimestre</div>
        </div>
      </div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between; margin-bottom: 1.5rem;">
      <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--slate-900);">Gestion des Chéquiers</h2>
      <div class="filters">
        <button class="filter-btn active" onclick="filterByStatus('tous')">Tous</button>
        <button class="filter-btn" onclick="filterByStatus('en-attente')">En attente</button>
        <button class="filter-btn" onclick="filterByStatus('acceptee')">Acceptées</button>
        <button class="filter-btn" onclick="filterByStatus('refusee')">Refusées</button>
      </div>
    </div>

    <div class="two-col-layout">
      <div id="tabDemandes" class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Demandes de chéquier <span style="font-size:.72rem;color:var(--muted);font-weight:400;margin-left:.4rem;"><?= count($demandes_db) ?> demandes</span></div>
        </div>
        <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Client / Compte</th>
              <th>N° Demande</th>
              <th>Date</th>
              <th>Type</th>
              <th>Chèques</th>
              <th>Réception</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="demandesTableBody">
             <?php foreach ($demandes_db as $dem): 
                $d = new DateTime($dem['date_demande']);
                $day = $d->format('d');
                $monthArr = ["janv.","févr.","mars","avr.","mai","juin","juil.","août","sept.","oct.","nov.","déc."];
                $month = $monthArr[(int)$d->format('m') - 1];
                $year = $d->format('Y');
                
                $statut = $dem['statut'] ?? 'En attente';
                $badgeStatut = 'b-attente';
                $dotColor = 'var(--amber)';
                if($statut === 'Acceptée') { $badgeStatut = 'b-acceptee'; $dotColor = 'var(--green)'; }
                if($statut === 'Refusée') { $badgeStatut = 'b-refusee'; $dotColor = 'var(--rose)'; }

                $isUrgent = $dem['type_chequier'] === 'urgent';
                $badgeType = $isUrgent ? 'b-urgent' : 'b-standard';
                $typeLabel = $isUrgent ? 'Urgent' : 'Standard';
                $recepLabel = $dem['mode_reception'] === 'agence' ? 'Agence' : 'Livraison';
             ?>
             <tr data-status="<?= strtolower(str_replace(['é', ' '], ['e', '-'], $statut)) ?>" 
                 data-id="<?= $dem['id_demande'] ?>"
                 data-id-compte="<?= $dem['id_compte'] ?>"
                 data-name="<?= htmlspecialchars($dem['nom et prenom']) ?>"
                 data-iban="<?= htmlspecialchars($dem['iban']) ?>"
                 data-date="<?= $day . ' ' . $month . ' ' . $year ?>"
                 data-type="<?= $typeLabel ?>"
                 data-nb="<?= htmlspecialchars($dem['nombre_cheques']) ?>"
                 data-montant="<?= htmlspecialchars($dem['montant_max_par_cheque']) ?>"
                 data-mode="<?= $recepLabel ?>"
                 data-adresse="<?= htmlspecialchars($dem['adresse_agence']) ?>"
                 data-tel="<?= htmlspecialchars($dem['telephone']) ?>"
                 data-email="<?= htmlspecialchars($dem['email']) ?>"
                 data-motif="<?= htmlspecialchars($dem['motif']) ?>"
                 data-statut="<?= $statut ?>">
              <td>
                <div class="td-name"><?= htmlspecialchars($dem['nom et prenom']) ?></div>
                <div class="td-mono"><?= htmlspecialchars($dem['iban']) ?></div>
              </td>
              <td class="td-mono">DEM-<?= $dem['id_demande'] ?></td>
              <td style="font-size:.78rem;"><?= $day ?><br><?= $month ?><br><?= $year ?></td>
              <td><span class="badge <?= $badgeType ?>"><?= $typeLabel ?></span></td>
              <td style="font-family:var(--fm);font-size:.78rem;"><?= htmlspecialchars($dem['nombre_cheques']) ?></td>
              <td style="font-size:.75rem;"><?= $recepLabel ?></td>
              <td><span class="badge <?= $badgeStatut ?>"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= $statut ?></span></td>
              <td>
                <div class="action-group">
                  <button class="act-btn" title="Voir" onclick="showDetail(this.closest('tr'))"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                  <button class="act-btn success" title="Accepter et Émettre" onclick="event.stopPropagation(); showDetail(this.closest('tr')); openPremiumModal(this.closest('tr').dataset)"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>

      <div class="detail-panel">
        <div class="dp-header">
          <div class="dp-av">MN</div>
          <div>
            <div class="dp-name">Sélectionner une demande</div>
            <div class="dp-kyc">Détails de la demande</div>
          </div>
        </div>

        <div class="panel-tabs">
          <button class="panel-tab active" onclick="switchPanel(this,'panelDemande')">Demande</button>
          <button class="panel-tab" onclick="switchPanel(this,'panelChequier')">Chéquier</button>
          <button class="panel-tab" onclick="switchPanel(this,'panelHistorique')">Historique</button>
        </div>

        <div id="panelDemande">
          <div class="dp-section" id="dpSectionTitle">Détails de la demande</div>
          <div class="dp-row"><span class="dp-key">Date demande</span><span class="dp-val" id="dpDate">—</span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="dp-val" id="dpStatutBadge">—</span></div>
          <div class="dp-row"><span class="dp-key">Type chéquier</span><span class="dp-val" id="dpTypeBadge">—</span></div>
          <div class="dp-row"><span class="dp-key">Nombre chèques</span><span class="dp-val" id="dpNb">—</span></div>
          <div class="dp-row"><span class="dp-key">Montant max/chèque</span><span class="dp-val" style="font-family:var(--fm)" id="dpMontant">—</span></div>
          <div class="dp-row"><span class="dp-key">Mode réception</span><span class="dp-val" id="dpMode">—</span></div>
          <div class="dp-row"><span class="dp-key">Adresse agence</span><span class="dp-val" id="dpAdresse">—</span></div>
          <div class="dp-row"><span class="dp-key">Téléphone</span><span class="dp-val" id="dpTel">—</span></div>
          <div class="dp-row"><span class="dp-key">Email</span><span class="dp-val" id="dpEmail">—</span></div>
          <div class="dp-row"><span class="dp-key">Motif</span><span class="dp-val" id="dpMotif">—</span></div>
          <div class="dp-row"><span class="dp-key">Compte lié</span><span class="dp-val-mono" id="dpIban">—</span></div>
        </div>

        <div id="panelChequier" style="display:none">
          <div class="dp-section">Saisie du Chéquier</div>
          
          <form method="POST" action="" id="formChequier" class="premium-form">
            <input type="hidden" name="id_demande" id="hidden_id_demande">
            <input type="hidden" name="id_Compte" id="hidden_id_compte">
            <input type="hidden" name="creer_chequier" value="1">

            <div class="chq-mini chq-visual-premium">
              <div class="chq-mini-num" id="prev_numero">CHQ-2026-XXXXX</div>
              <div class="chq-mini-bottom">
                <div>
                  <div class="chq-mini-name" id="prev_name">NOM DU CLIENT</div>
                  <div style="font-size:.55rem;opacity:.5;margin-top:2px;">Généré le: <?= date('d/m/Y') ?></div>
                </div>
                <div style="text-align:right">
                  <div class="chq-mini-feuilles" id="prev_feuilles">25</div>
                  <div class="chq-mini-label">feuilles</div>
                </div>
              </div>
            </div>

            <div class="panel-section-title">Attributs Automatiques <span>AUTO</span></div>
            <div class="attribute-group">
              <div class="attribute-field">
                <div class="attr-label-row"><span class="attr-label">Numéro Chéquier</span> <span class="attr-badge auto">Généré</span></div>
                <div class="attr-auto-val" id="disp_numero">CHQ-2026-XXXXX</div>
                <input type="hidden" name="numero_chequier" id="input_numero">
              </div>
              <div class="attribute-field">
                <div class="attr-label-row"><span class="attr-label">Date Création</span> <span class="attr-badge auto">Aujourd'hui</span></div>
                <div class="attr-auto-val"><?= date('d/m/Y') ?></div>
                <input type="hidden" name="date_creation" value="<?= date('Y-m-d') ?>">
              </div>
              <div class="attribute-field">
                <div class="attr-label-row"><span class="attr-label">Compte Lié (IBAN)</span> <span class="attr-badge auto">Verrouillé</span></div>
                <div class="attr-auto-val" id="disp_iban" style="font-size:.7rem">TN59 ...</div>
              </div>
            </div>

            <div class="panel-section-title" style="margin-top:0.8rem;">Paramètres à Configurer <span>MANUEL</span></div>
            <div class="attribute-group">
              <div class="attribute-field">
                <div class="attr-label-row"><span class="attr-label">Nombre de feuilles</span> <span class="attr-badge manual">Requis</span></div>
                <select name="nombre_feuilles" id="input_feuilles" class="attr-input" onchange="document.getElementById('prev_feuilles').textContent = this.value">
                  <option value="25">25 feuilles (Standard)</option>
                  <option value="50">50 feuilles (Professionnel)</option>
                  <option value="100">100 feuilles (Expert)</option>
                </select>
              </div>
              <div class="attribute-field">
                <div class="attr-label-row"><span class="attr-label">Date d'expiration</span> <span class="attr-badge manual">Modifiable</span></div>
                <input type="date" name="date_expiration" class="attr-input" value="<?= date('Y-m-d', strtotime('+2 years')) ?>">
              </div>
              <div class="attribute-field">
                <div class="attr-label-row"><span class="attr-label">Statut Initial</span> <span class="attr-badge manual">Défaut</span></div>
                <select name="statut_chequier" class="attr-input">
                  <option value="actif">✅ Actif</option>
                  <option value="bloque">🔒 Bloqué</option>
                  <option value="expire">⏳ Expiré</option>
                </select>
              </div>
            </div>

            <button type="submit" class="btn-primary premium-submit">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              Confirmer et Émettre
            </button>
          </form>
        </div>

        <div id="panelHistorique" style="display:none">
          <div class="dp-section">Historique</div>
        </div>

        <div class="dp-actions">
          <button class="dp-action-btn da-success" id="btnAccepter">Accepter la demande</button>
          <button class="dp-action-btn da-danger" id="btnRefuser">Refuser la demande</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function switchTab(btn, tabId) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const tabDemandes = document.getElementById('tabDemandes');
  const tabChequier = document.getElementById('tabChequier');
  if(tabId === 'demandes') {
    tabDemandes.style.display = 'block';
    tabChequier.style.display = 'none';
  } else {
    tabDemandes.style.display = 'none';
    tabChequier.style.display = 'block';
  }
}

function switchPanel(btn, panelId) {
  document.querySelectorAll('.panel-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['panelDemande','panelChequier','panelHistorique'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = id === panelId ? '' : 'none';
  });
}

let currentStatusFilter = 'tous';

function updateSearch() {
  applyFilters();
}

function filterByStatus(status) {
  currentStatusFilter = status;
  document.querySelectorAll('.filter-btn').forEach(btn => {
    const btnText = btn.textContent.toLowerCase();
    const statusText = status.replace('-', ' ').toLowerCase();
    if (btnText.includes(statusText) || (status === 'tous' && btnText === 'tous')) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  });
  applyFilters();
}

function applyFilters() {
  const searchInput = document.getElementById('searchInput');
  if (!searchInput) return;
  const query = searchInput.value.toLowerCase().trim();
  const tableBody = document.getElementById('demandesTableBody');
  if (tableBody) {
    const rows = tableBody.querySelectorAll('tr');
    rows.forEach(row => {
      const rowStatus = row.getAttribute('data-status') || '';
      const statusMatch = (currentStatusFilter === 'tous' || rowStatus === currentStatusFilter);
      const name = (row.getAttribute('data-name') || '').toLowerCase();
      const id = (row.getAttribute('data-id') || '').toLowerCase();
      const iban = (row.getAttribute('data-iban') || '').toLowerCase();
      const textContent = row.textContent.toLowerCase();
      const searchMatch = !query || name.includes(query) || id.includes(query) || iban.includes(query) || ("dem-" + id).includes(query) || textContent.includes(query);
      row.style.display = (statusMatch && searchMatch) ? '' : 'none';
    });
  }
  const cards = document.querySelectorAll('.chq-card-bo');
  if (cards.length > 0) {
    cards.forEach(card => {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const num = (card.getAttribute('data-num') || '').toLowerCase();
      const textContent = card.textContent.toLowerCase();
      const searchMatch = !query || name.includes(query) || num.includes(query) || textContent.includes(query);
      card.style.display = searchMatch ? '' : 'none';
    });
  }
}

function showDetail(row) {
  document.querySelectorAll('#demandesTableBody tr').forEach(r => r.classList.remove('row-selected'));
  row.classList.add('row-selected');
  const d = row.dataset;
  document.querySelector('.dp-name').textContent = d.name;
  document.getElementById('dpDate').textContent = d.date;
  document.getElementById('dpNb').textContent = d.nb;
  document.getElementById('dpMontant').textContent = d.montant;
  document.getElementById('dpMode').textContent = d.mode;
  document.getElementById('dpAdresse').textContent = d.adresse;
  document.getElementById('dpTel').textContent = d.tel;
  document.getElementById('dpEmail').textContent = d.email;
  document.getElementById('dpMotif').textContent = d.motif;
  document.getElementById('dpIban').textContent = d.iban;
  const st = d.statut || 'En attente';
  let bClass = 'b-attente';
  let dot = 'var(--amber)';
  if(st === 'Acceptée') { bClass = 'b-acceptee'; dot = 'var(--green)'; }
  if(st === 'Refusée') { bClass = 'b-refusee'; dot = 'var(--rose)'; }
  document.getElementById('dpStatutBadge').innerHTML = `<span class="badge ${bClass}"><span class="badge-dot" style="background:${dot}"></span>${st}</span>`;
  const ty = d.type || 'Standard';
  const tyClass = (ty === 'Urgent') ? 'b-urgent' : 'b-standard';
  document.getElementById('dpTypeBadge').innerHTML = `<span class="badge ${tyClass}">${ty}</span>`;
  document.getElementById('hidden_id_demande').value = d.id;
  document.getElementById('hidden_id_compte').value = d.idCompte;
  const num = 'CHQ-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 99999) + 1).padStart(5, '0');
  document.getElementById('input_numero').value = num;
  document.getElementById('disp_numero').textContent = num;
  document.getElementById('prev_numero').textContent = num;
  document.getElementById('prev_name').textContent = d.name;
  document.getElementById('disp_iban').textContent = d.iban;
  document.getElementById('input_feuilles').value = "25";
  document.getElementById('prev_feuilles').textContent = "25";
  document.getElementById('btnAccepter').onclick = () => { openPremiumModal(d); };
  document.getElementById('btnRefuser').onclick = () => { 
    if(confirm('Souhaitez-vous vraiment refuser cette demande ?')) {
      window.location.href='?action=refuser&id='+d.id;
    }
  };
}

function openPremiumModal(d) {
    const modal = document.getElementById('premiumChequeModal');
    modal.classList.remove('hidden');
    modal.classList.add('active');
    const chq = existingChequiers.find(c => parseInt(c.id_demande) === parseInt(d.id));
    const isEdit = !!chq;
    document.getElementById('hidden_id_demande_modal').value = d.id;
    document.getElementById('hidden_id_compte_modal').value = d.idCompte;
    document.getElementById('hidden_id_chequier_modal').value = isEdit ? chq.id_chequier : "";
    const btnE = document.getElementById('btnEmettreModal');
    const btnM = document.getElementById('btnModifierModal');
    const btnS = document.getElementById('btnSupprimerModal');
    btnE.style.display = 'inline-flex';
    if (isEdit) {
        btnM.style.display = 'inline-flex';
        btnS.style.display = 'inline-flex';
        btnS.href = "?action=delete_chq&id=" + chq.id_chequier;
    } else {
        btnM.style.display = 'none';
        btnS.style.display = 'none';
    }
    document.getElementById('preview_signature').textContent = d.name;
    document.getElementById('preview_iban').textContent = d.iban;
    document.getElementById('disp_id_demande_modal').textContent = 'DEM-' + d.id;
    if (isEdit) {
        document.getElementById('input_numero_chequier').value = chq.numero_chequier;
        document.getElementById('disp_chequier_num_preview').textContent = chq.numero_chequier;
        document.getElementById('modal_nb_feuilles').value = chq.nombre_feuilles;
        document.getElementById('modal_date_exp').value = chq.date_expiration;
        document.getElementById('modal_statut').value = chq.statut.toLowerCase().replace('é', 'e');
    } else {
        const num = 'CHQ-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 99999) + 1).padStart(5, '0');
        document.getElementById('input_numero_chequier').value = num;
        document.getElementById('disp_chequier_num_preview').textContent = num;
        document.getElementById('modal_nb_feuilles').value = "25";
        document.getElementById('modal_date_exp').value = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0];
        document.getElementById('modal_statut').value = "actif";
    }
    updatePremiumPreview();
}

const existingChequiers = <?= json_encode($chequiers_db) ?>;

function closePremiumModal() {
    const modal = document.getElementById('premiumChequeModal');
    modal.classList.remove('active');
    modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('premiumChequeModal');
    const closeBtn = document.getElementById('premiumModalCloseBtn');
    if(closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closePremiumModal();
        });
    }
    if(modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closePremiumModal();
        });
        modal.classList.add('hidden');
    }
});

function updatePremiumPreview() {
    const num = document.getElementById('input_numero_chequier').value || 'CHQ-XXXX-XXXXX';
    const sheets = document.getElementById('modal_nb_feuilles').value || '25';
    const expDate = document.getElementById('modal_date_exp').value || '—';
    const createDate = document.getElementById('modal_date_creation').value || '—';
    document.getElementById('disp_chequier_num_preview').textContent = num;
    document.getElementById('preview_nb_feuilles').textContent = sheets;
    document.getElementById('preview_exp_date').textContent = expDate;
    if(createDate !== '—') {
        const d = new Date(createDate);
        document.getElementById('preview_create_date').textContent = d.toLocaleDateString('fr-FR');
    }
}

function closeToast() {
  const t = document.getElementById('successToast');
  if(t) t.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', () => {
  const t = document.getElementById('successToast');
  if(t) setTimeout(closeToast, 5000);
});

document.querySelectorAll('#demandesTableBody tr').forEach(row => {
  row.addEventListener('click', () => showDetail(row));
});
</script>

<!-- PREMIUM ÉMISSION CHÈQUIER MODAL -->
<div class="modal-overlay" id="premiumChequeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; overflow-y:auto; align-items:center !important; justify-content:center !important; padding:2rem;">
    <div class="saisie-container" style="background: #ffffff !important; margin:auto !important; width:100%; max-width:900px; box-shadow: 0 0 100px rgba(0,0,0,0.5);">
        <div class="saisie-header">
            <div class="saisie-title">
                <h1>Émettre un nouveau chéquier</h1>
                <div class="saisie-subtitle">Demande associée : <span id="disp_id_demande_modal">DEM-Auto</span></div>
            </div>
            <button class="close-btn" id="premiumModalCloseBtn" title="Fermer" style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; border: none; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                <i class="fa-solid fa-xmark" style="font-size: 1.2rem;"></i>
            </button>
        </div>

        <div class="visual-check" style="height:220px; background:linear-gradient(135deg, #1e3a8a, #0f172a);">
            <div class="check-banner"></div>
            <div class="check-top">
                <div class="check-bank">
                    <div class="bank-logo" style="background:#fff; color:var(--blue); width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.2rem;">LF</div>
                    <div class="bank-info">
                        <h2 style="font-size:1rem; font-weight:700; color:#fff;">LegalFin Bank</h2>
                        <p style="font-size:0.65rem; color:rgba(255,255,255,0.6);">Chéquier Privilège — Service Premium</p>
                    </div>
                </div>
                <div class="check-meta" style="text-align:right; font-size:0.75rem; color:rgba(255,255,255,0.7); display:flex; flex-direction:column; gap:4px;">
                    <div style="font-family:var(--fm); font-size:0.9rem; color:#fff;" id="disp_chequier_num_preview">CHQ-2024-XXXXX</div>
                    <div>CRÉÉ LE : <span style="color:#fff;" id="preview_create_date"><?= date('d/m/Y') ?></span></div>
                    <div>EXPIRATION : <span id="preview_exp_date" style="color:#fff;">—</span></div>
                </div>
            </div>
            <div style="margin-top:2rem; display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    <div style="font-size:0.6rem; text-transform:uppercase; color:rgba(255,255,255,0.5); letter-spacing:0.1em;">Titulaire du compte</div>
                    <div style="font-family:'Dancing Script', cursive; font-size:1.6rem; color:#fff;" id="preview_signature">Nom du Client</div>
                    <div style="font-family:var(--fm); font-size:0.75rem; color:rgba(255,255,255,0.6); margin-top:4px;" id="preview_iban">TN59 1234 5678 9012 3456 7890</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-family:var(--fm); font-size:2rem; font-weight:700; color:#60A5FA; line-height:1;" id="preview_nb_feuilles">25</div>
                    <div style="font-size:0.6rem; text-transform:uppercase; color:rgba(255,255,255,0.5); letter-spacing:0.1em;">Feuilles standard</div>
                </div>
            </div>
        </div>

        <div class="premium-form-body">
            <div class="form-section-title">Informations du chéquier</div>
            <form method="POST" action="" onsubmit="return validerSaisieChequier()">
                <input type="hidden" name="id_demande" id="hidden_id_demande_modal">
                <input type="hidden" name="id_Compte" id="hidden_id_compte_modal">
                <input type="hidden" name="id_chequier" id="hidden_id_chequier_modal">
                <div class="form-grid">
                    <div class="form-group">
                        <div class="label-row"><label>N° Chéquier (Référence officielle)</label></div>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-hashtag input-icon"></i>
                            <input type="text" name="numero_chequier" id="input_numero_chequier" class="premium-input" placeholder="CHQ-2026-XXXXX">
                        </div>
                        <div id="errModalNum" class="error-msg" style="color:var(--rose); font-size:0.75rem; margin-top:4px; display:none;"></div>
                        <div class="input-hint" style="color:var(--blue)">• Vous pouvez utiliser le numéro généré ou le saisir manuellement.</div>
                    </div>
                    <div class="form-group">
                        <div class="label-row"><label>Date de création <span class="required-star">*</span></label></div>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-calendar-plus input-icon"></i>
                            <input type="date" name="date_creation" id="modal_date_creation" class="premium-input" value="<?= date('Y-m-d') ?>" oninput="updatePremiumPreview()" required>
                        </div>
                        <div id="errModalDateCreate" class="error-msg" style="color:var(--rose); font-size:0.75rem; margin-top:4px; display:none;"></div>
                        <div class="input-hint" style="color:#94a3b8">• Date d'émission officielle</div>
                    </div>
                    <div class="form-group">
                        <div class="label-row"><label>Nombre de feuilles <span class="required-star">*</span></label></div>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-layer-group input-icon"></i>
                            <select name="nombre_feuilles" id="modal_nb_feuilles" class="premium-input" onchange="updatePremiumPreview()">
                                <option value="25">25 feuilles (Standard)</option>
                                <option value="50">50 feuilles (Professionnel)</option>
                                <option value="100">100 feuilles (Grand Format)</option>
                            </select>
                        </div>
                        <div id="errModalSheets" class="error-msg" style="color:var(--rose); font-size:0.75rem; margin-top:4px; display:none;"></div>
                    </div>
                    <div class="form-group">
                        <div class="label-row"><label>Date d'expiration <span class="required-star">*</span></label></div>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-calendar-xmark input-icon"></i>
                            <input type="date" name="date_expiration" id="modal_date_exp" class="premium-input" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" oninput="updatePremiumPreview()" required>
                        </div>
                        <div id="errModalDateExp" class="error-msg" style="color:var(--rose); font-size:0.75rem; margin-top:4px; display:none;"></div>
                    </div>
                    <div class="form-group full">
                        <div class="label-row"><label>Statut Initial <span class="required-star">*</span></label></div>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <select name="statut_chequier" id="modal_statut" class="premium-input">
                                <option value="actif">Actif (Prêt à l'usage)</option>
                                <option value="bloque">Bloqué (En suspens)</option>
                                <option value="expire">Expiré (Ancien)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions" style="display:flex; justify-content:flex-end; gap:0.8rem; flex-wrap:wrap;">
                    <button type="button" class="btn-secondary" id="premiumModalCancelBtn" onclick="closePremiumModal()">Annuler</button>
                    <button type="submit" name="modifier_chequier" id="btnModifierModal" class="btn-submit" style="background:linear-gradient(135deg, #3b82f6, #2563eb); color:white; border:none; display:none;">
                        <i class="fa-solid fa-pen-to-square" style="margin-right:8px;"></i>Modifier
                    </button>
                    <a href="#" id="btnSupprimerModal" class="btn-submit" style="background:linear-gradient(135deg, #3b82f6, #2563eb); color:white; text-decoration:none; display:none; align-items:center;" onclick="return confirm('Souhaitez-vous vraiment supprimer ce chéquier ? Cela l\'effacera également de votre espace client.')">
                        <i class="fa-solid fa-trash-can" style="margin-right:8px;"></i>Supprimer
                    </a>
                    <button type="submit" name="creer_chequier" id="btnEmettreModal" class="btn-submit" style="background:linear-gradient(135deg, #3b82f6, #2563eb); color:white; border:none;">
                        <i class="fa-solid fa-plus" style="margin-right:8px;"></i>Émettre (Ajouter)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>