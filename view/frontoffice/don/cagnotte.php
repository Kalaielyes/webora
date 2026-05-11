<?php
require_once __DIR__ . '/../../../models/config.php';
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . '/../../../controller/CagnotteController.php';
require_once __DIR__ . '/../../../controller/DonController.php';
require_once __DIR__ . '/../../../controller/CompteController.php';
Session::start();
Session::requireLogin();

$cagCtrl = new CagnotteController();
$donCtrl = new DonController();
$defaultUserId = (int)(Session::get('user_id') ?: ($_SESSION['user']['id'] ?? 0));
$loggedInUserId = $defaultUserId;
$availableUsers = [];
if ($defaultUserId > 0) {
  $defaultUser = $cagCtrl->getUserById($defaultUserId);
  if ($defaultUser) {
    $availableUsers[] = $defaultUser;
  }
}
$selectedUserId = $loggedInUserId;
$donateurUserId = $loggedInUserId;
$currentUser = $cagCtrl->getUserById($selectedUserId);
if (!$currentUser) {
  $selectedUserId = (int)$defaultUserId;
  $_SESSION['frontoffice_user_id'] = $selectedUserId;
  $currentUser = $cagCtrl->getUserById($selectedUserId);
}
$_SESSION['frontoffice_user_id'] = $selectedUserId;
$currentUserName = trim((string)($currentUser['prenom'] ?? '') . ' ' . (string)($currentUser['nom'] ?? ''));
if ($currentUserName === '') {
  $currentUserName = 'Utilisateur';
}
$currentUserEmail = trim((string)($currentUser['email'] ?? ''));
$currentUserIsAssociation = (bool)($currentUser['association'] ?? 0);
$_SESSION['user'] = array_merge($_SESSION['user'] ?? [], $currentUser ?? []);
$userAccounts = $donateurUserId > 0 ? CompteController::findByUtilisateur($donateurUserId) : [];
$donEligibleAccounts = [];
$hasActiveSavingsAccount = false;
$hasAnyActiveAccount = false;
foreach ($userAccounts as $acc) {
    if ($acc->getStatut() === 'actif') {
      $hasAnyActiveAccount = true;
    }
    if ($acc->getStatut() === 'actif' && strtolower((string)$acc->getTypeCompte()) === 'epargne') {
      $hasActiveSavingsAccount = true;
    }
    if ($acc->getStatut() !== 'actif') {
      continue;
    }
    if (strtolower((string)$acc->getTypeCompte()) === 'epargne') {
      continue;
    }
    $donEligibleAccounts[] = [
      'id' => (int)$acc->getIdCompte(),
      'iban' => (string)$acc->getIban(),
      'devise' => strtoupper((string)$acc->getDevise()),
      'solde' => (float)$acc->getSolde(),
      'type' => (string)$acc->getTypeCompte(),
    ];
}
$showSavingsOnlyVirementMessage = empty($donEligibleAccounts) && $hasActiveSavingsAccount;
$showNoActiveAccountVirementMessage = empty($donEligibleAccounts) && !$hasAnyActiveAccount;
$currentUserInitials = strtoupper(substr((string)($currentUser['prenom'] ?? ''), 0, 1) . substr((string)($currentUser['nom'] ?? ''), 0, 1));
if ($currentUserInitials === '') {
  $currentUserInitials = 'US';
}
$createSuccess = isset($_GET['create_success']) && $_GET['create_success'] === '1';
$createError = $_GET['error'] ?? null;
$actionMessage = $_GET['msg'] ?? null;
$donSuccess = isset($_GET['don_success']) && $_GET['don_success'] === '1';

function frontCagnotteStatusLabel($status) {
  $labels = [
    'en_attente' => 'En attente',
    'acceptee' => 'Acceptée',
    'refusee' => 'Refusée',
    'suspendue' => 'Suspendue',
    'cloturee' => 'Clôturée'
  ];
  return $labels[$status] ?? 'En attente';
}

function frontCagnotteStatusClass($status) {
  if ($status === 'acceptee') return 'cc-status active';
  if ($status === 'refusee') return 'cc-status refused';
  if ($status === 'suspendue') return 'cc-status paused';
  if ($status === 'cloturee') return 'cc-status closed';
  return 'cc-status urgent';
}

