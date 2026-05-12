<?php
require_once __DIR__ . '/../../../models/config.php';
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . '/../../../controller/AchievementController.php';
require_once __DIR__ . '/../../../controller/CagnotteController.php';
Session::start();
Session::requireLogin();

$achievementCtrl = new AchievementController();
$cagCtrl = new CagnotteController();

$selectedUserId = isset($_SESSION['frontoffice_user_id']) && is_numeric($_SESSION['frontoffice_user_id'])
  ? (int)$_SESSION['frontoffice_user_id']
  : 0;
$achievements = [];
$unlockedIds = [];
$totalPoints = 0;

$defaultUserId = (int)Session::get('user_id');
$availableUsers = [];
if ($defaultUserId > 0) {
    $defaultUser = $cagCtrl->getUserById($defaultUserId);
    if ($defaultUser) {
        $availableUsers[] = $defaultUser;
    }
}
if ($selectedUserId <= 0) {
  $selectedUserId = $defaultUserId;
}

$currentUser = $selectedUserId > 0 ? $cagCtrl->getUserById($selectedUserId) : null;
if (!$currentUser) {
    $selectedUserId = (int)$defaultUserId;
  if ($selectedUserId > 0) {
    $_SESSION['frontoffice_user_id'] = $selectedUserId;
    $currentUser = $cagCtrl->getUserById($selectedUserId);
  }
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
$_SESSION['user'] = array_merge($_SESSION['user'] ?? [], $currentUser ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
      (function(){
        var t = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', t);
        if (localStorage.getItem('privacy') === 'true') {
          document.documentElement.classList.add('privacy-mode');
        }
      })();
    </script>
    <title>Mes Achievements</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/frontoffice/Utilisateur.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/frontoffice/cagnotte.css">
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
<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes Achievements</div>
    <div class="topbar-right">
      <button class="privacy-toggle" onclick="togglePrivacy()" title="Mode confidentialité">
        <svg id="privacy-icon-off" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        <svg id="privacy-icon-on" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
      </button>
      <button class="theme-toggle" onclick="toggleTheme()" title="Changer de thème">
        <svg id="theme-icon-sun" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
        <svg id="theme-icon-moon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
      </button>
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
</div>
<script>
function syncThemeIcons(){
  var t = document.documentElement.getAttribute('data-theme') || 'dark';
  var sun = document.getElementById('theme-icon-sun');
  var moon = document.getElementById('theme-icon-moon');
  if (sun && moon) {
    sun.style.display = t === 'light' ? 'block' : 'none';
    moon.style.display = t === 'light' ? 'none' : 'block';
  }
}
function syncPrivacyIcons(){
  var on = document.documentElement.classList.contains('privacy-mode');
  var offIcon = document.getElementById('privacy-icon-off');
  var onIcon = document.getElementById('privacy-icon-on');
  if (offIcon && onIcon) {
    offIcon.style.display = on ? 'none' : 'block';
    onIcon.style.display = on ? 'block' : 'none';
  }
}
function toggleTheme(){
  var cur = document.documentElement.getAttribute('data-theme') || 'dark';
  var next = cur === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  syncThemeIcons();
}
function togglePrivacy(){
  document.documentElement.classList.toggle('privacy-mode');
  localStorage.setItem('privacy', document.documentElement.classList.contains('privacy-mode') ? 'true' : 'false');
  syncPrivacyIcons();
}
document.addEventListener('DOMContentLoaded', function(){
  syncThemeIcons();
  syncPrivacyIcons();
});
</script>
</body>
</html>

