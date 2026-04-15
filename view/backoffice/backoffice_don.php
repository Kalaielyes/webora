<?php
require_once __DIR__ . "/../../controller/doncontroller.php";
$donCtrl = new doncontroller();
$message = $_GET['msg'] ?? '';

function donsRedirectWith($params = []) {
    $base = basename(__FILE__);
    $qs = http_build_query($params);
    header('Location: ' . $base . ($qs ? ('?' . $qs) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['don_id'])) {
    $id = (int)$_POST['don_id'];
    if ($_POST['action'] === 'confirm_don') {
        $ok = $donCtrl->confirmerDon($id);
        donsRedirectWith(['msg' => $ok ? 'Don confirme' : 'Echec confirmation']);
    } elseif ($_POST['action'] === 'refuse_don') {
        $ok = $donCtrl->refuserDon($id);
        donsRedirectWith(['msg' => $ok ? 'Don refuse' : 'Echec refus']);
    }
}

$dons = $donCtrl->getAllDons();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Backoffice - Moderation des dons</title>
<link rel="stylesheet" href="cagnotte.css">
</head>
<body>
<div style="padding:20px;max-width:1200px;margin:0 auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h2 style="margin:0;">Moderation des dons</h2>
    <a href="backoffice_cagnotte.php" class="btn-ghost">Retour cagnottes</a>
  </div>

  <?php if ($message !== ''): ?>
    <div style="margin-bottom:12px;padding:10px;border-radius:8px;background:#eef6ff;border:1px solid #c5d9f1;color:#1f3a5c;"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #d7dee8;">
    <thead>
      <tr style="background:#f7f9fc;">
        <th style="text-align:left;padding:10px;border-bottom:1px solid #d7dee8;">Cagnotte</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid #d7dee8;">Donateur</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid #d7dee8;">Montant</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid #d7dee8;">Paiement</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid #d7dee8;">Statut</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid #d7dee8;">Date</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid #d7dee8;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($dons)): ?>
      <tr><td colspan="7" style="padding:12px;">Aucun don.</td></tr>
      <?php else: foreach ($dons as $d): ?>
      <tr>
        <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= htmlspecialchars($d['cagnotte_titre'] ?? '-') ?></td>
        <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= htmlspecialchars(trim(($d['nom'] ?? '') . ' ' . ($d['prenom'] ?? ''))) ?></td>
        <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= number_format((float)($d['montant'] ?? 0), 3, ',', ' ') ?></td>
        <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= htmlspecialchars($d['moyen_paiement'] ?? '-') ?></td>
        <td style="padding:10px;border-bottom:1px solid #eef2f7;"><span class="badge <?= htmlspecialchars($donCtrl->getDonStatusBadgeClass($d['statut'] ?? 'en_attente')) ?>"><?= htmlspecialchars($donCtrl->getDonStatusLabel($d['statut'] ?? 'en_attente')) ?></span></td>
        <td style="padding:10px;border-bottom:1px solid #eef2f7;"><?= htmlspecialchars(substr($d['date_don'] ?? '', 0, 19)) ?></td>
        <td style="padding:10px;border-bottom:1px solid #eef2f7;white-space:nowrap;">
          <?php if (($d['statut'] ?? '') === 'en_attente'): ?>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="confirm_don" />
            <input type="hidden" name="don_id" value="<?= (int)$d['id_don'] ?>" />
            <button class="btn-primary" type="submit">Confirmer</button>
          </form>
          <?php endif; ?>
          <?php if (($d['statut'] ?? '') === 'en_attente'): ?>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="refuse_don" />
            <input type="hidden" name="don_id" value="<?= (int)$d['id_don'] ?>" />
            <button class="btn-ghost" type="submit">Refuser</button>
          </form>
          <?php else: ?>
            <span style="color:#64748b;font-size:.85rem;">Aucune action</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