function frontRedirectWith($params = [], $preserved = []) {
    $qs = http_build_query(array_merge($preserved, $params));
    header('Location: ' . APP_URL . '/view/frontoffice/don/cagnotte.php' . ($qs ? ('?' . $qs) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $act = $_POST['action'];
  try {
    if ($act === 'change_user' && isset($_POST['selected_user_id'])) {
      $_SESSION['frontoffice_user_id'] = $loggedInUserId;
      frontRedirectWith(['msg' => 'Compte actif mis à jour']);
    } elseif ($act === 'create_cagnotte') {
      $data = [
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'categorie' => $_POST['categorie'] ?? '',
        'objectif_montant' => $_POST['objectif_montant'] ?? 0,
        'date_debut' => $_POST['date_debut'] ?? null,
        'date_fin' => $_POST['date_fin'] ?? null
      ];
      $ok = $cagCtrl->ajouterCagnotte($data, $selectedUserId);
      frontRedirectWith(['create_success' => $ok ? '1' : '0', 'msg' => $ok ? 'Cagnotte créée' : ($cagCtrl->getLastError() ?: 'Erreur création')]);
    } elseif ($act === 'delete_cagnotte' && isset($_POST['id'])) {
      $ok = $cagCtrl->supprimerCagnotte((int)$_POST['id']);
      frontRedirectWith(['msg' => $ok ? 'Cagnotte supprimée' : 'Erreur suppression']);
    } elseif ($act === 'update_cagnotte' && isset($_POST['id'])) {
      $id = (int)$_POST['id'];
      $data = [];
      if (isset($_POST['titre'])) $data['titre'] = $_POST['titre'];
      if (isset($_POST['description'])) $data['description'] = $_POST['description'];
      if (isset($_POST['categorie'])) $data['categorie'] = $_POST['categorie'];
      if (isset($_POST['objectif_montant'])) $data['objectif_montant'] = $_POST['objectif_montant'];
      if (isset($_POST['date_debut'])) $data['date_debut'] = $_POST['date_debut'];
      if (isset($_POST['date_fin'])) $data['date_fin'] = $_POST['date_fin'];
      $ok = $cagCtrl->modifierCagnotte($id, $data);
      frontRedirectWith(['msg' => $ok ? 'Cagnotte modifiée' : ($cagCtrl->getLastError() ?: 'Erreur modification')]);
    } elseif ($act === 'toggle_status' && isset($_POST['id']) && isset($_POST['new_status'])) {
      $id = (int)$_POST['id'];
      $new = $_POST['new_status'];
      $ok = $cagCtrl->updateStatusUser($id, $new, $selectedUserId);
      frontRedirectWith(['msg' => $ok ? 'Statut mis à jour' : 'Erreur mise à jour statut']);
    } elseif ($act === 'create_don') {
      $donData = [
        'montant' => $_POST['montant'] ?? 0,
        'id_cagnotte' => $_POST['id_cagnotte'] ?? null,
        'moyen_paiement' => $_POST['moyen_paiement'] ?? 'carte',
        'id_compte' => $_POST['id_compte'] ?? null,
        'devise_don' => $_POST['devise_don'] ?? 'TND',
        'est_anonyme' => 0,
        'message' => $_POST['message'] ?? null
      ];
      $ok = $donCtrl->ajouterDon($donData, $donateurUserId);
      frontRedirectWith(['don_success' => $ok ? '1' : '0', 'don_amount' => $ok ? (string)$donData['montant'] : '0', 'msg' => $ok ? 'Don envoyé' : 'Erreur lors de l\'envoi du don']);
    } elseif ($act === 'delete_don' && isset($_POST['don_id'])) {
      $ok = $donCtrl->supprimerDon((int)$_POST['don_id']);
      frontRedirectWith(['msg' => $ok ? 'Don supprimé' : 'Erreur suppression don']);
    }
  } catch (Exception $e) {
    frontRedirectWith(['error' => $e->getMessage()]);
  }
}
$activeCagnottes = $cagCtrl->getAllCagnottes('acceptee');
$userCagnottes = $cagCtrl->getUserCagnottes($selectedUserId);

$totalCollectedMy = 0.0;
$userActiveCount = 0;
$userPendingCount = 0;
foreach ($userCagnottes as $uc) {
    $totalCollectedMy += floatval($uc['total_collecte'] ?? 0);
  if (($uc['statut'] ?? '') === 'acceptee') $userActiveCount++;
  if (($uc['statut'] ?? '') === 'en_attente') $userPendingCount++;
}

$userDons = $donCtrl->getDonsByDonateur($donateurUserId);
$mesDonsCount = count($userDons);
$mesDonsTotal = 0.0;
$mesPendingDons = 0;
foreach ($userDons as $d) {
    $mesDonsTotal += floatval($d['montant'] ?? 0);
    if (($d['statut'] ?? '') !== 'confirme') $mesPendingDons++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<script>
  (function(){
    var t = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
    if (localStorage.getItem('privacy') === 'true') {
      document.documentElement.classList.add('privacy-mode');
    }
  })();
</script>
<title>Cagnotte Solidaire — Espace Donateur</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/frontoffice/Utilisateur.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/frontoffice/cagnotte.css">
<script src="https://js.stripe.com/v3/"></script>

</head>
<body>
<?php if ($createSuccess): ?>
<script>document.addEventListener('DOMContentLoaded',function(){document.getElementById('success-overlay').style.display='flex';});</script>
<?php endif; ?>
<?php if ($actionMessage || $createError): ?>
<?php $toastStyle = $createError
  ? 'background:rgba(244,63,94,.15);border:1px solid rgba(244,63,94,.4);color:#fca5a5;'
  : 'background:rgba(45,212,191,.12);border:1px solid rgba(45,212,191,.35);color:#5eead4;'; ?>
<div id="front-toast" style="position:fixed;top:1.2rem;right:1.2rem;z-index:99999;max-width:380px;padding:.8rem 1.1rem;border-radius:12px;font-size:.82rem;font-weight:500;display:flex;align-items:center;gap:.7rem;box-shadow:0 4px 24px rgba(0,0,0,.35);transition:opacity .4s;<?= $toastStyle ?>">
  <?php if ($createError): ?>
    <svg width="15" height="15" fill="none" stroke="#f87171" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($createError) ?>
  <?php else: ?>
    <svg width="15" height="15" fill="none" stroke="#2DD4BF" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($actionMessage) ?>
  <?php endif; ?>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var t=document.getElementById('front-toast');if(t)setTimeout(function(){t.style.opacity='0';setTimeout(function(){t.remove();},400);},4000);});</script>
<?php endif; ?>

<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title" id="topbar-title">Tableau de bord</div>
    <div class="topbar-right">
      <div id="topbar-back" style="display:none;">
        <button class="btn-ghost" onclick="showView('dashboard')">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
          Retour
        </button>
      </div>
      <div id="topbar-dashboard-btns" style="display:flex;align-items:center;gap:1rem;">
        <button id="aiUrgentBtn" class="btn-ai-urgent" title="AI Urgent Campaign Analysis">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"></path>
            <path d="M12 12v-2m0 4v2M9 11h6" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <span>AI</span>
        </button>
        <div class="notif">
          <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
          <div class="notif-dot"></div>
        </div>
      </div>
      <button class="privacy-toggle" onclick="togglePrivacy()" title="Mode confidentialité">
        <svg id="privacy-icon-off" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        <svg id="privacy-icon-on" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
      </button>
      <button class="theme-toggle" onclick="toggleTheme()" title="Changer de thème">
        <svg id="theme-icon-sun" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
        <svg id="theme-icon-moon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
      </button>
    </div>
  </div>

  <!-- DASHBOARD -->
  <div class="view active" id="view-dashboard">
    <div class="content">
      <div class="hero-row">
        <?php if ($currentUserIsAssociation): ?>
        <div class="hero-card">
          <div class="hc-label">Total collecté (mes cagnottes)</div>
          <div class="hc-val"><?= number_format($totalCollectedMy,2,',',' ') ?><span>€</span></div>
          <div class="hc-badge">↑ +0% ce mois</div>
          <div class="hc-sub" style="margin-top:.8rem">Sur <?= (int)$userActiveCount ?> cagnottes actives</div>
        </div>
        <?php else: ?>
        <div class="hero-card">
          <div class="hc-label">Total donné</div>
          <div class="hc-val"><?= number_format($mesDonsTotal,2,',',' ') ?><span>€</span></div>
          <div class="hc-badge">↑ Historique des dons</div>
          <div class="hc-sub" style="margin-top:.8rem">Sur <?= (int)$mesDonsCount ?> don<?= $mesDonsCount > 1 ? 's' : '' ?></div>
        </div>
        <?php endif; ?>
        <div class="stat-mini">
          <div class="sm-label"><svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> Mes dons</div>
          <div class="sm-val" style="color:var(--blue)"><?= (int)$mesDonsCount ?></div>
          <div class="sm-sub">Total : <?= number_format($mesDonsTotal,2,',',' ') ?> €</div>
        </div>
        <?php if ($currentUserIsAssociation): ?>
        <div class="stat-mini">
          <div class="sm-label"><svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Cagnottes en attente</div>
          <div class="sm-val" style="color:var(--amber)"><?= (int)$userPendingCount ?></div>
          <div class="sm-sub">Validation admin requise</div>
        </div>
        <?php else: ?>
        <div class="stat-mini">
          <div class="sm-label"><svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Dons en attente</div>
          <div class="sm-val" style="color:var(--amber)"><?= (int)$mesPendingDons ?></div>
          <div class="sm-sub">Validation en cours</div>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($currentUserIsAssociation): ?>
      <div>
        <div class="section-head" style="margin-bottom:.9rem"><div class="section-title">Mes cagnottes</div></div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1rem;">
          <?php if (empty($userCagnottes)): ?>
            <div>Aucune cagnotte créée</div>
          <?php else: foreach ($userCagnottes as $uc): ?>
            <?php
              $userCagStatus = $uc['statut'] ?? 'en_attente';
              $userCagStatusLabel = frontCagnotteStatusLabel($userCagStatus);
              $userCagStatusClass = frontCagnotteStatusClass($userCagStatus);
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--border);">
              <div>
                <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;">
                  <div style="font-weight:600"><?=htmlspecialchars($uc['titre'])?></div>
                  <span class="<?= htmlspecialchars($userCagStatusClass) ?>"><?= htmlspecialchars($userCagStatusLabel) ?></span>
                </div>
                <div style="font-size:.85rem;color:var(--muted)"><?=htmlspecialchars(substr($uc['description'] ?? '',0,80))?></div>
                <div style="font-size:.75rem;color:var(--muted)"><?= ($uc['association'] ?? 0) ? 'Association' : 'Particulier' ?></div>
              </div>
              <div style="display:flex;gap:.5rem;">
                <form method="post" style="display:inline;margin:0;padding:0;">
                  <input type="hidden" name="action" value="delete_cagnotte" />
                  <input type="hidden" name="id" value="<?= (int)$uc['id_cagnotte'] ?>" />
                  <button class="btn-ghost" type="submit">Supprimer</button>
                </form>
                <button class="btn-ghost" onclick="openEdit(<?= (int)$uc['id_cagnotte'] ?>,'<?= htmlspecialchars(addslashes($uc['titre'])) ?>','<?= htmlspecialchars(addslashes($uc['description'] ?? '')) ?>','<?= htmlspecialchars(addslashes($uc['categorie'] ?? '')) ?>',<?= (float)($uc['objectif_montant'] ?? 0) ?>,'<?= htmlspecialchars($uc['date_debut'] ?? '') ?>','<?= htmlspecialchars($uc['date_fin'] ?? '') ?>')">Modifier</button>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div>
        <div class="section-head" style="margin-bottom:.9rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;flex-wrap:wrap;">
          <div class="section-title">Cagnottes en cours</div>
        </div>
        <div class="cagnottes-grid">
        <?php if (empty($activeCagnottes)): ?>
          <div class="empty">Aucune cagnotte active pour le moment.</div>
        <?php else: foreach ($activeCagnottes as $c):
            $objectif = floatval($c['objectif_montant'] ?? 0);
            $collected = floatval($c['total_collecte'] ?? 0);
            $pct = $objectif > 0 ? min(100, round(($collected / $objectif) * 100)) : 0;
            $catDb = strtolower((string)($c['categorie'] ?? ''));
            $catUi = $catDb === 'sante' ? 'medical' : ($catDb === 'solidarite' ? 'humanitaire' : ($catDb === 'autre' ? 'urgence' : 'education'));
            $catEmoji = $catUi === 'medical' ? '🏥' : ($catUi === 'education' ? '📚' : ($catUi === 'humanitaire' ? '🌍' : '⚡'));
        ?>
          <div class="cagnotte-card" onclick="openDon(<?= (int)$c['id_cagnotte'] ?>,'<?= htmlspecialchars(addslashes($c['titre'])) ?>')">
            <div class="cc-banner"><?= $catEmoji ?></div>
            <div class="cc-body">
              <div class="cc-cat <?= $catUi ?>"><?=htmlspecialchars($c['categorie'])?></div>
              <div class="cc-title"><?=htmlspecialchars($c['titre'])?></div>
              <div class="cc-desc"><?=htmlspecialchars(substr($c['description'] ?? '',0,120))?></div>
              <div style="font-size:.68rem;color:var(--muted);margin-bottom:.45rem;"><?= ($c['association'] ?? 0) ? 'Association' : 'Particulier' ?></div>
              <div class="progress-bar"><div class="progress-fill" style="width:<?=$pct?>%"></div></div>
              <div class="cc-amounts"><span class="cc-collected"><?=number_format($collected,2,',',' ')?> €</span><span class="cc-goal">/ <?=number_format($objectif,0,',',' ')?> €</span></div>
              <div class="cc-footer"><span class="cc-donateurs">❤️ <?=intval($c['nb_dons'])?> donateurs</span><span class="cc-status"><?=htmlspecialchars(ucfirst(str_replace('_',' ', $c['statut'])))?></span></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
        </div>
      </div>
      <div>
        <div class="section-head" style="margin-bottom:.9rem"><div class="section-title">Mes dons récents</div></div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:0 1.2rem;">
          <div class="dons-list">
            <?php if (empty($userDons)): ?>
              <div style="padding:1rem;color:var(--muted)">Aucun don récent</div>
            <?php else: foreach (array_slice($userDons,0,6) as $ud): ?>
              <div class="don-item">
                <div class="don-icon" style="background:rgba(45,212,191,.1)"><svg width="16" height="16" fill="none" stroke="#2DD4BF" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div>
                <div class="don-info"><div class="don-label"><?=htmlspecialchars($ud['cagnotte_titre'] ?? '—')?><span class="don-tag"><?=htmlspecialchars($ud['moyen_paiement'] ?? '')?></span></div><div class="don-date"><?=htmlspecialchars(substr($ud['date_don'] ?? '',0,10))?></div></div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.25rem;">
                  <?php
                    $donStatus = $ud['statut'] ?? 'en_attente';
                    $donStatusLabel = $donCtrl->getDonStatusLabel($donStatus);
                    $donStatusClass = $donStatus === 'confirme' ? 'status-confirme' : 'status-attente';
                    if ($donStatus === 'refuse') {
                      $donStatusClass = 'status-refuse';
                    }
                  ?>
                  <div><div class="don-amount">+ <?=number_format($ud['montant'],2,',',' ')?> €</div><div class="don-status <?= htmlspecialchars($donStatusClass) ?>"><?= htmlspecialchars($donStatusLabel) ?></div></div>
                  <form method="post" style="display:inline;margin:0;padding:0;">
                    <input type="hidden" name="action" value="delete_don" />
                    <input type="hidden" name="don_id" value="<?= (int)($ud['id_don'] ?? 0) ?>" />
                    <button class="btn-ghost" type="submit" style="font-size:.75rem;padding:.25rem .5rem;margin-top:.25rem;">Supprimer</button>
                  </form>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CRÉER CAGNOTTE -->
  <div class="view" id="view-creer">
    <div class="cc-content">
      <div class="stepper">
        <div class="step active" id="stp-1"><div class="step-num">1</div><div class="step-label">Informations</div></div>
        <div class="step-line" id="sline-1"></div>
        <div class="step" id="stp-2"><div class="step-num">2</div><div class="step-label">Objectif & Durée</div></div>
        <div class="step-line" id="sline-2"></div>
        <div class="step" id="stp-3"><div class="step-num">3</div><div class="step-label">Options</div></div>
        <div class="step-line" id="sline-3"></div>
        <div class="step" id="stp-4"><div class="step-num">4</div><div class="step-label">Confirmation</div></div>
      </div>

      <div class="form-grid">
        <form id="create-cagnotte-form" method="post" enctype="multipart/form-data">
        <input type="hidden" id="create-action" name="action" value="create_cagnotte" />
        <input type="hidden" id="create-id" name="id" value="" />
        <input type="hidden" id="form-is-edit" value="0" />
        <input type="hidden" name="categorie" id="inp-categorie" value="" />
        <div class="create-form-main">

          <!-- SECTION 1 : Informations -->
          <div class="form-card">
            <div class="form-card-title">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Informations générales
            </div>

            <!-- Catégorie -->
            <div class="field">
              <div class="field-label">Catégorie <span class="req">*</span></div>
              <div class="cat-grid">
                <div class="cat-btn" id="cat-medical" onclick="selectCat('medical')"><div class="cat-check"><svg viewBox="0 0 10 10"><polyline points="1,5 4,8 9,2"/></svg></div><div class="cat-emoji">🏥</div><div class="cat-name">Médical</div></div>
                <div class="cat-btn" id="cat-education" onclick="selectCat('education')"><div class="cat-check"><svg viewBox="0 0 10 10"><polyline points="1,5 4,8 9,2"/></svg></div><div class="cat-emoji">📚</div><div class="cat-name">Éducation</div></div>
                <div class="cat-btn" id="cat-urgence" onclick="selectCat('urgence')"><div class="cat-check"><svg viewBox="0 0 10 10"><polyline points="1,5 4,8 9,2"/></svg></div><div class="cat-emoji">⚡</div><div class="cat-name">Autre</div></div>
                <div class="cat-btn" id="cat-humanitaire" onclick="selectCat('humanitaire')"><div class="cat-check"><svg viewBox="0 0 10 10"><polyline points="1,5 4,8 9,2"/></svg></div><div class="cat-emoji">🌍</div><div class="cat-name">Humanitaire</div></div>
              </div>
              <div class="field-error" id="err-cat">⚠ Veuillez sélectionner une catégorie</div>
            </div>

            <!-- Titre -->
            <div class="field">
              <div class="field-label">Titre <span class="req">*</span></div>
              <div class="inp-wrap">
                <input class="fi" type="text" id="inp-titre" name="titre" maxlength="80" placeholder="Min. 10 caractères" oninput="validateTitre();updatePv();countC('inp-titre','cnt-titre',80)" onblur="validateTitre()"/>
                <span class="inp-icon" id="icon-titre"></span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div class="field-error" id="err-titre">⚠ Minimum 10 caractères requis</div>
                <div class="field-ok" id="ok-titre">✔ Titre valide</div>
                <div class="char-count"><span id="cnt-titre">0</span>/80</div>
              </div>
            </div>

            <!-- Description -->
            <div class="field">
              <div class="field-label">Description <span class="req">*</span></div>
              <textarea class="fi" id="inp-desc" name="description" maxlength="500" placeholder="Min. 30 caractères — décrivez la situation et l'utilisation des fonds…" oninput="validateDesc();updatePv();countC('inp-desc','cnt-desc',500)" onblur="validateDesc()"></textarea>
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div class="field-error" id="err-desc">⚠ Minimum 30 caractères requis</div>
                <div class="field-ok" id="ok-desc">✔ Description valide</div>
                <div class="char-count"><span id="cnt-desc">0</span>/500</div>
              </div>
            </div>

            <!-- Image -->
            <div class="field">
              <div class="field-label">Image / Bannière</div>
              <div class="banner-upload" id="banner-zone" onclick="document.getElementById('inp-file').click()">
                <img class="banner-preview-img" id="banner-prev" src="" alt=""/>
                <div class="upl-placeholder">
                  <div class="upl-icon"><svg width="17" height="17" fill="none" stroke="var(--muted2)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></div>
                  <div class="upl-lbl">Cliquez pour ajouter une image</div>
                  <div class="upl-sub">JPG, PNG — max 5 Mo</div>
                </div>
                <div class="banner-change-btn">✏ Changer</div>
              </div>
              <input type="file" id="inp-file" accept="image/jpeg,image/png,image/jpg" onchange="validateBanner(event)" style="display:none"/>
              <div class="field-error" id="err-banner">⚠ Format invalide ou fichier trop lourd (max 5 Mo, JPG/PNG)</div>
              <div class="field-hint">Une belle image augmente les dons de 40%</div>
            </div>
          </div>

          <!-- SECTION 2 : Objectif & Durée -->
          <div class="form-card" style="margin-top:1rem;">
            <div class="form-card-title">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Objectif & Durée
            </div>
            <div class="row-2">
              <div class="field">
                <div class="field-label">Montant objectif (€) <span class="req">*</span></div>
                <div class="inp-wrap">
                  <input class="fi" type="number" id="inp-objectif" name="objectif_montant" placeholder="Min. 100 €" oninput="validateObjectif()" onblur="validateObjectif()"/>
                  <span class="inp-icon" id="icon-objectif"></span>
                </div>
                <div class="field-error" id="err-objectif">⚠ Montant entre 100 et 9 999 999 €</div>
                <div class="field-ok" id="ok-objectif">✔ Montant valide</div>
              </div>
              <!-- statut is managed by admin; hidden on creation -->
            </div>
            <div class="row-2">
              <div class="field">
                <div class="field-label">Date de début <span class="req">*</span></div>
                <input class="fi" type="date" id="inp-debut" name="date_debut" onchange="validateDates()" onblur="validateDates()"/>
                <div class="field-error" id="err-debut">⚠ Date de début requise</div>
              </div>
              <div class="field">
                <div class="field-label">Date de fin <span class="req">*</span></div>
                <input class="fi" type="date" id="inp-fin" name="date_fin" onchange="validateDates()" onblur="validateDates()"/>
                <div class="field-error" id="err-fin">⚠ La date de fin doit être après le début</div>
                <div class="field-hint">La date de fin est obligatoire</div>
              </div>
            </div>
          </div>

          <!-- SECTION 3 : Options -->
          <div class="form-card" style="margin-top:1rem;">
            <div class="form-card-title">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
              Options de collecte
            </div>
            <div class="toggle-row">
              <div class="toggle-info"><div class="tl">Dons anonymes</div><div class="ts">Les donateurs peuvent cacher leur identité</div></div>
              <div class="tog on" onclick="this.classList.toggle('on')"><div class="tog-knob"></div></div>
            </div>
            <div class="divider"></div>
            <div class="toggle-row">
              <div class="toggle-info"><div class="tl">Messages d'encouragement</div><div class="ts">Les donateurs peuvent laisser un message</div></div>
              <div class="tog on" onclick="this.classList.toggle('on')"><div class="tog-knob"></div></div>
            </div>
            <div class="divider"></div>
            <div class="toggle-row">
              <div class="toggle-info"><div class="tl">Virements bancaires</div><div class="ts">En plus du paiement par carte</div></div>
              <div class="tog on" onclick="this.classList.toggle('on')"><div class="tog-knob"></div></div>
            </div>
            <div class="divider"></div>

            <!-- Bénéficiaire -->
            <div class="field">
              <div class="field-label">Nom du bénéficiaire</div>
              <input class="fi" type="text" id="inp-benef" name="beneficiaire" maxlength="100" placeholder="Ex : Sami B., 8 ans" oninput="validateBenef()" onblur="validateBenef()"/>
              <div class="field-error" id="err-benef">⚠ Nom trop court (min. 2 caractères)</div>
              <div class="field-hint">Optionnel — apparaîtra sur la page publique</div>
            </div>
          </div>

          <div class="submit-zone">
            <div class="sz-info">
              <strong>Prêt à publier ?</strong>
              <span>La cagnotte sera soumise à validation avant publication.</span>
            </div>
            <div class="sz-btns">
              <button type="button" class="btn-save" id="btn-draft" onclick="saveDraft()">💾 Brouillon</button>
              <button type="button" class="btn-submit" onclick="soumettre()">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                Soumettre
              </button>
            </div>
          </div>
          <div id="front-form-feedback" style="display:none;margin-top:.7rem;padding:.65rem .8rem;border:1px solid rgba(244,63,94,.35);background:rgba(244,63,94,.08);color:#be123c;border-radius:10px;font-size:.76rem;">Veuillez corriger tous les champs obligatoires avant de soumettre.</div>

        </form>

        <!-- APERÇU -->
        <div class="create-side-panel">
          <div class="preview-card">
            <div class="preview-hd"><div class="preview-hd-lbl">Aperçu en direct</div><div class="live-badge">Live</div></div>
            <div class="pv-banner" id="pv-banner">
              <img class="pv-banner-img" id="pv-banner-img" src="" alt=""/>
              <span id="pv-emoji">🎯</span>
            </div>
            <div class="pv-body">
              <div class="pv-cat" id="pv-cat">— Catégorie —</div>
              <div class="pv-titre" id="pv-titre" style="color:var(--muted)">Titre de votre cagnotte</div>
              <div class="pv-desc" id="pv-desc" style="color:var(--muted)">La description apparaîtra ici…</div>
              <div class="pv-progress"><div class="pv-fill" id="pv-fill"></div></div>
              <div class="pv-amounts"><span class="pv-collected">0 €</span><span class="pv-goal-txt" id="pv-goal">/ — €</span></div>
              <div class="pv-footer"><span class="pv-donateurs">❤️ 0 donateurs</span><span class="pv-status-badge active" id="pv-status">Active</span></div>
            </div>
          </div>
          <div class="info-box">
            <div class="info-box-title"><svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Processus de validation</div>
            <div class="info-step"><div class="info-step-num" style="background:rgba(45,212,191,.15);border:1px solid rgba(45,212,191,.3);color:var(--teal)">1</div><div class="info-step-txt">Soumission avec vos informations</div></div>
            <div class="info-step"><div class="info-step-num" style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:var(--amber)">2</div><div class="info-step-txt">Vérification par l'admin (24–48h)</div></div>
            <div class="info-step"><div class="info-step-num" style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);color:var(--green)">3</div><div class="info-step-txt">Publication et début de la collecte</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DON -->
