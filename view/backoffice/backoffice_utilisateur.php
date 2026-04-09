<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>NexaBank Admin — Gestion des Utilisateurs</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="Utilisateur.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">ADMIN</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av">SA</div>
    <div>
      <div class="sb-aname">user</div>
      <div class="sb-arole">Super Admin</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Tableau de bord</div>
    
    <div class="nav-section" style="margin-top:.4rem">Gestion</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Utilisateurs
      <span class="nav-badge">3</span>
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      KYC / AML
      <span class="nav-badge">7</span>
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
      Rapports
    </a>
    <div class="nav-section" style="margin-top:.4rem">Paramètres</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      Configuration
    </a>
    <a class="nav-item" href="..\FrontOffice\frontoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      frontoffice
    </a>
  </nav>
  <div class="sb-footer">
    <div class="sb-status"><span class="status-dot"></span> Système opérationnel</div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Utilisateurs</div>
      <span style="color:var(--muted2)">·</span>
      <div class="breadcrumb">Admin / Utilisateurs</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" placeholder="Chercher un utilisateur..."/>
      </div>
      <button class="btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        Nouvel utilisateur
      </button>
    </div>
  </header>

  <div class="content">

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Total utilisateurs</div>
          <div class="kpi-sub" style="color:var(--green)">↑ +0 ce mois</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Clients actifs</div>
          <div class="kpi-sub" style="color:var(--green)">0% du total</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val" style="color:var(--amber)">0</div>
          <div class="kpi-label">KYC en attente</div>
          <div class="kpi-sub" style="color:var(--amber)">À valider</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--rose-light)">
          <svg width="18" height="18" fill="none" stroke="var(--rose)" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val" style="color:var(--rose)">0</div>
          <div class="kpi-label">Comptes bloqués</div>
          <div class="kpi-sub" style="color:var(--rose)">Risque détecté</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--purple-light)">
          <svg width="18" height="18" fill="none" stroke="var(--purple)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Administrateurs</div>
          <div class="kpi-sub" style="color:var(--muted)">3 niveaux d'accès</div>
        </div>
      </div>
    </div>

    <!-- TABLE + DETAIL PANEL -->
    <div class="two-col-layout">

      <!-- TABLE -->
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des utilisateurs</div>
          <div class="filters">
            <button class="filter-btn active">Tous</button>
            <button class="filter-btn">Clients</button>
            <button class="filter-btn">Admins</button>
            <button class="filter-btn">Bloqués</button>
            <button class="filter-btn">KYC en attente</button>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>KYC</th>
              <th>AML</th>
              <th>Statut</th>
              <th>Inscription</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#2563EB,#0D9488);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0">AB</div>
                  <div>
                    <div class="td-name">user ben user</div>
                    <div class="td-mono">CIN: 00000000</div>
                  </div>
                </div>
              </td>
              <td><span class="td-mono">user@mail.tn</span></td>
              <td><span class="r-client">Client</span></td>
              <td><span class="kyc-ok">Vérifié</span></td>
              <td><span class="kyc-ok">Conforme</span></td>
              <td><span class="badge b-actif"><span class="badge-dot" style="background:var(--green)"></span>Actif</span></td>
              <td><span class="td-mono">14/03/2022</span></td>
              <td>
                <div class="action-group">
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                  <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <!--<td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#EC4899);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0">LM</div>
                  <div>
                    <div class="td-name">Leila Mansouri</div>
                    <div class="td-mono">CIN: 11223344</div>
                  </div>
                </div>
              </td>
              <!<td><span class="td-mono">leila.mansouri@mail.tn</span></td>
              <td><span class="r-client">Client</span></td>
              <td><span class="kyc-wait">En attente</span></td>
              <td><span class="kyc-wait">En cours</span></td>
              <td><span class="badge b-attente"><span class="badge-dot" style="background:var(--amber)"></span>En attente</span></td>
              <td><span class="td-mono">02/01/2024</span></td>
              <td>
                <div class="action-group">
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                  <button class="act-btn warn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></button>
                  <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#DC2626,#F59E0B);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0">YC</div>
                  <div>
                    <div class="td-name">Yassine Chaker</div>
                    <div class="td-mono">CIN: 13445690</div>
                  </div>
                </div>
              </td>
              <td><span class="td-mono">y.chaker@mail.tn</span></td>
              <td><span class="r-client">Client</span></td>
              <td><span class="kyc-ko">Rejeté</span></td>
              <td><span class="kyc-ko">Non conforme</span></td>
              <td><span class="badge b-bloque"><span class="badge-dot" style="background:var(--rose)"></span>Bloqué</span></td>
              <td><span class="td-mono">15/09/2023</span></td>
              <td>
                <div class="action-group">
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                  <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#3B82F6,#6366F1);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0">SA</div>
                  <div>
                    <div class="td-name">ADMIN</div>
                    <div class="td-mono">ID ADMIN: ADM-001</div>
                  </div>
                </div>
              </td>
              <td><span class="td-mono">Admin@LegalFin.tn</span></td>
              <td><span class="r-admin">Super Admin</span></td>
              <td><span style="color:var(--muted);font-size:.72rem">N/A</span></td>
              <td><span style="color:var(--muted);font-size:.72rem">N/A</span></td>
              <td><span class="badge b-actif"><span class="badge-dot" style="background:var(--green)"></span>Actif</span></td>
              <td><span class="td-mono">01/01/2021</span></td>
              <td>
                <div class="action-group">
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                  <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#0D9488,#22C55E);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0">RB</div>
                  <div>
                    <div class="td-name">Rim Boukthir</div>
                    <div class="td-mono">CIN: 08765432</div>
                  </div>
                </div>
              </td> 
              <td><span class="td-mono">rim.b@mail.tn</span></td>
              <td><span class="r-client">Client</span></td>
              <td><span class="kyc-ok">Vérifié</span></td>
              <td><span class="kyc-ok">Conforme</span></td>
              <td><span class="badge b-inactif"><span class="badge-dot" style="background:var(--muted)"></span>Inactif</span></td>
              <td><span class="td-mono">30/06/2023</span></td>
              <td>
                <div class="action-group">
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                  <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                  <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                </div>
              </td> -->
            </tr>
          </tbody>
        </table>
      </div>

      <!-- DETAIL PANEL -->
      <div class="detail-panel">
        <div class="dp-header">
          <div class="dp-av">AB</div>
          <div>
            <div class="dp-name">user</div>
            <div class="dp-cin">CIN: 09856321</div>
            <div class="dp-badges">
              <span class="kyc-ok">KYC ✓</span>
              <span class="r-client">Client</span>
            </div>
          </div>
        </div>

        <div>
          <div class="dp-section">Informations personnelles</div>
          <div class="dp-row"><span class="dp-key">Prénom</span><span class="dp-val">user</span></div>
          <div class="dp-row"><span class="dp-key">Nom</span><span class="dp-val">ben user</span></div>
          <div class="dp-row"><span class="dp-key">Email</span><span class="dp-val-mono">user@mail.tn</span></div>
          <div class="dp-row"><span class="dp-key">Téléphone</span><span class="dp-val">+216 25 123 456</span></div>
          <div class="dp-row"><span class="dp-key">Date naiss.</span><span class="dp-val">12/06/1990</span></div>
          <div class="dp-row"><span class="dp-key">Adresse</span><span class="dp-val" style="font-size:.72rem;max-width:160px;text-align:right">12 Rue République, Tunis</span></div>
        </div>

        <div>
          <div class="dp-section">Compte & Statut</div>
          <div class="dp-row"><span class="dp-key">ID Utilisateur</span><span class="dp-val-mono">#NXB-00421</span></div>
          <div class="dp-row"><span class="dp-key">Statut KYC</span><span class="kyc-ok">Vérifié</span></div>
          <div class="dp-row"><span class="dp-key">Statut AML</span><span class="kyc-ok">Conforme</span></div>
          <div class="dp-row"><span class="dp-key">Statut compte</span><span class="badge b-actif" style="font-size:.65rem">Actif</span></div>
          <div class="dp-row"><span class="dp-key">Inscription</span><span class="dp-val-mono">14/03/2022</span></div>
          <div class="dp-row"><span class="dp-key">Dernière connexion</span><span class="dp-val-mono">Auj. 09:34</span></div>
        </div>

        <div>
          <div class="dp-section">Historique des actions</div>
          <div class="timeline">
            <div class="tl-item">
              <div class="tl-dot" style="background:var(--green)"></div>
              <div class="tl-line"></div>
              <div>
                <div class="tl-text">KYC validé par Admin</div>
                <div class="tl-date">15 mars 2022 · Sami A.</div>
              </div>
            </div>
            <div class="tl-item">
              <div class="tl-dot" style="background:var(--blue)"></div>
              <div class="tl-line"></div>
              <div>
                <div class="tl-text">Compte créé — Client</div>
                <div class="tl-date">14 mars 2022</div>
              </div>
            </div>
            <div class="tl-item">
              <div class="tl-dot" style="background:var(--amber)"></div>
              <div class="tl-line"></div>
              <div>
                <div class="tl-text">Plafond virement modifié</div>
                <div class="tl-date">02 jan. 2024 · Admin</div>
              </div>
            </div>
            <div class="tl-item">
              <div class="tl-dot" style="background:var(--muted2)"></div>
              <div>
                <div class="tl-text">Mot de passe réinitialisé</div>
                <div class="tl-date">22 fév. 2024</div>
              </div>
            </div>
          </div>
        </div>

        <div class="dp-actions">
          <button class="dp-action-btn da-primary">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Modifier
          </button>
          <button class="dp-action-btn da-green">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
            Valider KYC
          </button>
          <button class="dp-action-btn da-warning">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            Réinitialiser MDP
          </button>
          <button class="dp-action-btn da-danger">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Bloquer
          </button>
          <button class="dp-action-btn da-neutral" style="grid-column:1/-1">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
            Générer rapport utilisateur
          </button>
        </div>
      </div>

    </div>
  </div><!-- /content -->
</div><!-- /main -->

</body>
</html>
