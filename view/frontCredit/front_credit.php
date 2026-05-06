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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Controller path for AJAX calls -->
  <script>
    // Make controller path available globally for geolocation AJAX
    window.CONTROLLER_PATH = '<?= htmlspecialchars($self) ?>';
    console.log('[Setup] Controller path:', window.CONTROLLER_PATH);
  </script>
  <script src="<?= htmlspecialchars($viewRoot) ?>/frontCredit/geolocation-form.js"></script>
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
      <a class="sb-item" onclick="showPage('statistique',this)">
        <span class="sb-ico">📊</span> Statistiques
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
        <div style="display:flex;gap:.4rem;">
  <a href="<?= htmlspecialchars($controllerRoot) ?>/ExportController.php?type=demandes" class="tb-btn" style="text-decoration:none;">📥 Demandes</a>
  <a href="<?= htmlspecialchars($controllerRoot) ?>/ExportController.php?type=garanties" class="tb-btn" style="text-decoration:none;">📥 Garanties</a>
</div>
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

            <!-- BULK ACTIONS PANEL -->
            <div id="bulk-actions-panel" style="display:none;padding:0.8rem;background:var(--info-bg);border-radius:0.4rem;margin-bottom:1rem;gap:1rem;align-items:center;border-left:3px solid var(--blue)">
              <span style="color:var(--blue);font-weight:500"><span id="bulk-count">0</span> sélectionné(e)(s)</span>
              <button onclick="bulkApprove()" class="btn-quick-approve" style="padding:0.4rem 0.8rem;font-size:0.85rem">✅ Approuver tout</button>
              <button onclick="bulkDelete()" class="btn-del" style="padding:0.4rem 0.8rem;font-size:0.85rem">🗑️ Supprimer tout</button>
            </div>
            <div style="overflow-x:auto">
              <table id="tbl-dem">
                <thead>
                  <tr>
                    <th style="width:35px"><input type="checkbox" id="check-all" onchange="toggleAllCheckboxes(this)"></th>
                    <th>#</th>
                    <th>Pays</th>
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
                      <td colspan="11" style="text-align:center;padding:2rem;color:var(--muted);font-size:.8rem">Aucun
                        dossier</td>
                    </tr>
                  <?php else: foreach ($demandes as $d): ?>
                      <tr data-resultat="<?= $d['resultat'] ?>">
                        <td><input type="checkbox" class="dem-checkbox" value="<?= $d['id'] ?>"></td>
                        <td>
                          <?= (int) $d['id'] ?>
                        </td>
                        <td style="text-align:center;cursor:pointer;" 
    onclick="openMapModal('<?= htmlspecialchars($d['country_code'] ?? 'TN') ?>', '<?= htmlspecialchars($d['ip_client'] ?? '') ?>')"
    title="Voir sur la carte">
  <?php
    $flags = ['TN'=>'🇹🇳','FR'=>'🇫🇷','US'=>'🇺🇸','GB'=>'🇬🇧','DE'=>'🇩🇪','IT'=>'🇮🇹','ES'=>'🇪🇸','CH'=>'🇨🇭','CA'=>'🇨🇦','BE'=>'🇧🇪','DZ'=>'🇩🇿','MA'=>'🇲🇦','LY'=>'🇱🇾'];
    $cc = $d['country_code'] ?? 'TN';
    echo ($flags[$cc] ?? '🌍') . ' ' . htmlspecialchars($cc);
  ?>
  <span style="font-size:0.6rem;display:block;color:var(--muted)">🗺️ voir carte</span>
