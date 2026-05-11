<?php
/**
 * Shared frontoffice Sidebar
 */
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . '/../../../models/config.php';
require_once __DIR__ . '/../../../models/Utilisateur.php';

$user = $user ?? $_SESSION['user'] ?? [];
if (empty($user) && Session::get('user_id')) {
    $userModel = new Utilisateur();
    $loadedUser = $userModel->findById((int)Session::get('user_id'));
    if ($loadedUser) {
        $_SESSION['user'] = $loadedUser;
        $user = $loadedUser;
    }
}
$initials = strtoupper(substr(($user['prenom']??''),0,1).substr(($user['nom']??''),0,1));
$isKycVerifie = (($user['status_kyc'] ?? '') === 'VERIFIE');
// Keep banking navigation visible for logged-in users; KYC is still indicated via badge.
$showBankingNavigation = Session::isLoggedIn();

// Detect active section based on URI or provided variables
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$showCompteForm = $showCompteForm ?? (!empty($_GET['form']) && $_GET['form']==='compte');
$showCarteForm  = $showCarteForm  ?? (!empty($_GET['form']) && $_GET['form']==='carte');
$showAttente    = $showAttente    ?? (!empty($_GET['tab'])  && $_GET['tab']==='attente');
$showVirementForm = $showVirementForm ?? (!empty($_GET['form']) && $_GET['form']==='virement');
$showObjectifs  = $showObjectifs  ?? (!empty($_GET['tab'])  && $_GET['tab']==='objectifs');
$showHistorique = $showHistorique ?? (!empty($_GET['tab'])  && $_GET['tab']==='historique');
$page = $page ?? (string)($_GET['page'] ?? '');
$activeTab = $activeTab ?? (string)($_GET['view'] ?? '');

// Dropdown state logic - only open if we are in that specific section
$isCompteSection = $isCompteSection ?? ($currentFile === 'frontoffice_compte.php' && ($showCompteForm || $showCarteForm || (!$showVirementForm && !$showObjectifs && !$showHistorique)));
$isOperationSection = $isOperationSection ?? ($currentFile === 'frontoffice_compte.php' && ($showVirementForm || $showObjectifs || $showHistorique));
$isChequierSection = ($currentFile === 'frontoffice_chequier.php');
$isCreditSection = ($page === 'credit');
$isDonationSection = str_ends_with($currentDir, '/view/frontoffice/don');

