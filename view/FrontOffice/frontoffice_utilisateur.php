<?php
require_once __DIR__ . '/../../model/Session.php';
require_once __DIR__ . '/../../model/Utilisateur.php';
Session::requireLogin('login.php');
$m    = new Utilisateur();
$user = $m->findById((int)Session::get('user_id'));
if (!$user) { Session::destroy(); header('Location: login.php'); exit; }

$flash    = Session::getFlash();
$initials = strtoupper(mb_substr($user['nom'],0,1).mb_substr($user['prenom'],0,1));

// Recuperer les anciennes valeurs du profil en cas d'erreur de validation
$oldProfil = Session::get('old_profil') ?? [];
Session::remove('old_profil');

// Determiner quel modal ouvrir automatiquement
// On detecte depuis quelle action vient l'erreur en se basant sur la cle de session
// old_profil => modal edit, erreur mot de passe => modal pwd
$openModal = '';
if (!empty($oldProfil) && $flash && $flash['type'] === 'error') {
    $openModal = 'm-edit';
}
// Pour update_password, pas d'old values (securite), mais on reafouvre le modal
// On stocke temporairement l'info dans une cle flash speciale
$pwdError = Session::get('pwd_error');
Session::remove('pwd_error');
if ($pwdError && $flash && $flash['type'] === 'error') {
    $openModal = 'm-pwd';
}

// Valeurs a afficher dans le modal edit
function oldProfil(string $key, array $old, array $user): string {
    return htmlspecialchars($old[$key] ?? $user[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mon Profil - NexaBank</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Utilisateur.css">
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
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Nexa<span>Bank</span></div>
    <div class="sb-logo-tag">Espace Client</div>
  </div>
  <div class="sb-user">
    <div class="sb-av"><?= $initials ?></div>
    <div>
      <div class="sb-uname"><?= htmlspecialchars($user['nom'].' '.$user['prenom']) ?></div>
      <div class="sb-uemail"><?= htmlspecialchars($user['email']) ?></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Compte</div>
    <a class="nav-item active" href="frontoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon profil
    </a>
    <?php if(Session::isAdmin()): ?>
    <div class="nav-section" style="margin-top:.4rem">Administration</div>
    <a class="nav-item" href="../backoffice/backoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Back Office
    </a>
    <?php endif; ?>
  </nav>
  <div class="sb-footer" style="display:flex;flex-direction:column;gap:.5rem">
    <span class="badge-kyc"><span class="dot-pulse"></span> KYC <?= $user['status_kyc'] ?></span>
    <a href="../../controller/AuthController.php?action=logout" style="font-size:.7rem;color:var(--rose);text-decoration:none">Deconnexion</a>
  </div>
</aside>
<div class="main">
  <header class="topbar">
    <div class="topbar-title">Mon Profil</div>
    <div class="topbar-right">
      <div class="notif"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg><span class="notif-dot"></span></div>
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
        <div class="profile-avatar"><?= $initials ?></div>
        <div class="avatar-edit" onclick="document.getElementById('m-edit').classList.add('open')">
          <svg width="11" height="11" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
      </div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($user['nom'].' '.$user['prenom']) ?></div>
        <div class="profile-cin">CIN : <?= htmlspecialchars($user['cin']) ?> &nbsp;·&nbsp; #NXB-<?= str_pad($user['id'],5,'0',STR_PAD_LEFT) ?></div>
        <div class="profile-badges">
          <span class="pbadge pbadge-kyc"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg> KYC <?= $user['status_kyc'] ?></span>
          <span class="pbadge pbadge-aml"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> AML <?= $user['status_aml'] ?></span>
          <span class="pbadge pbadge-actif"><span class="dot-pulse" style="background:var(--teal)"></span> <?= $user['status'] ?></span>
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
      </div>
    </div>
    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label"><svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg> Comptes actifs</div>
        <div class="stat-card-val">1</div><div class="stat-card-sub">Compte courant</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label"><svg width="13" height="13" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/></svg> Derniere connexion</div>
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
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Editer
          </button>
        </div>
        <div class="info-card">
          <div class="info-grid">
            <div class="info-field"><div class="info-label">Nom</div><div class="info-value"><?= htmlspecialchars($user['nom']) ?></div></div>
            <div class="info-field"><div class="info-label">Prenom</div><div class="info-value"><?= htmlspecialchars($user['prenom']) ?></div></div>
            <div class="info-field"><div class="info-label">Date de naissance</div><div class="info-value"><?= date('d/m/Y', strtotime($user['date_naissance'])) ?></div></div>
            <div class="info-field"><div class="info-label">Telephone</div><div class="info-value"><?= htmlspecialchars($user['numTel']) ?></div></div>
            <div class="info-field info-field-full"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($user['email']) ?></div></div>
            <div class="info-field info-field-full"><div class="info-label">Adresse</div><div class="info-value"><?= htmlspecialchars($user['adresse']) ?></div></div>
            <div class="info-field"><div class="info-label">CIN</div><div class="info-value mono"><?= htmlspecialchars($user['cin']) ?></div></div>
            <div class="info-field"><div class="info-label">Role</div><div class="info-value"><?= $user['role'] ?></div></div>
          </div>
        </div>
      </div>
      <div>
        <div class="section-head"><div class="section-title">Securite du compte</div></div>
        <div class="security-card">
          <div class="sec-item"><div class="sec-left"><div class="sec-icon" style="background:rgba(79,142,247,.1)"><svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div><div><div class="sec-title">Mot de passe</div><div class="sec-desc">Cliquez pour modifier</div></div></div><button class="btn-ghost" style="font-size:.7rem;padding:.22rem .6rem" onclick="document.getElementById('m-pwd').classList.add('open')">Changer</button></div>
          <div class="sec-item"><div class="sec-left"><div class="sec-icon" style="background:rgba(34,197,94,.1)"><svg width="16" height="16" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg></div><div><div class="sec-title">2FA SMS</div><div class="sec-desc">Authentification active</div></div></div><div class="toggle on"><div class="toggle-knob"></div></div></div>
          <div class="sec-item"><div class="sec-left"><div class="sec-icon" style="background:rgba(244,63,94,.1)"><svg width="16" height="16" fill="none" stroke="var(--rose)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/><path d="M12 8v4M12 16h.01"/></svg></div><div><div class="sec-title">Niveau de risque</div><div class="sec-desc">Aucune activite suspecte</div></div></div><span style="font-size:.7rem;font-weight:600;color:var(--green)">FAIBLE</span></div>
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
    <form method="POST" action="../../controller/UtilisateurController.php">
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
          <label class="ml">Prenom *</label>
          <input class="mi<?= (!empty($oldProfil) && empty($oldProfil['prenom'])) ? ' is-invalid' : '' ?>"
                 type="text" name="prenom"
                 value="<?= oldProfil('prenom',$oldProfil,$user) ?>"
                 required minlength="2" maxlength="50">
        </div>
      </div>
      <div class="mf">
        <label class="ml">Telephone *</label>
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
    <form method="POST" action="../../controller/UtilisateurController.php">
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

<script>
// Auto-ouvrir le modal en cas d'erreur de validation PHP
<?php if ($openModal): ?>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('<?= $openModal ?>').classList.add('open');
});
<?php endif; ?>
</script>
</body></html>
