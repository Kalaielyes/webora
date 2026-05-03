<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../controller/achievementcontroller.php';
require_once __DIR__ . '/../../controller/cagnottecontroller.php';

$achievementCtrl = new achievementcontroller();
$cagCtrl = new cagnottecontroller();

$selectedUserId = $_SESSION['frontoffice_user_id'] ?? null;
$achievements = [];
$unlockedIds = [];
$totalPoints = 0;

$defaultUserId = $cagCtrl->ensureDefaultUser();
$availableUsers = $cagCtrl->getSelectableUsers();
$currentUser = $cagCtrl->getUserById($selectedUserId);
if (!$currentUser) {
    $selectedUserId = (int)$defaultUserId;
    $_SESSION['frontoffice_user_id'] = $selectedUserId;
    $currentUser = $cagCtrl->getUserById($selectedUserId);
}

if ($selectedUserId) {
    $achievements = $achievementCtrl->getAllWithUnlockCount();
    $roleType = ((int)($currentUser['association'] ?? 0) === 1) ? 'association' : 'donor';
    $achievementData = $achievementCtrl->getUserAchievementsData($selectedUserId, $roleType);
    $unlockedIds = $achievementData['unlocked_ids'] ?? [];
    $totalPoints = $achievementData['total_points'] ?? 0;
}

$currentUserName = trim((string)($currentUser['prenom'] ?? '') . ' ' . (string)($currentUser['nom'] ?? ''));
if ($currentUserName === '') {
    $currentUserName = 'Utilisateur';
}
$currentUserEmail = trim((string)($currentUser['email'] ?? ''));
$currentUserInitials = strtoupper(substr((string)($currentUser['prenom'] ?? ''), 0, 1) . substr((string)($currentUser['nom'] ?? ''), 0, 1));
if ($currentUserInitials === '') {
    $currentUserInitials = 'US';
}
$currentUserIsAssociation = (bool)($currentUser['association'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Achievements</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="cagnotte.css">
    <style>
        .ach-stats-bar{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;}
        .ach-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.1rem 1.3rem;display:flex;align-items:center;gap:.9rem;}
        .ach-stat-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;}
        .ach-stat-val{font-family:var(--fh);font-size:1.5rem;font-weight:800;line-height:1;}
        .ach-stat-label{font-size:.67rem;color:var(--muted);margin-top:3px;text-transform:uppercase;letter-spacing:.08em;}
        .ach-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.3rem 1rem;text-align:center;transition:all .2s;position:relative;display:flex;flex-direction:column;align-items:center;}
        .ach-card:not(.locked):hover{border-color:rgba(45,212,191,.4);transform:translateY(-4px);box-shadow:0 12px 28px rgba(0,0,0,.3);}
        .ach-card.locked{opacity:.38;}
        .ach-card-glow{position:absolute;inset:0;border-radius:inherit;pointer-events:none;background:radial-gradient(ellipse at 50% 0%,rgba(45,212,191,.08),transparent 70%);opacity:0;transition:opacity .2s;}
        .ach-card:not(.locked):hover .ach-card-glow{opacity:1;}
        .ach-icon-wrap{width:56px;height:56px;border-radius:15px;background:linear-gradient(135deg,rgba(45,212,191,.12),rgba(79,142,247,.1));border:1px solid rgba(45,212,191,.18);display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:.75rem;flex-shrink:0;}
        .ach-icon-wrap i{font-size:1.4rem;color:var(--teal);}
        .ach-card.locked .ach-icon-wrap{background:rgba(107,114,128,.07);border-color:rgba(107,114,128,.1);filter:grayscale(1);}
        .ach-card.locked .ach-icon-wrap i{color:var(--muted);}
        .ach-name{font-family:var(--fh);font-size:.84rem;font-weight:700;margin-bottom:.3rem;line-height:1.3;}
        .ach-desc{font-size:.7rem;color:var(--muted2);line-height:1.55;margin-bottom:.75rem;flex:1;}
        .ach-pts{display:inline-flex;align-items:center;gap:4px;background:rgba(45,212,191,.1);border:1px solid rgba(45,212,191,.2);color:var(--teal);border-radius:99px;padding:.18rem .7rem;font-size:.67rem;font-weight:700;margin-bottom:.45rem;}
        .ach-card.locked .ach-pts{background:rgba(107,114,128,.07);border-color:rgba(107,114,128,.12);color:var(--muted);}
        .ach-badge{font-size:.64rem;font-weight:700;letter-spacing:.03em;}
        .ach-badge.unlocked{color:var(--teal);}
        .ach-badge.locked-lbl{color:var(--muted);}
        .ach-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:1rem;}
        .role-chip{display:inline-flex;align-items:center;gap:.35rem;border-radius:99px;padding:.25rem .85rem;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;}
        .role-chip.donor{background:rgba(79,142,247,.1);border:1px solid rgba(79,142,247,.22);color:var(--blue);}
        .role-chip.association{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.22);color:var(--amber);}
        @media(max-width:700px){.ach-stats-bar{grid-template-columns:1fr 1fr;}.ach-grid{grid-template-columns:repeat(auto-fill,minmax(145px,1fr));}}
    </style>