$pendingCount = $pendingCount ?? 0;
if ($pendingCount === 0 && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    require_once __DIR__ . '/../../../controller/CompteController.php';
    require_once __DIR__ . '/../../../controller/CarteController.php';
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
    <div class="sb-av"><?= $initials ?></div>
    <div>
      <div class="sb-uname"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
      <div class="sb-uemail"><?= htmlspecialchars($user['email']??'') ?></div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="nav-section">Personnel</div>
    <a class="nav-item <?= $currentFile === 'frontoffice_utilisateur.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon profil
    </a>

    <?php if ($showBankingNavigation): ?>
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
          <a class="nav-sub-item <?= ($currentFile === 'frontoffice_compte.php' && !$showCompteForm&&!$showCarteForm&&!$showAttente&&!$showVirementForm&&!$showObjectifs)?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php">
            <span class="nav-sub-dot"></span>Mes comptes
          </a>
          <a class="nav-sub-item <?= $showCompteForm?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php?form=compte">
            <span class="nav-sub-dot"></span>Ouvrir un compte
          </a>
          <a class="nav-sub-item <?= $showCarteForm?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php?form=carte">
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
          <a class="nav-sub-item <?= $showVirementForm?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php?form=virement">
            <span class="nav-sub-dot"></span>Virement
          </a>
          <a class="nav-sub-item <?= $showHistorique?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php?tab=historique">
            <span class="nav-sub-dot"></span>Historique & Export
          </a>
          <a class="nav-sub-item <?= $showObjectifs?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php?tab=objectifs">
            <span class="nav-sub-dot"></span>Objectifs
          </a>
        </div>
      </div>

      <!-- ══ CHÉQUIERS DROPDOWN ══ -->
      <div class="nav-dropdown <?= $isChequierSection ? 'open' : '' ?>" id="dropdown-chequier">
        <div class="nav-item nav-dropdown-toggle" onclick="toggleDropdown('dropdown-chequier')" style="cursor:pointer; user-select:none;">
          <div class="nav-dropdown-toggle-left">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Mes chéquiers</span>
          </div>
          <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="nav-dropdown-menu">
          <a class="nav-sub-item <?= ($currentFile === 'frontoffice_chequier.php' && (!isset($_GET['view']) || $_GET['view']==='dashboard'))?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_chequier.php?view=dashboard">
            <span class="nav-sub-dot"></span>Demander un chéquier
          </a>
          <a class="nav-sub-item <?= ($currentFile === 'frontoffice_chequier.php' && ($_GET['view']??'')==='mes_chequiers')?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_chequier.php?view=mes_chequiers">
            <span class="nav-sub-dot"></span>Liste des chéquiers
          </a>
        </div>
      </div>

      <!-- ══ CRÉDITS DROPDOWN ══ -->
      <div class="nav-dropdown <?= $isCreditSection ? 'open' : '' ?>" id="dropdown-credit">
        <div class="nav-item nav-dropdown-toggle" onclick="toggleDropdown('dropdown-credit')" style="cursor:pointer; user-select:none;">
          <div class="nav-dropdown-toggle-left">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            <span>Crédits</span>
          </div>
          <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="nav-dropdown-menu">
          <a class="nav-sub-item <?= ($isCreditSection && ($activeTab === 'mes-credits')) ? 'active' : '' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_utilisateur.php?page=credit&view=mes-credits">
            <span class="nav-sub-dot"></span>Mes Crédits
          </a>
          <a class="nav-sub-item <?= ($isCreditSection && ($activeTab === 'mes-garanties')) ? 'active' : '' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_utilisateur.php?page=credit&view=mes-garanties">
            <span class="nav-sub-dot"></span>Mes Garanties
          </a>
          <a class="nav-sub-item <?= ($isCreditSection && ($activeTab === 'nouvelle')) ? 'active' : '' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_utilisateur.php?page=credit&view=nouvelle">
            <span class="nav-sub-dot"></span>Demander un crédit
          </a>
        </div>
      </div>

      <?php if ($pendingCount > 0): ?>
      <div class="nav-section" style="margin-top:.4rem">Suivi</div>
      <a class="nav-item <?= $showAttente?'active':'' ?>" href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php?tab=attente">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
        En attente
        <span style="margin-left:auto;background:rgba(245,158,11,.2);color:var(--amber);border-radius:99px;padding:1px 7px;font-size:.62rem;font-weight:600"><?= $pendingCount ?></span>
      </a>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (in_array(Session::get('role'), ['ADMIN', 'SUPER_ADMIN'])): ?>
    <div class="nav-section" style="margin-top:.4rem">Administration</div>
    <a class="nav-item" href="<?= APP_URL ?>/view/backoffice/backoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Backoffice Admin
    </a>
    <?php endif; ?>

    <?php if (Session::isLoggedIn()): ?>
    <div class="nav-section" style="margin-top:.4rem">Donations</div>
    <?php if (($user['association'] ?? 0)): ?>
      <a class="nav-item <?= ($isDonationSection && $currentFile === 'cagnotte.php') ? 'active' : '' ?>" href="<?= APP_URL ?>/view/frontoffice/don/cagnotte.php">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Mes Cagnottes
      </a>
    <?php else: ?>
      <a class="nav-item <?= ($isDonationSection && $currentFile === 'cagnotte.php') ? 'active' : '' ?>" href="<?= APP_URL ?>/view/frontoffice/don/cagnotte.php">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        Cagnottes & Dons
      </a>
    <?php endif; ?>
      <a class="nav-item <?= ($isDonationSection && $currentFile === 'achievements.php') ? 'active' : '' ?>" href="<?= APP_URL ?>/view/frontoffice/don/achievements.php">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="5"/><path d="M8 14h8l-1 7-3-2-3 2-1-7z"/></svg>
        Mes Achievements
      </a>
    <?php endif; ?>

    <a class="nav-item" href="<?= APP_URL ?>/controller/AuthController.php?action=logout" style="margin-top:auto; color:var(--rose); text-decoration:none;">
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

