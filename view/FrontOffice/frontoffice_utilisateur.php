<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Mon Profil — Espace Client LegalFin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../FrontOffice/Utilisateur.css">

</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Espace Client</div>
  </div>
  <div class="sb-user">
    <div class="sb-av">u</div>
    <div>
      <div class="sb-uname">useer</div>
      <div class="sb-uemail">user@mail.tn</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Principal</div>
    
    <div class="nav-section" style="margin-top:.5rem">Compte</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon profil
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
      Notifications
    </a>
    <a class="nav-item" href="../backoffice\backoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      backoffice
    </a>
  </nav>
  <div class="sb-footer">
    <span class="badge-kyc"><span class="dot-pulse"></span> KYC Vérifié</span>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="topbar-title">Mon Profil</div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <span class="notif-dot"></span>
      </div>
    </div>
  </header>

  <div class="content">

    <!-- PROFILE HERO -->
    <div class="profile-hero">
      <div class="profile-avatar-wrap">
        <div class="profile-avatar">AB</div>
        <div class="avatar-edit">
          <svg width="11" height="11" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
      </div>
      <div class="profile-info">
        <div class="profile-name">user</div>
        <div class="profile-cin">CIN : 09856321 &nbsp;·&nbsp; ID : #NXB-00421</div>
        <div class="profile-badges">
          <span class="pbadge pbadge-kyc">
            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
            KYC Vérifié
          </span>
          <span class="pbadge pbadge-aml">
            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            AML Conforme
          </span>
          <span class="pbadge pbadge-actif">
            <span class="dot-pulse" style="background:var(--teal)"></span>
            Compte Actif
          </span>
        </div>
        <div class="profile-joined">Membre depuis le 14 mars 2022</div>
      </div>
      <div class="profile-actions">
        <button class="btn-primary">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Modifier
        </button>
        <button class="btn-ghost">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
          Télécharger RIB
        </button>
      </div>
    </div>

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          Comptes actifs
        </div>
        <div class="stat-card-val">0</div>
        <div class="stat-card-sub">Courant · Épargne</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/></svg>
          Dernière connexion
        </div>
        <div class="stat-card-val" style="font-size:1.1rem">Auj. 09:34</div>
        <div class="stat-card-sub">Tunis, TN · Chrome / Windows</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Score conformité
        </div>
        <div class="stat-card-val" style="color:var(--green)">0 / 100</div>
        <div class="stat-card-sub">Excellent — Aucun risque détecté</div>
      </div>
    </div>

    <!-- INFORMATIONS PERSONNELLES + SÉCURITÉ -->
    <div class="two-col">
      <!-- INFORMATIONS PERSONNELLES -->
      <div>
        <div class="section-head">
          <div class="section-title">Informations personnelles</div>
          <button class="btn-ghost" style="font-size:.72rem;padding:.25rem .7rem">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Éditer
          </button>
        </div>
        <div class="info-card">
          <div class="info-grid">
            <div class="info-field">
              <div class="info-label">Nom</div>
              <div class="info-value">user</div>
            </div>
            <div class="info-field">
              <div class="info-label">Prénom</div>
              <div class="info-value">user</div>
            </div>
            <div class="info-field">
              <div class="info-label">Date de naissance</div>
              <div class="info-value">12 juin 1990</div>
            </div>
            <div class="info-field">
              <div class="info-label">Téléphone</div>
              <div class="field-edit">
                <div class="info-value">+216 00000000</div>
              </div>
            </div>
            <div class="info-field info-field-full">
              <div class="info-label">Email</div>
              <div class="field-edit">
                <div class="info-value">user@mail.tn</div>
              </div>
            </div>
            <div class="info-field info-field-full">
              <div class="info-label">Adresse</div>
              <div class="info-value">12 Rue de la République, Tunis 1001</div>
            </div>
            <div class="info-field">
              <div class="info-label">CIN</div>
              <div class="info-value mono">09856321</div>
            </div>
            <div class="info-field">
              <div class="info-label">Date de création</div>
              <div class="info-value mono">14/03/2022</div>
            </div>
          </div>
          <hr class="divider"/>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:.75rem;color:var(--muted)">Dernière modification : 02 jan. 2024</span>
            <button class="btn-primary" style="font-size:.73rem;padding:.35rem .85rem">Sauvegarder</button>
          </div>
        </div>
      </div>

      <!-- SECURITE -->
      <div>
        <div class="section-head">
          <div class="section-title">Sécurité du compte</div>
        </div>
        <div class="security-card">
          <div class="sec-item">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(79,142,247,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
              </div>
              <div>
                <div class="sec-title">Mot de passe</div>
                <div class="sec-desc">Modifié il y a 45 jours</div>
              </div>
            </div>
            <button class="btn-ghost" style="font-size:.72rem;padding:.25rem .7rem">Changer</button>
          </div>
          <div class="sec-item">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(34,197,94,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
              </div>
              <div>
                <div class="sec-title">Authentification 2FA</div>
                <div class="sec-desc">SMS · +216 25 *** 456</div>
              </div>
            </div>
            <div class="toggle on"><div class="toggle-knob"></div></div>
          </div>
          <div class="sec-item">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(245,158,11,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--amber)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
              </div>
              <div>
                <div class="sec-title">Alertes de connexion</div>
                <div class="sec-desc">Notification par e-mail</div>
              </div>
            </div>
            <div class="toggle on"><div class="toggle-knob"></div></div>
          </div>
          <div class="sec-item">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(45,212,191,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--teal)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </div>
              <div>
                <div class="sec-title">Blocage à distance</div>
                <div class="sec-desc">Désactiver toutes sessions</div>
              </div>
            </div>
            <button class="btn-ghost" style="font-size:.72rem;padding:.25rem .7rem;border-color:rgba(244,63,94,.3);color:var(--rose)">Bloquer</button>
          </div>
          <div class="sec-item">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(244,63,94,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--rose)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/><path d="M12 8v4M12 16h.01"/></svg>
              </div>
              <div>
                <div class="sec-title">Niveau de risque</div>
                <div class="sec-desc">Aucune activité suspecte</div>
              </div>
            </div>
            <span style="font-size:.7rem;font-weight:600;color:var(--green)">FAIBLE</span>
          </div>
        </div>
      </div>
    </div>

    <!-- DOCUMENTS KYC + SESSIONS ACTIVES -->
    <div class="two-col">
      <!-- DOCUMENTS KYC/AML -->
      <div>
        <div class="section-head">
          <div class="section-title">Documents KYC / AML</div>
          <button class="btn-ghost" style="font-size:.72rem;padding:.25rem .7rem">+ Ajouter</button>
        </div>
        <div class="info-card">
          <div class="doc-list">
            <div class="doc-item">
              <div class="doc-icon" style="background:rgba(79,142,247,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
              </div>
              <div>
                <div class="doc-name">Carte Nationale d'Identité</div>
                <div class="doc-date">Soumis le 14 mars 2022</div>
              </div>
              <span class="doc-status ds-ok">Vérifié</span>
            </div>
            <div class="doc-item">
              <div class="doc-icon" style="background:rgba(34,197,94,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/></svg>
              </div>
              <div>
                <div class="doc-name">Justificatif de domicile</div>
                <div class="doc-date">Soumis le 14 mars 2022</div>
              </div>
              <span class="doc-status ds-ok">Vérifié</span>
            </div>
            <div class="doc-item">
              <div class="doc-icon" style="background:rgba(245,158,11,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--amber)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
              </div>
              <div>
                <div class="doc-name">Attestation d'emploi</div>
                <div class="doc-date">Soumis le 03 jan. 2024</div>
              </div>
              <span class="doc-status ds-wait">En attente</span>
            </div>
            <div class="doc-item">
              <div class="doc-icon" style="background:rgba(244,63,94,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--rose)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
              </div>
              <div>
                <div class="doc-name">Fiche de paie (3 mois)</div>
                <div class="doc-date">Non soumis</div>
              </div>
              <span class="doc-status ds-ko">Manquant</span>
            </div>
          </div>
        </div>
      </div>

      <!-- SESSIONS ACTIVES -->
      <div>
        <div class="section-head">
          <div class="section-title">Sessions actives</div>
          <button class="btn-ghost" style="font-size:.72rem;padding:.25rem .7rem;border-color:rgba(244,63,94,.3);color:var(--rose)">Tout révoquer</button>
        </div>
        <div class="info-card">
          <div class="session-list">
            <div class="session-item">
              <div class="session-icon">
                <svg width="15" height="15" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
              </div>
              <div class="session-info">
                <div class="session-device">
                  ----
                  <span class="session-current">Session actuelle</span>
                </div>
                <div class="session-detail">Tunis, TN · 196.179.xx.xx</div>
              </div>
              <div class="session-time">Auj. 09:34</div>
            </div>
            <div class="session-item">
              <!--<div class="session-icon">
                <svg width="15" height="15" fill="none" stroke="var(--muted2)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
              </div>
              <div class="session-info">
                <div class="session-device">Safari — iPhone 14</div>
                <div class="session-detail">Sfax, TN · 41.228.xx.xx</div>
              </div>
              <div class="session-time">
                <div>Hier 22:10</div>
                <button class="btn-revoke">Révoquer</button>
              </div>
            </div>
            <div class="session-item">
              <div class="session-icon">
                <svg width="15" height="15" fill="none" stroke="var(--muted2)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
              </div>
              <div class="session-info">
                <div class="session-device">Firefox — Ubuntu 22</div>
                <div class="session-detail">Tunis, TN · 196.200.xx.xx</div>
              </div>
              <div class="session-time">
                <div>02 avr. 18:45</div>
                <button class="btn-revoke">Révoquer</button>
              </div>
            </div>-->
          </div>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

</body>
</html>