<div id="don-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#13151E;border:1px solid rgba(79,142,247,.3);border-radius:18px;width:100%;max-width:460px;margin:1rem;padding:2rem;position:relative;">
    <button onclick="closeDon()" style="position:absolute;top:1rem;right:1rem;background:#1F2232;border:1px solid rgba(255,255,255,.07);border-radius:8px;width:32px;height:32px;color:#9CA3AF;cursor:pointer;font-size:1rem;">✕</button>
    <input type="hidden" id="inp-cag-id" value="" />

    <?php if ($currentUserIsAssociation): ?>
      <!-- Association Warning -->
      <div style="background: rgba(244,63,94,.12); border: 1px solid rgba(244,63,94,.35); border-radius: 10px; padding: 1.2rem; text-align: center;">
        <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚠️</div>
        <div style="font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; color: #F43F5E; margin-bottom: 0.5rem;">Donations non autorisées</div>
        <div style="font-size: 0.9rem; color: #F0F2FF; margin-bottom: 1rem;">Seuls les donateurs peuvent faire des dons. Les associations peuvent créer des cagnottes.</div>
        <button onclick="closeDon()" style="background: #F43F5E; color: white; border: none; border-radius: 8px; padding: 0.7rem 1.5rem; font-weight: 600; cursor: pointer;">Fermer</button>
      </div>
    <?php else: ?>

    <!-- STEP 1 -->
    <div id="don-step1">
      <div class="don-steps-bar"><div class="don-step-dot active"></div><div class="don-step-dot"></div><div class="don-step-dot"></div></div>
      <div style="font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:800;margin-bottom:.25rem;">Faire un don 💙</div>
      <div id="don-target-title" style="font-size:.78rem;color:#6B7280;margin-bottom:1.4rem;">— Sélectionnez une cagnotte —</div>

      <!-- Montants rapides -->
      <div style="font-size:.7rem;color:#6B7280;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.5rem;">Montant rapide</div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;margin-bottom:1rem;">
        <button class="qbtn" onclick="setMt(10)">10 €</button>
        <button class="qbtn" onclick="setMt(25)">25 €</button>
        <button class="qbtn" onclick="setMt(50)">50 €</button>
        <button class="qbtn" onclick="setMt(100)">100 €</button>
      </div>

      <!-- Montant libre -->
      <div style="font-size:.7rem;color:#6B7280;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.5rem;">Ou saisir un montant</div>
      <div style="position:relative;margin-bottom:.25rem;">
        <input id="inp-mt" type="number" min="1" max="999999" step="0.01" placeholder="0.00" oninput="validateMontantDon();document.querySelectorAll('.qbtn').forEach(b=>b.classList.remove('qsel'))" onblur="validateMontantDon()" style="width:100%;background:#1F2232;border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.7rem 3.5rem .7rem 1rem;color:#F0F2FF;font-size:1rem;outline:none;box-sizing:border-box;transition:border .18s;" onfocus="this.style.borderColor='#2DD4BF'" onblur="validateMontantDon()"/>
        <span style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);font-size:.75rem;color:#6B7280;">€</span>
      </div>
      <div class="don-field-err" id="err-mt-don">⚠ Montant invalide — min. 1 €, max. 999 999 €</div>

      <!-- Paiement -->
      <div style="font-size:.7rem;color:#6B7280;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.5rem;margin-top:.9rem;">Moyen de paiement <span style="color:#F43F5E">*</span></div>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem;margin-bottom:.25rem;">
        <label id="lbl-carte" class="plbl psel" onclick="selPay('carte')"><input type="radio" name="pay" value="carte" checked style="display:none">💳 Carte</label>
        <label id="lbl-virement" class="plbl" onclick="selPay('virement')"><input type="radio" name="pay" value="virement" style="display:none">🏦 Virement</label>
      </div>

      <div id="virement-account-box" style="display:none;margin:.8rem 0 1rem 0;">
        <div style="font-size:.7rem;color:#6B7280;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.45rem;">Compte à débiter (hors épargne)</div>
        <?php if (empty($donEligibleAccounts)): ?>
          <div style="font-size:.76rem;color:#fda4af;background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.25);border-radius:10px;padding:.75rem .85rem;line-height:1.5;">
            <?php if ($showSavingsOnlyVirementMessage): ?>
              Votre compte lié est un compte épargne. Les dons par virement utilisent uniquement un compte courant, devise ou professionnel actif.
              <a href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php" style="display:inline-flex;margin-top:.55rem;color:#93c5fd;text-decoration:none;font-weight:600;">Ouvrir mes comptes bancaires</a>
            <?php elseif ($showNoActiveAccountVirementMessage): ?>
              Aucun compte actif n'est disponible pour le virement.
            <?php else: ?>
              Aucun compte actif éligible au virement disponible.
            <?php endif; ?>
          </div>
        <?php else: ?>
          <select id="don-id-compte" style="width:100%;background:#1F2232;border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:.62rem .8rem;color:#F0F2FF;font-size:.82rem;outline:none;" onchange="updateVirementAccountUI()">
            <?php foreach ($donEligibleAccounts as $acc): ?>
              <option value="<?= (int)$acc['id'] ?>" data-devise="<?= htmlspecialchars($acc['devise']) ?>" data-solde="<?= htmlspecialchars((string)$acc['solde']) ?>" data-type="<?= htmlspecialchars($acc['type']) ?>">
                <?= htmlspecialchars(ucfirst($acc['type'])) ?> ···<?= htmlspecialchars(substr($acc['iban'], -6)) ?> — <?= number_format($acc['solde'], 3, ',', ' ') ?> <?= htmlspecialchars($acc['devise']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div id="don-conversion-preview" style="font-size:.72rem;color:#93c5fd;margin-top:.4rem;"></div>
        <?php endif; ?>
      </div>

      <!-- Message -->
      <div style="font-size:.7rem;color:#6B7280;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.5rem;margin-top:.9rem;">Message (optionnel)</div>
      <textarea id="inp-msg" rows="2" maxlength="200" placeholder="Un mot d'encouragement… (max 200 car.)" oninput="validateMessageDon()" style="width:100%;background:#1F2232;border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.7rem 1rem;color:#F0F2FF;font-size:.83rem;resize:none;outline:none;box-sizing:border-box;margin-bottom:.25rem;transition:border .18s;" onfocus="this.style.borderColor='#2DD4BF'" onblur="this.style.borderColor='rgba(255,255,255,.07)'"></textarea>
      <div style="display:flex;justify-content:space-between;margin-bottom:.8rem;">
        <div class="don-field-err" id="err-msg-don" style="margin:0;">⚠ Message trop long (max 200 caractères)</div>
        <span id="cnt-msg" style="font-size:.65rem;color:#6B7280;text-align:right;">0/200</span>
      </div>

      <button onclick="donStep2()" style="width:100%;background:#2DD4BF;color:#0C0E14;border:none;border-radius:10px;padding:.85rem;font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;">Continuer →</button>
    </div>

    <!-- STEP 2 : Résumé -->
    <div id="don-step2" style="display:none;">
      <div class="don-steps-bar"><div class="don-step-dot active"></div><div class="don-step-dot active"></div><div class="don-step-dot"></div></div>
      <div style="font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:800;margin-bottom:.25rem;">Confirmer le don ✅</div>
      <div style="font-size:.78rem;color:#6B7280;margin-bottom:1.4rem;">Vérifiez avant de valider</div>
      <div style="background:#1F2232;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:1.2rem;margin-bottom:1.2rem;">
          <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.07);"><span style="font-size:.78rem;color:#6B7280;">Cagnotte</span><span id="r-cag" style="font-size:.82rem;font-weight:500;">—</span></div>
        <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid rgba(255,255,255,.07);"><span style="font-size:.78rem;color:#6B7280;">Montant</span><span id="r-mt" style="font-size:1.1rem;font-weight:700;color:#2DD4BF;font-family:'DM Mono',monospace;">—</span></div>
        <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.07);"><span style="font-size:.78rem;color:#6B7280;">Paiement</span><span id="r-pay" style="font-size:.82rem;font-weight:500;">—</span></div>
        <div id="r-msg-row" style="display:none;border-top:1px solid rgba(255,255,255,.07);padding-top:.5rem;margin-top:.4rem;"><div style="font-size:.72rem;color:#6B7280;margin-bottom:.25rem;">Message</div><div id="r-msg" style="font-size:.8rem;color:#9CA3AF;font-style:italic;"></div></div>
      </div>
      <!-- Stripe card element (carte only) -->
      <div id="stripe-card-container" style="display:none;margin-bottom:1rem;">
        <div style="font-size:.7rem;color:#6B7280;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.5rem;">Informations de carte</div>
        <div id="stripe-card-element" style="background:#1F2232;border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:.85rem 1rem;transition:border .18s;"></div>
        <div id="stripe-error" style="display:none;color:#F43F5E;font-size:.75rem;margin-top:.45rem;padding:.4rem .7rem;background:rgba(244,63,94,.08);border-radius:6px;border:1px solid rgba(244,63,94,.2);"></div>
      </div>
      <!-- Virement info -->
      <div id="virement-info-container" style="display:none;margin-bottom:1rem;background:#1F2232;border:1px solid rgba(79,142,247,.2);border-radius:10px;padding:.9rem 1rem;">
        <div style="font-size:.74rem;color:#4F8EF7;font-weight:600;margin-bottom:.35rem;">Paiement par virement</div>
        <div style="font-size:.78rem;color:#9CA3AF;line-height:1.5;">Le débit se fait sur le solde réel du compte sélectionné. Pour un compte devise, le montant est débité directement dans la devise du compte. Pour les autres comptes, le montant reste converti depuis TND avant débit.</div>
      </div>
      <div style="display:flex;gap:.75rem;">
        <button onclick="donStep1()" style="flex:1;background:#1F2232;border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.75rem;color:#9CA3AF;cursor:pointer;font-family:'Syne',sans-serif;font-size:.85rem;">← Modifier</button>
        <button id="btn-confirmer-don" onclick="confirmerDon()" style="flex:2;background:#2DD4BF;color:#0C0E14;border:none;border-radius:10px;padding:.75rem;font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;">Confirmer le don 💙</button>
      </div>
    </div>

    <!-- STEP 3 : Succès -->
    <div id="don-step3" style="display:none;text-align:center;padding:1.5rem 0;">
      <div class="don-steps-bar"><div class="don-step-dot active"></div><div class="don-step-dot active"></div><div class="don-step-dot active"></div></div>
      <div style="font-size:4rem;margin-bottom:1rem;">🎉</div>
      <div style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;margin-bottom:.5rem;">Don envoyé !</div>
      <div style="font-size:.85rem;color:#6B7280;margin-bottom:1.5rem;">Merci pour votre générosité. Votre don est en cours de traitement.</div>
      <div id="r-final" style="background:#1F2232;border:1px solid rgba(45,212,191,.3);border-radius:12px;padding:1rem;margin-bottom:1.5rem;font-size:1.4rem;font-weight:700;color:#2DD4BF;font-family:'DM Mono',monospace;"></div>
      <button onclick="closeDon()" style="background:#2DD4BF;color:#0C0E14;border:none;border-radius:10px;padding:.75rem 2.5rem;font-family:'Syne',sans-serif;font-weight:700;cursor:pointer;font-size:.95rem;">Fermer</button>
    </div>
  </div>
