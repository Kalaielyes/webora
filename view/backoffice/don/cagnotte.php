<?php
require_once __DIR__ . '/../../../models/config.php';
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . "/../../../controller/CagnotteController.php";
require_once __DIR__ . "/../../../controller/DonController.php";
require_once __DIR__ . "/helpers.php";
Session::start();
Session::requireAdmin();

$cagCtrl = new CagnotteController();
$donCtrl = new DonController();
$adminMessage = $_GET['msg'] ?? '';
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

$cagFilters = $cagCtrl->buildAdminFilters([
    'status' => $_GET['status'] ?? '',
    'query' => $_GET['query'] ?? '',
    'category' => $_GET['category'] ?? ''
]);

$preservedFilters = array_filter([
    'status' => $cagFilters['status'],
    'query' => $cagFilters['query'],
    'category' => $cagFilters['category']
], static function ($value) {
    return $value !== '' && $value !== null;
});

function backRedirectWith($params = [], $preserved = []) {
    $base = basename(__FILE__);
    $qs = http_build_query(array_merge($preserved, $params));
    header('Location: ' . APP_URL . '/view/backoffice/don/cagnotte.php' . ($qs ? ('?' . $qs) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $respond = function ($ok, $msg) use ($isAjax, $preservedFilters) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => (bool)$ok, 'message' => $msg]);
            exit;
        }
        backRedirectWith(['msg' => $msg], $preservedFilters);
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

$cagnottes = $cagCtrl->getFilteredCagnottes($cagFilters);
$allDons = $donCtrl->getAllDons();
$recentDons = array_slice($allDons, 0, 6);

$selected = !empty($cagnottes) ? $cagnottes[0] : null;
$selectedId = $selected['id_cagnotte'] ?? 0;
$selectedTitre = $selected['titre'] ?? '';
$selectedDescription = $selected['description'] ?? '';
$selectedCategorie = $selected['categorie'] ?? '';
$selectedDateDebut = substr((string)($selected['date_debut'] ?? ''), 0, 10);
$selectedObjectif = $selected['objectif_montant'] ?? 0;
$selectedDateFin = substr((string)($selected['date_fin'] ?? ''), 0, 10);

