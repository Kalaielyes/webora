<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques — Chéquiers</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-bg: #0f1b2d;
            --sidebar-width: 224px;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --bg: #f1f4f9;
            --surface: #ffffff;
            --border: #e5e9f0;
            --text-primary: #0f1b2d;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --green: #10b981;
            --green-bg: #ecfdf5;
            --green-text: #065f46;
            --red: #ef4444;
            --red-bg: #fef2f2;
            --red-text: #991b1b;
            --amber: #f59e0b;
            --amber-bg: #fffbeb;
            --amber-text: #92400e;
            --blue-bg: #eff6ff;
            --blue-text: #1e40af;
            --radius: 12px;
            --radius-sm: 8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
            font-size: 14px;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            padding: 0;
        }

        .sidebar-logo {
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .logo-text span { color: var(--accent); }

        .logo-badge {
            display: inline-block;
            margin-top: 4px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.08em;
            background: rgba(59,130,246,0.2);
            color: #93c5fd;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }

        .user-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .user-name { font-size: 13px; font-weight: 500; color: #fff; }
        .user-role { font-size: 11px; color: rgba(255,255,255,0.4); }

        .sidebar-section-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
            padding: 20px 20px 8px;
        }

        .sidebar-nav { flex: 1; overflow-y: auto; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 13px;
            font-weight: 400;
            border-radius: 0;
            transition: all 0.15s;
            position: relative;
            cursor: pointer;
        }

        .nav-item:hover { color: #fff; background: rgba(255,255,255,0.06); }

        .nav-item.active {
            color: #fff;
            background: rgba(59,130,246,0.15);
            font-weight: 500;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--accent);
            border-radius: 0 2px 2px 0;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--red);
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 20px;
            min-width: 18px;
            text-align: center;
        }

        .nav-icon {
            width: 16px; height: 16px;
            opacity: 0.7;
            flex-shrink: 0;
        }

        .nav-item.active .nav-icon { opacity: 1; }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 56px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-secondary);
            flex: 1;
        }

        .breadcrumb a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
        }

        .breadcrumb span { color: var(--text-muted); font-size: 13px; }
        .breadcrumb .current { color: var(--text-primary); font-weight: 600; }

        .topbar-actions { display: flex; align-items: center; gap: 10px; }

        .btn-back {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn-back:hover {
            background: var(--bg);
            color: var(--text-primary);
            border-color: #cbd5e1;
        }

        .btn-primary {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: var(--accent);
            border: none;
            border-radius: var(--radius-sm);
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        .btn-primary:hover { background: var(--accent-hover); }

        /* ── PAGE CONTENT ── */
        .page-content {
            padding: 28px;
            flex: 1;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        /* ── PERIOD FILTER ── */
        .period-filter {
            display: flex;
            align-items: center;
            gap: 4px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 3px;
            width: fit-content;
            margin-bottom: 24px;
        }

        .period-btn {
            padding: 5px 14px;
            border: none;
            background: transparent;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s;
        }

        .period-btn.active {
            background: var(--accent);
            color: #fff;
        }

        .period-btn:hover:not(.active) {
            background: var(--bg);
            color: var(--text-primary);
        }

        /* ── STAT CARDS ── */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            letter-spacing: 0.01em;
        }

        .stat-icon {
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
        }

        .stat-icon svg { width: 16px; height: 16px; }

        .stat-icon.total   { background: var(--blue-bg); }
        .stat-icon.green   { background: var(--green-bg); }
        .stat-icon.red     { background: var(--red-bg); }
        .stat-icon.amber   { background: var(--amber-bg); }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .stat-footer {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
        }

        .stat-pct {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .stat-pct.green { background: var(--green-bg); color: var(--green-text); }
        .stat-pct.red   { background: var(--red-bg);   color: var(--red-text); }
        .stat-pct.amber { background: var(--amber-bg); color: var(--amber-text); }

        /* ── CHARTS ROW ── */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .chart-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 22px;
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .chart-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .chart-subtitle {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .chart-legend {
            display: flex;
            gap: 14px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .legend-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 220px;
        }

        .donut-wrapper {
            position: relative;
            width: 100%;
            height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .donut-center {
            position: absolute;
            text-align: center;
            pointer-events: none;
        }

        .donut-center-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .donut-center-label {
            font-size: 10px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .donut-legend {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .donut-legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
        }

        .donut-legend-left {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--text-secondary);
        }

        .donut-legend-right {
            font-weight: 600;
            color: var(--text-primary);
        }

        .loading-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 220px;
            color: var(--text-muted);
            font-size: 13px;
            gap: 8px;
        }

        .spinner {
            width: 16px; height: 16px;
            border: 2px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── TABLE CARD ── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }

        .table-card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 10px 20px;
            text-align: left;
            background: #fafbfc;
            border-bottom: 1px solid var(--border);
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.1s;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fafbfc; }

        tbody td {
            padding: 11px 20px;
            font-size: 13px;
            color: var(--text-primary);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }

        .badge::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
        }

        .badge.green  { background: var(--green-bg);  color: var(--green-text); }
        .badge.green::before { background: var(--green); }
        .badge.red    { background: var(--red-bg);    color: var(--red-text); }
        .badge.red::before   { background: var(--red); }
        .badge.amber  { background: var(--amber-bg);  color: var(--amber-text); }
        .badge.amber::before { background: var(--amber); }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'SF Mono', 'Consolas', monospace; font-size: 12px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .stat-cards { grid-template-columns: repeat(2, 1fr); }
            .charts-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; }
            .stat-cards { grid-template-columns: repeat(2, 1fr); }
            .page-content { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-text">Legal<span>Fin</span></div>
        <div class="logo-badge">Back Office</div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">MN</div>
        <div>
            <div class="user-name">Mouna Ncib</div>
            <div class="user-role">Agent bancaire</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="sidebar-section-label">Mon Compte</div>

        <a href="../backoffice/index.php" class="nav-item">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            Tableau de bord
        </a>

        <a href="../chequier/list.php" class="nav-item">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
            </svg>
            Mes chéquiers
            <span class="nav-badge">22</span>
        </a>

        <a href="backoffice_chequier.php" class="nav-item">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Backoffice
            <span class="nav-badge">2</span>
        </a>

        <div class="sidebar-section-label">Actions Rapides</div>

        <a href="../frontoffice/index.php" class="nav-item">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Aller au Frontoffice
        </a>

        <a href="statistiques.php" class="nav-item active">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            Statistiques
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="breadcrumb">
            <a href="backoffice_chequier.php">Gestion des chéquiers</a>
            <span>/</span>
            <span>Admin</span>
            <span>/</span>
            <span>Chéquiers</span>
            <span>/</span>
            <span class="current">Statistiques</span>
        </div>
        <div class="topbar-actions">
            <a href="backoffice_chequier.php" class="btn-back">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                Retour au Backoffice
            </a>
            <a href="../backoffice/backoffice.php?action=new" class="btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Émettre un chéquier
            </a>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="page-content">

        <div class="page-header">
            <div class="page-title">Statistiques des Demandes</div>
            <div class="page-subtitle">Vue d'ensemble des demandes de chéquiers</div>
        </div>

        <!-- PERIOD FILTER -->
        <div class="period-filter">
            <button class="period-btn" onclick="filterPeriod(7, this)">7 jours</button>
            <button class="period-btn active" onclick="filterPeriod(30, this)">30 jours</button>
            <button class="period-btn" onclick="filterPeriod(90, this)">3 mois</button>
            <button class="period-btn" onclick="filterPeriod(0, this)">Tout</button>
        </div>

        <!-- STAT CARDS -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-label">Total demandes</div>
                    <div class="stat-icon total">
                        <svg fill="none" stroke="#1e40af" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                            <rect x="9" y="3" width="6" height="4" rx="1"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" id="total-val">—</div>
                <div class="stat-footer">Toutes les demandes</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-label">Acceptées</div>
                    <div class="stat-icon green">
                        <svg fill="none" stroke="#065f46" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" id="accepted-val" style="color: var(--green);">—</div>
                <div class="stat-footer"><span class="stat-pct green" id="accepted-pct">—%</span> du total</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-label">Refusées</div>
                    <div class="stat-icon red">
                        <svg fill="none" stroke="#991b1b" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" id="refused-val" style="color: var(--red);">—</div>
                <div class="stat-footer"><span class="stat-pct red" id="refused-pct">—%</span> du total</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-label">En attente</div>
                    <div class="stat-icon amber">
                        <svg fill="none" stroke="#92400e" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" id="pending-val" style="color: var(--amber);">—</div>
                <div class="stat-footer"><span class="stat-pct amber" id="pending-pct">—%</span> du total</div>
            </div>
        </div>

        <!-- CHARTS ROW -->
        <div class="charts-row">
            <!-- Line chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">Évolution des demandes</div>
                        <div class="chart-subtitle">Acceptées vs Refusées par jour</div>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-dot" style="background: #10b981;"></div>
                            Acceptées
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background: #ef4444;"></div>
                            Refusées
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background: #f59e0b;"></div>
                            En attente
                        </div>
                    </div>
                </div>
                <div class="chart-wrapper" id="line-loading">
                    <div class="loading-state"><div class="spinner"></div> Chargement...</div>
                </div>
                <div class="chart-wrapper" id="line-chart-wrapper" style="display:none;">
                    <canvas id="lineChart" role="img" aria-label="Évolution des demandes de chéquiers par jour"></canvas>
                </div>
            </div>

            <!-- Donut chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">Répartition</div>
                        <div class="chart-subtitle">Par statut</div>
                    </div>
                </div>
                <div class="donut-wrapper" id="donut-loading">
                    <div class="loading-state"><div class="spinner"></div></div>
                </div>
                <div id="donut-area" style="display:none;">
                    <div class="donut-wrapper" style="height:150px;">
                        <canvas id="donutChart" role="img" aria-label="Répartition des demandes par statut"></canvas>
                        <div class="donut-center">
                            <div class="donut-center-value" id="donut-total">—</div>
                            <div class="donut-center-label">demandes</div>
                        </div>
                    </div>
                    <div class="donut-legend">
                        <div class="donut-legend-row">
                            <div class="donut-legend-left">
                                <div class="legend-dot" style="background:#10b981;"></div> Acceptées
                            </div>
                            <div class="donut-legend-right" id="dl-accepted">—</div>
                        </div>
                        <div class="donut-legend-row">
                            <div class="donut-legend-left">
                                <div class="legend-dot" style="background:#ef4444;"></div> Refusées
                            </div>
                            <div class="donut-legend-right" id="dl-refused">—</div>
                        </div>
                        <div class="donut-legend-row">
                            <div class="donut-legend-left">
                                <div class="legend-dot" style="background:#f59e0b;"></div> En attente
                            </div>
                            <div class="donut-legend-right" id="dl-pending">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-card-header">
                <div class="table-card-title">Détail par date</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-center">Acceptées</th>
                        <th class="text-center">Refusées</th>
                        <th class="text-center">En attente</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Taux acceptation</th>
                    </tr>
                </thead>
                <tbody id="stats-table">
                    <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">
                        <div style="display:flex;align-items:center;justify-content:center;gap:8px;">
                            <div class="spinner"></div> Chargement des données...
                        </div>
                    </td></tr>
                </tbody>
            </table>
        </div>

    </div><!-- /page-content -->
</div><!-- /main -->

<script>
let allData = null;
let lineChart = null;
let donutChart = null;

function pct(val, total) {
    if (!total) return '0%';
    return Math.round((val / total) * 100) + '%';
}

function buildLineChart(labels, accepted, refused, pending) {
    const ctx = document.getElementById('lineChart').getContext('2d');
    if (lineChart) lineChart.destroy();
    lineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Acceptées',
                    data: accepted,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Refusées',
                    data: refused,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.06)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'En attente',
                    data: pending,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,0.06)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#f59e0b',
                    tension: 0.4,
                    fill: true,
                    borderDash: [5, 3]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f1b2d',
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.7)',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: true
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { color: '#94a3b8', font: { size: 11 }, maxRotation: 45 },
                    border: { color: 'transparent' }
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1 },
                    border: { color: 'transparent' },
                    beginAtZero: true
                }
            }
        }
    });
}