</div>

    <?php endif; ?>

  <!-- HISTORY VIEW -->
  <div class="view" id="view-history">
    <div class="content">
      <?php
        $userRole = $_SESSION['role'] ?? 'donor';
        $isAssoc = ($userRole === 'association' || $currentUserIsAssociation);
        $histCagnottes = $cagCtrl->getUserCagnottes($selectedUserId);
        $histDons = $donCtrl->getDonsByDonateur($donateurUserId);

        // Summary KPIs
        if ($isAssoc) {
          $histTotal = 0; foreach ($histCagnottes as $hc) $histTotal += floatval($hc['montant_collecte'] ?? 0);
          $histCount = count($histCagnottes);
          $histActive = count(array_filter($histCagnottes, fn($c) => ($c['statut'] ?? '') === 'acceptee'));
        } else {
          $histTotal = 0; foreach ($histDons as $hd) $histTotal += floatval($hd['montant'] ?? 0);
          $histCount = count($histDons);
          $histActive = count(array_filter($histDons, fn($d) => ($d['statut'] ?? '') === 'confirme'));
        }
      ?>

      <!-- KPI Row -->
      <div class="hero-row" style="margin-bottom:1.5rem;">
        <div class="hero-card">
          <div class="hc-label"><?= $isAssoc ? 'Total collecté' : 'Total donné' ?></div>
          <div class="hc-val"><?= number_format($histTotal, 2, ',', ' ') ?><span>€</span></div>
          <div class="hc-badge"><?= $isAssoc ? '↑ Cagnottes actives' : '↑ Dons confirmés' ?></div>
        </div>
        <div class="stat-mini">
          <div class="sm-label">
            <svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <?= $isAssoc ? 'Cagnottes' : 'Dons' ?>
          </div>
          <div class="sm-val" style="color:var(--blue)"><?= $histCount ?></div>
          <div class="sm-sub">Total enregistré</div>
        </div>
        <div class="stat-mini">
          <div class="sm-label">
            <svg width="13" height="13" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <?= $isAssoc ? 'Actives' : 'Confirmés' ?>
          </div>
          <div class="sm-val" style="color:var(--green)"><?= $histActive ?></div>
          <div class="sm-sub"><?= $isAssoc ? 'En cours de collecte' : 'Paiements validés' ?></div>
        </div>
      </div>

      <?php if ($isAssoc): ?>
        <!-- Association : cagnottes créées -->
        <div class="section-head" style="margin-bottom:.9rem">
          <div class="section-title">Mes cagnottes créées</div>
          <span style="font-size:.72rem;color:var(--muted)"><?= $histCount ?> cagnotte<?= $histCount > 1 ? 's' : '' ?></span>
        </div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;">
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Titre</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Objectif</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Collecté</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Progression</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Statut</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Date début</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($histCagnottes)): ?>
                <tr><td colspan="6" style="padding:2rem;text-align:center;color:var(--muted);font-size:.85rem;">Aucune cagnotte créée pour le moment.</td></tr>
              <?php else: foreach ($histCagnottes as $hc):
                $hcObj = floatval($hc['objectif_montant'] ?? 0);
                $hcColl = floatval($hc['montant_collecte'] ?? 0);
                $hcPct = $hcObj > 0 ? min(100, round(($hcColl / $hcObj) * 100)) : 0;
                $hcStatut = $hc['statut'] ?? 'en_attente';
                $hcStatutClass = frontCagnotteStatusClass($hcStatut);
                $hcStatutLabel = frontCagnotteStatusLabel($hcStatut);
              ?>
                <tr style="border-bottom:1px solid var(--border);transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.03)'" onmouseout="this.style.background=''">
                  <td style="padding:.85rem 1.2rem;">
                    <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($hc['titre']) ?></div>
                    <?php if (!empty($hc['categorie'])): ?>
                      <div style="font-size:.68rem;color:var(--muted);margin-top:2px;"><?= htmlspecialchars(ucfirst($hc['categorie'])) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="padding:.85rem 1.2rem;font-size:.82rem;color:var(--muted2);font-family:var(--fm)"><?= number_format($hcObj, 0, ',', ' ') ?> €</td>
                  <td style="padding:.85rem 1.2rem;font-family:var(--fm);font-weight:700;color:var(--teal);"><?= number_format($hcColl, 0, ',', ' ') ?> €</td>
                  <td style="padding:.85rem 1.2rem;min-width:100px;">
                    <div style="height:5px;background:var(--bg3);border-radius:99px;overflow:hidden;margin-bottom:3px;">
                      <div style="height:100%;width:<?= $hcPct ?>%;background:linear-gradient(90deg,var(--teal),var(--blue));border-radius:99px;"></div>
                    </div>
                    <div style="font-size:.65rem;color:var(--muted)"><?= $hcPct ?>%</div>
                  </td>
                  <td style="padding:.85rem 1.2rem;"><span class="<?= htmlspecialchars($hcStatutClass) ?>"><?= htmlspecialchars($hcStatutLabel) ?></span></td>
                  <td style="padding:.85rem 1.2rem;font-size:.78rem;color:var(--muted);"><?= htmlspecialchars(substr($hc['date_debut'] ?? '', 0, 10)) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

      <?php else: ?>
        <!-- Donateur : historique des dons -->
        <div class="section-head" style="margin-bottom:.9rem">
          <div class="section-title">Mes dons</div>
          <span style="font-size:.72rem;color:var(--muted)"><?= $histCount ?> don<?= $histCount > 1 ? 's' : '' ?></span>
        </div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;">
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Cagnotte</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Montant</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Moyen de paiement</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Statut</th>
                <th style="padding:.7rem 1.2rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($histDons)): ?>
                <tr><td colspan="5" style="padding:2rem;text-align:center;color:var(--muted);font-size:.85rem;">Aucun don effectué pour le moment.</td></tr>
              <?php else: foreach ($histDons as $hd):
                $hdStatut = $hd['statut'] ?? 'en_attente';
                $hdStatutClass = match($hdStatut) { 'confirme' => 'status-confirme', 'refuse' => 'status-refuse', default => 'status-attente' };
                $hdStatutLabel = $donCtrl->getDonStatusLabel($hdStatut);
                $hdMoyen = match($hd['moyen_paiement'] ?? '') { 'carte' => '💳 Carte', 'virement' => '🏦 Virement', default => ucfirst($hd['moyen_paiement'] ?? '') };
              ?>
                <tr style="border-bottom:1px solid var(--border);transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.03)'" onmouseout="this.style.background=''">
                  <td style="padding:.85rem 1.2rem;">
                    <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($hd['cagnotte_titre'] ?? '—') ?></div>
                  </td>
                  <td style="padding:.85rem 1.2rem;font-family:var(--fm);font-weight:700;color:var(--teal);">+ <?= number_format(floatval($hd['montant']), 2, ',', ' ') ?> €</td>
                  <td style="padding:.85rem 1.2rem;font-size:.82rem;color:var(--muted2);"><?= $hdMoyen ?></td>
                  <td style="padding:.85rem 1.2rem;"><span class="don-status <?= htmlspecialchars($hdStatutClass) ?>"><?= htmlspecialchars($hdStatutLabel) ?></span></td>
                  <td style="padding:.85rem 1.2rem;font-size:.78rem;color:var(--muted);"><?= htmlspecialchars(substr($hd['date_don'] ?? '', 0, 10)) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>

