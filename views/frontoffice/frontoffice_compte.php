<?php
/**
 * frontoffice_compte.php — CLIENT VIEW
 */
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../controllers/CompteController.php';
require_once __DIR__ . '/../../controllers/CarteController.php';
require_once __DIR__ . '/../../controllers/ObjectifController.php';

Config::autoLogin();
$userId   = (int)$_SESSION['user']['id'];
$user     = $_SESSION['user'];
$initials = strtoupper(substr($user['prenom']??'',0,1).substr($user['nom']??'',0,1));
$comptes  = CompteController::findByUtilisateur($userId);

$selected = null;
if (!empty($_GET['id_compte'])) {
    foreach ($comptes as $c) { if ($c->getIdCompte()===(int)$_GET['id_compte']) { $selected=$c; break; } }
}
if (!$selected && !empty($comptes)) {
    foreach ($comptes as $c) { if ($c->getStatut()==='actif') { $selected=$c; break; } }
    if (!$selected) $selected = $comptes[0];
}
$cartes = $selected ? CarteController::findByCompte($selected->getIdCompte()) : [];

$pendingComptes = array_values(array_filter($comptes, fn($c)=>in_array($c->getStatut(),['en_attente','demande_cloture','demande_suppression','demande_activation_courant'])));
$pendingCartes  = [];
foreach ($comptes as $c) {
    foreach (CarteController::findByCompte($c->getIdCompte()) as $carte) {
        if (in_array($carte->getStatut(),['inactive','demande_cloture','demande_suppression','demande_reactivation'])) $pendingCartes[]=['carte'=>$carte,'compte'=>$c];
    }
}
$isKycVerifie   = (($user['status_kyc'] ?? '') === 'VERIFIE');
$showCompteForm = (!empty($_GET['form']) && $_GET['form']==='compte' && $isKycVerifie);
$showCarteForm  = (!empty($_GET['form']) && $_GET['form']==='carte' && $isKycVerifie);
$showAttente    = (!empty($_GET['tab'])  && $_GET['tab']==='attente');
$showVirementForm = (!empty($_GET['form']) && $_GET['form']==='virement' && $isKycVerifie);
$showObjectifs  = (!empty($_GET['tab'])  && $_GET['tab']==='objectifs');
$showHistorique = (!empty($_GET['tab'])  && $_GET['tab']==='historique');

function cvClass(string $style, string $statut=''): string {
    if ($statut==='bloquee') return 'cv-bloque';
    return match($style) { 'gold'=>'cv-gold','platinum'=>'cv-platinum','titanium'=>'cv-titanium',default=>'cv-standard' };
}
$pendingCount = count($pendingComptes)+count($pendingCartes);
function styleLabel(string $s): string {
    return match($s) { 'gold'=>'Gold','platinum'=>'Platinum','titanium'=>'Titanium',default=>'Classic' };
}
function badgeCompte(string $s): string {
    return match($s) {
        'actif' => '<span class="status-pill pill-green" style="font-size:.62rem">• Actif</span>',
        'en_attente' => '<span class="status-pill pill-amber" style="font-size:.62rem">• En attente</span>',
        'bloque' => '<span class="status-pill pill-red" style="font-size:.62rem">• Bloqué</span>',
        'demande_cloture' => '<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. clôture</span>',
        'demande_suppression' => '<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. supp.</span>',
        'demande_activation_courant' => '<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. activation</span>',
        'cloture' => '<span class="status-pill pill-grey" style="font-size:.62rem">• Clôturé</span>',
        default => '',
    };
}
function badgeCarte(string $s): string {
    return match($s) {
        'active'=>'<span class="status-pill pill-green" style="font-size:.62rem">• Active</span>',
        'inactive'=>'<span class="status-pill pill-grey" style="font-size:.62rem">• Inactive</span>',
        'bloquee'=>'<span class="status-pill pill-red" style="font-size:.62rem">• Bloquée</span>',
        'expiree'=>'<span class="status-pill pill-grey" style="font-size:.62rem">• Expirée</span>',
        'demande_cloture'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. supp.</span>',
        'demande_blocage'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. blocage</span>',
        'demande_reactivation'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. réactiv.</span>',
        'demande_suppression'=>'<span class="status-pill pill-amber" style="font-size:.62rem">• Dem. supp.</span>',
        default=>'',
    };
}
$pendingCount = count($pendingComptes)+count($pendingCartes);

// -- ERROR & DATA HANDLING FROM PHP POST --
$formErrors = $_SESSION['form_errors'] ?? [];
$formData   = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin — Mon espace</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/views/frontoffice/compte.css">
<script>
  // Apply theme BEFORE CSS renders to avoid flash and ensure correct colors
  (function() {
    var t = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
    var p = localStorage.getItem('privacy') === 'true';
    if(p) document.documentElement.classList.add('privacy-mode');
  })();
</script>
<script>
  window.CSRF_TOKEN = "<?= Security::getCsrfToken() ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- ══ LOADING OVERLAY ═══════════════════════════════ -->
<div id="loader-overlay">
  <div class="loader-spinner"></div>
  <div class="loader-text">LegalFin...</div>