</head>
<body>
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Plateforme de collecte solidaire</div>
  </div>
  <div class="sb-user">
    <div class="sb-av"><?= htmlspecialchars($currentUserInitials) ?></div>
    <div class="sb-user-meta">
      <div class="sb-uname"><?= htmlspecialchars($currentUserName) ?></div>
      <div class="sb-uemail"><?= htmlspecialchars($currentUserEmail !== '' ? $currentUserEmail : 'Aucun email') ?></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Tableau de bord</div>
    <a class="nav-item" href="frontoffice_cagnotte.php">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Aperçu
    </a>
    <div class="nav-section">Cagnottes</div>
    <?php if ($currentUserIsAssociation): ?>
    <a class="nav-item" href="frontoffice_cagnotte.php">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Créer une cagnotte
    </a>
    <?php endif; ?>
    <div class="nav-section">Dons</div>
    <a class="nav-item" href="frontoffice_cagnotte.php">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Historique
    </a>
    <a class="nav-item active" href="frontoffice_achievements.php">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="5"/><path d="M8 14h8l-1 7-3-2-3 2-1-7z"/></svg>
      Mes Achievements
    </a>
    <a class="nav-item" href="../backoffice/backoffice_cagnotte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      Backoffice
    </a>
  </nav>
  <div class="sb-footer">
    <div class="badge-verified"><div class="dot-pulse"></div> Compte vérifié</div>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes Achievements</div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
    </div>
  </div>

  <div class="view active">
    <div class="content">

      <?php
        $totalAch = count($achievements);
        $unlockedCount = count($unlockedIds);
        $lockedCount = $totalAch - $unlockedCount;
        $pctUnlocked = $totalAch > 0 ? round(($unlockedCount / $totalAch) * 100) : 0;
      ?>

      <!-- Stats bar -->
      <div class="ach-stats-bar">
        <div class="ach-stat">
          <div class="ach-stat-icon" style="background:rgba(167,139,250,.12);border:1px solid rgba(167,139,250,.2);">⭐</div>
          <div>
            <div class="ach-stat-val" style="color:#c4b5fd;"><?= (int)$totalPoints ?></div>
            <div class="ach-stat-label">Points gagnés</div>
          </div>
        </div>
        <div class="ach-stat">
          <div class="ach-stat-icon" style="background:rgba(45,212,191,.1);border:1px solid rgba(45,212,191,.18);">🏆</div>
          <div>
            <div class="ach-stat-val" style="color:var(--teal);"><?= $unlockedCount ?> / <?= $totalAch ?></div>
            <div class="ach-stat-label">Débloqués</div>
          </div>
        </div>
        <div class="ach-stat">
          <div class="ach-stat-icon" style="background:rgba(79,142,247,.1);border:1px solid rgba(79,142,247,.18);">📊</div>
          <div>
            <div class="ach-stat-val" style="color:var(--blue);"><?= $pctUnlocked ?>%</div>
            <div class="ach-stat-label">Progression</div>
          </div>
        </div>
      </div>

      <!-- Global progress bar -->
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1rem 1.3rem;margin-bottom:1.4rem;display:flex;align-items:center;gap:1rem;">
        <div style="font-size:.72rem;color:var(--muted);white-space:nowrap;"><?= $unlockedCount ?> / <?= $totalAch ?></div>
        <div style="flex:1;height:6px;background:var(--bg3);border-radius:99px;overflow:hidden;">
          <div style="height:100%;width:<?= $pctUnlocked ?>%;background:linear-gradient(90deg,var(--teal),var(--purple));border-radius:99px;transition:width .6s ease;"></div>
        </div>
        <div>
          <span class="role-chip <?= $currentUserIsAssociation ? 'association' : 'donor' ?>">
            <?= $currentUserIsAssociation ? '🏛 Association' : '❤️ Donateur' ?>
          </span>
        </div>
      </div>

      <!-- Section title -->
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">🏅 Mes badges</div>
        <div style="font-size:.72rem;color:var(--muted)"><?= $lockedCount ?> encore à débloquer</div>
      </div>

      <?php if (empty($achievements)): ?>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:3rem;text-align:center;color:var(--muted)">
          Aucun achievement disponible pour le moment.
        </div>
      <?php else: ?>
        <div class="ach-grid">
          <?php foreach ($achievements as $achievement):
              $isUnlocked = in_array((int)$achievement['id'], $unlockedIds);
              $icon = $achievement['icon'] ?? '🎖️';
          ?>
            <div class="ach-card <?= !$isUnlocked ? 'locked' : '' ?>">
              <div class="ach-card-glow"></div>
              <div class="ach-icon-wrap"><?php
                $icon = $achievement['icon'] ?? '';
                if (str_starts_with(trim($icon), 'fa')) {
                    echo '<i class="' . htmlspecialchars($icon) . '"></i>';
                } else {
                    echo htmlspecialchars($icon ?: '🎖️');
                }
              ?></div>
              <div class="ach-name"><?= htmlspecialchars($achievement['title']) ?></div>
              <div class="ach-desc"><?= htmlspecialchars($achievement['description']) ?></div>
              <div class="ach-pts">✦ +<?= (int)$achievement['points'] ?> pts</div>
              <div class="ach-badge <?= $isUnlocked ? 'unlocked' : 'locked-lbl' ?>">
                <?= $isUnlocked ? '✓ Débloqué' : '🔒 Verrouillé' ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
