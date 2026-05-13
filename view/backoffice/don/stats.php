<?php
require_once __DIR__ . '/../../../models/config.php';
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . "/../../../controller/CagnotteController.php";
require_once __DIR__ . "/../../../controller/DonController.php";
require_once __DIR__ . "/helpers.php";
Session::start();
Session::requireAdmin();

$cagCtrl = new CagnotteController();
$donCtrl = new DonController();

$overviewStats = $cagCtrl->getAdminOverviewStats();
$cagnotteStatusCounts = $cagCtrl->getCagnotteStatusCounts();
$cagnotteCategoryCounts = $cagCtrl->getCagnotteCategoryCounts();
$donStatusCounts = $donCtrl->getDonStatusCounts();
$confirmedStats = $donCtrl->getConfirmedStats();
$globalDonStats = $donCtrl->getGlobalStats();
$paymentStats = $donCtrl->getPaymentMethodStats();
$topCagnottes = $donCtrl->getTopDonCagnottes(5);

$pendingCount = (int)($cagnotteStatusCounts['en_attente'] ?? 0);
$donsToConfirm = (int)($donStatusCounts['en_attente'] ?? 0);
$totalCagnottes = max(1, (int)($overviewStats['total_cagnottes'] ?? 0));
$totalDons = max(1, (int)($globalDonStats['total_dons'] ?? 0));

