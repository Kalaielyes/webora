<?php
/**
 * front_credit.php — UNIFIED CREDIT VIEW
 */
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
require_once __DIR__ . '/../../models/Security.php';

Session::requireLogin('login.php');
$userId = (int)Session::get('user_id');

// Self-healing: if user data is missing
if (!isset($_SESSION['user']) || empty($_SESSION['user']) || !array_key_exists('selfie_path', $_SESSION['user'])) {
    $userModel = new Utilisateur();
    $loadedUser = $userModel->findById($userId);
    if ($loadedUser) {
        $_SESSION['user'] = $loadedUser;
    }
}
$user = $_SESSION['user'] ?? [];
$initials = strtoupper(mb_substr($user['nom']??'U',0,1).mb_substr($user['prenom']??'N',0,1));

// Logic variables
$activeTab = $activeTab ?? (string)($_GET['view'] ?? 'demande');
$activeTab = in_array($activeTab, ['demande', 'garantie', 'simulateur', 'mes-credits', 'mes-garanties'], true) ? $activeTab : 'demande';
$page = 'credit'; // For sidebar active state

// Encoding fix mapping
$sc = ['en_cours' => 'cs-open', 'traitee' => 'cs-closed', 'annulee' => 'st-off'];
$sl = ['en_cours' => 'En cours', 'traitee' => 'Traitée', 'annulee' => 'Annulée'];
$rc = ['en_attente' => 'cs-open', 'approuvee' => 'cs-closed', 'refusee' => 'st-off'];
$rl = ['en_attente' => 'En attente', 'approuvee' => 'Approuvée', 'refusee' => 'Refusée'];
$tgL = ['vehicule' => '🚗 Véhicule', 'immobilier' => '🏠 Immobilier', 'garant' => '🤝 Garant', 'autre' => '📄 Autre'];

$errors = $errors ?? [];
$success = $success ?? '';
$demandes = $demandes ?? [];
$garanties = $garanties ?? [];
$demandesSelect = $demandesSelect ?? [];
$editDemande = $editDemande ?? null;
$editGarantie = $editGarantie ?? null;
$dbError = $dbError ?? false;

$self = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>LegalFin — Gestion des Crédits</title>
  
  <!-- UI STYLES -->
  <link rel="stylesheet" href="../assets/css/frontoffice/compte.css">
  <link rel="stylesheet" href="creditttt.css">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
  
  <script>
    (function() {
      var t = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
      var p = localStorage.getItem('privacy_mode') || 'visible';
      document.documentElement.setAttribute('data-privacy', p);
      if(p === 'hidden') document.documentElement.classList.add('privacy-mode');
    })();
  </script>
</head>

