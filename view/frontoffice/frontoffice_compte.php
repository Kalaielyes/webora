<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>MonCompte — Espace Client</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../frontoffice/compte.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Espace client</div>
  </div>
  <div class="sb-user">
    <div class="sb-av">AB</div>
    <div>
      <div class="sb-uname">USER</div>
      <div class="sb-uemail">USER@email.tn</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Mon espace</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      Mes comptes
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Nouvelle transaction
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
      Historique transaction
    </a>
    <div class="nav-section">Ma carte</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      Gérer ma carte
    </a>
    <a class="nav-item" href="../backoffice/backoffice_compte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      backoffice
    </a>
   
  </nav>
  <div class="sb-footer">
    <div class="badge-kyc"><span class="dot-pulse"></span>Identité vérifiée</div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes comptes bancaires</div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
    </div>
  </div>

  <div class="content">

    <!-- BALANCE CARD -->
    <div class="balance-card">
      <div class="bc-top">
        <div>
          <div class="bc-label">Solde disponible</div>
          <div class="bc-amount">0<span>TND</span></div>
          <!--<div class="bc-change">▲ +0 TND ce mois</div>-->
        </div>
        <div class="bc-type-badge">Compte ------</div>
      </div>
      <div class="bc-iban-row">
        <div>
          <div class="bc-iban-label">IBAN</div>
          <div class="bc-iban">TN-- ---- ---- ---- ---- ----</div>
        </div>
        <div style="text-align:right">
          <div class="bc-iban-label">Date d'ouverture</div>
          <div style="font-size:.82rem;color:var(--muted2)">--/--/----</div>
        </div>
      </div>
    </div>

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="14" height="14" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
          Dépenses ce mois
        </div>
        <div class="stat-card-val" style="color:var(--rose)">0 TND</div>
        <div class="stat-card-sub">0 transactions</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="14" height="14" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
          Revenus ce mois
        </div>
        <div class="stat-card-val" style="color:var(--green)">0 TND</div>
        <div class="stat-card-sub">0 virements reçus</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="14" height="14" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/></svg>
          En attente
        </div>
        <div class="stat-card-val" style="color:var(--amber)">0 TND</div>
        <div class="stat-card-sub">0 opérations</div>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Actions rapides</div>
      </div>
      <div class="actions-grid">
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(79,142,247,.12)">
            <svg width="20" height="20" fill="none" stroke="#4F8EF7" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          </div>
          <div class="action-label">Virement</div>
        </div>
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(34,197,94,.12)">
            <svg width="20" height="20" fill="none" stroke="#22C55E" stroke-width="1.8" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
          </div>
          <div class="action-label">Dépôt</div>
        </div>
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(245,158,11,.12)">
            <svg width="20" height="20" fill="none" stroke="#F59E0B" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="action-label">Payer par carte</div>
        </div>
        <div class="action-btn">
          <div class="action-icon" style="background:rgba(45,212,191,.12)">
            <svg width="20" height="20" fill="none" stroke="#2DD4BF" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
          </div>
          <div class="action-label">Relevé PDF</div>
        </div>
      </div>
    </div>

    
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Ma carte bancaire</div>
        <button class="btn-ghost">Gérer</button>
      </div>
      
    <!-- TRANSACTIONS -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Dernières transactions</div>
        <button class="btn-ghost">Voir tout</button>
      </div>
      
  </div>
</div>

</body>
</html>
