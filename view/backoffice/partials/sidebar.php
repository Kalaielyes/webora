<?php
/**
 * Shared Backoffice Sidebar
 */
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . '/../../../models/config.php';

// Ensure user is admin
Session::requireAdmin();

$adminNom = $_SESSION['user_nom'] ?? 'Admin';
$adminPrenom = $_SESSION['user_prenom'] ?? '';
$adminInitials = strtoupper(substr($adminPrenom, 0, 1) . substr($adminNom, 0, 1));
$currentUserRole = $_SESSION['role'] ?? 'ADMIN';

// Current page detection
$currentFile = basename($_SERVER['PHP_SELF']);
$page = $_GET['page'] ?? 'dashboard';
$tab = $_GET['tab'] ?? 'comptes';

// Mock or fetch modules/permissions if available
// In a real app, these would come from the database based on the admin ID
$myModules = $myModules ?? ['dashboard', 'utilisateurs', 'statistiques', 'comptes', 'audit']; 
$pendingTotal = $pendingTotal ?? 0;
?>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">BACK OFFICE</div>
  </div>
  
  <div class="sb-user">
    <div class="sb-av"><?= $adminInitials ?></div>
    <div>
      <div class="sb-uname"><?= htmlspecialchars($adminPrenom . ' ' . $adminNom) ?></div>
      <div class="sb-uemail"><?= $currentUserRole === 'SUPER_ADMIN' ? 'Super Administrateur' : 'Agent Bancaire' ?></div>
    </div>
  </div>

  <div class="sb-status" style="margin: 0 1.4rem 1rem; padding: 4px 10px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 99px; display: inline-flex; align-items: center; gap: 6px; font-size: 0.65rem; color: #22C55E;">
    <span class="status-dot" style="width: 6px; height: 6px; background: #22C55E; border-radius: 50%; display: inline-block;"></span>
    Système Opérationnel
  </div>

  <nav class="sb-nav">
    <div class="nav-section">Gestion</div>
    
    <a href="<?= APP_URL ?>/view/backoffice/backoffice_utilisateur.php?page=dashboard" class="nav-item <?= ($currentFile === 'backoffice_utilisateur.php' && $page === 'dashboard') ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Tableau de bord
    </a>

    <!-- ══ COMPTES DROPDOWN ══ -->
    <div class="nav-dropdown <?= $currentFile === 'backoffice_compte.php' ? 'open' : '' ?>" id="dropdown-compte-admin">
      <button class="nav-dropdown-toggle" onclick="toggleDropdown('dropdown-compte-admin')">
        <div class="nav-dropdown-toggle-left">
          <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
          <span>Comptes & Cartes</span>
        </div>
        <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div class="nav-dropdown-menu">
        <a class="nav-sub-item <?= ($currentFile === 'backoffice_compte.php' && $tab==='comptes')?'active':'' ?>" href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=comptes">
          <span class="nav-sub-dot"></span>Liste des comptes
        </a>
        <a class="nav-sub-item <?= ($currentFile === 'backoffice_compte.php' && $tab==='attente')?'active':'' ?>" href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=attente">
          <span class="nav-sub-dot"></span>En attente
          <?php if ($pendingTotal > 0): ?>
            <span style="margin-left:auto;background:rgba(245,158,11,.2);color:var(--amber);border-radius:99px;padding:1px 7px;font-size:.62rem;font-weight:600"><?= $pendingTotal ?></span>
          <?php endif; ?>
        </a>
        <a class="nav-sub-item <?= ($currentFile === 'backoffice_compte.php' && $tab==='stats')?'active':'' ?>" href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=stats">
          <span class="nav-sub-dot"></span>Statistiques
        </a>
      </div>
    </div>

    <a href="<?= APP_URL ?>/view/backoffice/backoffice_utilisateur.php?page=utilisateurs" class="nav-item <?= ($currentFile === 'backoffice_utilisateur.php' && $page === 'utilisateurs') ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Utilisateurs
    </a>

    <a href="<?= APP_URL ?>/view/backoffice/backoffice_utilisateur.php?page=audit" class="nav-item <?= ($currentFile === 'backoffice_utilisateur.php' && $page === 'audit') ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Journal d'Audit
    </a>

    <div class="nav-section">Système</div>
    
    <?php if ($currentUserRole === 'SUPER_ADMIN'): ?>
    <a href="<?= APP_URL ?>/view/backoffice/backoffice_utilisateur.php?page=permissions" class="nav-item <?= ($currentFile === 'backoffice_utilisateur.php' && $page === 'permissions') ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      Permissions
    </a>
    <?php endif; ?>

    <a href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php" class="nav-item" style="color: var(--blue);">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      Retour frontoffice
    </a>

    <a href="<?= APP_URL ?>/controller/AuthController.php?action=logout" class="nav-item" style="margin-top:auto; color:var(--rose);">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Déconnexion
    </a>
  </nav>
</div>

<script>
function toggleDropdown(id) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('open');
}
</script>