</td>
<td><strong><?= number_format($d['montant'], 0, ',', ' ') ?> TND</strong></td>
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
                          style="color:var(--muted);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:pointer;"
                          title="Click to view full motif"
                          onclick="openMotifModal('<?= htmlspecialchars(addslashes($d['motif_resultat'] ?? ''), ENT_QUOTES) ?>')">
                          <?= htmlspecialchars(mb_substr($d['motif_resultat'] ?? '', 0, 40)) ?>    <?= mb_strlen($d['motif_resultat'] ?? '') > 40 ? '…' : '' ?>
                        </td>
                        <td>
                          <div class="td-acts">
                            <a href="<?= $self ?>?edit_d=<?= $d['id'] ?>" class="btn-edt">✏️ Modifier</a>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Approuver la demande #<?= $d['id'] ?> ?')">
                              <input type="hidden" name="action" value="approve_demande" />
                              <input type="hidden" name="id" value="<?= $d['id'] ?>" />
                              <button type="submit" class="btn-quick-approve">✅ Approuver</button>
                            </form>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Refuser la demande #<?= $d['id'] ?> ?')">
                              <input type="hidden" name="action" value="refuse_demande" />
                              <input type="hidden" name="id" value="<?= $d['id'] ?>" />
                              <button type="submit" class="btn-quick-refuse">❌ Refuser</button>
                            </form>
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

          <!-- Formulaire édition garantie (seulement si en mode édition) -->
          <?php if ($editGarantie): ?>
          <div class="dt-wrap" style="margin-bottom:1.2rem">
            <div class="dt-hd">
              <div class="dt-title">✏️ Modifier garantie #<?= (int) $editGarantie['id'] ?></div>
            </div>
            <div style="padding:1.2rem">
              <form method="POST" action="<?= $self ?>" id="form-gar" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="update_garantie" />
                <input type="hidden" name="id" value="<?= (int) $editGarantie['id'] ?>" />

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
                    <input class="fi-crud" name="document" id="g-doc" type="text"
                        placeholder="Référence officielle"
                        value="<?= htmlspecialchars($editGarantie['document'] ?? '') ?>" required />
                    <div class="field-hint" id="g-doc-hint">N° carte grise, titre propriété, nom garant…</div>
                    <div class="err-msg" id="ge-doc"></div>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Valeur estimée (TND) *</label>
                    <input class="fi-crud" name="valeur_estimee" id="g-val" type="number" min="0" step="100" 
                        placeholder="ex: 30000" value="<?= htmlspecialchars($editGarantie['valeur_estimee'] ?? '') ?>" required />
                    <div class="err-msg" id="ge-val"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud">Description</label>
                    <input class="fi-crud" name="description" type="text" placeholder="Détails…"
                        value="<?= htmlspecialchars($editGarantie['description'] ?? '') ?>" />
                  </div>
                </div>

                <div style="display:flex;gap:.6rem;align-items:center;margin-top:.9rem">
                  <button type="submit" class="btn-crud">💾 Enregistrer</button>
                  <a href="<?= $self ?>?tab=gar" class="fl" style="cursor:pointer">✕ Annuler</a>
                </div>
              </form>
            </div>
          </div>
          <?php endif; ?>

          <!-- Tableau garanties -->
          <div class="dt-wrap">
            <div class="dt-hd">
              <div class="dt-title">🔒 Gestion des garanties <span class="badge b-blue">
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
                    <th>Statut</th>
                    <th>Description</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($garanties)): ?>
                    <tr>
                      <td colspan="8" style="text-align:center;padding:2rem;color:var(--muted);font-size:.8rem">Aucune
                        garantie.</td>
                    </tr>
                  <?php else: foreach ($garanties as $g): 
                    $gStatus = $g['statut'] ?? 'en_attente';
                    $gStatusLabel = $rl[$gStatus] ?? $gStatus;
                    $gStatusBadge = $rc[$gStatus] ?? 'b-wait';
                    ?>
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
  <?php if (!empty($g['document']) && str_starts_with($g['document'], 'uploads/')): ?>
    <a href="<?= htmlspecialchars($controllerRoot) ?>/AdminCreditController.php?action=download_garantie_file&id=<?= (int)$g['id'] ?>" 
       target="_blank" 
       style="color:var(--blue);text-decoration:none;font-size:.75rem;">
      📎 Voir document
    </a>
  <?php else: ?>
    <?= htmlspecialchars($g['document'] ?: '—') ?>
  <?php endif; ?>
