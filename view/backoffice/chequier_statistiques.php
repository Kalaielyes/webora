<?php
/**
 * Statistiques des Demandes de Chéquiers - Exact Visual Match
 */
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/config.php';

Session::requireAdmin('../frontoffice/login.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Demandes - LegaFin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/backoffice/Utilisateur.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f4f9; color: #0f1b2d; }
        
        .stats-container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        
        .header-section { margin-bottom: 30px; }
        .page-title { font-size: 24px; font-weight: 700; color: #0f1b2d; margin-bottom: 4px; }
        .page-subtitle { font-size: 14px; color: #64748b; }

        /* Period Filter - Pill Style */
        .period-filter { display: flex; gap: 8px; margin-bottom: 30px; background: #fff; padding: 4px; border-radius: 12px; width: fit-content; border: 1px solid #e5e9f0; }
        .period-btn { padding: 6px 18px; border-radius: 8px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; background: transparent; color: #64748b; transition: all 0.2s; }
        .period-btn.active { background: #3b82f6; color: #fff; }
        .period-btn:hover:not(.active) { background: #f8fafc; }

        /* KPI Cards */
        .stat-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; border: 1px solid #e5e9f0; border-radius: 12px; padding: 22px 24px; position: relative; }
        .stat-label { font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 12px; display: block; }
        .stat-value { font-size: 32px; font-weight: 700; color: #0f1b2d; line-height: 1.2; margin-bottom: 8px; }
        .stat-footer { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
        .stat-pct { font-weight: 600; font-size: 12px; }
        .stat-pct.green { color: #10b981; }
        .stat-pct.red { color: #ef4444; }
        .stat-pct.amber { color: #f59e0b; }
        
        .stat-icon { position: absolute; top: 22px; right: 24px; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .stat-icon.blue { background: #eff6ff; color: #1e40af; }
        .stat-icon.green { background: #ecfdf5; color: #065f46; }
        .stat-icon.red { background: #fef2f2; color: #991b1b; }
        .stat-icon.amber { background: #fffbeb; color: #92400e; }

        /* Charts Row */
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .chart-card { background: #fff; border: 1px solid #e5e9f0; border-radius: 12px; padding: 24px; }
        .chart-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .chart-title { font-size: 14px; font-weight: 600; color: #0f1b2d; }
        .chart-subtitle { font-size: 12px; color: #64748b; margin-top: 2px; }
        
        /* Legend Alignment */
        .custom-legend { display: flex; gap: 12px; font-size: 11px; font-weight: 500; color: #64748b; }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .legend-dot { width: 8px; height: 8px; border-radius: 50%; }

        /* Donut Center */
        .donut-wrapper { position: relative; width: 100%; height: 220px; display: flex; align-items: center; justify-content: center; }
        .donut-center { position: absolute; text-align: center; }
        .donut-center-val { font-size: 26px; font-weight: 700; color: #0f1b2d; }
        .donut-center-lbl { font-size: 11px; color: #64748b; font-weight: 500; }
        
        .donut-legend { margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
        .dl-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
        .dl-left { display: flex; align-items: center; gap: 8px; color: #64748b; }
        .dl-right { font-weight: 600; color: #0f1b2d; }

        /* Table */
        .table-card { background: #fff; border: 1px solid #e5e9f0; border-radius: 12px; overflow: hidden; margin-top: 20px; }
        .table-header { padding: 16px 24px; border-bottom: 1px solid #e5e9f0; font-size: 13px; font-weight: 600; color: #0f1b2d; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fafbfc; color: #94a3b8; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 24px; text-align: left; }
        td { padding: 14px 24px; font-size: 13px; border-top: 1px solid #e5e9f0; color: #0f1b2d; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge.green { background: #ecfdf5; color: #065f46; }
        .badge.red { background: #fef2f2; color: #991b1b; }
        .badge.amber { background: #fffbeb; color: #92400e; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/partials/sidebar_unified.php'; ?>

    <div class="main" id="main-content" style="padding:0">
        <div class="stats-container">
            <div class="header-section">
                <h1 class="page-title">Statistiques des Demandes</h1>
                <p class="page-subtitle">Vue d'ensemble des demandes de chéquiers</p>
            </div>

            <div class="period-filter">
                <button class="period-btn" onclick="filterPeriod(7, this)">7 jours</button>
                <button class="period-btn active" onclick="filterPeriod(30, this)">30 jours</button>
                <button class="period-btn" onclick="filterPeriod(90, this)">3 mois</button>
                <button class="period-btn" onclick="filterPeriod(0, this)">Tout</button>
            </div>

            <!-- KPI CARDS -->
            <div class="stat-cards">
                <div class="stat-card">
                    <span class="stat-label">Total demandes</span>
                    <div class="stat-value" id="total-val">0</div>
                    <div class="stat-footer">Toutes les demandes</div>
                    <div class="stat-icon blue"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg></div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Acceptées</span>
                    <div class="stat-value" id="accepted-val" style="color:#10b981">0</div>
                    <div class="stat-footer"><span class="stat-pct green" id="accepted-pct">0%</span> du total</div>
                    <div class="stat-icon green"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Refusées</span>
                    <div class="stat-value" id="refused-val" style="color:#ef4444">0</div>
                    <div class="stat-footer"><span class="stat-pct red" id="refused-pct">0%</span> du total</div>
                    <div class="stat-icon red"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">En attente</span>
                    <div class="stat-value" id="pending-val" style="color:#f59e0b">0</div>
                    <div class="stat-footer"><span class="stat-pct amber" id="pending-pct">0%</span> du total</div>
                    <div class="stat-icon amber"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Évolution des demandes</div>
                            <div class="chart-subtitle">Acceptées vs Refusées par jour</div>
                        </div>
                        <div class="custom-legend">
                            <div class="legend-item"><div class="legend-dot" style="background:#10b981"></div> Acceptées</div>
                            <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div> Refusées</div>
                            <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div> En attente</div>
                        </div>
                    </div>
                    <div style="height: 250px;"><canvas id="lineChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Répartition</div>
                        <div class="chart-subtitle">Par statut</div>
                    </div>
                    <div class="donut-wrapper">
                        <canvas id="donutChart"></canvas>
                        <div class="donut-center">
                            <div class="donut-center-val" id="donut-total">0</div>
                            <div class="donut-center-lbl">demandes</div>
                        </div>
                    </div>
                    <div class="donut-legend">
                        <div class="dl-row">
                            <div class="dl-left"><div class="legend-dot" style="background:#10b981"></div> Acceptées</div>
                            <div class="dl-right" id="dl-acc">0 (0%)</div>
                        </div>
                        <div class="dl-row">
                            <div class="dl-left"><div class="legend-dot" style="background:#ef4444"></div> Refusées</div>
                            <div class="dl-right" id="dl-ref">0 (0%)</div>
                        </div>
                        <div class="dl-row">
                            <div class="dl-left"><div class="legend-dot" style="background:#f59e0b"></div> En attente</div>
                            <div class="dl-right" id="dl-pen">0 (0%)</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    let allData = null;
    let lineChart = null;
    let donutChart = null;

    function pct(val, total) {
        return total ? Math.round((val / total) * 100) + '%' : '0%';
    }

    function renderData(stats, totaux, total) {
        document.getElementById('total-val').textContent = total;
        document.getElementById('accepted-val').textContent = totaux.acceptées;
        document.getElementById('refused-val').textContent = totaux.refusées;
        document.getElementById('pending-val').textContent = totaux.en_attente;
        
        document.getElementById('accepted-pct').textContent = pct(totaux.acceptées, total);
        document.getElementById('refused-pct').textContent = pct(totaux.refusées, total);
        document.getElementById('pending-pct').textContent = pct(totaux.en_attente, total);

        document.getElementById('donut-total').textContent = total;
        document.getElementById('dl-acc').textContent = totaux.acceptées + ' (' + pct(totaux.acceptées, total) + ')';
        document.getElementById('dl-ref').textContent = totaux.refusées + ' (' + pct(totaux.refusées, total) + ')';
        document.getElementById('dl-pen').textContent = totaux.en_attente + ' (' + pct(totaux.en_attente, total) + ')';

        const labels = Object.keys(stats);
        const accepted = labels.map(d => stats[d].acceptées || 0);
        const refused = labels.map(d => stats[d].refusées || 0);
        const pending = labels.map(d => stats[d].en_attente || 0);

        updateCharts(labels, accepted, refused, pending, totaux);
    }

    function updateCharts(labels, accepted, refused, pending, totaux) {
        if (lineChart) lineChart.destroy();
        if (donutChart) donutChart.destroy();

        const ctxL = document.getElementById('lineChart').getContext('2d');
        lineChart = new Chart(ctxL, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Acceptées', data: accepted, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.05)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#10b981' },
                    { label: 'Refusées', data: refused, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.05)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#ef4444' },
                    { label: 'En attente', data: pending, borderColor: '#f59e0b', backgroundColor: 'transparent', borderDash: [5, 5], fill: false, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#f59e0b' }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }, 
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 } } }, 
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } } 
                } 
            }
        });

        const ctxD = document.getElementById('donutChart').getContext('2d');
        donutChart = new Chart(ctxD, {
            type: 'doughnut',
            data: {
                labels: ['Acceptées', 'Refusées', 'En attente'],
                datasets: [{ data: [totaux.acceptées, totaux.refusées, totaux.en_attente], backgroundColor: ['#10b981', '#ef4444', '#f59e0b'], borderWidth: 0 }]
            },
            options: { cutout: '75%', plugins: { legend: { display: false } } }
        });
    }

    function updateTable(labels, stats) {
        const tbody = document.getElementById('stats-table');
        if (!labels.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:3rem;color:#94a3b8">Aucune donnée disponible</td></tr>';
            return;
        }
        tbody.innerHTML = labels.reverse().map(date => {
            const row = stats[date];
            const rowTotal = (row.acceptées || 0) + (row.refusées || 0) + (row.en_attente || 0);
            const rate = rowTotal ? Math.round((row.acceptées / rowTotal) * 100) : 0;
            const rColor = rate >= 70 ? '#10b981' : (rate >= 40 ? '#f59e0b' : '#ef4444');
            return `<tr>
                <td style="font-family:monospace; font-size:12px; color:#64748b">${date}</td>
                <td style="text-align:center"><span class="badge green">${row.acceptées}</span></td>
                <td style="text-align:center"><span class="badge red">${row.refusées}</span></td>
                <td style="text-align:center"><span class="badge amber">${row.en_attente}</span></td>
                <td style="text-align:right; font-weight:600">${rowTotal}</td>
                <td style="text-align:right; font-weight:600; color:${rColor}">${rate}%</td>
            </tr>`;
        }).join('');
    }

    function filterPeriod(days, btn) {
        document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (!allData) return;
        let { stats, totaux, total } = allData;
        if (days > 0) {
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - days);
            const filtered = {};
            const newTotaux = { acceptées: 0, refusées: 0, en_attente: 0 };
            Object.keys(stats).forEach(date => {
                if (new Date(date) >= cutoff) {
                    filtered[date] = stats[date];
                    newTotaux.acceptées += stats[date].acceptées;
                    newTotaux.refusées += stats[date].refusées;
                    newTotaux.en_attente += stats[date].en_attente;
                }
            });
            renderData(filtered, newTotaux, Object.values(newTotaux).reduce((a,b)=>a+b, 0));
        } else {
            renderData(stats, totaux, total);
        }
    }

    fetch('statistiques_logic.php')
        .then(r => r.json())
        .then(data => {
            allData = data;
            renderData(data.stats, data.totaux, data.total);
        })
        .catch(err => console.error('Error loading stats:', err));
    </script>
</body>
</html>