<body>
  <!-- ══ LOADING OVERLAY ═══════════════════════════════ -->
  <div id="loader-overlay" style="display:none;">
    <div class="loader-spinner"></div>
    <div class="loader-text">LegalFin...</div>
  </div>

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="main">
    <!-- TOP BAR -->
    <header class="topbar">
      <div class="topbar-title" id="page-title">Gestion des Crédits</div>
      <div class="topbar-right">
        <button class="theme-toggle" id="privacy-toggle" onclick="togglePrivacy()" title="Mode Incognito (Cacher les données)">
          <svg id="privacy-icon-off" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          <svg id="privacy-icon-on" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
        </button>
        <button class="theme-toggle" id="theme-toggle" onclick="toggleTheme()" title="Changer de thème">
          <svg id="theme-icon-sun" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
          <svg id="theme-icon-moon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        </button>

      </div>
    </header>

    <div class="content">
      <!-- GREETING -->
      <div class="greeting-section">
        <div class="greeting-text">Gestion des <span>Crédits</span></div>
        <div class="greeting-time">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <span id="real-time-clock">En direct</span>
        </div>
      </div>

      <!-- ALERTS -->
      <?php if ($success): ?>
        <div class="alert-ok" style="margin-bottom:1rem; padding:1rem; border-radius:12px; background:rgba(34,197,94,0.1); color:#22c55e; border:1px solid rgba(34,197,94,0.2);">
          <strong>✓ Succès:</strong> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert-err" style="margin-bottom:1rem; padding:1rem; border-radius:12px; background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2);">
          <strong>⚠️ Erreurs:</strong>
          <ul style="margin:.4rem 0 0 1.2rem;">
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- PAGE: CREDITS -->
      <div class="page on">
        <!-- TABS -->
        <div class="tabs-crud">
          <div class="tab-c <?= $activeTab === 'demande' || $activeTab === 'nouvelle' ? 'on' : '' ?>" onclick="switchTab('demande',this)">
            📝 Nouvelle demande
          </div>
          <div class="tab-c <?= $activeTab === 'garantie' ? 'on' : '' ?>" onclick="switchTab('garantie',this)">
            🔒 Nouvelle garantie
          </div>
          <div class="tab-c <?= $activeTab === 'simulateur' ? 'on' : '' ?>" onclick="switchTab('simulateur',this)">
            🧮 Simulateur
          </div>
          <div class="tab-c <?= $activeTab === 'mes-credits' ? 'on' : '' ?>" onclick="showSubPage('mes-credits',this)">
            📋 Mes crédits
          </div>
          <div class="tab-c <?= $activeTab === 'mes-garanties' ? 'on' : '' ?>" onclick="showSubPage('mes-garanties',this)">
            🛡️ Mes garanties
          </div>
        </div>

        <!-- TAB CONTENT: NOUVELLE DEMANDE -->
        <div id="tab-demande" style="display:<?= ($activeTab === 'demande' || $activeTab === 'nouvelle') ? 'block' : 'none' ?>">
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title"><?= $editDemande ? '📝 Modifier demande #' . (int) $editDemande['id'] : '➕ Nouvelle demande de crédit' ?></div>
            </div>
            <div style="padding:1.5rem;">
              <form method="POST" action="<?= $self ?>" id="form-demande">
                <input type="hidden" name="action" value="<?= $editDemande ? 'update_demande' : 'create_demande' ?>" />
                <?php if ($editDemande): ?><input type="hidden" name="id" value="<?= (int) $editDemande['id'] ?>" /><?php endif; ?>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Montant (TND) *</label>
                    <input class="fi-crud" id="f-montant" name="montant" type="number" step="100" placeholder="ex: 25 000" value="<?= htmlspecialchars($editDemande['montant'] ?? '') ?>" />
                    <div class="field-hint">Min: 1 000 | Max: 1 000 000 TND</div>
                    <div class="err-msg" id="e-montant"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud">Durée (mois) *</label>
                    <input class="fi-crud" id="f-duree" name="duree_mois" type="number" step="1" placeholder="ex: 36" value="<?= htmlspecialchars($editDemande['duree_mois'] ?? '') ?>" />
                    <div class="field-hint">Min: 6 | Max: 360 mois</div>
                    <div class="err-msg" id="e-duree"></div>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Taux annuel (%) *</label>
                    <input class="fi-crud" id="f-taux" name="taux_interet" type="number" step="0.1" placeholder="ex: 8.5" value="<?= htmlspecialchars($editDemande['taux_interet'] ?? '8.5') ?>" />
                    <div class="field-hint">Plage suggérée: 5% - 15%</div>
                    <div class="err-msg" id="e-taux"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud">Date de demande *</label>
                    <input class="fi-crud" id="f-date" name="date_demande" type="date" value="<?= htmlspecialchars($editDemande['date_demande'] ?? date('Y-m-d')) ?>" />
                    <div class="err-msg" id="e-date"></div>
                  </div>
                </div>

                <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                  <button type="submit" class="btn-crud">
                    <?= $editDemande ? '💾 Enregistrer' : '✅ Créer la demande' ?>
                  </button>
                  <?php if ($editDemande): ?>
                    <a href="<?= $self ?>" class="sc-link" style="align-self:center; color:var(--text-muted);">✖ Annuler</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- TAB CONTENT: GARANTIE -->
        <div id="tab-garantie" style="display:<?= $activeTab === 'garantie' ? 'block' : 'none' ?>">
          <div class="sc">
            <div class="sc-hd"><div class="sc-title"><?= $editGarantie ? '📝 Modifier garantie #' . (int) $editGarantie['id'] : '🛡️ Ajouter une garantie' ?></div></div>
            <div style="padding:1.5rem;">
              <form method="POST" action="<?= $self ?>" id="form-garantie" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $editGarantie ? 'update_garantie' : 'create_garantie' ?>" />
                <?php if ($editGarantie): ?><input type="hidden" name="id" value="<?= (int) $editGarantie['id'] ?>" /><?php endif; ?>

                <?php if (!$editGarantie): ?>
                  <div class="fg-crud">
                    <label class="fl-crud">Demande associée *</label>
                    <select class="fs-crud" name="demande_credit_id" id="f-g-demande" required>
                      <option value="">— Sélectionner une demande —</option>
                      <?php foreach ($demandesSelect as $ds): ?>
                        <option value="<?= $ds['id'] ?>">#<?= $ds['id'] ?> — <?= number_format($ds['montant'], 0, ',', ' ') ?> TND — <?= $ds['date_demande'] ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="err-msg" id="e-g-demande"></div>
                  </div>
                <?php endif; ?>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Type de garantie *</label>
                    <select class="fs-crud" name="type" id="f-g-type" onchange="updateDocLabel(this.value)" required>
                      <option value="">— Choisir —</option>
                      <option value="vehicule" <?= ($editGarantie['type'] ?? '') === 'vehicule' ? 'selected' : '' ?>>🚗 Véhicule</option>
                      <option value="immobilier" <?= ($editGarantie['type'] ?? '') === 'immobilier' ? 'selected' : '' ?>>🏠 Immobilier</option>
                      <option value="garant" <?= ($editGarantie['type'] ?? '') === 'garant' ? 'selected' : '' ?>>🤝 Garant</option>
                      <option value="autre" <?= ($editGarantie['type'] ?? '') === 'autre' ? 'selected' : '' ?>>📄 Autre</option>
                    </select>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud" id="lbl-document">Référence / Document *</label>
                    <input class="fi-crud" id="f-g-doc" name="document" type="text" placeholder="Référence officielle" value="<?= htmlspecialchars($editGarantie['document'] ?? '') ?>" />
                    <div class="field-hint" id="hint-document">N° carte grise, titre propriété, nom garant…</div>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Valeur estimée (TND) *</label>
                    <input class="fi-crud" id="f-g-valeur" name="valeur_estimee" type="number" min="0" step="100" placeholder="ex: 30 000" value="<?= htmlspecialchars($editGarantie['valeur_estimee'] ?? '') ?>" />
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud">Description</label>
                    <input class="fi-crud" name="description" type="text" placeholder="Détails additionnels..." value="<?= htmlspecialchars($editGarantie['description'] ?? '') ?>" />
                  </div>
                </div>

                <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                  <button type="submit" class="btn-crud"><?= $editGarantie ? '💾 Enregistrer' : '🛡️ Ajouter la garantie' ?></button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- TAB CONTENT: SIMULATEUR -->
        <div id="tab-simulateur" style="display:<?= $activeTab === 'simulateur' ? 'block' : 'none' ?>">
          <div class="sc cr-sim">
            <div class="sc-title" style="margin-bottom:1.5rem;">🧮 Simulateur de Crédit</div>
            <div class="slider-row">
              <div class="sl-lbl"><span>Montant du crédit</span><span id="lv">20 000 TND</span></div>
              <input type="range" min="1000" max="100000" step="1000" value="20000" oninput="calcCredit()" />
            </div>
            <div class="slider-row">
              <div class="sl-lbl"><span>Durée du remboursement</span><span id="dv">36 mois</span></div>
              <input type="range" min="6" max="84" step="6" value="36" oninput="calcCredit()" />
            </div>
            <div class="slider-row">
              <div class="sl-lbl"><span>Taux annuel d'intérêt</span><span id="rv">8.5%</span></div>
              <input type="range" min="5" max="20" step="0.5" value="8.5" oninput="calcCredit()" />
            </div>
            <div class="cr-result">
              <div class="sl-lbl" style="justify-content:center;margin-bottom:.8rem;">Mensualité estimée</div>
              <div class="cr-monthly" id="mp">631 TND</div>
              <div class="cr-detail" id="cd">Total : 22 716 TND · Coût : 2 716 TND</div>
            </div>
            <button class="apply-btn" onclick="switchTab('demande',null)">Faire ma demande →</button>
          </div>
        </div>

        <!-- TAB CONTENT: MES CREDITS -->
        <div id="tab-mes-credits" style="display:<?= $activeTab === 'mes-credits' ? 'block' : 'none' ?>">
          <div class="sc">
            <div class="sc-hd"><div class="sc-title">📋 Vos demandes de crédit</div></div>
            <div style="overflow-x:auto;">
              <table class="tbl-crud">
                <thead>
                  <tr>
                    <th>N°</th>
                    <th>Montant</th>
                    <th>Durée</th>
                    <th>Taux</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Signature</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(empty($demandes)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucune demande trouvée.</td></tr>
                  <?php else: foreach($demandes as $d): ?>
                    <tr>
                      <td>#<?= (int)$d['id'] ?></td>
                      <td class="privacy-blur"><?= number_format($d['montant'], 0, ',', ' ') ?> TND</td>
                      <td><?= (int)$d['duree_mois'] ?> m</td>
                      <td><?= $d['taux_interet'] ?>%</td>
                      <td><?= htmlspecialchars($d['date_demande']) ?></td>
                      <td><span class="badge <?= $rc[$d['resultat']] ?? 'b-wait' ?>"><?= $rl[$d['resultat']] ?? $d['resultat'] ?></span></td>
                      <td>
                        <?php if($d['resultat'] === 'approuvee'): ?>
                          <span class="badge b-wait">✍️ En attente</span>
                        <?php else: ?>—<?php endif; ?>
                      </td>
                      <td>
                        <a href="?edit_d=<?= $d['id'] ?>" class="btn-edt" title="Modifier">✏️</a>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- TAB CONTENT: MES GARANTIES -->
        <div id="tab-mes-garanties" style="display:<?= $activeTab === 'mes-garanties' ? 'block' : 'none' ?>">
          <div class="sc">
            <div class="sc-hd"><div class="sc-title">🛡️ Vos garanties déposées</div></div>
            <div style="overflow-x:auto;">
              <table class="tbl-crud">
                <thead>
                  <tr>
                    <th>Demande</th>
                    <th>Type</th>
                    <th>Document</th>
                    <th>Valeur</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(empty($garanties)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucune garantie trouvée.</td></tr>
                  <?php else: foreach($garanties as $g): ?>
                    <tr>
                      <td>#<?= (int)$g['demande_credit_id'] ?></td>
                      <td><?= $tgL[$g['type']] ?? htmlspecialchars($g['type']) ?></td>
                      <td><?= htmlspecialchars($g['document']) ?></td>
                      <td class="privacy-blur"><?= number_format($g['valeur_estimee'], 0, ',', ' ') ?> TND</td>
                      <td><a href="?edit_g=<?= $g['id'] ?>&view=garantie" class="btn-edt">✏️</a></td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div><!-- /page -->
    </div><!-- /content -->
  </div><!-- /main -->

  <script>
    function toggleTheme() {
      var current = document.documentElement.getAttribute('data-theme') || 'dark';
      var next = (current === 'dark') ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
    }

    function togglePrivacy() {
      var current = document.documentElement.getAttribute('data-privacy') || 'visible';
      var next = (current === 'visible') ? 'hidden' : 'visible';
      document.documentElement.setAttribute('data-privacy', next);
      localStorage.setItem('privacy_mode', next);
      if(next === 'hidden') document.documentElement.classList.add('privacy-mode');
      else document.documentElement.classList.remove('privacy-mode');
    }

    function switchTab(name, el) {
      ['demande', 'garantie', 'simulateur', 'mes-credits', 'mes-garanties'].forEach(t => {
        const tabEl = document.getElementById('tab-' + t);
        if(tabEl) tabEl.style.display = (t === name) ? 'block' : 'none';
      });
      document.querySelectorAll('.tab-c').forEach(t => t.classList.remove('on'));
      if(el) el.classList.add('on');
    }

    function showSubPage(id, el) {
      switchTab(id, el);
    }

    function calcCredit() {
      const r = document.querySelectorAll('#tab-simulateur input[type=range]');
      if(!r.length) return;
      const l = parseInt(r[0].value), d = parseInt(r[1].value), t = parseFloat(r[2].value);
      document.getElementById('lv').textContent = l.toLocaleString() + ' TND';
      document.getElementById('dv').textContent = d + ' mois';
      document.getElementById('rv').textContent = t + '%';
      const mr = t / 100 / 12, mp = Math.round((l * (mr * Math.pow(1 + mr, d))) / (Math.pow(1 + mr, d) - 1)), tot = Math.round(mp * d);
      document.getElementById('mp').textContent = mp.toLocaleString() + ' TND';
      document.getElementById('cd').textContent = 'Total : ' + tot.toLocaleString() + ' TND · Coût : ' + (tot - l).toLocaleString() + ' TND';
    }

    function updateDocLabel(val) {
      const map = {
        vehicule: ['N° carte grise *', 'Ex: 123456TUN'],
        immobilier: ['N° titre propriété / acte *', 'Ex: TP-2021-88821'],
        garant: ['Nom complet du garant *', 'Prénom Nom'],
        autre: ['Référence document *', 'Toute référence officielle']
      };
      if (val && map[val]) {
        document.getElementById('lbl-document').textContent = map[val][0];
        document.getElementById('hint-document').textContent = map[val][1];
        document.getElementById('f-g-doc').placeholder = map[val][1];
      }
    }

    // Initialize clock
    setInterval(() => {
      const now = new Date();
      if(document.getElementById('real-time-clock'))
        document.getElementById('real-time-clock').textContent = now.toLocaleTimeString();
    }, 1000);

    // Initial calc
    document.addEventListener('DOMContentLoaded', () => {
        calcCredit();
        // Handle URL tab activation
        const urlParams = new URLSearchParams(window.location.search);
        const view = urlParams.get('view');
        if(view) {
            const btn = document.querySelector(`[onclick*="${view}"]`);
            if(btn) btn.click();
        }
    });
  </script>
</body>
</html>
