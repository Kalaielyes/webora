<?php
// =============================================================
//  view/frontoffice/reset_password.php — NexaBank
//  Étape 2 : L'utilisateur choisit son nouveau mot de passe
//  Arrivée depuis le lien WhatsApp : ?token=XXXX
// =============================================================
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/PasswordReset.php';
Session::start();

if (Session::isLoggedIn()) { header('Location: frontoffice_utilisateur.php'); exit; }

$rawToken = trim($_GET['token'] ?? $_POST['token'] ?? '');

// Vérification immédiate du token
$pr      = new PasswordReset();
$isValid = $pr->isValid($rawToken);
$minutes = $isValid ? $pr->minutesRemaining($rawToken) : 0;

$flash  = Session::getFlash();
$errors = Session::get('reset_errors') ?? [];
Session::remove('reset_errors');

function rErr(string $k, array $e): string {
    return isset($e[$k]) ? '<div class="field-error">' . htmlspecialchars($e[$k]) . '</div>' : '';
}
function rCls(string $k, array $e): string { return isset($e[$k]) ? 'field-input is-invalid' : 'field-input'; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>NexaBank — Nouveau mot de passe</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/frontoffice/Utilisateur.css">
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

.icon-wrap{width:64px;height:64px;border-radius:50%;
  background:rgba(79,142,247,.1);border:2px solid rgba(79,142,247,.2);
  display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;}
.icon-wrap svg{width:28px;height:28px;stroke:var(--blue);fill:none;stroke-width:1.8;}

.icon-error{background:rgba(244,63,94,.1);border-color:rgba(244,63,94,.2);}
.icon-error svg{stroke:var(--rose);}

.card-title{font-family:var(--fh);font-size:1.35rem;font-weight:800;letter-spacing:-.02em;text-align:center;margin-bottom:.4rem;}
.card-desc{font-size:.81rem;color:var(--muted2);text-align:center;line-height:1.65;margin-bottom:1.2rem;}

/* Timer */
.timer-badge{display:flex;align-items:center;justify-content:center;gap:.4rem;
  background:rgba(79,142,247,.08);border:1px solid rgba(79,142,247,.2);
  color:var(--blue);border-radius:99px;padding:4px 12px;font-size:.72rem;font-weight:600;
  width:fit-content;margin:0 auto 1.4rem;}
.timer-badge svg{width:13px;height:13px;stroke:var(--blue);fill:none;stroke-width:2;}
.timer-warn{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.2);color:var(--amber);}
.timer-warn svg{stroke:var(--amber);}

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
.field-eye{position:absolute;right:.9rem;color:var(--muted);cursor:pointer;display:flex;align-items:center;background:none;border:none;}
.field-eye svg{width:15px;height:15px;}

/* Force mdp */
.pwd-bars{display:flex;gap:3px;margin-top:4px;}
.pwd-bar{flex:1;height:3px;border-radius:99px;background:var(--border);transition:background .3s;}
.pwd-bar.weak{background:var(--rose);}
.pwd-bar.medium{background:var(--amber);}
.pwd-bar.strong{background:var(--green);}
.pwd-lbl{font-size:.67rem;color:var(--muted);margin-top:2px;}

/* Règles mdp */
.rules{display:flex;flex-direction:column;gap:.3rem;margin-bottom:1rem;}
.rule{display:flex;align-items:center;gap:.5rem;font-size:.74rem;color:var(--muted);}
.rule svg{width:13px;height:13px;flex-shrink:0;}
.rule.ok{color:var(--green);}
.rule.ok svg{stroke:var(--green);}
.rule.ko svg{stroke:var(--muted);}

/* Bouton */
.btn-submit{width:100%;background:linear-gradient(135deg,var(--blue),var(--blue2));
  color:#fff;border:none;border-radius:10px;padding:.82rem 1rem;font-size:.9rem;
  font-weight:600;font-family:var(--fb);cursor:pointer;display:flex;align-items:center;
  justify-content:center;gap:.5rem;transition:opacity .18s,transform .12s;}
.btn-submit:hover{opacity:.92;transform:translateY(-1px);}
.btn-submit:disabled{opacity:.4;cursor:not-allowed;transform:none;}

/* Flash */
.flash{border-radius:10px;padding:.75rem 1rem;font-size:.78rem;display:flex;align-items:center;gap:.6rem;margin-bottom:1.2rem;}
.flash svg{width:14px;height:14px;flex-shrink:0;}
.flash-error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);color:var(--rose);}

/* Lien expiré */
.expired-box{text-align:center;padding:1rem 0;}
.expired-title{font-family:var(--fh);font-size:1.1rem;font-weight:700;color:var(--rose);margin-bottom:.5rem;}
.expired-desc{font-size:.82rem;color:var(--muted2);line-height:1.6;margin-bottom:1.2rem;}
.btn-retry{display:inline-flex;align-items:center;gap:.5rem;
  background:var(--blue);color:#fff;border:none;border-radius:9px;
  padding:.6rem 1.4rem;font-size:.85rem;font-weight:600;font-family:var(--fb);cursor:pointer;text-decoration:none;}

.note{margin-top:1.2rem;padding-top:1rem;border-top:1px solid var(--border);
  font-size:.72rem;color:var(--muted);text-align:center;}
.note a{color:var(--blue);text-decoration:none;font-weight:500;}
</style>
</head>
<body>
<div class="bg-blob blob1"></div><div class="bg-blob blob2"></div>
<div class="grid-lines"></div>

