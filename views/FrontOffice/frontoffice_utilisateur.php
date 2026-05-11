<?php
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
Session::requireLogin('login.php');
$m    = new Utilisateur();
$user = $m->findById((int)Session::get('user_id'));
if (!$user) { Session::destroy(); header('Location: login.php'); exit; }

$flash    = Session::getFlash();
$initials = strtoupper(mb_substr($user['nom'],0,1).mb_substr($user['prenom'],0,1));

$oldProfil = Session::get('old_profil') ?? [];
Session::remove('old_profil');

$openModal = '';
if (!empty($oldProfil) && $flash && $flash['type'] === 'error') {
    $openModal = 'm-edit';
}

$pwdError = Session::get('pwd_error');
Session::remove('pwd_error');
if ($pwdError && $flash && $flash['type'] === 'error') {
    $openModal = 'm-pwd';
}

function oldProfil(string $key, array $old, array $user): string {
    return htmlspecialchars($old[$key] ?? $user[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mon Profil - LegalFin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Utilisateur.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
    // Apply theme BEFORE CSS renders to avoid flash
    (function() {
      var t = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
      var p = localStorage.getItem('privacy') === 'true';
      if(p) document.documentElement.classList.add('privacy-mode');
    })();
  </script>
  <style>
.flash{border-radius:10px;padding:.65rem 1rem;font-size:.78rem;display:flex;align-items:flex-start;gap:.6rem;line-height:1.6;}
.flash svg{width:14px;height:14px;flex-shrink:0;margin-top:2px;}
.flash-error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);color:var(--rose);}
.flash-success{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);color:var(--green);}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:100;display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border2);border-radius:14px;padding:1.8rem;width:100%;max-width:440px;position:relative;}
.modal-title{font-family:var(--fh);font-size:1rem;font-weight:700;margin-bottom:1rem;}
.modal-close{position:absolute;top:.9rem;right:.9rem;background:none;border:none;color:var(--muted);cursor:pointer;font-size:1.1rem;}
.mf{display:flex;flex-direction:column;gap:.3rem;margin-bottom:.8rem;}
.ml{font-size:.66rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);}
.mi{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.55rem .85rem;font-size:.83rem;color:var(--text);font-family:var(--fb);outline:none;width:100%;transition:border .18s;}
.mi:focus{border-color:var(--blue);}
.mi.is-invalid{border-color:var(--rose)!important;background:rgba(244,63,94,.04);}
.mfoot{display:flex;gap:.6rem;justify-content:flex-end;margin-top:1rem;}
.modal-flash{border-radius:8px;padding:.55rem .85rem;font-size:.76rem;margin-bottom:.9rem;display:flex;align-items:flex-start;gap:.5rem;line-height:1.5;}
.modal-flash svg{width:13px;height:13px;flex-shrink:0;margin-top:2px;}
.modal-flash-error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);color:var(--rose);}
</style>
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-title">Mon Profil</div>
    <div class="topbar-right">
      <div class="notif"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg><span class="notif-dot"></span></div>

      <button class="theme-toggle" id="privacy-toggle" onclick="togglePrivacy()" title="Mode Incognito" style="background:var(--bg3); border:1px solid var(--border); color:var(--text); width:36px; height:36px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.2s;">
        <svg id="privacy-icon-off" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        <svg id="privacy-icon-on" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
      </button>

      <button class="theme-toggle" id="theme-toggle" onclick="toggleTheme()" title="Changer de thème" style="background:var(--bg3); border:1px solid var(--border); color:var(--text); width:36px; height:36px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.2s;">
        <svg id="theme-icon-sun" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
        <svg id="theme-icon-moon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
      </button>
    </div>
  </header>
  <div class="content">
    <?php if($flash && $openModal === ''): ?>
    <div class="flash flash-<?= $flash['type'] ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $flash['type']==='success'?'<polyline points="20 6 9 17 4 12"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' ?></svg>
      <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
    <?php endif; ?>
    
    <!-- HERO -->
    <div class="profile-hero">
      <div class="profile-avatar-wrap">
        <?php if(!empty($user['selfie_path'])): ?>
          <div class="profile-avatar sensitive-data" style="padding:0;overflow:hidden;font-size:0"><img src="../../<?= htmlspecialchars($user['selfie_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%"></div>
        <?php else: ?>
          <div class="profile-avatar sensitive-data"><?= $initials ?></div>
        <?php endif; ?>
        <div class="avatar-edit" onclick="document.getElementById('m-edit').classList.add('open')">
          <svg width="11" height="11" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
      </div>
      <div class="profile-info">
        <div class="profile-name sensitive-data"><?= htmlspecialchars($user['nom'].' '.$user['prenom']) ?></div>
        <div class="profile-cin sensitive-data">CIN : <?= htmlspecialchars($user['cin']) ?> &nbsp;·&nbsp; #NXB-<?= str_pad($user['id'],5,'0',STR_PAD_LEFT) ?></div>
        <div class="profile-badges">
          <span class="pbadge pbadge-kyc"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg> KYC <?= $user['status_kyc'] ?></span>
          <span class="pbadge pbadge-aml"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> AML <?= $user['status_aml'] ?></span>
          <span class="pbadge pbadge-actif"><span class="dot-pulse" style="background:var(--teal)"></span> <?= $user['status'] ?></span>
          <?php if(!empty($user['id_file_path'])): ?>
          <span class="pbadge pbadge-file"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg> ID déposé</span>
          <?php endif; ?>
          <?php if(!empty($user['association'])): ?>
          <span class="pbadge pbadge-assoc"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg> Associé</span>
          <?php endif; ?>
        </div>
        <div class="profile-joined">Membre depuis le <?= date('d/m/Y', strtotime($user['date_inscription'])) ?></div>
      </div>
      <div class="profile-actions">
        <button class="btn-primary" onclick="document.getElementById('m-edit').classList.add('open')">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Modifier
        </button>
        <button class="btn-ghost" onclick="document.getElementById('m-pwd').classList.add('open')">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Mot de passe
        </button>
        <?php if(empty($user['id_file_path'])): ?>
        <button class="btn-ghost" onclick="document.getElementById('m-upload').classList.add('open')">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
          Déposer votre ID
        </button>
        <?php else: ?>
        <a class="btn-ghost" href="../../<?= htmlspecialchars($user['id_file_path']) ?>" target="_blank">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
          Voir mon ID
        </a>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label"><svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg> Comptes actifs</div>
        <div class="stat-card-val">1</div><div class="stat-card-sub">Compte courant</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label"><svg width="13" height="13" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/></svg> Dernière connexion</div>
        <div class="stat-card-val" style="font-size:1.1rem"><?= $user['derniere_connexion'] ? date('d/m H:i', strtotime($user['derniere_connexion'])) : 'Maintenant' ?></div>
        <div class="stat-card-sub">Tunis, TN</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label"><svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Statut KYC</div>
        <div class="stat-card-val" style="font-size:1rem;color:var(--<?= $user['status_kyc']==='VERIFIE'?'green':'amber' ?>)"><?= $user['status_kyc'] ?></div>
        <div class="stat-card-sub">AML: <?= $user['status_aml'] ?></div>
      </div>
    </div>
    
    <!-- INFOS + SECURITE -->
    <div class="two-col">
      <div>
        <div class="section-head">
          <div class="section-title">Informations personnelles</div>
          <button class="btn-ghost" style="font-size:.7rem;padding:.22rem .6rem" onclick="document.getElementById('m-edit').classList.add('open')">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Éditer
          </button>
        </div>
        <div class="info-card">
          <div class="info-grid">
            <div class="info-field"><div class="info-label">Nom</div><div class="info-value sensitive-data"><?= htmlspecialchars($user['nom']) ?></div></div>
            <div class="info-field"><div class="info-label">Prénom</div><div class="info-value sensitive-data"><?= htmlspecialchars($user['prenom']) ?></div></div>
            <div class="info-field"><div class="info-label">Date de naissance</div><div class="info-value sensitive-data"><?= date('d/m/Y', strtotime($user['date_naissance'])) ?></div></div>
            <div class="info-field"><div class="info-label">Téléphone</div><div class="info-value sensitive-data"><?= htmlspecialchars($user['numTel']) ?></div></div>
            <div class="info-field info-field-full"><div class="info-label">Email</div><div class="info-value sensitive-data"><?= htmlspecialchars($user['email']) ?></div></div>
            <div class="info-field info-field-full"><div class="info-label">Adresse</div><div class="info-value sensitive-data"><?= htmlspecialchars($user['adresse']) ?></div></div>
            <div class="info-field"><div class="info-label">CIN</div><div class="info-value mono sensitive-data"><?= htmlspecialchars($user['cin']) ?></div></div>
            <div class="info-field"><div class="info-label">Rôle</div><div class="info-value"><?= $user['role'] ?></div></div>
          </div>
        </div>
      </div>
      <div>
        <div class="section-head"><div class="section-title">Sécurité du compte</div></div>
        <div class="security-card">
          <div class="sec-item"><div class="sec-left"><div class="sec-icon" style="background:rgba(79,142,247,.1)"><svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div><div><div class="sec-title">Mot de passe</div><div class="sec-desc">Cliquez pour modifier</div></div></div><button class="btn-ghost" style="font-size:.7rem;padding:.22rem .6rem" onclick="document.getElementById('m-pwd').classList.add('open')">Changer</button></div>
          <div class="sec-item">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(34,197,94,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
              </div>
              <div>
                <div class="sec-title">Authentification à 2 facteurs (2FA)</div>
                <div class="sec-desc"><?= $user['two_factor_enabled'] == 1 ? 'Authentification active' : 'Authentification inactive' ?></div>
              </div>
            </div>
            <?php if($user['two_factor_enabled'] == 1): ?>
              <button class="btn-ghost" style="color:var(--rose); font-size:.7rem;padding:.22rem .6rem" onclick="document.getElementById('m-disable-2fa').classList.add('open')">Désactiver</button>
            <?php else: ?>
              <a href="2fa_setup.php" class="btn-primary" style="font-size:.7rem;padding:.22rem .6rem;text-decoration:none;">Activer</a>
            <?php endif; ?>
          </div>

          <!-- 🤳 FACE ID SETUP -->
          <div class="sec-item">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(79,142,247,.1)">
                <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/><circle cx="12" cy="12" r="3"/><path d="M16 16v1m-8-1v1"/></svg>
              </div>
              <div>
                <div class="sec-title">Connexion Face ID</div>
                <div class="sec-desc"><?= !empty($user['selfie_path']) ? 'Reconnaissance faciale active' : 'Non configurée' ?></div>
              </div>
            </div>
            <?php if(!empty($user['selfie_path'])): ?>
              <button class="btn-ghost" style="color:var(--rose); font-size:.7rem;padding:.22rem .6rem" onclick="if(confirm('Désactiver le Face ID ?')) window.location.href='../../controllers/UtilisateurController.php?action=delete_selfie_client'">Supprimer</button>
            <?php else: ?>
              <button class="btn-primary" style="font-size:.7rem;padding:.22rem .6rem" onclick="initClientFaceId()">Activer</button>
            <?php endif; ?>
          </div>
          
          <div id="face-client-zone" style="display:none; flex-direction:column; align-items:center; gap:.8rem; padding:1.5rem; background:var(--bg3); border-radius:12px; margin-top:.5rem; border:1px dashed var(--border);">
            <video id="webcam-client" autoplay playsinline style="width:100%; max-width:240px; border-radius:12px; background:#000;"></video>
            <canvas id="canvas-client" style="display:none;"></canvas>
            <div id="face-client-status" style="font-size:.7rem; color:var(--muted); text-align:center;">Centrez votre visage</div>
            <div style="display:flex; gap:.4rem; width:100%;">
              <button type="button" class="btn-primary" style="flex:1; font-size:.75rem;" id="capture-client-btn" onclick="captureClientFace()">Enregistrer mon visage</button>
              <button type="button" class="btn-ghost" style="font-size:.75rem;" onclick="cancelClientFace()">Annuler</button>
            </div>
          </div>
          
          <!-- 🌍 CARTE DE SÉCURITÉ CLIENT -->
          <div class="sec-item" style="flex-direction:column;align-items:stretch;gap:.8rem;border-bottom:none">
            <div class="sec-left">
              <div class="sec-icon" style="background:rgba(13,148,136,.1)"><svg width="16" height="16" fill="none" stroke="var(--teal)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
              <div><div class="sec-title">Dernière localisation</div><div class="sec-desc"><?= htmlspecialchars($user['last_city'] ?? 'Inconnue') ?> (IP: <?= htmlspecialchars($user['last_ip'] ?? '—') ?>)</div></div>
            </div>
            <?php if(!empty($user['last_lat'])): ?>
            <div id="user-map" style="height:120px;border-radius:10px;border:1px solid var(--border);z-index:1"></div>
            <script>
              (function(){
                var map = L.map('user-map', {zoomControl: false}).setView([<?= $user['last_lat'] ?>, <?= $user['last_long'] ?>], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker([<?= $user['last_lat'] ?>, <?= $user['last_long'] ?>]).addTo(map);
              })();
            </script>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ MODAL MODIFIER PROFIL ═══ -->
<div class="modal-overlay" id="m-edit" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-edit').classList.remove('open')">x</button>
    <div class="modal-title">Modifier mon profil</div>
    <?php if($flash && $openModal === 'm-edit'): ?>
    <div class="modal-flash modal-flash-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span><?= $flash['message'] ?></span>
    </div>
    <?php endif; ?>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="update_profil">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem">
        <div class="mf">
          <label class="ml">Nom *</label>
          <input class="mi<?= (!empty($oldProfil) && empty($oldProfil['nom'])) ? ' is-invalid' : '' ?>"
                 type="text" name="nom"
                 value="<?= oldProfil('nom',$oldProfil,$user) ?>"
                 required minlength="2" maxlength="50">
        </div>
        <div class="mf">
          <label class="ml">Prénom *</label>
          <input class="mi<?= (!empty($oldProfil) && empty($oldProfil['prenom'])) ? ' is-invalid' : '' ?>"
                 type="text" name="prenom"
                 value="<?= oldProfil('prenom',$oldProfil,$user) ?>"
                 required minlength="2" maxlength="50">
        </div>
      </div>
      <div class="mf">
        <label class="ml">Téléphone *</label>
        <input class="mi<?= (!empty($oldProfil) && empty($oldProfil['numTel'])) ? ' is-invalid' : '' ?>"
               type="tel" name="numTel"
               value="<?= oldProfil('numTel',$oldProfil,$user) ?>"
               required>
      </div>
      <div class="mf">
        <label class="ml">Adresse *</label>
        <textarea class="mi<?= (!empty($oldProfil) && empty($oldProfil['adresse'])) ? ' is-invalid' : '' ?>"
                  name="adresse" rows="2" style="resize:none" required><?= oldProfil('adresse',$oldProfil,$user) ?></textarea>
      </div>
      <div class="mfoot">
        <button type="button" class="btn-ghost" onclick="document.getElementById('m-edit').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn-primary">Sauvegarder</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL CHANGER MDP ═══ -->
<div class="modal-overlay" id="m-pwd" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-pwd').classList.remove('open')">x</button>
    <div class="modal-title">Changer le mot de passe</div>
    <?php if($flash && $openModal === 'm-pwd'): ?>
    <div class="modal-flash modal-flash-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span><?= $flash['message'] ?></span>
    </div>
    <?php endif; ?>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="update_password">
      <div class="mf"><label class="ml">Ancien mot de passe *</label><input class="mi" type="password" name="ancien_mdp" required></div>
      <div class="mf"><label class="ml">Nouveau mot de passe * <span style="font-weight:400;color:var(--muted)">(8+ car., 1 maj, 1 chiffre)</span></label><input class="mi" type="password" name="nouveau_mdp" required minlength="8"></div>
      <div class="mf"><label class="ml">Confirmer *</label><input class="mi" type="password" name="confirm_mdp" required minlength="8"></div>
      <div class="mfoot">
        <button type="button" class="btn-ghost" onclick="document.getElementById('m-pwd').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn-primary">Modifier</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL DEPOT FICHIER + SELFIE ═══ -->
<div class="modal-overlay" id="m-upload" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:500px">
    <button class="modal-close" onclick="document.getElementById('m-upload').classList.remove('open')">x</button>
    <div class="modal-title">🪪 Vérification d'Identité</div>
    <p style="font-size:.78rem;color:var(--muted);margin-bottom:1.2rem;line-height:1.5;">
      Pour valider votre identité automatiquement, veuillez déposer votre <strong>CIN/Passeport</strong> et prendre un <strong>selfie</strong>. Notre système comparera les deux images.
    </p>
    <form method="POST" action="../../controllers/UtilisateurController.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_file">

      <!-- ID Document -->
      <div class="mf">
        <label class="ml">📄 Document d'identité (CIN / Passeport) *</label>
        <input class="mi" type="file" name="file" id="id-file-input" accept=".jpg,.jpeg,.png,.gif,.pdf" required onchange="previewFile(this,'id-preview')">
        <div style="font-size:.7rem;color:var(--muted);margin-top:.3rem">Formats : JPEG, PNG, GIF, PDF. Max : 5MB.</div>
        <img id="id-preview" src="" alt="Aperçu ID" style="display:none;margin-top:.6rem;max-height:120px;border-radius:8px;border:1px solid var(--border);object-fit:cover;width:100%">
      </div>

      <!-- Selfie -->
      <div class="mf" style="margin-top:.8rem">
        <label class="ml">🤳 Selfie (votre visage visible) *</label>
        <input class="mi" type="file" name="selfie" id="selfie-input" accept="image/jpeg,image/png" capture="user" required onchange="previewFile(this,'selfie-preview')">
        <div style="font-size:.7rem;color:var(--muted);margin-top:.3rem">Format : JPEG ou PNG. Max : 5MB. Assurez-vous que votre visage est bien visible.</div>
        <img id="selfie-preview" src="" alt="Aperçu Selfie" style="display:none;margin-top:.6rem;max-height:120px;border-radius:8px;border:1px solid var(--border);object-fit:cover;width:100%">
      </div>

      <div style="background:rgba(79,142,247,.06);border:1px solid rgba(79,142,247,.2);border-radius:10px;padding:.7rem .9rem;font-size:.72rem;color:var(--blue);margin-top:1rem;line-height:1.5;">
        🔒 <strong>Sécurisé :</strong> Vos données biométriques sont utilisées uniquement pour la vérification KYC et ne sont jamais partagées avec des tiers.
      </div>

      <div class="mfoot" style="margin-top:1rem">
        <button type="button" class="btn-ghost" onclick="document.getElementById('m-upload').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn-primary">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
          Vérifier mon identité
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function previewFile(input, previewId) {
  var preview = document.getElementById(previewId);
  var file = input.files[0];
  if (file && file.type.startsWith('image/')) {
    var reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else {
    preview.style.display = 'none';
  }
}
</script>

<!-- ═══ MODAL DESACTIVER 2FA ═══ -->
<div class="modal-overlay" id="m-disable-2fa" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-disable-2fa').classList.remove('open')">x</button>
    <div class="modal-title">Désactiver le 2FA</div>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="disable_2fa">
      <div class="mf">
        <label class="ml">Mot de passe actuel *</label>
        <input class="mi" type="password" name="mdp" required placeholder="Pour des raisons de sécurité">
      </div>
      <div class="mfoot">
        <button type="button" class="btn-ghost" onclick="document.getElementById('m-disable-2fa').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn-primary" style="background:var(--rose);border-color:var(--rose);">Désactiver</button>
      </div>
    </form>
  </div>
</div>

<script>
let clientStream = null;
async function initClientFaceId() {
  try {
    clientStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
    document.getElementById('webcam-client').srcObject = clientStream;
    document.getElementById('face-client-zone').style.display = 'flex';
    document.getElementById('face-client-zone').scrollIntoView({ behavior: 'smooth' });
  } catch (err) { alert("Caméra indisponible : " + err.message); }
}
function cancelClientFace() {
  if(clientStream) clientStream.getTracks().forEach(t => t.stop());
  document.getElementById('face-client-zone').style.display = 'none';
}
async function captureClientFace() {
  const video = document.getElementById('webcam-client');
  const canvas = document.getElementById('canvas-client');
  const btn = document.getElementById('capture-client-btn');
  const status = document.getElementById('face-client-status');
  
  canvas.width = video.videoWidth; canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);
  const imageData = canvas.toDataURL('image/jpeg', 0.8);
  
  btn.disabled = true;
  status.innerText = "Traitement biométrique...";
  
  const formData = new FormData();
  formData.append('action', 'setup_face_id_biometric');
  formData.append('image', imageData);
  
  try {
    const resp = await fetch('../../controllers/UtilisateurController.php', { method:'POST', body:formData });
    const res = await resp.json();
    if(res.success) {
      status.innerHTML = "✅ Face ID activé avec succès !";
      setTimeout(() => location.reload(), 1200);
    } else {
      status.innerText = res.error;
      btn.disabled = false;
    }
  } catch (e) { status.innerText = "Erreur de connexion"; btn.disabled = false; }
}

<?php if ($openModal): ?>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('<?= $openModal ?>').classList.add('open');
});
<?php endif; ?>