</div>


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
  <?php
    $isCompteSection = (!$showCompteForm && !$showCarteForm && !$showAttente && !$showVirementForm && !$showObjectifs && !$showHistorique) || $showCompteForm || $showCarteForm;
    $isOperationSection = $showVirementForm || $showObjectifs || $showHistorique;
  ?>
  <nav class="sb-nav">
    <!-- ══ COMPTE DROPDOWN ══ -->
    <div class="nav-dropdown <?= $isCompteSection ? 'open' : '' ?>" id="dropdown-compte">
      <button class="nav-dropdown-toggle" onclick="toggleDropdown('dropdown-compte')">
        <div class="nav-dropdown-toggle-left">
          <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
          <span>Compte</span>
        </div>
        <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div class="nav-dropdown-menu">
        <a class="nav-sub-item <?= (!$showCompteForm&&!$showCarteForm&&!$showAttente&&!$showVirementForm&&!$showObjectifs)?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php">
          <span class="nav-sub-dot"></span>Mes comptes
        </a>
        <a class="nav-sub-item <?= $showCompteForm?'active':'' ?>" <?= $isKycVerifie ? 'href="'.APP_URL.'/views/frontoffice/frontoffice_compte.php?form=compte"' : 'style="opacity:0.5;cursor:not-allowed;" title="Vérification KYC requise" onclick="alert(\'Votre compte doit être vérifié (KYC) pour ouvrir un compte.\'); return false;"' ?>>
          <span class="nav-sub-dot"></span>Ouvrir un compte <?= !$isKycVerifie ? '🔒' : '' ?>
        </a>
        <a class="nav-sub-item <?= $showCarteForm?'active':'' ?>" <?= $isKycVerifie ? 'href="'.APP_URL.'/views/frontoffice/frontoffice_compte.php?form=carte'.($selected?'&id_compte='.$selected->getIdCompte():'').'"' : 'style="opacity:0.5;cursor:not-allowed;" title="Vérification KYC requise" onclick="alert(\'Votre compte doit être vérifié (KYC) pour demander une carte.\'); return false;"' ?>>
          <span class="nav-sub-dot"></span>Demander une carte <?= !$isKycVerifie ? '🔒' : '' ?>
        </a>
      </div>
    </div>

    <!-- ══ OPÉRATIONS DROPDOWN ══ -->
    <div class="nav-dropdown <?= $isOperationSection ? 'open' : '' ?>" id="dropdown-operations">
      <button class="nav-dropdown-toggle" onclick="toggleDropdown('dropdown-operations')">
        <div class="nav-dropdown-toggle-left">
          <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          <span>Opérations</span>
        </div>
        <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div class="nav-dropdown-menu">
        <a class="nav-sub-item <?= $showVirementForm?'active':'' ?>" <?= $isKycVerifie ? 'href="'.APP_URL.'/views/frontoffice/frontoffice_compte.php?form=virement"' : 'style="opacity:0.5;cursor:not-allowed;" title="Vérification KYC requise" onclick="alert(\'Votre compte doit être vérifié (KYC) pour effectuer un virement.\'); return false;"' ?>>
          <span class="nav-sub-dot"></span>Virement <?= !$isKycVerifie ? '🔒' : '' ?>
        </a>
        <a class="nav-sub-item <?= $showHistorique?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=historique">
          <span class="nav-sub-dot"></span>Historique & Export
        </a>
        <a class="nav-sub-item <?= $showObjectifs?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=objectifs">
          <span class="nav-sub-dot"></span>Objectifs
        </a>
      </div>
    </div>

    <?php if ($pendingCount>0): ?>
    <div class="nav-section" style="margin-top:.4rem">Suivi</div>
    <a class="nav-item <?= $showAttente?'active':'' ?>" href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=attente">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
      En attente
      <span style="margin-left:auto;background:rgba(245,158,11,.2);color:var(--amber);border-radius:99px;padding:1px 7px;font-size:.62rem;font-weight:600"><?= $pendingCount ?></span>
    </a>
    <?php endif; ?>
    <a class="nav-item" href="../backoffice/backoffice_compte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>backoffice
    </a>
  </nav>
  <div class="sb-footer">
    <?php if (($user['status_kyc']??'')==='VERIFIE'): ?>
      <div class="badge-kyc"><span class="dot-pulse"></span>Identité vérifiée</div>
    <?php else: ?>
      <div class="badge-kyc" style="background:rgba(245,158,11,.1);color:var(--amber)">KYC en cours</div>
    <?php endif; ?>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes comptes bancaires</div>
    <div class="topbar-right">
      
      <div class="notif" id="notif-bell" onclick="toggleNotifPanel(event)" title="Notifications">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <?php if ($pendingCount>0): ?><div class="notif-dot"></div><?php endif; ?>
      </div>
      
      <button class="theme-toggle" id="privacy-toggle" onclick="togglePrivacy()" title="Mode Incognito (Cacher les données)" style="background:var(--bg3); border:1px solid var(--border); color:var(--text); width:36px; height:36px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.2s;">
        <svg id="privacy-icon-off" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        <svg id="privacy-icon-on" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
      </button>

      <button class="theme-toggle" id="theme-toggle" onclick="toggleTheme()" title="Changer de thème" style="background:var(--bg3); border:1px solid var(--border); color:var(--text); width:36px; height:36px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.2s;">
        <svg id="theme-icon-sun" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
        <svg id="theme-icon-moon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
      </button>

    </div>
  </div>

  <div class="content">

    <!-- ══ DYNAMIC GREETING ════════════════════════════ -->
    <div class="greeting-section">
      <div class="greeting-text" id="greeting-display">Bonjour, <span><?= htmlspecialchars($user['prenom']??'Client') ?></span></div>
      <div class="greeting-time">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <span id="real-time-clock">00:00:00</span>
      </div>
    </div>


  <?php if ($showCompteForm): ?>
  <?php include __DIR__ . '/partialscomptes/form_compte.php'; ?>
  <?php elseif ($showCarteForm): ?>
  
  <?php include __DIR__ . '/partialscomptes/form_carte.php'; ?>

  <?php elseif ($showVirementForm): ?>
  <?php include __DIR__ . '/partialscomptes/virement.php'; ?>

  <?php elseif ($showObjectifs): ?>
  <?php include __DIR__ . '/partialscomptes/objectifs.php'; ?>

  <?php elseif ($showAttente): ?>
  <?php include __DIR__ . '/partialscomptes/en_attente.php'; ?>

  <?php elseif ($showHistorique): ?>
  <?php include __DIR__ . '/partialscomptes/historique.php'; ?>

  <?php elseif (empty($comptes)): ?>
  <?php include __DIR__ . '/partialscomptes/no_comptes.php'; ?>
  <?php else: ?>
  <!-- ══ MAIN ACCOUNT VIEW ══════════════════════════════ -->
  <?php include __DIR__ . '/partialscomptes/mes_comptes.php'; ?>

  <?php endif; // main view ?>
  </div>