$cagnotteStatusCounts = $cagCtrl->getCagnotteStatusCounts();
$donStatusCounts = $donCtrl->getDonStatusCounts();
$confirmedStats = $donCtrl->getConfirmedStats();
$overviewStats = $cagCtrl->getAdminOverviewStats();
$pendingCount = (int)($cagnotteStatusCounts['en_attente'] ?? 0);
$activeCount = (int)($cagnotteStatusCounts['acceptee'] ?? 0);
$donsToConfirm = (int)($donStatusCounts['en_attente'] ?? 0);
$statusOptions = [
    '' => 'Toutes',
    'acceptee' => 'Actives',
    'en_attente' => 'En attente',
    'cloturee' => 'Terminées'
];
$categoryOptions = [
    '' => 'Toutes les catégories',
    'sante' => 'Santé',
    'education' => 'Éducation',
    'solidarite' => 'Solidarité',
    'autre' => 'Autre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Admin - Gestion des cagnottes</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backoffice/Utilisateur.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backoffice/don.css">
</head>
<body>
<?php renderBackofficeSidebar('cagnottes'); ?>

<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Cagnottes</div>
      <div class="breadcrumb">Admin / Cagnottes</div>
    </div>
    <div class="tb-right">
      <form method="get" class="search-bar search-bar-form">
        <?php if ($cagFilters['status'] !== ''): ?>
          <input type="hidden" name="status" value="<?= htmlspecialchars($cagFilters['status']) ?>" />
        <?php endif; ?>
        <?php if ($cagFilters['category'] !== ''): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($cagFilters['category']) ?>" />
        <?php endif; ?>
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input name="query" value="<?= htmlspecialchars($cagFilters['query']) ?>" placeholder="Rechercher par titre ou catégorie..."/>
      </form>
    </div>
  </div>

  <div class="content">
    <?php if ($adminMessage !== ''): ?>
      <div class="flash-message"><?= htmlspecialchars($adminMessage) ?></div>
    <?php endif; ?>

    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--teal-light)">
          <svg width="18" height="18" fill="none" stroke="var(--teal)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
          <div class="kpi-val"><?= $activeCount ?></div>
          <div class="kpi-label">Cagnottes actives</div>
          <div class="kpi-sub" style="color:var(--teal)"><?= (int)($overviewStats['total_cagnottes'] ?? 0) ?> au total</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <div>
          <div class="kpi-val"><?= (int)($confirmedStats['nb_conf'] ?? 0) ?></div>
          <div class="kpi-label">Dons confirmés</div>
          <div class="kpi-sub" style="color:var(--blue)"><?= $donsToConfirm ?> à traiter</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="font-size:1.2rem"><?= number_format((float)($confirmedStats['total_conf'] ?? 0), 3, ',', ' ') ?></div>
          <div class="kpi-label">Montants confirmés (TND)</div>
          <div class="kpi-sub" style="color:var(--green)">Objectif global <?= number_format((float)($overviewStats['total_objectif'] ?? 0), 3, ',', ' ') ?></div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="color:var(--amber)"><?= $pendingCount ?></div>
          <div class="kpi-label">En attente validation</div>
          <div class="kpi-sub" style="color:var(--amber)"><?= (int)($cagnotteStatusCounts['refusee'] ?? 0) ?> refusées</div>
        </div>
      </div>
    </div>

    <div class="two-col-layout">
      <div class="table-card">
        <div class="table-toolbar table-toolbar-stack">
          <div class="admin-filters-grid compact-filters">
            <div class="filter-block">
              <div class="filter-block-title">Statut</div>
              <div class="filters filters-links filter-chip-group">
              <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                <?php
                  $statusParams = [
                      'status' => $statusValue,
                      'query' => $cagFilters['query'],
                      'category' => $cagFilters['category']
                  ];
                  $statusParams = array_filter($statusParams, static function ($value) {
                      return $value !== '' && $value !== null;
                  });
                ?>
                <a class="filter-btn filter-chip <?= $cagFilters['status'] === $statusValue ? 'active' : '' ?>" href="<?= backofficeBuildUrl('cagnotte.php', $statusParams) ?>"><?= htmlspecialchars($statusLabel) ?></a>
              <?php endforeach; ?>
            </div>
            </div>
            <div class="filter-block">
              <div class="filter-block-title">Catégorie</div>
              <div class="filters filters-links filter-chip-group">
                <?php foreach ($categoryOptions as $categoryValue => $categoryLabel): ?>
                  <?php
                    $categoryParams = [
                        'status' => $cagFilters['status'],
                        'query' => $cagFilters['query'],
                        'category' => $categoryValue
                    ];
                    $categoryParams = array_filter($categoryParams, static function ($value) {
                        return $value !== '' && $value !== null;
                    });
                  ?>
                  <a class="filter-btn filter-chip <?= $cagFilters['category'] === $categoryValue ? 'active' : '' ?>" href="<?= backofficeBuildUrl('cagnotte.php', $categoryParams) ?>"><?= htmlspecialchars($categoryLabel) ?></a>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="filter-actions-row">
              <a class="btn-ghost" href="<?= backofficeBuildUrl('cagnotte.php') ?>">Réinitialiser</a>
            </div>
          </div>
        </div>

        <div class="table-scroll">
        <table class="cagnotte-table">
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
          <?php if (empty($cagnottes)): ?>
            <tr>
              <td colspan="7">
                <div class="empty-state compact-empty-state">
                  <div class="empty-state-title">Aucune cagnotte trouvée</div>
                  <div class="empty-state-text">Modifiez les filtres ou relancez une recherche plus large.</div>
                </div>
              </td>
            </tr>
          <?php else: foreach ($cagnottes as $c):
              $objectif = (float)($c['objectif_montant'] ?? 0);
              $collected = (float)($c['total_collecte'] ?? 0);
              $pct = $objectif > 0 ? min(100, round(($collected / $objectif) * 100)) : 0;
              $categoryKey = $cagCtrl->sanitizeCategoryFilter($c['categorie'] ?? '');
          ?>
            <tr class="js-cag-row"
              data-id="<?= (int)$c['id_cagnotte'] ?>"
              data-titre="<?= htmlspecialchars($c['titre'] ?? '', ENT_QUOTES) ?>"
              data-categorie="<?= htmlspecialchars(cagnotteCategoryLabel($categoryKey !== '' ? $categoryKey : (string)($c['categorie'] ?? '')), ENT_QUOTES) ?>"
              data-association="<?= ($c['association'] ?? 0) ? 'Association' : 'Particulier' ?>"
              data-statut="<?= htmlspecialchars($c['statut'] ?? '', ENT_QUOTES) ?>"
              data-date-debut="<?= htmlspecialchars(substr((string)($c['date_debut'] ?? ''), 0, 10), ENT_QUOTES) ?>"
              data-objectif="<?= htmlspecialchars(number_format($objectif, 3, ',', ' '), ENT_QUOTES) ?>"
              data-collected="<?= htmlspecialchars(number_format($collected, 3, ',', ' '), ENT_QUOTES) ?>"
              data-pct="<?= (int)$pct ?>"
              data-nom="<?= htmlspecialchars(trim(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? '')), ENT_QUOTES) ?>"
              data-initials="<?= htmlspecialchars(strtoupper(substr((string)($c['prenom'] ?? ''), 0, 1) . substr((string)($c['nom'] ?? ''), 0, 1)), ENT_QUOTES) ?>"
              data-description="<?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?>"
              data-date-fin="<?= htmlspecialchars(substr((string)($c['date_fin'] ?? ''), 0, 10), ENT_QUOTES) ?>"
              data-objectif-raw="<?= (float)$objectif ?>">
              <td>
                <div class="td-name"><?= htmlspecialchars(trim(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? ''))) ?></div>
                <div class="td-mono"><?= ($c['association'] ?? 0) ? 'Association' : 'Particulier' ?></div>
                <div class="td-mono"><?= htmlspecialchars($c['titre'] ?? '—') ?></div>
              </td>
              <td><span class="<?= htmlspecialchars(cagnotteCategoryBadgeClass($categoryKey !== '' ? $categoryKey : (string)($c['categorie'] ?? ''))) ?>"><?= htmlspecialchars(cagnotteCategoryLabel($categoryKey !== '' ? $categoryKey : (string)($c['categorie'] ?? ''))) ?></span></td>
              <td>
                <div class="prog-wrap prog-wrap-wide">
                  <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%"></div></div>
                  <div class="prog-pct"><?= $pct ?>% — <?= number_format($collected, 3, ',', ' ') ?> / <?= number_format($objectif, 3, ',', ' ') ?></div>
                </div>
              </td>
              <td><span class="td-bold"><?= number_format($collected, 3, ',', ' ') ?></span></td>
              <td><span class="badge <?= htmlspecialchars(cagnotteStatusBadgeClass($c['statut'] ?? 'en_attente')) ?>"><?= htmlspecialchars(cagnotteStatusLabel($c['statut'] ?? 'en_attente')) ?></span></td>
              <td class="td-mono"><?= htmlspecialchars(substr((string)($c['date_debut'] ?? ''), 0, 10)) ?></td>
              <td>
                <div class="action-group">
                  <button class="act-btn" type="button" title="Voir" onclick="updateFeaturedFromRow(this.closest('tr'))">👁</button>
                  <?php if (($c['statut'] ?? '') !== 'acceptee'): ?>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="action" value="accept" />
                      <input type="hidden" name="id" value="<?= (int)$c['id_cagnotte'] ?>" />
                      <button class="act-btn" title="Accepter">✔</button>
                    </form>
                  <?php endif; ?>
                  <?php if (($c['statut'] ?? '') !== 'refusee'): ?>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="action" value="refuse" />
                      <input type="hidden" name="id" value="<?= (int)$c['id_cagnotte'] ?>" />
                      <button class="act-btn warn" title="Refuser">✖</button>
                    </form>
                  <?php endif; ?>
                  <button class="act-btn" type="button" title="Modifier" onclick="event.stopPropagation();adminOpenEdit(<?= (int)$c['id_cagnotte'] ?>, <?= htmlspecialchars(json_encode($c['titre'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($c['description'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($c['categorie'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode(substr((string)($c['date_debut'] ?? ''), 0, 10)), ENT_QUOTES) ?>, <?= (float)($c['objectif_montant'] ?? 0) ?>, <?= htmlspecialchars(json_encode(substr((string)($c['date_fin'] ?? ''), 0, 10)), ENT_QUOTES) ?>)">✎</button>
                  <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette cagnotte ?');">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= (int)$c['id_cagnotte'] ?>" />
                    <button class="act-btn danger" title="Supprimer">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
        </div>
      </div>

      <div class="detail-panel">
        <?php if ($selected):
            $selCollected = (float)($selected['total_collecte'] ?? 0);
            $selObjectif = (float)($selected['objectif_montant'] ?? 0);
            $selPct = $selObjectif > 0 ? min(100, round(($selCollected / $selObjectif) * 100)) : 0;
            $creatorName = trim(($selected['nom'] ?? '') . ' ' . ($selected['prenom'] ?? ''));
            $initials = strtoupper(substr((string)($selected['prenom'] ?? ''), 0, 1) . substr((string)($selected['nom'] ?? ''), 0, 1));
            $selectedCategoryKey = $cagCtrl->sanitizeCategoryFilter($selected['categorie'] ?? '');
        ?>
        <div class="dp-header">
          <div class="dp-av" id="detail-av"><?= htmlspecialchars($initials) ?></div>
          <div>
            <div class="dp-name" id="detail-name"><?= htmlspecialchars($creatorName !== '' ? $creatorName : ($selected['titre'] ?? '—')) ?></div>
            <div class="dp-meta" id="detail-meta">Créateur — depuis <?= htmlspecialchars(substr((string)($selected['date_debut'] ?? ''), 0, 10)) ?></div>
            <div class="dp-badge" id="detail-badge" style="background:var(--green-light);color:var(--green)"><?= ($selected['statut'] ?? '') === 'acceptee' ? 'Compte vérifié' : 'Compte à vérifier' ?></div>
          </div>
        </div>

        <div>
          <div class="dp-section">Cagnotte sélectionnée</div>
          <div class="dp-row"><span class="dp-key">Titre</span><span class="dp-val" id="detail-title"><?= htmlspecialchars($selected['titre'] ?? '—') ?></span></div>
          <div class="dp-row"><span class="dp-key">Catégorie</span><span class="<?= htmlspecialchars(cagnotteCategoryBadgeClass($selectedCategoryKey !== '' ? $selectedCategoryKey : (string)($selected['categorie'] ?? ''))) ?>" id="detail-category"><?= htmlspecialchars(cagnotteCategoryLabel($selectedCategoryKey !== '' ? $selectedCategoryKey : (string)($selected['categorie'] ?? ''))) ?></span></div>
          <div class="dp-row"><span class="dp-key">Type</span><span class="dp-val" id="detail-association"><?= ($selected['association'] ?? 0) ? 'Association' : 'Particulier' ?></span></div>
          <div class="dp-row"><span class="dp-key">Statut</span><span class="badge <?= htmlspecialchars(cagnotteStatusBadgeClass($selected['statut'] ?? 'en_attente')) ?>" id="detail-status"><?= htmlspecialchars(cagnotteStatusLabel($selected['statut'] ?? 'en_attente')) ?></span></div>
          <div class="dp-row"><span class="dp-key">Date début</span><span class="dp-val" id="detail-date"><?= htmlspecialchars(substr((string)($selected['date_debut'] ?? ''), 0, 10)) ?></span></div>
          <div class="dp-row"><span class="dp-key">Objectif</span><span class="dp-val-mono" id="detail-objectif" style="color:var(--blue)"><?= number_format($selObjectif, 3, ',', ' ') ?> TND</span></div>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-top:.5rem;">
              <span style="color:var(--teal);font-family:var(--fm);font-weight:500" id="detail-collected"><?= number_format($selCollected, 3, ',', ' ') ?> TND collectés</span>
              <span style="color:var(--muted)" id="detail-pct"><?= $selPct ?>%</span>
            </div>
            <div class="prog-detail-bar"><div class="prog-detail-fill" id="detail-fill" style="width:<?= $selPct ?>%"></div></div>
          </div>
        </div>

        <div>
          <div class="dp-section">Dons récents</div>
          <?php if (empty($recentDons)): ?>
            <div class="dp-row"><span class="dp-key">Aucun don récent</span><span class="dp-val-mono" style="color:var(--teal)">0</span></div>
          <?php else: foreach ($recentDons as $don): ?>
            <div class="dp-row">
              <span class="dp-key"><?= htmlspecialchars($don['cagnotte_titre'] ?? '—') ?></span>
              <span class="dp-val-mono" style="color:var(--teal)"><?= number_format((float)($don['montant'] ?? 0), 3, ',', ' ') ?></span>
            </div>
            <?php if (($don['statut'] ?? '') === 'en_attente'): ?>
              <div style="margin:6px 0 8px 0;">
                <form method="post" class="inline-form">
                  <input type="hidden" name="action" value="confirm_don" />
                  <input type="hidden" name="don_id" value="<?= (int)$don['id_don'] ?>" />
                  <button class="dp-action-btn da-primary" type="submit">Confirmer</button>
                </form>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
          <div class="dp-row"><span class="dp-key">Total dons</span><span class="dp-val"><?= count($allDons) ?> don(s)</span></div>
          <?php endif; ?>
        </div>

        <div class="dp-actions">
          <form method="post" class="inline-form" id="detail-form-accept">
            <input type="hidden" name="action" value="set_status" />
            <input type="hidden" name="id" id="detail-id-accept" value="<?= (int)$selectedId ?>" />
            <input type="hidden" name="new_status" value="acceptee" />
            <button class="dp-action-btn da-primary" type="submit">Valider</button>
          </form>
          <button class="dp-action-btn da-neutral" id="detail-edit-btn" type="button" onclick="adminOpenEdit(<?= (int)$selectedId ?>, <?= htmlspecialchars(json_encode($selectedTitre), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($selectedDescription), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($selectedCategorie), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($selectedDateDebut), ENT_QUOTES) ?>, <?= (float)$selectedObjectif ?>, <?= htmlspecialchars(json_encode($selectedDateFin), ENT_QUOTES) ?>)">Modifier</button>
          <form method="post" class="inline-form" id="detail-form-suspend">
            <input type="hidden" name="action" value="set_status" />
            <input type="hidden" name="id" id="detail-id-suspend" value="<?= (int)$selectedId ?>" />
            <input type="hidden" name="new_status" value="suspendue" />
            <button class="dp-action-btn da-warning" type="submit">Suspendre</button>
          </form>
          <form method="post" class="inline-form" id="detail-form-close">
            <input type="hidden" name="action" value="set_status" />
            <input type="hidden" name="id" id="detail-id-close" value="<?= (int)$selectedId ?>" />
            <input type="hidden" name="new_status" value="cloturee" />
            <button class="dp-action-btn da-danger" type="submit">Clôturer</button>
          </form>
        </div>
        <?php else: ?>
        <div class="empty-state compact-empty-state">
          <div class="empty-state-title">Aucune cagnotte sélectionnée</div>
          <div class="empty-state-text">Appliquez un filtre différent ou ouvrez une cagnotte depuis la liste.</div>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<div id="admin-edit-overlay" class="edit-modal-overlay" aria-hidden="true" style="display:none;">
  <form id="admin-edit-form" method="post" class="edit-modal-card" onclick="event.stopPropagation()" novalidate>
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
    <div class="edit-grid-2">
      <div class="edit-field"><label>Objectif</label><input id="admin-edit-objectif" name="objectif_montant" type="number" step="0.001" /></div>
      <div class="edit-field"><label>Date fin</label><input id="admin-edit-datefin" name="date_fin" type="date" /></div>
    </div>
    <div id="admin-edit-feedback" class="edit-feedback"></div>
    <div class="edit-modal-actions"><button type="button" onclick="adminCloseEdit()" class="btn-ghost">Annuler</button><button type="submit" id="admin-edit-submit" class="btn-primary">Enregistrer</button></div>
  </form>
