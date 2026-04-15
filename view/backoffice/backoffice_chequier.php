
<?php
require_once '../../config/config.php';
require_once '../../controller/demandechequiercontroller.php';

$demandeC = new DemandeChequierController();

// Traitement des actions Accepter / Refuser
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if ($action === 'accepter') {
        $demandeC->updateStatus($id, 'Acceptée');
    } elseif ($action === 'refuser') {
        $demandeC->updateStatus($id, 'Refusée');
    }
    header('Location: backoffice_chequier.php');
    exit();
}

$demandes_db = $demandeC->listDemandes();

// Calcul des statistiques réelles basées sur le statut
$en_attente_count = 0;
$refusees_count = 0;
$acceptees_count = 0;
foreach($demandes_db as $d) {
    $s = $d['statut'] ?? 'En attente';
    if($s === 'En attente') $en_attente_count++;
    if($s === 'Refusée') $refusees_count++;
    if($s === 'Acceptée') $acceptees_count++;
}

$stats = [
    'en_attente' => $en_attente_count, 
    'actifs' => $acceptees_count,
    'refusees' => $refusees_count
]; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin Admin — Gestion des Chéquiers</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="chequier.css">
</head>
<body>

<!-- SIDEBAR -->
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
    <div class="nav-section">Gestion</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/><path d="M14 3v4a1 1 0 001 1h4"/></svg>
      Chéquiers
      <span class="nav-badge"><?= (int)$stats['en_attente'] ?></span>
    </a>
    <a class="nav-item" href="../frontoffice/frontoffice_chequier.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      Frontoffice
    </a>
  </nav>
  <div class="sb-footer">
    <div class="sb-status"><span class="status-dot"></span>Système opérationnel</div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des chéquiers</div>
      <div class="breadcrumb">/ Demandes &amp; Chéquiers / Détail</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input placeholder="Rechercher n° chéquier, client..."/>
      </div>
      <button class="btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Émettre un chéquier
      </button>
    </div>
  </div>

  <div class="content">

    <!-- KPIs -->
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

    <!-- TABS + FILTERS -->
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab(this,'demandes')">Demandes de chéquier</button>
        <button class="tab-btn" onclick="switchTab(this,'chequier')">Chéquiers émis</button>
      </div>
      <div class="filters">
        <button class="filter-btn active" onclick="filterByStatus('tous')">Tous</button>
        <button class="filter-btn" onclick="filterByStatus('en-attente')">En attente</button>
        <button class="filter-btn" onclick="filterByStatus('acceptee')">Acceptées</button>
        <button class="filter-btn" onclick="filterByStatus('refusee')">Refusées</button>
      </div>
    </div>

    <!-- TABLE + DETAIL PANEL -->
    <div class="two-col-layout">

      <!-- TABLE DEMANDES -->
      <div id="tabDemandes" class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Demandes de chéquier <span style="font-size:.72rem;color:var(--muted);font-weight:400;margin-left:.4rem;">5 demandes</span></div>
        </div>

        <!-- ✦ WRAPPER SCROLL -->
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
                  <button class="act-btn" title="Voir"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>

          </tbody>
        </table>
        </div>
        <!-- FIN WRAPPER SCROLL -->

      </div>

      <!-- DETAIL PANEL -->
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

        <!-- PANEL DEMANDE -->
        <div id="panelDemande">
          <div class="dp-section" id="dpSectionTitle">Détails de la demande</div>
          <div class="dp-row"><span class="dp-key">Date demande</span><span class="dp-val" id="dpDate">—</span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span id="dpStatutBadge">—</span></div>
          <div class="dp-row"><span class="dp-key">Type chéquier</span><span id="dpTypeBadge">—</span></div>
          <div class="dp-row"><span class="dp-key">Nombre chèques</span><span class="dp-val" id="dpNb">—</span></div>
          <div class="dp-row"><span class="dp-key">Montant max/chèque</span><span class="dp-val" style="font-family:var(--fm)" id="dpMontant">—</span></div>
          <div class="dp-row"><span class="dp-key">Mode réception</span><span class="dp-val" id="dpMode">—</span></div>
          <div class="dp-row"><span class="dp-key">Adresse agence</span><span class="dp-val" style="font-size:.72rem;text-align:right;max-width:160px;" id="dpAdresse">—</span></div>
          <div class="dp-row"><span class="dp-key">Téléphone</span><span class="dp-val" style="font-family:var(--fm)" id="dpTel">—</span></div>
          <div class="dp-row"><span class="dp-key">Email</span><span class="dp-val" style="font-size:.72rem" id="dpEmail">—</span></div>
          <div class="dp-row"><span class="dp-key">Motif</span><span class="dp-val" style="font-size:.72rem;text-align:right;max-width:160px;" id="dpMotif">—</span></div>
          <div class="dp-row"><span class="dp-key">Compte lié</span><span class="dp-val-mono" id="dpIban">—</span></div>
        </div>

        <!-- PANEL CHEQUIER -->
        <div id="panelChequier" style="display:none">
          <div class="dp-section">Chéquier à émettre</div>
          <div class="chq-mini">
            <div class="chq-mini-num" id="miniChqId"></div>
            <div class="chq-mini-bottom">
              <div>
                <div class="chq-mini-name">Mouna Ncib</div>
                <div style="font-size:.55rem;opacity:.45;margin-top:2px;">Émission: 09/04/2024</div>
              </div>
              <div style="text-align:right">
                <div class="chq-mini-feuilles">25</div>
                <div class="chq-mini-label">feuilles</div>
              </div>
            </div>
          </div>
          <div class="dp-row"><span class="dp-key">Nb. feuilles</span><span class="dp-val">25 feuilles</span></div>
          <div class="dp-row"><span class="dp-key">Date expiration</span><span class="dp-val">09 avr. 2026</span></div>
          <div class="dp-row"><span class="dp-key">Statut à émettre</span><span class="badge b-attente" style="font-size:.65rem"><span class="badge-dot" style="background:var(--amber)"></span>Actif (dès accept.)</span></div>
        </div>

        <!-- PANEL HISTORIQUE -->
        <div id="panelHistorique" style="display:none">
          <div class="dp-section">Historique des demandes</div>
          <div class="history-list">
            <div class="hist-item">
              <div class="hist-dot" style="background:var(--amber)"></div>
              <div>
                <div class="hist-text">Demande soumise</div>
                <div class="hist-date">09 avr. 2024, 10:42</div>
              </div>
            </div>
            <div class="hist-item">
              <div class="hist-dot" style="background:var(--muted2)"></div>
              <div>
                <div class="hist-text">En cours de traitement</div>
                <div class="hist-date">09 avr. 2024, 11:05</div>
              </div>
            </div>
          </div>
        </div>

        <div class="dp-actions">
          <button class="dp-action-btn da-success">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Accepter la demande
          </button>
          <button class="dp-action-btn da-danger">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Refuser la demande
          </button>
          <button class="dp-action-btn da-neutral">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
            Générer attestation
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function genChqId() {
  const year = new Date().getFullYear();
  const rand = String(Math.floor(Math.random() * 99999) + 1).padStart(5, '0');
  return 'CHQ-' + year + '-' + rand;
}