</td>
                        <td><strong>
                            <?= number_format($g['valeur_estimee'], 0, ',', ' ') ?> TND
                          </strong></td>
                        <td>
                          <span class="badge <?= $gStatusBadge ?>"><?= $gStatusLabel ?></span>
                        </td>
                        <td style="color:var(--muted);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                          title="<?= htmlspecialchars($g['description'] ?? '') ?>">
                          <?= htmlspecialchars($g['description'] ?: '—') ?>
                        </td>
                        <td>
                          <div class="td-acts" style="flex-direction: column; gap: 0.3rem;">
                            <div style="display: flex; gap: 4px;">
                              <a href="<?= $self ?>?edit_g=<?= $g['id'] ?>&tab=gar" class="btn-edt">✏️ Modifier</a>
                              <form method="POST" action="<?= $self ?>" style="display:inline"
                                onsubmit="return confirm('Supprimer cette garantie ?')">
                                <input type="hidden" name="action" value="delete_garantie" />
                                <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                                <button type="submit" class="btn-del">🗑️</button>
                              </form>
                            </div>
                            <?php if ($gStatus === 'en_attente'): ?>
                            <div style="display: flex; gap: 4px; font-size: 0.65rem;">
                              <form method="POST" action="<?= $self ?>" style="display:inline">
                                <input type="hidden" name="action" value="update_garantie_status" />
                                <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                                <input type="hidden" name="statut" value="approuvee" />
                                <button type="submit" class="btn-quick-approve" title="Approuver cette garantie">✅ Approuver</button>
                              </form>
                              <form method="POST" action="<?= $self ?>" style="display:inline">
                                <input type="hidden" name="action" value="update_garantie_status" />
                                <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                                <input type="hidden" name="statut" value="refusee" />
                                <button type="submit" class="btn-quick-refuse" title="Refuser cette garantie">❌ Refuser</button>
                              </form>
                            </div>
                            <?php endif; ?>
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

      <!-- ══════════════════════════════════════════
           PAGE : STATISTIQUES
      ══════════════════════════════════════════ -->
      <div class="page" id="page-statistique">

        <?php
          // ── Compute stats data ──────────────────────────────────────────────
          $totalDecided = ($stats['approuvee'] + $stats['refusee']);
          $approvalRate = $totalDecided > 0 ? round(($stats['approuvee'] / $totalDecided) * 100, 1) : 0;
          $refusalRate  = $totalDecided > 0 ? round(($stats['refusee']  / $totalDecided) * 100, 1) : 0;

          $approuveesList = array_filter($demandes, fn($d) => $d['resultat'] === 'approuvee');
          $avgMontant = count($approuveesList) ? array_sum(array_column($approuveesList, 'montant')) / count($approuveesList) : 0;
          $avgDuree   = count($approuveesList) ? array_sum(array_column($approuveesList, 'duree_mois')) / count($approuveesList) : 0;
          $avgTaux    = count($demandes) ? array_sum(array_column($demandes, 'taux_interet')) / count($demandes) : 0;

          // Monthly (last 6 months)
          $monthly = [];
          for ($i = 5; $i >= 0; $i--) {
            $mKey = date('Y-m', strtotime("-$i months"));
            $monthly[$mKey] = ['label' => date('M', strtotime("-$i months")), 'total' => 0, 'approuvee' => 0, 'refusee' => 0, 'montant' => 0.0];
          }
          foreach ($demandes as $d) {
            $mKey = substr($d['date_demande'], 0, 7);
            if (isset($monthly[$mKey])) {
              $monthly[$mKey]['total']++;
              if ($d['resultat'] === 'approuvee') { $monthly[$mKey]['approuvee']++; $monthly[$mKey]['montant'] += (float)$d['montant']; }
              if ($d['resultat'] === 'refusee')   { $monthly[$mKey]['refusee']++; }
            }
          }

          // Guarantee types
          $garTypes = [];
          foreach ($garanties as $g) {
            $t = $g['type'] ?? 'autre';
            $garTypes[$t] = ($garTypes[$t] ?? 0) + 1;
          }

          // Status breakdown
          $statusBreakdown = ['en_cours' => 0, 'traitee' => 0, 'annulee' => 0];
          foreach ($demandes as $d) {
            $s = $d['statut'] ?? 'en_cours';
            $statusBreakdown[$s] = ($statusBreakdown[$s] ?? 0) + 1;
          }

          $monthlyJson  = json_encode(array_values($monthly));
          $garTypesJson = json_encode($garTypes);
          $statusJson   = json_encode($statusBreakdown);
        ?>

        <!-- ── KPI Row ───────────────────────────────────────────── -->
        <div class="stat-kpi-grid">

          <div class="stat-kpi-card sk-blue">
            <div class="sk-icon">📋</div>
            <div class="sk-value"><?= $stats['total'] ?></div>
            <div class="sk-label">Total demandes</div>
            <div class="sk-sub">Toutes périodes</div>
          </div>

          <div class="stat-kpi-card sk-green">
            <div class="sk-icon">✅</div>
            <div class="sk-value"><?= $approvalRate ?>%</div>
            <div class="sk-label">Taux d'approbation</div>
            <div class="sk-sub"><?= $stats['approuvee'] ?> approuvées / <?= $totalDecided ?> décidées</div>
          </div>

          <div class="stat-kpi-card sk-amber">
            <div class="sk-icon">⏳</div>
            <div class="sk-value"><?= $stats['attente'] ?></div>
            <div class="sk-label">En attente</div>
            <div class="sk-sub">Décision en cours</div>
          </div>

          <div class="stat-kpi-card sk-rose">
            <div class="sk-icon">💰</div>
            <div class="sk-value"><?= number_format($stats['encours'], 0, ',', ' ') ?></div>
            <div class="sk-label">Encours (TND)</div>
            <div class="sk-sub">Montant total approuvé</div>
          </div>

          <div class="stat-kpi-card sk-purple">
            <div class="sk-icon">📐</div>
            <div class="sk-value"><?= number_format($avgMontant, 0, ',', ' ') ?></div>
            <div class="sk-label">Montant moy. (TND)</div>
            <div class="sk-sub">Sur dossiers approuvés</div>
          </div>

          <div class="stat-kpi-card sk-teal">
            <div class="sk-icon">📅</div>
            <div class="sk-value"><?= round($avgDuree) ?> mois</div>
            <div class="sk-label">Durée moyenne</div>
            <div class="sk-sub">Dossiers approuvés</div>
          </div>

        </div>

        <!-- ── Charts Row ──────────────────────────────────────────── -->
        <div class="stat-charts-row">

          <!-- Bar chart — monthly -->
          <div class="stat-chart-card" style="flex:2">
            <div class="sc-header">
              <div class="sc-title">📅 Activité mensuelle (6 derniers mois)</div>
              <div class="sc-legend">
                <span class="leg-dot" style="background:#3b82f6"></span>Total
                <span class="leg-dot" style="background:#10b981;margin-left:.8rem"></span>Approuvées
                <span class="leg-dot" style="background:#ef4444;margin-left:.8rem"></span>Refusées
              </div>
            </div>
            <div class="sc-body">
              <canvas id="chartMonthly" height="180"></canvas>
            </div>
          </div>

          <!-- Donut — résultats -->
          <div class="stat-chart-card" style="flex:1">
            <div class="sc-header">
              <div class="sc-title">🎯 Résultats</div>
            </div>
            <div class="sc-body" style="display:flex;align-items:center;justify-content:center;height:180px">
              <canvas id="chartResultat" width="180" height="180"></canvas>
            </div>
            <div class="donut-legend">
              <div><span class="leg-dot" style="background:#10b981"></span>Approuvées (<?= $stats['approuvee'] ?>)</div>
              <div><span class="leg-dot" style="background:#f59e0b"></span>En attente (<?= $stats['attente'] ?>)</div>
              <div><span class="leg-dot" style="background:#ef4444"></span>Refusées (<?= $stats['refusee'] ?>)</div>
            </div>
          </div>

        </div>

        <!-- ── Second Charts Row ──────────────────────────────────── -->
        <div class="stat-charts-row">

          <!-- Guarantee types -->
          <div class="stat-chart-card" style="flex:1">
            <div class="sc-header">
              <div class="sc-title">🔒 Types de garanties</div>
            </div>
            <div class="sc-body" style="display:flex;align-items:center;justify-content:center;height:160px">
              <canvas id="chartGarTypes" width="160" height="160"></canvas>
            </div>
            <div class="donut-legend">
              <?php foreach ($garTypes as $type => $cnt): ?>
              <div><span class="leg-dot" style="background:<?= ['vehicule'=>'#3b82f6','immobilier'=>'#a78bfa','garant'=>'#10b981','autre'=>'#f59e0b'][$type] ?? '#8b949e' ?>"></span>
                <?= $tgL[$type] ?? $type ?> (<?= $cnt ?>)
              </div>
              <?php endforeach; ?>
              <?php if (empty($garTypes)): ?><div style="color:var(--muted);font-size:.8rem">Aucune garantie</div><?php endif; ?>
            </div>
          </div>

          <!-- Statut dossiers -->
          <div class="stat-chart-card" style="flex:1">
            <div class="sc-header">
              <div class="sc-title">📂 Statut des dossiers</div>
            </div>
            <div class="sc-body">
              <div class="stat-bar-list">
                <?php
                  $statusLabels = ['en_cours' => ['En cours', '#f59e0b'], 'traitee' => ['Traitée', '#10b981'], 'annulee' => ['Annulée', '#ef4444']];
                  $totalSt = array_sum($statusBreakdown) ?: 1;
                  foreach ($statusBreakdown as $sk => $sv):
                    [$slLabel, $slColor] = $statusLabels[$sk];
                    $pct = round(($sv / $totalSt) * 100);
                ?>
                <div class="sbl-item">
                  <div class="sbl-label"><span><?= $slLabel ?></span><span class="sbl-count"><?= $sv ?></span></div>
                  <div class="sbl-track"><div class="sbl-fill" style="width:<?= $pct ?>%;background:<?= $slColor ?>"></div></div>
                  <div class="sbl-pct"><?= $pct ?>%</div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Taux intérêt moyen -->
          <div class="stat-chart-card" style="flex:1">
            <div class="sc-header">
              <div class="sc-title">📉 Indicateurs clés</div>
            </div>
            <div class="sc-body">
              <div class="stat-indicator-list">
                <div class="sil-item">
                  <div class="sil-label">Taux d'intérêt moyen</div>
                  <div class="sil-val" style="color:var(--amber)"><?= number_format($avgTaux, 2) ?>%</div>
                </div>
                <div class="sil-item">
                  <div class="sil-label">Taux de refus</div>
                  <div class="sil-val" style="color:var(--rose)"><?= $refusalRate ?>%</div>
                </div>
                <div class="sil-item">
                  <div class="sil-label">Durée moy. remboursement</div>
                  <div class="sil-val" style="color:var(--blue)"><?= round($avgDuree) ?> mois</div>
                </div>
                <div class="sil-item">
                  <div class="sil-label">Total garanties</div>
                  <div class="sil-val" style="color:var(--purple)"><?= count($garanties) ?></div>
                </div>
                <div class="sil-item">
                  <div class="sil-label">Garanties / Demande</div>
                  <div class="sil-val" style="color:var(--emerald)"><?= $stats['total'] ? round(count($garanties) / $stats['total'], 1) : 0 ?></div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div><!-- /page-statistique -->

    </div>
  </div>

  <!-- MOTIF MODAL -->
   <!-- MAP MODAL -->
