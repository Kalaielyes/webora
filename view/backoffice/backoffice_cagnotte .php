<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Admin — Gestion des Dons &amp; Cagnottes</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>

<link rel="stylesheet" href="cagnotte.css">

</head>
<body>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">ADMIN</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av">AD</div>
    <div>
      <div class="sb-aname">Admin Général</div>
      <div class="sb-arole">Super administrateur</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Tableau de bord</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Vue globale
    </a>
    <div class="nav-section">Cagnottes</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Toutes les cagnottes
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      En attente validation
      <span class="nav-badge">0</span>
    </a>
    <div class="nav-section">Dons</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
      Tous les dons
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      Dons à confirmer
      <span class="nav-badge">1</span>
    </a>
    <div class="nav-section">Rapports</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Statistiques

    </a>
    <a class="nav-item" href="../frontoffice/frontoffice_cagnotte .php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      frontoffice
    </a>
  </nav>
  <div class="sb-footer">
    <div class="sb-status"><div class="status-dot"></div>Système opérationnel</div>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Cagnottes</div>
      <div class="breadcrumb">Admin / Dons &amp; Cagnottes</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input placeholder="Rechercher une cagnotte…"/>
      </div>
      <button class="btn-primary">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Valider une cagnotte
      </button>
    </div>
  </div>

  <div class="content">

    <!-- KPI -->
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--teal-light)">
          <svg width="18" height="18" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
          <div class="kpi-val">0</div>
          <div class="kpi-label">Cagnottes actives</div>
          <div class="kpi-sub" style="color:var(--teal)">↑ 0 ce mois</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <div>
          <div class="kpi-val">0</div>
          <div class="kpi-label">Dons confirmés</div>
          <div class="kpi-sub" style="color:var(--blue)">Ce mois</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="font-size:1.2rem">0</div>
          <div class="kpi-label">Total collecté (TND)</div>
          <div class="kpi-sub" style="color:var(--green)">↑ +0% vs mois dernier</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="color:var(--amber)">1</div>
          <div class="kpi-label">En attente validation</div>
          <div class="kpi-sub" style="color:var(--amber)">Action requise</div>
        </div>
      </div>
    </div>

    <!-- TABLE + DETAIL -->
    <div class="two-col-layout">

      <!-- TABLE CAGNOTTES -->
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des cagnottes</div>
          <div class="filters">
            <button class="filter-btn active">Toutes</button>
            <button class="filter-btn">Actives</button>
            <button class="filter-btn">En attente</button>
            <button class="filter-btn">Terminées</button>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Créateur / Titre</th>
              <th>Catégorie</th>
              <th>Progression</th>
              <th>Montant collecté</th>
              <th>Statut</th>
              <th>Date début</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="td-name">Mohamed Amri</div>
                <div class="td-mono">Opération cardiaque pour Sami</div>
              </td>
              <td><span class="cat-medical">Médical</span></td>
              <td>
                <div class="prog-wrap">
                  <div class="prog-bar"><div class="prog-fill" style="width:74%"></div></div>
                  <div class="prog-pct">74% — 7 400 / 10 000</div>
                </div>
              </td>
              <td><span class="td-bold">7 400,000</span></td>
              <td><span class="badge b-active"><span class="badge-dot" style="background:var(--green)"></span>Active</span></td>
              <td class="td-mono">01 mars 2024</td>
              <td>
                <div class="action-group">
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                  <button class="act-btn warn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg></button>
                  <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                </div>
              </td>
            </tr>
            
            
            
          </tbody>
        </table>
      </div>

      <!-- DETAIL PANEL -->
      <div class="detail-panel">
        <div class="dp-header">
          <div class="dp-av">MA</div>
          <div>
            <div class="dp-name">Mohamed Amri</div>
            <div class="dp-meta">Créateur — depuis mars. 2024</div>
            <div class="dp-badge" style="background:var(--green-light);color:var(--green)">Compte vérifié</div>
          </div>
        </div>

        <div>
          <div class="dp-section">Cagnotte sélectionnée</div>
          <div class="dp-row"><span class="dp-key">Titre</span><span class="dp-val">Opération User</span></div>
          <div class="dp-row"><span class="dp-key">Catégorie</span><span class="cat-medical">Médical</span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="badge b-active">Active</span></div>
          <div class="dp-row"><span class="dp-key">Date début</span><span class="dp-val">01 mars 2024</span></div>
          <div class="dp-row"><span class="dp-key">Objectif</span><span class="dp-val-mono" style="color:var(--blue)">0 TND</span></div>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-top:.5rem;">
              <span style="color:var(--teal);font-family:var(--fm);font-weight:500">7 400,000 TND collectés</span>
              <span style="color:var(--muted)">0%</span>
            </div>
            <div class="prog-detail-bar"><div class="prog-detail-fill" style="width:74%"></div></div>
          </div>
        </div>

        <div>
          <div class="dp-section">Dons récents</div>
          <div class="dp-row"><span class="dp-key">Don anonyme</span><span class="dp-val-mono" style="color:var(--teal)">+0</span></div>
          <div class="dp-row"><span class="dp-key">Mohamed Amri</span><span class="dp-val-mono" style="color:var(--teal)">+7 400,000</span></div>
          <div class="dp-row"><span class="dp-key">user</span><span class="dp-val-mono" style="color:var(--teal)">+0</span></div>
          <div class="dp-row"><span class="dp-key">Total dons</span><span class="dp-val">1 donateur</span></div>
        </div>

        <div class="dp-actions">
          <button class="dp-action-btn da-primary">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Valider
          </button>
          <button class="dp-action-btn da-neutral">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Modifier
          </button>
          <button class="dp-action-btn da-warning">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            Suspendre
          </button>
          <button class="dp-action-btn da-danger">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Clôturer
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