<div class="page">
  <div class="card">

    <?php if (!$isValid): ?>
    <!-- ── TOKEN INVALIDE / EXPIRÉ ── -->
    <div class="icon-wrap icon-error">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <div class="expired-box">
      <div class="expired-title">Lien invalide ou expiré</div>
      <div class="expired-desc">
        Ce lien de réinitialisation n'est plus valide.<br>
        Il a peut-être été déjà utilisé ou a expiré après 30 minutes.
      </div>
      <a href="forgot_password.php" class="btn-retry">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
        Faire une nouvelle demande
      </a>
    </div>

    <?php else: ?>
    <!-- ── FORMULAIRE RESET ── -->
    <div class="icon-wrap">
      <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
    </div>

    <div class="card-title">Nouveau mot de passe</div>
    <div class="card-desc">Choisissez un mot de passe fort pour sécuriser votre compte.</div>

    <!-- Timer countdown -->
    <div class="timer-badge <?= $minutes <= 5 ? 'timer-warn' : '' ?>">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Lien valide encore <span id="countdown"><?= $minutes ?></span> min
    </div>

    <?php if ($flash && $flash['type'] === 'error'): ?>
    <div class="flash flash-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="../../controller/ForgotPasswordController.php" id="reset-form">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="token"  value="<?= htmlspecialchars($rawToken) ?>">

      <!-- Nouveau mot de passe -->
      <div class="field">
        <label class="field-label">Nouveau mot de passe</label>
        <div class="field-wrap">
          <span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
          <input type="password" name="mdp" id="mdp"
                 class="<?= rCls('mdp', $errors) ?>"
                 placeholder="Minimum 8 caractères"
                 oninput="checkStrength(this.value)"
                 required>
          <button type="button" class="field-eye" onclick="togglePwd('mdp','ei1')">
            <svg id="ei1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="pwd-bars">
          <div class="pwd-bar" id="b1"></div>
          <div class="pwd-bar" id="b2"></div>
          <div class="pwd-bar" id="b3"></div>
          <div class="pwd-bar" id="b4"></div>
        </div>
        <div class="pwd-lbl" id="plbl">Entrez un mot de passe</div>
        <?= rErr('mdp', $errors) ?>
      </div>

      <!-- Règles visuelles -->
      <div class="rules">
        <div class="rule ko" id="r-len"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> Au moins 8 caractères</div>
        <div class="rule ko" id="r-maj"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> Une lettre majuscule</div>
        <div class="rule ko" id="r-num"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> Un chiffre</div>
      </div>

      <!-- Confirmer -->
      <div class="field">
        <label class="field-label">Confirmer le mot de passe</label>
        <div class="field-wrap">
          <span class="field-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
          <input type="password" name="mdp_confirm" id="mdp2"
                 class="<?= rCls('mdp_confirm', $errors) ?>"
                 placeholder="Répétez le mot de passe"
                 oninput="checkMatch()"
                 required>
          <button type="button" class="field-eye" onclick="togglePwd('mdp2','ei2')">
            <svg id="ei2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <?= rErr('mdp_confirm', $errors) ?>
      </div>

      <button type="submit" class="btn-submit" id="btn-sub" disabled>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
        Enregistrer le nouveau mot de passe
      </button>
    </form>

    <?php endif; ?>

    <div class="note">
      <a href="login.php">← Retour à la connexion</a>
    </div>
  </div>
</div>

<script>
// Validation visuelle temps réel
function checkStrength(v) {
  var s = 0;
  if (v.length >= 8) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;

  var cls = ['','weak','medium','strong','strong'];
  var lbl = ['','Faible','Moyen','Fort','Très fort'];
  ['b1','b2','b3','b4'].forEach(function(id,i){
    var el = document.getElementById(id);
    el.className = 'pwd-bar';
    if (i < s) el.classList.add(cls[s]);
  });
  document.getElementById('plbl').textContent = v.length ? (lbl[s]||'Faible') : 'Entrez un mot de passe';

  // Règles visuelles
  setRule('r-len', v.length >= 8);
  setRule('r-maj', /[A-Z]/.test(v));
  setRule('r-num', /[0-9]/.test(v));

  checkMatch();
}

function setRule(id, ok) {
  var el = document.getElementById(id);
  el.classList.toggle('ok', ok);
  el.classList.toggle('ko', !ok);
  el.querySelector('svg').innerHTML = ok
    ? '<polyline points="20 6 9 17 4 12"/>'
    : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>';
}

function checkMatch() {
  var mdp  = document.getElementById('mdp').value;
  var mdp2 = document.getElementById('mdp2').value;
  var ok   = mdp.length >= 8 && /[A-Z]/.test(mdp) && /[0-9]/.test(mdp) && mdp === mdp2;
  document.getElementById('btn-sub').disabled = !ok;
}

function togglePwd(id, eid) {
  var i = document.getElementById(id), e = document.getElementById(eid);
  if (i.type === 'password') {
    i.type = 'text';
    e.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    i.type = 'password';
    e.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}

// Countdown timer (décompte visuel)
<?php if ($isValid && $minutes > 0): ?>
var seconds = <?= $minutes ?> * 60;
var timerEl = document.getElementById('countdown');
var badge   = timerEl ? timerEl.closest('.timer-badge') : null;
if (timerEl) {
  var iv = setInterval(function() {
    seconds--;
    if (seconds <= 0) {
      clearInterval(iv);
      timerEl.textContent = '0';
      if (badge) badge.classList.add('timer-warn');
      // Désactiver le formulaire si expiré côté client
      var btn = document.getElementById('btn-sub');
      if (btn) { btn.disabled = true; btn.textContent = 'Lien expiré — Faites une nouvelle demande'; }
    } else {
      timerEl.textContent = Math.ceil(seconds / 60);
      if (seconds <= 300 && badge) badge.classList.add('timer-warn'); // rouge < 5 min
    }
  }, 1000);
}
<?php endif; ?>
</script>
</body>
</html>

