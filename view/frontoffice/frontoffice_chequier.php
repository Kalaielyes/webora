<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>MonCompte — Mes Chéquiers</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="chequier.css">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Espace Client</div>
  </div>
  <div class="sb-user">
    <div class="sb-av">AB</div>
    <div>
      <div class="sb-uname">Mouna Ncib</div>
      <div class="sb-uemail">mouna.ncib@esprit.tn</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Mon compte</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      Tableau de bord
    </a>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
      Mes chéquiers
      <span class="nav-badge">1</span>
    </a>
    <a class="nav-item" href="../backoffice/backoffice_chequier.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      backoffice
    </a>
  </nav>
  <div class="sb-footer">
    <div class="badge-kyc"><span class="dot-pulse"></span>KYC Vérifié</div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes chéquiers</div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
    </div>
  </div>

  <div class="content">

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
          Chéquiers actifs
        </div>
        <div class="stat-card-val" style="color:var(--blue)">0</div>
        <div class="stat-card-sub">En circulation</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          Demandes en cours
        </div>
        <div class="stat-card-val" style="color:var(--amber)">0</div>
        <div class="stat-card-sub">En attente de traitement</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--muted)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          Feuilles restantes
        </div>
        <div class="stat-card-val">0</div>
        <div class="stat-card-sub">Sur 0 chéquiers</div>
      </div>
    </div>

    <!-- DEMANDE FORM -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Faire une demande de chéquier</div>
      </div>
      <div class="demande-card">
        <div class="info-banner">
          <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          Votre demande sera traitée sous 24 à 48h ouvrables. Vous serez notifié par e-mail dès la validation ou le refus de votre demande.
        </div>
        <div class="form-grid">
          <div class="form-field">
            <label>Compte associé</label>
            <select>
              <option>TN59 0401 0050 0712 3412 — Courant</option>
              <option>TN59 0401 0050 0712 9900 — Épargne</option>
            </select>
          </div>
          <div class="form-field">
            <label>Nombre de feuilles</label>
            <select>
              <option>25feuilles</option>
              <option>50 feuilles</option>
              <option>100 feuilles</option>
            </select>
          </div>
          <div class="form-field full">
            <label>Motif de la demande</label>
            <textarea placeholder="Ex : Premier chéquier, renouvellement, chéquier perdu..."></textarea>
          </div>
        </div>
        <div class="form-submit-row">
          <button class="btn-ghost">Annuler</button>
          <button class="btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Soumettre la demande
          </button>
        </div>
      </div>
    </div>

    <!-- MES CHEQUIER ACTIFS -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Mes chéquiers</div>
        <button class="btn-ghost">Tout voir</button>
      </div>
      <div class="chq-list">

        <!-- Chéquier 1 — Actif -->
        <div class="chq-card">
          <div class="chq-visual">
            <div class="chq-num">CHQ-2024-00087</div>
            <div class="chq-bottom">
              <div>
                <div class="chq-name">Mouna Ncib</div>
                <div style="font-size:.5rem;color:rgba(255,255,255,.35);margin-top:2px;">Exp. 15/01/2026</div>
              </div>
              <div style="text-align:right">
                <div class="chq-feuilles-val">22</div>
                <div class="chq-feuilles-label">feuilles</div>
              </div>
            </div>
          </div>
          <div class="chq-details">
            <div class="chq-details-top">
              <span class="chq-details-title">Chéquier N° CHQ-2024-00087</span>
              <span class="badge b-actif"><span class="badge-dot" style="background:var(--green)"></span>Actif</span>
            </div>
            <div class="chq-info-grid">
              <div class="ci-item"><div class="ci-label">Numéro chéquier</div><div class="ci-val" style="font-family:var(--fm);font-size:.75rem">CHQ-2024-00087</div></div>
              <div class="ci-item"><div class="ci-label">Feuilles restantes</div><div class="ci-val">22 / 25</div></div>
              <div class="ci-item"><div class="ci-label">Date création</div><div class="ci-val">15 jan. 2024</div></div>
              <div class="ci-item"><div class="ci-label">Date expiration</div><div class="ci-val">15 jan. 2026</div></div>
              <div class="ci-item"><div class="ci-label">Compte lié</div><div class="ci-val" style="font-family:var(--fm);font-size:.72rem">TN59 0401 … 3412</div></div>
              <div class="ci-item"><div class="ci-label">Demande liée</div><div class="ci-val" style="font-family:var(--fm);font-size:.72rem">DEM-2024-00012</div></div>
            </div>
            <div class="chq-actions">
              <button class="chq-act-btn ca-primary">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Détails
              </button>
              <button class="chq-act-btn ca-neutral">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                Attestation
              </button>
              <button class="chq-act-btn ca-danger">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Faire opposition
              </button>
            </div>
          </div>
        </div>

    <!-- MES DEMANDES -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Mes demandes récentes</div>
        <button class="btn-ghost">Historique</button>
      </div>
      <div class="dem-list">
        <div class="dem-item">
          <div class="dem-icon" style="background:rgba(245,158,11,.1)">
            <svg width="16" height="16" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          </div>
          <div class="dem-info">
            <div class="dem-title">Demande de chéquier — Premier chéquier</div>
            <div class="dem-meta">Soumise le 09 avr. 2024 · <span class="dem-id">DEM-2024-00041</span></div>
          </div>
          <span class="badge b-attente"><span class="badge-dot" style="background:var(--amber)"></span>En attente</span>
        </div>
        <div class="dem-item">
          <div class="dem-icon" style="background:rgba(34,197,94,.1)">
            <svg width="16" height="16" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="dem-info">
            <div class="dem-title">Demande de chéquier — Renouvellement</div>
            <div class="dem-meta">Acceptée le 10 jan. 2024 · <span class="dem-id">DEM-2024-00012</span></div>
          </div>
          <span class="badge b-acceptee"><span class="badge-dot" style="background:var(--green)"></span>Acceptée</span>
        </div>
        <div class="dem-item">
          <div class="dem-icon" style="background:rgba(244,63,94,.1)">
            <svg width="16" height="16" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </div>
          <div class="dem-info">
            <div class="dem-title">Demande de chéquier — Compte épargne</div>
            <div class="dem-meta">Refusée le 05 juin 2023 · <span class="dem-id">DEM-2023-00031</span></div>
          </div>
          <span class="badge b-refusee"><span class="badge-dot" style="background:var(--rose)"></span>Refusée</span>
        </div>
      </div>
    </div>

  </div>
</div>

</body>
</html>
