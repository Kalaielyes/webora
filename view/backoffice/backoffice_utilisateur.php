<?php
require_once __DIR__ . '/../../model/Session.php';
require_once __DIR__ . '/../../model/Utilisateur.php';
Session::requireAdmin('../FrontOffice/login.php');

$m      = new Utilisateur();
$filtre = $_GET['filtre'] ?? 'tous';
$page   = $_GET['page'] ?? 'utilisateurs'; // dashboard | utilisateurs | profil | permissions
$users  = $m->findAll($filtre);
$periode= $_GET['periode'] ?? 'tout';
$stats  = $m->getStats($periode);
$flash  = Session::getFlash();

$currentUserId   = (int) Session::get('user_id');
$currentUserRole = Session::get('role');
$currentUser     = $m->findById($currentUserId);

$detailId = (int)($_GET['detail'] ?? ($users[0]['id'] ?? 0));
$detail   = $detailId ? $m->findById($detailId) : ($users[0] ?? null);
// Security: never show SUPER_ADMIN in the detail panel
if ($detail && ($detail['role'] ?? '') === 'SUPER_ADMIN') {
    $detail = $users[0] ?? null;
    $detailId = $detail['id'] ?? 0;
}

$adminInitials = strtoupper(mb_substr(Session::get('user_nom'),0,1).mb_substr(Session::get('user_prenom'),0,1));

