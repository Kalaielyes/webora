<?php
// =============================================================
//  view/backoffice/backoffice_utilisateur.php — NexaBank
//  CRUD Admin complet avec design backoffice (CSS light)
// =============================================================
require_once __DIR__ . '/../../model/Session.php';
require_once __DIR__ . '/../../model/Utilisateur.php';
Session::requireAdmin('../FrontOffice/login.php');

$m      = new Utilisateur();
$filtre = $_GET['filtre'] ?? 'tous';
$users  = $m->findAll($filtre);
$stats  = $m->getStats();
$flash  = Session::getFlash();

// Utilisateur a afficher dans le panneau detail
$detailId = (int)($_GET['detail'] ?? ($users[0]['id'] ?? 0));
$detail   = $detailId ? $m->findById($detailId) : ($users[0] ?? null);

$adminInitials = strtoupper(mb_substr(Session::get('user_nom'),0,1).mb_substr(Session::get('user_prenom'),0,1));

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
<title>NexaBank Admin - Gestion Utilisateurs</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Utilisateur.css">
<style>
.flash-bar{border-radius:8px;padding:.6rem 1rem;font-size:.78rem;display:flex;align-items:center;gap:.6rem;margin-bottom:.8rem;}
.flash-bar svg{width:14px;height:14px;flex-shrink:0;}
.flash-success{background:var(--green-light);border:1px solid rgba(22,163,74,.2);color:var(--green);}
.flash-error{background:var(--rose-light);border:1px solid rgba(220,38,38,.2);color:var(--rose);}
/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:200;display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--surface);border:1px solid var(--border2);border-radius:12px;padding:1.6rem;width:100%;max-width:520px;position:relative;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.15);}
.modal-title{font-family:var(--fh);font-size:.96rem;font-weight:700;margin-bottom:1.1rem;color:var(--text);}
.modal-close{position:absolute;top:.9rem;right:.9rem;background:none;border:1px solid var(--border);border-radius:6px;color:var(--muted);cursor:pointer;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:.9rem;transition:all .15s;}
.modal-close:hover{border-color:var(--rose);color:var(--rose);}
.mg{display:grid;gap:.7rem;}
.mg2{grid-template-columns:1fr 1fr;}
.mfull{grid-column:span 2;}
.mfield{display:flex;flex-direction:column;gap:.3rem;}
.mlabel{font-size:.66rem;font-weight:600;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);}
.minput,.mselect{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.5rem .8rem;font-size:.82rem;color:var(--text);font-family:var(--fb);outline:none;width:100%;transition:border .15s;}
.minput:focus,.mselect:focus{border-color:var(--blue);}
.mfoot{display:flex;gap:.6rem;justify-content:flex-end;margin-top:1rem;padding-top:.9rem;border-top:1px solid var(--border);}
.mbtn-cancel{background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:.4rem .9rem;font-size:.78rem;color:var(--muted);cursor:pointer;font-family:var(--fb);}
.mbtn-save{background:var(--blue);color:#fff;border:none;border-radius:7px;padding:.4rem 1rem;font-size:.78rem;font-weight:500;cursor:pointer;font-family:var(--fb);}
.mbtn-danger{background:var(--rose);color:#fff;border:none;border-radius:7px;padding:.4rem 1rem;font-size:.78rem;font-weight:500;cursor:pointer;font-family:var(--fb);}
.confirm-icon{width:48px;height:48px;border-radius:50%;background:var(--rose-light);display:flex;align-items:center;justify-content:center;margin:0 auto .8rem;}
.confirm-icon svg{width:22px;height:22px;stroke:var(--rose);}
.confirm-text{text-align:center;font-size:.84rem;color:var(--muted);margin-bottom:.4rem;}
.confirm-text strong{color:var(--text);}
/* Detail active row */
tr.detail-active td{background:var(--blue-light)!important;}
/* Search highlight */
.hl{background:rgba(37,99,235,.12);border-radius:2px;}

/* Animations */
.modal-overlay{display:none;opacity:0;transition:opacity .25s ease;}
.modal-overlay.open{display:flex;opacity:1;}
.modal{transform:translateY(-14px);opacity:0;transition:transform .25s ease,opacity .25s ease;}
.modal-overlay.open .modal{transform:translateY(0);opacity:1;}
.btn-primary, .act-btn, .mbtn-save, .mbtn-cancel, .dp-action-btn, .filter-btn{transition:transform .2s ease,background-color .2s ease,border-color .2s ease,color .2s ease;}
.btn-primary:hover, .act-btn:hover, .mbtn-save:hover, .mbtn-cancel:hover, .dp-action-btn:hover, .filter-btn:hover{transform:translateY(-1px);}
.kpi, .table-card, .detail-panel{transition:transform .2s ease,box-shadow .2s ease,opacity .2s ease;}
.kpi:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(15,23,42,.08);}
.user-row{transition:background .2s ease,transform .2s ease,opacity .2s ease;}
.user-row:hover{transform:translateX(2px);}
.detail-panel{opacity:0;animation:slideIn .28s ease forwards;}
@keyframes slideIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
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
    <div class="nav-section">Tableau de bord</div>
    <div class="nav-section" style="margin-top:.4rem">Gestion</div>
    <a class="nav-item active" href="backoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Utilisateurs <span class="nav-badge"><?= $stats['kyc'] ?></span>
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      KYC / AML <span class="nav-badge"><?= $stats['kyc'] ?></span>
    </a>
    <div class="nav-section" style="margin-top:.4rem">Parametres</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      Configuration
    </a>
    <a class="nav-item" href="../FrontOffice/frontoffice_utilisateur.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon profil
    </a>
  </nav>
  <div class="sb-footer" style="display:flex;flex-direction:column;gap:.4rem">
    <div class="sb-status"><span class="status-dot"></span> Systeme operationnel</div>
    <a href="../../controller/AuthController.php?action=logout" style="font-size:.68rem;color:#94A3B8;text-decoration:none">Deconnexion</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Utilisateurs</div>
      <span style="color:var(--muted2)">·</span>
      <div class="breadcrumb">Admin / Utilisateurs</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" id="search-input" placeholder="Chercher..." oninput="filterTable()">
      </div>
      <button class="btn-primary" onclick="document.getElementById('m-add').classList.add('open')">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        Nouvel utilisateur
      </button>
    </div>
  </header>

  <div class="content">

    <?php if($flash): ?>
    <div class="flash-bar flash-<?= $flash['type'] ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $flash['type']==='success'?'<polyline points="20 6 9 17 4 12"/>':'<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' ?></svg>
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)"><svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['total'] ?></div><div class="kpi-label">Total utilisateurs</div><div class="kpi-sub" style="color:var(--green)">+<?= $stats['mois'] ?> ce mois</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)"><svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['actifs'] ?></div><div class="kpi-label">Clients actifs</div><div class="kpi-sub" style="color:var(--green)"><?= $stats['total']?round($stats['actifs']/$stats['total']*100).'%':'0%' ?> du total</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)"><svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg></div>
        <div class="kpi-data"><div class="kpi-val" style="color:var(--amber)"><?= $stats['kyc'] ?></div><div class="kpi-label">KYC en attente</div><div class="kpi-sub" style="color:var(--amber)">A valider</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--rose-light)"><svg width="18" height="18" fill="none" stroke="var(--rose)" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
        <div class="kpi-data"><div class="kpi-val" style="color:var(--rose)"><?= $stats['bloques'] ?></div><div class="kpi-label">Comptes bloques</div><div class="kpi-sub" style="color:var(--rose)">Risque detecte</div></div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--purple-light)"><svg width="18" height="18" fill="none" stroke="var(--purple)" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="kpi-data"><div class="kpi-val"><?= $stats['admins'] ?></div><div class="kpi-label">Administrateurs</div><div class="kpi-sub" style="color:var(--muted)">3 niveaux d acces</div></div>
      </div>
    </div>

    <!-- TABLE + DETAIL -->
    <div class="two-col-layout">

      <!-- TABLE -->
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des utilisateurs (<?= count($users) ?>)</div>
          <div class="filters">
            <?php foreach(['tous'=>'Tous','clients'=>'Clients','admins'=>'Admins','bloques'=>'Bloques','kyc_attente'=>'KYC attente'] as $k=>$l): ?>
            <a href="?filtre=<?= $k ?>" class="filter-btn <?= $filtre===$k?'active':'' ?>"><?= $l ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <table id="user-table">
          <thead><tr><th>Utilisateur</th><th>Email</th><th>Role</th><th>KYC</th><th>AML</th><th>Statut</th><th>Inscription</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($users as $u): ?>
          <?php $ini=initials($u['nom'],$u['prenom']); ?>
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
            <td>
              <div class="action-group">
                <button class="act-btn" title="Voir" onclick="showDetail(<?= $u['id'] ?>)"><svg width="30" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                <button class="act-btn" title="Modifier" onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                <?php if($u['id']!==(int)Session::get('user_id')): ?>
                <button class="act-btn danger" title="Supprimer" onclick="openDel(<?= $u['id'] ?>,'<?= htmlspecialchars($u['nom'].' '.$u['prenom']) ?>')"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- DETAIL PANEL -->
      <?php if($detail): ?>
      <?php $ini=initials($detail['nom'],$detail['prenom']); ?>
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
          <div class="dp-row"><span class="dp-key">Prenom</span><span class="dp-val"><?= htmlspecialchars($detail['prenom']) ?></span></div>
          <div class="dp-row"><span class="dp-key">Email</span><span class="dp-val-mono"><?= htmlspecialchars($detail['email']) ?></span></div>
          <div class="dp-row"><span class="dp-key">Telephone</span><span class="dp-val"><?= htmlspecialchars($detail['numTel']) ?></span></div>
          <div class="dp-row"><span class="dp-key">Naissance</span><span class="dp-val"><?= date('d/m/Y',strtotime($detail['date_naissance'])) ?></span></div>
          <div class="dp-row"><span class="dp-key">Adresse</span><span class="dp-val" style="font-size:.72rem;max-width:160px;text-align:right"><?= htmlspecialchars($detail['adresse']) ?></span></div>
        </div>
        <div>
          <div class="dp-section">Compte &amp; Statut</div>
          <div class="dp-row"><span class="dp-key">ID</span><span class="dp-val-mono">#NXB-<?= str_pad($detail['id'],5,'0',STR_PAD_LEFT) ?></span></div>
          <div class="dp-row"><span class="dp-key">KYC</span><span class="<?= badge_kyc($detail['status_kyc']) ?>"><?= $detail['status_kyc'] ?></span></div>
          <div class="dp-row"><span class="dp-key">AML</span><span class="<?= badge_kyc($detail['status_aml']) ?>"><?= $detail['status_aml'] ?></span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="badge <?= badge_status($detail['status']) ?>"><?= $detail['status'] ?></span></div>
          <div class="dp-row"><span class="dp-key">Inscription</span><span class="dp-val-mono"><?= date('d/m/Y',strtotime($detail['date_inscription'])) ?></span></div>
          <div class="dp-row"><span class="dp-key">Connexion</span><span class="dp-val-mono"><?= $detail['derniere_connexion'] ? date('d/m H:i',strtotime($detail['derniere_connexion'])) : 'N/A' ?></span></div>
        </div>
        <div class="dp-actions">
          <button class="dp-action-btn da-primary" onclick="openEdit(<?= htmlspecialchars(json_encode($detail)) ?>)">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Modifier
          </button>
          <button class="dp-action-btn da-green" onclick="openKyc(<?= $detail['id'] ?>,'<?= $detail['role'] ?>')">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
            Valider KYC
          </button>
          <button class="dp-action-btn da-warning" onclick="openResetPwd(<?= $detail['id'] ?>,'<?= htmlspecialchars($detail['nom'].' '.$detail['prenom']) ?>')">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            Reset MDP
          </button>
          <button class="dp-action-btn da-danger" onclick="openBloquer(<?= $detail['id'] ?>,'<?= $detail['status_kyc'] ?>','<?= $detail['status_aml'] ?>','<?= $detail['role'] ?>')">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Bloquer
          </button>
          <?php if($detail['id']!==(int)Session::get('user_id')): ?>
          <button class="dp-action-btn da-danger" style="grid-column:1/-1" onclick="openDel(<?= $detail['id'] ?>,'<?= htmlspecialchars($detail['nom'].' '.$detail['prenom']) ?>')">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
            Supprimer
          </button>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="detail-panel"><p style="color:var(--muted);font-size:.8rem;text-align:center;padding:2rem">Aucun utilisateur selectionne.</p></div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- ═══ MODAL AJOUTER ═══ -->
