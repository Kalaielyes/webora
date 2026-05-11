<?php
require_once __DIR__ . '/../../models/Session.php';
Session::start();
if (Session::isLoggedIn()) {
    header('Location: ' . (Session::isAdmin() ? '../backoffice/backoffice_utilisateur.php' : 'frontoffice_utilisateur.php'));
    exit;
}
if (!Session::get('temp_2fa_user_id')) {
    header('Location: login.php');
    exit;
}
$loginErrors = Session::get('login_errors') ?? [];
Session::remove('login_errors');

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
<title>LegalFin — Vérification 2FA</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Utilisateur.css">
<style>
/* (Styles coped from login.php for consistency) */
.btn-submit { width: 100%; background: linear-gradient(135deg, var(--blue), #3A7AF0); color: #fff; border: none; border-radius: 10px; padding: 0.8rem 1rem; font-size: 0.9rem; font-weight: 600; font-family: var(--fb); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: opacity 0.18s, transform 0.12s; }
.btn-submit:hover { opacity: 0.92; transform: translateY(-1px); }
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
.brand-logo-icon svg{width:26px;height:26px;fill:var(--text);}
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
.field-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:.7rem .9rem .7rem 2.6rem;font-size:.85rem;color:var(--text);font-family:var(--fb);outline:none;transition:border .18s,background .18s; letter-spacing: 0.2em; text-align: center; padding-left:0.9rem;}
.field-input:focus{border-color:var(--blue);background:var(--surface);}
.field-input::placeholder{color:var(--muted); letter-spacing: normal;}
.field-input.is-invalid{border-color:var(--rose)!important;background:rgba(244,63,94,.04);}
.field-error{font-size:0.7rem;color:var(--rose);margin-top:0.3rem;}
.options-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;}
.btn-submit{width:100%;background:linear-gradient(135deg,var(--blue),var(--blue2));color:var(--text);}
.btn-submit:hover{opacity:.92;transform:translateY(-1px);}
@media(max-width:768px){.brand-panel{display:none;}.form-panel{padding:2rem 1.5rem;}}
</style>
<script>
  (function() {
    var t = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
</head>
<body>
<div class="bg-blob blob1"></div><div class="bg-blob blob2"></div><div class="bg-blob blob3"></div>
<div class="grid-lines"></div>
<div class="page">
  <div class="brand-panel">
    <div>
      <div class="brand-logo-icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7l-8 4-8-4v-7l8-4.32z"/></svg></div>
      <div class="brand-name">Legal<span>Fin</span></div>
      <div class="brand-tagline">Plateforme bancaire securisee</div>
    </div>
    <div>
      <div class="brand-headline">Authentification<br><em>à deux facteurs.</em></div>
      <div class="brand-desc">Veuillez entrer le code à 6 chiffres généré par votre application d'authentification pour continuer.</div>
    </div>
  </div>
  <div class="form-panel">
    <div class="form-box">
      <div class="form-title">Vérification de sécurité</div>
      <div class="form-sub">Entrez le code Google Authenticator</div>
      
      <form method="POST" action="../../controllers/AuthController.php">
        <input type="hidden" name="action" value="verify_2fa">
        <div class="field">
          <label class="field-label">Code 2FA</label>
          <div class="field-wrap">
            <input type="text" name="code" class="field-input <?= loginErrorClass('code', $loginErrors) ?>"
                   placeholder="123456" required autocomplete="off" maxlength="6" pattern="\d{6}">
          </div>
          <?= loginErrorMessage('code', $loginErrors) ?>
        </div>
        <button type="submit" class="btn-submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Vérifier
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