document.querySelectorAll('.chq-id-val').forEach(el => {
  const year = new Date().getFullYear();
  const rand = String(Math.floor(Math.random() * 99999) + 1).padStart(5, '0');
  el.textContent = year + '-' + rand;
});

(function() {
  const id = genChqId();
  const panelEl = document.getElementById('panelChqId');
  const miniEl  = document.getElementById('miniChqId');
  if (panelEl) panelEl.textContent = id;
  if (miniEl)  miniEl.textContent  = id;
})();

function switchTab(btn, tab) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function switchPanel(btn, panelId) {
  document.querySelectorAll('.panel-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['panelDemande','panelChequier','panelHistorique'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = id === panelId ? '' : 'none';
  });
}

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    
    const filterText = this.textContent.trim().toLowerCase();
    const rows = document.querySelectorAll('#tabDemandes tbody tr');
    
    rows.forEach(row => {
      const statusBadge = row.querySelector('td:nth-child(7)'); // Colonne statut
      if (statusBadge) {
        const statusText = statusBadge.textContent.trim().toLowerCase();
        let show = false;
        
        if (filterText === 'tous') {
          show = true;
        } else if (filterText === 'en attente' && statusText.includes('attente')) {
          show = true;
        } else if (filterText === 'acceptées' && statusText.includes('acceptée')) {
          show = true;
        } else if (filterText === 'refusées' && statusText.includes('refusée')) {
          show = true;
        }
        
        row.style.display = show ? '' : 'none';
      }
    });
  });
});
// --- NOUVEAU CODE POUR L'AFFICHAGE DU DÉTAIL ---
function showDetail(row) {
  // Retirer la sélection précédente
  document.querySelectorAll('#tabDemandes tbody tr').forEach(r => r.classList.remove('row-selected'));
  row.classList.add('row-selected');

  // Extraire les données des attributs data-*
  const id = row.getAttribute('data-id');
  const name = row.getAttribute('data-name');
  const iban = row.getAttribute('data-iban');
  const date = row.getAttribute('data-date');
  const type = row.getAttribute('data-type');
  const nb = row.getAttribute('data-nb');
  const montant = row.getAttribute('data-montant');
  const mode = row.getAttribute('data-mode');
  const adresse = row.getAttribute('data-adresse');
  const tel = row.getAttribute('data-tel');
  const email = row.getAttribute('data-email');
  const motif = row.getAttribute('data-motif');
  const statut = row.getAttribute('data-statut');

  // Mise à jour de l'en-tête du panel
  const nameEl = document.querySelector('.dp-name');
  if(nameEl) nameEl.textContent = name;
  
  // Initiales
  const parts = name.split(' ');
  let init = parts[0] ? parts[0][0] : '';
  if (parts.length > 1 && parts[1]) init += parts[1][0];
  const avEl = document.querySelector('.dp-av');
  if(avEl) avEl.textContent = init.toUpperCase();

  // Titre section
  const sectionTitle = document.getElementById('dpSectionTitle');
  if(sectionTitle) sectionTitle.textContent = 'Demande — DEM-' + id;

  // Remplissage des champs simples
  if(document.getElementById('dpDate')) document.getElementById('dpDate').textContent = date;
  if(document.getElementById('dpNb')) document.getElementById('dpNb').textContent = nb + ' chèques';
  if(document.getElementById('dpMontant')) document.getElementById('dpMontant').textContent = parseFloat(montant).toLocaleString() + ' TND';
  if(document.getElementById('dpMode')) document.getElementById('dpMode').textContent = mode;
  if(document.getElementById('dpAdresse')) document.getElementById('dpAdresse').textContent = adresse;
  if(document.getElementById('dpTel')) document.getElementById('dpTel').textContent = tel;
  if(document.getElementById('dpEmail')) document.getElementById('dpEmail').textContent = email;
  if(document.getElementById('dpMotif')) document.getElementById('dpMotif').textContent = motif;
  if(document.getElementById('dpIban')) document.getElementById('dpIban').textContent = iban;

  // Gestion des badges (Statut)
  const badgeStatut = document.getElementById('dpStatutBadge');
  if(badgeStatut) {
    let bClass = 'b-attente';
    let dotColor = 'var(--amber)';
    const s = statut.toLowerCase();
    if(s.includes('acceptee') || s.includes('acceptée')) { bClass = 'b-acceptee'; dotColor = 'var(--green)'; }
    if(s.includes('refusee') || s.includes('refusée')) { bClass = 'b-refusee'; dotColor = 'var(--rose)'; }
    badgeStatut.innerHTML = `<span class="badge ${bClass}" style="font-size:.65rem"><span class="badge-dot" style="background:${dotColor}"></span>${statut}</span>`;
  }

  // Gestion des badges (Type)
  const badgeType = document.getElementById('dpTypeBadge');
  if(badgeType) {
    const isUrgent = type.toLowerCase() === 'urgent';
    badgeType.innerHTML = `<span class="badge ${isUrgent ? 'b-urgent' : 'b-standard'}" style="font-size:.65rem">${type}</span>`;
  }

  // Activation des boutons d'action
  const btnAccept = document.querySelector('.da-success');
  const btnRefuse = document.querySelector('.da-danger');
  const statusKey = row.getAttribute('data-status');

  if (btnAccept && btnRefuse) {
    if (statusKey === 'acceptee' || statusKey === 'refusee') {
      btnAccept.style.display = 'none';
      btnRefuse.style.display = 'none';
    } else {
      btnAccept.style.display = '';
      btnRefuse.style.display = '';
      btnAccept.setAttribute('onclick', `if(confirm('Accepter cette demande ?')) window.location.href='?action=accepter&id=${id}'`);
      btnRefuse.setAttribute('onclick', `if(confirm('Refuser cette demande ?')) window.location.href='?action=refuser&id=${id}'`);
    }
  }
}