function percentValue($value, $total) {
  if ((int)$total <= 0) return '0.0';
  return number_format((((float)$value * 100) / (float)$total), 1, '.', '');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin - Statistiques</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/view/assets/css/backoffice/Utilisateur.css">
<link rel="stylesheet" href="<?= APP_URL ?>/view/assets/css/backoffice/don.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php renderBackofficeSidebar('stats'); ?>

<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Statistics</div>
      <div class="breadcrumb">Admin / Statistiques</div>
    </div>
    <div class="tb-right">
      <a class="btn-ghost" href="<?= backofficeBuildUrl('cagnotte.php') ?>">Ouvrir les cagnottes</a>
      <a class="btn-primary" href="<?= backofficeBuildUrl('dons.php') ?>">Ouvrir les dons</a>
    </div>
  </div>

  <div class="content" id="analytics-overview">
    <div class="stats-hero">
      <div>
        <div class="stats-hero-eyebrow">Centre analytique</div>
        <h2 class="stats-hero-title">Pilotage global des cagnottes et des dons</h2>
        <p class="stats-hero-text">Une vue consolidée pour suivre le volume, les statuts et les montants confirmés en un seul endroit.</p>
      </div>
      <div class="stats-hero-meta">
        <div class="stats-hero-chip">En attente cagnottes: <?= $pendingCount ?></div>
        <div class="stats-hero-chip">En attente dons: <?= $donsToConfirm ?></div>
      </div>
    </div>

    <div class="section-head">
      <h3 class="section-title">Indicateurs principaux</h3>
      <div class="section-subtitle">Les KPI clés du backoffice, mis à jour à partir des données courantes.</div>
    </div>
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--teal-light)">
          <svg width="18" height="18" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
          <div class="kpi-val"><?= (int)($overviewStats['total_cagnottes'] ?? 0) ?></div>
          <div class="kpi-label">Cagnottes créées</div>
          <div class="kpi-sub" style="color:var(--teal)"><?= (int)($cagnotteStatusCounts['acceptee'] ?? 0) ?> actives</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <div>
          <div class="kpi-val"><?= (int)($globalDonStats['total_dons'] ?? 0) ?></div>
          <div class="kpi-label">Dons enregistrés</div>
          <div class="kpi-sub" style="color:var(--blue)"><?= (int)($donStatusCounts['confirme'] ?? 0) ?> confirmés</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="font-size:1.2rem"><?= number_format((float)($confirmedStats['total_conf'] ?? 0), 2, ',', ' ') ?> €</div>
          <div class="kpi-label">Montant confirmé (€)</div>
          <div class="kpi-sub" style="color:var(--green)">Objectif <?= number_format((float)($overviewStats['total_objectif'] ?? 0), 2, ',', ' ') ?> €</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="color:var(--amber)"><?= $pendingCount + $donsToConfirm ?></div>
          <div class="kpi-label">Éléments à traiter</div>
          <div class="kpi-sub" style="color:var(--amber)"><?= $pendingCount ?> cagnottes, <?= $donsToConfirm ?> dons</div>
        </div>
      </div>
    </div>

    <div class="section-head">
      <h3 class="section-title">Répartition des statuts</h3>
      <div class="section-subtitle">Visualisation en pie/doughnut charts des statuts cagnottes et dons.</div>
    </div>
    <div class="charts-grid">
      <div class="chart-card">
        <div class="chart-title">Cagnottes par statut</div>
        <div class="chart-wrap"><canvas id="cagnotteStatusChart"></canvas></div>
        <div class="chart-summary">
          <?php foreach ($cagnotteStatusCounts as $status => $count): ?>
            <div class="chart-summary-row">
              <span class="chart-summary-label"><?= htmlspecialchars(cagnotteStatusLabel($status)) ?></span>
              <span class="chart-summary-value"><?= (int)$count ?> (<?= percentValue((int)$count, $totalCagnottes) ?>%)</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-title">Dons par statut</div>
        <div class="chart-wrap"><canvas id="donStatusChart"></canvas></div>
        <div class="chart-summary">
          <?php foreach ($donStatusCounts as $status => $count): ?>
            <div class="chart-summary-row">
              <span class="chart-summary-label"><?= htmlspecialchars($donCtrl->getDonStatusLabel($status)) ?></span>
              <span class="chart-summary-value"><?= (int)$count ?> (<?= percentValue((int)$count, $totalDons) ?>%)</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="section-head">
      <h3 class="section-title">Catégories et paiements</h3>
      <div class="section-subtitle">Pie chart pour catégories et bar chart pour montants par moyen de paiement.</div>
    </div>
    <div class="charts-grid">
      <div class="chart-card">
        <div class="chart-title">Catégories de cagnottes</div>
        <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
        <div class="chart-summary">
          <?php if (empty($cagnotteCategoryCounts)): ?>
            <div class="chart-summary-row">
              <span class="chart-summary-label">Aucune catégorie</span>
              <span class="chart-summary-value">0 (0.0%)</span>
            </div>
          <?php else: foreach ($cagnotteCategoryCounts as $categoryStat): ?>
            <?php $categoryCount = (int)($categoryStat['total'] ?? 0); ?>
            <div class="chart-summary-row">
              <span class="chart-summary-label"><?= htmlspecialchars(cagnotteCategoryLabel($categoryStat['categorie'] ?? 'autre')) ?></span>
              <span class="chart-summary-value"><?= $categoryCount ?> (<?= percentValue($categoryCount, $totalCagnottes) ?>%)</span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-title">Montants par moyen de paiement</div>
        <div class="chart-wrap"><canvas id="paymentChart"></canvas></div>
        <div class="chart-summary">
          <?php
            $paymentTotal = 0.0;
            foreach ($paymentStats as $paymentStat) {
              $paymentTotal += (float)($paymentStat['montant_total'] ?? 0);
            }
            $paymentTotal = max(0.0001, $paymentTotal);
          ?>
          <?php if (empty($paymentStats)): ?>
            <div class="chart-summary-row">
              <span class="chart-summary-label">Aucun paiement</span>
              <span class="chart-summary-value">0.00 € (0.0%)</span>
            </div>
          <?php else: foreach ($paymentStats as $paymentStat): ?>
            <?php $paymentAmount = (float)($paymentStat['montant_total'] ?? 0); ?>
            <div class="chart-summary-row">
              <span class="chart-summary-label"><?= htmlspecialchars(ucfirst((string)($paymentStat['moyen_paiement'] ?? 'Inconnu'))) ?></span>
              <span class="chart-summary-value"><?= number_format($paymentAmount, 2, ',', ' ') ?> € (<?= percentValue($paymentAmount, $paymentTotal) ?>%)</span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <div class="section-head">
      <h3 class="section-title">Classement des cagnottes</h3>
      <div class="section-subtitle">Top 5 des cagnottes selon les montants confirmés.</div>
    </div>
    <div class="table-card stats-panel">
      <div class="table-toolbar"><div class="table-toolbar-title">Top cagnottes par montant confirmé</div></div>
      <table>
        <thead>
          <tr>
            <th>Cagnotte</th>
            <th>Nombre de dons</th>
            <th>Total confirmé</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($topCagnottes)): ?>
            <tr><td colspan="3"><div class="empty-state compact-empty-state"><div class="empty-state-title">Aucune donnée disponible</div><div class="empty-state-text">Les meilleures cagnottes s'afficheront ici.</div></div></td></tr>
          <?php else: foreach ($topCagnottes as $topCagnotte): ?>
            <tr>
              <td><?= htmlspecialchars($topCagnotte['titre'] ?? '—') ?></td>
              <td class="td-mono"><?= (int)($topCagnotte['nb_dons'] ?? 0) ?></td>
              <td class="td-bold"><?= number_format((float)($topCagnotte['total_confirme'] ?? 0), 2, ',', ' ') ?> €</td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const cagnotteStatusLabels = <?= json_encode(array_map('cagnotteStatusLabel', array_keys($cagnotteStatusCounts))) ?>;
