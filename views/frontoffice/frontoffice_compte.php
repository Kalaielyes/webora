<?php
/**
 * frontoffice_compte.php — CLIENT VIEW
 */
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../controllers/CompteController.php';
require_once __DIR__ . '/../../controllers/CarteController.php';

Config::autoLogin();
$userId   = (int)$_SESSION['user']['id'];
$user     = $_SESSION['user'];
$initials = strtoupper(substr($user['prenom']??'',0,1).substr($user['nom']??'',0,1));
$comptes  = CompteController::findByUtilisateur($userId);

$selected = null;
if (!empty($_GET['id_compte'])) {
    foreach ($comptes as $c) { if ($c->getIdCompte()===(int)$_GET['id_compte']) { $selected=$c; break; } }
}
if (!$selected && !empty($comptes)) {
    foreach ($comptes as $c) { if ($c->getStatut()==='actif') { $selected=$c; break; } }
    if (!$selected) $selected = $comptes[0];
}
$cartes = $selected ? CarteController::findByCompte($selected->getIdCompte()) : [];

$pendingComptes = array_values(array_filter($comptes, fn($c)=>in_array($c->getStatut(),['en_attente','demande_cloture','demande_suppression'])));
$pendingCartes  = [];
foreach ($comptes as $c) {
    foreach (CarteController::findByCompte($c->getIdCompte()) as $carte) {
        if (in_array($carte->getStatut(),['inactive','demande_cloture','demande_suppression','demande_reactivation'])) $pendingCartes[]=['carte'=>$carte,'compte'=>$c];
    }
}
$isKycVerifie   = (($user['status_kyc'] ?? '') === 'VERIFIE');
$showCompteForm = (!empty($_GET['form']) && $_GET['form']==='compte' && $isKycVerifie);
$showCarteForm  = (!empty($_GET['form']) && $_GET['form']==='carte' && $isKycVerifie);
$showAttente    = (!empty($_GET['tab'])  && $_GET['tab']==='attente');

function cvClass(string $style, string $statut=''): string {
    if ($statut==='bloquee') return 'cv-bloque';
    return match($style) { 'gold'=>'cv-gold','platinum'=>'cv-platinum','titanium'=>'cv-titanium',default=>'cv-standard' };
}
function styleLabel(string $s): string {
    return match($s) { 'gold'=>'Gold','platinum'=>'Platinum','titanium'=>'Titanium',default=>'Classic' };
}
function badgeCompte(string $s): string {
    return match($s) {
        'actif'=>'<span class="status-pill pill-green">• Actif</span>',
        'bloque'=>'<span class="status-pill pill-red">• Bloqué</span>',
        'en_attente'=>'<span class="status-pill pill-amber">• En attente</span>',
        'demande_cloture'=>'<span class="status-pill pill-amber">• Dem. clôture</span>',
        'demande_suppression'=>'<span class="status-pill pill-amber">• Dem. suppression</span>',
        'cloture'=>'<span class="status-pill pill-grey">• Clôturé</span>',
        default=>'<span class="status-pill pill-grey">'.htmlspecialchars($s).'</span>',
    };
}
function badgeCarte(string $s): string {
    return match($s) {
        'active'=>'<span class="status-pill pill-green" style="font-size:.62rem">• Active</span>',
        'inactive'=>'<span class="status-pill pill-grey" style="font-size:.62rem">• Inactive</span>',
        'bloquee'=>'<span class="status-pill pill-red" style="font-size:.62rem">• Bloquée</span>',
        'expiree'=>'<span class="status-pill pill-grey" style="font-size:.62rem">• Expirée</span>',
        'demande_cloture'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. supp.</span>',
        'demande_blocage'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. blocage</span>',
        'demande_reactivation'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. réactiv.</span>',
        'demande_suppression'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. supp.</span>',
        default=>'',
    };
}
$pendingCount = count($pendingComptes)+count($pendingCartes);

