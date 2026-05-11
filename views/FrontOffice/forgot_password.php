<?php
// =============================================================
//  view/FrontOffice/forgot_password.php — NexaBank
//  Étape 1 : Saisie de l'email pour recevoir le lien WhatsApp
// =============================================================
require_once __DIR__ . '/../../models/Session.php';
Session::start();

if (Session::isLoggedIn()) { header('Location: frontoffice_utilisateur.php'); exit; }

$flash  = Session::getFlash();
$errors = Session::get('forgot_errors') ?? [];
$old    = Session::get('old_forgot')    ?? [];
Session::remove('forgot_errors');
Session::remove('old_forgot');

function fVal(string $k, array $o): string { return htmlspecialchars($o[$k] ?? '', ENT_QUOTES, 'UTF-8'); }
function fErr(string $k, array $e): string {
    return isset($e[$k]) ? '<div class="field-error">' . htmlspecialchars($e[$k]) . '</div>' : '';
}
function fCls(string $k, array $e): string { return isset($e[$k]) ? 'field-input is-invalid' : 'field-input'; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>NexaBank — Mot de passe oublié</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Utilisateur.css">
<style>
body{align-items:center;justify-content:center;overflow:hidden;}
.bg-blob{position:fixed;border-radius:50%;pointer-events:none;z-index:0;}
.blob1{width:500px;height:500px;top:-150px;left:-150px;background:radial-gradient(circle,rgba(79,142,247,.1),transparent 70%);}
.blob2{width:400px;height:400px;bottom:-100px;right:-100px;background:radial-gradient(circle,rgba(45,212,191,.07),transparent 70%);}
.grid-lines{position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:linear-gradient(rgba(79,142,247,.04) 1px,transparent 1px),
  linear-gradient(90deg,rgba(79,142,247,.04) 1px,transparent 1px);background-size:60px 60px;}
.page{position:relative;z-index:1;display:flex;width:100%;min-height:100vh;align-items:center;justify-content:center;}

.card{width:100%;max-width:440px;background:var(--bg2);border:1px solid var(--border2);border-radius:18px;
  padding:2.5rem 2rem;position:relative;overflow:hidden;
  animation:fadeUp .45s ease both;box-shadow:0 24px 80px rgba(0,0,0,.4);}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.card::before{content:'';position:absolute;top:-80px;right:-80px;width:280px;height:280px;border-radius:50%;
  background:radial-gradient(circle,rgba(79,142,247,.1),transparent 70%);}

/* Icône */
.icon-wrap{width:64px;height:64px;border-radius:50%;
  background:rgba(37,211,102,.1);border:2px solid rgba(37,211,102,.2);
  display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;position:relative;}
.icon-wrap svg{width:34px;height:34px;}

.card-title{font-family:var(--fh);font-size:1.35rem;font-weight:800;letter-spacing:-.02em;text-align:center;margin-bottom:.4rem;}
.card-desc{font-size:.81rem;color:var(--muted2);text-align:center;line-height:1.65;margin-bottom:1.8rem;}
.card-desc strong{color:var(--teal);}

/* Champs */
.field{display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem;}
.field-label{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);}
.field-wrap{position:relative;display:flex;align-items:center;}
.field-icon{position:absolute;left:.9rem;color:var(--muted);display:flex;align-items:center;}
.field-icon svg{width:16px;height:16px;}
.field-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;
  padding:.7rem .9rem .7rem 2.6rem;font-size:.85rem;color:var(--text);font-family:var(--fb);
  outline:none;transition:border .18s,background .18s;}
.field-input:focus{border-color:var(--blue);background:var(--surface);}
.field-input::placeholder{color:var(--muted);}
.field-input.is-invalid{border-color:var(--rose)!important;background:rgba(244,63,94,.04);}
.field-error{font-size:.7rem;color:var(--rose);margin-top:.2rem;}

/* Bouton WhatsApp */
.btn-wa{width:100%;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;
  border:none;border-radius:10px;padding:.82rem 1rem;font-size:.9rem;font-weight:600;
  font-family:var(--fb);cursor:pointer;display:flex;align-items:center;justify-content:center;
  gap:.6rem;transition:opacity .18s,transform .12s;margin-top:.4rem;}
