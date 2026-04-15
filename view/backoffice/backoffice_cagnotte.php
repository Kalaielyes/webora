<?php
require_once __DIR__ . "/../../controller/cagnottecontroller.php";
require_once __DIR__ . "/../../controller/doncontroller.php";
$cagCtrl = new cagnottecontroller();
$donCtrl = new doncontroller();
$adminMessage = $_GET['msg'] ?? '';
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

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

function backRedirectWith($params = []) {
  $base = basename(__FILE__);
  $qs = http_build_query($params);
  header('Location: ' . $base . ($qs ? ('?' . $qs) : ''));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $respond = function($ok, $msg) use ($isAjax) {
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => (bool)$ok, 'message' => $msg]);
      exit;
    }
    backRedirectWith(['msg' => $msg]);
  };

  if ($_POST['action'] === 'accept' && isset($_POST['id'])) {
    $ok = $cagCtrl->updateStatusAdmin((int)$_POST['id'], 'acceptee');
    $respond($ok, $ok ? 'Cagnotte validée' : 'Échec validation');
  } elseif ($_POST['action'] === 'refuse' && isset($_POST['id'])) {
    $ok = $cagCtrl->updateStatusAdmin((int)$_POST['id'], 'refusee');
    $respond($ok, $ok ? 'Cagnotte refusée' : 'Échec refus');
  } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
    $ok = $cagCtrl->supprimerCagnotte((int)$_POST['id']);
    $respond($ok, $ok ? 'Cagnotte supprimée' : 'Échec suppression');
  } elseif ($_POST['action'] === 'update_cagnotte' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $data = [];
    if (isset($_POST['titre'])) $data['titre'] = $_POST['titre'];
    if (isset($_POST['description'])) $data['description'] = $_POST['description'];
    if (isset($_POST['categorie'])) $data['categorie'] = $_POST['categorie'];
    if (isset($_POST['objectif_montant'])) $data['objectif_montant'] = $_POST['objectif_montant'];
    if (isset($_POST['date_debut'])) $data['date_debut'] = $_POST['date_debut'];
    if (isset($_POST['date_fin'])) $data['date_fin'] = $_POST['date_fin'];
    $ok = $cagCtrl->modifierCagnotte($id, $data);
    $respond($ok, $ok ? 'Cagnotte modifiée' : ($cagCtrl->getLastError() ?: 'Échec modification'));
  } elseif ($_POST['action'] === 'set_status' && isset($_POST['id']) && isset($_POST['new_status'])) {
    $ok = $cagCtrl->updateStatusAdmin((int)$_POST['id'], $_POST['new_status']);
    $respond($ok, $ok ? 'Statut mis à jour' : 'Échec mise à jour statut');
  } elseif ($_POST['action'] === 'confirm_don' && isset($_POST['don_id'])) {
    $ok = $donCtrl->confirmerDon((int)$_POST['don_id']);
    $respond($ok, $ok ? 'Don confirmé' : 'Échec confirmation don');
  } elseif ($_POST['action'] === 'refuse_don' && isset($_POST['don_id'])) {
    $ok = $donCtrl->refuserDon((int)$_POST['don_id']);
    $respond($ok, $ok ? 'Don refusé' : 'Échec refus don');
  }
}

$cagnottes = $cagCtrl->getAllCagnottes();
$dons = $donCtrl->getAllDons();

$selected = !empty($cagnottes) ? $cagnottes[0] : null;
$selectedId = $selected['id_cagnotte'] ?? 0;
$selectedTitre = $selected['titre'] ?? '';
$selectedDescription = $selected['description'] ?? '';
$selectedCategorie = $selected['categorie'] ?? '';
$selectedDateDebut = $selected['date_debut'] ?? '';
$selectedObjectif = $selected['objectif_montant'] ?? 0;
$selectedDateFin = $selected['date_fin'] ?? '';

