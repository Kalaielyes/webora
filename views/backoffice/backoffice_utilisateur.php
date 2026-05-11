<?php
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
require_once __DIR__ . '/../../models/AuditLog.php';
Session::requireAdmin('../FrontOffice/login.php');
$auditModel = new AuditLog();
$logs = [];
if (($_GET['page'] ?? '') === 'audit') {
    $filters = [
        'admin_id'  => $_GET['audit_admin'] ?? '',
        'action'    => $_GET['audit_action'] ?? '',
        'date_from' => $_GET['audit_from'] ?? '',
        'date_to'   => $_GET['audit_to'] ?? '',
    ];
    $auditLimit = 15;
    $auditP = max(1, (int)($_GET['p'] ?? 1));
    $auditOffset = ($auditP - 1) * $auditLimit;
    $totalLogs = $auditModel->countFilteredLogs($filters);
    $totalAuditPages = ceil($totalLogs / $auditLimit);
    $logs = $auditModel->getFilteredLogs($filters, $auditLimit, $auditOffset);
}
$allAdmins = \config::getConnexion()->query("SELECT id, nom, prenom FROM utilisateur WHERE role IN ('ADMIN', 'SUPER_ADMIN') ORDER BY nom ASC")->fetchAll();
$m      = new Utilisateur();
$filtre = $_GET['filtre'] ?? 'tous';
$page   = $_GET['page'] ?? 'utilisateurs'; 