<div class="modal-overlay" id="m-add" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-add').classList.remove('open')">x</button>
    <div class="modal-title">Ajouter un utilisateur</div>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_add">
      <div class="mg mg2">
        <div class="mfield"><label class="mlabel">Nom *</label><input class="minput" type="text" name="nom" required></div>
        <div class="mfield"><label class="mlabel">Prenom *</label><input class="minput" type="text" name="prenom" required></div>
        <div class="mfield mfull"><label class="mlabel">Email *</label><input class="minput" type="email" name="email" required></div>
        <div class="mfield"><label class="mlabel">Mot de passe *</label><input class="minput" type="password" name="mdp" required minlength="8"></div>
        <div class="mfield"><label class="mlabel">Telephone *</label><input class="minput" type="tel" name="numTel" required></div>
        <div class="mfield"><label class="mlabel">Date de naissance *</label><input class="minput" type="date" name="date_naissance" max="2006-12-31" required></div>
        <div class="mfield"><label class="mlabel">CIN *</label><input class="minput" type="text" name="cin" maxlength="8" pattern="\d{8}" required placeholder="8 chiffres"></div>
        <div class="mfield"><label class="mlabel">Role</label>
          <select class="mselect" name="role">
            <option value="CLIENT">CLIENT</option><option value="ADMIN">ADMIN</option><option value="SUPER_ADMIN">SUPER_ADMIN</option>
          </select>
        </div>
        <div class="mfield mfull"><label class="mlabel">Adresse *</label><textarea class="minput" name="adresse" rows="2" style="resize:none" required></textarea></div>
      </div>
      <div class="mfoot">
        <button type="button" class="mbtn-cancel" onclick="document.getElementById('m-add').classList.remove('open')">Annuler</button>
        <button type="submit" class="mbtn-save">Ajouter</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL MODIFIER ═══ -->
