<?php
// This partial is included inside backofficecondidature.php
// Variables from parent: $totalInvestmentsCount, $pendingInvestmentsCount, $totalInvestedAmount,
// $activeInvestorsCount, $avgInvestment, $avgTRI, $avgRetour, $validCount, $refusedInvCount,
// $topProjects, $investorsTotals, $sectors, $sectorLabels, $sectorData, $sectorColors,
// $topProjectLabels, $topProjectData, $investorLabels, $investorData
?>
<div class="stat-section">
  <div class="stat-section-title">
    <svg fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    Indicateurs Globaux
  </div>
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--blue)"></div>
      <div class="stat-icon" style="background:var(--blue-light)"><svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
      <div class="stat-val"><?= $totalInvestmentsCount ?></div>
      <div class="stat-label">Investissements total</div>
      <div class="stat-sub" style="color:var(--amber)">↑ <?= $pendingInvestmentsCount ?> en attente</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--amber)"></div>
      <div class="stat-icon" style="background:var(--amber-light)"><svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg></div>
      <div class="stat-val"><?= $pendingInvestmentsCount ?></div>
      <div class="stat-label">En attente</div>
      <div class="stat-sub" style="color:var(--amber)">Approbation requise</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--green)"></div>
      <div class="stat-icon" style="background:var(--green-light)"><svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div class="stat-val" style="font-size:1.15rem"><?= number_format($totalInvestedAmount, 0, ',', ' ') ?> TND</div>
      <div class="stat-label">Total investi</div>
      <div class="stat-sub" style="color:var(--green)">Contributions validées</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--violet)"></div>
      <div class="stat-icon" style="background:var(--violet-light)"><svg width="18" height="18" fill="none" stroke="var(--violet)" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
      <div class="stat-val"><?= $activeInvestorsCount ?></div>
      <div class="stat-label">Investisseurs actifs</div>
      <div class="stat-sub" style="color:var(--green)">Avec investissements validés</div>
    </div>
  </div>
</div>

<div class="stat-section">
  <div class="stat-section-title">
    <svg fill="none" stroke="var(--violet)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    Performance Investissements
  </div>
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--violet)"></div>
      <div class="stat-icon" style="background:var(--violet-light)"><svg width="18" height="18" fill="none" stroke="var(--violet)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
      <div class="stat-val" style="font-size:1.1rem"><?= number_format($avgInvestment, 0, ',', ' ') ?> TND</div>
      <div class="stat-label">Montant moyen investi</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--teal)"></div>
      <div class="stat-icon" style="background:var(--teal-light)"><svg width="18" height="18" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg></div>
      <div class="stat-val"><?= $avgTRI ?>%</div>
      <div class="stat-label">TRI moyen</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--amber)"></div>
      <div class="stat-icon" style="background:var(--amber-light)"><svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
      <div class="stat-val"><?= $avgRetour ?> mois</div>
      <div class="stat-label">Retour brut moyen</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-accent" style="background:var(--green)"></div>
      <div class="stat-icon" style="background:var(--green-light)"><svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div class="stat-val"><?= $validCount ?></div>
      <div class="stat-label">Investissements validés</div>
      <div class="stat-sub" style="color:var(--rose)"><?= $refusedInvCount ?> refusés</div>
    </div>
  </div>
</div>

<div class="two-chart">
  <div class="chart-card" style="position:relative;height:300px;">
    <div class="chart-title">Top Projets Financés</div>
    <canvas id="chart-top-projects"></canvas>
  </div>
  <div class="chart-card" style="position:relative;height:300px;">
    <div class="chart-title">Top Investisseurs</div>
    <canvas id="chart-top-investors"></canvas>
  </div>
</div>

<div class="chart-card" style="position:relative;height:350px;margin-top:1rem;">
  <div class="chart-title">Répartition par Secteur</div>
  <canvas id="chart-sectors"></canvas>
</div>