// Compute admin stats
$activeCount = count($cagCtrl->getAllCagnottes('acceptee'));
$pendingCount = count($cagCtrl->getAllCagnottes('en_attente'));
$confirmedStats = $donCtrl->getConfirmedStats();
$donsToConfirm = 0; foreach ($dons as $dd) { if (($dd['statut'] ?? '') === 'en_attente') $donsToConfirm++; }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Admin — Gestion des Dons &amp; Cagnottes</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>

<link rel="stylesheet" href="<?= htmlspecialchars(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')) ?>/cagnotte.css">
<style>
  /* Fallback: force edit modal popup behavior even if CSS cache is stale */
  #admin-edit-overlay.edit-modal-overlay{display:none;position:fixed;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(2px);align-items:center;justify-content:center;z-index:10050;padding:16px;}
  #admin-edit-overlay .edit-modal-card{width:min(720px,100%);background:linear-gradient(180deg,#ffffff,#f8fbff);border:1px solid #d7e2ee;border-radius:14px;box-shadow:0 20px 45px rgba(15,23,42,.18);padding:16px;animation:editModalIn .2s ease-out;}
  #admin-edit-overlay .edit-modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-family:'Syne',sans-serif;font-size:1rem;}
  #admin-edit-overlay .edit-close-btn{background:#eef3f8;border:1px solid #d4dde8;border-radius:8px;min-width:34px;height:34px;cursor:pointer;color:#334155;}
  #admin-edit-overlay .edit-field{display:flex;flex-direction:column;gap:5px;margin-bottom:10px;}
  #admin-edit-overlay .edit-field label{font-size:.72rem;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.06em;}
  #admin-edit-overlay .edit-field input,
  #admin-edit-overlay .edit-field textarea,
  #admin-edit-overlay .edit-field select{width:100%;border:1px solid #cdd8e4;border-radius:10px;padding:10px 11px;font-family:'DM Sans',sans-serif;font-size:.86rem;outline:none;background:#fff;}
  #admin-edit-overlay .edit-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
  #admin-edit-overlay .edit-modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:4px;}
  @keyframes editModalIn{from{opacity:0;transform:translateY(8px) scale(.98);}to{opacity:1;transform:translateY(0) scale(1);}}
  @media (max-width: 800px){#admin-edit-overlay .edit-grid-2{grid-template-columns:1fr;}}
</style>

</head>
<body>

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
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Vue globale
    </a>
    <div class="nav-section">Cagnottes</div>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Toutes les cagnottes
    </a>
      <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      En attente validation
      <span class="nav-badge"><?= (int)$pendingCount ?></span>
    </a>
    <div class="nav-section">Dons</div>
    <a class="nav-item" href="#dons-moderation">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
      Tous les dons
    </a>
    <a class="nav-item" href="#dons-moderation">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      Dons à confirmer
      <span class="nav-badge"><?= (int)$donsToConfirm ?></span>
    </a>
    <div class="nav-section">Rapports</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Statistiques

    </a>
    <a class="nav-item" href="../frontoffice/frontoffice_cagnotte.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      frontoffice
    </a>
  </nav>
  <div class="sb-footer">
    <div class="sb-status"><div class="status-dot"></div>Système opérationnel</div>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Cagnottes</div>
      <div class="breadcrumb">Admin / Dons &amp; Cagnottes</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input placeholder="Rechercher une cagnotte…"/>
      </div>
    </div>
  </div>

  <div class="content">
    <?php if ($adminMessage !== ''): ?>
      <div style="margin-bottom:12px;padding:10px;border-radius:8px;background:#eef6ff;border:1px solid #c5d9f1;color:#1f3a5c;"><?= htmlspecialchars($adminMessage) ?></div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--teal-light)">
          <svg width="18" height="18" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
          <div class="kpi-val"><?= (int)$activeCount ?></div>
          <div class="kpi-label">Cagnottes actives</div>
          <div class="kpi-sub" style="color:var(--teal)">↑ 0 ce mois</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <div>
          <div class="kpi-val"><?= (int)($confirmedStats['nb_conf'] ?? 0) ?></div>
          <div class="kpi-label">Dons confirmés</div>
          <div class="kpi-sub" style="color:var(--blue)">Ce mois</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="font-size:1.2rem"><?= number_format(($confirmedStats['total_conf'] ?? 0),3,',',' ') ?></div>
          <div class="kpi-label">Total collecté (TND)</div>
          <div class="kpi-sub" style="color:var(--green)">↑ +0% vs mois dernier</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="color:var(--amber)"><?= (int)$pendingCount ?></div>
          <div class="kpi-label">En attente validation</div>
          <div class="kpi-sub" style="color:var(--amber)">Action requise</div>
        </div>
      </div>
    </div>

    <!-- TABLE + DETAIL -->
    <div class="two-col-layout">

      <!-- TABLE CAGNOTTES -->
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des cagnottes</div>
          <div class="filters">
            <button class="filter-btn active">Toutes</button>
            <button class="filter-btn">Actives</button>
            <button class="filter-btn">En attente</button>
            <button class="filter-btn">Terminées</button>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Créateur / Titre</th>
              <th>Catégorie</th>
              <th>Progression</th>
              <th>Montant collecté</th>
              <th>Statut</th>
              <th>Date début</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($cagnottes as $c):
              $objectif = floatval($c['objectif_montant'] ?? 0);
              $collected = floatval($c['total_collecte'] ?? 0);
              $pct = $objectif > 0 ? min(100, round(($collected / $objectif) * 100)) : 0;
          ?>
            <tr class="js-cag-row"
              data-id="<?= (int)$c['id_cagnotte'] ?>"
              data-titre="<?= htmlspecialchars($c['titre'] ?? '', ENT_QUOTES) ?>"
              data-categorie="<?= htmlspecialchars($c['categorie'] ?? '', ENT_QUOTES) ?>"
              data-association="<?= htmlspecialchars($c['association'] ?? '', ENT_QUOTES) ?>"
              data-statut="<?= htmlspecialchars($c['statut'] ?? '', ENT_QUOTES) ?>"
              data-date-debut="<?= htmlspecialchars(substr($c['date_debut'] ?? '',0,10), ENT_QUOTES) ?>"
                data-objectif="<?= number_format($objectif,3,',',' ') ?>"
                data-collected="<?= number_format($collected,3,',',' ') ?>"
              data-pct="<?= (int)$pct ?>"
              data-nom="<?= htmlspecialchars(trim(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? '')), ENT_QUOTES) ?>"
              data-initials="<?= htmlspecialchars(strtoupper(substr(($c['prenom'] ?? ''),0,1) . substr(($c['nom'] ?? ''),0,1)), ENT_QUOTES) ?>"
              data-description="<?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?>"
              data-date-fin="<?= htmlspecialchars(substr($c['date_fin'] ?? '',0,10), ENT_QUOTES) ?>"
              data-objectif-raw="<?= (float)$objectif ?>">
              <td>
                <div class="td-name"><?=htmlspecialchars(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? ''))?></div>
                <div class="td-mono">Org: <?= htmlspecialchars($c['association'] ?? '—') ?></div>
                <div class="td-mono"><?=htmlspecialchars($c['titre'])?></div>
              </td>
              <td><span class="cat-medical"><?=htmlspecialchars($c['categorie'])?></span></td>
              <td>
                <div class="prog-wrap">
                  <div class="prog-bar"><div class="prog-fill" style="width:<?=$pct?>%"></div></div>
                  <div class="prog-pct"><?=$pct?>% — <?=number_format($collected,3,',',' ')?> / <?=number_format($objectif,3,',',' ')?></div>
                </div>
              </td>
              <td><span class="td-bold"><?=number_format($collected,3,',',' ')?></span></td>
              <td>
                <span class="badge <?= htmlspecialchars(cagnotteStatusBadgeClass($c['statut'] ?? 'en_attente')) ?>"><?= htmlspecialchars(cagnotteStatusLabel($c['statut'] ?? 'en_attente')) ?></span>
              </td>
              <td class="td-mono"><?=htmlspecialchars($c['date_debut'])?></td>
              <td>
                <div class="action-group">
                  <button class="act-btn" type="button" title="Voir" onclick="updateFeaturedFromRow(this.closest('tr'))">👁</button>
                  <?php if (($c['statut'] ?? '') !== 'acceptee'): ?>
                    <form method="post" style="display:inline;margin:0;padding:0;">
                      <input type="hidden" name="action" value="accept" />
                      <input type="hidden" name="id" value="<?= (int)$c['id_cagnotte'] ?>" />
                      <button class="act-btn" title="Accepter">✔</button>
                    </form>
                  <?php endif; ?>
                  <?php if (($c['statut'] ?? '') !== 'refusee'): ?>
                    <form method="post" style="display:inline;margin:0;padding:0;">
                      <input type="hidden" name="action" value="refuse" />
                      <input type="hidden" name="id" value="<?= (int)$c['id_cagnotte'] ?>" />
                      <button class="act-btn warn" title="Refuser">✖</button>
                    </form>
                  <?php endif; ?>
                  <button class="act-btn" title="Modifier" onclick="event.stopPropagation();adminOpenEdit(<?= (int)$c['id_cagnotte'] ?>,'<?= htmlspecialchars(addslashes($c['titre'])) ?>','<?= htmlspecialchars(addslashes($c['description'] ?? '')) ?>','<?= htmlspecialchars(addslashes($c['categorie'] ?? '')) ?>','<?= htmlspecialchars($c['date_debut'] ?? '') ?>',<?= (float)($c['objectif_montant'] ?? 0) ?>,'<?= htmlspecialchars($c['date_fin'] ?? '') ?>')">✎</button>
                  <form method="post" style="display:inline;margin:0;padding:0;">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= (int)$c['id_cagnotte'] ?>" />
                    <button class="act-btn danger" title="Supprimer">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- DETAIL PANEL -->
      <div class="detail-panel">
        <?php if ($selected):
            $sel = $selected;
            $selCollected = floatval($sel['total_collecte'] ?? 0);
            $selObjectif = floatval($sel['objectif_montant'] ?? 0);
            $selPct = $selObjectif > 0 ? min(100, round(($selCollected / $selObjectif) * 100)) : 0;
            $creatorName = trim(($sel['nom'] ?? '') . ' ' . ($sel['prenom'] ?? ''));
            $initials = strtoupper(substr(($sel['prenom'] ?? ''),0,1) . substr(($sel['nom'] ?? ''),0,1));
        ?>
        <div class="dp-header">
          <div class="dp-av" id="detail-av"><?= htmlspecialchars($initials) ?></div>
          <div>
            <div class="dp-name" id="detail-name"><?= htmlspecialchars($creatorName ?: ($sel['titre'] ?? '—')) ?></div>
            <div class="dp-meta" id="detail-meta">Créateur — depuis <?= htmlspecialchars(substr($sel['date_debut'] ?? '',0,10)) ?></div>
            <div class="dp-badge" id="detail-badge" style="background:var(--green-light);color:var(--green)"><?= ($sel['statut'] ?? '') === 'acceptee' ? 'Compte vérifié' : 'Compte' ?></div>
          </div>
        </div>

        <div>
          <div class="dp-section">Cagnotte sélectionnée</div>
          <div class="dp-row"><span class="dp-key">Titre</span><span class="dp-val" id="detail-title"><?= htmlspecialchars($sel['titre'] ?? '—') ?></span></div>
          <div class="dp-row"><span class="dp-key">Catégorie</span><span class="cat-medical" id="detail-category"><?= htmlspecialchars($sel['categorie'] ?? '—') ?></span></div>
          <div class="dp-row"><span class="dp-key">Organisation</span><span class="dp-val" id="detail-association"><?= htmlspecialchars($sel['association'] ?? '—') ?></span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="badge <?= htmlspecialchars(cagnotteStatusBadgeClass($sel['statut'] ?? 'en_attente')) ?>" id="detail-status"><?= htmlspecialchars(cagnotteStatusLabel($sel['statut'] ?? 'en_attente')) ?></span></div>
          <div class="dp-row"><span class="dp-key">Date début</span><span class="dp-val" id="detail-date"><?= htmlspecialchars(substr($sel['date_debut'] ?? '',0,10)) ?></span></div>
          <div class="dp-row"><span class="dp-key">Objectif</span><span class="dp-val-mono" id="detail-objectif" style="color:var(--blue)"><?= number_format($selObjectif,3,',',' ') ?> TND</span></div>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-top:.5rem;">
              <span style="color:var(--teal);font-family:var(--fm);font-weight:500" id="detail-collected"><?= number_format($selCollected,3,',',' ') ?> TND collectés</span>
              <span style="color:var(--muted)" id="detail-pct"><?= $selPct ?>%</span>
            </div>
            <div class="prog-detail-bar"><div class="prog-detail-fill" id="detail-fill" style="width:<?= $selPct ?>%"></div></div>
          </div>
        </div>
        <?php else: ?>
        <div style="padding:1rem;color:var(--muted)">Aucune cagnotte sélectionnée</div>
        <?php endif; ?>

        <div>
          <div class="dp-section">Dons récents</div>
          <?php if (empty($dons)): ?>
            <div class="dp-row"><span class="dp-key">Aucun don récent</span><span class="dp-val-mono" style="color:var(--teal)">0</span></div>
          <?php else: foreach (array_slice($dons, 0, 6) as $don): ?>
            <div class="dp-row">
              <span class="dp-key"><?=htmlspecialchars($don['cagnotte_titre'] ?? '—')?></span>
              <span class="dp-val-mono" style="color:var(--teal)"><?=number_format($don['montant'],3,',',' ')?></span>
            </div>
            <?php if (($don['statut'] ?? '') === 'en_attente'): ?>
              <div style="margin:6px 0 8px 0;">
                <form method="post" style="display:inline;margin:0;padding:0;">
                  <input type="hidden" name="action" value="confirm_don" />
                  <input type="hidden" name="don_id" value="<?= (int)$don['id_don'] ?>" />
                  <button class="dp-action-btn da-primary" type="submit">Confirmer</button>
                </form>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
          <div class="dp-row"><span class="dp-key">Total dons</span><span class="dp-val"><?=count($dons)?> donateur(s)</span></div>
          <?php endif; ?>
        </div>

        <div class="dp-actions">
          <form method="post" style="display:inline;margin:0;padding:0;" id="detail-form-accept">
            <input type="hidden" name="action" value="set_status" />
            <input type="hidden" name="id" id="detail-id-accept" value="<?= (int)$selectedId ?>" />
            <input type="hidden" name="new_status" value="acceptee" />
            <button class="dp-action-btn da-primary" type="submit">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Valider
            </button>
          </form>
          <button class="dp-action-btn da-neutral" id="detail-edit-btn" type="button" onclick="adminOpenEdit(<?= (int)$selectedId ?>,'<?= htmlspecialchars(addslashes($selectedTitre)) ?>','<?= htmlspecialchars(addslashes($selectedDescription)) ?>','<?= htmlspecialchars(addslashes($selectedCategorie)) ?>','<?= htmlspecialchars($selectedDateDebut) ?>',<?= (float)$selectedObjectif ?>,'<?= htmlspecialchars($selectedDateFin) ?>')">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Modifier
          </button>
          <form method="post" style="display:inline;margin:0;padding:0;" id="detail-form-suspend">
            <input type="hidden" name="action" value="set_status" />
            <input type="hidden" name="id" id="detail-id-suspend" value="<?= (int)$selectedId ?>" />
            <input type="hidden" name="new_status" value="suspendue" />
            <button class="dp-action-btn da-warning" type="submit">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            Suspendre
            </button>
          </form>
          <form method="post" style="display:inline;margin:0;padding:0;" id="detail-form-close">
            <input type="hidden" name="action" value="set_status" />
            <input type="hidden" name="id" id="detail-id-close" value="<?= (int)$selectedId ?>" />
            <input type="hidden" name="new_status" value="cloturee" />
            <button class="dp-action-btn da-danger" type="submit">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Clôturer
            </button>
          </form>
        </div>
      </div>

    </div>

    <div id="dons-moderation" class="table-card">
      <div class="table-toolbar">
        <div class="table-toolbar-title">Modération des dons (asynchrone)</div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Cagnotte</th>
            <th>Donateur</th>
            <th>Montant</th>
            <th>Paiement</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($dons)): ?>
            <tr><td colspan="7">Aucun don</td></tr>
          <?php else: foreach ($dons as $don): ?>
            <tr id="don-row-<?= (int)$don['id_don'] ?>">
              <td><?= htmlspecialchars($don['cagnotte_titre'] ?? '—') ?></td>
              <td><?= htmlspecialchars(trim(($don['nom'] ?? '') . ' ' . ($don['prenom'] ?? ''))) ?></td>
              <td class="td-bold"><?= number_format((float)$don['montant'], 3, ',', ' ') ?></td>
              <td><?= htmlspecialchars($don['moyen_paiement'] ?? '—') ?></td>
              <td><span class="badge <?= htmlspecialchars($donCtrl->getDonStatusBadgeClass($don['statut'] ?? 'en_attente')) ?>" id="don-status-<?= (int)$don['id_don'] ?>"><?= htmlspecialchars($donCtrl->getDonStatusLabel($don['statut'] ?? 'en_attente')) ?></span></td>
              <td class="td-mono"><?= htmlspecialchars(substr($don['date_don'] ?? '', 0, 19)) ?></td>
              <td>
                <div class="action-group">
                  <?php if (($don['statut'] ?? '') === 'en_attente'): ?>
                    <button class="act-btn js-don-action" data-don-id="<?= (int)$don['id_don'] ?>" data-action="confirm_don" title="Confirmer">✔</button>
                    <button class="act-btn warn js-don-action" data-don-id="<?= (int)$don['id_don'] ?>" data-action="refuse_don" title="Refuser">✖</button>
                  <?php else: ?>
                    <span class="td-mono">Aucune action</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Admin edit overlay -->