<!-- SUCCESS CAGNOTTE -->
<div class="success-overlay" id="success-overlay">
  <div class="success-box">
    <div class="success-icon">🎉</div>
    <div class="success-title">Cagnotte soumise !</div>
    <div class="success-sub">Votre cagnotte a été envoyée pour validation. Vous recevrez une notification dès qu'elle sera approuvée.</div>
    <div class="success-badge">
      <strong id="s-titre-final"><?php if ($createSuccess) echo htmlspecialchars($_POST['titre'] ?? ''); else echo '—'; ?></strong>
      En attente de validation • <span id="s-cat-final"><?php if ($createSuccess) echo htmlspecialchars(ucfirst($_POST['categorie'] ?? '')); else echo '—'; ?></span>
    </div>
    <button class="btn-success" onclick="document.getElementById('success-overlay').style.display='none'; showView('dashboard')">Retour au tableau de bord</button>
  </div>
</div>

<!-- AI MODAL -->
<div id="aiModalOverlay" class="ai-modal-overlay hidden">
    <div class="ai-modal-content">
        <div class="ai-modal-header">
            <h2>🤖 Urgent Campaign AI Analysis</h2>
            <button class="ai-modal-close" id="aiModalClose">&times;</button>
        </div>
        
        <div class="ai-modal-body">
            <div id="aiLoadingSpinner" class="ai-spinner">
                <div class="ai-spinner-dot"></div>
                <div class="ai-spinner-dot"></div>
                <div class="ai-spinner-dot"></div>
            </div>
            
            <div id="aiContent" class="ai-content hidden">
                <div class="ai-campaign-card">
                    <h3 id="aiCampaignTitle">Campaign Title</h3>
                    <div class="ai-urgency-badge" id="aiUrgencyBadge">
                        <span class="ai-urgency-score" id="aiUrgencyScore">85%</span>
                        <span class="ai-urgency-label">Urgency</span>
                    </div>
                </div>
                
                <div class="ai-progress-section">
                    <label>Funding Progress</label>
                    <div class="ai-progress-bar">
                        <div class="ai-progress-fill" id="aiProgressFill" style="width: 0%"></div>
                    </div>
                    <p id="aiProgressText">0% funded</p>
                </div>
                
                <div class="ai-explanation-section">
                    <h4>Analysis</h4>
                    <p id="aiExplanation">Loading analysis...</p>
                </div>
            </div>
            
            <div id="aiError" class="ai-error-message hidden">
                <p>Failed to load campaign analysis. Please try again.</p>
            </div>
        </div>
    </div>
</div>

<style>
.btn-ai-urgent {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-ai-urgent:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
}

.ai-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    transition: opacity 0.3s ease;
}

.ai-modal-overlay.hidden {
    display: none;
    opacity: 0;
}

.ai-modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: ai-slideIn 0.3s ease;
}

@keyframes ai-slideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.ai-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.ai-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
}

.ai-modal-close:hover {
    transform: rotate(90deg);
}

.ai-modal-body {
    padding: 24px;
}

.ai-spinner {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 30px 0;
}

.ai-spinner-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #667eea;
    animation: ai-bounce 1.4s infinite;
}

