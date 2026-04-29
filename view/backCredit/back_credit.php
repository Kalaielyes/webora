<?php
$sc = ['en_cours' => 'cs-open', 'traitee' => 'cs-closed', 'annulee' => 'st-off'];
$sl = ['en_cours' => 'En cours', 'traitee' => 'Traitée', 'annulee' => 'Annulée'];
$rc = ['en_attente' => 'cs-open', 'approuvee' => 'cs-closed', 'refusee' => 'st-off'];
$rl = ['en_attente' => 'En attente', 'approuvee' => 'Approuvée', 'refusee' => 'Refusée'];
$tgL = ['vehicule' => '🚗 Véhicule', 'immobilier' => '🏠 Immobilier', 'garant' => '🤝 Garant', 'autre' => '📄 Autre'];

$activeTab = $activeTab ?? 'demande';
$activeTab = in_array($activeTab, ['demande', 'garantie', 'simulateur'], true) ? $activeTab : 'demande';
$errors = $errors ?? [];
$success = $success ?? '';
$demandes = $demandes ?? [];
$garanties = $garanties ?? [];
$demandesSelect = $demandesSelect ?? [];
$editDemande = $editDemande ?? null;
$editGarantie = $editGarantie ?? null;
$dbError = $dbError ?? false;

$self = $controllerSelf ?? $_SERVER['SCRIPT_NAME'];
$viewRoot = defined('VIEW_URL') ? VIEW_URL : '';
$controllerRoot = defined('BASE_URL') ? BASE_URL . '/controller' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>LegalFin — Gestion des Crédits</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($viewRoot) ?>/backCredit/creditttttttttttttttt.css" />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
</head>

