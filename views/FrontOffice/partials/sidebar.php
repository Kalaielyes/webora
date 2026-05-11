<?php
/**
 * Shared Frontoffice Sidebar
 */
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . '/../../../models/config.php';

$user = $user ?? $_SESSION['user'] ?? [];
$initials = strtoupper(substr(($user['prenom']??''),0,1).substr(($user['nom']??''),0,1));
$isKycVerifie = (($user['status_kyc'] ?? '') === 'VERIFIE');

// Detect active section based on URI or provided variables
$currentFile = basename($_SERVER['PHP_SELF']);
$showCompteForm = $showCompteForm ?? (!empty($_GET['form']) && $_GET['form']==='compte');
$showCarteForm  = $showCarteForm  ?? (!empty($_GET['form']) && $_GET['form']==='carte');
$showAttente    = $showAttente    ?? (!empty($_GET['tab'])  && $_GET['tab']==='attente');
$showVirementForm = $showVirementForm ?? (!empty($_GET['form']) && $_GET['form']==='virement');
$showObjectifs  = $showObjectifs  ?? (!empty($_GET['tab'])  && $_GET['tab']==='objectifs');
$showHistorique = $showHistorique ?? (!empty($_GET['tab'])  && $_GET['tab']==='historique');

$isCompteSection = $isCompteSection ?? ((!$showCompteForm && !$showCarteForm && !$showAttente && !$showVirementForm && !$showObjectifs && !$showHistorique) || $showCompteForm || $showCarteForm);
$isOperationSection = $isOperationSection ?? ($showVirementForm || $showObjectifs || $showHistorique);
$pendingCount = $pendingCount ?? 0;
if ($pendingCount === 0 && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    require_once __DIR__ . '/../../../controllers/CompteController.php';
    require_once __DIR__ . '/../../../controllers/CarteController.php';
    $pts = CompteController::findByUtilisateur($uid);
    $pCount = 0;
    foreach ($pts as $c) {
        if (in_array($c->getStatut(), ['en_attente','demande_cloture','demande_suppression','demande_activation_courant'])) $pCount++;
        foreach (CarteController::findByCompte($c->getIdCompte()) as $carte) {
            if (in_array($carte->getStatut(), ['inactive','demande_cloture','demande_suppression','demande_reactivation'])) $pCount++;
        }
    }
    $pendingCount = $pCount;
}
?>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Espace client</div>
  </div>
  <div class="sb-user">
    <?php if(!empty($user['selfie_path'])): ?>
      <div class="sb-av sensitive-data" style="padding:0;overflow:hidden"><img src="<?= APP_URL ?>/<?= htmlspecialchars($user['selfie_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"></div>
    <?php else: ?>
      <div class="sb-av sensitive-data"><?= $initials ?></div>
    <?php endif; ?>
    <div>
      <div class="sb-uname sensitive-data"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
      <div class="sb-uemail sensitive-data"><?= htmlspecialchars($user['email']??'') ?></div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="nav-section">Personnel</div>
    <a class="nav-item <?= $currentFile === 'frontoffice_utilisateur.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon profil
    </a>

    <?php if ($isKycVerifie): ?>
      <!-- ══ COMPTE DROPDOWN ══ -->
      <div class="nav-dropdown <?= $isCompteSection ? 'open' : '' ?>" id="dropdown-compte">
        <div class="nav-item nav-dropdown-toggle" onclick="toggleDropdown('dropdown-compte')" style="cursor:pointer; user-select:none;">
          <div class="nav-dropdown-toggle-left">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
            <span>Compte</span>
          </div>
          <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="nav-dropdown-menu">
          <a class="nav-sub-item <?= ($currentFile === 'frontoffice_compte.php' && !$showCompteForm&&!$showCarteForm&&!$showAttente&&!$showVirementForm&&!$showObjectifs)?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php">
            <span class="nav-sub-dot"></span>Mes comptes
          </a>
          <a class="nav-sub-item <?= $showCompteForm?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte">
            <span class="nav-sub-dot"></span>Ouvrir un compte
          </a>
          <a class="nav-sub-item <?= $showCarteForm?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte">
            <span class="nav-sub-dot"></span>Demander une carte
          </a>
        </div>
      </div>

      <!-- ══ OPÉRATIONS DROPDOWN ══ -->
      <div class="nav-dropdown <?= $isOperationSection ? 'open' : '' ?>" id="dropdown-operations">
        <div class="nav-item nav-dropdown-toggle" onclick="toggleDropdown('dropdown-operations')" style="cursor:pointer; user-select:none;">
          <div class="nav-dropdown-toggle-left">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            <span>Opérations</span>
          </div>
          <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="nav-dropdown-menu">
          <a class="nav-sub-item <?= $showVirementForm?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=virement">
            <span class="nav-sub-dot"></span>Virement
          </a>
          <a class="nav-sub-item <?= $showHistorique?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=historique">
            <span class="nav-sub-dot"></span>Historique & Export
          </a>
          <a class="nav-sub-item <?= $showObjectifs?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=objectifs">
            <span class="nav-sub-dot"></span>Objectifs
          </a>
        </div>
      </div>

      <?php if ($pendingCount > 0): ?>
      <div class="nav-section" style="margin-top:.4rem">Suivi</div>
      <a class="nav-item <?= $showAttente?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=attente">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
        En attente
        <span style="margin-left:auto;background:rgba(245,158,11,.2);color:var(--amber);border-radius:99px;padding:1px 7px;font-size:.62rem;font-weight:600"><?= $pendingCount ?></span>
      </a>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (in_array(Session::get('role'), ['ADMIN', 'SUPER_ADMIN'])): ?>
    <div class="nav-section" style="margin-top:.4rem">Administration</div>
    <a class="nav-item" href="<?= APP_URL ?>/views/backoffice/backoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Backoffice Admin
    </a>
    <?php endif; ?>

    <a class="nav-item" href="../../controllers/AuthController.php?action=logout" style="margin-top:auto; color:var(--rose); text-decoration:none;">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Déconnexion
    </a>
  </nav>

  <div class="sb-footer">
    <?php if ($isKycVerifie): ?>
      <div class="badge-kyc"><span class="dot-pulse"></span>Identité vérifiée</div>
    <?php else: ?>
      <div class="badge-kyc" style="background:rgba(245,158,11,.1);color:var(--amber)">KYC requis</div>
    <?php endif; ?>
  </div>
</div>

<script>
if(typeof toggleDropdown !== 'function') {
  function toggleDropdown(id) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle('open');
  }
}
</script>
