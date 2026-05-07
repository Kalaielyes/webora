  <!-- ══ STATS TAB ════════════════════════════════ -->
  <style>
  .stats-header { margin-bottom: 2rem; }
  .stats-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; font-family: 'Syne', sans-serif; }
  .stats-subtitle { font-size: 0.85rem; color: #64748b; margin-top: 4px; }

  .stats-grid-kpi { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
  .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; transition: transform 0.2s, box-shadow 0.2s; }
  .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
  .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .kpi-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
  .kpi-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-top: 2px; }
  .kpi-value span { font-size: 0.85rem; color: #94a3b8; margin-left: 2px; }

  .stats-grid-main { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1.5rem; }
  .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 1.75rem; display: flex; flex-direction: column; }
  .stat-card-full { grid-column: 1 / -1; }
  
  .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; }
  .card-title { font-size: 1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.6rem; }
  
  .chart-wrapper { position: relative; width: 100%; height: 260px; }
  .chart-center-label { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; }
  
  .devise-list { display: flex; flex-direction: column; gap: 0.75rem; justify-content: center; }
  .devise-item { display: flex; justify-content: space-between; padding: 0.85rem 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
  .devise-name { font-weight: 700; color: #475569; font-size: 0.85rem; }
  .devise-val { font-weight: 800; color: #0f172a; font-size: 0.95rem; font-family: 'DM Mono', monospace; }

  @media (max-width: 1024px) {
    .stats-grid-kpi { grid-template-columns: 1fr; }
    .stats-grid-main { grid-template-columns: 1fr; }
  }
  </style>

  <?php
    $statComptes = ['actif'=>0,'bloque'=>0,'en_attente'=>0,'cloture'=>0,'demandes'=>0];
    $totalComptes = count($comptes);
    $statTypeComptesData = [];
    foreach($comptes as $c) {
        $st = $c['statut'];
        if ($st === 'actif') $statComptes['actif']++;
        elseif ($st === 'bloque') $statComptes['bloque']++;
        elseif ($st === 'cloture') $statComptes['cloture']++;
        else $statComptes['en_attente']++; // Includes pending, demands, etc.
        
        $typeCpt = $c['type_compte'] ?? 'courant';
        if (!isset($statTypeComptesData[$typeCpt])) $statTypeComptesData[$typeCpt] = 0;
        $statTypeComptesData[$typeCpt]++;
    }
    
    $statCartes = ['active'=>0,'inactive'=>0,'bloquee'=>0,'expiree'=>0,'demandes'=>0];
    $totalCartes = count($allCartes);
    $statTypeCartesData = ['visa'=>0, 'mastercard'=>0];
    foreach($allCartes as $ca) {
        $st = $ca->getStatut();
        if ($st === 'active') $statCartes['active']++;
        elseif ($st === 'bloquee') $statCartes['bloquee']++;
        elseif ($st === 'expiree') $statCartes['expiree']++;
        else $statCartes['inactive']++; // Includes pending, demands, etc.
        
        $typeCar = strtolower($ca->getTypeCarte() ?: 'visa');
        if ($typeCar !== 'mastercard') $typeCar = 'visa'; // Force binary categorization
        $statTypeCartesData[$typeCar]++;
    }
    
    $rates = ['TND'=>1, 'EUR'=>3.03, 'USD'=>3.12, 'GBP'=>3.85];
    $totalSoldeCourant = 0; $totalSoldeEpargne = 0; $totalSoldeDevise  = 0; $totalSoldePro     = 0;
    foreach($comptes as $c) {
        if ($c['statut'] !== 'cloture') {
            $dev = strtoupper($c['devise'] ?? 'TND');
            $rate = $rates[$dev] ?? 1.0;
            $valTnd = (float)$c['solde'] * $rate;
            
            if ($c['type_compte'] === 'courant') $totalSoldeCourant += $valTnd;
            elseif ($c['type_compte'] === 'epargne') $totalSoldeEpargne += $valTnd;
            elseif ($c['type_compte'] === 'devise') $totalSoldeDevise += $valTnd;
            elseif ($c['type_compte'] === 'professionnel') $totalSoldePro += $valTnd;
        }
    }
    $totalSoldeAll = $totalSoldeCourant + $totalSoldeEpargne + $totalSoldeDevise + $totalSoldePro;

    // -- GLOBAL FLOW CALCULATION (Ce mois) --
    $totalInAll  = 0;
    $totalOutAll = 0;
    try {
        $apiUrl = "https://sheetdb.io/api/v1/2eyctn6m5yzmz";
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $resp = curl_exec($ch);
        curl_close($ch);
        $allTxs = json_decode($resp, true) ?? [];
        $thisM = date('m'); $thisY = date('Y');
        foreach ($allTxs as $t) {
            $txDate = $t['date'] ?? '';
            if (!$txDate) continue;
            if (date('m', strtotime($txDate)) === $thisM && date('Y', strtotime($txDate)) === $thisY) {
                $amt = (float)($t['montant'] ?? 0);
                // In a global context, every transaction is both an IN and an OUT for the bank
                // but let's count 'virement' as bank activity.
                // Or better: count everything that isn't a fee/interest as 'flow'.
                // Simplified: total volume of transactions.
                if (($t['type']??'') === 'virement') $totalInAll += $amt;
            }
        }
    } catch (Exception $e) {}

    $soldeParDeviseData = ['TND'=>0, 'EUR'=>0, 'USD'=>0];
    foreach($comptes as $c) {
        if ($c['statut'] !== 'cloture') {
            $dev = strtoupper($c['devise'] ?? 'TND');
            if (!isset($soldeParDeviseData[$dev])) $soldeParDeviseData[$dev] = 0;
            $soldeParDeviseData[$dev] += (float)$c['solde'];
        }
    }
    function pct($val, $total) { return $total > 0 ? round(($val/$total)*100) : 0; }
  ?>

  <div class="stats-header">
    <h2 class="stats-title">Tableau de bord analytique</h2>
    <p class="stats-subtitle">Aperçu global des performances bancaires et de la répartition des actifs</p>
  </div>

  <div class="stats-grid-kpi">
    <div class="kpi-card">
      <div class="kpi-icon" style="background: #eff6ff; color: #2563eb;">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <div class="kpi-label">Solde Total (Global)</div>
        <div class="kpi-value"><?= number_format($totalSoldeAll, 2, '.', ' ') ?> <span>TND</span></div>
      </div>
    </div>
    
    <div class="kpi-card">
      <div class="kpi-icon" style="background: #ecfdf5; color: #10b981;">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      </div>
      <div>
        <div class="kpi-label">Entrées du mois</div>
        <div class="kpi-value" style="color: #10b981;">+<?= number_format($totalInAll, 2, '.', ' ') ?> <span>TND</span></div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon" style="background: #fff1f2; color: #f43f5e;">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
      </div>
      <div>
        <div class="kpi-label">Volume Transactions</div>
        <div class="kpi-value" style="color: #f43f5e;"><?= number_format($totalInAll, 2, '.', ' ') ?> <span>TND</span></div>
      </div>
    </div>
  </div>

  <div class="stats-grid-main">
    <!-- COMPTES STATUS -->
    <div class="stat-card">
      <div class="card-header">
        <div class="card-title">
          <svg width="20" height="20" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
          Statut des comptes (<?= $totalComptes ?>)
        </div>
      </div>
      <div class="chart-wrapper">
        <canvas id="comptesChart"></canvas>
      </div>
    </div>

    <!-- COMPTES TYPE -->
    <div class="stat-card">
      <div class="card-header">
        <div class="card-title">
          <svg width="20" height="20" fill="none" stroke="#8b5cf6" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
          Distribution par type
        </div>
      </div>
      <div class="chart-wrapper">
        <canvas id="typeComptesChart"></canvas>
      </div>
    </div>

    <!-- CARTES STATUS -->
    <div class="stat-card">
      <div class="card-header">
        <div class="card-title">
          <svg width="20" height="20" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          Statut des cartes (<?= $totalCartes ?>)
        </div>
      </div>
      <div class="chart-wrapper">
        <canvas id="cartesChart"></canvas>
      </div>
    </div>

    <!-- CARTES RESEAU -->
    <div class="stat-card">
      <div class="card-header">
        <div class="card-title">
          <svg width="20" height="20" fill="none" stroke="#ec4899" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Type de réseau
        </div>
      </div>
      <div class="chart-wrapper">
        <canvas id="typeCartesChart"></canvas>
      </div>
    </div>

    <!-- SOLDES (FULL WIDTH) -->
    <div class="stat-card stat-card-full">
      <div class="card-header">
        <div class="card-title">
          <svg width="20" height="20" fill="none" stroke="#10B981" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Répartition de la valeur par catégorie (TND)
        </div>
        <div style="font-size: 0.8rem; font-weight: 700; color: #10b981; background: #ecfdf5; padding: 4px 12px; border-radius: 99px;">
          Global: <?= number_format($totalSoldeAll, 0, '.', ' ') ?> TND
        </div>
      </div>
      
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div class="chart-wrapper" style="height: 320px;">
          <canvas id="soldesChart"></canvas>
        </div>
        
        <div class="devise-list">
          <div class="card-title" style="margin-bottom: 1rem; font-size: 0.9rem;">
            <svg width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Volume par devise
          </div>
          <div style="max-height: 120px; overflow-y: auto; padding-right: 5px;">
            <?php foreach($soldeParDeviseData as $d=>$s): if($s<=0)continue; ?>
            <div class="devise-item" style="margin-bottom: 0.5rem; padding: 0.6rem 0.8rem;">
              <span class="devise-name"><?= $d ?></span>
              <span class="devise-val"><?= number_format($s, 2, '.', ' ') ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          
          <div style="margin-top: 1rem; height: 160px;">
            <canvas id="soldeDeviseChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      // Data from PHP
      const compteData = [<?= $statComptes['actif'] ?>, <?= $statComptes['en_attente'] ?>, <?= $statComptes['bloque'] ?>, <?= $statComptes['cloture'] ?>];
      const carteData  = [<?= $statCartes['active'] ?>, <?= $statCartes['inactive'] ?>, <?= $statCartes['bloquee'] ?>, <?= $statCartes['expiree'] ?>];
      const soldeData  = [<?= $totalSoldeCourant ?>, <?= $totalSoldeEpargne ?>, <?= $totalSoldePro ?>, <?= $totalSoldeDevise ?>];
      
      const paletteStatus = ['#10B981', '#F59E0B', '#EF4444', '#94A3B8'];
      const paletteTypes  = ['#6366f1', '#8b5cf6', '#d946ef', '#f43f5e', '#06b6d4'];

      const doughnutBaseOptions = {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { 
             legend: { 
                 position: 'bottom', 
                 labels: { color: '#64748b', font: { family: 'Syne', size: 11, weight: '600' }, usePointStyle: true, pointStyle: 'circle', padding: 20 } 
             } 
          },
          cutout: '80%',
          spacing: 4
      };

      // 1. Statut Comptes
      new Chart(document.getElementById('comptesChart'), {
          type: 'doughnut',
          data: {
              labels: ['Actifs', 'En attente', 'Bloqués', 'Clôturés'],
              datasets: [{ data: compteData, backgroundColor: paletteStatus, borderWidth: 0, hoverOffset: 6, borderRadius: 6 }]
          },
          options: doughnutBaseOptions
      });

      // 2. Type Comptes
      new Chart(document.getElementById('typeComptesChart'), {
          type: 'doughnut',
          data: {
              labels: <?= json_encode(array_map('ucfirst', array_keys($statTypeComptesData))) ?>,
              datasets: [{ data: <?= json_encode(array_values($statTypeComptesData)) ?>, backgroundColor: paletteTypes, borderWidth: 0, hoverOffset: 6, borderRadius: 6 }]
          },
          options: doughnutBaseOptions
      });

      // 3. Statut Cartes
      new Chart(document.getElementById('cartesChart'), {
          type: 'doughnut',
          data: {
              labels: ['Actives', 'En attente', 'Bloquées', 'Expirées'],
              datasets: [{ data: carteData, backgroundColor: paletteStatus, borderWidth: 0, hoverOffset: 6, borderRadius: 6 }]
          },
          options: doughnutBaseOptions
      });
      
      // 4. Type Cartes
      new Chart(document.getElementById('typeCartesChart'), {
          type: 'doughnut',
          data: {
              labels: <?= json_encode(array_map('ucfirst', array_keys($statTypeCartesData))) ?>,
              datasets: [{ data: <?= json_encode(array_values($statTypeCartesData)) ?>, backgroundColor: paletteTypes, borderWidth: 0, hoverOffset: 6, borderRadius: 6 }]
          },
          options: doughnutBaseOptions
      });

      // 5. Line Chart (Répartition Actifs)
      const sCtx = document.getElementById('soldesChart').getContext('2d');
      const gradient = sCtx.createLinearGradient(0, 0, 0, 320);
      gradient.addColorStop(0, 'rgba(6, 182, 212, 0.2)');
      gradient.addColorStop(1, 'rgba(6, 182, 212, 0)');

      new Chart(sCtx, {
          type: 'line',
          data: {
              labels: ['Courant', 'Épargne', 'Pro', 'Devise'],
              datasets: [{
                  label: 'Volume (TND)',
                  data: soldeData,
                  borderColor: '#06b6d4',
                  backgroundColor: gradient,
                  borderWidth: 3,
                  fill: true,
                  tension: 0.45,
                  pointBackgroundColor: '#fff',
                  pointBorderColor: '#06b6d4',
                  pointBorderWidth: 2,
                  pointRadius: 5,
                  pointHoverRadius: 7,
                  pointHoverBorderWidth: 3
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { 
                  legend: { display: false },
                  tooltip: {
                      backgroundColor: '#0f172a', padding: 12, borderRadius: 12,
                      titleFont: { size: 13, family: 'Syne', weight: 'bold' },
                      bodyFont: { size: 12, family: 'DM Sans' },
                      displayColors: false,
                      callbacks: { label: (c) => ` ${Number(c.raw).toLocaleString()} TND` }
                  }
              },
              scales: {
                  y: { 
                      beginAtZero: true, 
                      grid: { color: 'rgba(226, 232, 240, 0.5)', drawBorder: false },
                      ticks: { color: '#94a3b8', font: { size: 10 }, callback: (v) => v.toLocaleString() }
                  },
                  x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Syne', weight: '700', size: 11 } } }
              }
          }
      });

      // 6. Solde par Devise (Doughnut compact)
      new Chart(document.getElementById('soldeDeviseChart'), {
          type: 'doughnut',
          data: {
              labels: <?= json_encode(array_keys($soldeParDeviseData)) ?>,
              datasets: [{
                  data: <?= json_encode(array_values($soldeParDeviseData)) ?>,
                  backgroundColor: ['#6366f1', '#14b8a6', '#f59e0b', '#ec4899', '#8b5cf6'],
                  borderWidth: 0,
                  hoverOffset: 8,
                  borderRadius: 10,
                  spacing: 4
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { 
                 legend: { 
                     position: 'bottom', 
                     labels: { color: '#64748b', font: { family: 'Syne', size: 10, weight: '600' }, usePointStyle: true, pointStyle: 'circle', padding: 12 } 
                 } 
              },
              cutout: '82%'
          }
      });
  });
  </script>