// -- ERROR & DATA HANDLING FROM PHP POST --
$formErrors = $_SESSION['form_errors'] ?? [];
$formData   = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin — Mon espace</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/views/frontoffice/compte.css">
</head>
<body>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Espace client</div>
  </div>
  <div class="sb-user">
    <div class="sb-av"><?= htmlspecialchars($initials) ?></div>
    <div>
      <div class="sb-uname"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
      <div class="sb-uemail"><?= htmlspecialchars($user['email']??'') ?></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Mon espace</div>
    <a class="nav-item <?= (!$showCompteForm&&!$showCarteForm&&!$showAttente)?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>Mes comptes
    </a>
    <a class="nav-item <?= $showCompteForm?'active':'' ?>" <?= $isKycVerifie ? 'href="'.APP_URL.'/views/frontoffice/frontoffice_compte.php?form=compte"' : 'style="opacity:0.5;cursor:not-allowed;" title="Vérification KYC requise" onclick="alert(\'Votre compte doit être vérifié (KYC) pour ouvrir un compte.\'); return false;"' ?>>
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Ouvrir un compte <?= !$isKycVerifie ? '🔒' : '' ?>
    </a>
    <div class="nav-section">Ma carte</div>
    <a class="nav-item <?= $showCarteForm?'active':'' ?>" <?= $isKycVerifie ? 'href="'.APP_URL.'/views/frontoffice/frontoffice_compte.php?form=carte'.($selected?'&id_compte='.$selected->getIdCompte():'').'"' : 'style="opacity:0.5;cursor:not-allowed;" title="Vérification KYC requise" onclick="alert(\'Votre compte doit être vérifié (KYC) pour demander une carte.\'); return false;"' ?>>
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>Demander une carte <?= !$isKycVerifie ? '🔒' : '' ?>
    </a>
    <?php if ($pendingCount>0): ?>
    <div class="nav-section">Suivi</div>
    <a class="nav-item <?= $showAttente?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=attente">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
      En attente
      <span style="margin-left:auto;background:rgba(245,158,11,.2);color:var(--amber);border-radius:99px;padding:1px 7px;font-size:.62rem;font-weight:600"><?= $pendingCount ?></span>
    </a>
    <?php endif; ?>
    <a class="nav-item <?= $showCompteForm?'active':'' ?>" href="../backoffice/backoffice_compte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>backoffice
    </a>
  </nav>
  <div class="sb-footer">
    <?php if (($user['status_kyc']??'')==='VERIFIE'): ?>
      <div class="badge-kyc"><span class="dot-pulse"></span>Identité vérifiée</div>
    <?php else: ?>
      <div class="badge-kyc" style="background:rgba(245,158,11,.1);color:var(--amber)">KYC en cours</div>
    <?php endif; ?>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes comptes bancaires</div>
    <div class="topbar-right">
      <?php if ($isKycVerifie): ?>
      <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte" class="btn-primary" style="font-size:.78rem">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Nouveau compte
      </a>
      <?php else: ?>
      <button class="btn-primary" style="font-size:.78rem;opacity:0.5;cursor:not-allowed;" onclick="alert('Vérification KYC requise pour ouvrir un compte.')" title="Vérification KYC requise">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Nouveau compte 🔒
      </button>
      <?php endif; ?>
      <div class="notif" id="notif-bell" onclick="toggleNotifPanel(event)" title="Notifications">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <?php if ($pendingCount>0): ?><div class="notif-dot"></div><?php endif; ?>
      </div>

    </div>
  </div>

  <div class="content">

  <?php if ($showCompteForm): ?>
  <!-- ══ FORM: NEW COMPTE ════════════════════════════════ -->
  <div class="section-head" style="margin-bottom:.3rem">
    <div class="section-title">Demander un nouveau compte</div>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php" class="btn-ghost">✕ Annuler</a>
  </div>
  <div class="demand-card">
    <div class="info-banner">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
      Votre demande sera examinée par un agent bancaire. Vous serez notifié une fois validée.
    </div>
    <form id="form_compte" method="POST" action="<?= APP_URL ?>/controllers/CompteController.php">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="id_utilisateur" value="<?= $userId ?>">
      <div class="form-grid">
        <div class="form-field">
          <label>Type de compte</label>
          <select id="type_compte" name="type_compte">
            <option value="courant">Compte courant</option>
            <option value="epargne">Compte épargne</option>
            <option value="devise">Compte devise</option>
            <option value="professionnel">Compte professionnel</option>
          </select>
        </div>
        <div class="form-field">
          <label>Devise</label>
          <select name="devise">
            <option value="TND">TND — Dinar tunisien</option>
            <option value="EUR">EUR — Euro</option>
            <option value="USD">USD — Dollar américain</option>
          </select>
        </div>
        <div class="form-field">
          <label>Plafond de virement souhaité (TND)</label>
          <input type="text" id="plafond_virement" name="plafond_virement" value="<?= htmlspecialchars($formData['plafond_virement'] ?? '') ?>" placeholder="1000">
          <?php if (isset($formErrors['plafond_virement'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_virement']) ?></div>
          <?php else: ?>
          <div id="err_plafond_virement" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php" class="btn-ghost">Annuler</a>
        <button type="submit" class="btn-primary">Envoyer la demande</button>
      </div>
    </form>
  </div>

  <?php elseif ($showCarteForm): ?>
  
  <div class="section-head" style="margin-bottom:.3rem">
    <div class="section-title">Demander une nouvelle carte</div>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php<?= $selected?'?id_compte='.$selected->getIdCompte():'' ?>" class="btn-ghost">✕ Annuler</a>
  </div>
  <div class="demand-card">
    <div class="info-banner">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
      Votre demande sera traitée par un agent bancaire sous 48h ouvrées.
    </div>
    <form id="form_carte" method="POST" action="<?= APP_URL ?>/controllers/CarteController.php">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="cvv" value="<?= rand(100,999) ?>">
      <?php if ($selected): ?><input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>"><?php endif; ?>
      <div class="form-grid">
        <div class="form-field">
          <label>Compte lié *</label>
          <select id="id_compte" name="id_compte">
            <option value="">— Sélectionner un compte —</option>
            <?php foreach ($comptes as $c):
              $isActif = $c->getStatut() === 'actif';
            ?>
            <option value="<?= $c->getIdCompte() ?>"
              <?= ($selected && $selected->getIdCompte()===$c->getIdCompte()) ? 'selected' : '' ?>
              <?= !$isActif ? 'disabled style="color:#6B7280"' : '' ?>>
              <?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> — IBAN ···<?= substr($c->getIban(),-6) ?>
              <?= !$isActif ? ' ('.$c->getStatut().')' : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($comptes)): ?>
          <div style="font-size:.7rem;color:var(--rose);margin-top:.3rem">⚠ Aucun compte disponible. <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte" style="color:var(--blue)">Ouvrir un compte d'abord.</a></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Type de carte</label>
          <select name="type_carte">
            <option value="debit">Débit</option>
            <option value="credit">Crédit</option>
            <option value="prepayee">Prépayée</option>
          </select>
        </div>
        <div class="form-field">
          <label>Réseau</label>
          <select name="reseau">
            <option value="visa">Visa</option>
            <option value="mastercard">Mastercard</option>
          </select>
        </div>
        <div class="form-field">
          <label>Nom du titulaire</label>
          <input type="text" id="titulaire_nom" name="titulaire_nom" value="<?= htmlspecialchars($formData['titulaire_nom'] ?? strtoupper(($user['prenom']??'').' '.($user['nom']??''))) ?>">
          <?php if (isset($formErrors['titulaire_nom'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['titulaire_nom']) ?></div>
          <?php else: ?>
          <div id="err_titulaire_nom" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Plafond paiement / jour (TND)</label>
          <input type="text" id="plafond_input" name="plafond_paiement_jour" value="<?= htmlspecialchars($formData['plafond_paiement_jour'] ?? '') ?>" placeholder="100" oninput="updateStyleHint(this.value)">
          <?php if (isset($formErrors['plafond_paiement_jour'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_paiement_jour']) ?></div>
          <?php else: ?>
          <div id="err_plafond_paiement" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
          <div id="style-hint" style="font-size:.68rem;margin-top:.3rem;color:var(--muted2)">
            → Carte <strong id="style-hint-name">Gold</strong>
            <span style="font-size:.6rem;color:var(--muted)">(< 500 = Classic · ≥ 500 = Gold · ≥ 1000 = Platinum · ≥ 1500 = Titanium)</span>
          </div>
        </div>
        <div class="form-field">
          <label>Plafond retrait / jour (TND)</label>
          <input type="text" id="plafond_retrait" name="plafond_retrait_jour" value="<?= htmlspecialchars($formData['plafond_retrait_jour'] ?? '') ?>" placeholder="100">
          <?php if (isset($formErrors['plafond_retrait_jour'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_retrait_jour']) ?></div>
          <?php else: ?>
          <div id="err_plafond_retrait" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Date d'expiration</label>
          <input type="text" id="date_expiration" name="date_expiration" value="<?= date('Y-m',strtotime('+4 years')) ?>" readonly style="background-color: var(--surface2); color: var(--muted); cursor: not-allowed;">
        </div>
      </div>
      <div class="form-actions">
        <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php<?= $selected?'?id_compte='.$selected->getIdCompte():'' ?>" class="btn-ghost">Annuler</a>
        <button type="submit" class="btn-primary">Envoyer la demande</button>
      </div>
    </form>
  </div>

  <?php elseif ($showAttente): ?>
  <!-- ══ EN ATTENTE TAB ════════════════════════════════ -->
  <div class="section-head">
    <div class="section-title">Demandes en attente de validation</div>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php" class="btn-ghost">← Retour</a>
  </div>
  <?php if ($pendingCount===0): ?>
  <div style="text-align:center;padding:3rem;color:var(--muted)">
    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:.8rem;display:block;margin-inline:auto"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
    <div style="font-size:.88rem">Aucune demande en attente.</div>
  </div>
  <?php else: ?>
  <?php if (!empty($pendingComptes)): ?>
  <div class="attente-section">
    <div class="attente-header">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      <span class="attente-title">Comptes (<?= count($pendingComptes) ?>)</span>
    </div>
    <?php foreach ($pendingComptes as $c): ?>
    <div class="attente-item">
      <div class="attente-icon"><svg width="15" height="15" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg></div>
      <div class="attente-info">
        <div class="attente-label"><?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> — <?= htmlspecialchars($c->getDevise()) ?></div>
        <div class="attente-meta">IBAN: <?= htmlspecialchars(substr($c->getIban(),0,16)).'...' ?></div>
      </div>
      <?= badgeCompte($c->getStatut()) ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($pendingCartes)): ?>
  <div class="attente-section" style="border-color:rgba(79,142,247,.2)">
    <div class="attente-header">
      <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      <span class="attente-title" style="color:var(--blue)">Cartes (<?= count($pendingCartes) ?>)</span>
    </div>
    <?php foreach ($pendingCartes as $item): $carte=$item['carte']; $cpt=$item['compte']; ?>
    <div class="attente-item">
      <div class="attente-icon" style="background:var(--blue-light);border-color:var(--border2)">
        <svg width="15" height="15" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      </div>
      <div class="attente-info">
        <div class="attente-label"><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> <?= strtoupper($carte->getReseau()) ?> — <?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
        <div class="attente-meta">Compte: <?= htmlspecialchars(ucfirst($cpt->getTypeCompte())) ?></div>
      </div>
      <?= badgeCarte($carte->getStatut()) ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <?php elseif (empty($comptes)): ?>
  <!-- ══ NO ACCOUNTS ═══════════════════════════════════ -->
  <div style="text-align:center;padding:4rem 1rem;color:var(--muted)">
    <svg width="52" height="52" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:1rem;opacity:.3;display:block;margin-inline:auto"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
    <div style="font-size:.9rem;margin-bottom:.5rem">Aucun compte bancaire</div>
    <div style="font-size:.78rem;margin-bottom:1.3rem">Soumettez une demande pour ouvrir votre premier compte.</div>
    <?php if ($isKycVerifie): ?>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte" class="btn-primary">Demander un compte</a>
    <?php else: ?>
    <div class="notice-msg notice-amber" style="display:inline-block;margin-top:1rem;">🔒 Votre identité doit être vérifiée (KYC) pour ouvrir un compte.</div>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- ══ MAIN ACCOUNT VIEW ══════════════════════════════ -->

  <?php if (count($comptes)>1): ?>
  <div class="accounts-switcher">
    <?php foreach ($comptes as $c): ?>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?id_compte=<?= $c->getIdCompte() ?>"
       class="acct-tab <?= ($selected&&$selected->getIdCompte()===$c->getIdCompte())?'active':'' ?>">
      <?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> ···<?= substr($c->getIban(),-4) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Balance card -->
  <div class="balance-card">
    <div class="bc-top">
      <div>
        <div class="bc-label">Solde disponible</div>
        <div class="bc-amount"><?= number_format((float)$selected->getSolde(),3,'.',' ') ?><span><?= htmlspecialchars($selected->getDevise()) ?></span></div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.45rem">
        <div class="bc-type-badge"><?= htmlspecialchars(ucfirst($selected->getTypeCompte())) ?></div>
        <?= badgeCompte($selected->getStatut()) ?>
      </div>
    </div>
    <div class="bc-iban-row">
      <div>
        <div class="bc-iban-label">IBAN</div>
        <div class="bc-iban"><?= htmlspecialchars($selected->getIban()) ?></div>
      </div>
      <div style="text-align:right">
        <div class="bc-iban-label">Date d'ouverture</div>
        <div style="font-size:.82rem;color:var(--muted2)"><?= htmlspecialchars($selected->getDateOuverture()) ?></div>
      </div>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.6rem;margin-top:.85rem;padding-top:.8rem;border-top:1px solid var(--border)">
      <div style="font-size:.72rem;color:var(--muted2)">Plafond virement: <strong style="color:var(--text)"><?= number_format((float)$selected->getPlafondVirement(),0,'.',' ') ?> TND</strong></div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?php if ($selected->getStatut()==='actif'): ?>
        <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:contents">
          <input type="hidden" name="action" value="demande_suppression">
          <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
          <button type="submit" class="acct-action-btn aa-neutral" onclick="return confirm('Demander la suppression de ce compte ?\nUn agent bancaire traitera votre demande.')">Dem. suppression</button>
        </form>
        <?php elseif ($selected->getStatut()==='bloque'): ?>
        <div class="notice-msg notice-amber">🔒 Ce compte est bloqué. Contactez votre conseiller.</div>
        <?php elseif ($selected->getStatut()==='demande_cloture'): ?>
        <div class="notice-msg notice-amber">⏳ Demande de clôture en attente de validation.</div>
        <?php elseif ($selected->getStatut()==='demande_suppression'): ?>
        <div class="notice-msg notice-amber">⏳ Demande de suppression en attente de validation.</div>
        <?php elseif ($selected->getStatut()==='en_attente'): ?>
        <div class="notice-msg notice-amber">⏳ Compte en attente d'activation.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Cards section -->
  <div>
    <div class="section-head" style="margin-bottom:.9rem">
      <div class="section-title">Mes cartes (<?= count($cartes) ?>)</div>
      <?php if ($selected->getStatut()==='actif'): ?>
        <?php if ($isKycVerifie): ?>
        <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte&id_compte=<?= $selected->getIdCompte() ?>" class="btn-ghost">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Nouvelle carte
        </a>
        <?php else: ?>
        <button class="btn-ghost" style="opacity:0.5;cursor:not-allowed;" onclick="alert('Vérification KYC requise pour demander une carte.')" title="Vérification KYC requise">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Nouvelle carte 🔒
        </button>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if (empty($cartes)): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:2rem 1.5rem;text-align:center;color:var(--muted)">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:.6rem;display:block;margin-inline:auto"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      <div style="font-size:.82rem;margin-bottom:.7rem">Aucune carte liée à ce compte.</div>
      <?php if ($selected->getStatut()==='actif'): ?>
        <?php if ($isKycVerifie): ?>
        <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte&id_compte=<?= $selected->getIdCompte() ?>" class="btn-primary">Demander une carte</a>
        <?php else: ?>
        <div class="notice-msg notice-amber" style="display:inline-block;margin-top:1rem;">🔒 Vérification KYC requise pour demander une carte.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="cartes-grid">
      <?php foreach ($cartes as $i=>$carte):
        $cardId = 'card-'.$carte->getIdCarte();
        $isBloque = $carte->getStatut()==='bloquee';
        $gradClass = $isBloque ? 'rcg-bloque' : 'rcg-'.($carte->getStyle()?:'standard');
        $reseau = strtolower($carte->getReseau());
        $expRaw = $carte->getDateExpiration(); // YYYY-MM or YYYY-MM-DD
        $expDisplay = '';
        if ($expRaw && strlen($expRaw) >= 7) {
            $expDisplay = substr($expRaw, 5, 2) . '/' . substr($expRaw, 2, 2);
        } else {
            $expDisplay = $expRaw ?: '--/--';
        }
      ?>
      <div class="carte-card">

        <!-- 3D FLIP CARD -->
        <div class="card-scene" id="<?= $cardId ?>" onclick="flipCard('<?= $cardId ?>')">
          <div class="card-inner">

            <!-- FRONT -->
            <div class="card-face">
              <div class="real-card-front <?= $gradClass ?>">
                <div class="card-holo"></div>
                <div class="rcf-top">
                  <div class="rcf-chip">
                    <div class="rcf-chip-grid"><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div></div>
                  </div>
                  <div style="display:flex;align-items:center;gap:.4rem;position:relative;z-index:1;">
                    <div style="font-size:.5rem;font-weight:700;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.12em;background:rgba(255,255,255,.08);border-radius:3px;padding:1px 5px;"><?= strtoupper(styleLabel($carte->getStyle()?:'standard')) ?></div>
                    <svg class="rcf-contactless" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10"/><path d="M12 6c3.31 0 6 2.69 6 6s-2.69 6-6 6"/><path d="M12 10c1.1 0 2 .9 2 2s-.9 2-2 2"/></svg>
                  </div>
                </div>
                <div class="rcf-number"><?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
                <div class="rcf-bottom">
                  <div>
                    <div class="rcf-holder-lbl">Card Holder</div>
                    <div class="rcf-holder-val"><?= htmlspecialchars($carte->getTitulaireNom()) ?></div>
                  </div>
                  <div style="text-align:center">
                    <div class="rcf-exp-lbl">Expires</div>
                    <div class="rcf-exp-val"><?= htmlspecialchars($expDisplay) ?></div>
                  </div>
                  <?php if ($reseau==='visa'): ?>
                  <div class="rcf-visa">VISA</div>
                  <?php else: ?>
                  <div class="rcf-mc"><div class="rcf-mc-l"></div><div class="rcf-mc-r"></div></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- BACK -->
            <div class="card-face card-face-back">
              <div class="real-card-back <?= $gradClass ?>">
                <div class="rcb-stripe"></div>
                <div class="rcb-sig-area">
                  <div class="rcb-sig-lbl">Signature autorisée</div>
                  <div class="rcb-sig-box">
                    <div class="rcb-sig-strip"></div>
                    <div class="rcb-cvv"><?= htmlspecialchars($carte->getCvvDisplay() ?: '•••') ?></div>
                  </div>
                </div>
                <div class="rcb-footer">
                  <div>
                    <div class="rcb-bank">LegalFin</div>
                    <div style="font-size:.45rem;color:rgba(255,255,255,.22);margin-top:2px;">Service client: 71 000 000</div>
                  </div>
                  <?php if ($reseau==='visa'): ?>
                  <div class="rcf-visa" style="font-size:.8rem;opacity:.4;">VISA</div>
                  <?php else: ?>
                  <div class="rcf-mc"><div class="rcf-mc-l" style="width:18px;height:18px;opacity:.6;"></div><div class="rcf-mc-r" style="width:18px;height:18px;opacity:.6;"></div></div>
                  <?php endif; ?>
                </div>
                <div class="rcb-fine">Cette carte est la propriété de LegalFin. En cas de perte ou vol, appelez le 71 000 000.</div>
              </div>
            </div>

          </div><!-- .card-inner -->
        </div><!-- .card-scene -->

        <div class="card-flip-hint">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
          Cliquez pour retourner
        </div>

        <!-- DETAILS -->
        <div class="card-info-section">
          <!-- Header row: type + status -->
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.8rem;flex-wrap:wrap;">
            <span style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> <?= strtoupper($carte->getReseau()) ?></span>
            <?= badgeCarte($carte->getStatut()) ?>
            <span style="margin-left:auto;font-size:.62rem;background:var(--surface2);border:1px solid var(--border);border-radius:4px;padding:2px 7px;color:var(--muted2);text-transform:uppercase;letter-spacing:.06em;"><?= strtoupper(styleLabel($carte->getStyle()?:'standard')) ?></span>
          </div>

          <!-- Full info grid -->
          <div class="carte-info-grid" style="grid-template-columns:1fr 1fr;gap:.65rem;">
            <div class="carte-info-item">
              <div class="ci-label">Titulaire</div>
              <div class="ci-val" style="font-size:.78rem;"><?= htmlspecialchars($carte->getTitulaireNom()) ?></div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Numéro</div>
              <div class="ci-val" style="font-family:var(--fm);font-size:.75rem;letter-spacing:.06em;"><?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Plafond paiement/j</div>
              <div class="ci-val"><?= number_format((float)$carte->getPlafondPaiementJour(),0,'.',' ') ?> TND</div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Plafond retrait/j</div>
              <div class="ci-val"><?= number_format((float)$carte->getPlafondRetraitJour(),0,'.',' ') ?> TND</div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Réseau</div>
              <div class="ci-val"><?= strtoupper($carte->getReseau()) ?></div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Expiration</div>
              <div class="ci-val"><?= htmlspecialchars($expDisplay) ?></div>
            </div>
            <?php if ($carte->getDateEmission()): ?>
            <div class="carte-info-item">
              <div class="ci-label">Date d'émission</div>
              <div class="ci-val"><?= htmlspecialchars($carte->getDateEmission()) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($carte->getMotifBlocage()): ?>
            <div class="carte-info-item" style="grid-column:1/-1;">
              <div class="ci-label" style="color:var(--rose);">Motif blocage</div>
              <div class="ci-val" style="color:var(--rose);font-size:.77rem;"><?= htmlspecialchars($carte->getMotifBlocage()) ?></div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Status notices -->
          <?php if ($carte->getStatut()==='demande_cloture' || $carte->getStatut()==='demande_suppression'): ?>
          <div class="notice-msg notice-amber" style="margin-top:.7rem;">⏳ Demande de suppression en cours de traitement.</div>
          <?php elseif ($carte->getStatut()==='inactive'): ?>
          <div class="notice-msg notice-amber" style="margin-top:.7rem;">⏳ En attente d'activation par un agent bancaire.</div>
          <?php elseif ($carte->getStatut()==='expiree'): ?>
          <div class="notice-msg notice-red" style="margin-top:.7rem;">✕ Cette carte est expirée.</div>
          <div class="carte-actions-row" style="margin-top:.8rem;">
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="width:100%;">
              <input type="hidden" name="action" value="demander_reactivation">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="btn-primary" style="width:100%; font-size:.75rem; padding:.5rem;">Demander Réactivation</button>
            </form>
          </div>
          <?php elseif ($carte->getStatut()==='demande_reactivation'): ?>
          <div class="notice-msg notice-amber" style="margin-top:.7rem;">⏳ Demande de réactivation en cours.</div>
          <?php else: ?>
          <!-- Action buttons -->
          <div class="carte-actions-row" style="margin-top:.8rem;">
            <?php if ($carte->getStatut()==='bloquee'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1;">
              <input type="hidden" name="action" value="debloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="carte-action-btn success" style="width:100%;" onclick="return confirm('Débloquer cette carte ?')">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Débloquer
              </button>
            </form>
            <?php else: ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1;">
              <input type="hidden" name="action" value="bloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="carte-action-btn danger" style="width:100%;" onclick="return confirm('Bloquer cette carte ?')">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Bloquer
              </button>
            </form>
            <?php endif; ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1;">
              <input type="hidden" name="action" value="demande_suppression">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="carte-action-btn danger" style="width:100%;" onclick="return confirm('Demander la suppression de cette carte ?')">
                Supprimer
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>

      </div><!-- .carte-card -->
      <?php endforeach; ?>
    </div>
    <?php endif; // cartes ?>

    <script>
    function flipCard(id){document.getElementById(id).classList.toggle('flipped');}
    function updateStyleHint(val){
        var v=parseFloat(val)||0, name='Classic';
        if(v>=1500) name='Titanium';
        else if(v>=1000) name='Platinum';
        else if(v>=500) name='Gold';
        var el=document.getElementById('style-hint-name');
        if(el) el.textContent=name;
    }
    // ── NOTIFICATION PANEL (сихed position via getBoundingClientRect) ──
    function toggleNotifPanel(e){
        e.stopPropagation();
        var panel=document.getElementById('notif-panel');
        var bell=document.getElementById('notif-bell');
        var open=panel.classList.toggle('np-open');
        bell.classList.toggle('np-bell-active',open);
        if(open){
            var rect=bell.getBoundingClientRect();
            panel.style.top=(rect.bottom+10)+'px';
            panel.style.right=(window.innerWidth-rect.right)+'px';
            panel.style.left='auto';
        }
    }
    function closeNotifPanel(){
        var p=document.getElementById('notif-panel'),b=document.getElementById('notif-bell');
        if(p) p.classList.remove('np-open');
        if(b) b.classList.remove('np-bell-active');
    }
    document.addEventListener('click',function(e){
        var panel=document.getElementById('notif-panel'),bell=document.getElementById('notif-bell');
        if(panel&&panel.classList.contains('np-open')&&!panel.contains(e.target)&&bell&&!bell.contains(e.target)) closeNotifPanel();
    });
    document.addEventListener('keydown',function(e){
        if(e.key==='Escape'){closeNotifPanel();cbClose();}
    });
    // ── CHATBOT ──────────────────────────────────────────
    <?php if (!$showCompteForm && !$showCarteForm && !$showAttente): ?>
    var cbHistory=[],cbIsOpen=false;
    var cbUrl='<?= APP_URL ?>/controllers/ChatbotController.php';
    function cbToggle(){
        cbIsOpen=!cbIsOpen;
        var panel=document.getElementById('cb-panel');
        var iconO=document.getElementById('cb-icon-open');
        var iconC=document.getElementById('cb-icon-close');
        panel.classList.toggle('cb-open',cbIsOpen);
        iconO.style.display=cbIsOpen?'none':'block';
        iconC.style.display=cbIsOpen?'block':'none';
        
        if(cbIsOpen) {
            // Wake Up Sequence
            var watermarkLogo = document.querySelector('.cb-bot-watermark');
            var headerRobotHead = watermarkLogo ? watermarkLogo.querySelector('.cb-robot-head-grp') : null;
            var butterflyEl = document.getElementById('magic-butterfly');
            if(headerRobotHead) headerRobotHead.classList.add('is-sleeping');
            if(butterflyEl) butterflyEl.style.display = 'block';
            
            isWakingUp = true;
            isResting = false;
            
            setTimeout(() => {
                var pRect = panel.getBoundingClientRect();
                var waterRect = watermarkLogo.getBoundingClientRect();
                var targetX = (waterRect.left - pRect.left) + waterRect.width/2;
                var targetY = (waterRect.top - pRect.top) + waterRect.height/2 - 70;
                
                var startX = pRect.width + 250, startY = pRect.height + 200;
                bfX = startX; bfY = startY;
                var wakeAnimStart = performance.now();
                
                function wakeStep(time) {
                    var elapsed = time - wakeAnimStart;
                    var progress = Math.min(elapsed / 2200, 1); // Much slower: 2.2s
                    var t = 1 - Math.pow(1 - progress, 4);
                    var curve = Math.sin(progress * Math.PI) * 120;
                    
                    mouseX = startX + (targetX - startX) * t + curve;
                    mouseY = startY + (targetY - startY) * t - Math.abs(curve) * 0.5;
                    
                    if(progress < 1) {
                        requestAnimationFrame(wakeStep);
                    } else {
                        // HIT! Surprise!
                        if(headerRobotHead) {
                            headerRobotHead.classList.remove('is-sleeping');
                            headerRobotHead.classList.add('is-surprised');
                        }
                        
                        // Butterfly fly away sequence
                        var awayStart = performance.now();
                        var hitX = mouseX, hitY = mouseY;
                        var exitX = -200, exitY = -200;
                        
                        function flyAway(time2) {
                            var p2 = Math.min((time2 - awayStart) / 1800, 1); // Much slower: 1.8s
                            var t2 = p2 * p2;
                            bfX = hitX + (exitX - hitX) * t2;
                            bfY = hitY + (exitY - hitY) * t2;
                            
                            if(p2 < 1) {
                                requestAnimationFrame(flyAway);
                            } else {
                                if(butterflyEl) butterflyEl.style.display = 'none';
                                isWakingUp = false;
                                
                                // GREET NOW! Animation is finished.
                                if(document.getElementById('cb-messages').children.length === 0) {
                                    var typing = cbTyping();
                                    setTimeout(function() {
                                        if(typing && typing.parentNode) typing.remove();
                                        cbBotMsg('Bonjour ! 👋 Je suis votre assistant bancaire **LegalFin**.\nTapez **aide** pour voir toutes les options.');
                                    }, 600); 
                                }
                            }
                        }
                        requestAnimationFrame(flyAway);
                        
                        setTimeout(() => { if(headerRobotHead) headerRobotHead.classList.remove('is-surprised'); }, 800);
                    }
                }
                requestAnimationFrame(wakeStep);
            }, 100);
        }

        // Message logic moved inside animation callback above
        if(cbIsOpen) setTimeout(()=>document.getElementById('cb-input').focus(),280);
    }
    function cbClose(){ if(cbIsOpen) cbToggle(); }
    function cbEscHtml(t){ return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function cbFormat(t){
        return t.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
                .replace(/\*(.+?)\*/g,'<em>$1</em>')
                .replace(/`([^`]+)`/g,'<code>$1</code>')
                .replace(/\n/g,'<br>');
    }
    var robotSvg = 
      '<svg width="100%" height="100%" viewBox="0 0 100 100" overflow="visible">' +
        '<path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />' +
        '<polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />' +
        '<g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">' +
           '<rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />' +
           '<circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>' +
        '</g>' +
        '<g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">' +
           '<rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />' +
           '<circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>' +
        '</g>' +
        '<g class="cb-robot-head-grp" style="transform-origin: 50px 46px">' +
          '<rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />' +
          '<path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>' +
          '<rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />' +
          '<rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />' +
          '<path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />' +
          '<circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />' +
          '<rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />' +
          '<g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">' +
            '<rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />' +
            '<rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />' +
          '</g>' +
        '</g>' +
      '</svg>';

    function cbUserMsg(text){
        var m=document.getElementById('cb-messages');
        var d=document.createElement('div'); d.className='cb-msg cb-user';
        d.innerHTML='<span>'+cbEscHtml(text)+'</span>';
        m.appendChild(d); m.scrollTop=m.scrollHeight;
    }
    function cbBotMsg(text){
        var m=document.getElementById('cb-messages');
        var d=document.createElement('div'); d.className='cb-msg cb-bot';
        d.innerHTML='<div class="cb-av">'+robotSvg+'</div><span>'+cbFormat(text)+'</span>';
        m.appendChild(d); m.scrollTop=m.scrollHeight;
    }
    function cbTyping(){
        var m=document.getElementById('cb-messages');
        var d=document.createElement('div'); d.className='cb-msg cb-bot cb-typing-row'; d.id='cb-typing';
        d.innerHTML='<div class="cb-av cb-av-thinking">'+robotSvg+'</div><span class="cb-dots"><span></span><span></span><span></span></span>';
        m.appendChild(d); m.scrollTop=m.scrollHeight; return d;
    }
    function cbSend(){
        var inp=document.getElementById('cb-input');
        var msg=inp.value.trim(); if(!msg) return;
        inp.value='';
        cbUserMsg(msg);
        cbHistory.push({role:'user',content:msg});
        var typing=cbTyping();
        var formData = new URLSearchParams();
        formData.append('message', msg);
        formData.append('lang', currentLang);
        formData.append('history', JSON.stringify(cbHistory.slice(-6)));

        fetch(cbUrl, {
            method: 'POST',
            body: formData
        })
        .then(r=>r.json())
        .then(data=>{
            typing.remove();
            var reply=data.reply||'Désolé, une erreur est survenue.';
            cbBotMsg(reply);
            cbHistory.push({role:'bot',content:reply});
        })
        .catch(()=>{ typing.remove(); cbBotMsg('❌ Impossible de contacter l\'assistant.'); });
    }
    function cbQuick(t){ document.getElementById('cb-input').value=t; cbSend(); }
    
    // Voice & Speech Logic
    var isVoiceEnabled = false;
    var currentLang = 'fr-FR';
    var recognition = null;
    var voices = [];
    var currentVoiceIdx = 0;

    function cbChangeLang(lang) {
        currentLang = lang;
        if (recognition) recognition.lang = lang;
        loadVoices(); // Reload voices for the new language
        
        // Update placeholder
        var ph = "Posez votre question...";
        if(lang === 'en-US') ph = "Ask your question...";
        if(lang === 'ar-SA') ph = "إسأل سؤالك...";
        document.getElementById('cb-input').placeholder = ph;
    }

    function loadVoices() {
        var all = window.speechSynthesis.getVoices();
        voices = all.filter(v => v.lang.startsWith(currentLang.split('-')[0]));
        if (!voices.length) voices = all.filter(v => v.lang.startsWith('fr')); // fallback
        currentVoiceIdx = 0;
    }
    window.speechSynthesis.onvoiceschanged = loadVoices;
    loadVoices();

    function cbCycleVoice() {
        if (!voices.length) loadVoices();
        currentVoiceIdx = (currentVoiceIdx + 1) % (voices.length || 1);
        var v = voices[currentVoiceIdx];
        if(v) alert("Voix changée : " + v.name);
    }

    function cbStopSpeech() {
        window.speechSynthesis.cancel();
        document.getElementById('cb-stop-btn').style.display = 'none';
    }

    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        var SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRec();
        recognition.lang = 'fr-FR';
        recognition.continuous = false;
        recognition.interimResults = false;
        
        recognition.onresult = function(event) {
            var text = event.results[0][0].transcript;
            document.getElementById('cb-input').value = text;
            cbSend();
            document.getElementById('cb-mic-btn').style.color = '#4F8EF7';
        };
        recognition.onerror = function() {
            document.getElementById('cb-mic-btn').style.color = '#4F8EF7';
        };
        recognition.onend = function() {
            document.getElementById('cb-mic-btn').style.color = '#4F8EF7';
        };
    }

    function cbToggleVoice() {
        isVoiceEnabled = !isVoiceEnabled;
        var btn = document.getElementById('cb-voice-toggle');
        var icon = document.getElementById('cb-voice-icon');
        if (isVoiceEnabled) {
            btn.style.color = '#2DD4BF';
            icon.innerHTML = '<path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07M19 12h.01"/>';
        } else {
            cbStopSpeech();
            btn.style.color = 'rgba(255,255,255,0.7)';
            icon.innerHTML = '<path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/><line x1="1" y1="1" x2="23" y2="23"/>';
        }
    }

    function cbStartMic() {
        if (!recognition) return alert("Votre navigateur ne supporte pas la reconnaissance vocale.");
        cbStopSpeech();
        document.getElementById('cb-mic-btn').style.color = '#ef4444';
        recognition.start();
    }

    function cbSpeak(text) {
        if (!isVoiceEnabled) return;
        window.speechSynthesis.cancel(); // Stop current before new
        var clean = text.replace(/\*\*/g, '').replace(/\*/g, '').replace(/`/g, '');
        var msg = new SpeechSynthesisUtterance(clean);
        
        if (voices.length > 0) {
            msg.voice = voices[currentVoiceIdx];
        }
        msg.lang = currentLang;
        msg.rate = 1.0; 
        
        msg.onstart = function() {
            document.getElementById('cb-stop-btn').style.display = 'block';
        };
        msg.onend = function() {
            document.getElementById('cb-stop-btn').style.display = 'none';
        };
        window.speechSynthesis.speak(msg);
    }

    // Wrap cbBotMsg to include speech
    var originalCbBotMsg = cbBotMsg;
    cbBotMsg = function(t) {
        originalCbBotMsg(t);
        cbSpeak(t);
    };
    // Init on page load
    document.addEventListener('DOMContentLoaded',function(){
        var inp=document.getElementById('plafond_input');
        if(inp) updateStyleHint(inp.value);
    });
    
    // Complex Tracking for Butterfly and Robot
    var butterflyInfo = document.createElement('div');
    butterflyInfo.id = 'magic-butterfly';
    butterflyInfo.innerHTML = '<svg width="30" height="30" viewBox="0 0 24 24" overflow="visible">' +
     '<g style="transform-origin: 12px 12px; animation: b-flap 0.15s infinite alternate ease-in-out;">' +
       '<path d="M 11 12 C 4 2, 0 8, 11 18 Z" fill="#4F8EF7" stroke="#fff" stroke-width="0.5"/>' +
       '<path d="M 11 12 C 5 18, 2 22, 11 18 Z" fill="#2DD4BF"/>' +
     '</g>' +
     '<g style="transform-origin: 12px 12px; animation: b-flap-r 0.15s infinite alternate ease-in-out;">' +
       '<path d="M 13 12 C 20 2, 24 8, 13 18 Z" fill="#4F8EF7" stroke="#fff" stroke-width="0.5"/>' +
       '<path d="M 13 12 C 19 18, 22 22, 13 18 Z" fill="#2DD4BF"/>' +
     '</g>' +
     '<rect x="11" y="9" width="2" height="9" rx="1" fill="#fff"/>' +
    '</svg>';

    var realMouseX = 180, realMouseY = 150;
    var realMouseGlobX = window.innerWidth/2, realMouseGlobY = window.innerHeight/2;
    var mouseX = realMouseX, mouseY = realMouseY;
    var bfX = mouseX, bfY = mouseY;
    var isWakingUp = false;
    var isResting = false;

    document.addEventListener('DOMContentLoaded', function() {
        var panelEl = document.getElementById('cb-panel');
        if(panelEl) {
            panelEl.appendChild(butterflyInfo);
        }

        // Tracking globally!
        document.addEventListener('mousemove', function(e) {
            realMouseGlobX = e.clientX;
            realMouseGlobY = e.clientY;
            
            if(panelEl) {
                var rect = panelEl.getBoundingClientRect();
                realMouseX = e.clientX - rect.left;
                realMouseY = e.clientY - rect.top;
            }
            
            if(!isWakingUp && !isResting) {
                mouseX = realMouseX;
                mouseY = realMouseY;
            }
        });

        requestAnimationFrame(loopAnim);
    });

    var handStates = []; // To keep track of smooth hand positions

    function loopAnim() {
        var panelEl = document.getElementById('cb-panel');
        if(!panelEl) return requestAnimationFrame(loopAnim);

        var pRect = panelEl.getBoundingClientRect();
        var winW = window.innerWidth;
        var winH = window.innerHeight;

        var heads = document.querySelectorAll('.cb-robot-head-grp');
        var eyes = document.querySelectorAll('.cb-robot-eyes-grp');
        var hands = document.querySelectorAll('.cb-robot-hand-grp');

        var finalRot = 0;

        if(!isResting) {
            var dx = mouseX - bfX;
            var dy = mouseY - bfY;
            bfX += dx * 0.05; // Slower butterfly tracking
            bfY += dy * 0.05;
            finalRot = dx * 0.2;
            if(finalRot > 30) finalRot = 30; if(finalRot < -30) finalRot = -30;
        }

        heads.forEach(function(head, idx) {
            if (head.closest('.cb-panel') && !head.closest('.cb-open')) return;
            if (head.offsetParent === null && !head.closest('.cb-fab')) return;

            if(head.classList.contains('is-sleeping')) {
                head.style.transform = 'rotate(-18deg) translateY(6px)';
                if(eyes[idx]) eyes[idx].style.transform = 'scaleY(0.1) translateY(4px)';
                if(hands[idx]) {
                   // Reset hands for sleep
                   hands.forEach(h => {
                       if(h.closest('.cb-robot-head-grp') === head.parentElement) {
                           h.style.transform = 'translateY(10px)';
                       }
                   });
                }
                return;
            }

            var rect = head.getBoundingClientRect();
            if(rect.width === 0) return;
            var rX = rect.left + rect.width / 2;
            var rY = rect.top + rect.height / 2;
            
            var targetGlobalX, targetGlobalY;
            // Use realMouseGlobX/Y to track ACTUAL mouse for robot senses even if butterfly is resting
            targetGlobalX = isWakingUp ? (pRect.left + bfX) : realMouseGlobX;
            targetGlobalY = isWakingUp ? (pRect.top + bfY) : realMouseGlobY;

            var hDx = targetGlobalX - rX;
            var hDy = targetGlobalY - rY;
            var dist = Math.hypot(hDx, hDy);

            var tilt = (hDx / winW) * 40; 
            var pitch = (hDy / winH) * 45;
            head.style.transform = 'rotate(' + tilt + 'deg) translateY(' + (pitch * 0.3) + 'px)';

            // Butterly position when WAKING UP
            if(isWakingUp && head.closest('.cb-bot-watermark')) {
                // Keep butterfly consistent with its flying coordinates
            }

            // Sync hands for this head
            var robotHands = [];
            hands.forEach(h => {
                if(h.closest('.cb-robot-head-grp') === null && h.closest('svg') === head.closest('svg')) {
                    robotHands.push(h);
                }
            });

            robotHands.forEach((h, hIdx) => {
                var isRightHand = h.classList.contains('cb-robot-hand-right');
                
                // Hand smoothing logic
                if(!handStates[idx]) handStates[idx] = {};
                if(!handStates[idx][hIdx]) handStates[idx][hIdx] = {x:0, y:0, r:0};
                
                var hState = handStates[idx][hIdx];
                var handReach = Math.min(dist / 6, 35);
                var hRad = Math.atan2(hDy, hDx);
                var tx = Math.cos(hRad) * handReach;
                var ty = Math.sin(hRad) * handReach;
                var tr = hRad * 180 / Math.PI + (isRightHand ? -10 : 10);
                
                // Smoother transition (LERP) - halved speed
                hState.x += (tx - hState.x) * 0.06;
                hState.y += (ty - hState.y) * 0.06;
                hState.r += (tr - hState.r) * 0.06;
                
                h.style.transform = 'translate(' + hState.x + 'px, ' + hState.y + 'px) rotate(' + hState.r + 'deg)';
            });

            if(eyes[idx]) {
                var eyeDist = Math.min(dist / 25, 12);
                var eRad = Math.atan2(hDy, hDx);
                eyes[idx].style.transform = 'translate(' + (Math.cos(eRad) * eyeDist) + 'px,' + (Math.sin(eRad) * eyeDist) + 'px)';
            }
        });

        butterflyInfo.style.transform = 'translate(' + (bfX - 15) + 'px, ' + (bfY - 15) + 'px) rotate(' + finalRot + 'deg)';
        requestAnimationFrame(loopAnim);
    }
    requestAnimationFrame(loopAnim);
    <?php endif; ?>
    </script>
  </div>

  <?php endif; // main view ?>
  </div>
</div>
<!-- ══ NOTIFICATION PANEL (fixed, body-level) ════════════ -->
<div class="notif-panel" id="notif-panel">
  <div class="np-header">
    <div class="np-title">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
      Notifications
    </div>
    <?php if ($pendingCount>0): ?>
    <span class="np-count"><?= $pendingCount ?></span>
    <?php endif; ?>
  </div>
  <div class="np-body">
  <?php if ($pendingCount===0): ?>
    <div class="np-empty">
      <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
      <div>Aucune notification</div>
    </div>
  <?php else: ?>
    <?php if (!empty($pendingComptes)): ?>
    <div class="np-section-label">Comptes</div>
    <?php foreach ($pendingComptes as $c): ?>
    <div class="np-item">
      <div class="np-item-icon np-icon-amber"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg></div>
      <div class="np-item-info">
        <div class="np-item-name"><?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> — <?= htmlspecialchars($c->getDevise()) ?></div>
        <div class="np-item-sub">IBAN: ···<?= htmlspecialchars(substr($c->getIban(),-6)) ?></div>
      </div>
      <?= badgeCompte($c->getStatut()) ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($pendingCartes)): ?>
    <div class="np-section-label" style="margin-top:.6rem">Cartes</div>
    <?php foreach ($pendingCartes as $item): $carte=$item['carte']; $cpt=$item['compte']; ?>
    <div class="np-item">
      <div class="np-item-icon np-icon-blue"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></div>
      <div class="np-item-info">
        <div class="np-item-name"><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> <?= strtoupper($carte->getReseau()) ?></div>
        <div class="np-item-sub">···<?= htmlspecialchars(substr($carte->getNumeroCarte(),-4)) ?> — <?= htmlspecialchars(ucfirst($cpt->getTypeCompte())) ?></div>
      </div>
      <?= badgeCarte($carte->getStatut()) ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
  </div>
  <?php if ($pendingCount>0): ?>
  <div class="np-footer">
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=attente" onclick="closeNotifPanel()">Voir toutes les demandes →</a>
  </div>
  <?php endif; ?>
</div>

<!-- ══ CHATBOT WIDGET ══════════════════════════════ -->
<?php if (!$showCompteForm && !$showCarteForm && !$showAttente): ?>
<div class="cb-widget" id="cb-widget">

  <!-- Floating action button -->
  <button class="cb-fab" id="cb-fab" onclick="cbToggle()" title="Assistant LegalFin">
    <svg id="cb-icon-open" width="34" height="34" viewBox="0 0 100 100" overflow="visible" style="display:block">
      <path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />
      <polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />
      <g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">
         <rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
         <circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
      </g>
      <g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">
         <rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
         <circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
      </g>
      <g class="cb-robot-head-grp" style="transform-origin: 50px 46px">
        <rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />
        <path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>
        <rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
        <rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
        <path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />
        <circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />
        <rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />
        <g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">
          <rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
          <rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
        </g>
      </g>
    </svg>
    <svg id="cb-icon-close" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="display:none">
      <path d="M18 6L6 18M6 6l12 12"/>
    </svg>
    <?php if ($pendingCount>0): ?><div class="cb-fab-dot"></div><?php endif; ?>
  </button>

  <!-- Chat panel -->
  <div class="cb-panel" id="cb-panel" style="position:relative;">

    <!-- Background Robot Watermark -->
    <div style="position:absolute; inset:0; top:50px; display:flex; align-items:center; justify-content:center; opacity:0.1; pointer-events:none; z-index:0; overflow:hidden;">
      <svg id="cb-watermark-robot" class="cb-bot-watermark" width="260" height="260" viewBox="0 0 100 100" overflow="visible" style="transform:translateY(20px);">
        <path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />
        <polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />
        <g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">
           <rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
           <circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
        </g>
        <g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">
           <rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
           <circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
        </g>
        <g class="cb-robot-head-grp" style="transform-origin: 50px 46px">
          <rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />
          <path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>
          <rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
          <rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
          <path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />
          <circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />
          <rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />
          <g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">
            <rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
            <rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
          </g>
        </g>
      </svg>
    </div>

    <!-- Header -->
    <div class="cb-header" style="position:relative; z-index:1;">
      <div class="cb-header-left">
        <div class="cb-hav">
          <svg width="28" height="28" viewBox="0 0 100 100" overflow="visible">
            <path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />
            <polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />
            <g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">
               <rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
               <circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
            </g>
            <g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">
               <rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
               <circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
            </g>
            <g class="cb-robot-head-grp" style="transform-origin: 50px 46px">
              <rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />
              <path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>
              <rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
              <rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
              <path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />
              <circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />
              <rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />
              <g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">
                <rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
                <rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
              </g>
            </g>
          </svg>
        </div>
        <div>
          <div class="cb-htitle">Assistant LegalFin</div>
          <div class="cb-hsub"><span class="cb-online-dot"></span> En ligne</div>
        </div>
      </div>
        <div class="cb-lang-selector" style="margin-right:4px;">
          <select id="cb-lang-select" onchange="cbChangeLang(this.value)" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #fff; font-size: 0.68rem; font-weight: 600; padding: 4px 8px; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
            <option value="fr-FR" style="background:#1a1d2b;">FR</option>
            <option value="en-US" style="background:#1a1d2b;">EN</option>
            <option value="ar-SA" style="background:#1a1d2b;">AR</option>
          </select>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
        <button id="cb-stop-btn" onclick="cbStopSpeech()" title="Arrêter de parler" style="background:none; border:none; cursor:pointer; color:#ef4444; display:none; padding:4px;">
           <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
        </button>
        <button id="cb-voice-cycle" onclick="cbCycleVoice()" title="Changer de voix" style="background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.7); display:flex; padding:4px;">
           <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg>
        </button>
        <button class="cb-voice-toggle" id="cb-voice-toggle" onclick="cbToggleVoice()" title="Activer/Désactiver la voix" style="background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.7); display:flex; padding:4px;">
           <svg width="16" height="16" id="cb-voice-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
        </button>
        <button class="cb-hclose" onclick="cbClose()">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
    </div>

    <!-- Messages -->
    <div class="cb-messages" id="cb-messages" style="position:relative; z-index:1;"></div>

    <!-- Quick buttons -->
    <div class="cb-quick">
      <button onclick="cbQuick('Mon solde')">💰 Solde</button>
      <button onclick="cbQuick('Mes cartes')">💳 Cartes</button>
      <button onclick="cbQuick('Mon IBAN')">🏦 IBAN</button>
      <button onclick="cbQuick('aide')">❓ Aide</button>
    </div>

    <!-- Input row -->
    <div class="cb-footer" style="gap:8px;">
      <button class="cb-mic-btn" id="cb-mic-btn" onclick="cbStartMic()" title="Parler" style="background:none; border:none; cursor:pointer; color:#4F8EF7; display:flex; padding:5px; transition: 0.2s;">
         <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2M12 19v4M8 23h8"/></svg>
      </button>
      <input type="text" id="cb-input" class="cb-input" placeholder="Posez votre question..." autocomplete="off"
             onkeydown="if(event.key==='Enter')cbSend()" style="flex:1;">
      <button class="cb-send" onclick="cbSend()">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
          <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>

  </div>
</div>
<?php endif; ?>

</body>
</html>