<div id="map-modal" class="modal-overlay" style="display:none;" onclick="closeMapModal()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:700px;width:95%">
    <div class="modal-header">
      <div class="modal-title" id="map-modal-title">🗺️ Localisation client</div>
      <button class="modal-close" onclick="closeMapModal()">✕</button>
    </div>
    <div class="modal-body" style="padding:0">
      <iframe id="map-iframe" src="" width="100%" height="420" style="border:none;border-radius:0 0 0.5rem 0.5rem;" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
</div>
  <div id="motif-modal" class="modal-overlay" style="display:none;" onclick="closeMotifModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="modal-header">
        <div class="modal-title">📋 Motif complet</div>
        <button class="modal-close" onclick="closeMotifModal()">✕</button>
      </div>
      <div class="modal-body">
        <div id="motif-text" style="padding:1rem;background:var(--bg-alt);border-radius:0.5rem;line-height:1.6;word-wrap:break-word;white-space:pre-wrap;max-height:400px;overflow-y:auto;"></div>
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
      const titles = { dashboard: 'Dashboard Administration', credits: 'Dossiers Crédit', statistique: 'Statistiques', reclamations: 'Réclamations' };
      const bcs = { dashboard: "Admin › Vue d'ensemble", credits: 'Admin › Crédits', statistique: 'Admin › Statistiques', reclamations: 'Admin › Réclamations' };
      document.querySelector('.pt').textContent = titles[id] || id;
      document.querySelector('.bc').textContent = bcs[id] || '';
      if (id === 'statistique') { setTimeout(() => renderCharts(), 100); }
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

    const formGar = document.getElementById('form-gar');
    if (formGar) {
      formGar.addEventListener('submit', function (e) {
        let ok = true;
        const set = (id, msg) => { const el = document.getElementById(id); if (el) { el.textContent = msg; if (msg) ok = false; } };
        set('ge-type', !document.getElementById('g-type').value ? 'Type obligatoire.' : '');
        set('ge-doc', (v => (!v || v.length < 3) ? 'Document requis (min 3 car.).' : '')(document.getElementById('g-doc').value.trim()));
        set('ge-val', (v => (isNaN(v) || v < 0) ? 'Valeur invalide.' : '')(parseFloat(document.getElementById('g-val').value)));
        if (!ok) e.preventDefault();
      });
    }

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

    // ────── MOTIF MODAL ─────────────────────────────────────────────
    function openMotifModal(motif) {
      const modal = document.getElementById('motif-modal');
      const motifText = document.getElementById('motif-text');
      motifText.textContent = motif || '(Pas de motif)';
      modal.style.display = 'flex';
    }

    function closeMotifModal() {
      const modal = document.getElementById('motif-modal');
      modal.style.display = 'none';
    }
    // ────── MAP MODAL ────────────────────────────────────────────────
