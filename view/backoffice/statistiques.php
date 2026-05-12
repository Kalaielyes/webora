<?php
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../models/investissement_projet.php';
require_once __DIR__ . '/../../models/score.php';
Session::start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>BankFlow Admin — Statistiques</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="action.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.stat-section{margin-bottom:1.5rem;}
.stat-section-title{font-family:var(--fh);font-size:.88rem;font-weight:700;margin-bottom:.8rem;display:flex;align-items:center;gap:.5rem;color:var(--text);}
.stat-section-title svg{width:16px;height:16px;flex-shrink:0;}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.1rem;position:relative;overflow:hidden;transition:transform .15s,box-shadow .15s;}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(15,23,42,.08);}
.stat-card-accent{position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--r) var(--r) 0 0;}
.stat-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;margin-bottom:.6rem;}
.stat-val{font-family:var(--fh);font-size:1.4rem;font-weight:700;line-height:1;}
.stat-label{font-size:.72rem;color:var(--muted);margin-top:.25rem;}
.stat-sub{font-size:.66rem;margin-top:.3rem;font-weight:500;}
.chart-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.2rem;}
.chart-title{font-family:var(--fh);font-size:.82rem;font-weight:700;margin-bottom:1rem;}
.bar-chart{display:flex;flex-direction:column;gap:.6rem;}
.bar-row{display:flex;align-items:center;gap:.8rem;}
.bar-label{width:120px;font-size:.75rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0;}
.bar-track{flex:1;height:22px;background:var(--bg3);border-radius:6px;overflow:hidden;position:relative;}
.bar-fill{height:100%;border-radius:6px;transition:width .6s ease;}
.bar-value{font-size:.7rem;font-family:var(--fm);color:var(--muted);width:80px;text-align:right;flex-shrink:0;}
.two-chart{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.rank-list{display:flex;flex-direction:column;gap:0;}
.rank-item{display:flex;align-items:center;gap:.7rem;padding:.55rem 0;border-bottom:1px solid var(--border);}
.rank-item:last-child{border-bottom:none;}
.rank-num{width:22px;height:22px;border-radius:6px;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:var(--muted);flex-shrink:0;}
.rank-item:nth-child(1) .rank-num{background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;}
.rank-item:nth-child(2) .rank-num{background:linear-gradient(135deg,#94A3B8,#64748B);color:#fff;}
.rank-item:nth-child(3) .rank-num{background:linear-gradient(135deg,#CD7F32,#A0522D);color:#fff;}
.rank-name{flex:1;font-size:.78rem;font-weight:500;}
.rank-val{font-size:.78rem;font-family:var(--fm);font-weight:600;}
.donut-wrap{display:flex;align-items:center;gap:1.5rem;padding:.5rem 0;}
.donut-legend{display:flex;flex-direction:column;gap:.5rem;}
.legend-item{display:flex;align-items:center;gap:.5rem;font-size:.75rem;}
.legend-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0;}
.score-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1rem;}
.score-table{width:100%;border-collapse:collapse;}
.score-table th,.score-table td{padding:.65rem .55rem;border-bottom:1px solid var(--border);text-align:left;font-size:.78rem;}
.score-table th{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;}
.score-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .55rem;border-radius:999px;font-weight:700;font-size:.72rem;}
.score-green{background:#dcfce7;color:#166534;}
.score-amber{background:#fef3c7;color:#92400e;}
.score-rose{background:#fee2e2;color:#991b1b;}
.score-modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:12000;align-items:center;justify-content:center;padding:1rem;}
.score-modal.active{display:flex;}
.score-modal-card{width:min(620px,100%);background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 30px 80px rgba(15,23,42,.28);}
.score-modal-head{padding:.9rem 1rem;background:#0f172a;color:#fff;display:flex;justify-content:space-between;align-items:center;}
.score-modal-body{padding:1rem;max-height:70vh;overflow:auto;}
.score-factor{border:1px solid #e2e8f0;border-radius:10px;padding:.7rem .75rem;margin-bottom:.55rem;}
.score-factor-title{font-size:.75rem;font-weight:700;color:#0f172a;margin-bottom:.2rem;}
.score-factor-reason{font-size:.74rem;color:#64748b;line-height:1.45;}
</style>


</head>
<body>
<?php
$pdo = Config::getConnexion();
$projects = []; $investments = [];
$pendingCount = 0; $totalCount = 0; $pendingInvestmentsCount = 0;
$approvedProjects = 0; $refusedProjects = 0; $enCoursProjects = 0;
$totalObjectif = 0; $totalInvesti = 0;

try {
    $stmt = $pdo->query("
        SELECT p.id_projet, p.titre, p.montant_objectif, p.secteur, p.status,
               p.taux_rentabilite, p.temps_retour_brut,
               COALESCE((SELECT SUM(i.montant_investi) FROM investissement i WHERE i.id_projet = p.id_projet AND i.status = 'VALIDE'), 0) AS total_investi
        FROM projet p ORDER BY p.date_creation DESC
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCount = count($projects);
    foreach ($projects as &$p) {
        if ($p['status'] === 'EN_ATTENTE') $pendingCount++;
        elseif ($p['status'] === 'VALIDE' || $p['status'] === 'TERMINE') $approvedProjects++;
        elseif ($p['status'] === 'REFUSE' || $p['status'] === 'ANNULE') $refusedProjects++;
        elseif ($p['status'] === 'EN_COURS') $enCoursProjects++;
        $totalObjectif += (float)$p['montant_objectif'];
        $totalInvesti += (float)$p['total_investi'];
        $p['progression'] = $p['montant_objectif'] > 0 ? round(($p['total_investi'] / $p['montant_objectif']) * 100, 1) : 0;
    }
    unset($p);
} catch (Exception $e) { $projects = []; }

$totalInvestmentsCount = 0; $totalInvestedAmount = 0; $activeInvestorsCount = 0;
$validCount = 0; $refusedInvCount = 0;
try {
    $investments = Investissement::getAllInvestments();
    $totalInvestmentsCount = count($investments);
    $uniqueInvestors = [];
    foreach ($investments as $inv) {
        if ($inv['status'] === 'EN_ATTENTE') $pendingInvestmentsCount++;
        if ($inv['status'] === 'VALIDE') {
            $validCount++;
            $totalInvestedAmount += $inv['montant_investi'];
            $uniqueInvestors[$inv['id_investisseur']] = true;
        }
        if ($inv['status'] === 'REFUSE' || $inv['status'] === 'ANNULE') $refusedInvCount++;
    }
    $activeInvestorsCount = count($uniqueInvestors);
} catch (Exception $e) { $investments = []; }

try {
    Score::recalculateAllUsers();
} catch (Exception $e) {
    // non-blocking
}
$userScores = [];
try {
    $userScores = Score::getAdminScores();
} catch (Exception $e) {
    $userScores = [];
}

$avgInvestment = $validCount > 0 ? $totalInvestedAmount / $validCount : 0;
$acceptanceRate = $totalCount > 0 ? round(($approvedProjects / $totalCount) * 100, 1) : 0;
$globalProgress = $totalObjectif > 0 ? round(($totalInvesti / $totalObjectif) * 100, 1) : 0;

// TRI & Retour brut averages
$triVals = array_filter(array_column($projects, 'taux_rentabilite'), fn($v) => $v > 0);
$avgTRI = count($triVals) > 0 ? round(array_sum($triVals) / count($triVals), 2) : 0;
$retourVals = array_filter(array_column($projects, 'temps_retour_brut'), fn($v) => $v > 0);
$avgRetour = count($retourVals) > 0 ? round(array_sum($retourVals) / count($retourVals), 1) : 0;

// Sector breakdown
$sectors = [];
foreach ($projects as $p) {
    $s = $p['secteur'] ?: 'Autre';
    if (!isset($sectors[$s])) $sectors[$s] = ['count' => 0, 'invested' => 0];
    $sectors[$s]['count']++;
    $sectors[$s]['invested'] += (float)$p['total_investi'];
}
arsort($sectors);

// Top investors
$investorsTotals = [];
foreach ($investments as $inv) {
    if ($inv['status'] === 'VALIDE') {
        $name = $inv['nom'] . ' ' . $inv['prenom'];
        $investorsTotals[$name] = ($investorsTotals[$name] ?? 0) + $inv['montant_investi'];
    }
}
arsort($investorsTotals);

// Top funded projects
$topProjects = $projects;
usort($topProjects, fn($a, $b) => $b['total_investi'] <=> $a['total_investi']);
$topProjects = array_filter(array_slice($topProjects, 0, 5), fn($p) => $p['total_investi'] > 0);
$maxInvested = !empty($topProjects) ? max(array_column($topProjects, 'total_investi')) : 1;
$maxSectorInv = !empty($sectors) ? max(array_column($sectors, 'invested')) : 1;
?>
<?php include __DIR__ . '/partials/sidebar_unified.php'; ?>
<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Statistiques</div>
      <div class="breadcrumb">Admin / Statistiques</div>
    </div>
  </div>
  <div class="content">

    <!-- ═══ GLOBAL KPIs ═══ -->
    <div class="stat-section">
      <div class="stat-section-title">
        <svg fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Indicateurs Globaux
      </div>
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--blue)"></div>
          <div class="stat-icon" style="background:var(--blue-light)">
            <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="stat-val"><?= $totalInvestmentsCount ?></div>
          <div class="stat-label">Investissements total</div>
          <div class="stat-sub" style="color:var(--amber)">↑ <?= $pendingInvestmentsCount ?> en attente</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--amber)"></div>
          <div class="stat-icon" style="background:var(--amber-light)">
            <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          </div>
          <div class="stat-val"><?= $pendingInvestmentsCount ?></div>
          <div class="stat-label">En attente</div>
          <div class="stat-sub" style="color:var(--amber)">Approbation requise</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--green)"></div>
          <div class="stat-icon" style="background:var(--green-light)">
            <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="stat-val" style="font-size:1.15rem"><?= number_format($totalInvestedAmount, 0, ',', ' ') ?> TND</div>
          <div class="stat-label">Total investi</div>
          <div class="stat-sub" style="color:var(--green)">Contributions validées</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--violet)"></div>
          <div class="stat-icon" style="background:var(--violet-light)">
            <svg width="18" height="18" fill="none" stroke="var(--violet)" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <div class="stat-val"><?= $activeInvestorsCount ?></div>
          <div class="stat-label">Investisseurs actifs</div>
          <div class="stat-sub" style="color:var(--green)">Avec investissements validés</div>
        </div>
      </div>
    </div>



    <!-- ═══ INVESTMENT PERFORMANCE ═══ -->
    <div class="stat-section">
      <div class="stat-section-title">
        <svg fill="none" stroke="var(--violet)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Performance Investissements
      </div>
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--violet)"></div>
          <div class="stat-icon" style="background:var(--violet-light)">
            <svg width="18" height="18" fill="none" stroke="var(--violet)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="stat-val" style="font-size:1.1rem"><?= number_format($avgInvestment, 0, ',', ' ') ?> TND</div>
          <div class="stat-label">Montant moyen investi</div>
          <div class="stat-sub" style="color:var(--violet)">Par investissement validé</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--teal)"></div>
          <div class="stat-icon" style="background:var(--teal-light)">
            <svg width="18" height="18" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
          </div>
          <div class="stat-val"><?= $avgTRI ?>%</div>
          <div class="stat-label">TRI moyen</div>
          <div class="stat-sub" style="color:var(--teal)">Taux de rentabilité interne</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--amber)"></div>
          <div class="stat-icon" style="background:var(--amber-light)">
            <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div class="stat-val"><?= $avgRetour ?> mois</div>
          <div class="stat-label">Retour brut moyen</div>
          <div class="stat-sub" style="color:var(--amber)">Temps de récupération</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent" style="background:var(--green)"></div>
          <div class="stat-icon" style="background:var(--green-light)">
            <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="stat-val"><?= $validCount ?></div>
          <div class="stat-label">Investissements validés</div>
          <div class="stat-sub" style="color:var(--rose)"><?= $refusedInvCount ?> refusés</div>
        </div>
      </div>
    </div>

    <!-- ═══ CHARTS ═══ -->
    <div class="two-chart">
      <div class="chart-card" style="position: relative; height: 300px;">
        <div class="chart-title">Top Projets Financés</div>
        <canvas id="chart-top-projects"></canvas>
      </div>
      <div class="chart-card" style="position: relative; height: 300px;">
        <div class="chart-title">Top Investisseurs</div>
        <canvas id="chart-top-investors"></canvas>
      </div>
    </div>

    <!-- ═══ SECTOR BREAKDOWN ═══ -->
    <div class="chart-card" style="position: relative; height: 350px; margin-top: 1rem;">
      <div class="chart-title">Répartition par Secteur</div>
      <canvas id="chart-sectors"></canvas>
    </div>

    <?php
    $topProjectLabels = array_map(fn($p) => htmlspecialchars_decode($p['titre']), $topProjects);
    $topProjectData = array_map(fn($p) => (float)$p['total_investi'], $topProjects);

    $investorsSlice = array_slice($investorsTotals, 0, 5, true);
    $investorLabels = array_map('htmlspecialchars_decode', array_keys($investorsSlice));
    $investorData = array_values($investorsSlice);

    $sectorLabels = array_map('htmlspecialchars_decode', array_keys($sectors));
    $sectorData = array_column($sectors, 'invested');
    $sectorColors = [];
    $defaultColors = ['Énergie'=>'#fde68a','Tech'=>'#99f6e4','Santé'=>'#ddd6fe','Agriculture'=>'#a7f3d0','Immobilier'=>'#bfdbfe','Finance'=>'#c7d2fe'];
    foreach ($sectorLabels as $lbl) {
        $sectorColors[] = $defaultColors[$lbl] ?? '#e2e8f0';
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
      Chart.defaults.color = 'rgba(148, 163, 184, 0.8)';
      Chart.defaults.font.family = "'DM Sans', sans-serif";

      // 1. Top Projets Financés (Bar Chart)
      new Chart(document.getElementById('chart-top-projects'), {
        type: 'bar',
        data: {
          labels: <?= json_encode($topProjectLabels) ?>,
          datasets: [{
            label: 'Montant Investi (TND)',
            data: <?= json_encode($topProjectData) ?>,
            backgroundColor: 'rgba(147, 197, 253, 0.9)',
            borderWidth: 0,
            borderRadius: 6,
            barPercentage: 0.6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
            x: { grid: { display: false } }
          }
        }
      });

      // 2. Top Investisseurs (Horizontal Bar Chart)
      new Chart(document.getElementById('chart-top-investors'), {
        type: 'bar',
        data: {
          labels: <?= json_encode($investorLabels) ?>,
          datasets: [{
            label: 'Total Investi (TND)',
            data: <?= json_encode($investorData) ?>,
            backgroundColor: 'rgba(167, 243, 208, 0.9)',
            borderWidth: 0,
            borderRadius: 6,
            barPercentage: 0.6
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
            y: { grid: { display: false } }
          }
        }
      });

      // 3. Répartition par Secteur (Doughnut Chart)
      new Chart(document.getElementById('chart-sectors'), {
        type: 'doughnut',
        data: {
          labels: <?= json_encode($sectorLabels) ?>,
          datasets: [{
            data: <?= json_encode($sectorData) ?>,
            backgroundColor: <?= json_encode($sectorColors) ?>,
            borderWidth: 0,
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: { position: 'right' }
          }
        }
      });
    });
    </script>

  </div>
</div>

  </div>
</div>
</body>
</html>