<div id="admin-edit-overlay" class="edit-modal-overlay" aria-hidden="true" style="display:none;">
  <form id="admin-edit-form" method="post" class="edit-modal-card" onclick="event.stopPropagation()">
    <input type="hidden" name="action" value="update_cagnotte" />
    <input type="hidden" name="id" id="admin-edit-id" />
    <div class="edit-modal-head"><strong>Modifier la cagnotte</strong><button type="button" onclick="adminCloseEdit()" class="edit-close-btn">✕</button></div>
    <div class="edit-field"><label>Titre</label><input id="admin-edit-titre" name="titre" type="text" /></div>
    <div class="edit-field"><label>Description</label><textarea id="admin-edit-desc" name="description" rows="3"></textarea></div>
    <div class="edit-grid-2">
      <div class="edit-field">
        <label>Catégorie</label>
        <select id="admin-edit-categorie" name="categorie">
          <option value="">-- Sélectionner --</option>
          <option value="sante">Santé</option>
          <option value="education">Éducation</option>
          <option value="solidarite">Solidarité</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="edit-field"><label>Date début</label><input id="admin-edit-datedebut" name="date_debut" type="date" /></div>
    </div>
    <div class="edit-grid-2"><div class="edit-field"><label>Objectif</label><input id="admin-edit-objectif" name="objectif_montant" type="number" step="0.001" /></div><div class="edit-field"><label>Date fin</label><input id="admin-edit-datefin" name="date_fin" type="date" /></div></div>
    <div id="admin-edit-feedback" style="display:none;margin-top:8px;padding:8px 10px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:.78rem;"></div>
    <div class="edit-modal-actions"><button type="button" onclick="adminCloseEdit()" class="btn-ghost">Annuler</button><button type="submit" id="admin-edit-submit" class="btn-primary">Enregistrer</button></div>
  </form>