.ai-spinner-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.ai-spinner-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes ai-bounce {
    0%, 80%, 100% { opacity: 0.5; transform: scale(1); }
    40% { opacity: 1; transform: scale(1.2); }
}

.ai-content.hidden {
    display: none;
}

.ai-campaign-card {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #667eea;
}

.ai-campaign-card h3 {
    margin: 0 0 12px 0;
    font-size: 16px;
    color: #333;
}

.ai-urgency-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fee;
    color: #c33;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.ai-urgency-score {
    font-size: 14px;
    font-weight: bold;
}

.ai-progress-section {
    margin-bottom: 20px;
}

.ai-progress-section label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
}

.ai-progress-bar {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.ai-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.5s ease;
}

.ai-progress-section p {
    margin: 8px 0 0 0;
    font-size: 12px;
    color: #999;
}

.ai-explanation-section {
    background: #f0f2f5;
    padding: 16px;
    border-radius: 8px;
}

.ai-explanation-section h4 {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #333;
    font-weight: 600;
}

.ai-explanation-section p {
    margin: 0;
    font-size: 13px;
    line-height: 1.6;
    color: #555;
}

.ai-error-message {
    background: #fee;
    color: #c33;
    padding: 16px;
    border-radius: 8px;
    text-align: center;
}

.ai-error-message.hidden {
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const aiBtn = document.getElementById('aiUrgentBtn');
    const modalOverlay = document.getElementById('aiModalOverlay');
    const modalClose = document.getElementById('aiModalClose');
    const loadingSpinner = document.getElementById('aiLoadingSpinner');
    const aiContent = document.getElementById('aiContent');
    const aiError = document.getElementById('aiError');

    if (aiBtn) {
        aiBtn.addEventListener('click', function() {
            modalOverlay.classList.remove('hidden');
            loadingSpinner.style.display = 'flex';
            aiContent.classList.add('hidden');
            aiError.classList.add('hidden');
            
            fetchAIAnalysis();
        });
    }

    if (modalClose) {
        modalClose.addEventListener('click', function() {
            modalOverlay.classList.add('hidden');
        });
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                modalOverlay.classList.add('hidden');
            }
        });
    }

    function fetchAIAnalysis() {
        fetch('<?= APP_URL ?>/controller/UrgentCampaignController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=suggest_urgent'
        })
        .then(function(response) {
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            return response.json();
        })
        .then(function(data) {
            loadingSpinner.style.display = 'none';
            if (data.ok && data.data) {
                displayAIContent(data.data);
            } else {
                showError(data.error || '');
            }
        })
        .catch(function(error) {
            console.error('AI fetch error:', error);
            loadingSpinner.style.display = 'none';
            showError(error.message || '');
        });
    }

    function displayAIContent(d) {
        document.getElementById('aiCampaignTitle').textContent = d.title || 'Campaign';
        document.getElementById('aiUrgencyScore').textContent = d.urgency_score + '%';
        var pct = d.metrics ? (d.metrics.percentage_funded || 0) : 0;
        document.getElementById('aiProgressFill').style.width = pct + '%';
        var raised = d.metrics ? d.metrics.raised_amount : 0;
        var goal = d.metrics ? d.metrics.goal_amount : 0;
        var daysLeft = d.metrics ? d.metrics.days_left : '?';
        document.getElementById('aiProgressText').textContent = Math.round(pct) + '% financé — ' + raised + ' / ' + goal + ' € · ' + daysLeft + ' j restants';
        document.getElementById('aiExplanation').textContent = d.explanation || '';
        aiContent.classList.remove('hidden');
    }

    function showError(msg) {
        aiError.classList.remove('hidden');
        if (msg) {
            var p = aiError.querySelector('p');
            if (p) p.textContent = msg;
        }
    }
});
</script>

<script>
  
function showView(v) {
  document.querySelectorAll('.view').forEach(el => el.classList.remove('active'));
  document.getElementById('view-' + v).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  if (v === 'dashboard') {
    document.getElementById('nav-apercu').classList.add('active');
    document.getElementById('topbar-title').textContent = 'Tableau de bord';
    document.getElementById('topbar-back').style.display = 'none';
    document.getElementById('topbar-dashboard-btns').style.display = 'flex';
  } else if (v === 'creer') {
    var navCreer = document.getElementById('nav-creer');
    if (navCreer) navCreer.classList.add('active');
    document.getElementById('topbar-title').textContent = 'Créer une cagnotte';
    document.getElementById('topbar-back').style.display = 'block';
    document.getElementById('topbar-dashboard-btns').style.display = 'none';
    // Reset to create mode if coming from navigation (not openEdit)
    if (!isEditMode) {
      document.getElementById('create-action').value = 'create_cagnotte';
      document.getElementById('create-id').value = '';
      document.getElementById('form-is-edit').value = '0';
    }
    checkStepper();
  } else if (v === 'history') {
    document.querySelector('a[onclick="showView(\'history\')"]')?.classList.add('active');
    document.getElementById('topbar-title').textContent = 'Historique';
    document.getElementById('topbar-back').style.display = 'block';
    document.getElementById('topbar-dashboard-btns').style.display = 'none';
  }
}

function setField(inputId, errId, okId, iconId, isValid, errMsg, okMsg) {
  var el = document.getElementById(inputId);
  var err = document.getElementById(errId);
  var ok = okId ? document.getElementById(okId) : null;
  var icon = iconId ? document.getElementById(iconId) : null;
  if (!el || el.value.trim() === '' && !isValid) {
    if(el){ el.classList.remove('valid','error'); }
    if(err){ err.classList.remove('visible'); }
    if(ok){ ok.classList.remove('visible'); }
    if(icon){ icon.textContent=''; icon.classList.remove('visible'); }
    return;
  }
  el.classList.toggle('valid', isValid);
  el.classList.toggle('error', !isValid);
  if(err){ err.textContent = '⚠ ' + errMsg; err.classList.toggle('visible', !isValid); }
  if(ok){ ok.textContent = '✔ ' + okMsg; ok.classList.toggle('visible', isValid); }
  if(icon){ icon.textContent = isValid ? '✔' : '✖'; icon.style.color = isValid ? 'var(--green)' : 'var(--rose)'; icon.classList.add('visible'); }
  checkStepper();
}

var selectedCat = '';
var isEditMode = false;
var uiToDbCategory = {medical:'sante', education:'education', humanitaire:'solidarite', urgence:'autre'};
var dbToUiCategory = {sante:'medical', education:'education', solidarite:'humanitaire', autre:'urgence', medical:'medical', humanitaire:'humanitaire', urgence:'urgence'};
var catEmojis = {medical:'🏥', education:'📚', urgence:'⚡', humanitaire:'🌍'};
var catBg = {
  medical:'linear-gradient(135deg,rgba(244,63,94,.15),rgba(244,63,94,.05))',
  education:'linear-gradient(135deg,rgba(79,142,247,.15),rgba(79,142,247,.05))',
  urgence:'linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.05))',
  humanitaire:'linear-gradient(135deg,rgba(45,212,191,.15),rgba(45,212,191,.05))'
};

function selectCat(cat) {
  ['medical','education','urgence','humanitaire'].forEach(c => { document.getElementById('cat-' + c).className = 'cat-btn'; });
  document.getElementById('cat-' + cat).className = 'cat-btn sel-' + cat;
  selectedCat = cat;
  document.getElementById('err-cat').classList.remove('visible');
  var h = document.getElementById('inp-categorie'); if (h) h.value = uiToDbCategory[cat] || cat;
  updatePv(); checkStepper();
}

function validateTitre() {
  var v = document.getElementById('inp-titre').value.trim();
  if (v === '') { document.getElementById('inp-titre').classList.remove('valid','error'); document.getElementById('err-titre').classList.remove('visible'); document.getElementById('ok-titre').classList.remove('visible'); checkStepper(); return; }
  var ok = v.length >= 10 && v.length <= 80;
  setField('inp-titre','err-titre','ok-titre','icon-titre', ok, 'Minimum 10 caractères requis', 'Titre valide');
}

function validateDesc() {
  var v = document.getElementById('inp-desc').value.trim();
  if (v === '') { document.getElementById('inp-desc').classList.remove('valid','error'); document.getElementById('err-desc').classList.remove('visible'); document.getElementById('ok-desc').classList.remove('visible'); checkStepper(); return; }
  var ok = v.length >= 30;
  setField('inp-desc','err-desc','ok-desc',null, ok, 'Minimum 30 caractères requis', 'Description valide');
}

function validateObjectif() {
  var v = parseFloat(document.getElementById('inp-objectif').value);
  var raw = document.getElementById('inp-objectif').value;
  if (raw === '') { document.getElementById('inp-objectif').classList.remove('valid','error'); document.getElementById('err-objectif').classList.remove('visible'); document.getElementById('ok-objectif').classList.remove('visible'); document.getElementById('icon-objectif').classList.remove('visible'); checkStepper(); return; }
  var ok = !isNaN(v) && v >= 100 && v <= 9999999;
  setField('inp-objectif','err-objectif','ok-objectif','icon-objectif', ok, 'Montant minimum 100 €', 'Montant valide');
}

function validateDates() {
  var debut = document.getElementById('inp-debut').value;
  var fin = document.getElementById('inp-fin').value;
  var today = new Date(); today.setHours(0,0,0,0);

  // Date début
  var el = document.getElementById('inp-debut');
  var err = document.getElementById('err-debut');
  if (debut) {
    var dp = debut.split('-'); var d = new Date(parseInt(dp[0]), parseInt(dp[1])-1, parseInt(dp[2]));
    var startOk = true;
    el.classList.toggle('valid', startOk); el.classList.toggle('error', !startOk);
    err.textContent = startOk ? '⚠ Date de début requise' : '⚠ Date de début antérieure à aujourd\'hui interdite';
    err.classList.toggle('visible', !startOk);
  } else {
    el.classList.remove('valid','error'); err.classList.remove('visible');
  }

  // Date fin
  var el2 = document.getElementById('inp-fin');
  var err2 = document.getElementById('err-fin');
  if (fin && debut) {
    var ok = new Date(fin) >= new Date(debut);
    el2.classList.toggle('valid', ok); el2.classList.toggle('error', !ok);
    err2.classList.toggle('visible', !ok);
  } else if (!fin) {
    el2.classList.add('error'); err2.textContent = '⚠ Date de fin requise'; err2.classList.add('visible');
  } else if (fin && !debut) {
    el2.classList.add('error'); err2.textContent = '⚠ Saisissez d\'abord la date de début'; err2.classList.add('visible');
  } else {
    el2.classList.remove('valid','error'); err2.classList.remove('visible');
  }
  checkStepper();
}

