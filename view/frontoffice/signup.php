<?php
require_once __DIR__ . '/../../models/Session.php';
Session::start();
if (Session::isLoggedIn()) { 
    header('Location: frontoffice_utilisateur.php'); 
    exit; 
}
$flash = Session::getFlash();


$fieldErrors = Session::get('register_errors') ?? [];
Session::remove('register_errors');


$old = Session::get('old_register') ?? [];
Session::remove('old_register');


function old(string $key, array $old, string $default = ''): string {
    return htmlspecialchars($old[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}


function errorClass(string $field, array $errors): string {
    return isset($errors[$field]) ? 'is-invalid' : '';
}

function errorMessage(string $field, array $errors): string {
    if (isset($errors[$field])) {
        return '<div class="field-error" style="font-size:0.7rem; color:var(--rose); margin-top:0.3rem;">' . htmlspecialchars($errors[$field]) . '</div>';
    }
    return '';
}


$startStep = 1;
if (!empty($fieldErrors)) {
    $step1Fields = ['nom', 'prenom', 'date_naissance', 'cin'];
    $step2Fields = ['email', 'numTel', 'adresse', 'gouvernorat'];
    
    foreach ($step1Fields as $field) {
        if (isset($fieldErrors[$field])) { $startStep = 1; break; }
    }
    foreach ($step2Fields as $field) {
        if (isset($fieldErrors[$field])) { $startStep = 2; break; }
    }
    if (isset($fieldErrors['mdp']) || isset($fieldErrors['mdp_confirm']) || 
        isset($fieldErrors['terms']) || isset($fieldErrors['kyc_consent'])) {
        $startStep = 3;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>NexaBank - Inscription</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/frontoffice/Utilisateur.css">
<style>
  .btn-next {
    background: linear-gradient(135deg, var(--blue), #3A7AF0);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 0.55rem 1.6rem;
    font-size: 0.86rem;
    font-weight: 600;
    font-family: var(--fb);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: opacity 0.18s, transform 0.12s;
}

.btn-next:hover {
    opacity: 0.92;
    transform: translateY(-1px);
}

.btn-back {
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 9px;
    padding: 0.55rem 1.1rem;
    font-size: 0.82rem;
    color: var(--muted2);
    font-family: var(--fb);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.18s;
}

.btn-back:hover {
    border-color: var(--blue);
    color: var(--blue);
    background: rgba(79, 142, 247, 0.08);
}
body{overflow-x:hidden;align-items:flex-start;justify-content:center;padding:2rem 1.5rem;}
.bg-blob{position:fixed;border-radius:50%;pointer-events:none;z-index:0;}
.blob1{width:500px;height:500px;top:-150px;right:-100px;background:radial-gradient(circle,rgba(79,142,247,.1),transparent 70%);}
.blob2{width:400px;height:400px;bottom:-100px;left:-100px;background:radial-gradient(circle,rgba(45,212,191,.07),transparent 70%);}
.grid-lines{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(79,142,247,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(79,142,247,.04) 1px,transparent 1px);background-size:60px 60px;}
.wrap{position:relative;z-index:1;width:100%;max-width:780px;margin:0 auto;}
.card{background:var(--bg2);border:1px solid var(--border);border-radius:18px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.5);animation:fadeUp .45s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.card-head{padding:1.8rem 2rem 1.4rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:relative;overflow:hidden;}
.card-head::before{content:'';position:absolute;top:-60px;right:-60px;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(79,142,247,.1),transparent 70%);}
.head-logo{display:flex;align-items:center;gap:.9rem;}
.logo-icon{width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,var(--blue),var(--teal));display:flex;align-items:center;justify-content:center;}
.logo-icon svg{width:20px;height:20px;fill:var(--text);}
.logo-name{font-family:var(--fh);font-size:1.1rem;font-weight:800;letter-spacing:-.02em;}
.logo-name span{color:var(--blue);}
.card-title{font-family:var(--fh);font-size:1.3rem;font-weight:800;text-align:right;}
.card-sub{font-size:.76rem;color:var(--muted);text-align:right;}
.card-sub a{color:var(--blue);text-decoration:none;font-weight:500;}
.steps{display:flex;align-items:center;padding:1rem 2rem;border-bottom:1px solid var(--border);}
.step{display:flex;align-items:center;flex:1;}
.step-circle{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;font-family:var(--fh);flex-shrink:0;border:2px solid var(--border);color:var(--muted);transition:all .3s;}
.step.done .step-circle{background:var(--blue);border-color:var(--blue);color:var(--text);}
.step.active .step-circle{border-color:var(--blue);color:var(--blue);}
.step.error .step-circle{border-color:var(--rose);color:var(--rose);}
.step-label{font-size:.68rem;color:var(--muted);margin-left:.4rem;white-space:nowrap;}
.step.done .step-label,.step.active .step-label{color:var(--text);}
.step-line{flex:1;height:1px;background:var(--border);margin:0 .5rem;}
.step.done .step-line{background:var(--blue);opacity:.4;}
.card-body{padding:1.8rem 2rem;}
.flash{border-radius:10px;padding:.65rem 1rem;font-size:.78rem;display:flex;align-items:flex-start;gap:.6rem;margin-bottom:1rem;line-height:1.6;}
.flash svg{width:14px;height:14px;flex-shrink:0;margin-top:2px;}
.flash-error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);color:var(--rose);}
.flash-success{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);color:var(--green);}
.slabel{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);margin-bottom:.8rem;display:flex;align-items:center;gap:.5rem;}
.slabel::after{content:'';flex:1;height:1px;background:var(--border);}
.fgrid{display:grid;gap:.9rem;}
.g2{grid-template-columns:1fr 1fr;}.g1{grid-template-columns:1fr;}
.span2{grid-column:span 2;}
.field{display:flex;flex-direction:column;gap:.35rem;margin-bottom:0.5rem;}
.field-label{font-size:.66rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);}
.field-wrap{position:relative;display:flex;align-items:center;}
.fi{position:absolute;left:.9rem;color:var(--muted);display:flex;align-items:center;}
.fi svg{width:14px;height:14px;}
.finput{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:.6rem .9rem .6rem 2.4rem;font-size:.83rem;color:var(--text);font-family:var(--fb);outline:none;transition:border .18s,background .18s;}
.finput:focus{border-color:var(--blue);background:var(--surface);}
.finput::placeholder{color:var(--muted);}
.finput.mono{font-family:var(--fm);letter-spacing:.05em;}
.finput.is-invalid{border-color:var(--rose)!important;background:rgba(244,63,94,.04);}
.field-error{font-size:0.65rem;color:var(--rose);margin-top:0.25rem;}
.fhint{font-size:.65rem;color:var(--muted);margin-top:2px;}
.feye{position:absolute;right:.9rem;color:var(--muted);cursor:pointer;display:flex;align-items:center;background:none;border:none;}
.feye svg{width:14px;height:14px;}
.pwd-bars{display:flex;gap:3px;margin-top:4px;}
.pwd-bar{flex:1;height:3px;border-radius:99px;background:var(--border);transition:background .3s;}
.pwd-bar.weak{background:var(--rose);}.pwd-bar.medium{background:var(--amber);}.pwd-bar.strong{background:var(--green);}
.pwd-lbl{font-size:.64rem;color:var(--muted);margin-top:2px;}
.terms-row{display:flex;align-items:flex-start;gap:.55rem;margin-top:.4rem;font-size:.76rem;color:var(--muted2);}
.terms-row input[type=checkbox]{appearance:none;width:15px;height:15px;min-width:15px;border-radius:4px;border:1px solid var(--border2);background:var(--bg3);cursor:pointer;position:relative;margin-top:2px;}
.terms-row input[type=checkbox]:checked{background:var(--blue);border-color:var(--blue);}
.terms-row input[type=checkbox]:checked::after{content:"\2713";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:9px;color:var(--text);}
.terms-row a{color:var(--blue);text-decoration:none;}
.fdivider{border:none;border-top:1px solid var(--border);margin:1.2rem 0;}
.card-foot{padding:1.1rem 2rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:1rem;}
.btn-back{background:transparent;border:1px solid var(--border);border-radius:9px;padding:.55rem 1.1rem;font-size:.82rem;color:var(--muted2);font-family:var(--fb);cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:all .18s;}
.btn-back:hover{border-color:var(--blue);color:var(--blue);}
.btn-next{background:linear-gradient(135deg,var(--blue),var(--blue2));color:var(--text);}
.btn-next:hover{opacity:.92;transform:translateY(-1px);}
.foot-badges{display:flex;align-items:center;gap:.5rem;}
.step-panel{display:none;}
.step-panel.active{display:block;}
@media(max-width:600px){.g2{grid-template-columns:1fr;}.span2{grid-column:span 1;}.card-head{flex-direction:column;gap:.7rem;}.card-title,.card-sub{text-align:left;}.steps,.card-body,.card-foot{padding:1rem 1.2rem;}.step-label{display:none;}}
</style>
</head>
<body>
<div class="bg-blob blob1"></div><div class="bg-blob blob2"></div>
<div class="grid-lines"></div>
<div class="wrap">
<div class="card">
  <div class="card-head">
    <div class="head-logo">
      <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7l-8 4-8-4v-7l8-4.32z"/></svg></div>
      <div class="logo-name">Legal<span>Fin</span></div>
    </div>
    <div><div class="card-title">Creer un compte</div><div class="card-sub">Deja client ? <a href="login.php">Se connecter</a></div></div>
  </div>
  <div class="steps" id="steps-bar">
    <div class="step <?= $startStep == 1 ? 'active' : ($startStep > 1 ? 'done' : '') ?>" id="s1">
      <div class="step-circle">1</div>
      <div class="step-label">Identite</div>
      <div class="step-line"></div>
    </div>
    <div class="step <?= $startStep == 2 ? 'active' : ($startStep > 2 ? 'done' : '') ?>" id="s2">
      <div class="step-circle">2</div>
      <div class="step-label">Coordonnees</div>
      <div class="step-line"></div>
    </div>
    <div class="step <?= $startStep == 3 ? 'active' : '' ?>" id="s3">
      <div class="step-circle">3</div>
      <div class="step-label">Securite</div>
    </div>
  </div>
  
  <form method="POST" action="../../controller/AuthController.php" id="registerForm">
    <input type="hidden" name="action" value="register">
    <div class="card-body">
      <?php if ($flash && $flash['type'] === 'error' && empty($fieldErrors)): ?>
      <div class="flash flash-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
      <?php endif; ?>
      
      <?php if ($flash && $flash['type'] === 'success'): ?>
      <div class="flash flash-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
      <?php endif; ?>

      
      <div class="step-panel <?= $startStep == 1 ? 'active' : '' ?>" id="panel-1">
        <div class="slabel">Informations personnelles</div>
        <div class="fgrid g2">
          <div class="field span2">
            <label class="field-label">Type de compte *</label>
            <div style="display:flex;gap:1rem;flex-wrap:wrap;padding-top:0.35rem;">
              <label class="terms-row" style="align-items:center;margin:0;">
                <input type="radio" name="account_type" value="personal" <?= old('account_type', $old) !== 'association' ? 'checked' : '' ?>>
                <span>Compte personnel</span>
              </label>
              <label class="terms-row" style="align-items:center;margin:0;">
                <input type="radio" name="account_type" value="association" <?= old('account_type', $old) === 'association' ? 'checked' : '' ?>>
                <span>Association</span>
              </label>
            </div>
            <?= errorMessage('account_type', $fieldErrors) ?>
          </div>
          <div class="field" id="nom-field">
            <label class="field-label" id="nom-label">Nom *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <input type="text" id="nom-input" name="nom" class="finput <?= errorClass('nom', $fieldErrors) ?>" 
                     placeholder="ben user" value="<?= old('nom', $old) ?>">
            </div>
            <?= errorMessage('nom', $fieldErrors) ?>
          </div>
          <div class="field" id="prenom-field">
            <label class="field-label">Prenom *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <input type="text" id="prenom-input" name="prenom" class="finput <?= errorClass('prenom', $fieldErrors) ?>" 
                     placeholder="user" value="<?= old('prenom', $old) ?>">
            </div>
            <?= errorMessage('prenom', $fieldErrors) ?>
          </div>
          <div class="field">
            <label class="field-label">Date de naissance *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
              <input type="date" name="date_naissance" class="finput <?= errorClass('date_naissance', $fieldErrors) ?>" 
                     max="<?= date('Y-m-d', strtotime('-18 years')) ?>" value="<?= old('date_naissance', $old) ?>">
            </div>
            <?= errorMessage('date_naissance', $fieldErrors) ?>
          </div>
          <div class="field">
            <label class="field-label">CIN *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
              <input type="text" name="cin" class="finput mono <?= errorClass('cin', $fieldErrors) ?>" 
                     placeholder="12345678" maxlength="8" value="<?= old('cin', $old) ?>">
            </div>
            <div class="fhint">8 chiffres exactement</div>
            <?= errorMessage('cin', $fieldErrors) ?>
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; margin-top:1.5rem;">
          <button type="button" class="btn-next" onclick="goToStep(2)">
            Suivant <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>

      
      <div class="step-panel <?= $startStep == 2 ? 'active' : '' ?>" id="panel-2">
        <div class="slabel">Coordonnees</div>
        <div class="fgrid g1">
          <div class="field">
            <label class="field-label">Email *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
              <input type="email" name="email" class="finput <?= errorClass('email', $fieldErrors) ?>" 
                     placeholder="ahmed@email.com" value="<?= old('email', $old) ?>">
            </div>
            <div class="fhint">Doit contenir le symbole @</div>
            <?= errorMessage('email', $fieldErrors) ?>
          </div>
        </div>
        <div class="fgrid g2" style="margin-top:.8rem">
          <div class="field">
            <label class="field-label">Telephone *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 014.69 12 19.79 19.79 0 011.61 3.18 2 2 0 013.59 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L7.91 8.56a16 16 0 006 6l.87-.87a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg></span>
              <input type="tel" name="numTel" class="finput mono <?= errorClass('numTel', $fieldErrors) ?>" 
                     placeholder="+216 XX XXX XXX" value="<?= old('numTel', $old) ?>">
            </div>
            <div class="fhint">Au moins 8 chiffres</div>
            <?= errorMessage('numTel', $fieldErrors) ?>
          </div>
          <div class="field">
            <label class="field-label">Gouvernorat *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
              <select name="gouvernorat" class="finput <?= errorClass('gouvernorat', $fieldErrors) ?>" style="cursor:pointer">
                <option value="">Selectionner...</option>
                <?php foreach(['Tunis','Ariana','Ben Arous','Manouba','Nabeul','Zaghouan','Bizerte','Sousse','Monastir','Mahdia','Sfax','Kairouan','Kasserine','Sidi Bouzid','Gabes','Medenine','Tataouine','Gafsa','Tozeur','Kebili'] as $g): ?>
                <option value="<?= $g ?>" <?= (($old['gouvernorat'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?= errorMessage('gouvernorat', $fieldErrors) ?>
          </div>
          <div class="field span2">
            <label class="field-label">Adresse complete *</label>
            <div class="field-wrap">
              <span class="fi" style="top:.7rem;align-self:flex-start"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
              <textarea name="adresse" class="finput <?= errorClass('adresse', $fieldErrors) ?>" rows="2" style="padding-left:2.4rem;resize:none" placeholder="Rue, numero, cite, code postal..."><?= old('adresse', $old) ?></textarea>
            </div>
            <?= errorMessage('adresse', $fieldErrors) ?>
          </div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:1.5rem;">
          <button type="button" class="btn-back" onclick="goToStep(1)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg> Retour
          </button>
          <button type="button" class="btn-next" onclick="goToStep(3)">
            Suivant <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>

      
      <div class="step-panel <?= $startStep == 3 ? 'active' : '' ?>" id="panel-3">
        <div class="slabel">Securite du compte</div>
        <div class="fgrid g1">
          <div class="field">
            <label class="field-label">Mot de passe *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
              <input type="password" name="mdp" id="mdp" class="finput <?= errorClass('mdp', $fieldErrors) ?>" 
                     placeholder="Minimum 8 caracteres, 1 maj, 1 chiffre">
              <button type="button" class="feye" onclick="toggleP('mdp','ei1')"><svg id="ei1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
            <div class="pwd-bars"><div class="pwd-bar" id="b1"></div><div class="pwd-bar" id="b2"></div><div class="pwd-bar" id="b3"></div><div class="pwd-bar" id="b4"></div></div>
            <div class="pwd-lbl" id="plbl">Entrez un mot de passe</div>
            <?= errorMessage('mdp', $fieldErrors) ?>
          </div>
          <div class="field">
            <label class="field-label">Confirmer *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
              <input type="password" name="mdp_confirm" id="mdp2" class="finput <?= errorClass('mdp_confirm', $fieldErrors) ?>" placeholder="Repeter">
              <button type="button" class="feye" onclick="toggleP('mdp2','ei2')"><svg id="ei2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
            <?= errorMessage('mdp_confirm', $fieldErrors) ?>
          </div>
        </div>
        <hr class="fdivider">
        <div class="slabel">Consentements</div>
        <div style="display:flex;flex-direction:column;gap:.65rem">
          <label class="terms-row"><input type="checkbox" name="terms" value="1"><span>J'accepte les <a href="#">CGU</a> et la <a href="#">Politique de confidentialite</a> de NexaBank.</span></label>
          <?php if (isset($fieldErrors['terms'])): ?>
            <div class="field-error"><?= htmlspecialchars($fieldErrors['terms']) ?></div>
          <?php endif; ?>
          <label class="terms-row"><input type="checkbox" name="kyc_consent" value="1"><span>Je consens a la verification KYC/AML.</span></label>
          <?php if (isset($fieldErrors['kyc_consent'])): ?>
            <div class="field-error"><?= htmlspecialchars($fieldErrors['kyc_consent']) ?></div>
          <?php endif; ?>
          <div class="field" id="association-name-group" style="display:none; margin-top:0.8rem;">
            <label class="field-label">Nom de l'association *</label>
            <div class="field-wrap">
              <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11h18M6 7h12M6 15h12M3 19h18"/></svg></span>
              <input type="text" name="association_name" id="association_name" class="finput <?= errorClass('association_name', $fieldErrors) ?>" value="<?= htmlspecialchars($old['association_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom de l'association">
            </div>
            <?= errorMessage('association_name', $fieldErrors) ?>
          </div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:1.5rem;">
          <button type="button" class="btn-back" onclick="goToStep(2)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg> Retour
          </button>
          <button type="submit" class="btn-next" id="submitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Creer mon compte
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
</div>

<script>
var cur = <?= $startStep ?>;
var tot = 3;

function goToStep(step) {
  if (step >= 1 && step <= tot) {
    cur = step;
    updateUI();
  }
}

function updateUI() {
  for (var i = 1; i <= tot; i++) {
    var panel = document.getElementById('panel-' + i);
    if (panel) {
      if (i === cur) {
        panel.classList.add('active');
      } else {
        panel.classList.remove('active');
      }
    }
    var s = document.getElementById('s' + i);
    if (s) {
      s.classList.remove('active', 'done');
      if (i === cur) {
        s.classList.add('active');
      }
      if (i < cur) {
        s.classList.add('done');
      }
    }
  }
}

function toggleP(id, eid) {
  var i = document.getElementById(id), e = document.getElementById(eid);
  if (i.type === 'password') {
    i.type = 'text';
    e.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    i.type = 'password';
    e.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}

function checkStr(v) {
  var s = 0;
  if (v.length >= 8) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  var cls = ['','weak','medium','strong','strong'];
  var lbl = ['','Faible','Moyen','Fort','Tres fort'];
  for (var i = 1; i <= 4; i++) {
    var el = document.getElementById('b' + i);
    if (el) {
      el.className = 'pwd-bar';
      if (i <= s) el.classList.add(cls[s]);
    }
  }
  var plbl = document.getElementById('plbl');
  if (plbl) plbl.textContent = v.length ? (lbl[s] || 'Faible') : 'Entrez un mot de passe';
}

var mdpInput = document.getElementById('mdp');
if (mdpInput) {
  mdpInput.addEventListener('input', function() { checkStr(this.value); });
}

var accountTypeRadios = document.querySelectorAll('input[name="account_type"]');
var nomField = document.getElementById('nom-field');
var prenomField = document.getElementById('prenom-field');
var nomLabel = document.getElementById('nom-label');
var nomInput = document.getElementById('nom-input');
var prenomInput = document.getElementById('prenom-input');

function syncAccountTypeFields() {
  var accountType = 'personal';
  if (accountTypeRadios) {
    accountTypeRadios.forEach(function(radio) {
      if (radio.checked) accountType = radio.value;
    });
  }

  if (prenomField) {
    prenomField.style.display = accountType === 'association' ? 'none' : 'block';
  }
  if (nomLabel) {
    nomLabel.textContent = accountType === 'association' ? "Nom de l'association *" : 'Nom *';
  }
  if (nomInput) {
    nomInput.placeholder = accountType === 'association' ? "Nom de l'association" : 'ben user';
  }
  if (prenomInput) {
    if (accountType === 'association') {
      prenomInput.value = 'association';
    } else if (prenomInput.value === 'association') {
      prenomInput.value = '';
    }
  }
}

if (accountTypeRadios) {
  accountTypeRadios.forEach(function(radio) {
    radio.addEventListener('change', syncAccountTypeFields);
  });
}

syncAccountTypeFields();
updateUI();
</script>
</body>
</html>