</div>
<!-- ══ NOTIFICATION PANEL (fixed, body-level) ════════════ -->
<div class="notif-panel" id="notif-panel">
  <div class="np-header">
    <div class="np-title">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
      Notifications
    </div>
    <?php if ($pendingCount>0): ?>
    <span class="np-count"><?= $pendingCount ?></span>
    <?php endif; ?>
  </div>
  <div class="np-body">
  <?php if ($pendingCount===0): ?>
    <div class="np-empty">
      <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
      <div>Aucune notification</div>
    </div>
  <?php else: ?>
    <?php if (!empty($pendingComptes)): ?>
    <div class="np-section-label">Comptes</div>
    <?php foreach ($pendingComptes as $c): ?>
    <div class="np-item">
      <div class="np-item-icon np-icon-amber"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg></div>
      <div class="np-item-info">
        <div class="np-item-name"><?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> — <?= htmlspecialchars($c->getDevise()) ?></div>
        <div class="np-item-sub">IBAN: ···<span class="sensitive-data"><?= htmlspecialchars(substr($c->getIban(),-6)) ?></span></div>
      </div>
      <?= badgeCompte($c->getStatut()) ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($pendingCartes)): ?>
    <div class="np-section-label" style="margin-top:.6rem">Cartes</div>
    <?php foreach ($pendingCartes as $item): $carte=$item['carte']; $cpt=$item['compte']; ?>
    <div class="np-item">
      <div class="np-item-icon np-icon-blue"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></div>
      <div class="np-item-info">
        <div class="np-item-name"><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> <?= strtoupper($carte->getReseau()) ?></div>
        <div class="np-item-sub">···<span class="sensitive-data"><?= htmlspecialchars(substr($carte->getNumeroCarte(),-4)) ?></span> — <?= htmlspecialchars(ucfirst($cpt->getTypeCompte())) ?></div>
      </div>
      <?= badgeCarte($carte->getStatut()) ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
  </div>
  <?php if ($pendingCount>0): ?>
  <div class="np-footer">
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?tab=attente" onclick="closeNotifPanel()">Voir toutes les demandes →</a>
  </div>
  <?php endif; ?>
</div>

