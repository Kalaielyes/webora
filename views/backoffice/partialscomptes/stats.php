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
      
      <div class="stat-card-title" style="margin-top: 2rem;">
        <svg width="20" height="20" fill="none" stroke="#8b5cf6" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
        Type de compte
      </div>
      <div class="chart-container" style="position:relative; height:250px; width:100%; display:flex; justify-content:center;">
        <canvas id="typeComptesChart"></canvas>
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
      
      <div class="stat-card-title" style="margin-top: 2rem;">
        <svg width="20" height="20" fill="none" stroke="#ec4899" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Type de carte
      </div>
      <div class="chart-container" style="position:relative; height:250px; width:100%; display:flex; justify-content:center;">
        <canvas id="typeCartesChart"></canvas>
      </div>
    </div>

    <!-- SOLDES (COURBE) -->
    <div class="stat-card">
      <div class="stat-card-title">
        <svg width="20" height="20" fill="none" stroke="#10B981" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Répartition des actifs (<?= number_format($totalSoldeAll,0,',',' ') ?> TND)
      </div>
      <div class="chart-container" style="position:relative; height:250px; width:100%; display:flex; justify-content:center;">
        <canvas id="soldesChart"></canvas>
      </div>

      <div class="stat-card-title" style="margin-top: 2rem;">
        <svg width="20" height="20" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Solde par devise
      </div>
      <div class="chart-container" style="position:relative; height:250px; width:100%; display:flex; justify-content:center;">
        <canvas id="soldeDeviseChart"></canvas>
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
                  backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#94A3B8'],
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
              labels: ['Actives', 'En attente', 'Bloquées', 'Expirées'],
              datasets: [{
                  data: carteData,
                  backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#94A3B8'],
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
                     labels: { color: '#64748b', font: { family: 'inherit', size: 12 }, usePointStyle: true, pointStyle: 'circle', padding: 15 } 
                 } 
              },
              cutout: '82%'
          }
      });
      
      // Chart 2a: Type de compte (Doughnut)
      new Chart(document.getElementById('typeComptesChart'), {
          type: 'doughnut',
          data: {
              labels: <?= json_encode(array_map('ucfirst', array_keys($statTypeComptesData))) ?>,
              datasets: [{
                  data: <?= json_encode(array_values($statTypeComptesData)) ?>,
                  backgroundColor: ['#8b5cf6', '#3b82f6', '#06b6d4', '#14b8a6', '#f43f5e'],
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
                     labels: { color: '#64748b', font: { family: 'inherit', size: 12 }, usePointStyle: true, pointStyle: 'circle', padding: 15 } 
                 } 
              },
              cutout: '82%'
          }
      });

      // Chart 2b: Type de carte (Doughnut)
      new Chart(document.getElementById('typeCartesChart'), {
          type: 'doughnut',
          data: {
              labels: <?= json_encode(array_map('ucfirst', array_keys($statTypeCartesData))) ?>,
              datasets: [{
                  data: <?= json_encode(array_values($statTypeCartesData)) ?>,
                  backgroundColor: ['#ec4899', '#f97316', '#84cc16', '#a855f7', '#3b82f6'],
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
                     labels: { color: '#64748b', font: { family: 'inherit', size: 12 }, usePointStyle: true, pointStyle: 'circle', padding: 15 } 
                 } 
              },
              cutout: '82%'
          }
      });

      // Chart 3: Soldes (Line Chart - Courbe diagrame)
      const sCtx = document.getElementById('soldesChart').getContext('2d');
      const gradient = sCtx.createLinearGradient(0, 0, 0, 180);
      gradient.addColorStop(0, 'rgba(6, 182, 212, 0.25)');
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
                  pointRadius: 4,
                  pointHoverRadius: 6,
                  pointHoverBorderWidth: 2
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { 
                  legend: { display: false },
                  tooltip: {
                      backgroundColor: '#1e293b',
                      padding: 12,
                      titleFont: { size: 14, family: 'Syne', weight: 'bold' },
                      bodyFont: { size: 13, family: 'DM Sans' },
                      displayColors: false,
                      callbacks: {
                          label: function(c) { return ' ' + Number(c.raw).toLocaleString() + ' TND'; }
                      }
                  }
              },
              scales: {
                  y: { 
                      beginAtZero: true, 
                      grid: { color: 'rgba(241, 245, 249, 0.5)', drawBorder: false }, 
                      ticks: { 
                          color: '#64748b', 
                          font: { family: 'DM Sans', size: 11 },
                          callback: function(v) {
                              return new Intl.NumberFormat('fr-FR', { notation: 'compact', compactDisplay: 'short' }).format(v);
                          }
                      } 
                  },
                  x: { 
                      grid: { display: false }, 
                      ticks: { color: '#64748b', font: { family: 'DM Sans', weight: 600, size: 11 } } 
                  }
              }
          }
      });
      
      // Chart 3b: Solde par Devise (Doughnut)
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
                     labels: { color: '#64748b', font: { family: 'inherit', size: 12 }, usePointStyle: true, pointStyle: 'circle', padding: 15 } 
                 } 
              },
              cutout: '82%'
          }
      });
  });
  </script>