</div>

<script>
function adminOpenEdit(id, titre, desc, categorie, datedebut, objectif, datefin) {
  var map = {medical: 'sante', 'médical': 'sante', sante: 'sante', 'santé': 'sante', education: 'education', 'éducation': 'education', humanitaire: 'solidarite', solidarite: 'solidarite', 'solidarité': 'solidarite', urgence: 'autre', autre: 'autre'};
  var normalizedCategory = map[String(categorie || '').toLowerCase()] || '';
  var today = new Date().toISOString().split('T')[0];
  document.getElementById('admin-edit-id').value = id || '';
  document.getElementById('admin-edit-titre').value = titre || '';
  document.getElementById('admin-edit-desc').value = desc || '';
  document.getElementById('admin-edit-categorie').value = normalizedCategory;
  document.getElementById('admin-edit-datedebut').value = datedebut || '';
  document.getElementById('admin-edit-objectif').value = objectif || '';
  document.getElementById('admin-edit-datefin').value = datefin || '';
  var feedback = document.getElementById('admin-edit-feedback');
  feedback.style.display = 'none';
  feedback.textContent = '';
  var overlay = document.getElementById('admin-edit-overlay');
  overlay.style.display = 'flex';
  overlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function updateFeaturedFromRow(row) {
  if (!row) return;
  var id = row.dataset.id || '0';
  var titre = row.dataset.titre || '—';
  var categorie = row.dataset.categorie || '—';
  var association = row.dataset.association || '—';
  var statut = row.dataset.statut || 'en_attente';
  var dateDebut = row.dataset.dateDebut || '—';
  var objectif = row.dataset.objectif || '0';
  var collected = row.dataset.collected || '0';
  var pct = row.dataset.pct || '0';
  var nom = row.dataset.nom || titre;
  var initials = row.dataset.initials || '--';
  var description = row.dataset.description || '';
  var dateFin = row.dataset.dateFin || '';
  var objectifRaw = row.dataset.objectifRaw || '0';

  var labels = {en_attente: 'En attente', acceptee: 'Acceptée', refusee: 'Refusée', suspendue: 'Suspendue', cloturee: 'Clôturée'};
  var classes = {en_attente: 'b-attente', acceptee: 'b-active', refusee: 'b-danger', suspendue: 'b-suspend', cloturee: 'b-termine'};
  var statusEl = document.getElementById('detail-status');
  statusEl.textContent = labels[statut] || 'En attente';
  statusEl.className = 'badge ' + (classes[statut] || 'b-attente');

  document.getElementById('detail-av').textContent = initials;
  document.getElementById('detail-name').textContent = nom;
  document.getElementById('detail-meta').textContent = 'Créateur — depuis ' + dateDebut;
  document.getElementById('detail-badge').textContent = statut === 'acceptee' ? 'Compte vérifié' : 'Compte à vérifier';
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
  document.getElementById('detail-edit-btn').onclick = function () {
    adminOpenEdit(id, titre, description, categorie, dateDebut, objectifRaw, dateFin);
  };

  document.querySelectorAll('.js-cag-row').forEach(function (currentRow) {
    currentRow.classList.remove('row-selected');
  });
  row.classList.add('row-selected');
}

function adminCloseEdit() {
  var overlay = document.getElementById('admin-edit-overlay');
  overlay.style.display = 'none';
  overlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function () {
  var overlay = document.getElementById('admin-edit-overlay');
  overlay.addEventListener('click', adminCloseEdit);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      adminCloseEdit();
    }
  });

  var editForm = document.getElementById('admin-edit-form');
  editForm.addEventListener('submit', function (event) {
    var feedback = document.getElementById('admin-edit-feedback');
    feedback.textContent = '';
    feedback.style.display = 'none';
  });

  var firstRow = document.querySelector('.js-cag-row');
  if (firstRow) {
    updateFeaturedFromRow(firstRow);
  }
});
</script>
</body>
</html>
