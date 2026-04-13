<?php
require_once __DIR__ . '/../../model/Session.php';
Session::start();
if (Session::isLoggedIn()) {
    header('Location: ' . (Session::isAdmin() ? '../backoffice/backoffice_utilisateur.php' : 'frontoffice_utilisateur.php'));
    exit;
}
$flash = Session::getFlash();
$oldLogin = Session::get('old_login') ?? [];
Session::remove('old_login');

// Récupérer les erreurs de connexion
$loginErrors = Session::get('login_errors') ?? [];
Session::remove('login_errors');

function oldL(string $key, array $old): string {
    return htmlspecialchars($old[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

function loginErrorClass(string $field, array $errors): string {
    return isset($errors[$field]) ? 'is-invalid' : '';
}

function loginErrorMessage(string $field, array $errors): string {
    if (isset($errors[$field])) {
        return '<div class="field-error" style="font-size:0.7rem; color:var(--rose); margin-top:0.3rem;">' . htmlspecialchars($errors[$field]) . '</div>';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>NexaBank — Connexion</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Utilisateur.css">
<style>
body{align-items:center;justify-content:center;overflow:hidden;}
.bg-blob{position:fixed;border-radius:50%;pointer-events:none;z-index:0;}
.blob1{width:500px;height:500px;top:-150px;left:-150px;background:radial-gradient(circle,rgba(79,142,247,.1),transparent 70%);}
.blob2{width:400px;height:400px;bottom:-100px;right:-100px;background:radial-gradient(circle,rgba(45,212,191,.07),transparent 70%);}
.blob3{width:300px;height:300px;top:50%;left:50%;transform:translate(-50%,-50%);background:radial-gradient(circle,rgba(79,142,247,.05),transparent 70%);}
.grid-lines{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(79,142,247,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(79,142,247,.04) 1px,transparent 1px);background-size:60px 60px;}
.page{position:relative;z-index:1;display:flex;width:100%;min-height:100vh;}
.brand-panel{width:42%;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;justify-content:space-between;padding:3rem;position:relative;overflow:hidden;}
.brand-panel::before{content:'';position:absolute;top:-80px;right:-80px;width:350px;height:350px;border-radius:50%;background:radial-gradient(circle,rgba(79,142,247,.12),transparent 70%);}
.brand-panel::after{content:'';position:absolute;bottom:-60px;left:10%;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(45,212,191,.07),transparent 70%);}
.brand-logo-icon{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,var(--blue),var(--teal));display:flex;align-items:center;justify-content:center;margin-bottom:.8rem;}
.brand-logo-icon svg{width:26px;height:26px;fill:#fff;}
.brand-name{font-family:var(--fh);font-size:1.6rem;font-weight:800;letter-spacing:-.03em;}
.brand-name span{color:var(--blue);}
.brand-tagline{font-size:.78rem;color:var(--muted);margin-top:4px;}
.brand-headline{font-family:var(--fh);font-size:2.2rem;font-weight:800;line-height:1.2;letter-spacing:-.03em;margin-bottom:1rem;}
.brand-headline em{font-style:normal;background:linear-gradient(90deg,var(--blue),var(--teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.brand-desc{font-size:.85rem;color:var(--muted2);line-height:1.6;max-width:320px;}
.brand-features{display:flex;flex-direction:column;gap:.7rem;}
.feat-item{display:flex;align-items:center;gap:.75rem;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:.75rem 1rem;}
.feat-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.feat-dot.blue{background:var(--blue);}.feat-dot.teal{background:var(--teal);}.feat-dot.green{background:var(--green);}
.feat-text{font-size:.78rem;color:var(--muted2);}
.feat-text strong{color:var(--text);font-weight:500;}
.form-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:3rem;}
.form-box{width:100%;max-width:420px;animation:fadeUp .45s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.form-title{font-family:var(--fh);font-size:1.6rem;font-weight:800;letter-spacing:-.02em;}
.form-sub{font-size:.83rem;color:var(--muted);margin-top:.35rem;margin-bottom:1.5rem;}
.form-sub a{color:var(--blue);text-decoration:none;font-weight:500;}
.field{display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem;}
.field-label{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);}
.field-wrap{position:relative;display:flex;align-items:center;}
.field-icon{position:absolute;left:.9rem;color:var(--muted);display:flex;align-items:center;}
.field-icon svg{width:16px;height:16px;}
.field-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:.7rem .9rem .7rem 2.6rem;font-size:.85rem;color:var(--text);font-family:var(--fb);outline:none;transition:border .18s,background .18s;}
.field-input:focus{border-color:var(--blue);background:var(--surface);}
.field-input::placeholder{color:var(--muted);}
.field-input.is-invalid{border-color:var(--rose)!important;background:rgba(244,63,94,.04);}
.field-error{font-size:0.7rem;color:var(--rose);margin-top:0.3rem;}
.field-eye{position:absolute;right:.9rem;color:var(--muted);cursor:pointer;display:flex;align-items:center;background:none;border:none;}
.field-eye svg{width:16px;height:16px;}
.options-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;}
.check-label{display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:var(--muted2);cursor:pointer;}
.check-label input[type=checkbox]{appearance:none;width:16px;height:16px;border-radius:4px;border:1px solid var(--border2);background:var(--bg3);cursor:pointer;position:relative;flex-shrink:0;}
.check-label input[type=checkbox]:checked{background:var(--blue);border-color:var(--blue);}
.check-label input[type=checkbox]:checked::after{content:"\2713";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;font-weight:700;}
.forgot-link{font-size:.78rem;color:var(--blue);text-decoration:none;font-weight:500;}
.btn-submit{width:100%;background:linear-gradient(135deg,var(--blue),var(--blue2));color:#fff;border:none;border-radius:10px;padding:.8rem 1rem;font-size:.9rem;font-weight:600;font-family:var(--fb);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:opacity .18s,transform .12s;}
.btn-submit:hover{opacity:.92;transform:translateY(-1px);}
.flash{border-radius:10px;padding:.7rem 1rem;font-size:.78rem;display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;}
.flash svg{width:14px;height:14px;flex-shrink:0;}
.flash-error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);color:var(--rose);}
.flash-success{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);color:var(--green);}
.or-divider{display:flex;align-items:center;gap:.75rem;margin:1.2rem 0;font-size:.72rem;color:var(--muted);}
.or-divider::before,.or-divider::after{content:'';flex:1;border-top:1px solid var(--border);}
.form-foot{margin-top:1.5rem;display:flex;align-items:center;justify-content:center;gap:.5rem;font-size:.7rem;color:var(--muted);}
@media(max-width:768px){.brand-panel{display:none;}.form-panel{padding:2rem 1.5rem;}}
</style>
</head>
<body>
<div class="bg-blob blob1"></div><div class="bg-blob blob2"></div><div class="bg-blob blob3"></div>
<div class="grid-lines"></div>
<div class="page">
  <div class="brand-panel">
    <div>
      <div class="brand-logo-icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7l-8 4-8-4v-7l8-4.32z"/></svg></div>
      <div class="brand-name">Nexa<span>Bank</span></div>
      <div class="brand-tagline">Plateforme bancaire securisee</div>
    </div>
    <div>
      <div class="brand-headline">Votre banque,<br><em>reinventee.</em></div>
      <div class="brand-desc">Gerez vos comptes, effectuez des virements et suivez vos finances en temps reel.</div>
    </div>
    <div class="brand-features">
      <div class="feat-item"><div class="feat-dot blue"></div><div class="feat-text"><strong>KYC/AML</strong> Conformite reglementaire</div></div>
      <div class="feat-item"><div class="feat-dot teal"></div><div class="feat-text"><strong>Chiffrement bout-a-bout</strong> 100% protege</div></div>
      <div class="feat-item"><div class="feat-dot green"></div><div class="feat-text"><strong>Acces 24/7</strong> Partout, a tout moment</div></div>
    </div>
  </div>
  <div class="form-panel">
    <div class="form-box">
      <div class="form-title">Bienvenue</div>
      <div class="form-sub">Pas encore de compte ? <a href="signup.php">Creer un compte</a></div>
      <?php if ($flash): ?>
      <div class="flash flash-<?= $flash['type'] ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <?= $flash['type']==='success' ? '<polyline points="20 6 9 17 4 12"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' ?>
        </svg>
        <?= htmlspecialchars($flash['message']) ?>
      </div>
      <?php endif; ?>
      
      <!-- Affichage des erreurs générales -->
      <?php if (isset($loginErrors['general'])): ?>
      <div class="field-error" style="margin-bottom: 1rem; text-align: center; background: rgba(244,63,94,.08); padding: 0.5rem; border-radius: 8px;">
        <?= htmlspecialchars($loginErrors['general']) ?>
      </div>
      <?php endif; ?>
      
      <form method="POST" action="../../controller/AuthController.php">
        <input type="hidden" name="action" value="login">
        <div class="field">
          <label class="field-label">Adresse e-mail</label>
          <div class="field-wrap">
            <span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
            <input type="email" name="email" class="field-input <?= loginErrorClass('email', $loginErrors) ?>"
                   placeholder="exemple@email.com" required autocomplete="email"
                   value="<?= oldL('email', $oldLogin) ?>">
          </div>
          <?= loginErrorMessage('email', $loginErrors) ?>
        </div>
        <div class="field">
          <label class="field-label">Mot de passe</label>
          <div class="field-wrap">
            <span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
            <input type="password" name="mdp" id="mdp" class="field-input <?= loginErrorClass('mdp', $loginErrors) ?>"
                   placeholder="..." required autocomplete="current-password">
            <button type="button" class="field-eye" onclick="togglePwd()">
              <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <?= loginErrorMessage('mdp', $loginErrors) ?>
        </div>
        <div class="options-row">
          <label class="check-label"><input type="checkbox" name="remember"> Se souvenir de moi</label>
          <a href="#" class="forgot-link">Mot de passe oublie ?</a>
        </div>
        <button type="submit" class="btn-submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Se connecter
        </button>
      </form>
      <div class="or-divider">Ou</div>
      <div class="form-foot">
        <span class="badge-kyc"><span class="dot-pulse"></span> KYC Verifie</span>
        Connexion securisee SSL 256-bit
      </div>
    </div>
  </div>
</div>
<script>
function togglePwd(){
  var i=document.getElementById('mdp'),e=document.getElementById('eye-icon');
  if(i.type==='password'){i.type='text';e.innerHTML='<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';}
  else{i.type='password';e.innerHTML='<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';}
}
</script>
</body></html>