function validateBenef() {
  var v = document.getElementById('inp-benef').value.trim();
  var el = document.getElementById('inp-benef');
  var err = document.getElementById('err-benef');
  if (v === '') { el.classList.remove('valid','error'); err.classList.remove('visible'); return; }
  var ok = v.length >= 2;
  el.classList.toggle('valid', ok); el.classList.toggle('error', !ok);
  err.classList.toggle('visible', !ok);
}

function validateBanner(e) {
  var file = e.target.files[0];
  var err = document.getElementById('err-banner');
  if (!file) return;
  var validTypes = ['image/jpeg','image/jpg','image/png'];
  var maxSize = 5 * 1024 * 1024; // 5MB
  if (!validTypes.includes(file.type) || file.size > maxSize) {
    err.classList.add('visible'); return;
  }
  err.classList.remove('visible');
  // Preview
  var reader = new FileReader();
  reader.onload = function(ev) {
    var zone = document.getElementById('banner-zone');
    var img = document.getElementById('banner-prev');
    img.src = ev.target.result; zone.classList.add('has-img');
    var pvImg = document.getElementById('pv-banner-img');
    pvImg.src = ev.target.result; pvImg.style.display = 'block';
    document.getElementById('pv-emoji').style.display = 'none';
  };
  reader.readAsDataURL(file);
}

function countC(inputId, countId, max) {
  document.getElementById(countId).textContent = document.getElementById(inputId).value.length;
}

function updatePv() {
  var titre = document.getElementById('inp-titre').value.trim();
  var desc = document.getElementById('inp-desc').value.trim();
  var obj = document.getElementById('inp-objectif').value;
  var pvTitre = document.getElementById('pv-titre');
  pvTitre.textContent = titre || 'Titre de votre cagnotte';
  pvTitre.style.color = titre ? 'var(--text)' : 'var(--muted)';
  var pvDesc = document.getElementById('pv-desc');
  pvDesc.textContent = desc || 'La description apparaîtra ici…';
  pvDesc.style.color = desc ? 'var(--muted2)' : 'var(--muted)';
  document.getElementById('pv-goal').textContent = obj ? '/ ' + parseInt(obj).toLocaleString('fr') + ' €' : '/ — €';
  if (selectedCat) {
    var pvCat = document.getElementById('pv-cat');
    pvCat.textContent = selectedCat.charAt(0).toUpperCase() + selectedCat.slice(1);
    pvCat.className = 'pv-cat ' + selectedCat;
    document.getElementById('pv-emoji').textContent = catEmojis[selectedCat];
    if (document.getElementById('pv-banner-img').style.display !== 'block') {
      document.getElementById('pv-banner').style.background = catBg[selectedCat];
    }
  }
  // New cagnottes are submitted as pending (en attente). Status is shown only after admin accepts.
  var pvStatus = document.getElementById('pv-status');
  pvStatus.textContent = 'En attente';
  pvStatus.className = 'pv-status-badge pending';
}

// ═══════════════════════════════════════════
// STEPPER
// ═══════════════════════════════════════════
function checkStepper() {
  if (!document.getElementById('stp-1')) return;
  var s1 = selectedCat !== '' &&
            document.getElementById('inp-titre').value.trim().length >= 10 &&
            document.getElementById('inp-desc').value.trim().length >= 30;
  var obj = parseFloat(document.getElementById('inp-objectif').value);
  var debut = document.getElementById('inp-debut').value;
  var fin = document.getElementById('inp-fin').value;
  var today = new Date(); today.setHours(0,0,0,0);
  var _dp = debut ? debut.split('-') : null; var _fp = fin ? fin.split('-') : null;
  var _d = _dp ? new Date(parseInt(_dp[0]),parseInt(_dp[1])-1,parseInt(_dp[2])) : null;
  var _f = _fp ? new Date(parseInt(_fp[0]),parseInt(_fp[1])-1,parseInt(_fp[2])) : null;
  var startDateOk = true;
  var datesOk = debut !== '' && fin !== '' && startDateOk && _f >= _d;
  var s2 = s1 && !isNaN(obj) && obj >= 100 && obj <= 9999999 && datesOk;
  var s3 = s2;
  setStep(1, true, s1);
  setStep(2, s1, s2);
  setStep(3, s2, s3);
  setStep(4, s3, s3);
  var sl = function(id, done){ var e=document.getElementById(id); if(e) e.className='step-line'+(done?' done':''); };
  sl('sline-1', s1); sl('sline-2', s2); sl('sline-3', s3);
}
function setStep(n, isActive, isDone) {
  var el = document.getElementById('stp-' + n);
  if (!el) return;
  el.className = 'step' + (isDone ? ' done' : (isActive ? ' active' : ''));
}

function validateForm() {
  var ok = true;
  if (!selectedCat) { document.getElementById('err-cat').classList.add('visible'); ok = false; }
  var titre = document.getElementById('inp-titre').value.trim();
  if (titre.length < 10) { document.getElementById('inp-titre').classList.add('error'); document.getElementById('err-titre').classList.add('visible'); ok=false; }
  var desc = document.getElementById('inp-desc').value.trim();
  if (desc.length < 30) { document.getElementById('inp-desc').classList.add('error'); document.getElementById('err-desc').classList.add('visible'); ok=false; }
  var obj = parseFloat(document.getElementById('inp-objectif').value);
  if (!obj || obj < 100 || obj > 9999999) { document.getElementById('inp-objectif').classList.add('error'); document.getElementById('err-objectif').classList.add('visible'); ok=false; }
  var debut = document.getElementById('inp-debut').value;
  if (!debut) { document.getElementById('inp-debut').classList.add('error'); document.getElementById('err-debut').classList.add('visible'); ok=false; }
  var fin = document.getElementById('inp-fin').value;
  var today = new Date(); today.setHours(0,0,0,0);
  if (!fin) { document.getElementById('inp-fin').classList.add('error'); document.getElementById('err-fin').textContent='⚠ Date de fin requise'; document.getElementById('err-fin').classList.add('visible'); ok=false; }
  if (fin && debut && new Date(fin) < new Date(debut)) { document.getElementById('inp-fin').classList.add('error'); document.getElementById('err-fin').classList.add('visible'); ok=false; }
  return ok;
}

function soumettre() {
  if (!validateForm()) {
    var fb = document.getElementById('front-form-feedback');
    if (fb) fb.style.display = 'block';
    // Scroll to first error
    var firstErr = document.querySelector('.fi.error');
    if (firstErr) firstErr.scrollIntoView({behavior:'smooth', block:'center'});
    return;
  }
  var fb2 = document.getElementById('front-form-feedback');
  if (fb2) fb2.style.display = 'none';
  // submit server-side (controller will validate). The preview overlay will be shown after server redirects back on success.
  var form = document.getElementById('create-cagnotte-form');
  if (form) form.submit();
}

function saveDraft() {
  var btn = document.getElementById('btn-draft');
  btn.textContent = '✓ Sauvegardé'; btn.style.color = 'var(--teal)'; btn.style.borderColor = 'rgba(45,212,191,.3)';
  setTimeout(function() { btn.textContent = '💾 Brouillon'; btn.style.color=''; btn.style.borderColor=''; }, 2000);
}

// ═══════════════════════════════════════════
// VALIDATION DON
// ═══════════════════════════════════════════
function validateMontantDon() {
  var el = document.getElementById('inp-mt');
  var err = document.getElementById('err-mt-don');
  var v = parseFloat(el.value);
  var raw = el.value;
  updateVirementAccountUI();
  if (raw === '') { el.style.borderColor='rgba(255,255,255,.07)'; err.classList.remove('show'); return; }
  var ok = !isNaN(v) && v >= 1 && v <= 999999;
  el.style.borderColor = ok ? '#2DD4BF' : '#F43F5E';
  err.classList.toggle('show', !ok);
}

function validateMessageDon() {
  var el = document.getElementById('inp-msg');
  var cnt = document.getElementById('cnt-msg');
  var err = document.getElementById('err-msg-don');
  var len = el.value.length;
  cnt.textContent = len + '/300';
  cnt.style.color = len > 280 ? '#F59E0B' : '#6B7280';
  err.classList.toggle('show', len > 300);
  if (len > 300) el.style.borderColor = '#F43F5E';
  else el.style.borderColor = 'rgba(255,255,255,.07)';
}

function donStep2() {
  var m = parseFloat(document.getElementById('inp-mt').value);
  var raw = document.getElementById('inp-mt').value;
  // Validate montant
  if (raw === '' || isNaN(m) || m < 1 || m > 999999) {
    document.getElementById('inp-mt').style.borderColor = '#F43F5E';
    document.getElementById('err-mt-don').classList.add('show');
    return;
  }
  // Validate message length
  if (document.getElementById('inp-msg').value.length > 300) {
    document.getElementById('err-msg-don').classList.add('show');
    return;
  }
  if (payOn === 'virement') {
    var compteSel = document.getElementById('don-id-compte');
    if (!compteSel || !compteSel.value) {
      alert('Sélectionnez un compte courant actif pour le virement.');
      return;
    }
  }
  document.getElementById('r-mt').textContent = m.toFixed(2) + ' €';
  document.getElementById('r-pay').textContent = {carte:'Carte bancaire', virement:'Virement bancaire'}[payOn];
  var tgt = document.getElementById('don-target-title');
  if (tgt) document.getElementById('r-cag').textContent = tgt.textContent;
  var msg = document.getElementById('inp-msg').value.trim();
  if (msg) { document.getElementById('r-msg').textContent = msg; document.getElementById('r-msg-row').style.display = 'block'; }
  else { document.getElementById('r-msg-row').style.display = 'none'; }
  ['don-step1','don-step2','don-step3'].forEach(s => document.getElementById(s).style.display = s === 'don-step2' ? 'block' : 'none');
  var cardCont = document.getElementById('stripe-card-container');
  var virCont  = document.getElementById('virement-info-container');
  if (payOn === 'carte') {
    if (cardCont) cardCont.style.display = 'block';
    if (virCont)  virCont.style.display  = 'none';
    mountStripeCard();
  } else {
    if (cardCont) cardCont.style.display = 'none';
    if (virCont)  virCont.style.display  = 'block';
  }
}

// ═══════════════════════════════════════════
// MODAL DON
// ═══════════════════════════════════════════
var payOn = 'carte';
var donRates = {
  TND: { TND: 1.000, EUR: 0.297, USD: 0.323, GBP: 0.254 },
  EUR: { TND: 3.366, EUR: 1.000, USD: 1.087, GBP: 0.856 },
  USD: { TND: 3.096, EUR: 0.920, USD: 1.000, GBP: 0.788 },
  GBP: { TND: 3.935, EUR: 1.168, USD: 1.269, GBP: 1.000 }
};

