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
    <div class="sb-av">SB</div>
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
      <span class="nav-badge">5</span>
    </a>
    <a class="nav-item" href="../frontoffice/frontoffice_chequier.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      frontoffice
    </a>
   
  </nav>
  <div class="sb-footer">
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
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Demandes en attente</div>
          <div class="kpi-sub" style="color:var(--amber)">À traiter</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Chéquiers actifs</div>
          <div class="kpi-sub" style="color:var(--blue)">En circulation</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--rose-light)">
          <svg width="18" height="18" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Chéquiers bloqués</div>
          <div class="kpi-sub" style="color:var(--rose)">Opposition / fraude</div>
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

    <!-- TABS -->
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <div class="tabs">
        <button class="tab-btn active">Demandes de chéquier</button>
        <button class="tab-btn">Chéquiers émis</button>
      </div>
      <div class="filters">
        <button class="filter-btn active">Tous</button>
        <button class="filter-btn">En attente</button>
        <button class="filter-btn">Acceptées</button>
        <button class="filter-btn">Refusées</button>
      </div>
    </div>

    <!-- TABLE + DETAIL PANEL -->
    <div class="two-col-layout">

      <!-- TABLE DEMANDES -->
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Demandes de chéquier <span style="font-size:.72rem;color:var(--muted);font-weight:400;margin-left:.4rem;">0 demandes</span></div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Client / Compte</th>
              <th>N° Demande</th>
              <th>Date demande</th>
              <th>Motif</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            
          </tbody>
        </table>
      </div>

      <!-- DETAIL PANEL -->
      <div class="detail-panel">
        <div class="dp-header">
          <div class="dp-av">AB</div>
          <div>
            <div class="dp-name">Mouna Ncib</div>
            <div class="dp-cin">CIN: 00000000</div>
            <div class="dp-kyc">KYC vérifié</div>
          </div>
        </div>

        <div class="panel-tabs">
          <button class="panel-tab active">Demande</button>
          <button class="panel-tab">Chéquier</button>
          <button class="panel-tab">Historique</button>
        </div>

        <div>
          <div class="dp-section">Demande — DEM-2024-00041</div>
          <div class="dp-row"><span class="dp-key">Date demande</span><span class="dp-val">09 avr. 2024</span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="badge b-attente" style="font-size:.65rem"><span class="badge-dot" style="background:var(--amber)"></span>En attente</span></div>
          <div class="dp-row"><span class="dp-key">Motif</span><span class="dp-val">Premier chéquier</span></div>
          <div class="dp-row"><span class="dp-key">Compte lié</span><span class="dp-val-mono">TN59 0401 … 3412</span></div>
          <div class="dp-row"><span class="dp-key">Solde compte</span><span class="dp-val" style="color:var(--blue);font-family:var(--fm)">0 TND</span></div>
        </div>

        <div>
          <div class="dp-section">Chéquier à émettre</div>
          <div class="chq-mini">
            <div class="chq-mini-num">CHQ-2024-XXXXX</div>
            <div class="chq-mini-bottom">
              <div>
                <div class="chq-mini-name">mouna ncib</div>
                <div style="font-size:.55rem;opacity:.45;margin-top:2px;">Émission: 09/04/2024</div>
              </div>
              <div style="text-align:right">
                <div class="chq-mini-feuilles">0</div>
                <div class="chq-mini-label">feuilles</div>
              </div>
            </div>
          </div>
          <div class="dp-row"><span class="dp-key">Nb. feuilles</span><span class="dp-val">0 feuilles</span></div>
          <div class="dp-row"><span class="dp-key">Date expiration</span><span class="dp-val">09 avr. 2026</span></div>
          <div class="dp-row"><span class="dp-key">Statut à émettre</span><span class="badge b-attente" style="font-size:.65rem"><span class="badge-dot" style="background:var(--amber)"></span>Actif (dès accept.)</span></div>
        </div>

<div>
  <div class="dp-section">Historique des demandes</div>
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
          <button class="dp-action-btn da-warning">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Bloquer le chéquier
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

</body>
</html>
