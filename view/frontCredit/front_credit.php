<?php
$sc = ['en_cours' => 'b-wait', 'traitee' => 'b-on', 'annulee' => 'b-off'];
$sl = ['en_cours' => 'En cours', 'traitee' => 'Traitée', 'annulee' => 'Annulée'];
$rc = ['en_attente' => 'b-wait', 'approuvee' => 'b-on', 'refusee' => 'b-off'];
$rl = ['en_attente' => 'En attente', 'approuvee' => 'Approuvée', 'refusee' => 'Refusée'];
$tgL = ['vehicule' => '🚗 Véhicule', 'immobilier' => '🏠 Immobilier', 'garant' => '🤝 Garant', 'autre' => '📄 Autre'];

$activeTab = $activeTab ?? 'dem';
$errors = $errors ?? [];
$success = $success ?? '';
$demandes = $demandes ?? [];
$garanties = $garanties ?? [];
$demandesSelect = $demandesSelect ?? [];
$editDemande = $editDemande ?? null;
$editGarantie = $editGarantie ?? null;
$dbError = $dbError ?? false;
$stats = $stats ?? ['total' => 0, 'attente' => 0, 'approuvee' => 0, 'refusee' => 0, 'encours' => 0];

// $controllerSelf is injected by AdminCreditController — always points to the controller, never this view file
$self = $controllerSelf ?? $_SERVER['SCRIPT_NAME'];
$viewRoot = defined('VIEW_URL') ? VIEW_URL : '';
$controllerRoot = defined('BASE_URL') ? BASE_URL . '/controller' : '';
?>
<!DOCTYPE html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>LegalFin — Back Office Admin</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($viewRoot) ?>/frontCredit/creditttt.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@500;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap"
    rel="stylesheet" />
  
</head>

