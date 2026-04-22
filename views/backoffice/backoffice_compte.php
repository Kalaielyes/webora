<?php
/**
 * backoffice_compte.php — ADMIN VIEW
 * • Table of all comptes with filter/search
 * • Detail panel: view, inline-edit compte, inline-edit carte, actions
 * • En attente tab: pending compte + carte requests
 */
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../controllers/CompteController.php';
require_once __DIR__ . '/../../controllers/CarteController.php';

Config::autoLogin();

$comptes     = CompteController::findAll();
$kpi_actifs  = CompteController::countByStatut('actif');
$kpi_attente = CompteController::countByStatut('en_attente');
$kpi_bloques = CompteController::countByStatut('bloque');
$kpi_solde   = CompteController::totalSolde();

$selected = null;
$cartes   = [];
if (!empty($_GET['id_compte'])) {
    $selected = CompteController::findById((int)$_GET['id_compte']);
    if ($selected) $cartes = CarteController::findByCompte($selected->getIdCompte());
}

$tab        = $_GET['tab'] ?? 'comptes';
$editCompte = (!empty($_GET['edit']) && $_GET['edit']==='compte' && $selected);
$editCarte  = (!empty($_GET['edit_carte']));
$carteEdit  = null;
if ($editCarte) {
    $carteEdit = CarteController::findById((int)$_GET['edit_carte']);
}

// Pending requests for "En attente" tab
$pendingComptes = array_filter($comptes, fn($r)=>in_array($r['statut'],['en_attente','demande_cloture','demande_suppression']));
$allCartes      = CarteController::findAll();
$pendingCartes  = array_filter($allCartes, fn($c)=>in_array($c->getStatut(),['inactive','demande_cloture','demande_suppression','demande_reactivation']));

// Helpers
function badgeCompte(string $s): string {
    $map=['actif'=>['b-actif','Actif'],'bloque'=>['b-bloque','Bloqué'],'en_attente'=>['b-attente','En attente'],'demande_cloture'=>['b-attente','Dem. clôture'],'demande_suppression'=>['b-attente','Dem. supp.'],'cloture'=>['b-cloture','Clôturé']];
    [$cls,$label]=$map[$s]??['b-cloture',ucfirst($s)];
    return "<span class=\"badge {$cls}\"><span class=\"badge-dot\"></span>{$label}</span>";
}
function badgeCarte(string $s): string {
    $map=['active'=>['b-actif','Active'],'inactive'=>['b-cloture','Inactive'],'bloquee'=>['b-bloque','Bloquée'],'expiree'=>['b-cloture','Expirée'],'demande_cloture'=>['b-attente','Dem. supp.'],'demande_blocage'=>['b-attente','Dem. blocage'],'demande_suppression'=>['b-attente','Dem. supp.'],'demande_reactivation'=>['b-attente','Dem. réactiv.']];
    [$cls,$label]=$map[$s]??['b-cloture',ucfirst($s)];
    return "<span class=\"badge {$cls}\"><span class=\"badge-dot\"></span>{$label}</span>";
}
function typeLabel(string $t): string {
    return match($t){
        'courant'=>'<span class="t-courant">Courant</span>',
        'epargne'=>'<span class="t-epargne">Épargne</span>',
        'professionnel'=>'<span class="t-pro">Pro</span>',
        'devise'=>'<span class="t-devise">Devise</span>',
        default=>htmlspecialchars($t),
    };
}
function cmClass(string $style, string $statut=''): string {
    if ($statut==='bloquee') return 'cm-bloque';
    return match($style){'gold'=>'cm-gold','platinum'=>'cm-platinum','titanium'=>'cm-titanium',default=>'cm-standard'};
}
function styleLabel(string $s): string {
    return match($s){'gold'=>'Gold','platinum'=>'Platinum','titanium'=>'Titanium',default=>'Classic'};
}

$adminInitials = strtoupper(substr($_SESSION['user']['prenom']??'A',0,1).substr($_SESSION['user']['nom']??'D',0,1));
$adminNom = htmlspecialchars(($_SESSION['user']['prenom']??'').' '.($_SESSION['user']['nom']??''));
$pendingTotal = count($pendingComptes)+count($pendingCartes);