function filterByStatus(status) {
  const rows = document.querySelectorAll('#demandesTableBody tr');
  const buttons = document.querySelectorAll('.filter-btn');
  
  // Mettre à jour les boutons visuellement
  buttons.forEach(btn => {
    btn.classList.remove('active');
    if(status === 'tous' && btn.textContent === 'Tous') btn.classList.add('active');
    if(status === 'en-attente' && btn.textContent === 'En attente') btn.classList.add('active');
    if(status === 'acceptee' && btn.textContent === 'Acceptées') btn.classList.add('active');
    if(status === 'refusee' && btn.textContent === 'Refusées') btn.classList.add('active');
  });

  // Filtrer les lignes
  rows.forEach(row => {
    const rowStatus = row.getAttribute('data-status');
    if (status === 'tous' || rowStatus === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  // Mettre à jour le compteur affiché à côté du titre "Demandes de chéquier"
  const titleCounter = document.querySelector('.table-header .section-title');
  if(titleCounter) {
    const visibleRows = Array.from(rows).filter(r => r.style.display !== 'none').length;
    // On met à jour le texte après le titre
    const span = titleCounter.querySelector('span') || document.createElement('span');
    span.style.fontSize = '0.75rem';
    span.style.color = 'var(--muted)';
    span.style.marginLeft = '10px';
    span.style.fontWeight = '400';
    span.textContent = visibleRows + ' demandes';
    if(!titleCounter.querySelector('span')) titleCounter.appendChild(span);
  }
}

// Attacher l'événement à toutes les lignes pour le détail
document.querySelectorAll('#demandesTableBody tr').forEach(row => {
  row.addEventListener('click', function() {
    showDetail(this);
  });
});
</script>
</body>
</html>