</div>

<script>
function adminOpenEdit(id, titre, desc, categorie, datedebut, objectif, datefin) {
  try {
    var map = {medical:'sante', 'médical':'sante', sante:'sante', 'santé':'sante', education:'education', 'éducation':'education', humanitaire:'solidarite', solidarite:'solidarite', 'solidarité':'solidarite', urgence:'autre', autre:'autre'};
    var normalizedCategory = map[String(categorie || '').toLowerCase()] || '';
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('admin-edit-id').value = id || '';
    document.getElementById('admin-edit-titre').value = titre || '';
    document.getElementById('admin-edit-desc').value = desc || '';
    document.getElementById('admin-edit-categorie').value = normalizedCategory;
    document.getElementById('admin-edit-datedebut').value = datedebut || '';
    document.getElementById('admin-edit-datedebut').min = today;
    document.getElementById('admin-edit-objectif').value = objectif || '';
    document.getElementById('admin-edit-datefin').value = datefin || '';
    document.getElementById('admin-edit-datefin').min = (datedebut && datedebut > today) ? datedebut : today;
    document.getElementById('admin-edit-feedback').style.display = 'none';
    document.getElementById('admin-edit-feedback').textContent = '';
    var overlay = document.getElementById('admin-edit-overlay');
    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  } catch(e) { console.error(e); }
}

