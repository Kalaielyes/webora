<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Cagnotte Solidaire — Espace Donateur</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="cagnotte.css">

</head>
<body>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Plateforme de collecte solidaire</div>
  </div>
  <div class="sb-user">
    <div class="sb-av">USR</div>
    <div>
      <div class="sb-uname">User</div>
      <div class="sb-uemail">user@gmail.com</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Tableau de bord</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Aperçu
    </a>
    <div class="nav-section">Cagnottes</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Mes cagnottes
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Créer une cagnotte
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      Explorer
    </a>
    <div class="nav-section">Dons</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
      Mes dons
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
      Historique
    </a>

    <a class="nav-item" href="../backoffice/backoffice_cagnotte .php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      backoffice

  </nav>
  <div class="sb-footer">
    <div class="badge-verified"><div class="dot-pulse"></div> Compte vérifié</div>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Tableau de bord</div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
      <button class="btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouvelle cagnotte
      </button>
    </div>
  </div>

  <div class="content">

    <!-- HERO STATS -->
    <div class="hero-row">
      <div class="hero-card">
        <div class="hc-label">Total collecté (mes cagnottes)</div>
        <div class="hc-val">0<span>TND</span></div>
        <div class="hc-badge">↑ +0% ce mois</div>
        <div class="hc-sub" style="margin-top:.8rem">Sur 0 cagnottes actives</div>
      </div>
      <div class="stat-mini">
        <div class="sm-label">
          <svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          Mes dons
        </div>
        <div class="sm-val" style="color:var(--blue)">0</div>
        <div class="sm-sub">Total : 0 TND</div>
      </div>
      <div class="stat-mini">
        <div class="sm-label">
          <svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          En attente
        </div>
        <div class="sm-val" style="color:var(--amber)">0</div>
        <div class="sm-sub">Confirmation requise</div>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Actions rapides</div>
      </div>
      <div class="actions-grid">
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(45,212,191,.12)">
            <svg width="20" height="20" fill="none" stroke="#2DD4BF" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div class="action-label">Créer cagnotte</div>
        </div>
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(79,142,247,.12)">
            <svg width="20" height="20" fill="none" stroke="#4F8EF7" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          </div>
          <div class="action-label">Faire un don</div>
        </div>
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(167,139,250,.12)">
            <svg width="20" height="20" fill="none" stroke="#A78BFA" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          </div>
          <div class="action-label">Don anonyme</div>
        </div>
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(245,158,11,.12)">
            <svg width="20" height="20" fill="none" stroke="#F59E0B" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
          </div>
          <div class="action-label">Historique dons</div>
        </div>
      </div>
    </div>

    <!-- CAGNOTTES -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Cagnottes en cours</div>
        <button class="btn-ghost">Explorer tout</button>
      </div>
      <div class="cagnottes-grid">

        <div class="cagnotte-card">
          <div class="cc-banner" style="background:linear-gradient(135deg,rgba(244,63,94,.15),rgba(244,63,94,.05))">🏥</div>
          <div class="cc-body">
            <div class="cc-cat medical">Médical</div>
            <div class="cc-title">Opération cardiaque pour Sami, 8 ans</div>
            <div class="cc-desc">Aide pour financer une opération urgente à l'hôpital de Tunis.</div>
            <div class="progress-bar"><div class="progress-fill" style="width:74%"></div></div>
            <div class="cc-amounts">
              <span class="cc-collected">7 400 TND</span>
              <span class="cc-goal">/ 10 000 TND</span>
            </div>
            <div class="cc-footer">
              <span class="cc-donateurs">❤️ 142 donateurs</span>
              <span class="cc-status urgent">Urgent</span>
            </div>
          </div>
        </div>

 

      </div>
    </div>

    <!-- MES DONS RÉCENTS -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Mes dons récents</div>
        <button class="btn-ghost">Voir tout</button>
      </div>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:0 1.2rem;">
        <div class="dons-list">

          <div class="don-item">
            <div class="don-icon" style="background:rgba(45,212,191,.1)">
              <svg width="16" height="16" fill="none" stroke="#2DD4BF" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
            </div>
            <div class="don-info">
              <div class="don-label">Opération cardiaque pour Sami <span class="don-tag">carte</span></div>
              <div class="don-date">05 avr. 2024 — Laisser un message ✉️</div>
            </div>
            <div><div class="don-amount">+ 100,000</div><div class="don-status status-confirme">Confirmé</div></div>
          </div>

          <div class="don-item">
            <div class="don-icon" style="background:rgba(167,139,250,.1)">
              <svg width="16" height="16" fill="none" stroke="#A78BFA" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="don-info">
              <div class="don-label">Fournitures scolaires <span class="don-tag">virement</span><span class="don-tag" style="color:var(--purple);border-color:rgba(167,139,250,.3)">anonyme</span></div>
              <div class="don-date">03 avr. 2024</div>
            </div>
            <div><div class="don-amount">+0</div><div class="don-status status-anon">Anonyme</div></div>
          </div>

          <div class="don-item">
            <div class="don-icon" style="background:rgba(245,158,11,.1)">
              <svg width="16" height="16" fill="none" stroke="#F59E0B" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="don-info">
              <div class="don-label">Aide sinistrés Nabeul <span class="don-tag">especes</span></div>
              <div class="don-date">01 avr. 2024</div>
            </div>
            <div><div class="don-amount">+ 0</div><div class="don-status status-attente">En attente</div></div>
          </div>

          

        </div>
      </div>
    </div>

  </div>
</div>

</body>
</html>
