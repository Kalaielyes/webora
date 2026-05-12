<?php
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
require_once __DIR__ . '/../../models/GoogleAuthenticator.php';

Session::start();
Session::requireLogin('login.php');

$id = (int) Session::get('user_id');
$m = new Utilisateur();
$user = $m->findById($id);

if ($user['two_factor_enabled'] == 1) {
    Session::setFlash('success', 'L\'authentification à deux facteurs est déjà activée.');
    header('Location: frontoffice_utilisateur.php');
    exit;
}

$ga = new GoogleAuthenticator();
// Generate a new secret
$secret = $ga->createSecret();
$qrCodeUrl = $ga->getQRCodeGoogleUrl('LegalFin (' . $user['email'] . ')', $secret);

$flash = Session::getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>LegalFin — Activer 2FA</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/frontoffice/Utilisateur.css">
<style>
body{align-items:center;justify-content:center;overflow:hidden;background:var(--bg);}
.page{position:relative;z-index:1;display:flex;width:100%;min-height:100vh;}
.form-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:3rem;}
.form-box{width:100%;max-width:450px;background:var(--surface);border:1px solid var(--border);border-radius:15px;padding:2.5rem;box-shadow:0 10px 30px rgba(0,0,0,0.05);}
.form-title{font-family:var(--fh);font-size:1.6rem;font-weight:800;letter-spacing:-.02em;text-align:center;}
.form-sub{font-size:.85rem;color:var(--muted);margin-top:.5rem;margin-bottom:1.5rem;text-align:center;}
.qr-code-box { display:flex; justify-content:center; margin: 1.5rem 0; background:#fff; padding:1rem; border-radius:10px; border:1px solid var(--border);}
.field{display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem;}
.field-label{font-size:.75rem;font-weight:600;color:var(--muted);}
.field-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:.8rem;font-size:1rem;color:var(--text);font-family:var(--fb);outline:none;text-align:center;letter-spacing:0.2em;}
.btn-submit{width:100%;background:linear-gradient(135deg,var(--blue),#3A7AF0);color:#fff;border:none;border-radius:10px;padding:0.9rem;font-size:0.9rem;font-weight:600;cursor:pointer;transition:opacity 0.2s;}
.btn-submit:hover{opacity:0.9;}
.flash{border-radius:10px;padding:.7rem 1rem;font-size:.8rem;margin-bottom:1rem;}
.flash-error{background:rgba(244,63,94,.08);color:var(--rose);}
.btn-cancel { display:block; text-align:center; margin-top:1rem; color:var(--muted); font-size:0.8rem; text-decoration:none; }
.btn-cancel:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="page">
  <div class="form-panel">
    <div class="form-box">
      <div class="form-title">Activer le 2FA</div>
      <div class="form-sub">Sécurisez votre compte en ajoutant une deuxième étape de vérification.</div>

      <?php if ($flash): ?>
      <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
      <?php endif; ?>

      <div style="font-size:0.85rem; color:var(--muted2); line-height:1.5; text-align:center;">
        1. Téléchargez une application comme <strong>Google Authenticator</strong> ou <strong>Authy</strong>.<br>
        2. Scannez le QR code ci-dessous.
      </div>

      <div class="qr-code-box">
        <img src="<?= $qrCodeUrl ?>" alt="QR Code 2FA">
      </div>

      <div style="font-size:0.75rem; color:var(--muted); text-align:center; margin-bottom:1.5rem;">
        Clé secrète : <span style="font-family:var(--fmono); background:var(--bg3); padding:2px 5px; border-radius:4px;"><?= $secret ?></span>
      </div>

      <form method="POST" action="../../controller/UtilisateurController.php">
        <input type="hidden" name="action" value="enable_2fa">
        <input type="hidden" name="secret" value="<?= htmlspecialchars($secret) ?>">
        <div class="field">
          <label class="field-label">Code de vérification (6 chiffres)</label>
          <input type="text" name="code" class="field-input" placeholder="123456" maxlength="6" pattern="\d{6}" required autocomplete="off">
        </div>
        <button type="submit" class="btn-submit">Vérifier et Activer</button>
      </form>
      <a href="frontoffice_utilisateur.php" class="btn-cancel">Annuler</a>
    </div>
  </div>
</div>
</body>
</html>