// -- ERROR & DATA HANDLING FROM PHP POST --
$formErrors = $_SESSION['form_errors'] ?? [];
$formData   = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin Admin — Comptes</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/views/frontoffice/compte.css">
<link rel="stylesheet" href="<?= APP_URL ?>/views/backoffice/compte.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">BACK OFFICE</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av"><?= $adminInitials ?></div>
    <div>
      <div class="sb-aname"><?= $adminNom ?></div>
      <div class="sb-arole">Agent bancaire</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Gestion</div>
    <a class="nav-item <?= $tab==='comptes'?'active':'' ?>" href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      Comptes bancaires
    </a>
    <a class="nav-item <?= $tab==='attente'?'active':'' ?>" href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=attente">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
      En attente
      <?php if ($pendingTotal>0): ?>
      <span class="nav-badge"><?= $pendingTotal ?></span>
      <?php endif; ?>
    </a>
    <a class="nav-item <?= $tab==='stats'?'active':'' ?>" href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=stats">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
      Statistiques
    </a>
    <a class="nav-item <?= $showCompteForm?'active':'' ?>" href="../frontoffice/frontoffice_compte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>frontoffice
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
      <div class="page-title">
        <?= $tab==='attente' ? 'Demandes en attente' : 'Comptes bancaires' ?>
      </div>
      <div class="breadcrumb">/ <?= $tab==='attente' ? 'Validation' : ('Liste' . ($selected?' / #'.$selected->getIdCompte():'')) ?></div>
    </div>
    <div class="tb-right">
      <?php if ($tab==='comptes'): ?>
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input placeholder="Rechercher IBAN, nom…" id="search-input" oninput="applyFilters()"/>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="content">
  <?php if ($tab==='stats'): ?>
  <!-- ══ STATS TAB ════════════════════════════════ -->
  <style>
  .stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
  .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 1.5rem; }
  .stat-card-title { font-weight: 700; font-family: var(--fs); color: var(--text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; font-size: 1.1rem; }
  .stat-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: .9rem; }
  .stat-label { color: var(--muted); display:flex; align-items:center; gap:.4rem;font-weight:500; }
  .stat-val { font-weight: 700; color: var(--text); font-family: var(--fm); }
  .stat-bar-bg { height: 6px; background: var(--surface2); border-radius: 3px; overflow: hidden; margin-top: .4rem; }
  .stat-bar-fg { height: 100%; border-radius: 3px; }
  </style>

  <?php
    $statComptes = ['actif'=>0,'bloque'=>0,'en_attente'=>0,'cloture'=>0,'demandes'=>0];
    $totalComptes = count($comptes);
    foreach($comptes as $c) {
        $st = $c['statut'];
        if ($st === 'actif') $statComptes['actif']++;
        elseif ($st === 'bloque') $statComptes['bloque']++;
        elseif ($st === 'en_attente') $statComptes['en_attente']++;
        elseif ($st === 'cloture') $statComptes['cloture']++;
        elseif (in_array($st, ['demande_cloture', 'demande_suppression'])) $statComptes['demandes']++;
    }
    
    $statCartes = ['active'=>0,'inactive'=>0,'bloquee'=>0,'expiree'=>0,'demandes'=>0];
    $totalCartes = count($allCartes);
    foreach($allCartes as $ca) {
        $st = $ca->getStatut();
        if ($st === 'active') $statCartes['active']++;
        elseif ($st === 'inactive') $statCartes['inactive']++;
        elseif ($st === 'bloquee') $statCartes['bloquee']++;
        elseif ($st === 'expiree') $statCartes['expiree']++;
        elseif (in_array($st, ['demande_cloture', 'demande_blocage', 'demande_suppression'])) $statCartes['demandes']++;
    }
    
    $totalSoldeCourant = 0; $totalSoldeEpargne = 0; $totalSoldeDevise  = 0; $totalSoldePro     = 0;
    foreach($comptes as $c) {
        if ($c['statut'] !== 'cloture') {
            $val = (float)$c['solde'];
            if ($c['type_compte'] === 'courant') $totalSoldeCourant += $val;
            elseif ($c['type_compte'] === 'epargne') $totalSoldeEpargne += $val;
            elseif ($c['type_compte'] === 'devise') $totalSoldeDevise += $val;
            elseif ($c['type_compte'] === 'professionnel') $totalSoldePro += $val;
        }
    }
    $totalSoldeAll = $totalSoldeCourant + $totalSoldeEpargne + $totalSoldeDevise + $totalSoldePro;
    function pct($val, $total) { return $total > 0 ? round(($val/$total)*100) : 0; }
  ?>

  <div class="stats-grid">
    <!-- COMPTES -->
    <div class="stat-card">
      <div class="stat-card-title">
        <svg width="20" height="20" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
        Statut des comptes (<?= $totalComptes ?>)
      </div>
      <div class="chart-container" style="position:relative; height:250px; width:100%; display:flex; justify-content:center;">
        <canvas id="comptesChart"></canvas>
      </div>
    </div>

    <!-- CARTES -->
    <div class="stat-card">
      <div class="stat-card-title">
        <svg width="20" height="20" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        Statut des cartes (<?= $totalCartes ?>)
      </div>
      <div class="chart-container" style="position:relative; height:250px; width:100%; display:flex; justify-content:center;">
        <canvas id="cartesChart"></canvas>
      </div>
    </div>

    <!-- SOLDES (COURBE) -->
    <div class="stat-card">
      <div class="stat-card-title">
        <svg width="20" height="20" fill="none" stroke="#10B981" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Répartition des soldes (<?= number_format($totalSoldeAll, 2, ',', ' ') ?> TND)
      </div>
      <div class="chart-container" style="position:relative; height:250px; width:100%;">
        <canvas id="soldesChart"></canvas>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      // Data
      const compteData = [<?= $statComptes['actif'] ?>, <?= $statComptes['en_attente'] ?>, <?= $statComptes['bloque'] ?>, <?= $statComptes['cloture'] ?>];
      const carteData = [<?= $statCartes['active'] ?>, <?= $statCartes['inactive'] ?>, <?= $statCartes['bloquee'] ?>, <?= $statCartes['expiree'] ?>];
      const soldeData = [<?= $totalSoldeCourant ?>, <?= $totalSoldeEpargne ?>, <?= $totalSoldePro ?>, <?= $totalSoldeDevise ?>];
      
      const customColors = ['#14b8a6', '#f59e0b', '#f43f5e', '#cbd5e1'];

      // Chart 1: Comptes (Doughnut)
      new Chart(document.getElementById('comptesChart'), {
          type: 'doughnut',
          data: {
              labels: ['Actifs', 'En attente', 'Bloqués', 'Clôturés'],
              datasets: [{
                  data: compteData,
                  backgroundColor: customColors,
                  borderWidth: 0,
                  hoverOffset: 6,
                  borderRadius: 8,
                  spacing: 4
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { 
                 legend: { 
                     position: 'bottom', 
                     labels: { color: '#64748b', font: { family: 'inherit', size: 12 }, usePointStyle: true, pointStyle: 'circle', padding: 15 } 
                 } 
              },
              cutout: '82%'
          }
      });

      // Chart 2: Cartes (Doughnut)
      new Chart(document.getElementById('cartesChart'), {
          type: 'doughnut',
          data: {
              labels: ['Actives', 'Inactives', 'Bloquées', 'Expirées'],
              datasets: [{
                  data: carteData,
                  backgroundColor: customColors,
                  borderWidth: 0,
                  hoverOffset: 6,
                  borderRadius: 8,
                  spacing: 4
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { 
                 legend: { 
                     position: 'bottom', 
                     labels: { color: '#64748b', font: { family: 'inherit', size: 12 }, usePointStyle: true, pointStyle: 'circle', padding: 15 } 
                 } 
              },
              cutout: '82%'
          }
      });

      // Chart 3: Soldes (Line Chart - Courbe diagrame)
      new Chart(document.getElementById('soldesChart'), {
          type: 'line',
          data: {
              labels: ['Courant', 'Épargne', 'Professionnel', 'Devise'],
              datasets: [{
                  label: 'Solde total (TND)',
                  data: soldeData,
                  borderColor: '#06b6d4',
                  backgroundColor: 'rgba(6, 182, 212, 0.1)',
                  borderWidth: 3,
                  fill: true,
                  tension: 0.5,
                  pointBackgroundColor: '#0891b2',
                  pointBorderColor: '#fff',
                  pointBorderWidth: 2,
                  pointRadius: 5,
                  pointHoverRadius: 8
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { 
                  legend: { display: false } 
              },
              scales: {
                  y: { 
                      beginAtZero: true, 
                      grid: { color: '#f1f5f9', drawBorder: false }, 
                      ticks: { color: '#64748b', font: { family: 'inherit' } } 
                  },
                  x: { 
                      grid: { display: false }, 
                      ticks: { color: '#64748b', font: { family: 'inherit', weight: 600 } } 
                  }
              }
          }
      });
  });
  </script>
  <?php elseif ($tab==='attente'): ?>
  <!-- ══ EN ATTENTE TAB ════════════════════════════════ -->
  <?php if ($pendingTotal===0): ?>
  <div style="text-align:center;padding:4rem;color:var(--muted)">
    <svg width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;display:block;margin:0 auto 1rem"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
    <div style="font-size:.9rem">Aucune demande en attente — tout est à jour.</div>
  </div>
  <?php else: ?>

  <?php if (!empty($pendingComptes)): ?>
  <!-- Pending comptes -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Comptes en attente (<?= count($pendingComptes) ?>)</div>
    </div>
    <table>
      <thead><tr><th>Client</th><th>IBAN</th><th>Type</th><th>Statut</th><th>Ouverture</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($pendingComptes as $row): ?>
      <tr>
        <td>
          <div class="td-name"><?= htmlspecialchars($row['prenom'].' '.$row['nom']) ?></div>
          <div style="font-size:.65rem;color:var(--muted)"><?= htmlspecialchars($row['email']) ?></div>
        </td>
        <td class="td-iban"><?= htmlspecialchars($row['iban']) ?></td>
        <td><?= typeLabel($row['type_compte']) ?></td>
        <td><?= badgeCompte($row['statut']) ?></td>
        <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($row['date_ouverture']) ?></td>
        <td>
          <div class="action-group">
            <?php if ($row['statut']==='en_attente'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline">
              <input type="hidden" name="action" value="activer">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn success" title="Accepter">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Voulez-vous supprimer cette demande ?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn danger" title="Supprimer">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($row['statut']==='demande_cloture'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Accepter la clôture ?')">
              <input type="hidden" name="action" value="cloturer">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn success" title="Accepter clôture">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de clôture ?')">
              <input type="hidden" name="action" value="refuser_cloture">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn" title="Refuser clôture" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($row['statut']==='demande_suppression'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Accepter la suppression définitive ?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn success" title="Accepter suppression">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de suppression ?')">
              <input type="hidden" name="action" value="refuser_suppression">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn" title="Refuser suppression" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if (!empty($pendingCartes)): ?>
  <!-- Pending cartes -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Cartes en attente (<?= count($pendingCartes) ?>)</div>
    </div>
    <table>
      <thead><tr><th>Titulaire</th><th>Numéro</th><th>Type / Réseau</th><th>Statut</th><th>Emission</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($pendingCartes as $carte): ?>
      <tr>
        <td class="td-name"><?= htmlspecialchars($carte->getTitulaireNom()) ?></td>
        <td class="td-iban"><?= htmlspecialchars($carte->getNumeroCarte()) ?></td>
        <td><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> · <?= strtoupper($carte->getReseau()) ?></td>
        <td><?= badgeCarte($carte->getStatut()) ?></td>
        <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($carte->getDateEmission()) ?></td>
        <td>
          <div class="action-group">
            <?php if ($carte->getStatut()==='inactive'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline">
              <input type="hidden" name="action" value="activer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Voulez-vous supprimer cette demande ?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn danger" title="Supprimer">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
              </button>
            </form>
            <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $carte->getIdCompte() ?>" class="act-btn" title="Voir détail du compte">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            </a>
            <?php endif; ?>
            <?php if ($carte->getStatut()==='demande_blocage'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Accepter et bloquer cette carte ?')">
              <input type="hidden" name="action" value="bloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter blocage">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de blocage ?')">
              <input type="hidden" name="action" value="refuser_blocage">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn" title="Refuser blocage" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($carte->getStatut()==='demande_cloture' || $carte->getStatut()==='demande_suppression'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Accepter la suppression de cette carte ?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter suppression">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de suppression ?')">
              <input type="hidden" name="action" value="refuser_cloture">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn" title="Refuser suppression" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($carte->getStatut()==='demande_reactivation'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Réactiver cette carte ?')">
              <input type="hidden" name="action" value="activer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter réactivation">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Refuser la réactivation ?')">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="statut" value="expiree">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn" title="Refuser réactivation" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?php endif; // pendingTotal ?>

  <?php elseif ($editCarte && $carteEdit): ?>
  <!-- ══ EDIT CARTE PAGE ════════════════════════════════ -->
  <div class="section-head" style="margin-bottom:.5rem">
    <div class="page-title">Modifier la carte</div>
    <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $carteEdit->getIdCompte() ?>" class="btn-ghost">← Retour</a>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.5rem;max-width:600px">
    <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id_carte" value="<?= $carteEdit->getIdCarte() ?>">
      <input type="hidden" name="redirect_id_compte" value="<?= $carteEdit->getIdCompte() ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-field">
          <label>Numéro de carte</label>
          <input type="text" value="<?= htmlspecialchars($carteEdit->getNumeroCarte()) ?>" readonly>
        </div>
        <div class="form-field">
          <label>Titulaire</label>
          <input type="text" name="titulaire_nom" value="<?= htmlspecialchars($formData['titulaire_nom'] ?? $carteEdit->getTitulaireNom()) ?>">
          <?php if (isset($formErrors['titulaire_nom'])): ?>
            <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['titulaire_nom']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Type de carte</label>
          <select name="type_carte">
            <?php foreach (['debit'=>'Débit','credit'=>'Crédit','prepayee'=>'Prépayée'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $carteEdit->getTypeCarte()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Réseau</label>
          <select name="reseau">
            <?php foreach (['visa'=>'Visa','mastercard'=>'Mastercard','amex'=>'Amex'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $carteEdit->getReseau()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Statut</label>
          <select name="statut">
            <?php foreach (['active'=>'Active','inactive'=>'Inactive','bloquee'=>'Bloquée','expiree'=>'Expirée'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $carteEdit->getStatut()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Style</label>
          <select name="style">
            <?php foreach (['standard'=>'Standard','gold'=>'Gold','platinum'=>'Platinum','titanium'=>'Titanium'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $carteEdit->getStyle()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Date d'expiration</label>
          <input type="text" name="date_expiration" value="<?= htmlspecialchars(substr($formData['date_expiration'] ?? $carteEdit->getDateExpiration(), 0, 7)) ?>">
        </div>
        <div class="form-field">
          <label>Motif blocage</label>
          <input type="text" name="motif_blocage" value="<?= htmlspecialchars($carteEdit->getMotifBlocage()??'') ?>" placeholder="Laisser vide si aucun">
        </div>
        <div class="form-field">
          <label>Plafond paiement/jour (TND)</label>
          <input type="text" name="plafond_paiement_jour" value="<?= htmlspecialchars($formData['plafond_paiement_jour'] ?? $carteEdit->getPlafondPaiementJour()) ?>">
          <?php if (isset($formErrors['plafond_paiement_jour'])): ?>
            <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_paiement_jour']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Plafond retrait/jour (TND)</label>
          <input type="text" name="plafond_retrait_jour" value="<?= htmlspecialchars($formData['plafond_retrait_jour'] ?? $carteEdit->getPlafondRetraitJour()) ?>">
          <?php if (isset($formErrors['plafond_retrait_jour'])): ?>
            <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_retrait_jour']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-actions-row" style="margin-top:1.2rem;padding-top:1rem;border-top:1px solid var(--border)">
        <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $carteEdit->getIdCompte() ?>" class="btn-cancel">Annuler</a>
        <button type="submit" class="btn-save">Enregistrer</button>
      </div>
    </form>
  </div>

  <?php else: ?>
  <!-- ══ MAIN COMPTES TAB ═══════════════════════════════ -->

  <!-- KPIs -->
  <div class="kpi-row">
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--blue-light)">
        <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      </div>
      <div class="kpi-data"><div class="kpi-val"><?= $kpi_actifs ?></div><div class="kpi-label">Comptes actifs</div></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--amber-light)">
        <svg width="18" height="18" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/></svg>
      </div>
      <div class="kpi-data"><div class="kpi-val"><?= $kpi_attente ?></div><div class="kpi-label">En attente</div><?php if($kpi_attente>0):?><div class="kpi-sub" style="color:var(--amber)">À traiter</div><?php endif;?></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--rose-light)">
        <svg width="18" height="18" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <div class="kpi-data"><div class="kpi-val"><?= $kpi_bloques ?></div><div class="kpi-label">Bloqués</div></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--green-light)">
        <svg width="18" height="18" fill="none" stroke="#16A34A" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
      </div>
      <div class="kpi-data"><div class="kpi-val"><?= number_format($kpi_solde,0,'.',' ') ?></div><div class="kpi-label">Solde total (TND)</div></div>
    </div>
  </div>

  <!-- Table + Detail panel -->
  <div class="two-col-layout">

    <!-- TABLE -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="table-toolbar-title">Liste des comptes (<?= count($comptes) ?>)</div>
        <div class="filters">
          <button class="filter-btn active" onclick="setFilter('tous',this)">Tous</button>
          <button class="filter-btn" onclick="setFilter('actif',this)">Actifs</button>
          <button class="filter-btn" onclick="setFilter('en_attente',this)">En attente</button>
          <button class="filter-btn" onclick="setFilter('bloque',this)">Bloqués</button>
          <button class="filter-btn" onclick="setFilter('demande_cloture',this)">Dem. clôture</button>
          <button class="filter-btn" onclick="setFilter('demande_suppression',this)">Dem. supp.</button>
          <button class="filter-btn" onclick="setFilter('cloture',this)">Clôturés</button>
        </div>
      </div>
      <table id="comptes-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>IBAN</th>
            <th>Type</th>
            <th>Solde (TND)</th>
            <th>Statut</th>
            <th>Cartes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($comptes as $row):
          $nb = count(CarteController::findByCompte((int)$row['id_compte']));
          $ini = strtoupper(substr($row['prenom']??'',0,1).substr($row['nom']??'',0,1));
          $isSelected = $selected && $selected->getIdCompte()===(int)$row['id_compte'];
        ?>
        <tr data-statut="<?= htmlspecialchars($row['statut']) ?>" <?= $isSelected?'style="background:var(--blue-light)"':'' ?>>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem">
              <div class="dp-av" style="width:28px;height:28px;font-size:.6rem;flex-shrink:0;border-radius:50%"><?= $ini ?></div>
              <div>
                <div class="td-name"><?= htmlspecialchars($row['prenom'].' '.$row['nom']) ?></div>
                <div style="font-size:.65rem;color:var(--muted)"><?= htmlspecialchars($row['email']) ?></div>
              </div>
            </div>
          </td>
          <td class="td-iban"><?= htmlspecialchars(substr($row['iban'],0,20)).'…' ?></td>
          <td><?= typeLabel($row['type_compte']) ?></td>
          <td style="font-family:var(--fm);font-weight:500"><?= number_format((float)$row['solde'],3,'.',' ') ?></td>
          <td><?= badgeCompte($row['statut']) ?></td>
          <td style="font-size:.78rem;color:var(--muted)"><?= $nb ?> carte<?= $nb!==1?'s':'' ?></td>
          <td>
            <div class="action-group">
              <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $row['id_compte'] ?>" class="act-btn" title="Voir">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
              </a>
              <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $row['id_compte'] ?>&edit=compte" class="act-btn" title="Modifier">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php if ($row['statut']==='en_attente'): ?>
              <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline">
                <input type="hidden" name="action" value="activer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn success" title="Activer">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='bloque'): ?>
              <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline">
                <input type="hidden" name="action" value="debloquer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn" title="Débloquer">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='demande_cloture'): ?>
              <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Confirmer la clôture ?')">
                <input type="hidden" name="action" value="cloturer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Confirmer clôture">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='demande_suppression'): ?>
              <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Confirmer la suppression définitive ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Confirmer suppression">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de suppression ?')">
                <input type="hidden" name="action" value="refuser_suppression">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn" title="Refuser suppression">
                   <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='cloture'): ?>
              <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Supprimer définitivement ce compte et ses cartes ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Supprimer le compte">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='actif'): ?>
              <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline">
                <input type="hidden" name="action" value="bloquer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Bloquer">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($comptes)): ?>
        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">Aucun compte trouvé.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- DETAIL PANEL -->
    <div class="detail-panel">
    <?php if ($selected):
      $db=$_db??null;
      try {
        $db = Config::getConnexion();
        $uq = $db->prepare("SELECT * FROM utilisateur WHERE id=:id");
        $uq->execute(['id'=>$selected->getIdUtilisateur()]);
        $uRow=$uq->fetch();
      } catch(Exception $e){ $uRow=null; }
      $initDP = $uRow ? strtoupper(substr($uRow['prenom']??'',0,1).substr($uRow['nom']??'',0,1)) : '?';
    ?>

    <div class="dp-header">
      <div class="dp-av"><?= $initDP ?></div>
      <div>
        <div class="dp-name"><?= $uRow ? htmlspecialchars($uRow['prenom'].' '.$uRow['nom']) : 'Inconnu' ?></div>
        <?php if ($uRow): ?>
        <div class="dp-cin"><?= htmlspecialchars($uRow['cin']??'') ?></div>
        <div class="dp-kyc"><?= ($uRow['status_kyc']??'')==='VERIFIE'?'KYC vérifié':'KYC: '.htmlspecialchars($uRow['status_kyc']??'—') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($editCompte): ?>
    <!-- ─── INLINE EDIT FORM ────────────────────────── -->
    <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
      <div class="edit-form">
        <div class="edit-form-title">Modifier le compte #<?= $selected->getIdCompte() ?></div>
        <div class="form-field">
          <label>IBAN (non modifiable)</label>
          <input type="text" value="<?= htmlspecialchars($selected->getIban()) ?>" readonly>
        </div>
        <div class="form-field">
          <label>Type de compte</label>
          <select name="type_compte">
            <?php foreach (['courant'=>'Courant','epargne'=>'Épargne','devise'=>'Devise','professionnel'=>'Professionnel'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $selected->getTypeCompte()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Solde (TND)</label>
          <input type="text" name="solde" value="<?= htmlspecialchars($formData['solde'] ?? $selected->getSolde()) ?>">
          <?php if (isset($formErrors['solde'])): ?>
            <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['solde']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Devise</label>
          <select name="devise">
            <?php foreach (['TND','EUR','USD','GBP'] as $d): ?>
            <option value="<?= $d ?>" <?= $selected->getDevise()===$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Plafond virement (TND)</label>
          <input type="text" name="plafond_virement" value="<?= htmlspecialchars($formData['plafond_virement'] ?? $selected->getPlafondVirement()) ?>">
          <?php if (isset($formErrors['plafond_virement'])): ?>
            <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_virement']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Statut</label>
          <select name="statut">
            <?php foreach (['actif'=>'Actif','bloque'=>'Bloqué','en_attente'=>'En attente','demande_cloture'=>'Dem. clôture','demande_suppression'=>'Dem. supp.','cloture'=>'Clôturé'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $selected->getStatut()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Date de fermeture</label>
          <input type="text" name="date_fermeture" value="<?= htmlspecialchars($formData['date_fermeture'] ?? $selected->getDateFermeture() ?? '') ?>" placeholder="YYYY-MM-DD">
        </div>
        <div class="form-actions-row">
          <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $selected->getIdCompte() ?>" class="btn-cancel">Annuler</a>
          <button type="submit" class="btn-save">Enregistrer</button>
        </div>
      </div>
    </form>

    <?php else: ?>
    <!-- ─── READ MODE ───────────────────────────────── -->
    <div>
      <div class="dp-section">Compte</div>
      <div class="dp-row"><span class="dp-key">ID</span><span class="dp-val">#<?= $selected->getIdCompte() ?></span></div>
      <div class="dp-row"><span class="dp-key">IBAN</span><span class="dp-val-mono" style="font-size:.68rem;word-break:break-all"><?= htmlspecialchars($selected->getIban()) ?></span></div>
      <div class="dp-row"><span class="dp-key">Type</span><span class="dp-val"><?= typeLabel($selected->getTypeCompte()) ?></span></div>
      <div class="dp-row"><span class="dp-key">Solde</span><span class="dp-val" style="font-family:var(--fm);color:var(--blue)"><?= number_format((float)$selected->getSolde(),3,'.',' ') ?> <?= htmlspecialchars($selected->getDevise()) ?></span></div>
      <div class="dp-row"><span class="dp-key">Plafond virement</span><span class="dp-val"><?= number_format((float)$selected->getPlafondVirement(),0,'.',' ') ?> TND</span></div>
      <div class="dp-row"><span class="dp-key">Statut</span><?= badgeCompte($selected->getStatut()) ?></div>
      <div class="dp-row"><span class="dp-key">Ouverture</span><span class="dp-val"><?= htmlspecialchars($selected->getDateOuverture()) ?></span></div>
      <?php if ($selected->getDateFermeture()): ?>
      <div class="dp-row"><span class="dp-key">Fermeture</span><span class="dp-val"><?= htmlspecialchars($selected->getDateFermeture()) ?></span></div>
      <?php endif; ?>
    </div>

    <!-- Cards -->
    <div>
      <div class="dp-section">Cartes (<?= count($cartes) ?>)</div>
      <?php if (empty($cartes)): ?>
      <div style="font-size:.75rem;color:var(--muted)">Aucune carte liée.</div>
      <?php else: ?>
      <?php foreach ($cartes as $carte):
        $dpGrad = ($carte->getStatut()==='bloquee') ? 'rcg-bloque' : 'rcg-'.($carte->getStyle()?:'standard');
        $dpReseau = strtolower($carte->getReseau());
        $dpExp = $carte->getDateExpiration();
        $dpExpDisp = ($dpExp && strlen($dpExp) >= 7) ? substr($dpExp, 5, 2) . '/' . substr($dpExp, 2, 2) : ($dpExp ?: '--/--');
        $dpSceneId = 'dp-scene-'.$carte->getIdCarte();
        $dpModalData = json_encode([
          'num'    => $carte->getNumeroCarte(),
          'holder' => $carte->getTitulaireNom(),
          'exp'    => $dpExpDisp,
          'reseau' => strtolower($carte->getReseau()),
          'statut' => $carte->getStatut(),
          'style'  => $carte->getStyle()?:'standard',
          'plafondPay' => $carte->getPlafondPaiementJour(),
          'plafondRet' => $carte->getPlafondRetraitJour(),
          'cvv'        => $carte->getCvvDisplay(),
        ]);
      ?>
      <div style="margin-bottom:.6rem">

        <!-- Flippable card — same design as frontoffice -->
        <div class="card-scene" id="<?= $dpSceneId ?>" onclick="flipDpCard('<?= $dpSceneId ?>')">
          <div class="card-inner">
            <div class="card-face">
              <div class="real-card-front <?= $dpGrad ?>">
                <div class="card-holo"></div>
                <div class="rcf-top">
                  <div class="rcf-chip">
                    <div class="rcf-chip-grid"><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div></div>
                  </div>
                  <div style="display:flex;align-items:center;gap:.4rem;position:relative;z-index:1;">
                    <div style="font-size:.5rem;font-weight:700;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.12em;background:rgba(255,255,255,.08);border-radius:3px;padding:1px 5px;"><?= strtoupper(styleLabel($carte->getStyle()?:'standard')) ?></div>
                    <svg class="rcf-contactless" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10"/><path d="M12 6c3.31 0 6 2.69 6 6s-2.69 6-6 6"/><path d="M12 10c1.1 0 2 .9 2 2s-.9 2-2 2"/></svg>
                  </div>
                </div>
                <div class="rcf-number"><?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
                <div class="rcf-bottom">
                  <div>
                    <div class="rcf-holder-lbl">Card Holder</div>
                    <div class="rcf-holder-val"><?= htmlspecialchars($carte->getTitulaireNom()) ?></div>
                  </div>
                  <div style="text-align:center">
                    <div class="rcf-exp-lbl">Expires</div>
                    <div class="rcf-exp-val"><?= htmlspecialchars($dpExpDisp) ?></div>
                  </div>
                  <?php if ($dpReseau==='visa'): ?>
                  <div class="rcf-visa">VISA</div>
                  <?php else: ?>
                  <div class="rcf-mc"><div class="rcf-mc-l"></div><div class="rcf-mc-r"></div></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="card-face card-face-back">
              <div class="real-card-back <?= $dpGrad ?>">
                <div class="rcb-stripe"></div>
                <div class="rcb-sig-area">
                  <div class="rcb-sig-lbl">Signature autorisée</div>
                  <div class="rcb-sig-box">
                    <div class="rcb-sig-strip"></div>
                    <div class="rcb-cvv"><?= htmlspecialchars($carte->getCvvDisplay() ?: '•••') ?></div>
                  </div>
                </div>
                <div class="rcb-footer">
                  <div>
                    <div class="rcb-bank">LegalFin</div>
                    <div style="font-size:.45rem;color:rgba(255,255,255,.22);margin-top:2px;">Service client: 71 000 000</div>
                  </div>
                  <?php if ($dpReseau==='visa'): ?>
                  <div class="rcf-visa" style="font-size:.8rem;opacity:.4;">VISA</div>
                  <?php else: ?>
                  <div class="rcf-mc"><div class="rcf-mc-l" style="width:18px;height:18px;opacity:.6;"></div><div class="rcf-mc-r" style="width:18px;height:18px;opacity:.6;"></div></div>
                  <?php endif; ?>
                </div>
                <div class="rcb-fine">Cette carte est la propriété de LegalFin. En cas de perte ou vol, appelez le 71 000 000.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-flip-hint" style="margin-top:.3rem;">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
          Cliquer pour retourner · <a href="#" onclick="openCardModal(<?= htmlspecialchars($dpModalData) ?>);return false;" style="color:var(--blue);text-decoration:none;font-size:.58rem;">Voir en grand</a>
        </div>
        <?= badgeCarte($carte->getStatut()) ?>&nbsp;
        <span style="font-size:.62rem;background:var(--bg3);border:1px solid var(--border);border-radius:3px;padding:1px 5px;color:var(--muted);text-transform:uppercase"><?= htmlspecialchars(styleLabel($carte->getStyle())) ?></span>

        <!-- Card actions -->
        <div class="carte-mini-actions" style="margin-top:.5rem;">
          <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $selected->getIdCompte() ?>&edit_carte=<?= $carte->getIdCarte() ?>" class="cma-btn" title="Modifier">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Modifier
          </a>
          <?php if ($carte->getStatut()==='inactive'): ?>
          <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1">
            <input type="hidden" name="action" value="activer">
            <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
            <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
            <button type="submit" class="cma-btn success" style="width:100%">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              Activer
            </button>
          </form>
          <?php elseif ($carte->getStatut()==='demande_blocage'): ?>
          <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1" onsubmit="return confirm('Accepter et bloquer ?')">
            <input type="hidden" name="action" value="bloquer">
            <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
            <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
            <button type="submit" class="cma-btn success" style="width:100%">✓ Accepter blocage</button>
          </form>
          <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1" onsubmit="return confirm('Refuser le blocage ?')">
            <input type="hidden" name="action" value="refuser_blocage">
            <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
            <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
            <button type="submit" class="cma-btn" style="width:100%;background:rgba(107,114,128,.15);color:var(--muted)">✕ Refuser</button>
          </form>
          <?php elseif ($carte->getStatut()==='bloquee'): ?>
          <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1">
            <input type="hidden" name="action" value="debloquer">
            <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
            <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
            <button type="submit" class="cma-btn success" style="width:100%">Débloquer</button>
          </form>
          <?php elseif ($carte->getStatut()==='demande_cloture' || $carte->getStatut()==='demande_suppression'): ?>
          <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1" onsubmit="return confirm('Supprimer définitivement cette carte ?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
            <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
            <button type="submit" class="cma-btn danger" style="width:100%">🗑 Confirmer suppression</button>
          </form>
          <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1">
            <input type="hidden" name="action" value="refuser_cloture">
            <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
            <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
            <button type="submit" class="cma-btn" style="width:100%;background:rgba(107,114,128,.15);color:var(--muted)">✕ Refuser supp.</button>
          </form>
          <?php else: ?>
          <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1">
            <input type="hidden" name="action" value="bloquer">
            <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
            <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
            <button type="submit" class="cma-btn danger" style="width:100%">Bloquer</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Account actions -->
    <div class="dp-actions">
      <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $selected->getIdCompte() ?>&edit=compte" class="dp-action-btn da-primary">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Modifier le compte
      </a>
      <?php if ($selected->getStatut()==='bloque'): ?>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php">
        <input type="hidden" name="action" value="debloquer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-success" style="width:100%">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Débloquer le compte
        </button>
      </form>
      <?php elseif ($selected->getStatut()==='en_attente'): ?>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php">
        <input type="hidden" name="action" value="activer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-success" style="width:100%">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          Activer le compte
        </button>
      </form>
      <?php elseif (in_array($selected->getStatut(),['actif','demande_cloture','demande_suppression'])): ?>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php">
        <input type="hidden" name="action" value="bloquer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          Bloquer le compte
        </button>
      </form>
      <?php endif; ?>
      <?php if ($selected->getStatut()==='demande_cloture'): ?>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" onsubmit="return confirm('Accepter la demande de clôture ?')">
        <input type="hidden" name="action" value="cloturer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%;background:#991B1B;color:#fff;border:none">
          ✓ Accepter clôture (dem. client)
        </button>
      </form>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" onsubmit="return confirm('Refuser la demande de clôture ?')">
        <input type="hidden" name="action" value="refuser_cloture">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn" style="width:100%;background:rgba(107,114,128,.15);color:var(--muted);border:none">
          ✕ Refuser clôture
        </button>
      </form>
      <?php elseif ($selected->getStatut()==='demande_suppression'): ?>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" onsubmit="return confirm('Accepter la demande de suppression (Suppression définitive) ?')">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%;background:#991B1B;color:#fff;border:none">
          ✓ Accepter suppression
        </button>
      </form>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" onsubmit="return confirm('Refuser la demande de suppression ?')">
        <input type="hidden" name="action" value="refuser_suppression">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn" style="width:100%;background:rgba(107,114,128,.15);color:var(--muted);border:none">
          ✕ Refuser suppression
        </button>
      </form>
      <?php elseif ($selected->getStatut()==='cloture'): ?>
      <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" onsubmit="return confirm('Supprimer définitivement ce compte et ses cartes ?')">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%;border:none">
          🗑 Supprimer le compte (clôturé)
        </button>
      </form>
      <?php endif; ?>
    </div>

    <?php endif; // editCompte ?>

    <?php else: ?>
    <!-- Empty state -->
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.6rem;color:var(--muted);text-align:center;padding:2.5rem 0">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" opacity=".3"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      <div style="font-size:.82rem">Sélectionnez un compte<br>pour voir le détail</div>
    </div>
    <?php endif; // selected ?>
    </div><!-- .detail-panel -->

  </div><!-- .two-col-layout -->
  <?php endif; // tab ?>

  </div><!-- .content -->
</div><!-- .main -->

<!-- ═══ CARD VIEW MODAL ═══════════════════════════════ -->
<div class="card-modal-overlay" id="cardModal" onclick="if(event.target===this)closeCardModal()">
  <div class="card-modal-box">
    <div class="card-modal-header">
      <div>
        <div class="card-modal-title" id="modalTitle">Carte bancaire</div>
        <div class="card-modal-sub" id="modalSub">—</div>
      </div>
      <button class="card-modal-close" onclick="closeCardModal()">✕</button>
    </div>

    <!-- 3D flip card -->
    <div class="modal-card-scene" id="modalCardScene" onclick="this.classList.toggle('flipped')">
      <div class="modal-card-inner">
        <div class="modal-card-face">
          <div class="modal-real-front" id="modalFront">
            <div class="modal-holo"></div>
            <div class="modal-top">
              <div class="modal-chip">
                <div class="modal-chip-grid"><div class="modal-chip-sq"></div><div class="modal-chip-sq"></div><div class="modal-chip-sq"></div><div class="modal-chip-sq"></div></div>
              </div>
              <svg class="modal-contactless" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10"/><path d="M12 6c3.31 0 6 2.69 6 6s-2.69 6-6 6"/><path d="M12 10c1.1 0 2 .9 2 2s-.9 2-2 2"/></svg>
            </div>
            <div class="modal-num" id="modalNum">•••• •••• •••• ••••</div>
            <div class="modal-bot">
              <div>
                <div class="modal-holder-lbl">Card Holder</div>
                <div class="modal-holder-val" id="modalHolder">—</div>
              </div>
              <div>
                <div class="modal-exp-lbl">Expires</div>
                <div class="modal-exp-val" id="modalExp">—</div>
              </div>
              <div id="modalFrontNet"></div>
            </div>
          </div>
        </div>
        <div class="modal-card-face modal-card-face-back">
          <div class="modal-real-back" id="modalBack">
            <div class="modal-stripe"></div>
            <div class="modal-sig-area">
              <div class="modal-sig-lbl">Signature autorisée</div>
              <div class="modal-sig-box">
                <div class="modal-sig-strip"></div>
                <div class="modal-cvv-val" id="modalCvv">•••</div>
              </div>
            </div>
            <div class="modal-back-footer">
              <div class="modal-back-bank">LegalFin</div>
              <div id="modalBackNet"></div>
            </div>
            <div class="modal-fine">Cette carte est la propriété de LegalFin. En cas de perte ou vol, appelez immédiatement le 71 000 000.</div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-flip-hint">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
      Cliquez sur la carte pour voir le verso
    </div>

    <div class="modal-info-grid">
      <div class="mi-item"><div class="mi-lbl">Réseau</div><div class="mi-val" id="mi-reseau">—</div></div>
      <div class="mi-item"><div class="mi-lbl">Statut</div><div id="mi-statut">—</div></div>
      <div class="mi-item"><div class="mi-lbl">Plafond paiement/j</div><div class="mi-val" id="mi-ppay">—</div></div>
      <div class="mi-item"><div class="mi-lbl">Plafond retrait/j</div><div class="mi-val" id="mi-pret">—</div></div>
    </div>
  </div>
</div>

<script>
/* ── Table filters ── */
let currentFilter='tous';
function setFilter(val,btn){
  currentFilter=val;
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function applyFilters(){
  const q=(document.getElementById('search-input')?.value||'').toLowerCase();
  document.querySelectorAll('#comptes-table tbody tr').forEach(tr=>{
    const statut=tr.dataset.statut||'';
    const text=tr.textContent.toLowerCase();
    const matchS=currentFilter==='tous'||statut===currentFilter;
    const matchQ=!q||text.includes(q);
    tr.style.display=(matchS&&matchQ)?'':'none';
  });
}

/* ── Card flip (small panel) ── */
function flipDpCard(id){document.getElementById(id).classList.toggle('flipped');}
function flipCard(id){document.getElementById(id).classList.toggle('flipped');}

/* ── Card Modal ── */
const gradMap={
  standard:'linear-gradient(135deg,#1a2a6c,#2563eb,#1e40af)',
  gold:'linear-gradient(135deg,#3d2c00,#8b6914,#c9a227)',
  platinum:'linear-gradient(135deg,#1a1a2e,#374151,#6b7280)',
  titanium:'linear-gradient(135deg,#111111 0%,#2a2a2a 35%,#585858 65%,#8a8a8a 85%,#b0b0b0 100%)',
  bloque:'linear-gradient(135deg,#2d1b1b,#7f1d1d,#991b1b)',
};
const statusLabels={
  active:'<span style="display:inline-flex;align-items:center;gap:4px;background:#F0FDF4;color:#16A34A;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Active</span>',
  demande_blocage:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FFFBEB;color:#D97706;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Dem. blocage</span>',
  inactive:'<span style="display:inline-flex;align-items:center;gap:4px;background:#F1F5F9;color:#64748B;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Inactive</span>',
  bloquee:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FEF2F2;color:#DC2626;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Bloquée</span>',
  expiree:'<span style="display:inline-flex;align-items:center;gap:4px;background:#F1F5F9;color:#64748B;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Expirée</span>',
  demande_cloture:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FFFBEB;color:#D97706;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Dem. supp.</span>',
  demande_suppression:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FFFBEB;color:#D97706;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Dem. supp.</span>',
};
function openCardModal(data){
  if(typeof data==='string') data=JSON.parse(data);
  document.getElementById('modalCardScene').classList.remove('flipped');
  document.getElementById('modalTitle').textContent=data.holder;
  document.getElementById('modalSub').textContent=data.num+' · '+data.style.charAt(0).toUpperCase()+data.style.slice(1);
  document.getElementById('modalNum').textContent=data.num;
  document.getElementById('modalHolder').textContent=data.holder.toUpperCase();
  document.getElementById('modalExp').textContent=data.exp;
  const grad=data.statut==='bloquee'?gradMap.bloque:(gradMap[data.style]||gradMap.standard);
  document.getElementById('modalFront').style.background=grad;
  document.getElementById('modalBack').style.background=grad.replace('135deg','145deg');
  // network
  const visaHtml='<div style="font-family:Syne,sans-serif;font-size:.95rem;font-weight:800;color:rgba(255,255,255,.85);font-style:italic;">VISA</div>';
  const mcHtml='<div style="display:flex;align-items:center;"><div style="width:22px;height:22px;border-radius:50%;background:#eb001b;opacity:.9;"></div><div style="width:22px;height:22px;border-radius:50%;background:#f79e1b;opacity:.9;margin-left:-8px;"></div></div>';
  const visaBackHtml='<div style="font-family:Syne,sans-serif;font-size:.8rem;font-weight:800;color:rgba(255,255,255,.35);font-style:italic;">VISA</div>';
  const mcBackHtml='<div style="display:flex;align-items:center;"><div style="width:18px;height:18px;border-radius:50%;background:#eb001b;opacity:.5;"></div><div style="width:18px;height:18px;border-radius:50%;background:#f79e1b;opacity:.5;margin-left:-7px;"></div></div>';
  document.getElementById('modalFrontNet').innerHTML=data.reseau==='visa'?visaHtml:mcHtml;
  document.getElementById('modalBackNet').innerHTML=data.reseau==='visa'?visaBackHtml:mcBackHtml;
  // info
  document.getElementById('mi-reseau').textContent=data.reseau.toUpperCase();
  document.getElementById('mi-statut').innerHTML=statusLabels[data.statut]||data.statut;
  document.getElementById('mi-ppay').textContent=Number(data.plafondPay).toLocaleString()+' TND';
  document.getElementById('mi-pret').textContent=Number(data.plafondRet).toLocaleString()+' TND';
  if(document.getElementById('modalCvv')) document.getElementById('modalCvv').textContent=data.cvv||'•••';
  document.getElementById('cardModal').classList.add('open');
}
function closeCardModal(){document.getElementById('cardModal').classList.remove('open');}
</script>
</body>
</html>