function buildDonutChart(accepted, refused, pending) {
    const ctx = document.getElementById('donutChart').getContext('2d');
    if (donutChart) donutChart.destroy();
    donutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Acceptées', 'Refusées', 'En attente'],
            datasets: [{
                data: [accepted, refused, pending],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f1b2d',
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.7)',
                    padding: 10,
                    cornerRadius: 8
                }
            }
        }
    });
}

function renderData(stats, totaux, total) {
    // Cards
    document.getElementById('total-val').textContent = total;
    document.getElementById('accepted-val').textContent = totaux.acceptées;
    document.getElementById('refused-val').textContent = totaux.refusées;
    document.getElementById('pending-val').textContent = totaux.en_attente;
    document.getElementById('accepted-pct').textContent = pct(totaux.acceptées, total);
    document.getElementById('refused-pct').textContent = pct(totaux.refusées, total);
    document.getElementById('pending-pct').textContent = pct(totaux.en_attente, total);

    // Donut center
    document.getElementById('donut-total').textContent = total;
    document.getElementById('dl-accepted').textContent = totaux.acceptées + ' (' + pct(totaux.acceptées, total) + ')';
    document.getElementById('dl-refused').textContent = totaux.refusées + ' (' + pct(totaux.refusées, total) + ')';
    document.getElementById('dl-pending').textContent = totaux.en_attente + ' (' + pct(totaux.en_attente, total) + ')';

    const labels = Object.keys(stats);
    const accepted = labels.map(d => stats[d].acceptées || 0);
    const refused = labels.map(d => stats[d].refusées || 0);
    const pending = labels.map(d => stats[d].en_attente || 0);

    // Show charts
    document.getElementById('line-loading').style.display = 'none';
    document.getElementById('line-chart-wrapper').style.display = 'block';
    document.getElementById('donut-loading').style.display = 'none';
    document.getElementById('donut-area').style.display = 'block';

    buildLineChart(labels, accepted, refused, pending);
    buildDonutChart(totaux.acceptées, totaux.refusées, totaux.en_attente);

    // Table
    const tbody = document.getElementById('stats-table');
    if (!labels.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">Aucune donnée disponible</td></tr>';
        return;
    }

    tbody.innerHTML = labels.map(date => {
        const row = stats[date];
        const rowTotal = (row.acceptées || 0) + (row.refusées || 0) + (row.en_attente || 0);
        const taux = pct(row.acceptées || 0, rowTotal);
        const tauxNum = rowTotal ? Math.round(((row.acceptées || 0) / rowTotal) * 100) : 0;
        const tauxClass = tauxNum >= 70 ? 'green' : tauxNum >= 40 ? 'amber' : 'red';
        return `<tr>
            <td class="font-mono">${date}</td>
            <td class="text-center"><span class="badge green">${row.acceptées || 0}</span></td>
            <td class="text-center"><span class="badge red">${row.refusées || 0}</span></td>
            <td class="text-center"><span class="badge amber">${row.en_attente || 0}</span></td>
            <td class="text-right" style="font-weight:600;">${rowTotal}</td>
            <td class="text-right"><span class="badge ${tauxClass}">${taux}</span></td>
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
        let newTotal = 0;

        Object.keys(stats).forEach(date => {
            if (new Date(date) >= cutoff) {
                filtered[date] = stats[date];
                newTotaux.acceptées   += stats[date].acceptées   || 0;
                newTotaux.refusées    += stats[date].refusées    || 0;
                newTotaux.en_attente  += stats[date].en_attente  || 0;
                newTotal += (stats[date].acceptées || 0) + (stats[date].refusées || 0) + (stats[date].en_attente || 0);
            }
        });
        renderData(filtered, newTotaux, newTotal);
    } else {
        renderData(stats, totaux, total);
    }
}

// Load data
fetch('statistiques_logic.php')
    .then(r => r.json())
    .then(data => {
        allData = data;
        renderData(data.stats, data.totaux, data.total);
    })
    .catch(() => {
        // Demo data for preview
        const demo = {
            stats: {
                '2026-04-25': { acceptées: 4, refusées: 1, en_attente: 1 },
                '2026-04-26': { acceptées: 5, refusées: 0, en_attente: 2 },
                '2026-04-27': { acceptées: 1, refusées: 1, en_attente: 0 },
                '2026-04-28': { acceptées: 1, refusées: 2, en_attente: 1 },
                '2026-04-29': { acceptées: 7, refusées: 2, en_attente: 0 },
                '2026-04-30': { acceptées: 1, refusées: 1, en_attente: 3 },
                '2026-05-01': { acceptées: 2, refusées: 1, en_attente: 1 },
                '2026-05-02': { acceptées: 3, refusées: 1, en_attente: 0 },
                '2026-05-03': { acceptées: 3, refusées: 1, en_attente: 0 },
            },
            totaux: { acceptées: 27, refusées: 10, en_attente: 8 },
            total: 45
        };
        allData = demo;
        renderData(demo.stats, demo.totaux, demo.total);
    });
</script>
</body>
</html>