<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin Admin — Gestion des Comptes</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../backoffice/compte.css">
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
      <div class="sb-aname">ADMIN</div>
      <div class="sb-arole">Agent bancaire</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Gestion</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      Comptes bancaires
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Transactions
      
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path d="M3 17l6-6 4 4 7-7"/><path d="M3 21h18"/>
      </svg>
      Statistique
    </a>
    <a class="nav-item" href="../frontoffice/frontoffice_compte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      frontoffice
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
      <div class="page-title">Comptes bancaires</div>
      <div class="breadcrumb">/ Liste des comptes / Détail</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input placeholder="Rechercher IBAN, nom..."/>
      </div>
      <button class="btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Ouvrir un compte
      </button>
    </div>
  </div>

  <div class="content">

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Comptes actifs</div>
          <div class="kpi-sub" style="color:var(--green)">+0 ce mois</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">En attente validation</div>
          <div class="kpi-sub" style="color:var(--amber)">À traiter</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--rose-light)">
          <svg width="18" height="18" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Comptes bloqués</div>
          </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="#16A34A" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Solde total géré (TND)</div>
          <div class="kpi-sub" style="color:var(--green)">↑ 0.0%</div>
        </div>
      </div>
    </div>

    <!-- TABLE + DETAIL PANEL -->
    <div class="two-col-layout">

      <!-- TABLE -->
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des comptes</div>
          <div class="filters">
            <button class="filter-btn active">Tous</button>
            <button class="filter-btn">Actifs</button>
            <button class="filter-btn">En attente</button>
            <button class="filter-btn">Bloqués</button>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Client</th>
              <th>IBAN</th>
              <th>Type</th>
              <th>Solde (TND)</th>
              <th>Statut</th>
              <th>Carte</th>
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
            <div class="dp-name">USER</div>
            <div class="dp-cin">CIN: --------</div>
            <div class="dp-kyc">KYC vérifié</div>
          </div>
        </div>

        <div>
          <div class="dp-section">Compte</div>
          <div class="dp-row"><span class="dp-key">IBAN</span><span class="dp-val-mono">TN-- ---- ---- ---- ---- ----</span></div>
          <div class="dp-row"><span class="dp-key">Type</span><span class="dp-val">Courant</span></div>
          <div class="dp-row"><span class="dp-key">Solde</span><span class="dp-val" style="color:var(--blue);font-family:var(--fm)">0 TND</span></div>
          <div class="dp-row"><span class="dp-key">Solde dispo.</span><span class="dp-val" style="font-family:var(--fm)">0 TND</span></div>
          <div class="dp-row"><span class="dp-key">Plafond virement</span><span class="dp-val">0 TND</span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="badge b-actif" style="font-size:.65rem">Actif</span></div>
          <div class="dp-row"><span class="dp-key">Ouverture</span><span class="dp-val">--/--/----</span></div>
        </div>

        <div>
          <div class="dp-section">Carte bancaire</div>
         
        <div>
          <div class="dp-section">Dernières transactions</div>
         
        <div class="dp-actions">
          <button class="dp-action-btn da-primary">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Modifier le compte
          </button>
          <button class="dp-action-btn da-warning">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            Modifier le plafond
          </button>
          <button class="dp-action-btn da-danger">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Bloquer le compte
          </button>
          <button class="dp-action-btn da-neutral">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
            Générer relevé
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