<body>
  <!-- ══ SIDEBAR ══ -->
  <aside class="sidebar">
    <div class="sb-logo">
      LegalFin<span>Bank</span>
      <div class="sb-env" style="margin-top:.4rem;">CLIENT</div>
    </div>
    <div class="sb-user">
      <div class="sb-av">AK</div>
      <div>
        <div class="sb-aname">Utilisateur</div>
        <div class="sb-badge"><span class="sb-dot"></span> Actif</div>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-sec">COMPTE</div>
      <a class="sb-item on" onclick="showPage('credits',this)"><span class="sb-ico">📈</span> Crédits</a>
      <a class="sb-item" onclick="showPage('mes-credits',this)"><span class="sb-ico">📋</span> Mes crédits <span class="sb-badge ba"><?= count($demandes) ?></span></a>
      <a class="sb-item" onclick="showPage('mes-garanties',this)"><span class="sb-ico">🔒</span> Mes garanties <span class="sb-badge ba"><?= count($garanties) ?></span></a>
      <div class="sb-sec">AUTRES</div>
      <a class="sb-item" onclick="showPage('dashboard',this)"><span class="sb-ico">📊</span> Tableau de bord</a>
      <a class="sb-item" onclick="showPage('profil',this)"><span class="sb-ico">👤</span> Profil</a>
    </nav>
    <div class="sb-footer">
      <div class="st-row">
        <span class="std" style="background:#22C55E;"></span>
        <span>Système en ligne</span>
      </div>
      <a href="<?= htmlspecialchars($controllerRoot) ?>/AuthController.php?action=logout" style="font-size:.7rem;color:#94A3B8;text-decoration:none;transition:color .2s;">
        Déconnexion ↗
      </a>
    </div>
  </aside>

  <!-- ══ MAIN CONTENT ══ -->
  <div class="main">
    <!-- TOP BAR -->
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div class="tb-title" id="page-title">Gestion des Crédits</div>
      </div>
      <div class="tb-right">
        <div class="live"><span class="ldot"></span> En direct</div>
        <a href="<?= htmlspecialchars($controllerRoot) ?>/CreditController.php" class="btn-backoffice">Accueil ↗</a>
      </div>
    </header>

    <!-- CONTENT AREA -->
    <div class="content">

      <!-- ════════════════════════════════════════════════════
           PAGE: CRÉDITS (MAIN)
      ════════════════════════════════════════════════════ -->
      <div class="page on" id="page-credits">

        <!-- ALERTS & MESSAGES -->
        <?php if (isset($dbStatus) && !$dbStatus['ok']): ?>
          <div class="alert-err">
            <strong>⚠️ Erreur Connexion:</strong> <?= htmlspecialchars($dbStatus['error']) ?>
          </div>
        <?php endif; ?>
        <?php if ($dbError): ?>
          <div class="alert-err">
            <strong>⚠️ Erreur:</strong> Connexion base de données indisponible.
          </div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert-ok">
            <strong>✓ Succès:</strong> <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert-err">
            <strong>⚠️ Erreurs:</strong>
            <ul style="margin:.4rem 0 0 1.2rem;">
              <?php foreach ($errors as $e): ?>
                <li style="margin:.2rem 0;"><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- SECTION TABS -->
        <div class="tabs-crud">
          <div class="tab-c <?= $activeTab === 'demande' ? 'on' : '' ?>" onclick="switchTab('demande',this)">
            📋 Nouvelle demande
          </div>
          <div class="tab-c <?= $activeTab === 'garantie' ? 'on' : '' ?>" onclick="switchTab('garantie',this)">
            🔒 Nouvelle garantie
          </div>
          <div class="tab-c <?= $activeTab === 'simulateur' ? 'on' : '' ?>" onclick="switchTab('simulateur',this)">
            🧮 Simulateur
          </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             TAB 1: DEMANDES DE CRÉDIT
        ══════════════════════════════════════════════════ -->
        <div id="tab-demande" style="display:<?= $activeTab === 'demande' ? 'block' : 'none' ?>">

          <!-- FORM: NEW/EDIT DEMANDE -->
          <div class="sc" style="margin-bottom:1.5rem;">
            <div class="sc-hd">
              <div class="sc-title">
                <?= $editDemande ? '✏️ Modifier demande #' . (int) $editDemande['id'] : '➕ Nouvelle demande de crédit' ?>
              </div>
            </div>
            <div style="padding:1.2rem;">
              <form method="POST" action="<?= $self ?>" id="form-demande" novalidate>
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
                    <input class="fi-crud" id="f-taux" name="taux_interet" type="number" step="0.1" placeholder="ex: 8.5" value="<?= htmlspecialchars($editDemande['taux_interet'] ?? '') ?>" required />
                    <div class="field-hint">Plage: 0% - 30%</div>
                    <div class="err-msg" id="e-taux"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud">Date de demande *</label>
                    <input class="fi-crud" id="f-date" name="date_demande" type="date" value="<?= htmlspecialchars($editDemande['date_demande'] ?? date('Y-m-d')) ?>" required />
                    <div class="err-msg" id="e-date"></div>
                  </div>
                </div>

                <?php if ($editDemande): ?>
                  <div class="fg-crud">
                    <label class="fl-crud">Date de traitement</label>
                    <input class="fi-crud" name="date_traitement" type="date" value="<?= htmlspecialchars($editDemande['date_traitement'] ?? '') ?>" />
                  </div>
                <?php endif; ?>

                <div style="display:flex;gap:.8rem;margin-top:1rem;">
                  <button type="submit" class="btn-crud">
                    <?= $editDemande ? '💾 Enregistrer' : '✅ Créer la demande' ?>
                  </button>
                  <?php if ($editDemande): ?>
                    <a href="<?= $self ?>" class="sc-link">✕ Annuler</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </div>

        </div><!-- /tab-demande -->

        <!-- ══════════════════════════════════════════════════
             TAB 2: GARANTIES
        ══════════════════════════════════════════════════ -->
        <div id="tab-garantie" style="display:<?= $activeTab === 'garantie' ? 'block' : 'none' ?>">

          <!-- FORM: NEW/EDIT GARANTIE -->
          <div class="sc" style="margin-bottom:1.5rem;">
            <div class="sc-hd">
              <div class="sc-title">
                <?= $editGarantie ? '✏️ Modifier garantie #' . (int) $editGarantie['id'] : '🔒 Ajouter une garantie' ?>
              </div>
            </div>
            <div style="padding:1.2rem;">
              <form method="POST" action="<?= $self ?>" id="form-garantie" enctype="multipart/form-data" novalidate>
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
                    <div class="err-msg" id="e-g-type"></div>
                  </div>
                  <div class="fg-crud">
    <label class="fl-crud" id="lbl-document">Document justificatif *</label>
    <input class="fi-crud" id="f-g-doc" name="document" type="text"
        placeholder="Référence officielle (ex: 123456TUN)"
        value="<?= htmlspecialchars($editGarantie['document'] ?? '') ?>" />
    <div class="field-hint" id="hint-document">N° carte grise, titre propriété, nom garant…</div>

    <!-- Upload fichier -->
    <label class="fl-crud" style="margin-top:.8rem;">
        📎 Ou joindre un fichier
        <span style="font-weight:400;color:var(--muted);text-transform:none;font-size:.75rem;">
            (PDF, JPG, PNG — max 5 Mo)
        </span>
    </label>
    <input class="fi-crud" name="document_file" id="f-g-file" type="file"
        accept=".pdf,.jpg,.jpeg,.png,.webp"
        style="padding:.45rem;cursor:pointer;" />

    <?php if (!empty($editGarantie['document']) && str_starts_with($editGarantie['document'], 'uploads/')): ?>
        <div class="field-hint" style="margin-top:.4rem;">
            📄 Fichier actuel :
            <a href="<?= htmlspecialchars(BASE_URL . '/' . $editGarantie['document']) ?>"
               target="_blank" style="color:var(--blue);text-decoration:underline;">
                Voir le document ↗
            </a>
        </div>
    <?php endif; ?>

    <div class="err-msg" id="e-g-doc"></div>
