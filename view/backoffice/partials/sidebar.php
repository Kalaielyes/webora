<?php
// Shared sidebar for all backoffice pages
// Usage: $currentPage = 'projets'; include __DIR__ . '/partials/sidebar.php';
$currentPage = $currentPage ?? '';
?>
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">ADMIN</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av">AD</div>
    <div>
      <div class="sb-aname">Sara</div>
      <div class="sb-arole">Super administrateur</div>
    </div>
  </div>
  <div class="sb-nav">
    <div class="nav-dropdown" id="actions-dropdown">
      <div class="nav-item <?= in_array($currentPage, ['projets','investissements','statistiques','chequier','comptes','utilisateurs','credit']) ? 'active' : '' ?>" id="nav-actions-parent">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        Actions
        <svg class="dropdown-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto;transition:transform .2s;<?= in_array($currentPage, ['projets','investissements','statistiques','chequier','comptes','utilisateurs','credit']) ? 'transform:rotate(180deg)' : '' ?>"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="nav-submenu" id="actions-submenu" style="display:<?= in_array($currentPage, ['projets','investissements','statistiques','chequier','comptes','utilisateurs','credit']) ? 'block' : 'none' ?>">
        <a class="nav-item sub-item <?= $currentPage === 'projets' ? 'active' : '' ?>" href="backofficecondidature.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Projets
        </a>
        <a class="nav-item sub-item <?= $currentPage === 'investissements' ? 'active' : '' ?>" href="backofficeinvestissements.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Investissements
        </a>
        <a class="nav-item sub-item <?= $currentPage === 'statistiques' ? 'active' : '' ?>" href="statistiques.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Statistiques
        </a>
        <a class="nav-item sub-item <?= $currentPage === 'chequier' ? 'active' : '' ?>" href="backoffice_chequier.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Chéquier
        </a>
        <a class="nav-item sub-item <?= $currentPage === 'comptes' ? 'active' : '' ?>" href="backoffice_compte.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Comptes
        </a>
        <a class="nav-item sub-item <?= $currentPage === 'utilisateurs' ? 'active' : '' ?>" href="backoffice_utilisateur.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Utilisateurs
        </a>
        <a class="nav-item sub-item <?= $currentPage === 'credit' ? 'active' : '' ?>" href="back_credit.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Crédit
        </a>
      </div>
    </div>

    <div class="nav-section">Paramètres</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/></svg>
      Paramètres
    </a>
  </div>
  <div class="sb-footer">
    <div class="sb-status"><div class="status-dot"></div> Système opérationnel</div>
  </div>
</div>
<script>
(function(){
  const ap = document.getElementById('nav-actions-parent');
  const as = document.getElementById('actions-submenu');
  const ch = ap ? ap.querySelector('.dropdown-chevron') : null;
  if(ap && as && ch){
    ap.addEventListener('click', () => {
      const v = as.style.display === 'block';
      as.style.display = v ? 'none' : 'block';
      ch.style.transform = v ? 'rotate(0deg)' : 'rotate(180deg)';
    });
  }
})();
</script>