function updateVirementAccountUI() {
  var preview = document.getElementById('don-conversion-preview');
  var sel = document.getElementById('don-id-compte');
  var mtEl = document.getElementById('inp-mt');
  if (!preview || !sel || !mtEl) return;
  var mt = parseFloat(mtEl.value || '0');
  var opt = sel.options[sel.selectedIndex];
  if (!opt || isNaN(mt) || mt <= 0) {
    preview.textContent = '';
    return;
  }
  var devise = String(opt.getAttribute('data-devise') || 'TND').toUpperCase();
  var accountType = String(opt.getAttribute('data-type') || '').toLowerCase();
  var solde = parseFloat(opt.getAttribute('data-solde') || '0');
  var shouldConvert = !(accountType === 'devise' || devise === 'TND');
  var rate = shouldConvert && (donRates.TND && donRates.TND[devise]) ? donRates.TND[devise] : 1;
  var debit = Math.round((mt * rate) * 1000) / 1000;
  var suffix = solde >= debit ? 'Solde suffisant' : 'Solde insuffisant';
  preview.textContent = shouldConvert
    ? 'Debit estime: ' + debit.toFixed(3) + ' ' + devise + ' (conversion depuis TND) · ' + suffix
    : 'Debit estime: ' + debit.toFixed(3) + ' ' + devise + ' (aucune conversion) · ' + suffix;
}

// ── Stripe ────────────────────────────────────────────────────────────────────
var stripe          = Stripe('<?= htmlspecialchars(STRIPE_PUBLISHABLE_KEY, ENT_QUOTES) ?>');
var stripeCardElement = null;

function mountStripeCard() {
  if (stripeCardElement) {
    try { stripeCardElement.unmount(); } catch(e) {}
    stripeCardElement.destroy();
    stripeCardElement = null;
  }
  var elements = stripe.elements();
  stripeCardElement = elements.create('card', {
    style: {
      base: {
        color: '#F0F2FF',
        fontFamily: "'DM Mono', monospace, sans-serif",
        fontSmoothing: 'antialiased',
        fontSize: '14px',
        '::placeholder': { color: '#6B7280' }
      },
      invalid: { color: '#F43F5E', iconColor: '#F43F5E' }
    }
  });
  stripeCardElement.mount('#stripe-card-element');
  stripeCardElement.on('change', function(event) {
    var errEl = document.getElementById('stripe-error');
    if (event.error) { errEl.textContent = event.error.message; errEl.style.display = 'block'; }
    else { errEl.style.display = 'none'; }
  });
}

function openDon(id, title) {
  try {
    var hid = document.getElementById('inp-cag-id');
    if (hid) hid.value = id ? id : '';
    var tgt = document.getElementById('don-target-title');
    if (tgt) tgt.textContent = title ? title : '— Sélectionnez une cagnotte —';
  } catch (e) {}
  document.getElementById('don-overlay').style.display='flex'; donStep1();
}
function closeDon() {
  document.getElementById('don-overlay').style.display = 'none';
  if (stripeCardElement) { try { stripeCardElement.unmount(); } catch(e) {} stripeCardElement.destroy(); stripeCardElement = null; }
}
function setMt(v) {
  document.getElementById('inp-mt').value = v;
  document.querySelectorAll('.qbtn').forEach(b => b.classList.toggle('qsel', parseInt(b.textContent) === v));
  document.getElementById('inp-mt').style.borderColor = '#2DD4BF';
  document.getElementById('err-mt-don').classList.remove('show');
  updateVirementAccountUI();
}
function selPay(v) {
  payOn = v;
  ['carte','virement'].forEach(function(p){ document.getElementById('lbl-'+p).classList.toggle('psel', p===v); });
  var box = document.getElementById('virement-account-box');
  if (box) box.style.display = v === 'virement' ? 'block' : 'none';
  if (v === 'virement') updateVirementAccountUI();
}
function donStep1() {
  ['don-step1','don-step2','don-step3'].forEach(s => document.getElementById(s).style.display = s === 'don-step1' ? 'block' : 'none');
  if (stripeCardElement) { try { stripeCardElement.unmount(); } catch(e) {} stripeCardElement.destroy(); stripeCardElement = null; }
}

function confirmerDon() {
  var montant    = parseFloat(document.getElementById('inp-mt').value) || 0;
  var idCagnotte = document.getElementById('inp-cag-id').value || '';
  var message    = document.getElementById('inp-msg').value.trim();

  // ── Virement: existing server-side form flow ──────────────────────────────
  if (payOn === 'virement') {
    var compteSel = document.getElementById('don-id-compte');
    if (!compteSel || !compteSel.value) {
      alert('Aucun compte courant actif selectionne pour le virement.');
      return;
    }
    var selectedOption = compteSel.options[compteSel.selectedIndex];
    var accountType = String(selectedOption ? (selectedOption.getAttribute('data-type') || '') : '').toLowerCase();
    var deviseDon = 'TND';
    if (accountType === 'devise' && selectedOption) {
      deviseDon = String(selectedOption.getAttribute('data-devise') || 'TND').toUpperCase();
    }
    var form = document.getElementById('create-don-form');
    if (form) {
      form.querySelector('input[name="montant"]').value       = montant;
      form.querySelector('input[name="id_cagnotte"]').value   = idCagnotte;
      form.querySelector('input[name="moyen_paiement"]').value = 'virement';
      form.querySelector('input[name="id_compte"]').value      = compteSel.value;
      form.querySelector('input[name="devise_don"]').value     = deviseDon;
      form.querySelector('input[name="message"]').value       = message;
      form.submit();
    }
    return;
  }

  // ── Carte: Stripe payment flow ────────────────────────────────────────────
  if (!stripeCardElement) return;
  var btn   = document.getElementById('btn-confirmer-don');
  var errEl = document.getElementById('stripe-error');
  btn.disabled    = true;
  btn.textContent = 'Traitement…';
  if (errEl) errEl.style.display = 'none';

  // Step 1: ask server to create a PaymentIntent
  fetch('<?= APP_URL ?>/controller/StripePaymentController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ action: 'create_intent', montant: montant, id_cagnotte: idCagnotte })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.error) throw new Error(data.error);
    // Step 2: confirm the payment via Stripe.js (handles 3DS etc.)
    return stripe.confirmCardPayment(data.client_secret, {
      payment_method: { card: stripeCardElement }
    });
  })
  .then(function(result) {
    if (result.error) throw new Error(result.error.message);
    // Step 3: record the don in the DB (server verifies payment status)
    return fetch('<?= APP_URL ?>/controller/StripePaymentController.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action:             'confirm_don',
        payment_intent_id:  result.paymentIntent.id,
        montant:            montant,
        id_cagnotte:        idCagnotte,
        message:            message
      })
    });
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.error) throw new Error(data.error);
    document.getElementById('r-final').textContent = montant.toFixed(2) + ' €';
    ['don-step1','don-step2','don-step3'].forEach(function(s) {
      document.getElementById(s).style.display = s === 'don-step3' ? 'block' : 'none';
    });
  })
  .catch(function(err) {
    if (errEl) { errEl.textContent = err.message || 'Erreur de paiement'; errEl.style.display = 'block'; }
    btn.disabled    = false;
    btn.textContent = 'Confirmer le don 💙';
  });
}
document.getElementById('don-overlay').addEventListener('click', function(e) { if(e.target===this) closeDon(); });

// ═══════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════
window.addEventListener('load', function() {
  document.getElementById('inp-debut').valueAsDate = new Date();
  var todayStr = new Date().toISOString().split('T')[0];
  document.getElementById('inp-debut').setAttribute('min', todayStr);
  document.getElementById('inp-fin').setAttribute('min', todayStr);
  document.getElementById('inp-debut').addEventListener('change', function(){
    var start = document.getElementById('inp-debut').value || todayStr;
    document.getElementById('inp-fin').setAttribute('min', start);
  });
  ['inp-titre','inp-desc','inp-objectif','inp-debut','inp-fin'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) { el.addEventListener('input', checkStepper); el.addEventListener('change', checkStepper); }
  });
  var form = document.getElementById('create-cagnotte-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      if (!validateForm()) {
        e.preventDefault();
        var fb = document.getElementById('front-form-feedback');
        if (fb) fb.style.display = 'block';
      }
    });
  }
  checkStepper();
});
</script>
<script>
function openEdit(id, title, desc, categorie, objectif, datedebut, datefin) {
  try {
    // Prefill the create form and switch it to update mode
    isEditMode = true;
    document.getElementById('form-is-edit').value = '1';
    document.getElementById('create-action').value = 'update_cagnotte';
    document.getElementById('create-id').value = id;
    if (typeof title !== 'undefined') document.getElementById('inp-titre').value = title;
    if (typeof desc !== 'undefined') document.getElementById('inp-desc').value = desc;
    if (typeof categorie !== 'undefined') {
      var uiCategory = dbToUiCategory[String(categorie || '').toLowerCase()] || '';
      if (uiCategory) selectCat(uiCategory);
    }
    if (typeof objectif !== 'undefined') document.getElementById('inp-objectif').value = objectif;
    if (typeof datedebut !== 'undefined' && datedebut) document.getElementById('inp-debut').value = datedebut;
    if (typeof datefin !== 'undefined' && datefin) document.getElementById('inp-fin').value = datefin;
    updatePv(); checkStepper();
    showView('creer');
  } catch (e) { console.error(e); }
}
</script>
<!-- Hidden create-don form (submitted by JS) -->
<form id="create-don-form" method="post" style="display:none;">
  <input type="hidden" name="action" value="create_don" />
  <input type="hidden" name="id_cagnotte" value="" />
  <input type="hidden" name="montant" value="" />
  <input type="hidden" name="moyen_paiement" value="carte" />
  <input type="hidden" name="id_compte" value="" />
  <input type="hidden" name="devise_don" value="TND" />
  <input type="hidden" name="message" value="" />
</form>

<?php if (!empty($donSuccess)): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  try {
    document.getElementById('don-overlay').style.display='flex';
    ['don-step1','don-step2','don-step3'].forEach(s => document.getElementById(s).style.display = s === 'don-step3' ? 'block' : 'none');
    var mt = '<?= isset($_GET['don_amount']) ? number_format((float)$_GET['don_amount'],2,',',' ') : '—' ?> €';
    document.getElementById('r-final').textContent = mt;
  } catch(e){}
});
</script>
<?php endif; ?>
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