<div class="modal-overlay" id="m-edit" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('m-edit').classList.remove('open')">x</button>
    <div class="modal-title">Modifier l utilisateur</div>
    <form method="POST" action="../../controller/UtilisateurController.php" id="edit-form">
      <input type="hidden" name="action" value="admin_edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="mg mg2">
        <div class="mfield"><label class="mlabel">Nom *</label><input class="minput" type="text" name="nom" id="edit-nom" required></div>
        <div class="mfield"><label class="mlabel">Prenom *</label><input class="minput" type="text" name="prenom" id="edit-prenom" required></div>
        <div class="mfield"><label class="mlabel">Telephone *</label><input class="minput" type="tel" name="numTel" id="edit-numTel" required></div>
        <div class="mfield"><label class="mlabel">Role</label>
          <select class="mselect" name="role" id="edit-role">
            <option value="CLIENT">CLIENT</option><option value="ADMIN">ADMIN</option><option value="SUPER_ADMIN">SUPER_ADMIN</option>
          </select>
        </div>
        <div class="mfield"><label class="mlabel">Statut</label>
          <select class="mselect" name="status" id="edit-status">
            <option value="ACTIF">ACTIF</option><option value="INACTIF">INACTIF</option><option value="SUSPENDU">SUSPENDU</option>
          </select>
        </div>
        <div class="mfield"><label class="mlabel">KYC</label>
          <select class="mselect" name="status_kyc" id="edit-kyc">
            <option value="EN_ATTENTE">EN_ATTENTE</option><option value="VERIFIE">VERIFIE</option><option value="REJETE">REJETE</option>
          </select>
        </div>
        <div class="mfield"><label class="mlabel">AML</label>
          <select class="mselect" name="status_aml" id="edit-aml">
            <option value="EN_ATTENTE">EN_ATTENTE</option><option value="CONFORME">CONFORME</option><option value="ALERTE">ALERTE</option>
          </select>
        </div>
        <div class="mfield mfull"><label class="mlabel">Adresse *</label><textarea class="minput" name="adresse" id="edit-adresse" rows="2" style="resize:none" required></textarea></div>
      </div>
      <div class="mfoot">
        <button type="button" class="mbtn-cancel" onclick="document.getElementById('m-edit').classList.remove('open')">Annuler</button>
        <button type="submit" class="mbtn-save">Sauvegarder</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL RESET MDP ═══ -->