function validateAdminEditForm() {
  var titre = (document.getElementById('admin-edit-titre').value || '').trim();
  var description = (document.getElementById('admin-edit-desc').value || '').trim();
  var categorie = (document.getElementById('admin-edit-categorie').value || '').trim();
  var objectif = parseFloat(document.getElementById('admin-edit-objectif').value || '0');
  var dateDebut = document.getElementById('admin-edit-datedebut').value;
  var dateFin = document.getElementById('admin-edit-datefin').value;
  var today = new Date(); today.setHours(0,0,0,0);

  if (titre.length < 10) return 'Le titre doit contenir au moins 10 caractères.';
  if (description.length < 30) return 'La description doit contenir au moins 30 caractères.';
  if (!categorie) return 'Veuillez sélectionner une catégorie.';
  if (isNaN(objectif) || objectif < 100) return 'L\'objectif minimum est 100.';
  if (!dateDebut) return 'La date de début est obligatoire.';
  if (!dateFin) return 'La date de fin est obligatoire.';
  if (new Date(dateDebut) < today) return 'La date de début ne peut pas être antérieure à aujourd\'hui.';
  if (new Date(dateFin) < new Date(dateDebut)) return 'La date de fin doit être supérieure ou égale à la date de début.';
  return '';
}

function updateFeaturedFromRow(row) {
  if (!row) return;
  var id = row.dataset.id || '0';
  var titre = row.dataset.titre || '—';
  var categorie = row.dataset.categorie || '—';
  var association = row.dataset.association || '—';
  var statut = row.dataset.statut || '—';
  var dateDebut = row.dataset.dateDebut || '—';
  var objectif = row.dataset.objectif || '0';
  var collected = row.dataset.collected || '0';
  var pct = row.dataset.pct || '0';
  var nom = row.dataset.nom || titre;
  var initials = row.dataset.initials || '--';
  var description = row.dataset.description || '';
  var dateFin = row.dataset.dateFin || '';
  var objectifRaw = row.dataset.objectifRaw || '0';

  var statusEl = document.getElementById('detail-status');
  var cagnotteLabels = {
    en_attente: 'En attente',
    acceptee: 'Acceptée',
    refusee: 'Refusée',
    suspendue: 'Suspendue',
    cloturee: 'Clôturée'
  };
  var cagnotteClasses = {
    en_attente: 'b-attente',
    acceptee: 'b-active',
    refusee: 'b-danger',
    suspendue: 'b-suspend',
    cloturee: 'b-termine'
  };
  statusEl.textContent = cagnotteLabels[statut] || 'En attente';
  statusEl.className = 'badge ' + (cagnotteClasses[statut] || 'b-attente');

  document.getElementById('detail-av').textContent = initials;
  document.getElementById('detail-name').textContent = nom;
  document.getElementById('detail-meta').textContent = 'Créateur — depuis ' + dateDebut;
  document.getElementById('detail-badge').textContent = (statut === 'acceptee') ? 'Compte vérifié' : 'Compte';
  document.getElementById('detail-title').textContent = titre;
  document.getElementById('detail-category').textContent = categorie;
  document.getElementById('detail-association').textContent = association;
  document.getElementById('detail-date').textContent = dateDebut;
  document.getElementById('detail-objectif').textContent = objectif + ' TND';
  document.getElementById('detail-collected').textContent = collected + ' TND collectés';
  document.getElementById('detail-pct').textContent = pct + '%';
  document.getElementById('detail-fill').style.width = pct + '%';

  document.getElementById('detail-id-accept').value = id;
  document.getElementById('detail-id-suspend').value = id;
  document.getElementById('detail-id-close').value = id;
  document.getElementById('detail-edit-btn').setAttribute('onclick', "adminOpenEdit(" + id + "," + JSON.stringify(titre) + "," + JSON.stringify(description) + "," + JSON.stringify(categorie) + "," + JSON.stringify(dateDebut) + "," + objectifRaw + "," + JSON.stringify(dateFin) + ")");

  document.querySelectorAll('.js-cag-row').forEach(function(r){ r.style.background = ''; });
  row.style.background = '#f8fafc';
}
function adminCloseEdit(){
  try{
    var overlay = document.getElementById('admin-edit-overlay');
    overlay.style.display='none';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  } catch(e){}
}

