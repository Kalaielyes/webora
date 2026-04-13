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

$pendingComptes = array_values(array_filter($comptes, fn($c)=>in_array($c->getStatut(),['en_attente','demande_cloture'])));
$pendingCartes  = [];
foreach ($comptes as $c) {
    foreach (CarteController::findByCompte($c->getIdCompte()) as $carte) {
        if (in_array($carte->getStatut(),['inactive','demande_cloture','demande_suppression'])) $pendingCartes[]=['carte'=>$carte,'compte'=>$c];
    }
}
$showCompteForm = (!empty($_GET['form']) && $_GET['form']==='compte');
$showCarteForm  = (!empty($_GET['form']) && $_GET['form']==='carte');
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
    <a class="nav-item <?= $showCompteForm?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Ouvrir un compte
    </a>
    <div class="nav-section">Ma carte</div>
    <a class="nav-item <?= $showCarteForm?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte<?= $selected?'&id_compte='.$selected->getIdCompte():'' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>Demander une carte
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
      <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte" class="btn-primary" style="font-size:.78rem">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Nouveau compte
      </a>
      <div class="notif">
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
          <input type="text" id="plafond_virement" name="plafond_virement" value="<?= htmlspecialchars($formData['plafond_virement'] ?? '5000') ?>">
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
          <input type="text" id="plafond_input" name="plafond_paiement_jour" value="<?= htmlspecialchars($formData['plafond_paiement_jour'] ?? '1000') ?>" oninput="updateStyleHint(this.value)">
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
          <input type="text" id="plafond_retrait" name="plafond_retrait_jour" value="<?= htmlspecialchars($formData['plafond_retrait_jour'] ?? '1000') ?>">
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
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte" class="btn-primary">Demander un compte</a>
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
          <input type="hidden" name="action" value="demander_cloture">
          <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
          <button type="submit" class="acct-action-btn aa-neutral" onclick="return confirm('Demander la clôture de ce compte ?\nUn agent bancaire traitera votre demande.')">Dem. clôture</button>
        </form>
        <?php elseif ($selected->getStatut()==='bloque'): ?>
        <div class="notice-msg notice-amber">🔒 Ce compte est bloqué. Contactez votre conseiller.</div>
        <?php elseif ($selected->getStatut()==='demande_cloture'): ?>
        <div class="notice-msg notice-amber">⏳ Demande de clôture en attente de validation.</div>
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
      <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte&id_compte=<?= $selected->getIdCompte() ?>" class="btn-ghost">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Nouvelle carte
      </a>
      <?php endif; ?>
    </div>

    <?php if (empty($cartes)): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:2rem 1.5rem;text-align:center;color:var(--muted)">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:.6rem;display:block;margin-inline:auto"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      <div style="font-size:.82rem;margin-bottom:.7rem">Aucune carte liée à ce compte.</div>
      <?php if ($selected->getStatut()==='actif'): ?>
      <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte&id_compte=<?= $selected->getIdCompte() ?>" class="btn-primary">Demander une carte</a>
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
    // Init on page load
    document.addEventListener('DOMContentLoaded',function(){
        var inp=document.getElementById('plafond_input');
        if(inp) updateStyleHint(inp.value);
    });
    </script>
  </div>

  <?php endif; // main view ?>
  </div>
</div>
</body>
</html>