.btn-wa:hover{opacity:.92;transform:translateY(-1px);}
.btn-wa:active{transform:translateY(0);}
.btn-wa svg{width:20px;height:20px;fill:#fff;flex-shrink:0;}

/* Flash */
.flash{border-radius:10px;padding:.75rem 1rem;font-size:.78rem;display:flex;
  flex-direction:column;gap:.3rem;margin-bottom:1.2rem;}
.flash-inner{display:flex;align-items:center;gap:.6rem;}
.flash svg{width:14px;height:14px;flex-shrink:0;}
.flash-error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);color:var(--rose);}
.flash-success{background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.25);color:#1a9e4d;}
.flash-success .flash-note{font-size:.74rem;color:var(--muted);padding-left:1.3rem;}

/* Timer badge */
.timer-badge{display:inline-flex;align-items:center;gap:.4rem;
  background:rgba(79,142,247,.08);border:1px solid rgba(79,142,247,.2);
  color:var(--blue);border-radius:99px;padding:3px 10px;font-size:.68rem;font-weight:600;
  margin:0 auto .3rem;width:fit-content;}
.timer-badge svg{width:12px;height:12px;}

.note{margin-top:1.3rem;padding-top:1.1rem;border-top:1px solid var(--border);
  font-size:.72rem;color:var(--muted);text-align:center;line-height:1.7;}
.note a{color:var(--blue);text-decoration:none;font-weight:500;}
.note a:hover{text-decoration:underline;}
</style>
<script>
  (function() {
    var t = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
</head>
<body>
<div class="bg-blob blob1"></div><div class="bg-blob blob2"></div>
<div class="grid-lines"></div>

<div class="page">
  <div class="card">

    <!-- Icône WhatsApp -->
    <div class="icon-wrap">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path fill="#25D366" d="M2.004 22l1.352-4.968A9.954 9.954 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10a9.954 9.954 0 01-4.932-1.292L2.004 22zm7.08-8.43a.44.44 0 00.12-.084c.208-.222.415-.445.596-.686.082-.11.076-.269-.011-.374-.13-.157-.272-.308-.41-.46l-.41-.46a.337.337 0 010-.46c.176-.207.36-.408.536-.614a.337.337 0 01.514.017c.408.488.8.987 1.21 1.474a.337.337 0 010 .46c-.175.207-.36.408-.536.614a.337.337 0 01-.515-.017c-.13-.157-.272-.308-.41-.46l-.41-.46a.337.337 0 010-.46c.175-.207.36-.408.536-.614zm4.336 2.156a6.38 6.38 0 01-2.87-.693l-2.067.542.553-2.025a6.329 6.329 0 01-.727-2.943C8.31 8.247 10.043 6.5 12.17 6.5A3.828 3.828 0 0116 10.332c0 2.127-1.733 3.874-3.861 3.874h-.719z"/>
      </svg>
    </div>

    <div class="card-title">Mot de passe oublié ?</div>
    <div class="card-desc">
      Entrez votre e-mail. Vous recevrez un <strong>lien sécurisé</strong><br>
      sur votre WhatsApp — valable <strong>30 minutes</strong>.
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>">
      <div class="flash-inner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <?= $flash['type']==='success'
              ? '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
              : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' ?>
        </svg>
        <?= htmlspecialchars($flash['message']) ?>
      </div>
      <?php if ($flash['type'] === 'success'): ?>
      <div class="flash-note">📱 Vérifiez votre WhatsApp et cliquez sur le lien reçu.</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Formulaire email -->
    <form method="POST" action="../../controllers/ForgotPasswordController.php">
      <input type="hidden" name="action" value="forgot_password">

      <div class="field">
        <label class="field-label">Adresse e-mail du compte</label>
        <div class="field-wrap">
          <span class="field-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </span>
          <input type="email" name="email"
                 class="<?= fCls('email', $errors) ?>"
                 placeholder="exemple@email.com"
                 value="<?= fVal('email', $old) ?>"
                 required autocomplete="email">
        </div>
        <?= fErr('email', $errors) ?>
      </div>

      <button type="submit" class="btn-wa">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M2.004 22l1.352-4.968A9.954 9.954 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10a9.954 9.954 0 01-4.932-1.292L2.004 22z"/>
        </svg>
        Envoyer le lien par WhatsApp
      </button>
    </form>

    <div class="note">
      <a href="login.php">← Retour à la connexion</a>
      &nbsp;·&nbsp;
      <a href="signup.php">Créer un compte</a>
    </div>

  </div>
</div>
</body>
</html>