<body>
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-top">
      <div class="sb-brand">🏦 Legal<span class="hl">Fin</span> <span class="sb-env">ADMIN</span></div>
    </div>
    <div class="sb-admin">
      <div class="sb-av">SA</div>
      <div>
        <div class="sb-aname">Administrateur</div>
        <div class="sb-arole">Administrateur Système</div>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-sec">Vue d'ensemble</div>
      <a class="sb-item" onclick="showPage('dashboard',this)"><span class="sb-ico">📊</span> Dashboard</a>
      <div class="sb-sec">Gestion</div>
      <a class="sb-item on" onclick="showPage('credits',this)">
        <span class="sb-ico">📈</span> Crédits
        <span class="sb-badge ba">
          <?= count($demandes) ?>
        </span>
      </a>
      <div class="sb-sec">Support</div>
      <a class="sb-item" onclick="showPage('reclamations',this)"><span class="sb-ico">📣</span> Réclamations <span
          class="sb-badge br">5</span></a>
    </nav>
    <div class="sb-status">
      <div class="st-row"><span class="std" style="background:var(--emerald)"></span>API Core — Opérationnel</div>
      <div class="st-row"><span class="std" style="background:var(--emerald)"></span>Base de données — OK</div>
      <div class="st-row"><span class="std" style="background:var(--amber)"></span>Email Service — Latence</div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="tb-left">
        <div class="pt" id="page-title">Dashboard Administration</div>
        <div class="bc" id="page-bc">Admin › Vue d'ensemble</div>
      </div>
      <div class="tb-right">
        <div class="live"><span class="ldot"></span> Live</div>
        <button class="tb-btn">📥 Exporter</button>
        <a href="<?= htmlspecialchars($controllerRoot) ?>/AdminCreditController.php" class="btn-frontoffice">👤 Back →</a>
      </div>
    </header>

    <div class="content">

      <!-- DASHBOARD -->
      <div class="page on" id="page-dashboard">
        <div class="kpi-grid">
          <div class="kpi kb">
            <div class="kpi-top"><span class="kpi-ico">📋</span></div>
            <div class="kpi-val" style="color:var(--blue)">
              <?= $stats['total'] ?>
            </div>
            <div class="kpi-lbl">Total demandes</div>
          </div>
          <div class="kpi ke">
            <div class="kpi-top"><span class="kpi-ico">✅</span></div>
            <div class="kpi-val" style="color:var(--emerald)">
              <?= $stats['approuvee'] ?>
            </div>
            <div class="kpi-lbl">Approuvées</div>
          </div>
          <div class="kpi ka">
            <div class="kpi-top"><span class="kpi-ico">⏳</span></div>
            <div class="kpi-val" style="color:var(--amber)">
              <?= $stats['attente'] ?>
            </div>
            <div class="kpi-lbl">En attente</div>
          </div>
          <div class="kpi kr">
            <div class="kpi-top"><span class="kpi-ico">💰</span></div>
            <div class="kpi-val" style="color:var(--rose)">
              <?= number_format($stats['encours'], 0, ',', ' ') ?> TND
            </div>
            <div class="kpi-lbl">Encours approuvé</div>
          </div>
        </div>
      </div>

      <!-- CRÉDITS -->
      <div class="page" id="page-credits">

        <?php if (isset($dbStatus) && !$dbStatus['ok']): ?>
          <div class="alert-err">⚠️ Base de données indisponible : <?= htmlspecialchars($dbStatus['error']) ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
          <div class="no-db">⚠️ Base de données indisponible.</div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert-ok">✅
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert-err">⚠️ Erreurs :
            <ul>
              <?php foreach ($errors as $e): ?>
                <li>
                  <?= htmlspecialchars($e) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- KPIs crédits -->
        <div class="carte-stats" style="margin-bottom:1rem">
          <div class="cs-card">
            <div class="cs-val" style="color:var(--amber)">
              <?= $stats['attente'] ?>
            </div>
            <div class="cs-lbl">En attente</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--emerald)">
              <?= $stats['approuvee'] ?>
            </div>
            <div class="cs-lbl">Approuvées</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--rose)">
              <?= $stats['refusee'] ?>
            </div>
            <div class="cs-lbl">Refusées</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--blue)">
              <?= number_format($stats['encours'], 0, ',', ' ') ?> TND
            </div>
            <div class="cs-lbl">Encours total</div>
          </div>
        </div>

        <!-- TABS -->
        <div class="tabs-a">
          <div class="tab-a <?= $activeTab === 'dem' ? 'on' : '' ?>" onclick="switchTab('dem',this)">📋 Demandes de crédit
          </div>
          <div class="tab-a <?= $activeTab === 'gar' ? 'on' : '' ?>" onclick="switchTab('gar',this)">🔒 Garanties</div>
        </div>

        <!-- ══════════════════════
             TAB : DEMANDES
        ══════════════════════ -->
        <div id="tab-dem" style="display:<?= $activeTab === 'dem' ? 'block' : 'none' ?>">

          <!-- Tableau -->
          <div class="dt-wrap" style="margin-bottom:1.2rem">
            <div class="dt-hd">
              <div style="display:flex;align-items:center;gap:.7rem">
                <div class="dt-title">📈 Dossiers Crédit & Garanties</div>
                <span class="badge b-blue">DemandeCredit / Garantie</span>
              </div>
              <div class="dt-acts">
                <input type="text" id="search-dem" class="fi" placeholder="🔍 Rechercher..." style="width:180px"
                  oninput="filterTable('tbl-dem','search-dem')" />
                <select id="filter-res" class="fs" style="width:130px" onchange="filterTable('tbl-dem','search-dem')">
                  <option value="">Tous</option>
                  <option value="en_attente">⏳ En attente</option>
                  <option value="approuvee">✅ Approuvée</option>
                  <option value="refusee">❌ Refusée</option>
                </select>
              </div>
            </div>
            <div style="overflow-x:auto">
              <table id="tbl-dem">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Montant</th>
                    <th>Durée</th>
                    <th>Taux</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Résultat</th>
                    <th>Motif</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($demandes)): ?>
                    <tr>
                      <td colspan="9" style="text-align:center;padding:2rem;color:var(--muted);font-size:.8rem">Aucun
                        dossier</td>
                    </tr>
                  <?php else: foreach ($demandes as $d): ?>
                      <tr data-resultat="<?= $d['resultat'] ?>">
                        <td>
                          <?= (int) $d['id'] ?>
                        </td>
                        <td><strong>
                            <?= number_format($d['montant'], 0, ',', ' ') ?> TND
                          </strong></td>
                        <td>
                          <?= (int) $d['duree_mois'] ?> mois
                        </td>
                        <td>
                          <?= $d['taux_interet'] ?>%
                        </td>
                        <td>
                          <?= htmlspecialchars($d['date_demande']) ?>
                        </td>
                        <td><span class="badge <?= $sc[$d['statut']] ?? 'b-wait' ?>"><?= $sl[$d['statut']] ?? $d['statut'] ?></span></td>
                        <td><span class="badge <?= $rc[$d['resultat']] ?? 'b-wait' ?>"><?= $rl[$d['resultat']] ?? $d['resultat'] ?></span></td>
                        <td
                          style="color:var(--muted);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                          title="<?= htmlspecialchars($d['motif_resultat'] ?? '') ?>">
                          <?= htmlspecialchars(mb_substr($d['motif_resultat'] ?? '', 0, 40)) ?>    <?= mb_strlen($d['motif_resultat'] ?? '') > 40 ? '…' : '' ?>
                        </td>
                        <td>
                          <div class="td-acts">
                            <a href="<?= $self ?>?edit_d=<?= $d['id'] ?>" class="btn-edt">✏️ Modifier</a>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Supprimer la demande #<?= $d['id'] ?> ?')">
                              <input type="hidden" name="action" value="delete_demande" />
                              <input type="hidden" name="id" value="<?= $d['id'] ?>" />
                              <button type="submit" class="btn-del">🗑️</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Formulaire édition -->
          <?php if ($editDemande): ?>
            <div class="dt-wrap">
              <div class="dt-hd">
                <div class="dt-title">✏️ Modifier demande #
                  <?= (int) $editDemande['id'] ?>
                </div>
              </div>
              <div style="padding:1.2rem">
                <form method="POST" action="<?= $self ?>" id="form-edit-dem" novalidate>
                  <input type="hidden" name="action" value="update_demande" />
                  <input type="hidden" name="id" value="<?= (int) $editDemande['id'] ?>" />

                  <div class="form-row-2">
                    <div class="fg-crud"><label class="fl-crud">Montant (TND) *</label><input class="fi-crud"
                        id="a-montant" name="montant" type="number"  step="100"
                        value="<?= htmlspecialchars($editDemande['montant']) ?>" />
                      <div class="err-msg" id="ae-montant"></div>
                    </div>
                    <div class="fg-crud"><label class="fl-crud">Durée (mois) *</label><input class="fi-crud" id="a-duree"
                        name="duree_mois" type="number" 
                        value="<?= htmlspecialchars($editDemande['duree_mois']) ?>"  />
                      <div class="err-msg" id="ae-duree"></div>
                    </div>
                  </div>
                  <div class="form-row-2">
                    <div class="fg-crud"><label class="fl-crud">Taux (%) *</label><input class="fi-crud" id="a-taux"
                        name="taux_interet" type="number"  step="0.1"
                        value="<?= htmlspecialchars($editDemande['taux_interet']) ?>"  />
                      <div class="err-msg" id="ae-taux"></div>
                    </div>
                    <div class="fg-crud"><label class="fl-crud">Date de traitement</label><input class="fi-crud"
                        name="date_traitement" type="date"
                        value="<?= htmlspecialchars($editDemande['date_traitement'] ?? '') ?>" /></div>
                  </div>

                  <div class="decision-row">
                    <div class="decision-title">🏛️ Décision administrative</div>
                    <div class="form-row-2">
                      <div class="fg-crud">
                        <label class="fl-crud">Statut *</label>
                        <select class="fs-crud" name="statut" id="a-statut">
                          <?php foreach (['en_cours' => 'En cours', 'traitee' => 'Traitée', 'annulee' => 'Annulée'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $editDemande['statut'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="fg-crud">
                        <label class="fl-crud">Résultat *</label>
                        <select class="fs-crud" name="resultat" id="a-resultat">
                          <?php foreach (['en_attente' => '⏳ En attente', 'approuvee' => '✅ Approuvée', 'refusee' => '❌ Refusée'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $editDemande['resultat'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="fg-crud">
                      <label class="fl-crud">Motif * <span
                          style="color:var(--muted);font-weight:400;text-transform:none">(obligatoire si approuvée /
                          refusée)</span></label>
                      <textarea class="fta-crud" id="a-motif" name="motif_resultat"
                        placeholder="Explication de la décision..."><?= htmlspecialchars($editDemande['motif_resultat'] ?? '') ?></textarea>
                      <div class="err-msg" id="ae-motif"></div>
                    </div>
                  </div>

                  <div style="display:flex;gap:.6rem;align-items:center;margin-top:.9rem">
                    <button type="submit" class="btn-crud">💾 Enregistrer</button>
                    <button type="button" class="btn-crud-red" onclick="quickDecision('refusee')">❌ Refuser</button>
                    <button type="button" class="btn-crud-green" onclick="quickDecision('approuvee')">✅ Approuver</button>
                    <a href="<?= $self ?>" class="fl" style="margin-left:.4rem;cursor:pointer">✕ Annuler</a>
                  </div>
                </form>
              </div>
            </div>
          <?php endif; ?>

        </div><!-- /tab-dem -->

        <!-- ══════════════════
             TAB : GARANTIES
        ══════════════════ -->
        <div id="tab-gar" style="display:<?= $activeTab === 'gar' ? 'block' : 'none' ?>">

          <!-- Formulaire garantie -->
          <div class="dt-wrap" style="margin-bottom:1.2rem">
            <div class="dt-hd">
              <div class="dt-title">
                <?= $editGarantie ? '✏️ Modifier garantie #' . (int) $editGarantie['id'] : '➕ Ajouter une garantie' ?>
              </div>
            </div>
            <div style="padding:1.2rem">
              <form method="POST" action="<?= $self ?>" id="form-gar" novalidate>
                <input type="hidden" name="action"
                  value="<?= $editGarantie ? 'update_garantie' : 'create_garantie' ?>" />
                <?php if ($editGarantie): ?><input type="hidden" name="id"
                    value="<?= (int) $editGarantie['id'] ?>" /><?php endif; ?>

                <?php if (!$editGarantie): ?>
                  <div class="fg-crud">
                    <label class="fl-crud">Demande associée *</label>
                    <select class="fs-crud" name="demande_credit_id" id="g-demande" required>
                      <option value="">— Sélectionner —</option>
                      <?php foreach ($demandesSelect as $ds): ?>
                        <option value="<?= $ds['id'] ?>">#<?= $ds['id'] ?> — <?= number_format($ds['montant'], 0, ',', ' ') ?>
                          TND — <?= $ds['date_demande'] ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="err-msg" id="ge-demande"></div>
                  </div>
                <?php endif; ?>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Type *</label>
                    <select class="fs-crud" name="type" id="g-type" onchange="updateGarDocLabel(this.value)" required>
                      <option value="">— Choisir —</option>
                      <?php foreach (['vehicule' => '🚗 Véhicule', 'immobilier' => '🏠 Immobilier', 'garant' => '🤝 Garant', 'autre' => '📄 Autre'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($editGarantie['type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="err-msg" id="ge-type"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud" id="g-doc-lbl">Document *</label>
                    <input class="fi-crud" name="document" id="g-doc" type="text" placeholder="Référence officielle"
                      value="<?= htmlspecialchars($editGarantie['document'] ?? '') ?>" required />
                    <div class="field-hint" id="g-doc-hint">N° carte grise, titre propriété, nom garant…</div>
                    <div class="err-msg" id="ge-doc"></div>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="fg-crud"><label class="fl-crud">Valeur estimée (TND) *</label><input class="fi-crud"
                      name="valeur_estimee" id="g-val" type="number" min="0" step="100" placeholder="ex: 30000"
                      value="<?= htmlspecialchars($editGarantie['valeur_estimee'] ?? '') ?>" required />
                    <div class="err-msg" id="ge-val"></div>
                  </div>
                  <div class="fg-crud"><label class="fl-crud">Description</label><input class="fi-crud"
                      name="description" type="text" placeholder="Détails…"
                      value="<?= htmlspecialchars($editGarantie['description'] ?? '') ?>" /></div>
                </div>

                <button type="submit" class="btn-crud">
                  <?= $editGarantie ? '💾 Enregistrer' : '🔒 Ajouter' ?>
                </button>
                <?php if ($editGarantie): ?>
                  <a href="<?= $self ?>?tab=gar" class="fl" style="margin-left:.6rem;cursor:pointer">✕ Annuler</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <!-- Tableau garanties -->
          <div class="dt-wrap">
            <div class="dt-hd">
              <div class="dt-title">🔒 Toutes les garanties <span class="badge b-blue">
                  <?= count($garanties) ?>
                </span></div>
              <input type="text" id="search-gar" class="fi" placeholder="🔍 Rechercher..." style="width:180px"
                oninput="filterTable('tbl-gar','search-gar')" />
            </div>
            <div style="overflow-x:auto">
              <table id="tbl-gar">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Demande</th>
                    <th>Type</th>
                    <th>Document</th>
                    <th>Valeur</th>
                    <th>Description</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($garanties)): ?>
                    <tr>
                      <td colspan="7" style="text-align:center;padding:2rem;color:var(--muted);font-size:.8rem">Aucune
                        garantie.</td>
                    </tr>
                  <?php else: foreach ($garanties as $g): ?>
                      <tr>
                        <td>
                          <?= (int) $g['id'] ?>
                        </td>
                        <td><span class="badge b-wait">#
                            <?= (int) $g['demande_credit_id'] ?>
                            <?= $g['dc_montant'] ? ' — ' . number_format($g['dc_montant'], 0, ',', ' ') . ' TND' : '' ?>
                          </span></td>
                        <td>
                          <?= $tgL[$g['type']] ?? htmlspecialchars($g['type']) ?>
                        </td>
                        <td>
                          <?= htmlspecialchars($g['document']) ?>
                        </td>
                        <td><strong>
                            <?= number_format($g['valeur_estimee'], 0, ',', ' ') ?> TND
                          </strong></td>
                        <td style="color:var(--muted)">
                          <?= htmlspecialchars($g['description'] ?: '—') ?>
                        </td>
                        <td>
                          <div class="td-acts">
                            <a href="<?= $self ?>?edit_g=<?= $g['id'] ?>&tab=gar" class="btn-edt">✏️</a>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Supprimer ?')">
                              <input type="hidden" name="action" value="delete_garantie" />
                              <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                              <button type="submit" class="btn-del">🗑️</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div><!-- /tab-gar -->

      </div><!-- /page-credits -->

      <div class="page" id="page-reclamations">
        <div class="dt-wrap">
          <div class="dt-hd">
            <div class="dt-title">📣 Réclamations</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    function showPage(id, el) {
      document.querySelectorAll('.page').forEach(p => p.classList.remove('on'));
      const pg = document.getElementById('page-' + id);
      if (pg) pg.classList.add('on');
      document.querySelectorAll('.sb-item').forEach(s => s.classList.remove('on'));
      if (el) el.classList.add('on');
      const titles = { dashboard: 'Dashboard Administration', credits: 'Dossiers Crédit', reclamations: 'Réclamations' };
      const bcs = { dashboard: "Admin › Vue d'ensemble", credits: 'Admin › Crédits', reclamations: 'Admin › Réclamations' };
      document.querySelector('.pt').textContent = titles[id] || id;
      document.querySelector('.bc').textContent = bcs[id] || '';
    }

    function switchTab(name, el) {
      ['dem', 'gar'].forEach(t => document.getElementById('tab-' + t).style.display = (t === name) ? 'block' : 'none');
      document.querySelectorAll('.tab-a').forEach(t => t.classList.remove('on'));
      if (el) el.classList.add('on');
      else document.querySelectorAll('.tab-a')[{ dem: 0, gar: 1 }[name]]?.classList.add('on');
    }

    const formDem = document.getElementById('form-edit-dem');
    if (formDem) formDem.addEventListener('submit', function (e) {
      let ok = true;
      const set = (id, msg) => { document.getElementById(id).textContent = msg; if (msg) ok = false; };
      set('ae-montant', (v => (!v || v < 1 || v > 1000000) ? 'Montant invalide.' : '')(parseFloat(document.getElementById('a-montant')?.value)));
      set('ae-duree', (v => (!v || v < 6 || v > 360) ? 'Durée invalide.' : '')(parseInt(document.getElementById('a-duree')?.value)));
      set('ae-taux', (v => (isNaN(v) || v < 0 || v > 30) ? 'Taux invalide.' : '')(parseFloat(document.getElementById('a-taux')?.value)));
      const res = document.getElementById('a-resultat')?.value;
      const mot = document.getElementById('a-motif')?.value.trim();
      set('ae-motif', (res !== 'en_attente' && !mot) ? 'Motif obligatoire pour une décision finale.' : '');
      if (!ok) e.preventDefault();
    });

    document.getElementById('form-gar').addEventListener('submit', function (e) {
      let ok = true;
      const set = (id, msg) => { const el = document.getElementById(id); if (el) { el.textContent = msg; if (msg) ok = false; } };
      if (document.getElementById('g-demande'))
        set('ge-demande', !document.getElementById('g-demande').value ? 'Sélectionnez une demande.' : '');
      set('ge-type', !document.getElementById('g-type').value ? 'Type obligatoire.' : '');
      set('ge-doc', (v => (!v || v.length < 3) ? 'Document requis (min 3 car.).' : '')(document.getElementById('g-doc').value.trim()));
      set('ge-val', (v => (isNaN(v) || v < 0) ? 'Valeur invalide.' : '')(parseFloat(document.getElementById('g-val').value)));
      if (!ok) e.preventDefault();
    });

    function quickDecision(type) {
      const res = document.getElementById('a-resultat'), stat = document.getElementById('a-statut'), mot = document.getElementById('a-motif');
      if (res) res.value = type;
      if (stat) stat.value = 'traitee';
      if (mot && !mot.value.trim())
        mot.placeholder = type === 'approuvee' ? 'Ex: Dossier complet, garantie suffisante.' : 'Ex: Garantie insuffisante.';
      document.getElementById('form-edit-dem')?.requestSubmit();
    }

    function updateGarDocLabel(val) {
      const map = { vehicule: ['N° carte grise *', 'Ex: 123456TUN'], immobilier: ['N° titre propriété *', 'Ex: TP-2021-88821'], garant: ['Nom complet du garant *', 'Prénom Nom'], autre: ['Référence document *', 'Toute référence officielle'] };
      if (val && map[val]) {
        document.getElementById('g-doc-lbl').textContent = map[val][0];
        document.getElementById('g-doc-hint').textContent = map[val][1];
        document.getElementById('g-doc').placeholder = map[val][1];
      }
    }

    function filterTable(tblId, searchId) {
      const term = document.getElementById(searchId)?.value.toLowerCase() || '';
      const resF = document.getElementById('filter-res')?.value || '';
      document.querySelectorAll('#' + tblId + ' tbody tr').forEach(row => {
        const matchT = !term || row.textContent.toLowerCase().includes(term);
        const matchR = !resF || row.dataset.resultat === resF;
        row.style.display = (matchT && matchR) ? '' : 'none';
      });
    }

    // Auto-activate correct page on load
    showPage('credits', document.querySelector('.sb-item[onclick*="credits"]'));
  <?php if ($activeTab === 'gar'): ?>switchTab('gar', null); <?php endif; ?>
  </script>
</body>

</html>