<!-- ══ CHATBOT WIDGET ══════════════════════════════ -->
<div class="cb-widget" id="cb-widget">

  <!-- Floating action button -->
  <button class="cb-fab" id="cb-fab" onclick="cbToggle()" title="Assistant LegalFin">
    <svg id="cb-icon-open" width="34" height="34" viewBox="0 0 100 100" overflow="visible" style="display:block">
      <path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />
      <polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />
      <g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">
         <rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
         <circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
      </g>
      <g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">
         <rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
         <circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
      </g>
      <g class="cb-robot-head-grp" style="transform-origin: 50px 46px">
        <rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />
        <path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>
        <rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
        <rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
        <path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />
        <circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />
        <rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />
        <g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">
          <rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
          <rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
        </g>
      </g>
    </svg>
    <svg id="cb-icon-close" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="display:none">
      <path d="M18 6L6 18M6 6l12 12"/>
    </svg>
    <?php if ($pendingCount>0): ?><div class="cb-fab-dot"></div><?php endif; ?>
  </button>

  <!-- Chat panel -->
  <div class="cb-panel" id="cb-panel" style="position:relative;">

    <!-- Background Robot Watermark -->
    <div style="position:absolute; inset:0; top:50px; display:flex; align-items:center; justify-content:center; opacity:0.1; pointer-events:none; z-index:0; overflow:hidden;">
      <svg id="cb-watermark-robot" class="cb-bot-watermark" width="260" height="260" viewBox="0 0 100 100" overflow="visible" style="transform:translateY(20px);">
        <path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />
        <polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />
        <g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">
           <rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
           <circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
        </g>
        <g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">
           <rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
           <circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
        </g>
        <g class="cb-robot-head-grp" style="transform-origin: 50px 46px">
          <rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />
          <path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>
          <rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
          <rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
          <path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />
          <circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />
          <rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />
          <g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">
            <rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
            <rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
          </g>
        </g>
      </svg>
    </div>

    <!-- Header -->
    <div class="cb-header" style="position:relative; z-index:1;">
      <div class="cb-header-left">
        <div class="cb-hav">
          <svg width="28" height="28" viewBox="0 0 100 100" overflow="visible">
            <path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />
            <polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />
            <g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">
               <rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
               <circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
            </g>
            <g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">
               <rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />
               <circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>
            </g>
            <g class="cb-robot-head-grp" style="transform-origin: 50px 46px">
              <rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />
              <path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>
              <rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
              <rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />
              <path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />
              <circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />
              <rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />
              <g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">
                <rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
                <rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />
              </g>
            </g>
          </svg>
        </div>
        <div>
          <div class="cb-htitle">Elyes AI</div>
          <div class="cb-hsub"><span class="cb-online-dot"></span> En ligne</div>
        </div>
      </div>
        <div class="cb-lang-selector" style="margin-right:4px;">
          <select id="cb-lang-select" onchange="cbChangeLang(this.value)" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #fff; font-size: 0.68rem; font-weight: 600; padding: 4px 8px; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
            <option value="fr-FR" style="background:#1a1d2b;">FR</option>
            <option value="en-US" style="background:#1a1d2b;">EN</option>
            <option value="ar-SA" style="background:#1a1d2b;">AR</option>
          </select>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
        <button id="cb-stop-btn" onclick="cbStopSpeech()" title="Arrêter de parler" style="background:none; border:none; cursor:pointer; color:#ef4444; display:none; padding:4px;">
           <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
        </button>
        <button id="cb-voice-cycle" onclick="cbCycleVoice()" title="Changer de voix" style="background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.7); display:flex; padding:4px;">
           <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg>
        </button>
        <button class="cb-voice-toggle" id="cb-voice-toggle" onclick="cbToggleVoice()" title="Activer/Désactiver la voix" style="background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.7); display:flex; padding:4px;">
           <svg width="16" height="16" id="cb-voice-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
        </button>
        <button class="cb-hclose" onclick="cbClose()">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
    </div>

    <!-- Messages -->
    <div class="cb-messages" id="cb-messages" style="position:relative; z-index:1;"></div>

    <!-- Quick buttons -->
    <div class="cb-quick">
      <button onclick="cbQuick('Mon solde')">💰 Solde</button>
      <button onclick="cbQuick('Mes cartes')">💳 Cartes</button>
      <button onclick="cbQuick('Mon IBAN')">🏦 IBAN</button>
      <button onclick="cbQuick('aide')">❓ Aide</button>
    </div>

    <!-- Input row -->
    <div class="cb-footer" style="gap:8px;">
      <button class="cb-mic-btn" id="cb-mic-btn" onclick="cbStartMic()" title="Parler" style="background:none; border:none; cursor:pointer; color:#4F8EF7; display:flex; padding:5px; transition: 0.2s;">
         <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2M12 19v4M8 23h8"/></svg>
      </button>
      <input type="text" id="cb-input" class="cb-input" placeholder="Posez votre question..." autocomplete="off"
             onkeydown="if(event.key==='Enter')cbSend()" style="flex:1;">
      <button class="cb-send" onclick="cbSend()">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
          <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>

  </div>
