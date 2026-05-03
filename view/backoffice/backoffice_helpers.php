<?php

function backofficeBuildUrl($file, $params = []) {
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $query = http_build_query($params);
    return htmlspecialchars($baseDir . '/' . $file . ($query !== '' ? ('?' . $query) : ''), ENT_QUOTES, 'UTF-8');
}

function cagnotteStatusLabel($status) {
    $labels = [
        'en_attente' => 'En attente',
        'acceptee' => 'Acceptée',
        'refusee' => 'Refusée',
        'suspendue' => 'Suspendue',
        'cloturee' => 'Clôturée'
    ];
    return $labels[$status] ?? 'En attente';
}

function cagnotteStatusBadgeClass($status) {
    if ($status === 'acceptee') return 'b-active';
    if ($status === 'refusee') return 'b-danger';
    if ($status === 'suspendue') return 'b-suspend';
    if ($status === 'cloturee') return 'b-termine';
    return 'b-attente';
}

function cagnotteCategoryLabel($category) {
    $labels = [
        'sante' => 'Santé',
        'education' => 'Éducation',
        'solidarite' => 'Solidarité',
        'autre' => 'Autre'
    ];
    return $labels[$category] ?? ucfirst((string)$category);
}

function cagnotteCategoryBadgeClass($category) {
    $classes = [
        'sante' => 'cat-medical',
        'education' => 'cat-education',
        'solidarite' => 'cat-humanitaire',
        'autre' => 'cat-urgence'
    ];
    return $classes[$category] ?? 'cat-urgence';
}

function renderBackofficeSidebar($activePage) {
    $items = [
        'stats' => backofficeBuildUrl('backoffice_stats.php'),
        'cagnottes' => backofficeBuildUrl('backoffice_cagnotte.php'),
      'dons' => backofficeBuildUrl('backoffice_don.php'),
      'achievements' => backofficeBuildUrl('backoffice_achievements.php')
    ];
    ?>
    <div class="sidebar">
      <div class="sb-logo">
        <div class="sb-logo-name">Legal<span>Fin</span></div>
        <div class="sb-logo-env">ADMIN</div>
      </div>
      <div class="sb-admin">
        <div class="sb-av">AD</div>
        <div>
          <div class="sb-aname">Admin Général</div>
          <div class="sb-arole">Super administrateur</div>
        </div>
      </div>
      <nav class="sb-nav">
        <div class="nav-section">Tableau de bord</div>
        <a class="nav-item <?= $activePage === 'stats' ? 'active' : '' ?>" href="<?= $items['stats'] ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Vue globale
        </a>
        <div class="nav-section">Cagnottes</div>
        <a class="nav-item <?= $activePage === 'cagnottes' ? 'active' : '' ?>" href="<?= $items['cagnottes'] ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Toutes les cagnottes
        </a>
        <div class="nav-section">Dons</div>
        <a class="nav-item <?= $activePage === 'dons' ? 'active' : '' ?>" href="<?= $items['dons'] ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          Tous les dons
        </a>
        <div class="nav-section">Gamification</div>
        <a class="nav-item <?= $activePage === 'achievements' ? 'active' : '' ?>" href="<?= $items['achievements'] ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="5"/><path d="M8 14h8l-1 7-3-2-3 2-1-7z"/></svg>
          Achievements
        </a>
        <div class="nav-section">Rapports</div>
        <a class="nav-item <?= $activePage === 'stats' ? 'active' : '' ?>" href="<?= $items['stats'] ?>#analytics-overview">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Statistiques
        </a>
        <a class="nav-item" href="<?= htmlspecialchars('../frontoffice/frontoffice_cagnotte.php', ENT_QUOTES, 'UTF-8') ?>">
          <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          frontoffice
        </a>
      </nav>
      <div class="sb-footer">
        <div class="sb-status"><div class="status-dot"></div>Système opérationnel</div>
      </div>
    </div>
    <?php
}