<div class="modal-overlay" id="m-resetpwd" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:380px">
    <button class="modal-close" onclick="document.getElementById('m-resetpwd').classList.remove('open')">x</button>
    <div class="modal-title">Reinitialiser le mot de passe</div>
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:.9rem" id="reset-name-label"></p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_reset_pwd">
      <input type="hidden" name="id" id="reset-id">
      <div class="mfield" style="margin-bottom:.8rem"><label class="mlabel">Nouveau mot de passe</label><input class="minput" type="text" name="new_mdp" value="Nexa@2025" required minlength="8"></div>
      <div class="mfoot">
        <button type="button" class="mbtn-cancel" onclick="document.getElementById('m-resetpwd').classList.remove('open')">Annuler</button>
        <button type="submit" class="mbtn-save">Reinitialiser</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL VALIDER KYC ═══ -->
<div class="modal-overlay" id="m-kyc" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-kyc').classList.remove('open')">x</button>
    <div style="width:48px;height:48px;border-radius:50%;background:var(--green-light);display:flex;align-items:center;justify-content:center;margin:0 auto .8rem">
      <svg width="22" height="22" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <div class="modal-title" style="text-align:center">Valider le KYC</div>
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:.9rem">Confirmer la validation KYC et AML de cet utilisateur ?</p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="valider_kyc">
      <input type="hidden" name="id" id="kyc-id">
      <input type="hidden" name="role" id="kyc-role">
      <div class="mfoot" style="justify-content:center">
        <button type="button" class="mbtn-cancel" onclick="document.getElementById('m-kyc').classList.remove('open')">Annuler</button>
        <button type="submit" class="mbtn-save" style="background:var(--green)">Valider KYC</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL BLOQUER ═══ -->