// ── Module definitions ──────────────────────────────────────────────────────
$ALL_MODULES = [
    'comptes'          => ['label'=>'Comptes',           'icon'=>'<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',                                              'color'=>'var(--blue)'],
    'actions'          => ['label'=>'Actions',           'icon'=>'<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',                                                                                                'color'=>'var(--teal)'],
    'credit'           => ['label'=>'Crédit',            'icon'=>'<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',                                                    'color'=>'var(--green)'],
    'demande_chequier' => ['label'=>'Demande Chéquier',  'icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>','color'=>'var(--amber)'],
    'dons_collectes'   => ['label'=>'Dons Collectés',   'icon'=>'<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>','color'=>'var(--rose)'],
    'utilisateurs'     => ['label'=>'Utilisateurs',     'icon'=>'<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>','color'=>'var(--purple)'],
    'statistiques'     => ['label'=>'Statistiques',     'icon'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',                               'color'=>'#F59E0B'],
];

// ── Notifications ───────────────────────────────────────────────────────────
$notifications = [];
$pdo = \config::getConnexion();
// 1. Nouveaux comptes d'aujourd'hui
$stmtInscrits = $pdo->query("SELECT id, nom, prenom, date_inscription FROM utilisateur WHERE DATE(date_inscription) = CURDATE() ORDER BY date_inscription DESC LIMIT 5");
foreach($stmtInscrits->fetchAll() as $row) {
    $notifications[] = [
        'type' => 'new_account',
        'message' => "Nouveau compte : {$row['nom']} {$row['prenom']}",
        'time' => $row['date_inscription'],
        'link' => "?page=utilisateurs&filtre=tous&detail={$row['id']}"
    ];
}
// 2. ID déposé (KYC en attente)
$stmtKyc = $pdo->query("SELECT id, nom, prenom, date_inscription FROM utilisateur WHERE status_kyc = 'EN_ATTENTE' AND id_file_path IS NOT NULL AND id_file_path != '' ORDER BY date_inscription DESC LIMIT 5");
foreach($stmtKyc->fetchAll() as $row) {
    $notifications[] = [
        'type' => 'id_deposited',
        'message' => "ID déposé : {$row['nom']} {$row['prenom']} attend la validation KYC",
        'time' => $row['date_inscription'],
        'link' => "?page=utilisateurs&filtre=kyc_attente&detail={$row['id']}"
    ];
}
// Sort by time descending
usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$notifications = array_slice($notifications, 0, 5); // Keep top 5
$unreadCount = count($notifications);

// ── Per-admin permission store (JSON file, simple approach) ──────────────────
$permFile = __DIR__ . '/../../model/admin_permissions.json';
$perms = [];
if (file_exists($permFile)) {
    $perms = json_decode(file_get_contents($permFile), true) ?? [];
}

// Helper: get modules an admin can access
function getAdminModules(array $perms, int $uid, string $role, array $ALL_MODULES): array {
    if ($role === 'SUPER_ADMIN') return array_keys($ALL_MODULES);
    return $perms[$uid] ?? [];
}

$myModules = getAdminModules($perms, $currentUserId, $currentUserRole, $ALL_MODULES);

// ── Handle permission save (SUPER_ADMIN only, AJAX-style POST) ───────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='save_permissions') {
    if ($currentUserRole === 'SUPER_ADMIN') {
        $targetId  = (int)($_POST['target_id'] ?? 0);
        $granted   = $_POST['modules'] ?? [];
        // Only valid module keys
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
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>LegalFin Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Utilisateur.css">
<style>
/* ── LAYOUT ──────────────────────────────────────────────────────────────── */
body{display:flex;overflow:hidden;height:100vh;}
.sidebar{width:230px;background:var(--navy);display:flex;flex-direction:column;flex-shrink:0;height:100vh;}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.content{padding:1.4rem 1.8rem;flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:1.2rem;}

/* ── SIDEBAR ──────────────────────────────────────────────────────────────── */
.sb-logo{padding:1.2rem 1.2rem .9rem;border-bottom:1px solid rgba(255,255,255,.08);}
.sb-logo-name{font-family:var(--fh);font-size:1.05rem;font-weight:800;color:#fff;letter-spacing:-.01em;}
.sb-logo-name span{color:#60A5FA;}
.sb-logo-env{display:inline-flex;align-items:center;gap:4px;background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.3);color:#FCA5A5;border-radius:5px;padding:1px 7px;font-size:.6rem;font-weight:700;margin-top:4px;}
.sb-admin{padding:.9rem 1.2rem;display:flex;align-items:center;gap:.7rem;border-bottom:1px solid rgba(255,255,255,.08);}
.sb-av{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#3B82F6,#6366F1);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-weight:700;font-size:.75rem;color:#fff;flex-shrink:0;}
.sb-aname{font-size:.8rem;font-weight:500;color:#F1F5F9;}
.sb-arole{font-size:.65rem;color:#64748B;}
.sb-nav{flex:1;padding:.6rem 0;overflow-y:auto;}
.nav-section{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#475569;padding:.5rem 1.2rem .25rem;}
.nav-item{display:flex;align-items:center;gap:.65rem;padding:.5rem 1.2rem;font-size:.8rem;color:#94A3B8;cursor:pointer;transition:all .15s;border-left:2px solid transparent;text-decoration:none;}
.nav-item:hover{color:#F1F5F9;background:rgba(255,255,255,.05);}
.nav-item.active{color:#60A5FA;background:rgba(96,165,250,.08);border-left-color:#3B82F6;}
.nav-item svg{width:15px;height:15px;flex-shrink:0;}
.nav-item.disabled{opacity:.35;pointer-events:none;cursor:not-allowed;}
.nav-badge{margin-left:auto;background:rgba(220,38,38,.2);color:#FCA5A5;border-radius:99px;padding:1px 6px;font-size:.6rem;font-weight:700;}
.sb-footer{padding:.8rem 1.2rem;border-top:1px solid rgba(255,255,255,.08);}
.sb-status{display:flex;align-items:center;gap:5px;font-size:.7rem;color:#64748B;}
.status-dot{width:6px;height:6px;border-radius:50%;background:#22C55E;}

/* ── TOPBAR ───────────────────────────────────────────────────────────────── */
.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:0 1.8rem;height:52px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.tb-left{display:flex;align-items:center;gap:.8rem;}
.page-title{font-family:var(--fh);font-size:.92rem;font-weight:700;}
.breadcrumb{font-size:.72rem;color:var(--muted);}
.tb-right{display:flex;align-items:center;gap:.8rem;}
.btn-primary{background:var(--blue);color:#fff;border:none;border-radius:8px;padding:.4rem 1rem;font-size:.78rem;font-weight:500;cursor:pointer;font-family:var(--fb);display:flex;align-items:center;gap:.4rem;transition:background .15s;}
.btn-primary:hover{background:#1D4ED8;}
.btn-ghost{background:transparent;border:1px solid var(--border);border-radius:8px;padding:.35rem .9rem;font-size:.78rem;color:var(--muted2);cursor:pointer;font-family:var(--fb);display:inline-flex;align-items:center;gap:.4rem;transition:all .15s;}
.btn-ghost:hover{border-color:var(--blue);color:var(--blue);}
.search-bar{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 .8rem;height:34px;display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:var(--muted);width:220px;}
.search-bar input{background:transparent;border:none;outline:none;font-size:.8rem;color:var(--text);width:100%;font-family:var(--fb);}

/* ── NOTIFICATIONS ────────────────────────────────────────────────────────── */
.notif-wrapper{position:relative;}
.notif-btn{position:relative;background:transparent;border:1px solid var(--border);border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);transition:all .15s;}
.notif-btn:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-light);}
.notif-badge{position:absolute;top:-4px;right:-4px;background:var(--rose);color:#fff;font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:9px;border:2px solid var(--bg2);line-height:1;}
.notif-dropdown{position:absolute;top:calc(100% + .5rem);right:0;width:300px;background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.05);display:none;flex-direction:column;z-index:100;animation:modalIn .15s ease;}
.notif-dropdown.show{display:flex;}
.notif-header{padding:.8rem 1rem;font-size:.8rem;font-weight:700;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.notif-list{display:flex;flex-direction:column;max-height:320px;overflow-y:auto;}
.notif-item{display:flex;align-items:flex-start;gap:.7rem;padding:.8rem 1rem;border-bottom:1px solid var(--border);text-decoration:none;transition:background .15s;}
.notif-item:hover{background:var(--bg3);}
.notif-item:last-child{border-bottom:none;}
.notif-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.notif-icon.new_account{background:var(--blue-light);color:var(--blue);}
.notif-icon.id_deposited{background:var(--green-light);color:var(--green);}
.notif-msg{font-size:.72rem;color:var(--text);font-weight:500;line-height:1.3;}
.notif-time{font-size:.65rem;color:var(--muted);margin-top:.3rem;font-family:var(--fm);}

/* ── FLASH ────────────────────────────────────────────────────────────────── */
.flash{border-radius:10px;padding:.7rem 1rem;font-size:.78rem;display:flex;align-items:center;gap:.6rem;}
.flash svg{width:14px;height:14px;flex-shrink:0;}
.flash-error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);color:var(--rose);}
.flash-success{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);color:var(--green);}

/* ── KPI ──────────────────────────────────────────────────────────────────── */
.kpi-row{display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1rem 1.1rem;display:flex;align-items:flex-start;gap:.8rem;}
.kpi-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.kpi-data .kpi-val{font-family:var(--fh);font-size:1.5rem;font-weight:700;line-height:1;}
.kpi-data .kpi-label{font-size:.7rem;color:var(--muted);margin-top:.2rem;}
.kpi-data .kpi-sub{font-size:.68rem;margin-top:.25rem;}

/* ── MODULE GRID ──────────────────────────────────────────────────────────── */
.module-section{margin-top:.5rem;}
.module-section-title{font-family:var(--fh);font-size:.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.8rem;}
.module-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;}
.module-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.3rem 1rem;display:flex;flex-direction:column;align-items:center;gap:.7rem;cursor:pointer;transition:all .18s;text-align:center;text-decoration:none;color:var(--text);}
.module-card:hover{border-color:var(--blue);box-shadow:0 4px 20px rgba(37,99,235,.1);transform:translateY(-2px);}
.module-card.locked{opacity:.4;cursor:not-allowed;pointer-events:none;}
.module-card.locked:hover{border-color:var(--border);box-shadow:none;transform:none;}
.module-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;}
.module-label{font-size:.8rem;font-weight:600;}
.module-sub{font-size:.68rem;color:var(--muted);}
.lock-badge{font-size:.6rem;background:var(--rose-light);color:var(--rose);border-radius:99px;padding:2px 8px;font-weight:600;}

/* ── TABLE ────────────────────────────────────────────────────────────────── */
.two-col-layout{display:grid;grid-template-columns:1fr 320px;gap:1.2rem;}
.table-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;}
.table-toolbar{padding:.75rem 1.1rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);}
.table-toolbar-title{font-size:.88rem;font-weight:500;}
.filters{display:flex;gap:.5rem;flex-wrap:wrap;}
.filter-btn{background:transparent;border:1px solid var(--border);border-radius:6px;padding:.28rem .7rem;font-size:.72rem;color:var(--muted);cursor:pointer;font-family:var(--fb);transition:all .15s;text-decoration:none;}
.filter-btn:hover,.filter-btn.active{border-color:var(--blue);color:var(--blue);background:var(--blue-light);}
table{width:100%;border-collapse:collapse;}
thead tr{background:var(--bg3);}
th{padding:.6rem 1rem;text-align:left;font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);white-space:nowrap;}
td{padding:.7rem 1rem;font-size:.8rem;border-bottom:1px solid var(--border);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:var(--bg3);}
.td-mono{font-family:var(--fm);font-size:.72rem;color:var(--muted);}
.td-name{font-weight:500;}

/* ── BADGES ───────────────────────────────────────────────────────────────── */
.badge{display:inline-flex;align-items:center;gap:4px;border-radius:99px;padding:2px 9px;font-size:.67rem;font-weight:600;white-space:nowrap;}
.badge-dot{width:5px;height:5px;border-radius:50%;}
.b-actif{background:var(--green-light);color:var(--green);}
.b-bloque{background:var(--rose-light);color:var(--rose);}
.b-attente{background:var(--amber-light);color:var(--amber);}
.b-inactif{background:#F1F5F9;color:var(--muted);}
.r-client{background:var(--blue-light);color:var(--blue);border-radius:5px;padding:2px 7px;font-size:.65rem;font-weight:600;}
.r-admin{background:var(--purple-light);color:var(--purple);border-radius:5px;padding:2px 7px;font-size:.65rem;font-weight:600;}
.kyc-ok{background:var(--green-light);color:var(--green);border-radius:5px;padding:2px 7px;font-size:.65rem;font-weight:600;}
.kyc-wait{background:var(--amber-light);color:var(--amber);border-radius:5px;padding:2px 7px;font-size:.65rem;font-weight:600;}
.kyc-ko{background:var(--rose-light);color:var(--rose);border-radius:5px;padding:2px 7px;font-size:.65rem;font-weight:600;}

/* ── ACTION BUTTONS ───────────────────────────────────────────────────────── */
.action-group{display:flex;gap:.35rem;}
.act-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);transition:all .15s;}
.act-btn:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-light);}
.act-btn.danger:hover{border-color:var(--rose);color:var(--rose);background:var(--rose-light);}
.act-btn.warn:hover{border-color:var(--amber);color:var(--amber);background:var(--amber-light);}

/* ── DETAIL PANEL ─────────────────────────────────────────────────────────── */
.detail-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.2rem;display:flex;flex-direction:column;gap:1rem;align-self:start;max-height:calc(100vh - 160px);overflow-y:auto;}
.dp-header{display:flex;align-items:center;gap:.8rem;padding-bottom:.8rem;border-bottom:1px solid var(--border);}
.dp-av{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2563EB,#0D9488);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0;}
.dp-name{font-weight:600;font-size:.88rem;}
.dp-cin{font-size:.7rem;color:var(--muted);font-family:var(--fm);}
.dp-badges{display:flex;gap:.3rem;margin-top:.3rem;flex-wrap:wrap;}
.dp-section{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.5rem;}
.dp-row{display:flex;align-items:center;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid var(--border);}
.dp-row:last-child{border-bottom:none;}
.dp-key{font-size:.72rem;color:var(--muted);}
.dp-val{font-size:.78rem;font-weight:500;}
.dp-val-mono{font-family:var(--fm);font-size:.72rem;}
.dp-actions{display:grid;grid-template-columns:1fr 1fr;gap:.4rem;}
.dp-action-btn{border:none;border-radius:7px;padding:.45rem .6rem;font-size:.72rem;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:.4rem;justify-content:center;font-family:var(--fb);transition:all .15s;}
.da-primary{background:var(--blue-light);color:var(--blue);}
.da-primary:hover{background:var(--blue);color:#fff;}
.da-green{background:var(--green-light);color:var(--green);}
.da-green:hover{background:var(--green);color:#fff;}
.da-warning{background:var(--amber-light);color:var(--amber);}
.da-warning:hover{background:var(--amber);color:#fff;}
.da-danger{background:var(--rose-light);color:var(--rose);}
.da-danger:hover{background:var(--rose);color:#fff;}

/* ── MODAL ────────────────────────────────────────────────────────────────── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:1000;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:1.5rem;width:100%;max-width:520px;position:relative;max-height:90vh;overflow-y:auto;animation:modalIn .2s ease both;}
@keyframes modalIn{from{opacity:0;transform:scale(.96);}to{opacity:1;transform:scale(1);}}
.modal-close{position:absolute;top:.8rem;right:.8rem;width:26px;height:26px;border-radius:6px;border:1px solid var(--border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;color:var(--muted);}
.modal-close:hover{background:var(--rose-light);color:var(--rose);}
.modal-title{font-family:var(--fh);font-size:1rem;font-weight:700;margin-bottom:1rem;}
.mg{display:flex;flex-direction:column;gap:.7rem;}
.mg2{display:grid;grid-template-columns:1fr 1fr;gap:.7rem;}
.mfield{display:flex;flex-direction:column;gap:.3rem;}
.mfield.mfull{grid-column:1/-1;}
.mlabel{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);}
.minput{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.5rem .8rem;font-size:.82rem;color:var(--text);font-family:var(--fb);outline:none;transition:border .15s;width:100%;}
.minput:focus{border-color:var(--blue);}
.mselect{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.5rem .8rem;font-size:.82rem;color:var(--text);font-family:var(--fb);outline:none;width:100%;}
.mfoot{display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;}
.mbtn-cancel{background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:.4rem 1rem;font-size:.78rem;color:var(--muted);cursor:pointer;font-family:var(--fb);}
.mbtn-cancel:hover{border-color:var(--blue);}
.mbtn-save{background:var(--blue);color:#fff;border:none;border-radius:7px;padding:.4rem 1.2rem;font-size:.78rem;font-weight:600;cursor:pointer;font-family:var(--fb);}
.mbtn-save:hover{background:#1D4ED8;}
.mbtn-danger{background:var(--rose);color:#fff;border:none;border-radius:7px;padding:.4rem 1.2rem;font-size:.78rem;font-weight:600;cursor:pointer;font-family:var(--fb);}
.confirm-icon{width:48px;height:48px;border-radius:50%;background:var(--rose-light);display:flex;align-items:center;justify-content:center;margin:0 auto .8rem;}
.confirm-text{font-size:.8rem;color:var(--muted);margin-bottom:.9rem;}

/* ── PROFILE PAGE ─────────────────────────────────────────────────────────── */
.profile-grid{display:grid;grid-template-columns:280px 1fr;gap:1.4rem;align-items:start;}
.profile-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.5rem;display:flex;flex-direction:column;align-items:center;gap:1rem;}
.profile-avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#2563EB,#7C3AED);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:1.5rem;font-weight:700;color:#fff;}
.profile-name{font-family:var(--fh);font-size:1.1rem;font-weight:700;text-align:center;}
.profile-email{font-size:.75rem;color:var(--muted);font-family:var(--fm);text-align:center;}
.profile-role{display:inline-flex;align-items:center;gap:.4rem;background:var(--purple-light);color:var(--purple);border-radius:99px;padding:3px 12px;font-size:.72rem;font-weight:700;margin-top:.2rem;}
.profile-stats{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;width:100%;}
.ps-item{background:var(--bg3);border-radius:10px;padding:.7rem;text-align:center;}
.ps-val{font-family:var(--fh);font-size:1.2rem;font-weight:700;}
.ps-key{font-size:.65rem;color:var(--muted);margin-top:.1rem;}
.info-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.5rem;}
.info-card-title{font-family:var(--fh);font-size:.88rem;font-weight:700;margin-bottom:1rem;padding-bottom:.7rem;border-bottom:1px solid var(--border);}
.info-row{display:flex;align-items:center;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--border);}
.info-row:last-child{border-bottom:none;}
.info-key{font-size:.75rem;color:var(--muted);display:flex;align-items:center;gap:.5rem;}
.info-key svg{width:14px;height:14px;}
.info-val{font-size:.8rem;font-weight:500;}
.form-section{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.5rem;margin-top:1rem;}
.form-section-title{font-family:var(--fh);font-size:.88rem;font-weight:700;margin-bottom:1rem;padding-bottom:.7rem;border-bottom:1px solid var(--border);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.form-field{display:flex;flex-direction:column;gap:.35rem;}
.form-field.full{grid-column:1/-1;}
.form-label{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);}
.form-input{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.55rem .8rem;font-size:.83rem;color:var(--text);font-family:var(--fb);outline:none;transition:border .15s;width:100%;}
.form-input:focus{border-color:var(--blue);background:var(--surface);}
.form-foot{display:flex;justify-content:flex-end;margin-top:1rem;}
.btn-save{background:var(--blue);color:#fff;border:none;border-radius:8px;padding:.5rem 1.3rem;font-size:.82rem;font-weight:600;cursor:pointer;font-family:var(--fb);transition:background .15s;}
.btn-save:hover{background:#1D4ED8;}

/* ── PERMISSIONS PAGE ─────────────────────────────────────────────────────── */
.perm-admin-list{display:flex;flex-direction:column;gap:1rem;}
.perm-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.2rem 1.5rem;}
.perm-card-header{display:flex;align-items:center;gap:.8rem;margin-bottom:1rem;padding-bottom:.8rem;border-bottom:1px solid var(--border);}
.perm-av{width:38px;height:38px;border-radius:9px;background:linear-gradient(135deg,#3B82F6,#6366F1);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-weight:700;font-size:.75rem;color:#fff;flex-shrink:0;}
.perm-name{font-weight:600;font-size:.88rem;}
.perm-email{font-size:.72rem;color:var(--muted);font-family:var(--fm);}
.perm-module-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.6rem;}
.perm-toggle{display:flex;align-items:center;gap:.55rem;background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:.55rem .8rem;cursor:pointer;transition:all .15s;user-select:none;}
.perm-toggle:hover{border-color:var(--blue);}
.perm-toggle.active{background:var(--blue-light);border-color:var(--blue-border);}
.perm-toggle input{display:none;}
.perm-dot{width:14px;height:14px;border-radius:50%;border:2px solid var(--border);flex-shrink:0;transition:all .15s;}
.perm-toggle.active .perm-dot{background:var(--blue);border-color:var(--blue);}
.perm-toggle-label{font-size:.73rem;font-weight:500;}
.perm-module-icon{display:flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:5px;}
.perm-footer{margin-top:1rem;display:flex;justify-content:flex-end;}
.btn-perm-save{background:var(--blue);color:#fff;border:none;border-radius:8px;padding:.45rem 1.2rem;font-size:.78rem;font-weight:600;cursor:pointer;font-family:var(--fb);}
.btn-perm-save:hover{background:#1D4ED8;}

/* ── STATISTICS ───────────────────────────────────────────────────────────── */
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.2rem;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.3rem;}
.stat-card-title{font-size:.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.8rem;}
.stat-bar-row{display:flex;flex-direction:column;gap:.6rem;}
.stat-bar-item{display:flex;flex-direction:column;gap:.25rem;}
.stat-bar-label{display:flex;justify-content:space-between;font-size:.72rem;}
.stat-bar-label span:first-child{color:var(--muted);}
.stat-bar-label span:last-child{font-weight:600;}
.stat-bar-track{height:6px;background:var(--bg3);border-radius:99px;overflow:hidden;}
.stat-bar-fill{height:100%;border-radius:99px;transition:width .6s ease;}
.big-number{font-family:var(--fh);font-size:2.2rem;font-weight:800;line-height:1;}
.big-number-sub{font-size:.75rem;color:var(--muted);margin-top:.3rem;}
.trend-up{color:var(--green);font-size:.72rem;font-weight:600;}
.trend-pill{display:inline-flex;align-items:center;gap:.3rem;background:var(--green-light);color:var(--green);border-radius:99px;padding:2px 10px;font-size:.68rem;font-weight:600;}

/* ── USER ROW ─────────────────────────────────────────────────────────────── */
.user-row.detail-active td{background:var(--blue-light);}
.perm-toggle-v2{transition:all .15s; cursor:pointer;}
.perm-toggle-v2:hover{border-color:var(--blue) !important; background:var(--bg3) !important;}
.perm-toggle-v2.active{background:var(--blue-light) !important; border-color:var(--blue) !important;}
.perm-toggle-v2 .switch{position:relative; display:inline-block; width:34px; height:18px; background-color:var(--border2); border-radius:20px; transition:.2s; flex-shrink:0;}
.perm-toggle-v2 .switch:before{position:absolute; content:""; height:14px; width:14px; left:2px; bottom:2px; background-color:white; transition:.2s; border-radius:50%;}
.perm-toggle-v2.active .switch{background-color:var(--blue);}
.perm-toggle-v2.active .switch:before{transform:translateX(16px);}
</style>
</head>
<body>

<!-- ═══════════════════════════════ SIDEBAR ══════════════════════════════════ -->
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">ADMIN</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av"><?= $adminInitials ?></div>
    <div>
      <div class="sb-aname"><?= htmlspecialchars(Session::get('user_nom').' '.Session::get('user_prenom')) ?></div>
      <div class="sb-arole"><?= Session::get('role') ?></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Principal</div>
    <a href="?page=utilisateurs&filtre=tous" class="nav-item <?= $page==='utilisateurs'?'active':'' ?> <?= !in_array('utilisateurs',$myModules)?'disabled':'' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Utilisateurs
      <?php if($stats['kyc']>0): ?><span class="nav-badge"><?= $stats['kyc'] ?></span><?php endif; ?>
    </a>
    <a href="?page=statistiques" class="nav-item <?= $page==='statistiques'?'active':'' ?> <?= !in_array('statistiques',$myModules)?'disabled':'' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Statistiques
    </a>
    <?php foreach(['comptes','actions','credit','demande_chequier','dons_collectes'] as $mk): ?>
    <a href="#" class="nav-item <?= !in_array($mk,$myModules)?'disabled':'' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $ALL_MODULES[$mk]['icon'] ?></svg>
      <?= $ALL_MODULES[$mk]['label'] ?>
    </a>
    <?php endforeach; ?>

    <div class="nav-section">Compte</div>
    <a href="?page=profil" class="nav-item <?= $page==='profil'?'active':'' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon Profil
    </a>
    <?php if($currentUserRole==='SUPER_ADMIN'): ?>
    <a href="?page=permissions" class="nav-item <?= $page==='permissions'?'active':'' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Permissions
    </a>
    <?php endif; ?>
  </nav>
  <div class="sb-footer">
    <div class="sb-status"><div class="status-dot"></div>Système opérationnel</div>
    <a href="../../controller/AuthController.php?action=logout" style="display:flex;align-items:center;gap:.5rem;font-size:.72rem;color:#475569;text-decoration:none;margin-top:.6rem;">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Déconnexion
    </a>
  </div>
</div>

<!-- ═══════════════════════════════ MAIN ═════════════════════════════════════ -->
<div class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <div class="tb-left">
      <?php
      $titles = ['dashboard'=>'Tableau de bord','utilisateurs'=>'Gestion Utilisateurs','profil'=>'Mon Profil','permissions'=>'Gestion des Permissions','statistiques'=>'Statistiques'];
      ?>
      <div class="page-title"><?= $titles[$page] ?? 'Backoffice' ?></div>
      <div class="breadcrumb">LegalFin / <?= $titles[$page] ?? '' ?></div>
    </div>
    <div class="tb-right">
      <?php if($page==='utilisateurs'): ?>
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="search-input" placeholder="Rechercher..." oninput="filterTable()">
      </div>
      <button class="btn-primary" onclick="document.getElementById('m-add').classList.add('open')">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Ajouter
      </button>
      <?php endif; ?>
      
      <!-- Notifications -->
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
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════ PAGE: DASHBOARD ══════════════════════════════ -->
    <?php if($page==='dashboard'): ?>

    <!-- KPI Row -->
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

    <!-- Module Grid -->
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

    <!-- ══════════════════════ PAGE: UTILISATEURS ═══════════════════════════ -->
    <?php elseif($page==='utilisateurs' && in_array('utilisateurs',$myModules)): ?>
    <div class="two-col-layout">
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des utilisateurs (<?= count($users) ?>)</div>
          <div class="filters">
            <?php foreach(['tous'=>'Tous','clients'=>'Clients','admins'=>'Admins','association'=>'Association','bloques'=>'Bloqués','kyc_attente'=>'KYC attente'] as $k=>$l): ?>
            <a href="?page=utilisateurs&filtre=<?= $k ?>" class="filter-btn <?= $filtre===$k?'active':'' ?>"><?= $l ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div style="overflow-x:auto;">
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

        <!-- AML SCAN SECTION -->
        <div style="background:var(--bg2);padding:1rem;border-radius:12px;border:1px solid var(--border)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem">
            <div class="dp-section" style="margin:0">Score Anti-Fraude (AML)</div>
            <form method="POST" action="../../controller/UtilisateurController.php" style="margin:0">
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

        <!-- OCR SCAN SECTION -->
        <div style="background:var(--bg2);padding:1rem;border-radius:12px;border:1px solid var(--border);margin-top:1rem">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem">
            <div class="dp-section" style="margin:0">Analyse OCR (Document)</div>
            <form method="POST" action="../../controller/UtilisateurController.php" style="margin:0">
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

    <!-- ══════════════════════ PAGE: PROFIL ════════════════════════════════ -->
    <?php elseif($page==='profil'): ?>
    <div class="profile-grid">
      <!-- Left card -->
      <div>
        <div class="profile-card">
          <div class="profile-avatar"><?= $adminInitials ?></div>
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

      <!-- Right: edit forms -->
      <div>
        <div class="form-section">
          <div class="form-section-title">Modifier mes informations</div>
          <form method="POST" action="../../controller/UtilisateurController.php">
            <input type="hidden" name="action" value="admin_edit">
            <input type="hidden" name="id" value="<?= $currentUserId ?>">
            <div class="form-grid">
              <div class="form-field"><label class="form-label">Nom</label><input class="form-input" type="text" name="nom" value="<?= htmlspecialchars($currentUser['nom']??'') ?>" required></div>
              <div class="form-field"><label class="form-label">Prénom</label><input class="form-input" type="text" name="prenom" value="<?= htmlspecialchars($currentUser['prenom']??'') ?>" required></div>
              <div class="form-field"><label class="form-label">Téléphone</label><input class="form-input" type="tel" name="numTel" value="<?= htmlspecialchars($currentUser['numTel']??'') ?>" required></div>
              <div class="form-field"><label class="form-label">Rôle</label><input class="form-input" type="text" value="<?= $currentUserRole ?>" disabled style="opacity:.5"></div>
              <div class="form-field full"><label class="form-label">Adresse</label><textarea class="form-input" name="adresse" rows="2" style="resize:none" required><?= htmlspecialchars($currentUser['adresse']??'') ?></textarea></div>
            </div>
            <div class="form-foot">
              <button type="submit" class="btn-save">Enregistrer les modifications</button>
            </div>
          </form>
        </div>

        <div class="form-section">
          <div class="form-section-title">Changer mon mot de passe</div>
          <form method="POST" action="../../controller/UtilisateurController.php">
            <input type="hidden" name="action" value="admin_reset_pwd">
            <input type="hidden" name="id" value="<?= $currentUserId ?>">
            <div class="form-grid">
              <div class="form-field full"><label class="form-label">Nouveau mot de passe</label><input class="form-input" type="password" name="new_mdp" minlength="8" required placeholder="Min. 8 caractères, 1 majuscule, 1 chiffre"></div>
            </div>
            <div class="form-foot">
              <button type="submit" class="btn-save" style="background:var(--purple)">Changer le mot de passe</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ══════════════════════ PAGE: PERMISSIONS ═══════════════════════════ -->
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
        <div style="overflow-x:auto;">
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

    <!-- ══════════════════════ PAGE: STATISTIQUES ══════════════════════════ -->
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

    <!-- Top KPIs -->
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
      <!-- Répartition par rôle (Chart) -->
      <div class="stat-card">
        <div class="stat-card-title">Répartition par rôle</div>
        <div style="position:relative;height:240px;width:100%">
            <canvas id="roleChart"></canvas>
        </div>
      </div>

      <!-- Statut KYC (Chart) -->
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

      <!-- Statut des comptes (Chart) -->
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
      document.addEventListener("DOMContentLoaded", function() {
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
      });
    </script>

    <!-- Recent activity table -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="table-toolbar-title">Inscriptions récentes (10 derniers)</div>
      </div>
      <table>
        <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>KYC</th><th>Statut</th><th>Date inscription</th></tr></thead>
        <tbody>
        <?php
          $recent = \config::getConnexion()->query("SELECT * FROM utilisateur ORDER BY date_inscription DESC LIMIT 10")->fetchAll();
          foreach($recent as $r): $ri=initials($r['nom'],$r['prenom']);
        ?>
        <tr>
          <td><div style="display:flex;align-items:center;gap:.6rem"><div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2563EB,#0D9488);display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;color:#fff"><?= $ri ?></div><?= htmlspecialchars($r['nom'].' '.$r['prenom']) ?></div></td>
          <td><span class="td-mono"><?= htmlspecialchars($r['email']) ?></span></td>
          <td><span class="<?= $r['role']==='CLIENT'?'r-client':'r-admin' ?>"><?= $r['role'] ?></span></td>
          <td><span class="<?= badge_kyc($r['status_kyc']) ?>"><?= $r['status_kyc'] ?></span></td>
          <td><span class="badge <?= badge_status($r['status']) ?>"><span class="badge-dot" style="background:<?= badge_dot($r['status']) ?>"></span><?= $r['status'] ?></span></td>
          <td><span class="td-mono"><?= date('d/m/Y H:i',strtotime($r['date_inscription'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php else: ?>
    <!-- Access denied fallback -->
    <div style="text-align:center;padding:4rem;color:var(--muted)">
      <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 1rem;display:block;opacity:.4"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <div style="font-family:var(--fh);font-size:1.1rem;font-weight:700;margin-bottom:.5rem">Accès refusé</div>
      <div style="font-size:.82rem">Vous n'avez pas les permissions pour accéder à ce module.</div>
      <a href="?page=utilisateurs" style="display:inline-block;margin-top:1rem;color:var(--blue);font-size:.82rem;">← Retour aux utilisateurs</a>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ═════════════════════════════ MODALS ═════════════════════════════════════ -->
<!-- Add User -->
<div class="modal-overlay" id="m-add" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-add').classList.remove('open')">✕</button>
    <div class="modal-title">Ajouter un utilisateur</div>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_add">
      <div class="mg2">
        <div class="mfield"><label class="mlabel">Nom *</label><input class="minput" type="text" name="nom" required></div>
        <div class="mfield"><label class="mlabel">Prénom *</label><input class="minput" type="text" name="prenom" required></div>
        <div class="mfield mfull"><label class="mlabel">Email *</label><input class="minput" type="email" name="email" required></div>
        <div class="mfield"><label class="mlabel">Mot de passe *</label><input class="minput" type="password" name="mdp" required minlength="8"></div>
        <div class="mfield"><label class="mlabel">Téléphone *</label><input class="minput" type="tel" name="numTel" required></div>
        <div class="mfield"><label class="mlabel">Date de naissance *</label><input class="minput" type="date" name="date_naissance" max="2006-12-31" required></div>
        <div class="mfield"><label class="mlabel">CIN *</label><input class="minput" type="text" name="cin" maxlength="8" pattern="\d{8}" required placeholder="8 chiffres"></div>
        <div class="mfield"><label class="mlabel">Rôle</label>
          <select class="mselect" name="role"><option value="CLIENT">CLIENT</option><option value="ADMIN">ADMIN</option></select>
        </div>
        <div class="mfield mfull"><label class="mlabel">Adresse *</label><textarea class="minput" name="adresse" rows="2" style="resize:none" required></textarea></div>
      </div>
      <div class="mfoot"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-add').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save">Ajouter</button></div>
    </form>
  </div>
</div>

<!-- Edit User -->
<div class="modal-overlay" id="m-edit" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-edit').classList.remove('open')">✕</button>
    <div class="modal-title">Modifier l'utilisateur</div>
    <form method="POST" action="../../controller/UtilisateurController.php" id="edit-form">
      <input type="hidden" name="action" value="admin_edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="mg2">
        <div class="mfield"><label class="mlabel">Nom *</label><input class="minput" type="text" name="nom" id="edit-nom" required></div>
        <div class="mfield"><label class="mlabel">Prénom *</label><input class="minput" type="text" name="prenom" id="edit-prenom" required></div>
        <div class="mfield"><label class="mlabel">Téléphone *</label><input class="minput" type="tel" name="numTel" id="edit-numTel" required></div>
        <!-- Role is locked: passed as hidden field, not changeable -->
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
        <div class="mfield mfull"><label class="mlabel">Adresse *</label><textarea class="minput" name="adresse" id="edit-adresse" rows="2" style="resize:none" required></textarea></div>
      </div>
      <div class="mfoot"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-edit').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save">Sauvegarder</button></div>
    </form>
  </div>
</div>

<!-- Reset Password -->
<div class="modal-overlay" id="m-resetpwd" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:380px">
    <button class="modal-close" onclick="document.getElementById('m-resetpwd').classList.remove('open')">✕</button>
    <div class="modal-title">Réinitialiser le mot de passe</div>
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:.9rem" id="reset-name-label"></p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_reset_pwd">
      <input type="hidden" name="id" id="reset-id">
      <div class="mfield" style="margin-bottom:.8rem"><label class="mlabel">Nouveau mot de passe</label><input class="minput" type="text" name="new_mdp" value="Nexa@2025" required minlength="8"></div>
      <div class="mfoot"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-resetpwd').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save">Réinitialiser</button></div>
    </form>
  </div>
</div>

<!-- KYC Validate -->
<div class="modal-overlay" id="m-kyc" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-kyc').classList.remove('open')">✕</button>
    <div style="width:48px;height:48px;border-radius:50%;background:var(--green-light);display:flex;align-items:center;justify-content:center;margin:0 auto .8rem"><svg width="22" height="22" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></div>
    <div class="modal-title" style="text-align:center">Valider le KYC</div>
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:.9rem">Confirmer la validation KYC et AML de cet utilisateur ?</p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="valider_kyc">
      <input type="hidden" name="id" id="kyc-id">
      <input type="hidden" name="role" id="kyc-role">
      <div class="mfoot" style="justify-content:center"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-kyc').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-save" style="background:var(--green)">Valider KYC</button></div>
    </form>
  </div>
</div>

<!-- Block -->
<div class="modal-overlay" id="m-bloquer" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-bloquer').classList.remove('open')">✕</button>
    <div class="confirm-icon"><svg width="22" height="22" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
    <div class="modal-title" style="text-align:center">Bloquer le compte</div>
    <p class="confirm-text">Cette action suspendra l'accès de l'utilisateur.</p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="bloquer">
      <input type="hidden" name="id"   id="bloq-id">
      <input type="hidden" name="kyc"  id="bloq-kyc">
      <input type="hidden" name="aml"  id="bloq-aml">
      <input type="hidden" name="role" id="bloq-role">
      <div class="mfoot" style="justify-content:center"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-bloquer').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-danger">Bloquer</button></div>
    </form>
  </div>
</div>

<!-- Delete -->
<div class="modal-overlay" id="m-del" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-del').classList.remove('open')">✕</button>
    <div class="confirm-icon"><svg width="22" height="22" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg></div>
    <div class="modal-title" style="text-align:center">Confirmer la suppression</div>
    <p class="confirm-text">Supprimer <strong id="del-name"></strong> ? Action irréversible.</p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_delete">
      <input type="hidden" name="id" id="del-id">
      <div class="mfoot" style="justify-content:center"><button type="button" class="mbtn-cancel" onclick="document.getElementById('m-del').classList.remove('open')">Annuler</button><button type="submit" class="mbtn-danger">Supprimer</button></div>
    </form>
  </div>
</div>

<!-- Manage Permissions -->
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
// Permission helpers
function openPermModal(id, perms, name) {
  document.getElementById('perm-target-id').value = id;
  document.getElementById('perm-admin-name').textContent = name;
  
  // Uncheck all
  document.querySelectorAll('#form-perm .perm-toggle').forEach(function(lbl) {
    lbl.classList.remove('active');
    lbl.querySelector('input[type=checkbox]').checked = false;
  });
  
  // Check the ones in perms
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

// Close notification dropdown when clicking outside
document.addEventListener('click', function(e) {
  var wrapper = document.getElementById('notif-wrapper');
  var dropdown = document.getElementById('notif-dropdown');
  if (wrapper && dropdown && dropdown.classList.contains('show')) {
    if (!wrapper.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  }
});
</script>
</body>
</html>