</div>
                </div>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Valeur estimée (TND) *</label>
                    <input class="fi-crud" id="f-g-valeur" name="valeur_estimee" type="number" min="0" step="100" placeholder="ex: 30 000" value="<?= htmlspecialchars($editGarantie['valeur_estimee'] ?? '') ?>" required />
                    <div class="err-msg" id="e-g-valeur"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud">Description</label>
                    <input class="fi-crud" name="description" type="text" placeholder="Détails additionnels..." value="<?= htmlspecialchars($editGarantie['description'] ?? '') ?>" />
                  </div>
                </div>

                <div style="display:flex;gap:.8rem;margin-top:1rem;">
                  <button type="submit" class="btn-crud">
                    <?= $editGarantie ? '💾 Enregistrer' : '🔒 Ajouter la garantie' ?>
                  </button>
                  <?php if ($editGarantie): ?>
                    <a href="<?= $self ?>?tab=garantie" class="sc-link">✕ Annuler</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </div>

        </div><!-- /tab-garantie -->

        <!-- ══════════════════════════════════════════════════
             TAB 3: SIMULATEUR
        ══════════════════════════════════════════════════ -->
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

            <button class="apply-btn" onclick="switchTab('demande',null);document.querySelector('[onclick*=\'demande\']').click();">Faire ma demande →</button>
          </div>
        </div>

      </div><!-- /page-credits -->

      <!-- ════════════════════════════════════════════════════
           PAGE: TABLEAU DE BORD
      ════════════════════════════════════════════════════ -->
      <div class="page" id="page-dashboard">
        <div class="sc">
          <div class="sc-title">📊 Tableau de Bord</div>
          <div style="padding:2rem;text-align:center;color:var(--muted);">
            Tableau de bord à venir.
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════════
           PAGE: PROFIL
      ════════════════════════════════════════════════════ -->
      <div class="page" id="page-profil">
        <div class="sc">
          <div class="sc-title" style="margin-bottom:1rem;">👤 Mon Profil</div>
          <div style="padding:1.5rem;color:var(--muted);">
            Gestion du profil à venir.
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════════
           PAGE: MES CRÉDITS (DEMANDES TABLE)
      ════════════════════════════════════════════════════ -->
      <div class="page" id="page-mes-credits">
        <div class="sc">
          <div class="sc-hd">
            <div class="sc-title">📋 Vos demandes (<?= count($demandes) ?>)</div>
          </div>
          <?php if (empty($demandes)): ?>
            <div style="padding:2rem;text-align:center;color:var(--muted);">
              <div style="font-size:3rem;margin-bottom:.5rem;">📋</div>
              Aucune demande de crédit pour le moment.
            </div>
          <?php else: ?>
            <div style="overflow-x:auto;">
              <table class="tbl-crud">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Montant</th>
                    <th>Durée</th>
                    <th>Taux</th>
                    <th>Date</th>
                    <th style="text-align:center;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($demandes as $d): ?>
                    <tr>
                      <td><strong>#<?= (int) $d['id'] ?></strong></td>
                      <td><strong><?= number_format($d['montant'], 0, ',', ' ') ?> TND</strong></td>
                      <td><?= (int) $d['duree_mois'] ?> m</td>
                      <td><?= $d['taux_interet'] ?>%</td>
                      <td><?= htmlspecialchars($d['date_demande']) ?></td>
                      <td style="text-align:center;">
                        <div class="td-acts-row" style="justify-content:center;">
                          <a href="<?= $self ?>?edit_d=<?= $d['id'] ?>" class="btn-edt">✏️</a>
                          <form method="POST" action="<?= $self ?>" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                            <input type="hidden" name="action" value="delete_demande" />
                            <input type="hidden" name="id" value="<?= $d['id'] ?>" />
                            <button type="submit" class="btn-del">🗑️</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════════
           PAGE: MES GARANTIES (GARANTIES TABLE)
      ════════════════════════════════════════════════════ -->
      <div class="page" id="page-mes-garanties">
        <div class="sc">
          <div class="sc-hd">
            <div class="sc-title">🔒 Vos garanties (<?= count($garanties) ?>)</div>
          </div>
          <?php if (empty($garanties)): ?>
            <div style="padding:2rem;text-align:center;color:var(--muted);">
              <div style="font-size:3rem;margin-bottom:.5rem;">🔒</div>
              Aucune garantie pour le moment.
            </div>
          <?php else: ?>
            <div style="overflow-x:auto;">
              <table class="tbl-crud">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Demande</th>
                    <th>Type</th>
                    <th>Document</th>
                    <th>Valeur</th>
                    <th style="text-align:center;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($garanties as $g): ?>
                    <tr>
                      <td><strong>#<?= (int) $g['id'] ?></strong></td>
                      <td><span class="claim-st cs-open">#<?= (int) $g['demande_credit_id'] ?> <?= $g['dc_montant'] ? ' — ' . number_format($g['dc_montant'], 0, ',', ' ') . ' TND' : '' ?></span></td>
                      <td><?= $tgL[$g['type']] ?? htmlspecialchars($g['type']) ?></td>
                      <td>
                        <?php if (str_starts_with($g['document'], 'uploads/')): ?>
                          <a href="<?= $self ?>?action=download_garantie_file&id=<?= $g['id'] ?>" 
                             target="_blank" 
                             style="color:var(--blue);text-decoration:underline;cursor:pointer;">
                            📄 <?= htmlspecialchars(basename($g['document'])) ?> ↗
                          </a>
                        <?php else: ?>
                          <?= htmlspecialchars($g['document']) ?>
                        <?php endif; ?>
                      </td>
                      <td><strong><?= number_format($g['valeur_estimee'], 0, ',', ' ') ?> TND</strong></td>
                      <td style="text-align:center;">
                        <div class="td-acts-row" style="justify-content:center;">
                          <a href="<?= $self ?>?edit_g=<?= $g['id'] ?>&tab=garantie" class="btn-edt">✏️</a>
                          <form method="POST" action="<?= $self ?>" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                            <input type="hidden" name="action" value="delete_garantie" />
                            <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                            <button type="submit" class="btn-del">🗑️</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <script>
    function showPage(id, el) {
      document.querySelectorAll('.page').forEach(p => p.classList.remove('on'));
      document.getElementById('page-' + id).classList.add('on');
      document.querySelectorAll('.sb-item').forEach(s => s.classList.remove('on'));
      if (el) el.classList.add('on');
      const titles = { dashboard: 'Tableau de bord', credits: 'Gestion des Crédits', profil: 'Mon Profil', 'mes-credits': 'Mes demandes de crédit', 'mes-garanties': 'Mes garanties' };
      document.getElementById('page-title').textContent = titles[id] || id;
    }

    function switchTab(name, el) {
      ['demande', 'garantie', 'simulateur'].forEach(t => {
        document.getElementById('tab-' + t).style.display = (t === name) ? 'block' : 'none';
      });
      document.querySelectorAll('.tab-c').forEach(t => t.classList.remove('on'));
      if (el) el.classList.add('on');
      else document.querySelectorAll('.tab-c')[{ demande: 0, garantie: 1, simulateur: 2}[name]]?.classList.add('on');
    }

    document.getElementById('form-demande').addEventListener('submit', function (e) {
      let ok = true;
      const set = (id, msg) => { const el = document.getElementById(id); if (el) { el.textContent = msg; if (msg) ok = false; } };
      set('e-montant', (v => (!v || v < 1 || v > 1000000) ? 'Montant invalide (1–1 000 000 TND).' : '')(parseFloat(document.getElementById('f-montant').value)));
      set('e-duree', (v => (!v || v < 6 || v > 360) ? 'Durée invalide (6–360 mois).' : '')(parseInt(document.getElementById('f-duree').value)));
      set('e-taux', (v => (isNaN(v) || v < 0 || v > 30) ? 'Taux invalide (0–30 %).' : '')(parseFloat(document.getElementById('f-taux').value)));
      set('e-date', !document.getElementById('f-date').value ? 'Date requise.' : '');
      if (!ok) e.preventDefault();
    });

    document.getElementById('form-garantie').addEventListener('submit', function (e) {
      let ok = true;
      const set = (id, msg) => { const el = document.getElementById(id); if (el) { el.textContent = msg; if (msg) ok = false; } };
      if (document.getElementById('f-g-demande'))
        set('e-g-demande', !document.getElementById('f-g-demande').value ? 'Sélectionnez une demande.' : '');
      set('e-g-type', !document.getElementById('f-g-type').value ? 'Type obligatoire.' : '');
const docVal = document.getElementById('f-g-doc').value.trim();
const fileVal = document.getElementById('f-g-file')?.files?.length > 0;
set('e-g-doc', (!docVal && !fileVal) ? 'Référence ou fichier requis.' : '');
      set('e-g-valeur', (v => (isNaN(v) || v < 0) ? 'Valeur invalide.' : '')(parseFloat(document.getElementById('f-g-valeur').value)));
      if (!ok) e.preventDefault();
    });

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

    function calcCredit() {
      const r = document.querySelectorAll('#tab-simulateur input[type=range]');
      const l = parseInt(r[0].value), d = parseInt(r[1].value), t = parseFloat(r[2].value);
      document.getElementById('lv').textContent = l.toLocaleString() + ' TND';
      document.getElementById('dv').textContent = d + ' mois';
      document.getElementById('rv').textContent = t + '%';
      const mr = t / 100 / 12, mp = Math.round((l * (mr * Math.pow(1 + mr, d))) / (Math.pow(1 + mr, d) - 1)), tot = Math.round(mp * d);
      document.getElementById('mp').textContent = mp.toLocaleString() + ' TND';
      document.getElementById('cd').textContent = 'Total : ' + tot.toLocaleString() + ' TND · Coût : ' + (tot - l).toLocaleString() + ' TND';
    }

    // Initialize calculator on load
    document.addEventListener('DOMContentLoaded', calcCredit);
  </script>
</body>
</html>