<div class="modal-overlay" id="m-bloquer" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-bloquer').classList.remove('open')">x</button>
    <div class="confirm-icon"><svg width="22" height="22" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
    <div class="modal-title" style="text-align:center">Bloquer le compte</div>
    <p class="confirm-text">Cette action suspendra l acces de l utilisateur.</p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="bloquer">
      <input type="hidden" name="id"   id="bloq-id">
      <input type="hidden" name="kyc"  id="bloq-kyc">
      <input type="hidden" name="aml"  id="bloq-aml">
      <input type="hidden" name="role" id="bloq-role">
      <div class="mfoot" style="justify-content:center">
        <button type="button" class="mbtn-cancel" onclick="document.getElementById('m-bloquer').classList.remove('open')">Annuler</button>
        <button type="submit" class="mbtn-danger">Bloquer</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL SUPPRIMER ═══ -->
<div class="modal-overlay" id="m-del" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:360px;text-align:center">
    <button class="modal-close" onclick="document.getElementById('m-del').classList.remove('open')">x</button>
    <div class="confirm-icon"><svg width="22" height="22" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg></div>
    <div class="modal-title" style="text-align:center">Confirmer la suppression</div>
    <p class="confirm-text">Supprimer <strong id="del-name"></strong> ? Action irreversible.</p>
    <form method="POST" action="../../controller/UtilisateurController.php">
      <input type="hidden" name="action" value="admin_delete">
      <input type="hidden" name="id" id="del-id">
      <div class="mfoot" style="justify-content:center">
        <button type="button" class="mbtn-cancel" onclick="document.getElementById('m-del').classList.remove('open')">Annuler</button>
        <button type="submit" class="mbtn-danger">Supprimer</button>
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
  window.location.href = '?filtre=<?= urlencode($filtre) ?>&detail=' + id;
}
function filterTable() {
  var q = document.getElementById('search-input').value.toLowerCase();
  document.querySelectorAll('#user-table tbody tr').forEach(function(row) {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body></html>