</div>

    <script>
    function flipCard(id){document.getElementById(id).classList.toggle('flipped');}

    // ── SIDEBAR DROPDOWN TOGGLE ──
    function toggleDropdown(id) {
        document.getElementById(id).classList.toggle('open');
    }
    
    // ── PRIVACY MODE (INCOGNITO) ──
    var isPrivacyActive = localStorage.getItem('privacy') === 'true';
    function updatePrivacyUI() {
        document.documentElement.classList.toggle('privacy-mode', isPrivacyActive);
        document.getElementById('privacy-icon-off').style.display = isPrivacyActive ? 'none' : 'block';
        document.getElementById('privacy-icon-on').style.display = isPrivacyActive ? 'block' : 'none';
        var btn = document.getElementById('privacy-toggle');
        if(btn) btn.style.color = isPrivacyActive ? 'var(--teal)' : 'var(--text)';
        if(btn) btn.style.borderColor = isPrivacyActive ? 'var(--teal)' : 'var(--border)';
    }
    function togglePrivacy() {
        isPrivacyActive = !isPrivacyActive;
        localStorage.setItem('privacy', isPrivacyActive);
        updatePrivacyUI();
    }
    // Apply privacy on load
    document.addEventListener('DOMContentLoaded', updatePrivacyUI);

    // ── LOADING (Conditional: show only if > 400ms) ──
    (function() {
        var loaderShown = false;
        var loaderDelay = setTimeout(function() {
            var loader = document.getElementById('loader-overlay');
            if(loader) {
                loader.style.display = 'flex';
                loaderShown = true;
                // Fade in
                setTimeout(() => loader.style.opacity = '1', 10);
            }
        }, 400);

        window.addEventListener('load', function() {
            clearTimeout(loaderDelay);
            if(loaderShown) {
                var loader = document.getElementById('loader-overlay');
                if(loader) {
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 400);
                }
            }
        });
    })();

    function updateGreeting() {
        var hour = new Date().getHours();
        var greeting = "Bonjour";
        if(hour >= 18) greeting = "Bonsoir";
        else if(hour >= 12) greeting = "Bon après-midi";
        
        var display = document.getElementById('greeting-display');
        if(display) {
            var name = display.querySelector('span').outerHTML;
            display.innerHTML = greeting + ", " + name;
        }
    }

    function updateClock() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        var el = document.getElementById('real-time-clock');
        if(el) el.textContent = `${h}:${m}:${s}`;
    }
    
    // ── COPY TO CLIPBOARD ──
    function copyToClipboard(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            var tooltip = btn.nextElementSibling;
            if(tooltip && tooltip.classList.contains('copy-tooltip')) {
                tooltip.classList.add('show');
                setTimeout(() => tooltip.classList.remove('show'), 2000);
            }
        });
    }

    setInterval(updateClock, 1000);
    updateClock();
    updateGreeting();

    function updateStyleHint(val){
        var v=parseFloat(val)||0, name='Classic';
        if(v>=1500) name='Titanium';
        else if(v>=1000) name='Platinum';
        else if(v>=500) name='Gold';
        var el=document.getElementById('style-hint-name');
        if(el) el.textContent=name;
    }
    // ── NOTIFICATION PANEL (сихed position via getBoundingClientRect) ──

    function toggleNotifPanel(e){
        e.stopPropagation();
        var panel=document.getElementById('notif-panel');
        var bell=document.getElementById('notif-bell');
        var open=panel.classList.toggle('np-open');
        bell.classList.toggle('np-bell-active',open);
        if(open){
            var rect=bell.getBoundingClientRect();
            panel.style.top=(rect.bottom+10)+'px';
            panel.style.right=(window.innerWidth-rect.right)+'px';
            panel.style.left='auto';
        }
    }
    function closeNotifPanel(){
        var p=document.getElementById('notif-panel'),b=document.getElementById('notif-bell');
        if(p) p.classList.remove('np-open');
        if(b) b.classList.remove('np-bell-active');
    }
    document.addEventListener('click',function(e){
        var panel=document.getElementById('notif-panel'),bell=document.getElementById('notif-bell');
        if(panel&&panel.classList.contains('np-open')&&!panel.contains(e.target)&&bell&&!bell.contains(e.target)) closeNotifPanel();
    });
    document.addEventListener('keydown',function(e){
        if(e.key==='Escape'){closeNotifPanel();cbClose();}
    });
    // ── CHATBOT ──────────────────────────────────────────
    var cbHistory=[],cbIsOpen=false;
    var cbUrl='<?= APP_URL ?>/controllers/ChatbotController.php';
    function cbToggle(){
        cbIsOpen=!cbIsOpen;
        var panel=document.getElementById('cb-panel');
        var iconO=document.getElementById('cb-icon-open');
        var iconC=document.getElementById('cb-icon-close');
        panel.classList.toggle('cb-open',cbIsOpen);
        iconO.style.display=cbIsOpen?'none':'block';
        iconC.style.display=cbIsOpen?'block':'none';
        
        if(cbIsOpen) {
            // Wake Up Sequence
            var watermarkLogo = document.querySelector('.cb-bot-watermark');
            var headerRobotHead = watermarkLogo ? watermarkLogo.querySelector('.cb-robot-head-grp') : null;
            var butterflyEl = document.getElementById('magic-butterfly');
            if(headerRobotHead) headerRobotHead.classList.add('is-sleeping');
            if(butterflyEl) butterflyEl.style.display = 'block';
            
            isWakingUp = true;
            isResting = false;
            
            setTimeout(() => {
                var pRect = panel.getBoundingClientRect();
                var waterRect = watermarkLogo.getBoundingClientRect();
                var targetX = (waterRect.left - pRect.left) + waterRect.width/2;
                var targetY = (waterRect.top - pRect.top) + waterRect.height/2 - 70;
                
                var startX = pRect.width + 250, startY = pRect.height + 200;
                bfX = startX; bfY = startY;
                var wakeAnimStart = performance.now();
                
                function wakeStep(time) {
                    var elapsed = time - wakeAnimStart;
                    var progress = Math.min(elapsed / 2200, 1); // Much slower: 2.2s
                    var t = 1 - Math.pow(1 - progress, 4);
                    var curve = Math.sin(progress * Math.PI) * 120;
                    
                    mouseX = startX + (targetX - startX) * t + curve;
                    mouseY = startY + (targetY - startY) * t - Math.abs(curve) * 0.5;
                    
                    if(progress < 1) {
                        requestAnimationFrame(wakeStep);
                    } else {
                        // HIT! Surprise!
                        if(headerRobotHead) {
                            headerRobotHead.classList.remove('is-sleeping');
                            headerRobotHead.classList.add('is-surprised');
                        }
                        
                        // Butterfly fly away sequence
                        var awayStart = performance.now();
                        var hitX = mouseX, hitY = mouseY;
                        var exitX = -200, exitY = -200;
                        
                        function flyAway(time2) {
                            var p2 = Math.min((time2 - awayStart) / 1800, 1); // Much slower: 1.8s
                            var t2 = p2 * p2;
                            bfX = hitX + (exitX - hitX) * t2;
                            bfY = hitY + (exitY - hitY) * t2;
                            
                            if(p2 < 1) {
                                requestAnimationFrame(flyAway);
                            } else {
                                if(butterflyEl) butterflyEl.style.display = 'none';
                                isWakingUp = false;
                                
                                 // GREET NOW! Animation is finished.
                                 if(document.getElementById('cb-messages').children.length === 0) {
                                     var typing = cbTyping();
                                     setTimeout(function() {
                                         if(typing && typing.parentNode) typing.remove();
                                         cbBotMsg('Bonjour ! 👋 Je suis votre assistant bancaire **LegalFin**.\nTapez **aide** pour voir toutes les options.');
                                     }, 600);
                                 }
                            }
                        }
                        requestAnimationFrame(flyAway);
                        
                        setTimeout(() => { if(headerRobotHead) headerRobotHead.classList.remove('is-surprised'); }, 800);
                    }
                }
                requestAnimationFrame(wakeStep);
            }, 100);
        }

        // Message logic moved inside animation callback above
        if(cbIsOpen) setTimeout(()=>document.getElementById('cb-input').focus(),280);
    }
    function cbClose(){ if(cbIsOpen) cbToggle(); }
    function cbEscHtml(t){ return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function cbFormat(t){
        return t.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
                .replace(/\*(.+?)\*/g,'<em>$1</em>')
                .replace(/`([^`]+)`/g,'<code>$1</code>')
                .replace(/\n/g,'<br>');
    }
    var robotSvg = 
      '<svg width="100%" height="100%" viewBox="0 0 100 100" overflow="visible">' +
        '<path d="M 15 100 C 15 65, 85 65, 85 100" fill="#fff" opacity="0.9" />' +
        '<polygon points="45 80, 55 80, 52 100, 48 100" fill="#2DD4BF" />' +
        '<g class="cb-robot-hand-grp cb-robot-hand-left" style="transform-origin: 30px 80px">' +
           '<rect x="12" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />' +
           '<circle cx="21" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>' +
        '</g>' +
        '<g class="cb-robot-hand-grp cb-robot-hand-right" style="transform-origin: 70px 80px">' +
           '<rect x="70" y="65" width="18" height="14" rx="7" fill="#fff" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.3))" />' +
           '<circle cx="79" cy="72" r="3" fill="#2DD4BF" opacity="0.7"/>' +
        '</g>' +
        '<g class="cb-robot-head-grp" style="transform-origin: 50px 46px">' +
          '<rect x="20" y="22" width="60" height="48" rx="14" fill="#fff" />' +
          '<path d="M 16 42 A 38 38 0 0 1 84 42" fill="none" stroke="#13151E" stroke-width="3" stroke-linecap="round"/>' +
          '<rect x="13" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />' +
          '<rect x="79" y="37" width="8" height="15" rx="4" fill="#2DD4BF" />' +
          '<path d="M 79 49 Q 87 55, 68 62" fill="none" stroke="#13151E" stroke-width="2.5" stroke-linecap="round" />' +
          '<circle cx="68" cy="62" r="2.5" fill="#2DD4BF" />' +
          '<rect x="28" y="30" width="44" height="24" rx="6" fill="#13151E" />' +
          '<g class="cb-robot-eyes-grp" style="transition: transform 0.08s ease-out;">' +
            '<rect x="35" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />' +
            '<rect x="57" y="37" width="8" height="6" rx="3" fill="#4F8EF7" class="robot-eye" />' +
          '</g>' +
        '</g>' +
      '</svg>';

    function cbUserMsg(text){
        var m=document.getElementById('cb-messages');
        var d=document.createElement('div'); d.className='cb-msg cb-user';
        d.innerHTML='<span>'+cbEscHtml(text)+'</span>';
        m.appendChild(d); m.scrollTop=m.scrollHeight;
    }
    function cbBotMsg(text){
        var m=document.getElementById('cb-messages');
        var d=document.createElement('div'); d.className='cb-msg cb-bot';
        d.innerHTML='<div class="cb-av">'+robotSvg+'</div><span>'+cbFormat(text)+'</span>';
        m.appendChild(d); m.scrollTop=m.scrollHeight;
    }
    function cbTyping(){
        var m=document.getElementById('cb-messages');
        var d=document.createElement('div'); d.className='cb-msg cb-bot cb-typing-row'; d.id='cb-typing';
        d.innerHTML='<div class="cb-av cb-av-thinking">'+robotSvg+'</div><span class="cb-dots"><span></span><span></span><span></span></span>';
        m.appendChild(d); m.scrollTop=m.scrollHeight; return d;
    }
    function cbSend(){
        var inp=document.getElementById('cb-input');
        var msg=inp.value.trim(); if(!msg) return;
        inp.value='';
        cbUserMsg(msg);
        cbHistory.push({role:'user',content:msg});
        var typing=cbTyping();
        var formData = new URLSearchParams();
        formData.append('message', msg);
        formData.append('lang', currentLang);
        formData.append('history', JSON.stringify(cbHistory.slice(-6)));
        if (window.CSRF_TOKEN) formData.append('csrf_token', window.CSRF_TOKEN);

        fetch(cbUrl, {
            method: 'POST',
            body: formData
        })
        .then(r=>r.json())
        .then(data=>{
            typing.remove();
            var reply = data.reply || '⚠️ Erreur de réponse.';
            cbBotMsg(reply);
            cbHistory.push({role:'bot',content:reply});
        })
        .catch(()=>{ typing.remove(); cbBotMsg('❌ Impossible de contacter l\'assistant.'); });
    }
    function cbQuick(t){ document.getElementById('cb-input').value=t; cbSend(); }
    
    // Voice & Speech Logic
    var isVoiceEnabled = false;
    var currentLang = 'fr-FR';
    var recognition = null;
    var voices = [];
    var currentVoiceIdx = 0;

    function cbChangeLang(lang) {
        currentLang = lang;
        if (recognition) recognition.lang = lang;
        loadVoices(); // Reload voices for the new language
        
        // Update placeholder
        var ph = "Posez votre question...";
        if(lang === 'en-US') ph = "Ask your question...";
        if(lang === 'ar-SA') ph = "إسأل سؤالك...";
        document.getElementById('cb-input').placeholder = ph;
    }

    function loadVoices() {
        var all = window.speechSynthesis.getVoices();
        voices = all.filter(v => v.lang.startsWith(currentLang.split('-')[0]));
        if (!voices.length) voices = all.filter(v => v.lang.startsWith('fr')); // fallback
        currentVoiceIdx = 0;
    }
    window.speechSynthesis.onvoiceschanged = loadVoices;
    loadVoices();

    function cbCycleVoice() {
        if (!voices.length) loadVoices();
        currentVoiceIdx = (currentVoiceIdx + 1) % (voices.length || 1);
        var v = voices[currentVoiceIdx];
        if(v) alert("Voix changée : " + v.name);
    }

    function cbStopSpeech() {
        window.speechSynthesis.cancel();
        document.getElementById('cb-stop-btn').style.display = 'none';
    }

    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        var SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRec();
        recognition.lang = 'fr-FR';
        recognition.continuous = false;
        recognition.interimResults = false;
        
        recognition.onresult = function(event) {
            var text = event.results[0][0].transcript;
            document.getElementById('cb-input').value = text;
            cbSend();
            document.getElementById('cb-mic-btn').style.color = '#4F8EF7';
        };
        recognition.onerror = function() {
            document.getElementById('cb-mic-btn').style.color = '#4F8EF7';
        };
        recognition.onend = function() {
            document.getElementById('cb-mic-btn').style.color = '#4F8EF7';
        };
    }

    function cbToggleVoice() {
        isVoiceEnabled = !isVoiceEnabled;
        var btn = document.getElementById('cb-voice-toggle');
        var icon = document.getElementById('cb-voice-icon');
        if (isVoiceEnabled) {
            btn.style.color = '#2DD4BF';
            icon.innerHTML = '<path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07M19 12h.01"/>';
        } else {
            cbStopSpeech();
            btn.style.color = 'rgba(255,255,255,0.7)';
            icon.innerHTML = '<path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/><line x1="1" y1="1" x2="23" y2="23"/>';
        }
    }

    function cbStartMic() {
        if (!recognition) return alert("Votre navigateur ne supporte pas la reconnaissance vocale.");
        cbStopSpeech();
        document.getElementById('cb-mic-btn').style.color = '#ef4444';
        recognition.start();
    }

    function cbSpeak(text) {
        if (!isVoiceEnabled) return;
        window.speechSynthesis.cancel(); // Stop current before new
        var clean = text.replace(/\*\*/g, '').replace(/\*/g, '').replace(/`/g, '');
        var msg = new SpeechSynthesisUtterance(clean);
        
        if (voices.length > 0) {
            msg.voice = voices[currentVoiceIdx];
        }
        msg.lang = currentLang;
        msg.rate = 1.0; 
        
        msg.onstart = function() {
            document.getElementById('cb-stop-btn').style.display = 'block';
        };
        msg.onend = function() {
            document.getElementById('cb-stop-btn').style.display = 'none';
        };
        window.speechSynthesis.speak(msg);
    }

    // Wrap cbBotMsg to include speech
    var originalCbBotMsg = cbBotMsg;
    cbBotMsg = function(t) {
        originalCbBotMsg(t);
        cbSpeak(t);
    };
    // Init on page load
    document.addEventListener('DOMContentLoaded',function(){
        var inp=document.getElementById('plafond_input');
        if(inp) updateStyleHint(inp.value);
    });
    
    // Complex Tracking for Butterfly and Robot
    var butterflyInfo = document.createElement('div');
    butterflyInfo.id = 'magic-butterfly';
    butterflyInfo.innerHTML = '<svg width="30" height="30" viewBox="0 0 24 24" overflow="visible">' +
     '<g style="transform-origin: 12px 12px; animation: b-flap 0.15s infinite alternate ease-in-out;">' +
       '<path d="M 11 12 C 4 2, 0 8, 11 18 Z" fill="#4F8EF7" stroke="#fff" stroke-width="0.5"/>' +
       '<path d="M 11 12 C 5 18, 2 22, 11 18 Z" fill="#2DD4BF"/>' +
     '</g>' +
     '<g style="transform-origin: 12px 12px; animation: b-flap-r 0.15s infinite alternate ease-in-out;">' +
       '<path d="M 13 12 C 20 2, 24 8, 13 18 Z" fill="#4F8EF7" stroke="#fff" stroke-width="0.5"/>' +
       '<path d="M 13 12 C 19 18, 22 22, 13 18 Z" fill="#2DD4BF"/>' +
     '</g>' +
     '<rect x="11" y="9" width="2" height="9" rx="1" fill="#fff"/>' +
    '</svg>';

    var realMouseX = 180, realMouseY = 150;
    var realMouseGlobX = window.innerWidth/2, realMouseGlobY = window.innerHeight/2;
    var mouseX = realMouseX, mouseY = realMouseY;
    var bfX = mouseX, bfY = mouseY;
    var isWakingUp = false;
    var isResting = false;

    document.addEventListener('DOMContentLoaded', function() {
        var panelEl = document.getElementById('cb-panel');
        if(panelEl) {
            panelEl.appendChild(butterflyInfo);
        }

        // Tracking globally!
        document.addEventListener('mousemove', function(e) {
            realMouseGlobX = e.clientX;
            realMouseGlobY = e.clientY;
            
            if(panelEl) {
                var rect = panelEl.getBoundingClientRect();
                realMouseX = e.clientX - rect.left;
                realMouseY = e.clientY - rect.top;
            }
            
            if(!isWakingUp && !isResting) {
                mouseX = realMouseX;
                mouseY = realMouseY;
            }
        });

        requestAnimationFrame(loopAnim);
    });

    var handStates = []; // To keep track of smooth hand positions

    function loopAnim() {
        var panelEl = document.getElementById('cb-panel');
        if(!panelEl) return requestAnimationFrame(loopAnim);

        var pRect = panelEl.getBoundingClientRect();
        var winW = window.innerWidth;
        var winH = window.innerHeight;

        var heads = document.querySelectorAll('.cb-robot-head-grp');
        var eyes = document.querySelectorAll('.cb-robot-eyes-grp');
        var hands = document.querySelectorAll('.cb-robot-hand-grp');

        var finalRot = 0;

        if(!isResting) {
            var dx = mouseX - bfX;
            var dy = mouseY - bfY;
            bfX += dx * 0.05; // Slower butterfly tracking
            bfY += dy * 0.05;
            finalRot = dx * 0.2;
            if(finalRot > 30) finalRot = 30; if(finalRot < -30) finalRot = -30;
        }

        heads.forEach(function(head, idx) {
            if (head.closest('.cb-panel') && !head.closest('.cb-open')) return;
            if (head.offsetParent === null && !head.closest('.cb-fab')) return;

            if(head.classList.contains('is-sleeping')) {
                head.style.transform = 'rotate(-18deg) translateY(6px)';
                if(eyes[idx]) eyes[idx].style.transform = 'scaleY(0.1) translateY(4px)';
                if(hands[idx]) {
                   // Reset hands for sleep
                   hands.forEach(h => {
                       if(h.closest('.cb-robot-head-grp') === head.parentElement) {
                           h.style.transform = 'translateY(10px)';
                       }
                   });
                }
                return;
            }

            var rect = head.getBoundingClientRect();
            if(rect.width === 0) return;
            var rX = rect.left + rect.width / 2;
            var rY = rect.top + rect.height / 2;
            
            var targetGlobalX, targetGlobalY;
            // Use realMouseGlobX/Y to track ACTUAL mouse for robot senses even if butterfly is resting
            targetGlobalX = isWakingUp ? (pRect.left + bfX) : realMouseGlobX;
            targetGlobalY = isWakingUp ? (pRect.top + bfY) : realMouseGlobY;

            var hDx = targetGlobalX - rX;
            var hDy = targetGlobalY - rY;
            var dist = Math.hypot(hDx, hDy);

            var tilt = (hDx / winW) * 40; 
            var pitch = (hDy / winH) * 45;
            head.style.transform = 'rotate(' + tilt + 'deg) translateY(' + (pitch * 0.3) + 'px)';

            // Butterly position when WAKING UP
            if(isWakingUp && head.closest('.cb-bot-watermark')) {
                // Keep butterfly consistent with its flying coordinates
            }

            // Sync hands for this head
            var robotHands = [];
            hands.forEach(h => {
                if(h.closest('.cb-robot-head-grp') === null && h.closest('svg') === head.closest('svg')) {
                    robotHands.push(h);
                }
            });

            robotHands.forEach((h, hIdx) => {
                var isRightHand = h.classList.contains('cb-robot-hand-right');
                
                // Hand smoothing logic
                if(!handStates[idx]) handStates[idx] = {};
                if(!handStates[idx][hIdx]) handStates[idx][hIdx] = {x:0, y:0, r:0};
                
                var hState = handStates[idx][hIdx];
                var handReach = Math.min(dist / 6, 35);
                var hRad = Math.atan2(hDy, hDx);
                var tx = Math.cos(hRad) * handReach;
                var ty = Math.sin(hRad) * handReach;
                var tr = hRad * 180 / Math.PI + (isRightHand ? -10 : 10);
                
                // Smoother transition (LERP) - halved speed
                hState.x += (tx - hState.x) * 0.06;
                hState.y += (ty - hState.y) * 0.06;
                hState.r += (tr - hState.r) * 0.06;
                
                h.style.transform = 'translate(' + hState.x + 'px, ' + hState.y + 'px) rotate(' + hState.r + 'deg)';
            });

            if(eyes[idx]) {
                var eyeDist = Math.min(dist / 25, 12);
                var eRad = Math.atan2(hDy, hDx);
                eyes[idx].style.transform = 'translate(' + (Math.cos(eRad) * eyeDist) + 'px,' + (Math.sin(eRad) * eyeDist) + 'px)';
            }
        });

        butterflyInfo.style.transform = 'translate(' + (bfX - 15) + 'px, ' + (bfY - 15) + 'px) rotate(' + finalRot + 'deg)';
        requestAnimationFrame(loopAnim);
    }
        requestAnimationFrame(loopAnim);

    // ── THEME MANAGER ────────────────────────────────────
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        var sun = document.getElementById('theme-icon-sun');
        var moon = document.getElementById('theme-icon-moon');
        if (theme === 'light') {
            if (sun) sun.style.display = 'block';
            if (moon) moon.style.display = 'none';
        } else {
            if (sun) sun.style.display = 'none';
            if (moon) moon.style.display = 'block';
        }
    }
    function toggleTheme() {
        // Read the LIVE attribute, not localStorage (avoids stale value bugs)
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }
    // Sync icons with the theme already set in <head>
    applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');
    // -- GLOBAL NOTIFICATIONS --
    document.addEventListener('DOMContentLoaded', () => {
        const showDesktopNotif = (title, body) => {
            if (window.Notification && Notification.permission === 'granted') {
                new Notification(title, { body: body });
            } else {
                alert(title + ": " + body);
            }
        };

        <?php if (!empty($_SESSION['pending_desktop_notif'])): 
            $notifs = $_SESSION['pending_desktop_notif'];
            unset($_SESSION['pending_desktop_notif']);
        ?>
            const list = <?= json_encode($notifs) ?>;
            list.forEach((n, i) => {
                setTimeout(() => {
                    showDesktopNotif(n.title, n.body);
                }, i * 800);
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>