const countryCoords = {
  TN:{lat:33.8869,lon:9.5375,name:'Tunisie'},
  FR:{lat:46.2276,lon:2.2137,name:'France'},
  US:{lat:37.0902,lon:-95.7129,name:'États-Unis'},
  GB:{lat:55.3781,lon:-3.4360,name:'Royaume-Uni'},
  DE:{lat:51.1657,lon:10.4515,name:'Allemagne'},
  IT:{lat:41.8719,lon:12.5674,name:'Italie'},
  ES:{lat:40.4637,lon:-3.7492,name:'Espagne'},
  CH:{lat:46.8182,lon:8.2275,name:'Suisse'},
  CA:{lat:56.1304,lon:-106.3468,name:'Canada'},
  BE:{lat:50.5039,lon:4.4699,name:'Belgique'},
  DZ:{lat:28.0339,lon:1.6596,name:'Algérie'},
  MA:{lat:31.7917,lon:-7.0926,name:'Maroc'},
  LY:{lat:26.3351,lon:17.2283,name:'Libye'},
};

function openMapModal(countryCode, ip) {
  const modal = document.getElementById('map-modal');
  const iframe = document.getElementById('map-iframe');
  const title  = document.getElementById('map-modal-title');
  const info   = countryCoords[countryCode] || {lat:0,lon:0,name:countryCode};
  const zoom   = 5;

  title.textContent = '🗺️ ' + info.name + (ip ? '  —  IP: ' + ip : '');
  iframe.src = `https://www.openstreetmap.org/export/embed.html?bbox=${info.lon-8},${info.lat-6},${info.lon+8},${info.lat+6}&layer=mapnik&marker=${info.lat},${info.lon}`;
  modal.style.display = 'flex';
}