const cagnotteStatusValues = <?= json_encode(array_map('intval', array_values($cagnotteStatusCounts))) ?>;

const donStatusLabels = <?= json_encode(array_map([$donCtrl, 'getDonStatusLabel'], array_keys($donStatusCounts))) ?>;
const donStatusValues = <?= json_encode(array_map('intval', array_values($donStatusCounts))) ?>;

const categoryLabels = <?= json_encode(array_map(static function ($row) { return cagnotteCategoryLabel($row['categorie'] ?? 'autre'); }, $cagnotteCategoryCounts)) ?>;
const categoryValues = <?= json_encode(array_map(static function ($row) { return (int)($row['total'] ?? 0); }, $cagnotteCategoryCounts)) ?>;

const paymentLabels = <?= json_encode(array_map(static function ($row) { return ucfirst((string)($row['moyen_paiement'] ?? 'Inconnu')); }, $paymentStats)) ?>;
const paymentAmounts = <?= json_encode(array_map(static function ($row) { return (float)($row['montant_total'] ?? 0); }, $paymentStats)) ?>;

const palette = ['#6ee7b7', '#93c5fd', '#fde68a', '#f9a8d4', '#c4b5fd', '#fda4af'];

function buildDoughnut(id, labels, values) {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: palette, borderWidth: 0 }]
    },
    options: {
      maintainAspectRatio: false,
      animation: {
        duration: 1500,
        easing: 'easeOutCubic'
      },
      animations: {
        animateRotate: true,
        animateScale: true
      },
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
}

function buildPie(id, labels, values) {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: palette, borderWidth: 0 }]
    },
    options: {
      maintainAspectRatio: false,
      animation: {
        duration: 1500,
        easing: 'easeOutCubic'
      },
      animations: {
        animateRotate: true,
        animateScale: true
      },
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
}

function buildBar(id, labels, values) {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'EUR', data: values, backgroundColor: '#93c5fd', borderColor: '#60a5fa', borderWidth: 1, borderRadius: 8 }]
    },
    options: {
      maintainAspectRatio: false,
      animation: {
        duration: 1400,
        easing: 'easeOutCubic'
      },
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#64748b' }, grid: { color: '#e5edf7' } },
        x: { ticks: { color: '#64748b' }, grid: { display: false } }
      }
    }
  });
}

buildDoughnut('cagnotteStatusChart', cagnotteStatusLabels, cagnotteStatusValues);
buildDoughnut('donStatusChart', donStatusLabels, donStatusValues);
buildPie('categoryChart', categoryLabels, categoryValues);
buildBar('paymentChart', paymentLabels, paymentAmounts);
</script>
</body>
</html>
