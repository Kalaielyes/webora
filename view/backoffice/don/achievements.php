<?php
require_once __DIR__ . '/../../../models/config.php';
require_once __DIR__ . '/../../../models/Session.php';
require_once __DIR__ . '/../../../controller/AchievementController.php';
require_once __DIR__ . '/helpers.php';
Session::start();
Session::requireAdmin();
$_SESSION['is_backoffice_admin'] = true;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Mark session as backoffice admin so the achievement AJAX endpoint allows writes
$_SESSION['is_backoffice_admin'] = true;

$ctrl = new AchievementController();
$rows = $ctrl->getAllWithUnlockCount();

$roleOptions = ['donor' => 'Donateur', 'association' => 'Association'];
$conditionLabels = [
    'amount_total'             => 'Montant total donné',
    'donation_count'           => 'Nombre de dons',
    'supported_campaign_count' => 'Cagnottes soutenues',
    'campaign_count'           => 'Cagnottes créées',
    'raised_amount_total'      => 'Montant total collecté',
    'funded_campaign_count'    => 'Cagnottes financées',
];
$conditionOptions = array_keys($conditionLabels);
$achievementEndpoint = APP_URL . '/controller/AchievementController.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Admin - Achievements</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backoffice/Utilisateur.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backoffice/don.css">
<style>
  .ach-input{width:100%;border:1px solid var(--border2);background:var(--bg);color:var(--text);border-radius:8px;padding:.52rem .75rem;font-family:var(--fb);font-size:.82rem;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box;}
  .ach-input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.1);}
  .ach-label{display:block;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:.28rem;}
  .ach-fg{display:grid;grid-template-columns:1fr 1fr;gap:.65rem;}
  .ach-fg .full{grid-column:1/-1;}
  .pill-on{display:inline-flex;align-items:center;background:var(--green-light);color:var(--green);border:1px solid rgba(22,163,74,.25);padding:.18rem .6rem;border-radius:999px;font-size:.68rem;font-weight:700;}
  .pill-off{display:inline-flex;align-items:center;background:var(--rose-light);color:var(--rose);border:1px solid rgba(220,38,38,.2);padding:.18rem .6rem;border-radius:999px;font-size:.68rem;font-weight:700;}
  .ach-kpi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.2rem;}
  .ach-kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:.9rem 1.1rem;display:flex;align-items:center;gap:.8rem;}
  .ach-kpi-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
  .ach-kpi-val{font-family:var(--fh);font-size:1.35rem;font-weight:800;line-height:1;}
  .ach-kpi-label{font-size:.66rem;color:var(--muted);margin-top:2px;text-transform:uppercase;letter-spacing:.07em;}
  /* Icon action buttons */
  .ach-icon-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--border2);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;transition:all .15s;color:var(--muted);}
  .ach-icon-btn:hover{background:var(--bg);color:var(--navy);border-color:var(--border2);}
  .ach-icon-btn.edit:hover{color:var(--blue);border-color:rgba(37,99,235,.3);background:rgba(37,99,235,.06);}
  .ach-icon-btn.toggle-on:hover{color:var(--amber);border-color:rgba(217,119,6,.3);background:rgba(217,119,6,.06);}
  .ach-icon-btn.toggle-off:hover{color:var(--green);border-color:rgba(22,163,74,.3);background:rgba(22,163,74,.06);}
  .ach-icon-btn.del:hover{color:var(--rose);border-color:rgba(220,38,38,.3);background:rgba(220,38,38,.06);}
  /* Modal */
  .ach-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);backdrop-filter:blur(3px);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s;}
  .ach-modal-overlay.open{opacity:1;pointer-events:all;}
  .ach-modal{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.6rem;width:min(540px,95vw);box-shadow:0 20px 60px rgba(0,0,0,.18);transform:translateY(12px) scale(.97);transition:transform .2s;}
  .ach-modal-overlay.open .ach-modal{transform:none;}
  .ach-modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;}
  .ach-modal-title{font-family:var(--fh);font-size:1rem;font-weight:800;color:var(--navy);}
  .ach-modal-close{width:30px;height:30px;border-radius:7px;border:1px solid var(--border2);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:.85rem;transition:all .15s;}
  .ach-modal-close:hover{background:var(--rose-light);color:var(--rose);border-color:rgba(220,38,38,.25);}
  .ach-icon-preview{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,rgba(13,148,136,.12),rgba(37,99,235,.1));border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--teal);}
  @media(max-width:1100px){.ach-kpi-row{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>
<?php renderBackofficeSidebar('achievements'); ?>
<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Achievements</div>
      <div class="breadcrumb">Admin / Achievements</div>
    </div>
  </div>

  <div class="content">
    <div id="ach-flash" style="display:none" class="flash-message"></div>

    <!-- KPI row -->
    <?php
      $totalEnabled  = count(array_filter($rows, fn($r) => (int)$r['is_enabled'] === 1));
      $totalDisabled = count($rows) - $totalEnabled;
      $totalUnlocks  = array_sum(array_column($rows, 'unlocked_users_count'));
    ?>
    <div class="ach-kpi-row">
      <div class="ach-kpi">
        <div class="ach-kpi-icon" style="background:var(--teal-light);border:1px solid var(--teal-border);">🏅</div>
        <div>
          <div class="ach-kpi-val" style="color:var(--teal)"><?= count($rows) ?></div>
          <div class="ach-kpi-label">Achievements configurés</div>
        </div>
      </div>
      <div class="ach-kpi">
        <div class="ach-kpi-icon" style="background:var(--green-light);border:1px solid rgba(22,163,74,.2);">✅</div>
        <div>
          <div class="ach-kpi-val" style="color:var(--green)"><?= $totalEnabled ?></div>
          <div class="ach-kpi-label">Actifs</div>
        </div>
      </div>
      <div class="ach-kpi">
        <div class="ach-kpi-icon" style="background:var(--blue-light);border:1px solid var(--blue-border);">👥</div>
        <div>
          <div class="ach-kpi-val" style="color:var(--blue)"><?= $totalUnlocks ?></div>
          <div class="ach-kpi-label">Débloquages totaux</div>
        </div>
      </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des achievements</div>
          <button class="btn-primary" onclick="openModal()" style="display:flex;align-items:center;gap:.4rem;">
            <i class="fa-solid fa-plus"></i> Nouveau badge
          </button>
        </div>
        <table>
          <thead>
            <tr>
              <th>Icône</th>
              <th>Titre</th>
              <th>Rôle</th>
              <th>Condition</th>
              <th>Valeur</th>
              <th>Points</th>
              <th>État</th>
              <th>Débloqué par</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="9">Aucun achievement</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td>
                <div class="ach-icon-preview">
                  <?php if (str_starts_with(trim($r['icon'] ?? ''), 'fa')): ?>
                    <i class="<?= htmlspecialchars($r['icon']) ?>"></i>
                  <?php else: ?>
                    <?= htmlspecialchars($r['icon'] ?? '🎖️') ?>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <strong><?= htmlspecialchars($r['title']) ?></strong>
                <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($r['description']) ?></div>
              </td>
              <td><?= htmlspecialchars($roleOptions[$r['role_type']] ?? ucfirst($r['role_type'])) ?></td>
              <td><?= htmlspecialchars($conditionLabels[$r['condition_type']] ?? $r['condition_type']) ?></td>
              <td><?= htmlspecialchars((string)$r['condition_value']) ?></td>
              <td><strong><?= (int)$r['points'] ?></strong></td>
              <td>
                <?php if ((int)$r['is_enabled'] === 1): ?>
                  <span class="pill-on">Actif</span>
                <?php else: ?>
                  <span class="pill-off">Inactif</span>
                <?php endif; ?>
              </td>
              <td><?= (int)$r['unlocked_users_count'] ?></td>
              <td>
                <div style="display:flex;gap:.3rem;align-items:center;">
                  <button class="ach-icon-btn edit" title="Modifier" onclick='prefillEdit(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <?php if ((int)$r['is_enabled'] === 1): ?>
                  <button class="ach-icon-btn toggle-on" title="Désactiver" onclick="runAction('toggle',{id:<?= (int)$r['id'] ?>,enabled:0})">
                    <i class="fa-solid fa-toggle-on"></i>
                  </button>
                  <?php else: ?>
                  <button class="ach-icon-btn toggle-off" title="Activer" onclick="runAction('toggle',{id:<?= (int)$r['id'] ?>,enabled:1})">
                    <i class="fa-solid fa-toggle-off"></i>
                  </button>
                  <?php endif; ?>
                  <button class="ach-icon-btn del" title="Supprimer" onclick="runAction('delete',{id:<?= (int)$r['id'] ?>})">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
  </div>
</div>

<!-- Modal: Créer / Modifier un achievement -->
<div class="ach-modal-overlay" id="ach-modal-overlay" onclick="handleOverlayClick(event)">
  <div class="ach-modal">
    <div class="ach-modal-header">
      <div class="ach-modal-title" id="ach-form-title">✦ Nouveau badge</div>
      <button class="ach-modal-close" onclick="closeModal()" title="Fermer"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="ach-flash-modal" style="display:none;margin-bottom:.8rem;padding:.6rem .9rem;border-radius:8px;font-size:.82rem;font-weight:600;"></div>
    <form id="achievement-form" onsubmit="submitAchievement(event)">
      <input type="hidden" id="ach-id" value="">
      <div class="ach-fg">
        <div class="full">
          <label class="ach-label">Titre</label>
          <input id="ach-title" class="ach-input" maxlength="120" required>
        </div>
        <div class="full">
          <label class="ach-label">Description</label>
          <textarea id="ach-description" class="ach-input" maxlength="255" rows="2" required></textarea>
        </div>
        <div>
          <label class="ach-label">Icône (classe FA)</label>
          <input id="ach-icon" class="ach-input" maxlength="120" value="fa-solid fa-star" required oninput="updateIconPreview(this.value)">
        </div>
        <div style="display:flex;align-items:flex-end;gap:.5rem;">
          <div style="flex:1">
            <label class="ach-label">Aperçu</label>
            <div class="ach-icon-preview" id="ach-icon-preview"><i class="fa-solid fa-star"></i></div>
          </div>
        </div>
        <div>
          <label class="ach-label">Rôle</label>
          <select id="ach-role" class="ach-input" required>
            <?php foreach ($roleOptions as $val => $label): ?>
              <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="ach-label">Type de condition</label>
          <select id="ach-condition-type" class="ach-input" required>
            <?php foreach ($conditionLabels as $val => $label): ?>
              <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="ach-label">Valeur condition</label>
          <input id="ach-condition-value" class="ach-input" type="number" min="0" step="0.01" required>
        </div>
        <div>
          <label class="ach-label">Points</label>
          <input id="ach-points" class="ach-input" type="number" min="0" step="1" required>
        </div>
        <div class="full">
          <label class="ach-label">Statut</label>
          <select id="ach-enabled" class="ach-input">
            <option value="1" selected>Actif</option>
            <option value="0">Inactif</option>
          </select>
        </div>
      </div>
      <div style="margin-top:1.1rem;display:flex;gap:.5rem;justify-content:flex-end;">
        <button type="button" class="btn-ghost" onclick="closeModal()">Annuler</button>
        <button type="submit" class="btn-primary" style="display:flex;align-items:center;gap:.35rem;">
          <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const csrfToken = <?= json_encode($csrfToken) ?>;

function openModal() {
  document.getElementById('ach-modal-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('ach-modal-overlay').classList.remove('open');
  document.body.style.overflow = '';
  resetForm();
}
function handleOverlayClick(e) {
  if (e.target === document.getElementById('ach-modal-overlay')) closeModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

function updateIconPreview(val) {
  const el = document.getElementById('ach-icon-preview');
  if (!el) return;
  el.innerHTML = val.trim().startsWith('fa') ? `<i class="${val}"></i>` : val;
}

function showFlash(message, ok) {
  // Flash in modal if open, else in page
  const modal = document.getElementById('ach-modal-overlay');
  const elId = modal && modal.classList.contains('open') ? 'ach-flash-modal' : 'ach-flash';
  const el = document.getElementById(elId);
  if (!el) return;
  el.style.display = 'block';
  el.style.background = ok ? 'var(--green-light)' : 'var(--rose-light)';
  el.style.borderColor = ok ? 'rgba(22,163,74,.3)' : 'rgba(220,38,38,.3)';
  el.style.color = ok ? 'var(--green)' : 'var(--rose)';
  el.style.border = ok ? '1px solid rgba(22,163,74,.3)' : '1px solid rgba(220,38,38,.3)';
  el.textContent = message;
  setTimeout(() => { el.style.display = 'none'; }, 4000);
}

function buildPayload(base) {
  const params = new URLSearchParams();
  Object.keys(base).forEach((key) => params.append(key, String(base[key])));
  params.append('csrf_token', csrfToken);
  return params;
}

async function runAction(action, payload) {
  if (action === 'delete' && !confirm('Confirmer la suppression de cet achievement ?')) {
    return;
  }

  try {
    const body = buildPayload(Object.assign({action}, payload));
    const res = await fetch('<?= htmlspecialchars($achievementEndpoint, ENT_QUOTES, 'UTF-8') ?>', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body,
    });
    const raw = await res.text();
    let data;
    try {
      data = JSON.parse(raw);
    } catch (parseErr) {
      throw new Error('Réponse serveur invalide (attendu JSON).');
    }

    if (!res.ok) {
      throw new Error(data.error || data.message || ('Erreur HTTP ' + res.status));
    }

    showFlash(data.message || data.error || 'Terminé', !!data.ok);
    if (data.ok) {
      setTimeout(() => window.location.reload(), 500);
    }
  } catch (e) {
    showFlash('Erreur réseau: ' + e.message, false);
  }
}

function submitAchievement(event) {
  event.preventDefault();

  const id = document.getElementById('ach-id').value.trim();
  const payload = {
    title: document.getElementById('ach-title').value.trim(),
    description: document.getElementById('ach-description').value.trim(),
    icon: document.getElementById('ach-icon').value.trim(),
    role_type: document.getElementById('ach-role').value,
    condition_type: document.getElementById('ach-condition-type').value,
    condition_value: document.getElementById('ach-condition-value').value,
    points: document.getElementById('ach-points').value,
    is_enabled: document.getElementById('ach-enabled').value,
  };

  if (!payload.title || !payload.description || !payload.icon) {
    showFlash('Tous les champs sont requis.', false);
    return;
  }

  if (id !== '') {
    payload.id = id;
    runAction('update', payload);
  } else {
    runAction('create', payload);
  }
}

function prefillEdit(data) {
  document.getElementById('ach-form-title').textContent = '✦ Modifier un badge';
  document.getElementById('ach-id').value = data.id;
  document.getElementById('ach-title').value = data.title || '';
  document.getElementById('ach-description').value = data.description || '';
  const iconVal = data.icon || 'fa-solid fa-star';
  document.getElementById('ach-icon').value = iconVal;
  updateIconPreview(iconVal);
  document.getElementById('ach-role').value = data.role_type || 'donor';
  document.getElementById('ach-condition-type').value = data.condition_type || 'donation_count';
  document.getElementById('ach-condition-value').value = data.condition_value || 0;
  document.getElementById('ach-points').value = data.points || 0;
  document.getElementById('ach-enabled').value = Number(data.is_enabled) === 1 ? '1' : '0';
  openModal();
}

function resetForm() {
  document.getElementById('ach-form-title').textContent = '✦ Nouveau badge';
  document.getElementById('ach-id').value = '';
  document.getElementById('achievement-form').reset();
  document.getElementById('ach-icon').value = 'fa-solid fa-star';
  document.getElementById('ach-enabled').value = '1';
  updateIconPreview('fa-solid fa-star');
}
</script>
</body>
</html>