function closeMapModal() {
  document.getElementById('map-modal').style.display = 'none';
  document.getElementById('map-iframe').src = '';
}

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeMotifModal(); closeMapModal(); }
});

    // ────── CHECKBOX SELECTION ─────────────────────────────────────────────
    function toggleAllCheckboxes(checkAllCheckbox) {
      const checkboxes = document.querySelectorAll('.dem-checkbox');
      checkboxes.forEach(cb => cb.checked = checkAllCheckbox.checked);
      updateBulkActionButtons();
    }

    function updateBulkActionButtons() {
      const selectedCheckboxes = document.querySelectorAll('.dem-checkbox:checked');
      const bulkActionsPanel = document.getElementById('bulk-actions-panel');
      if (!bulkActionsPanel) return;
      
      if (selectedCheckboxes.length > 0) {
        bulkActionsPanel.style.display = 'flex';
        document.getElementById('bulk-count').textContent = selectedCheckboxes.length;
      } else {
        bulkActionsPanel.style.display = 'none';
      }
    }

    // Add change listeners to checkboxes
    document.querySelectorAll('.dem-checkbox').forEach(cb => {
      cb.addEventListener('change', function() {
        updateBulkActionButtons();
        const allCheckboxes = document.querySelectorAll('.dem-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.dem-checkbox:checked');
        const checkAll = document.getElementById('check-all');
        if (checkAll) {
          checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
        }
      });
    });

    function getBulkActionIds() {
      const selectedCheckboxes = document.querySelectorAll('.dem-checkbox:checked');
      return Array.from(selectedCheckboxes).map(cb => cb.value);
    }

    function bulkApprove() {
      const ids = getBulkActionIds();
      if (ids.length === 0) {
        alert('Veuillez sélectionner au moins une demande.');
        return;
      }
      if (!confirm(`Approuver ${ids.length} demande(s) ?`)) return;
      
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '<?= $self ?>';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'bulk_approve_demandes';
      form.appendChild(actionInput);
      
      ids.forEach(id => {
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'ids[]';
        idInput.value = id;
        form.appendChild(idInput);
      });
      
      document.body.appendChild(form);
      form.submit();
    }

    function bulkDelete() {
      const ids = getBulkActionIds();
      if (ids.length === 0) {
        alert('Veuillez sélectionner au moins une demande.');
        return;
      }
      if (!confirm(`Supprimer ${ids.length} demande(s) ? Cette action est irréversible.`)) return;
      
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '<?= $self ?>';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'bulk_delete_demandes';
      form.appendChild(actionInput);
      
      ids.forEach(id => {
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'ids[]';
        idInput.value = id;
        form.appendChild(idInput);
      });
      
      document.body.appendChild(form);
      form.submit();
    }

    // Auto-activate correct page on load
    showPage('credits', document.querySelector('.sb-item[onclick*="credits"]'));
  <?php if ($activeTab === 'gar'): ?>switchTab('gar', null); <?php endif; ?>

    // ────── STATISTICS CHARTS ─────────────────────────────────────────────
    const monthlyData = <?= $monthlyJson ?>;
    const garTypesData = <?= $garTypesJson ?>;
    const statusData = <?= $statusJson ?>;
    let chartsInst = {};

    function renderCharts() {
      // Monthly Activity Chart
      const ctxMonthly = document.getElementById('chartMonthly');
      if (ctxMonthly && !chartsInst.monthly) {
        chartsInst.monthly = new Chart(ctxMonthly, {
          type: 'bar',
          data: {
            labels: monthlyData.map(m => m.label),
            datasets: [
              {
                label: 'Total',
                data: monthlyData.map(m => m.total),
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                barPercentage: 0.7
              },
              {
                label: 'Approuvées',
                data: monthlyData.map(m => m.approuvee),
                backgroundColor: '#10b981',
                borderRadius: 4,
                barPercentage: 0.7
              },
              {
                label: 'Refusées',
                data: monthlyData.map(m => m.refusee),
                backgroundColor: '#ef4444',
                borderRadius: 4,
                barPercentage: 0.7
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } },
              x: { grid: { display: false } }
            }
          }
        });
      }

      // Results Donut Chart
      const ctxRes = document.getElementById('chartResultat');
      if (ctxRes && !chartsInst.resultat) {
        chartsInst.resultat = new Chart(ctxRes, {
          type: 'doughnut',
          data: {
            labels: ['Approuvées', 'En attente', 'Refusées'],
            datasets: [{
              data: [<?= $stats['approuvee'] ?>, <?= $stats['attente'] ?>, <?= $stats['refusee'] ?>],
              backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
          }
        });
      }

      // Guarantee Types Chart
      const ctxGar = document.getElementById('chartGarTypes');
      if (ctxGar && !chartsInst.garTypes) {
        const garLabels = Object.keys(garTypesData);
        const garValues = Object.values(garTypesData);
        const colors = { vehicule: '#3b82f6', immobilier: '#a78bfa', garant: '#10b981', autre: '#f59e0b' };
        chartsInst.garTypes = new Chart(ctxGar, {
          type: 'doughnut',
          data: {
            labels: garLabels.map(l => ({'vehicule':'🚗 Véhicule','immobilier':'🏠 Immobilier','garant':'🤝 Garant','autre':'📄 Autre'}[l] || l)),
            datasets: [{
              data: garValues,
              backgroundColor: garLabels.map(l => colors[l] || '#8b949e'),
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
          }
        });
      }
    }

    // Render charts when page loads if statistique is visible
    if (document.getElementById('page-statistique').classList.contains('on')) {
      renderCharts();
    }
  </script>
</body>

</html>