document.addEventListener('DOMContentLoaded', function() {
  var overlay = document.getElementById('admin-edit-overlay');
  if (overlay) {
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.addEventListener('click', adminCloseEdit);
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') adminCloseEdit();
  });

  var editForm = document.getElementById('admin-edit-form');
  if (editForm) {
    editForm.addEventListener('submit', function(e) {
      var msg = validateAdminEditForm();
      var feedback = document.getElementById('admin-edit-feedback');
      if (msg) {
        e.preventDefault();
        feedback.textContent = msg;
        feedback.style.display = 'block';
      } else {
        feedback.textContent = '';
        feedback.style.display = 'none';
      }
    });
  }

  var first = document.querySelector('.js-cag-row');
  if (first) updateFeaturedFromRow(first);
});

document.querySelectorAll('.js-don-action').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var donId = this.getAttribute('data-don-id');
    var action = this.getAttribute('data-action');
    var fd = new FormData();
    fd.append('action', action);
    fd.append('don_id', donId);

    fetch('backoffice_cagnotte.php?ajax=1', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data || !data.ok) {
          alert((data && data.message) ? data.message : 'Erreur action don');
          return;
        }
        var statusEl = document.getElementById('don-status-' + donId);
        if (statusEl) {
          statusEl.textContent = (action === 'confirm_don') ? 'Confirmé' : 'Refusé';
          statusEl.className = 'badge ' + ((action === 'confirm_don') ? 'b-confirme' : 'b-danger');
        }
        if (btn.parentElement) {
          btn.parentElement.innerHTML = '<span class="td-mono">Aucune action</span>';
        }
      })
      .catch(function(){ alert('Erreur réseau'); });
  });
});
</script>
</body>
</html>