// ── THEME MANAGER ────────────────────────────────────
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    var sun = document.getElementById('theme-icon-sun');
    var moon = document.getElementById('theme-icon-moon');
    if (theme === 'light') {
        if (sun) sun.style.display = 'block';
        if (moon) moon.style.display = 'none';
    } else {
        if (sun) sun.style.display = 'none';
        if (moon) moon.style.display = 'block';
    }
}
function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'dark';
    applyTheme(current === 'dark' ? 'light' : 'dark');
}
applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');

// ── PRIVACY MODE (INCOGNITO) ──
var isPrivacyActive = localStorage.getItem('privacy') === 'true';
function updatePrivacyUI() {
    document.documentElement.classList.toggle('privacy-mode', isPrivacyActive);
    var offIcon = document.getElementById('privacy-icon-off');
    var onIcon = document.getElementById('privacy-icon-on');
    if(offIcon) offIcon.style.display = isPrivacyActive ? 'none' : 'block';
    if(onIcon) onIcon.style.display = isPrivacyActive ? 'block' : 'none';
    var btn = document.getElementById('privacy-toggle');
    if(btn) btn.style.color = isPrivacyActive ? 'var(--teal)' : 'var(--text)';
    if(btn) btn.style.borderColor = isPrivacyActive ? 'var(--teal)' : 'var(--border)';
}
function togglePrivacy() {
    isPrivacyActive = !isPrivacyActive;
    localStorage.setItem('privacy', isPrivacyActive);
    updatePrivacyUI();
}
document.addEventListener('DOMContentLoaded', updatePrivacyUI);
</script>
</body>
</html>