// Pagination Logic
$limit  = 10; // Users per page
$p      = max(1, (int)($_GET['p'] ?? 1));
$offset = ($p - 1) * $limit;
$totalUsers = $m->countAll($filtre);
$totalPages = ceil($totalUsers / $limit);
$users  = $m->findPaginated($filtre, $limit, $offset);
$periode= $_GET['periode'] ?? 'tout';
$stats  = $m->getStats($periode);
$flash  = Session::getFlash();
$currentUserId   = (int) Session::get('user_id');
$currentUserRole = Session::get('role');
$currentUser     = $m->findById($currentUserId);
$detailId = (int)($_GET['detail'] ?? ($users[0]['id'] ?? 0));
$detail   = $detailId ? $m->findById($detailId) : ($users[0] ?? null);
if ($detail && ($detail['role'] ?? '') === 'SUPER_ADMIN') {
    $detail = $users[0] ?? null;
    $detailId = $detail['id'] ?? 0;
}
$adminInitials = strtoupper(mb_substr(Session::get('user_nom'),0,1).mb_substr(Session::get('user_prenom'),0,1));
$ALL_MODULES = [
    'comptes'          => ['label'=>'Comptes',           'icon'=>'<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',                                              'color'=>'var(--blue)'],
    'actions'          => ['label'=>'Actions',           'icon'=>'<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',                                                                                                'color'=>'var(--teal)'],
    'credit'           => ['label'=>'Crédit',            'icon'=>'<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',                                                    'color'=>'var(--green)'],
    'demande_chequier' => ['label'=>'Demande Chéquier',  'icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>','color'=>'var(--amber)'],
    'dons_collectes'   => ['label'=>'Dons Collectés',   'icon'=>'<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>','color'=>'var(--rose)'],
    'utilisateurs'     => ['label'=>'Utilisateurs',     'icon'=>'<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>','color'=>'var(--purple)'],
    'statistiques'     => ['label'=>'Statistiques',     'icon'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',                               'color'=>'#F59E0B'],
    'audit'            => ['label'=>'Journal d\'Audit', 'icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>','color'=>'var(--rose)'],
];
$notifications = [];
$pdo = \config::getConnexion();
$stmtInscrits = $pdo->query("SELECT id, nom, prenom, date_inscription FROM utilisateur WHERE DATE(date_inscription) = CURDATE() ORDER BY date_inscription DESC LIMIT 5");
foreach($stmtInscrits->fetchAll() as $row) {
    $notifications[] = [
        'type' => 'new_account',
        'message' => "Nouveau compte : {$row['nom']} {$row['prenom']}",
        'time' => $row['date_inscription'],
        'link' => "?page=utilisateurs&filtre=tous&detail={$row['id']}"
    ];
}
$stmtKyc = $pdo->query("SELECT id, nom, prenom, date_inscription FROM utilisateur WHERE status_kyc = 'EN_ATTENTE' AND id_file_path IS NOT NULL AND id_file_path != '' ORDER BY date_inscription DESC LIMIT 5");
foreach($stmtKyc->fetchAll() as $row) {
    $notifications[] = [
        'type' => 'id_deposited',
        'message' => "ID déposé : {$row['nom']} {$row['prenom']} attend la validation KYC",
        'time' => $row['date_inscription'],
        'link' => "?page=utilisateurs&filtre=kyc_attente&detail={$row['id']}"
    ];
}
usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$notifications = array_slice($notifications, 0, 5); 
$unreadCount = count($notifications);
$permFile = __DIR__ . '/../../models/admin_permissions.json';
$perms = [];
if (file_exists($permFile)) {
    $perms = json_decode(file_get_contents($permFile), true) ?? [];
}
function getAdminModules(array $perms, int $uid, string $role, array $ALL_MODULES): array {
    if ($role === 'SUPER_ADMIN') return array_keys($ALL_MODULES);
    return $perms[$uid] ?? [];
}
$myModules = getAdminModules($perms, $currentUserId, $currentUserRole, $ALL_MODULES);
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='save_permissions') {
    if ($currentUserRole === 'SUPER_ADMIN') {
        $targetId  = (int)($_POST['target_id'] ?? 0);
        $granted   = $_POST['modules'] ?? [];
        $granted   = array_intersect($granted, array_keys($ALL_MODULES));
        $perms[$targetId] = array_values($granted);
        file_put_contents($permFile, json_encode($perms, JSON_PRETTY_PRINT));
        Session::setFlash('success', 'Permissions mises à jour.');
    }
    header('Location: ?page=permissions');
    exit;
}
function badge_kyc(string $v): string {
    return match($v) {'VERIFIE'=>'kyc-ok','EN_ATTENTE'=>'kyc-wait',default=>'kyc-ko'};
}
function badge_status(string $v): string {
    return match($v) {'ACTIF'=>'b-actif','SUSPENDU'=>'b-bloque','INACTIF'=>'b-inactif',default=>'b-attente'};
}
function badge_dot(string $v): string {
    return match($v) {'ACTIF'=>'var(--green)','SUSPENDU'=>'var(--rose)',default=>'var(--muted)'};
}
function initials(string $n, string $p): string {
    return strtoupper(mb_substr($n,0,1).mb_substr($p,0,1));
}
?>
<?php if (!isset($_GET['ajax'])): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>LegalFin Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Utilisateur.css">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
    (function() {
      var t = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>
<body>
<?php include __DIR__ . '/partials/sidebar_unified.php'; ?>
<div class="main" id="main-content">
<?php endif; ?>
  <div class="topbar">
    <div class="tb-left">
      <?php
      $titles = ['dashboard'=>'Tableau de bord','utilisateurs'=>'Gestion Utilisateurs','profil'=>'Mon Profil','permissions'=>'Gestion des Permissions','statistiques'=>'Statistiques','audit'=>'Journal d\'Audit'];
      ?>
      <div class="page-title"><?= $titles[$page] ?? 'Backoffice' ?></div>
      <div class="breadcrumb">LegalFin / <?= $titles[$page] ?? '' ?></div>
    </div>
    <div class="tb-right">
      <div class="notif-wrapper" id="notif-wrapper">
        <button class="notif-btn" id="notif-btn" onclick="document.getElementById('notif-dropdown').classList.toggle('show')">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 01-3.46 0"></path></svg>
          <?php if($unreadCount > 0): ?>
          <span class="notif-badge"><?= $unreadCount ?></span>
          <?php endif; ?>
        </button>
        <div id="notif-dropdown" class="notif-dropdown">
          <div class="notif-header">Notifications <?= $unreadCount>0 ? "($unreadCount)" : "" ?></div>
          <div class="notif-list">
             <?php if(empty($notifications)): ?>
             <div style="padding:1.5rem;text-align:center;color:var(--muted);font-size:.75rem;">Aucune notification</div>
             <?php else: ?>
             <?php foreach($notifications as $n): ?>
             <a href="<?= $n['link'] ?>" class="notif-item">
               <div class="notif-icon <?= $n['type'] ?>">
                 <?php if($n['type']==='new_account'): ?>
                 <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                 <?php else: ?>
                 <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                 <?php endif; ?>
               </div>
               <div>
                 <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                 <div class="notif-time"><?= date('d/m/Y H:i', strtotime($n['time'])) ?></div>
               </div>
             </a>
             <?php endforeach; ?>
             <?php endif; ?>
          </div>
        </div>
      </div>
      <div style="font-size:.72rem;color:var(--muted);"><?= date('d/m/Y H:i') ?></div>
    </div>
  </div>
  <div class="content">
    <?php if($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14">
        <?= $flash['type']==='success' ? '<polyline points="20 6 9 17 4 12"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>' ?>
      </svg>
      <?= $flash['message'] ?>
    </div>
    <?php endif; ?>
    <?php if($page==='dashboard'): ?>
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)"><svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['total'] ?></div><div class="kpi-label">Total utilisateurs</div><div class="kpi-sub" style="color:var(--green)">+<?= $stats['mois'] ?> ce mois</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)"><svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['actifs'] ?></div><div class="kpi-label">Clients actifs</div><div class="kpi-sub" style="color:var(--green)"><?= $stats['total']?round($stats['actifs']/$stats['total']*100).'%':'0%' ?> du total</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)"><svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg></div>
        <div class="kpi-data"><div class="kpi-val" style="color:var(--amber)"><?= $stats['kyc'] ?></div><div class="kpi-label">KYC en attente</div><div class="kpi-sub" style="color:var(--amber)">À valider</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--rose-light)"><svg width="18" height="18" fill="none" stroke="var(--rose)" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
        <div class="kpi-data"><div class="kpi-val" style="color:var(--rose)"><?= $stats['bloques'] ?></div><div class="kpi-label">Comptes bloqués</div><div class="kpi-sub" style="color:var(--rose)">Risque détecté</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--purple-light)"><svg width="18" height="18" fill="none" stroke="var(--purple)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['admins'] ?></div><div class="kpi-label">Administrateurs</div><div class="kpi-sub" style="color:var(--muted)">Multi-niveaux</div></div>
      </div>
    </div>
    <div class="module-section">
      <div class="module-section-title">Modules disponibles</div>
      <div class="module-grid">
        <?php foreach($ALL_MODULES as $mk => $md):
          $locked = !in_array($mk, $myModules);
          $href = match($mk) {
            'utilisateurs' => '?page=utilisateurs&filtre=tous',
            'statistiques' => '?page=statistiques',
            default        => '#'
          };
        ?>
        <a href="<?= $locked ? '#' : $href ?>" class="module-card <?= $locked ? 'locked' : '' ?>">
          <div class="module-icon" style="background:<?= str_replace('var(--','rgba(', str_replace(')',',0.12)',$md['color'])) ?>">
            <svg width="22" height="22" fill="none" stroke="<?= $md['color'] ?>" stroke-width="1.8" viewBox="0 0 24 24"><?= $md['icon'] ?></svg>
          </div>
          <div class="module-label"><?= $md['label'] ?></div>
          <?php if($locked): ?>
          <div class="lock-badge">🔒 Accès refusé</div>
          <?php else: ?>
          <div class="module-sub" style="color:<?= $md['color'] ?>">Actif</div>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif($page==='utilisateurs' && in_array('utilisateurs',$myModules)): ?>
    <div class="two-col-layout">
      <div class="table-card">
        <div class="table-toolbar" style="flex-wrap: wrap; gap: 1rem;">
          <div class="table-toolbar-title">Liste des utilisateurs (<?= $totalUsers ?>)</div>
          <div style="display:flex; gap: 0.8rem; align-items: center; margin-left: auto;">
            <div class="search-bar">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="search-input" placeholder="Rechercher..." oninput="filterTable()">
            </div>
            <button class="btn-primary" onclick="document.getElementById('m-add').classList.add('open')">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Ajouter
            </button>
          </div>
          <div class="filters">
            <?php foreach(['tous'=>'Tous','clients'=>'Clients','admins'=>'Admins','association'=>'Association','bloques'=>'Bloqués','kyc_attente'=>'KYC attente'] as $k=>$l): ?>
            <a href="?page=utilisateurs&filtre=<?= $k ?>" class="filter-btn <?= $filtre===$k?'active':'' ?>"><?= $l ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="table-responsive">
        <table id="user-table">
          <thead><tr>
            <th>Utilisateur</th><th>Email</th><th>Rôle</th><th>KYC</th><th>AML</th><th>Statut</th><th>Inscription</th><th style="min-width:110px; text-align:right;">Actions</th>
          </tr></thead>
          <tbody>
          <?php foreach($users as $u): $ini=initials($u['nom'],$u['prenom']); ?>
          <tr class="user-row <?= $u['id']===$detailId?'detail-active':'' ?>" data-id="<?= $u['id'] ?>">
            <td>
              <div style="display:flex;align-items:center;gap:.6rem">
                <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#2563EB,#0D9488);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0"><?= $ini ?></div>
                <div><div class="td-name"><?= htmlspecialchars($u['nom'].' '.$u['prenom']) ?></div><div class="td-mono">CIN: <?= htmlspecialchars($u['cin']) ?></div></div>
              </div>
            </td>
            <td><span class="td-mono"><?= htmlspecialchars($u['email']) ?></span></td>
            <td><span class="<?= $u['role']==='CLIENT'?'r-client':'r-admin' ?>"><?= $u['role'] ?></span></td>
            <td><span class="<?= badge_kyc($u['status_kyc']) ?>"><?= $u['status_kyc'] ?></span></td>
            <td><span class="<?= badge_kyc($u['status_aml']) ?>"><?= $u['status_aml'] ?></span></td>
            <td><span class="badge <?= badge_status($u['status']) ?>"><span class="badge-dot" style="background:<?= badge_dot($u['status']) ?>"></span><?= $u['status'] ?></span></td>
            <td><span class="td-mono"><?= date('d/m/Y', strtotime($u['date_inscription'])) ?></span></td>
            <td style="text-align:right;">
              <div class="action-group" style="justify-content:flex-end;">
                <button class="act-btn" title="Voir" onclick="showDetail(<?= $u['id'] ?>)"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                <button class="act-btn" title="Modifier" onclick='openEdit(<?= htmlspecialchars(json_encode($u)) ?>)'><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                <?php if($u['id']!==$currentUserId): ?>
                <button class="act-btn danger" title="Supprimer" onclick="openDel(<?= $u['id'] ?>,'<?= htmlspecialchars($u['nom'].' '.$u['prenom']) ?>')"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <a href="?page=utilisateurs&filtre=<?= $filtre ?>&p=<?= max(1, $p-1) ?>" class="pg-link <?= $p <= 1 ? 'disabled' : '' ?>">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
          </a>
          
          <?php 
          $start = max(1, $p - 2);
          $end = min($totalPages, $p + 2);
          if($start > 1) echo '<span class="pg-text">...</span>';
          for($i = $start; $i <= $end; $i++): ?>
            <a href="?page=utilisateurs&filtre=<?= $filtre ?>&p=<?= $i ?>" class="pg-link <?= $i === $p ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; 
          if($end < $totalPages) echo '<span class="pg-text">...</span>';
          ?>
          
          <a href="?page=utilisateurs&filtre=<?= $filtre ?>&p=<?= min($totalPages, $p+1) ?>" class="pg-link <?= $p >= $totalPages ? 'disabled' : '' ?>">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
        </div>
        <?php endif; ?>
      </div>
      <?php if($detail): $ini=initials($detail['nom'],$detail['prenom']); ?>
      <div class="detail-panel" id="detail-panel">
        <div class="dp-header">
          <div class="dp-av"><?= $ini ?></div>
          <div>
            <div class="dp-name"><?= htmlspecialchars($detail['nom'].' '.$detail['prenom']) ?></div>
            <div class="dp-cin">CIN: <?= htmlspecialchars($detail['cin']) ?></div>
            <div class="dp-badges">
              <span class="<?= badge_kyc($detail['status_kyc']) ?>">KYC <?= $detail['status_kyc'] ?></span>
              <span class="<?= $detail['role']==='CLIENT'?'r-client':'r-admin' ?>"><?= $detail['role'] ?></span>
            </div>
          </div>
        </div>
        <div>
          <div class="dp-section">Informations personnelles</div>
          <div class="dp-row"><span class="dp-key">Nom</span><span class="dp-val"><?= htmlspecialchars($detail['nom']) ?></span></div>
          <div class="dp-row"><span class="dp-key">Prénom</span><span class="dp-val"><?= htmlspecialchars($detail['prenom']) ?></span></div>
          <div class="dp-row"><span class="dp-key">Email</span><span class="dp-val-mono"><?= htmlspecialchars($detail['email']) ?></span></div>
          <div class="dp-row"><span class="dp-key">Téléphone</span><span class="dp-val"><?= htmlspecialchars($detail['numTel']) ?></span></div>
          <div class="dp-row"><span class="dp-key">Naissance</span><span class="dp-val"><?= date('d/m/Y',strtotime($detail['date_naissance'])) ?></span></div>
          <div class="dp-row"><span class="dp-key">Adresse</span><span class="dp-val" style="font-size:.72rem;max-width:160px;text-align:right"><?= htmlspecialchars($detail['adresse']) ?></span></div>
        </div>
        <div>
          <div class="dp-section">Compte &amp; Statut</div>
          <div class="dp-row"><span class="dp-key">KYC</span><span class="<?= badge_kyc($detail['status_kyc']) ?>"><?= $detail['status_kyc'] ?></span></div>
          <div class="dp-row"><span class="dp-key">AML</span><span class="<?= badge_kyc($detail['status_aml']) ?>"><?= $detail['status_aml'] ?></span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="badge <?= badge_status($detail['status']) ?>"><?= $detail['status'] ?></span></div>
          <div class="dp-row"><span class="dp-key">Inscription</span><span class="dp-val-mono"><?= date('d/m/Y',strtotime($detail['date_inscription'])) ?></span></div>
          <div class="dp-row"><span class="dp-key">Dernière connexion</span><span class="dp-val-mono"><?= $detail['derniere_connexion'] ? date('d/m H:i',strtotime($detail['derniere_connexion'])) : 'N/A' ?></span></div>
          <div class="dp-row">
            <span class="dp-key">Justificatif ID</span>
            <?php if(!empty($detail['id_file_path'])): ?>
              <a href="../../<?= htmlspecialchars($detail['id_file_path']) ?>" target="_blank" class="badge" style="background:var(--blue-light);color:var(--blue);border:none;text-decoration:none">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:2px"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Voir le document
              </a>
            <?php else: ?>
              <span class="badge" style="background:var(--bg3);color:var(--muted)">Aucun déposé</span>
            <?php endif; ?>
          </div>
        </div>
        <div style="background:var(--bg2);padding:1rem;border-radius:12px;border:1px solid var(--border)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem">
            <div class="dp-section" style="margin:0">Score Anti-Fraude (AML)</div>
            <form method="POST" action="../../controllers/UtilisateurController.php" style="margin:0">
              <input type="hidden" name="action" value="scan_aml">
              <input type="hidden" name="id" value="<?= $detail['id'] ?>">
              <button type="submit" class="badge" style="background:var(--purple-light);color:var(--purple);border:none;cursor:pointer">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:2px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="12" r="3"/></svg> Scan API
              </button>
            </form>
          </div>
          <?php
          $score = (int)($detail['aml_score'] ?? 0);
          $reasons = json_decode($detail['aml_reasons'] ?? '[]', true) ?: [];
          $scoreColor = 'var(--green)';
          if($score > 30) $scoreColor = 'var(--amber)';
          if($score > 70) $scoreColor = 'var(--rose)';
          ?>
          <div style="display:flex;align-items:center;gap:1rem">
            <div style="width:45px;height:45px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;border:3px solid <?= $scoreColor ?>;color:<?= $scoreColor ?>"><?= $score ?></div>
            <div style="flex:1">
              <div style="width:100%;height:6px;background:var(--border);border-radius:3px;overflow:hidden">
                <div style="height:100%;background:<?= $scoreColor ?>;width:<?= $score ?>%"></div>
              </div>
              <div style="font-size:.65rem;color:var(--muted);margin-top:.3rem;text-align:right">Risque sur 100</div>
            </div>
          </div>
          <?php if(!empty($reasons)): ?>
          <div style="margin-top:.8rem;font-size:.7rem">
            <?php foreach($reasons as $r): ?>
            <div style="display:flex;gap:.4rem;margin-bottom:.3rem;color:var(--rose)">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              <span><?= htmlspecialchars($r) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <div style="background:var(--bg2);padding:1rem;border-radius:12px;border:1px solid var(--border);margin-top:1rem">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem">
            <div class="dp-section" style="margin:0">Analyse OCR (Document)</div>
            <form method="POST" action="../../controllers/UtilisateurController.php" style="margin:0">
              <input type="hidden" name="action" value="scan_ocr">
              <input type="hidden" name="id" value="<?= $detail['id'] ?>">
              <button type="submit" class="badge" style="background:var(--blue-light);color:var(--blue);border:none;cursor:pointer">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:2px"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg> Scanner ID
              </button>
            </form>
          </div>
          <?php
          $ocr = json_decode($detail['ocr_result'] ?? '[]', true) ?: null;
          if($ocr):
            $matchNom = strtolower($ocr['nom'] ?? '') === strtolower($detail['nom']);
            $matchPrenom = strtolower($ocr['prenom'] ?? '') === strtolower($detail['prenom']);
            $matchCin = ($ocr['cin'] ?? '') === $detail['cin'];
          ?>
            <div style="display:flex;flex-direction:column;gap:.4rem">
              <div class="dp-row" style="border:none;padding:0">
                <span class="dp-key">Nom extrait</span>
                <span class="dp-val"><?= htmlspecialchars($ocr['nom'] ?? 'N/A') ?> <?= $matchNom ? '<span style="color:var(--green)">✅</span>' : '<span style="color:var(--rose)">❌</span>' ?></span>
              </div>
              <div class="dp-row" style="border:none;padding:0">
                <span class="dp-key">Prénom extrait</span>
                <span class="dp-val"><?= htmlspecialchars($ocr['prenom'] ?? 'N/A') ?> <?= $matchPrenom ? '<span style="color:var(--green)">✅</span>' : '<span style="color:var(--rose)">❌</span>' ?></span>
              </div>
              <div class="dp-row" style="border:none;padding:0">
                <span class="dp-key">CIN extrait</span>
                <span class="dp-val-mono"><?= htmlspecialchars($ocr['cin'] ?? 'N/A') ?> <?= $matchCin ? '<span style="color:var(--green)">✅</span>' : '<span style="color:var(--rose)">❌</span>' ?></span>
              </div>
              <div style="font-size:.65rem;color:var(--muted);margin-top:.3rem;text-align:right">Confiance : <?= (int)(($ocr['confiance'] ?? 0)*100) ?>%</div>
            </div>
          <?php else: ?>
            <div style="font-size:.75rem;color:var(--muted);text-align:center;padding:.5rem">Aucune analyse OCR effectuée.</div>
          <?php endif; ?>
        </div>
        <div style="background:var(--bg2);padding:1rem;border-radius:12px;border:1px solid var(--border);margin-top:1rem">
          <div class="dp-section" style="margin:0 0 .8rem 0">Dernière Localisation</div>
          <?php if(!empty($detail['last_lat']) && !empty($detail['last_long'])): ?>
            <div id="map-detail" style="height:150px;border-radius:8px;border:1px solid var(--border);z-index:1"></div>
            <div style="font-size:.65rem;color:var(--muted);margin-top:.4rem;display:flex;justify-content:space-between">
              <span>📍 <?= htmlspecialchars($detail['last_city'] ?? 'Ville inconnue') ?></span>
              <span>🌐 IP: <?= htmlspecialchars($detail['last_ip'] ?? '—') ?></span>
            </div>
            <script>
              (function(){
                setTimeout(function(){
                  var mCont = document.getElementById('map-detail');
                  if(!mCont) return;
                  var map = L.map('map-detail').setView([<?= $detail['last_lat'] ?>, <?= $detail['last_long'] ?>], 10);
                  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                  L.marker([<?= $detail['last_lat'] ?>, <?= $detail['last_long'] ?>]).addTo(map);
                }, 400);
              })();
            </script>
          <?php else: ?>
            <div style="font-size:.75rem;color:var(--muted);text-align:center;padding:.5rem">Aucune donnée de localisation.</div>
          <?php endif; ?>
        </div>
        <?php if(!empty($detail['selfie_path']) || !empty($detail['face_match_score'])): ?>
        <div style="background:var(--bg2);padding:1rem;border-radius:12px;border:1px solid var(--border);margin-top:1rem">
          <div class="dp-section" style="margin:0 0 .8rem 0">🤳 Vérification Biométrique (Face++)</div>
          <?php
            $faceScore = (float)($detail['face_match_score'] ?? 0);
            $faceColor = $faceScore >= 80 ? 'var(--green)' : ($faceScore >= 60 ? 'var(--amber)' : 'var(--rose)');
            $faceLabel = $faceScore >= 80 ? '✅ Identité confirmée' : ($faceScore >= 60 ? '⚠️ Doute - vérifier manuellement' : '❌ Identité non confirmée');
          ?>
          <div style="display:flex;gap:.8rem;align-items:flex-start">
            <?php if(!empty($detail['selfie_path'])): ?>
            <img src="../../<?= htmlspecialchars($detail['selfie_path']) ?>" alt="Selfie"
                 style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid <?= $faceColor ?>;flex-shrink:0">
            <?php endif; ?>
            <div style="flex:1">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
                <span style="font-size:.72rem;font-weight:600;color:<?= $faceColor ?>"><?= $faceLabel ?></span>
                <span style="font-family:var(--fm);font-size:.9rem;font-weight:700;color:<?= $faceColor ?>"><?= $faceScore ?>/100</span>
              </div>
              <div style="width:100%;height:8px;background:var(--border);border-radius:99px;overflow:hidden">
                <div style="height:100%;background:<?= $faceColor ?>;width:<?= $faceScore ?>%;border-radius:99px;transition:width .6s ease"></div>
              </div>
              <div style="font-size:.62rem;color:var(--muted);margin-top:.3rem">Seuil de validation automatique : 80/100</div>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div class="dp-actions">
          <button class="dp-action-btn da-primary" onclick='openEdit(<?= htmlspecialchars(json_encode($detail)) ?>)'><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Modifier</button>
          <button class="dp-action-btn da-green" onclick="openKyc(<?= $detail['id'] ?>,'<?= $detail['role'] ?>')"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>Valider KYC</button>
          <button class="dp-action-btn da-warning" onclick="openResetPwd(<?= $detail['id'] ?>,'<?= htmlspecialchars($detail['nom'].' '.$detail['prenom']) ?>')"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>Reset MDP</button>
          <button class="dp-action-btn da-danger" onclick="openBloquer(<?= $detail['id'] ?>,'<?= $detail['status_kyc'] ?>','<?= $detail['status_aml'] ?>','<?= $detail['role'] ?>')"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Bloquer</button>
          <?php if($detail['id']!==$currentUserId): ?>
          <button class="dp-action-btn da-danger" style="grid-column:1/-1" onclick="openDel(<?= $detail['id'] ?>,'<?= htmlspecialchars($detail['nom'].' '.$detail['prenom']) ?>')"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>Supprimer</button>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="detail-panel"><p style="color:var(--muted);font-size:.8rem;text-align:center;padding:2rem">Aucun utilisateur sélectionné.</p></div>
      <?php endif; ?>
    </div>
    <?php elseif($page==='profil'): ?>
    <div class="profile-grid">
      <div>
        <div class="profile-card">
          <?php if(!empty($currentUser['selfie_path'])): ?>
            <div class="profile-avatar" style="padding:0;overflow:hidden;font-size:0"><img src="../../<?= htmlspecialchars($currentUser['selfie_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%"></div>
          <?php else: ?>
            <div class="profile-avatar"><?= $adminInitials ?></div>
          <?php endif; ?>
          <div>
            <div class="profile-name"><?= htmlspecialchars(($currentUser['nom']??'').' '.($currentUser['prenom']??'')) ?></div>
            <div class="profile-email"><?= htmlspecialchars($currentUser['email']??'') ?></div>
            <div style="display:flex;justify-content:center;margin-top:.5rem">
              <div class="profile-role">
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?= $currentUserRole ?>
              </div>
            </div>
          </div>
        </div>
        <div class="info-card" style="margin-top:1rem;">
          <div class="info-card-title">Informations du compte</div>
          <div class="info-row">
            <div class="info-key"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.07 1.18 2 2 0 012.03 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>Téléphone</div>
            <div class="info-val"><?= htmlspecialchars($currentUser['numTel']??'—') ?></div>
          </div>
          <div class="info-row">
            <div class="info-key"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Inscrit le</div>
            <div class="info-val"><?= $currentUser['date_inscription'] ? date('d/m/Y',strtotime($currentUser['date_inscription'])) : '—' ?></div>
          </div>
          <div class="info-row">
            <div class="info-key"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Dernière connexion</div>
            <div class="info-val"><?= $currentUser['derniere_connexion'] ? date('d/m/Y H:i',strtotime($currentUser['derniere_connexion'])) : '—' ?></div>
          </div>
          <div class="info-row">
            <div class="info-key"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>Adresse</div>
            <div class="info-val" style="max-width:160px;text-align:right;font-size:.72rem;"><?= htmlspecialchars($currentUser['adresse']??'—') ?></div>
          </div>
          <div class="info-row">
            <div class="info-key">Modules accessibles</div>
            <div class="info-val" style="color:var(--blue);font-weight:700"><?= count($myModules) ?> / <?= count($ALL_MODULES) ?></div>
          </div>
        </div>
      </div>
      <div>
        <div class="form-section">
          <div class="form-section-title">Modifier mes informations</div>
          <form method="POST" action="../../controllers/UtilisateurController.php">
            <input type="hidden" name="action" value="admin_edit">
            <input type="hidden" name="id" value="<?= $currentUserId ?>">
            <div class="form-grid">
              <div class="form-field"><label class="form-label">Nom</label><input class="form-input" type="text" name="nom" value="<?= htmlspecialchars($currentUser['nom']??'') ?>"></div>
              <div class="form-field"><label class="form-label">Prénom</label><input class="form-input" type="text" name="prenom" value="<?= htmlspecialchars($currentUser['prenom']??'') ?>"></div>
              <div class="form-field"><label class="form-label">Téléphone</label><input class="form-input" type="text" name="numTel" value="<?= htmlspecialchars($currentUser['numTel']??'') ?>"></div>
              <div class="form-field"><label class="form-label">Rôle</label><input class="form-input" type="text" value="<?= $currentUserRole ?>" disabled style="opacity:.5"></div>
              <div class="form-field full"><label class="form-label">Adresse</label><textarea class="form-input" name="adresse" rows="2" style="resize:none"><?= htmlspecialchars($currentUser['adresse']??'') ?></textarea></div>
            </div>
            <div class="form-foot">
              <button type="submit" class="btn-save">Enregistrer les modifications</button>
            </div>
          </form>
        </div>
        <div class="form-section">
          <div class="form-section-title">Changer mon mot de passe</div>
          <form method="POST" action="../../controllers/UtilisateurController.php">
            <input type="hidden" name="action" value="admin_reset_pwd">
            <input type="hidden" name="id" value="<?= $currentUserId ?>">
            <div class="form-grid">
              <div class="form-field full"><label class="form-label">Nouveau mot de passe</label><input class="form-input" type="password" name="new_mdp" placeholder="Min. 8 caractères, 1 majuscule, 1 chiffre"></div>
            </div>
            <div class="form-foot">
              <button type="submit" class="btn-save" style="background:var(--purple)">Changer le mot de passe</button>
            </div>
          </form>
        </div>
        <div class="form-section">
          <div class="form-section-title">Sécurité - Authentification à 2 facteurs (2FA)</div>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
              <p style="font-size: .8rem; color: var(--muted); margin-bottom: 0.5rem;">
                <?= $currentUser['two_factor_enabled'] == 1 ? 'L\'authentification à deux facteurs est <strong>activée</strong>.' : 'L\'authentification à deux facteurs est <strong>inactive</strong>.' ?>
              </p>
            </div>
            <div>
              <?php if($currentUser['two_factor_enabled'] == 1): ?>
                <button class="btn-save" style="background:var(--rose);" onclick="document.getElementById('m-disable-2fa-admin').classList.add('open')">Désactiver</button>
              <?php else: ?>
                <a href="../FrontOffice/2fa_setup.php" class="btn-save" style="text-decoration:none;">Activer</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title">Sécurité - Connexion par Face ID</div>
          <div style="display:flex; flex-direction:column; gap:1rem;">
            <p style="font-size: .8rem; color: var(--muted);">
              Configurez la reconnaissance faciale pour vous connecter rapidement sans mot de passe.
            </p>
            <?php if(!empty($currentUser['selfie_path'])): ?>
              <div style="display:flex; align-items:center; gap:1rem; background:rgba(34,197,94,.08); padding:.8rem; border-radius:10px; border:1px solid rgba(34,197,94,.2);">
                <div style="width:40px; height:40px; border-radius:50%; overflow:hidden; border:2px solid var(--green);">
                  <img src="../../<?= $currentUser['selfie_path'] ?>" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div style="flex:1;">
                  <div style="font-size:.75rem; font-weight:700; color:var(--green);">Face ID Activé</div>
                  <div style="font-size:.65rem; color:var(--muted);">Votre visage est enregistré comme référence.</div>
                </div>
                <button class="act-btn danger" title="Supprimer Face ID" onclick="if(confirm('Supprimer votre Face ID ?')) window.location.href='../../controllers/UtilisateurController.php?action=delete_selfie_admin'"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg></button>
              </div>
            <?php else: ?>
              <div id="face-id-setup-admin">
                <button class="btn-save" style="background:var(--blue-light); color:var(--blue); border:1px dashed var(--blue);" onclick="initAdminFaceSetup()">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.5rem"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/><circle cx="12" cy="12" r="3"/><path d="M16 16v1m-8-1v1"/></svg>
                  Configurer mon Face ID
                </button>
              </div>
              <div id="face-admin-zone" style="display:none; flex-direction:column; align-items:center; gap:.8rem;">
                <video id="webcam-admin" autoplay playsinline style="width:100%; max-width:240px; border-radius:12px; background:#000;"></video>
                <canvas id="canvas-admin" style="display:none;"></canvas>
                <div id="face-admin-status" style="font-size:.7rem; color:var(--muted);">Regardez la caméra</div>
                <div style="display:flex; gap:.4rem; width:100%;">
                  <button type="button" class="btn-save" style="flex:1;" id="capture-admin-btn" onclick="captureAdminFace()">Capturer</button>
                  <button type="button" class="mbtn-cancel" onclick="cancelAdminFace()">Annuler</button>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php elseif($page==='permissions' && $currentUserRole==='SUPER_ADMIN'): ?>
    <?php
      $admins = array_filter($m->findAll('admins'), fn($a) => $a['role'] !== 'SUPER_ADMIN');
    ?>
    <div class="two-col-layout">
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Administrateurs (<?= count($admins) ?>)</div>
          <div class="search-bar" style="width:250px">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="perm-search" placeholder="Rechercher un admin..." oninput="filterPermTable()">
          </div>
        </div>
        <div class="table-responsive">
          <table id="perm-table">
            <thead><tr><th>Admin</th><th>Email</th><th>Modules</th></tr></thead>
            <tbody>
            <?php foreach($admins as $adm):
              $adm_ini = initials($adm['nom'], $adm['prenom']);
              $adm_perms = $perms[$adm['id']] ?? [];
            ?>
            <tr class="user-row" onclick="showPermPanel(<?= $adm['id'] ?>)" data-id="<?= $adm['id'] ?>">
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#3B82F6,#6366F1);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0"><?= $adm_ini ?></div>
                  <div class="td-name"><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></div>
                </div>
              </td>
              <td><span class="td-mono"><?= htmlspecialchars($adm['email']) ?></span></td>
              <td><span class="badge b-attente"><?= count($adm_perms) ?>/<?= count($ALL_MODULES) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="detail-panel" id="perm-detail-panel">
        <div id="perm-empty-msg" style="text-align:center;padding:4rem 1rem;color:var(--muted);">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:1rem;opacity:.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="12" r="3"/></svg>
          <p style="font-size:.82rem">Sélectionnez un administrateur pour gérer ses accès.</p>
        </div>
        <?php foreach($admins as $adm): $adm_perms = $perms[$adm['id']] ?? []; ?>
        <div class="perm-form-container" id="perm-form-<?= $adm['id'] ?>" style="display:none">
          <div class="dp-header" style="margin-bottom:1.5rem">
            <div class="dp-av"><?= initials($adm['nom'], $adm['prenom']) ?></div>
            <div>
              <div class="dp-name"><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></div>
              <div class="dp-cin" style="color:var(--blue);font-weight:600">ADMINISTRATEUR</div>
            </div>
          </div>
          <form method="POST" action="?page=permissions">
            <input type="hidden" name="action" value="save_permissions">
            <input type="hidden" name="target_id" value="<?= $adm['id'] ?>">
            <div class="dp-section">Accès aux modules</div>
            <div style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:1.5rem">
              <?php foreach($ALL_MODULES as $mk => $md): $checked = in_array($mk, $adm_perms); ?>
              <label class="perm-toggle-v2 <?= $checked?'active':'' ?>" style="padding:.7rem; border-radius:12px; border:1px solid var(--border); display:flex; align-items:center; gap:.8rem; cursor:pointer;">
                <input type="checkbox" name="modules[]" value="<?= $mk ?>" <?= $checked?'checked':'' ?> onchange="this.parentElement.classList.toggle('active', this.checked)" style="position:absolute;opacity:0;width:0;height:0">
                <div class="switch"></div>
                <div style="background:<?= str_replace('var(--','rgba(', str_replace(')',',0.12)',$md['color'])) ?>; width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg width="14" height="14" fill="none" stroke="<?= $md['color'] ?>" stroke-width="2" viewBox="0 0 24 24"><?= $md['icon'] ?></svg>
                </div>
                <div style="font-size:.82rem;font-weight:600;"><?= $md['label'] ?></div>
              </label>
              <?php endforeach; ?>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:1rem">
              <button type="button" class="filter-btn" onclick="selectAll(this.closest('form'))">Tout cocher</button>
              <button type="button" class="filter-btn" onclick="clearAll(this.closest('form'))">Tout décocher</button>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:.8rem">
              Sauvegarder les permissions
            </button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif($page==='statistiques' && in_array('statistiques',$myModules)): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h2 style="font-family:var(--fh);font-size:1.1rem;margin:0;">Tableau de bord dynamique</h2>
      <form method="GET" action="" style="display:flex;gap:.5rem;align-items:center;">
        <input type="hidden" name="page" value="statistiques">
        <label style="font-size:.8rem;color:var(--muted);font-weight:600;">Période :</label>
        <select name="periode" class="minput" style="width:180px;padding:.3rem .8rem;" onchange="this.form.submit()">
          <option value="tout" <?= $periode==='tout'?'selected':'' ?>>Depuis toujours</option>
          <option value="annee" <?= $periode==='annee'?'selected':'' ?>>Cette année</option>
          <option value="mois" <?= $periode==='mois'?'selected':'' ?>>Ce mois-ci</option>
        </select>
      </form>
    </div>
    <div class="kpi-row" style="grid-template-columns:repeat(4,1fr)">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)"><svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['total'] ?></div><div class="kpi-label">Total inscrits</div><div class="trend-pill">+<?= $stats['mois'] ?> ce mois</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)"><svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['actifs'] ?></div><div class="kpi-label">Clients actifs</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--purple-light)"><svg width="18" height="18" fill="none" stroke="var(--purple)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['admins'] ?></div><div class="kpi-label">Administrateurs</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:rgba(245,158,11,.1)"><svg width="18" height="18" fill="none" stroke="#F59E0B" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['association'] ?></div><div class="kpi-label">Associations</div></div>
      </div>
    </div>
    <div class="stats-grid" style="margin-top:1.5rem">
      <div class="stat-card">
        <div class="stat-card-title">Répartition par rôle</div>
        <div style="position:relative;height:240px;width:100%">
            <canvas id="roleChart"></canvas>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-title">Statut KYC</div>
        <?php
          $wKyc = "";
          if ($periode === 'mois') $wKyc = " AND MONTH(date_inscription)=MONTH(NOW()) AND YEAR(date_inscription)=YEAR(NOW())";
          elseif ($periode === 'annee') $wKyc = " AND YEAR(date_inscription)=YEAR(NOW())";
          $stmt = \config::getConnexion()->query("SELECT status_kyc, COUNT(*) as c FROM utilisateur WHERE 1=1 $wKyc GROUP BY status_kyc");
          $kycData = [];
          foreach($stmt->fetchAll() as $r) $kycData[$r['status_kyc']] = $r['c'];
          $kycVerifie  = $kycData['VERIFIE']    ?? 0;
          $kycAttente  = $kycData['EN_ATTENTE'] ?? 0;
          $kycRejete   = $kycData['REJETE']     ?? 0;
        ?>
        <div style="position:relative;height:240px;width:100%">
            <canvas id="kycChart"></canvas>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-title">Statut des comptes</div>
        <?php
          $stmt2 = \config::getConnexion()->query("SELECT status, COUNT(*) as c FROM utilisateur WHERE 1=1 $wKyc GROUP BY status");
          $statusData = [];
          foreach($stmt2->fetchAll() as $r) $statusData[$r['status']] = $r['c'];
          $stActif    = $statusData['ACTIF']    ?? 0;
          $stInactif  = $statusData['INACTIF']  ?? 0;
          $stSuspendu = $statusData['SUSPENDU'] ?? 0;
        ?>
        <div style="position:relative;height:240px;width:100%">
            <canvas id="statusChart"></canvas>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      (function() {
        const roleCtx = document.getElementById('roleChart');
        if(roleCtx) {
          new Chart(roleCtx, {
              type: 'doughnut',
              data: {
                  labels: ['Clients', 'Administrateurs', 'Associations'],
                  datasets: [{
                      data: [<?= max($stats['total'] - $stats['admins'] - $stats['association'], 0) ?>, <?= $stats['admins'] ?>, <?= $stats['association'] ?>],
                      backgroundColor: ['#3B82F6', '#8B5CF6', '#F59E0B'],
                      borderWidth: 0
                  }]
              },
              options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
          });
        }
        const kycCtx = document.getElementById('kycChart');
        if(kycCtx) {
          new Chart(kycCtx, {
              type: 'bar',
              data: {
                  labels: ['Vérifié', 'En attente', 'Rejeté'],
                  datasets: [{
                      label: 'Utilisateurs',
                      data: [<?= $kycVerifie ?>, <?= $kycAttente ?>, <?= $kycRejete ?>],
                      backgroundColor: ['#22C55E', '#F59E0B', '#F43F5E'],
                      borderRadius: 4
                  }]
              },
              options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
          });
        }
        const statusCtx = document.getElementById('statusChart');
        if(statusCtx) {
          new Chart(statusCtx, {
              type: 'pie',
              data: {
                  labels: ['Actifs', 'Inactifs', 'Suspendus'],
                  datasets: [{
                      data: [<?= $stActif ?>, <?= $stInactif ?>, <?= $stSuspendu ?>],
                      backgroundColor: ['#22C55E', '#94A3B8', '#F43F5E'],
                      borderWidth: 0
                  }]
              },
              options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
          });
        }
      })();
    </script>

    <?php elseif($page==='audit' && in_array('audit', $myModules)): ?>
    <div class="table-card">
      <div class="table-toolbar" style="flex-direction:column; align-items:stretch; gap:1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="table-toolbar-title">Journal d'Audit (<?= $totalLogs ?>)</div>
            <a href="?page=audit" class="btn-ghost" style="font-size:.7rem;">Réinitialiser les filtres</a>
        </div>
        
        <form method="GET" action="" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:.8rem; background:var(--bg3); padding:1rem; border-radius:10px;">
            <input type="hidden" name="page" value="audit">
            
            <div class="mfield">
                <label class="mlabel" style="font-size:.6rem;">Administrateur</label>
                <select name="audit_admin" class="mselect" style="padding:.35rem .6rem; font-size:.75rem;">
                    <option value="">Tous les admins</option>
                    <?php foreach($allAdmins as $adm): ?>
                        <option value="<?= $adm['id'] ?>" <?= ($_GET['audit_admin']??'') == $adm['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mfield">
                <label class="mlabel" style="font-size:.6rem;">Action</label>
                <input type="text" name="audit_action" class="minput" style="padding:.35rem .6rem; font-size:.75rem;" placeholder="Ex: login, update..." value="<?= htmlspecialchars($_GET['audit_action']??'') ?>">
            </div>
            
            <div class="mfield">
                <label class="mlabel" style="font-size:.6rem;">Depuis le</label>
                <input type="date" name="audit_from" class="minput" style="padding:.35rem .6rem; font-size:.75rem;" value="<?= htmlspecialchars($_GET['audit_from']??'') ?>">
            </div>
            
            <div class="mfield">
                <label class="mlabel" style="font-size:.6rem;">Jusqu'au</label>
                <input type="date" name="audit_to" class="minput" style="padding:.35rem .6rem; font-size:.75rem;" value="<?= htmlspecialchars($_GET['audit_to']??'') ?>">
            </div>
            
            <div style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:.45rem;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filtrer
                </button>
            </div>
        </form>
      </div>
      <div class="table-responsive">
      <table>
        <thead><tr><th>Date</th><th>Admin</th><th>Action</th><th>Cible</th><th>Détails</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach($logs as $l): ?>
        <tr>
          <td style="white-space:nowrap"><span class="td-mono"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></span></td>
          <td style="white-space:nowrap"><div class="td-name"><?= htmlspecialchars(($l['admin_nom']??'').' '.($l['admin_prenom']??'')) ?></div></td>
          <td style="white-space:nowrap"><span class="badge" style="background:var(--blue-light);color:var(--blue);border:none"><?= htmlspecialchars($l['action']) ?></span></td>
          <td style="white-space:nowrap">
            <?php if($l['target_user_id']): ?>
              <span class="td-mono">#<?= $l['target_user_id'] ?></span> <?= htmlspecialchars(($l['target_nom']??'').' '.($l['target_prenom']??'')) ?>
            <?php else: ?>
              <span style="color:var(--muted)">-</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.72rem;color:var(--muted)"><?= htmlspecialchars($l['details']) ?></td>
          <td><span class="td-mono" style="font-size:.65rem"><?= htmlspecialchars($l['ip_address']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($logs)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">Aucun journal trouvé.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      </div>
      
      <!-- Audit Pagination -->
      <?php if (isset($totalAuditPages) && $totalAuditPages > 1): ?>
      <?php 
        $qParams = $_GET;
        unset($qParams['p']);
        $baseQuery = http_build_query($qParams);
      ?>
      <div class="pagination">
        <a href="?<?= $baseQuery ?>&p=<?= max(1, $auditP-1) ?>" class="pg-link <?= $auditP <= 1 ? 'disabled' : '' ?>">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        
        <?php 
        $start = max(1, $auditP - 2);
        $end = min($totalAuditPages, $auditP + 2);
        if($start > 1) echo '<span class="pg-text">...</span>';
        for($i = $start; $i <= $end; $i++): ?>
          <a href="?<?= $baseQuery ?>&p=<?= $i ?>" class="pg-link <?= $i === $auditP ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; 
        if($end < $totalAuditPages) echo '<span class="pg-text">...</span>';
        ?>
        
        <a href="?<?= $baseQuery ?>&p=<?= min($totalAuditPages, $auditP+1) ?>" class="pg-link <?= $auditP >= $totalAuditPages ? 'disabled' : '' ?>">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:4rem;color:var(--muted)">
      <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 1rem;display:block;opacity:.4"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <div style="font-family:var(--fh);font-size:1.1rem;font-weight:700;margin-bottom:.5rem">Accès refusé</div>
      <div style="font-size:.82rem">Vous n'avez pas les permissions pour accéder à ce module.</div>
      <a href="?page=utilisateurs" style="display:inline-block;margin-top:1rem;color:var(--blue);font-size:.82rem;">← Retour aux utilisateurs</a>
    </div>
    <?php endif; ?>
  </div>
<?php if (isset($_GET['ajax'])) exit; ?>
</div>
<script>
function loadPage(url, pushState = true) {
    const main = document.getElementById('main-content');
    if (!main) return;
    // Feedback visuel
    main.style.transition = 'opacity 0.2s';
    main.style.opacity = '0.4';
    const ajaxUrl = url.includes('?') ? url + '&ajax=1' : url + '?ajax=1';
    fetch(ajaxUrl)
        .then(response => response.text())
        .then(html => {
            main.innerHTML = html;
            main.style.opacity = '1';
            // Ré-exécution des scripts (Chart.js, Leaflet)
            const scripts = main.querySelectorAll('script');
            scripts.forEach(s => {
                const newScript = document.createElement('script');
                if (s.src) {
                    newScript.src = s.src;
                } else {
                    newScript.textContent = s.textContent;
                }
                document.body.appendChild(newScript);
                if (!s.src) newScript.remove();
            });
            // Mise à jour menu actif
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
                const href = item.getAttribute('href');
                if (href && url.includes(href)) item.classList.add('active');
            });
            if (pushState) window.history.pushState({url: url}, '', url);
            // Scroll top
            const content = main.querySelector('.content');
            if (content) content.scrollTop = 0;
        })
        .catch(err => {
            console.error('Erreur AJAX:', err);
            window.location.href = url;
        });
}
document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (!link) return;
    const href = link.getAttribute('href');
    if (href && href.startsWith('?page=')) {
        e.preventDefault();
        loadPage(href);
    }
});
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.method.toLowerCase() === 'get') {
        const pageInput = form.querySelector('input[name="page"]');
        if (pageInput) {
            e.preventDefault();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            loadPage('?' + params.toString());
        }
    }
});
window.onpopstate = function(e) {
    if (e.state && e.state.url) loadPage(e.state.url, false);
};
</script>
<?php if (!isset($_GET['ajax'])): ?>
<div class="modal-overlay" id="m-add" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-add').classList.remove('open')">✕</button>
    <div class="modal-title">Ajouter un utilisateur</div>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_add">
      <div class="mg2">
        <div class="mfield"><label class="mlabel">Nom *</label><input class="minput" type="text" name="nom"></div>
        <div class="mfield"><label class="mlabel">Prénom *</label><input class="minput" type="text" name="prenom"></div>
        <div class="mfield mfull"><label class="mlabel">Email *</label><input class="minput" type="text" name="email"></div>
        <div class="mfield"><label class="mlabel">Mot de passe *</label><input class="minput" type="password" name="mdp"></div>
        <div class="mfield"><label class="mlabel">Téléphone *</label><input class="minput" type="text" name="numTel"></div>
        <div class="mfield"><label class="mlabel">Date de naissance *</label><input class="minput" type="text" name="date_naissance" placeholder="YYYY-MM-DD"></div>
        <div class="mfield"><label class="mlabel">CIN *</label><input class="minput" type="text" name="cin" placeholder="8 chiffres"></div>
        <div class="mfield"><label class="mlabel">Rôle</label>
          <select class="mselect" name="role"><option value="CLIENT">CLIENT</option><option value="ADMIN">ADMIN</option></select>
        </div>
        <div class="mfield mfull"><label class="mlabel">Adresse *</label><textarea class="minput" name="adresse" rows="2" style="resize:none"></textarea></div>
      </div>
      <div class="mfoot"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-add').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save">Ajouter</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="m-edit" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-edit').classList.remove('open')">✕</button>
    <div class="modal-title">Modifier l'utilisateur</div>
    <form method="POST" action="../../controllers/UtilisateurController.php" id="edit-form">
      <input type="hidden" name="action" value="admin_edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="mg2">
        <div class="mfield"><label class="mlabel">Nom *</label><input class="minput" type="text" name="nom" id="edit-nom"></div>
        <div class="mfield"><label class="mlabel">Prénom *</label><input class="minput" type="text" name="prenom" id="edit-prenom"></div>
        <div class="mfield"><label class="mlabel">Téléphone *</label><input class="minput" type="text" name="numTel" id="edit-numTel"></div>
        <input type="hidden" name="role" id="edit-role">
        <div class="mfield"><label class="mlabel">Statut</label>
          <select class="mselect" name="status" id="edit-status"><option value="ACTIF">ACTIF</option><option value="INACTIF">INACTIF</option><option value="SUSPENDU">SUSPENDU</option></select>
        </div>
        <div class="mfield"><label class="mlabel">KYC</label>
          <select class="mselect" name="status_kyc" id="edit-kyc"><option value="EN_ATTENTE">EN_ATTENTE</option><option value="VERIFIE">VERIFIE</option><option value="REJETE">REJETE</option></select>
        </div>
        <div class="mfield"><label class="mlabel">AML</label>
          <select class="mselect" name="status_aml" id="edit-aml"><option value="EN_ATTENTE">EN_ATTENTE</option><option value="CONFORME">CONFORME</option><option value="ALERTE">ALERTE</option></select>
        </div>
        <div class="mfield mfull"><label class="mlabel">Adresse *</label><textarea class="minput" name="adresse" id="edit-adresse" rows="2" style="resize:none"></textarea></div>
      </div>
      <div class="mfoot"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-edit').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save">Sauvegarder</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="m-resetpwd" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:380px">
    <button class="modal-close" onclick="document.getElementById('m-resetpwd').classList.remove('open')">✕</button>
    <div class="modal-title">Réinitialiser le mot de passe</div>
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:.9rem" id="reset-name-label"></p>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_reset_pwd">
      <input type="hidden" name="id" id="reset-id">
      <div class="mfield" style="margin-bottom:.8rem"><label class="mlabel">Nouveau mot de passe</label><input class="minput" type="text" name="new_mdp" value="Nexa@2025"></div>
      <div class="mfoot"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-resetpwd').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save">Réinitialiser</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="m-kyc" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-kyc').classList.remove('open')">✕</button>
    <div style="width:48px;height:48px;border-radius:50%;background:var(--green-light);display:flex;align-items:center;justify-content:center;margin:0 auto .8rem"><svg width="22" height="22" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></div>
    <div class="modal-title" style="text-align:center">Valider le KYC</div>
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:.9rem">Confirmer la validation KYC et AML de cet utilisateur ?</p>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="valider_kyc">
      <input type="hidden" name="id" id="kyc-id">
      <input type="hidden" name="role" id="kyc-role">
      <div class="mfoot" style="justify-content:center"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-kyc').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save" style="background:var(--green)">Valider KYC</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="m-bloquer" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-bloquer').classList.remove('open')">✕</button>
    <div class="confirm-icon"><svg width="22" height="22" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
    <div class="modal-title" style="text-align:center">Bloquer le compte</div>
    <p class="confirm-text">Cette action suspendra l'accès de l'utilisateur.</p>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="bloquer">
      <input type="hidden" name="id"   id="bloq-id">
      <input type="hidden" name="kyc"  id="bloq-kyc">
      <input type="hidden" name="aml"  id="bloq-aml">
      <input type="hidden" name="role" id="bloq-role">
      <div class="mfoot" style="justify-content:center"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-bloquer').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-danger">Bloquer</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="m-del" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-del').classList.remove('open')">✕</button>
    <div class="confirm-icon"><svg width="22" height="22" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg></div>
    <div class="modal-title" style="text-align:center">Confirmer la suppression</div>
    <p class="confirm-text">Supprimer <strong id="del-name"></strong> ? Action irréversible.</p>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_delete">
      <input type="hidden" name="id" id="del-id">
      <div class="mfoot" style="justify-content:center"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-del').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-danger">Supprimer</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="m-perm" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:500px;">
    <button class="modal-close" onclick="document.getElementById('m-perm').classList.remove('open')">✕</button>
    <div class="modal-title">Permissions : <span id="perm-admin-name" style="color:var(--blue)"></span></div>
    <form method="POST" action="?page=permissions" id="form-perm">
      <input type="hidden" name="action" value="save_permissions">
      <input type="hidden" name="target_id" id="perm-target-id">
      <div class="perm-module-grid">
        <?php foreach($ALL_MODULES as $mk => $md): ?>
        <label class="perm-toggle" id="lbl-perm-<?= $mk ?>">
          <input type="checkbox" name="modules[]" value="<?= $mk ?>" id="chk-perm-<?= $mk ?>" onchange="this.parentElement.classList.toggle('active', this.checked)">
          <div class="perm-dot"></div>
          <div>
            <svg width="14" height="14" fill="none" stroke="<?= $md['color'] ?>" stroke-width="1.8" viewBox="0 0 24 24"><?= $md['icon'] ?></svg>
          </div>
          <div class="perm-toggle-label"><?= $md['label'] ?></div>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="perm-footer">
        <button type="button" class="btn-ghost" style="margin-right:.5rem" onclick="selectAll(this.closest('form'))">Tout cocher</button>
        <button type="button" class="btn-ghost" style="margin-right:.5rem" onclick="clearAll(this.closest('form'))">Tout décocher</button>
        <button type="submit" class="btn-perm-save">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
          Sauvegarder
        </button>
      </div>
    </form>
  </div>
