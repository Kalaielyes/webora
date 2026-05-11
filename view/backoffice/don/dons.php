<?php
require_once __DIR__ . '/../../../models/config.php';
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . "/../../../controller/DonController.php";
require_once __DIR__ . "/../../../controller/CagnotteController.php";
require_once __DIR__ . "/helpers.php";
Session::start();
Session::requireAdmin();

$donCtrl = new DonController();
$cagCtrl = new CagnotteController();
$message = $_GET['msg'] ?? '';

$donFilters = $donCtrl->buildAdminFilters([
  'query' => $_GET['query'] ?? '',
    'status' => $_GET['status'] ?? '',
    'cagnotte_query' => $_GET['cagnotte_query'] ?? '',
    'donor_query' => $_GET['donor_query'] ?? '',
    'payment_method' => $_GET['payment_method'] ?? '',
    'date_start' => $_GET['date_start'] ?? '',
    'date_end' => $_GET['date_end'] ?? ''
]);

$preservedFilters = array_filter($donFilters, static function ($value) {
    return $value !== '' && $value !== null;
});

function donsRedirectWith($params = [], $preserved = []) {
    $base = basename(__FILE__);
    $qs = http_build_query(array_merge($preserved, $params));
    header('Location: ' . $base . ($qs ? ('?' . $qs) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['don_id'])) {
    $id = (int)$_POST['don_id'];
    if ($_POST['action'] === 'confirm_don') {
        $ok = $donCtrl->confirmerDon($id);
        donsRedirectWith(['msg' => $ok ? 'Don confirmé' : 'Échec confirmation'], $preservedFilters);
    } elseif ($_POST['action'] === 'refuse_don') {
        $ok = $donCtrl->refuserDon($id);
        donsRedirectWith(['msg' => $ok ? 'Don refusé' : 'Échec refus'], $preservedFilters);
    }
}

$dons = $donCtrl->getFilteredDons($donFilters);
$donStatusCounts = $donCtrl->getDonStatusCounts();
$donGlobalStats = $donCtrl->getGlobalStats();
$confirmedStats = $donCtrl->getConfirmedStats();
$paymentStats = $donCtrl->getPaymentMethodStats();
$cagnotteStatusCounts = $cagCtrl->getCagnotteStatusCounts();

$pendingCount = (int)($cagnotteStatusCounts['en_attente'] ?? 0);
$donsToConfirm = (int)($donStatusCounts['en_attente'] ?? 0);
$statusOptions = [
    '' => 'Tous les statuts',
    'en_attente' => 'En attente',
    'confirme' => 'Confirmé',
    'refuse' => 'Refusé'
];
$paymentOptions = [
    '' => 'Tous les paiements',
    'carte' => 'Carte',
    'virement' => 'Virement'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin - Gestion des dons</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backoffice/Utilisateur.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backoffice/don.css">
</head>
<body>
<?php renderBackofficeSidebar('dons'); ?>

<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Dons</div>
      <div class="breadcrumb">Admin / Dons</div>
    </div>
    <div class="tb-right">
      <form method="get" class="search-bar search-bar-form">
        <?php if ($donFilters['status'] !== ''): ?>
          <input type="hidden" name="status" value="<?= htmlspecialchars($donFilters['status']) ?>" />
        <?php endif; ?>
        <?php if ($donFilters['payment_method'] !== ''): ?>
          <input type="hidden" name="payment_method" value="<?= htmlspecialchars($donFilters['payment_method']) ?>" />
        <?php endif; ?>
        <?php if ($donFilters['date_start'] !== ''): ?>
          <input type="hidden" name="date_start" value="<?= htmlspecialchars($donFilters['date_start']) ?>" />
        <?php endif; ?>
        <?php if ($donFilters['date_end'] !== ''): ?>
          <input type="hidden" name="date_end" value="<?= htmlspecialchars($donFilters['date_end']) ?>" />
        <?php endif; ?>
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input name="query" value="<?= htmlspecialchars($donFilters['query']) ?>" placeholder="Rechercher cagnotte ou donateur..."/>
      </form>
      <a class="btn-ghost" href="<?= backofficeBuildUrl('stats.php') ?>">Voir les statistiques</a>
    </div>
  </div>

  <div class="content">
    <?php if ($message !== ''): ?>
      <div class="flash-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <div>
          <div class="kpi-val"><?= (int)($donGlobalStats['total_dons'] ?? 0) ?></div>
          <div class="kpi-label">Dons enregistrés</div>
          <div class="kpi-sub" style="color:var(--blue)"><?= count($dons) ?> résultat(s) affiché(s)</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="color:var(--amber)"><?= $donsToConfirm ?></div>
          <div class="kpi-label">Dons en attente</div>
          <div class="kpi-sub" style="color:var(--amber)">Modération requise</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="font-size:1.2rem"><?= number_format((float)($confirmedStats['total_conf'] ?? 0), 3, ',', ' ') ?></div>
          <div class="kpi-label">Montant confirmé (TND)</div>
          <div class="kpi-sub" style="color:var(--green)"><?= (int)($confirmedStats['nb_conf'] ?? 0) ?> dons confirmés</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--rose-light)">
          <svg width="18" height="18" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div>
          <div class="kpi-val" style="color:var(--rose)"><?= (int)($donStatusCounts['refuse'] ?? 0) ?></div>
          <div class="kpi-label">Dons refusés</div>
          <div class="kpi-sub" style="color:var(--rose)">Historique contrôlé</div>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-toolbar table-toolbar-stack">
        <div class="admin-filters-grid admin-filters-grid-wide compact-filters">
          <div class="filter-block">
            <div class="filter-block-title">Statut</div>
            <div class="filters filters-links filter-chip-group">
              <?php foreach ($statusOptions as $value => $label): ?>
                <?php
                  $statusParams = [
                      'query' => $donFilters['query'],
                      'status' => $value,
                      'payment_method' => $donFilters['payment_method'],
                      'date_start' => $donFilters['date_start'],
                      'date_end' => $donFilters['date_end']
                  ];
                  $statusParams = array_filter($statusParams, static function ($item) {
                      return $item !== '' && $item !== null;
                  });
                ?>
                <a class="filter-btn filter-chip <?= $donFilters['status'] === $value ? 'active' : '' ?>" href="<?= backofficeBuildUrl('dons.php', $statusParams) ?>"><?= htmlspecialchars($label) ?></a>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="filter-block">
            <div class="filter-block-title">Paiement</div>
            <div class="filters filters-links filter-chip-group">
              <?php foreach ($paymentOptions as $value => $label): ?>
                <?php
                  $paymentParams = [
                      'query' => $donFilters['query'],
                      'status' => $donFilters['status'],
                      'payment_method' => $value,
                      'date_start' => $donFilters['date_start'],
                      'date_end' => $donFilters['date_end']
                  ];
                  $paymentParams = array_filter($paymentParams, static function ($item) {
                      return $item !== '' && $item !== null;
                  });
                ?>
                <a class="filter-btn filter-chip <?= $donFilters['payment_method'] === $value ? 'active' : '' ?>" href="<?= backofficeBuildUrl('dons.php', $paymentParams) ?>"><?= htmlspecialchars($label) ?></a>
              <?php endforeach; ?>
            </div>
          </div>

          <form method="get" class="admin-filters-grid admin-filters-grid-wide date-filter-form">
            <?php if ($donFilters['query'] !== ''): ?>
              <input type="hidden" name="query" value="<?= htmlspecialchars($donFilters['query']) ?>" />
            <?php endif; ?>
            <?php if ($donFilters['status'] !== ''): ?>
              <input type="hidden" name="status" value="<?= htmlspecialchars($donFilters['status']) ?>" />
            <?php endif; ?>
            <?php if ($donFilters['payment_method'] !== ''): ?>
              <input type="hidden" name="payment_method" value="<?= htmlspecialchars($donFilters['payment_method']) ?>" />
            <?php endif; ?>
            <div class="filter-fields-row filter-fields-row-2">
              <div class="admin-filter-field">
                <label for="date_start">Date début</label>
                <input id="date_start" name="date_start" type="date" value="<?= htmlspecialchars($donFilters['date_start']) ?>" />
              </div>
              <div class="admin-filter-field">
                <label for="date_end">Date fin</label>
                <input id="date_end" name="date_end" type="date" value="<?= htmlspecialchars($donFilters['date_end']) ?>" />
              </div>
            </div>
            <div class="filter-actions-row">
              <button class="btn-primary" type="submit">Appliquer les dates</button>
              <a class="btn-ghost" href="<?= backofficeBuildUrl('dons.php') ?>">Réinitialiser</a>
            </div>
          </form>
        </div>
      </div>

      <div class="table-scroll">
      <table class="dons-table">
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
            <tr>
              <td colspan="7">
                <div class="empty-state compact-empty-state">
                  <div class="empty-state-title">Aucun don trouvé</div>
                  <div class="empty-state-text">Ajustez les filtres ou supprimez une contrainte de recherche.</div>
                </div>
              </td>
            </tr>
          <?php else: foreach ($dons as $d): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid #eef2f7;">
              <div class="td-name"><?= htmlspecialchars($d['cagnotte_titre'] ?? '-') ?></div>
              <div class="td-mono">ID don #<?= (int)($d['id_don'] ?? 0) ?></div>
            </td>
            <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= htmlspecialchars(trim(($d['nom'] ?? '') . ' ' . ($d['prenom'] ?? ''))) ?></td>
            <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= number_format((float)($d['montant'] ?? 0), 3, ',', ' ') ?></td>
            <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= htmlspecialchars(ucfirst((string)($d['moyen_paiement'] ?? '-'))) ?></td>
            <td style="padding:10px;border-bottom:1px solid #eef2f7;"><span class="badge <?= htmlspecialchars($donCtrl->getDonStatusBadgeClass($d['statut'] ?? 'en_attente')) ?>"><?= htmlspecialchars($donCtrl->getDonStatusLabel($d['statut'] ?? 'en_attente')) ?></span></td>
            <td style="padding:10px;border-bottom:1px solid #eef2f7;" class="td-mono"><?= htmlspecialchars(substr((string)($d['date_don'] ?? ''), 0, 19)) ?></td>
            <td style="padding:10px;border-bottom:1px solid #eef2f7;white-space:nowrap;">
              <?php if (($d['statut'] ?? '') === 'en_attente'): ?>
              <form method="post" class="inline-form">
                <input type="hidden" name="action" value="confirm_don" />
                <input type="hidden" name="don_id" value="<?= (int)$d['id_don'] ?>" />
                <button class="btn-primary" type="submit">Confirmer</button>
              </form>
              <form method="post" class="inline-form">
                <input type="hidden" name="action" value="refuse_don" />
                <input type="hidden" name="don_id" value="<?= (int)$d['id_don'] ?>" />
                <button class="btn-ghost" type="submit">Refuser</button>
              </form>
              <?php else: ?>
                <span class="td-mono">Aucune action</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div class="stats-grid stats-grid-2">
      <div class="table-card stats-panel">
        <div class="table-toolbar"><div class="table-toolbar-title">Répartition des paiements</div></div>
        <div class="stats-list">
          <?php if (empty($paymentStats)): ?>
            <div class="empty-state compact-empty-state">
              <div class="empty-state-title">Aucune donnée de paiement</div>
              <div class="empty-state-text">Les paiements apparaîtront ici dès qu'un don sera créé.</div>
            </div>
          <?php else: foreach ($paymentStats as $paymentStat): ?>
            <div class="stats-list-row">
              <div>
                <div class="stats-list-title"><?= htmlspecialchars(ucfirst((string)($paymentStat['moyen_paiement'] ?? 'Inconnu'))) ?></div>
                <div class="stats-list-subtitle"><?= (int)($paymentStat['total'] ?? 0) ?> don(s)</div>
              </div>
              <div class="stats-list-value"><?= number_format((float)($paymentStat['montant_total'] ?? 0), 3, ',', ' ') ?> TND</div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="table-card stats-panel">
        <div class="table-toolbar"><div class="table-toolbar-title">Synthèse rapide</div></div>
        <div class="stats-list">
          <div class="stats-list-row"><span class="stats-list-title">Montant total reçu</span><span class="stats-list-value"><?= number_format((float)($donGlobalStats['montant_total'] ?? 0), 3, ',', ' ') ?> TND</span></div>
          <div class="stats-list-row"><span class="stats-list-title">Montant confirmé</span><span class="stats-list-value"><?= number_format((float)($donGlobalStats['montant_confirme'] ?? 0), 3, ',', ' ') ?> TND</span></div>
          <div class="stats-list-row"><span class="stats-list-title">Dons confirmés</span><span class="stats-list-value"><?= (int)($donStatusCounts['confirme'] ?? 0) ?></span></div>
          <div class="stats-list-row"><span class="stats-list-title">Dons refusés</span><span class="stats-list-value"><?= (int)($donStatusCounts['refuse'] ?? 0) ?></span></div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