</div>
<script>
function openEdit(u) {
  document.getElementById('edit-id').value      = u.id;
  document.getElementById('edit-nom').value     = u.nom;
  document.getElementById('edit-prenom').value  = u.prenom;
  document.getElementById('edit-numTel').value  = u.numTel;
  document.getElementById('edit-adresse').value = u.adresse;
  document.getElementById('edit-role').value    = u.role;
  document.getElementById('edit-status').value  = u.status;
  document.getElementById('edit-kyc').value     = u.status_kyc;
  document.getElementById('edit-aml').value     = u.status_aml;
  document.getElementById('m-edit').classList.add('open');
}
function openDel(id, name) {
  document.getElementById('del-id').value = id;
  document.getElementById('del-name').textContent = name;
  document.getElementById('m-del').classList.add('open');
}
function openResetPwd(id, name) {
  document.getElementById('reset-id').value = id;
  document.getElementById('reset-name-label').textContent = 'Utilisateur : ' + name;
  document.getElementById('m-resetpwd').classList.add('open');
}
function openKyc(id, role) {
  document.getElementById('kyc-id').value   = id;
  document.getElementById('kyc-role').value = role;
  document.getElementById('m-kyc').classList.add('open');
}
function openBloquer(id, kyc, aml, role) {
  document.getElementById('bloq-id').value   = id;
  document.getElementById('bloq-kyc').value  = kyc;
  document.getElementById('bloq-aml').value  = aml;
  document.getElementById('bloq-role').value = role;
  document.getElementById('m-bloquer').classList.add('open');
}
function showDetail(id) {
  window.location.href = '?page=utilisateurs&filtre=<?= urlencode($filtre) ?>&detail=' + id;
}
function filterTable() {
  var q = document.getElementById('search-input').value.toLowerCase();
  document.querySelectorAll('#user-table tbody tr').forEach(function(row) {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function filterPermTable() {
  var q = document.getElementById('perm-search').value.toLowerCase();
  document.querySelectorAll('#perm-table tbody tr').forEach(function(row) {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function showPermPanel(id) {
  document.getElementById('perm-empty-msg').style.display = 'none';
  document.querySelectorAll('.perm-form-container').forEach(c => c.style.display = 'none');
  document.querySelectorAll('#perm-table tr').forEach(r => r.classList.remove('detail-active'));
  document.getElementById('perm-form-' + id).style.display = 'block';
  document.querySelector('#perm-table tr[data-id="'+id+'"]').classList.add('detail-active');
}
function openPermModal(id, perms, name) {
  document.getElementById('perm-target-id').value = id;
  document.getElementById('perm-admin-name').textContent = name;
  document.querySelectorAll('#form-perm .perm-toggle').forEach(function(lbl) {
    lbl.classList.remove('active');
    lbl.querySelector('input[type=checkbox]').checked = false;
  });
  perms.forEach(function(mk) {
    let chk = document.getElementById('chk-perm-' + mk);
    if(chk) {
      chk.checked = true;
      chk.parentElement.classList.add('active');
    }
  });
  document.getElementById('m-perm').classList.add('open');
}
function selectAll(form) {
  form.querySelectorAll('.perm-toggle').forEach(function(lbl) {
    lbl.classList.add('active');
    lbl.querySelector('input[type=checkbox]').checked = true;
  });
}
function clearAll(form) {
  form.querySelectorAll('.perm-toggle').forEach(function(lbl) {
    lbl.classList.remove('active');
    lbl.querySelector('input[type=checkbox]').checked = false;
  });
}
document.addEventListener('click', function(e) {
  var wrapper = document.getElementById('notif-wrapper');
  var dropdown = document.getElementById('notif-dropdown');
  if (wrapper && dropdown && dropdown.classList.contains('show')) {
    if (!wrapper.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  }
});

let adminStream = null;
async function initAdminFaceSetup() {
  try {
    adminStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
    document.getElementById('webcam-admin').srcObject = adminStream;
    document.getElementById('face-id-setup-admin').style.display = 'none';
    document.getElementById('face-admin-zone').style.display = 'flex';
  } catch (err) { alert("Erreur caméra : " + err.message); }
}
function cancelAdminFace() {
  if(adminStream) adminStream.getTracks().forEach(t => t.stop());
  document.getElementById('face-id-setup-admin').style.display = 'block';
  document.getElementById('face-admin-zone').style.display = 'none';
}
async function captureAdminFace() {
  const video = document.getElementById('webcam-admin');
  const canvas = document.getElementById('canvas-admin');
  const btn = document.getElementById('capture-admin-btn');
  const status = document.getElementById('face-admin-status');
  
  canvas.width = video.videoWidth; canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);
  const imageData = canvas.toDataURL('image/jpeg', 0.8);
  
  btn.disabled = true;
  status.innerText = "Enregistrement...";
  
  const formData = new FormData();
  formData.append('action', 'setup_face_id_admin');
  formData.append('image', imageData);
  
  try {
    const resp = await fetch('../../controllers/UtilisateurController.php', { method:'POST', body:formData });
    const res = await resp.json();
    if(res.success) {
      status.innerHTML = "✅ Face ID configuré !";
      setTimeout(() => location.reload(), 1000);
    } else {
      status.innerText = res.error;
      btn.disabled = false;
    }
  } catch (e) { status.innerText = "Erreur serveur"; btn.disabled = false; }
}
</script>

<!-- ═══ MODAL DESACTIVER 2FA (ADMIN) ═══ -->
<div class="modal-overlay" id="m-disable-2fa-admin" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-disable-2fa-admin').classList.remove('open')">x</button>
    <div class="modal-title">Désactiver le 2FA</div>
    <form method="POST" action="../../controllers/UtilisateurController.php">
      <input type="hidden" name="action" value="disable_2fa">
      <div class="mfield">
        <label class="mlabel">Mot de passe actuel *</label>
        <input class="minput" type="password" name="mdp" required placeholder="Pour des raisons de sécurité">
      </div>
      <div class="mfoot">
        <button type="button" class="mbtn-cancel" onclick="document.getElementById('m-disable-2fa-admin').classList.remove('open')">Annuler</button>
        <button type="submit" class="mbtn-danger">Désactiver</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
<?php endif; ?>