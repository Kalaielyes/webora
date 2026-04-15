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

// $controllerSelf is injected by CreditController — always points to the controller, never this view file
$self = $controllerSelf ?? $_SERVER['SCRIPT_NAME'];
$viewRoot = defined('VIEW_URL') ? VIEW_URL : '';
$controllerRoot = defined('BASE_URL') ? BASE_URL . '/controller' : '';
?>
<!DOCTYPE html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>LegalFinAI — Espace Client</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($viewRoot) ?>/backCredit/creditttttttttttttttt.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@500;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap"
    rel="stylesheet" />

</head>

<body>
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-logo">🏦 Legal<span>Fin</span></div>
    <div class="sb-user">
      <div class="sb-av">AK</div>
      <div>
        <div class="sb-uname">Nom Prénom</div>
        <div class="sb-badge"><span class="sb-dot"></span> Compte vérifié</div>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-sec">Principal</div>
      <a class="sb-item" onclick="showPage('dashboard',this)"><span class="sb-ico">📊</span> Tableau de bord</a>
      <a class="sb-item on" onclick="showPage('credits',this)"><span class="sb-ico">📈</span> Crédits & Épargne</a>
      <div class="sb-sec">Support</div>
      <a class="sb-item" onclick="showPage('reclamations',this)"><span class="sb-ico">📣</span> Réclamations</a>
      <div class="sb-sec">Compte</div>
      <a class="sb-item" onclick="showPage('profil',this)"><span class="sb-ico">👤</span> Mon Profil</a>
    </nav>
    <div class="sb-footer">
      <div class="sb-ft-btn">🚪 Déconnexion</div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="tb-title" id="page-title">Crédits & Épargne</div>
      <div class="tb-right">
        <div class="live"><span class="ldot"></span> Données en direct</div>
        <a href="<?= htmlspecialchars($controllerRoot) ?>/CreditController.php" class="btn-backoffice">🛡️ front
          →</a>
      </div>
    </header>

    <div class="content">

      <!-- ═══ DASHBOARD ═══ -->
      <div class="page" id="page-dashboard">
        <div class="bank-card">
          <div class="bc-lbl">Solde disponible</div>
          <div class="bc-bal">— —<span style="font-size:1rem;color:var(--muted)"> TND</span></div>
          <div class="bc-row">
            <div class="bc-item">
              <div class="v" style="color:var(--emerald)">+4 200 TND</div>
              <div class="l">Revenus</div>
            </div>
            <div class="bc-item">
              <div class="v" style="color:var(--rose)">-1 840 TND</div>
              <div class="l">Dépenses</div>
            </div>
            <div class="bc-item">
              <div class="v" style="color:var(--cyan)">+890 TND</div>
              <div class="l">Épargne</div>
            </div>
          </div>
          <div class="bc-num">•••• •••• •••• ——</div>
        </div>
        <div class="qa-grid">
          <div class="qa" onclick="showPage('credits',null)">
            <div class="qa-ico">📈</div>
            <div class="qa-lbl">Crédit</div>
            <div class="qa-sub">Simuler & demander</div>
          </div>
          <div class="qa" onclick="showPage('reclamations',null)">
            <div class="qa-ico">📣</div>
            <div class="qa-lbl">Réclamation</div>
            <div class="qa-sub">Soumettre un ticket</div>
          </div>
        </div>
      </div>

      <!-- ═══ CRÉDITS ═══ -->
      <div class="page on" id="page-credits">

        <?php if (isset($dbStatus) && !$dbStatus['ok']): ?>
          <div class="alert-err">⚠️ Base de données indisponible : <?= htmlspecialchars($dbStatus['error']) ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
          <div class="no-db">⚠️ Connexion base de données indisponible.</div>
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

        <!-- TABS -->
        <div class="tabs-crud">
          <div class="tab-c <?= $activeTab === 'demande' ? 'on' : '' ?>" onclick="switchTab('demande',this)">📋 Demandes
            de
            crédit</div>
          <div class="tab-c <?= $activeTab === 'garantie' ? 'on' : '' ?>" onclick="switchTab('garantie',this)">🔒
            Garanties
          </div>
          <div class="tab-c <?= $activeTab === 'simulateur' ? 'on' : '' ?>" onclick="switchTab('simulateur',this)">🧮
            Simulateur</div>
        </div>

        <!-- ══════════════════════════
             TAB : DEMANDES DE CRÉDIT
        ══════════════════════════ -->
        <div id="tab-demande" style="display:<?= $activeTab === 'demande' ? 'block' : 'none' ?>">

          <!-- Formulaire -->
          <div class="sc" style="margin-bottom:1.2rem">
            <div class="sc-hd">
              <div class="sc-title">
                <?= $editDemande ? '✏️ Modifier demande #' . (int) $editDemande['id'] : '➕ Nouvelle demande de crédit' ?>
              </div>
            </div>
            <form method="POST" action="<?= $self ?>" id="form-demande" novalidate>
              <input type="hidden" name="action" value="<?= $editDemande ? 'update_demande' : 'create_demande' ?>" />
              <?php if ($editDemande): ?><input type="hidden" name="id" value="<?= (int) $editDemande['id'] ?>" /><?php endif; ?>

              <div class="form-row-2">
                <div class="fg-crud">
                  <label class="fl-crud">Montant (TND) *</label>
                  <input class="fi-crud" id="f-montant" name="montant" type="number" step="100"
                    placeholder="ex: 25000" value="<?= htmlspecialchars($editDemande['montant'] ?? '') ?>"  />
                  <div class="field-hint">Entre 1 et 1 000 000 TND</div>
                  <div class="err-msg" id="e-montant"></div>
                </div>
                <div class="fg-crud">
                  <label class="fl-crud">Durée (mois) *</label>
                  <input class="fi-crud" id="f-duree" name="duree_mois" type="number" step="1"
                    placeholder="ex: 36" value="<?= htmlspecialchars($editDemande['duree_mois'] ?? '') ?>"  />
                  <div class="field-hint">6 à 360 mois</div>
                  <div class="err-msg" id="e-duree"></div>
                </div>
              </div>

              <div class="form-row-2">
                <div class="fg-crud">
                  <label class="fl-crud">Taux annuel (%) *</label>
                  <input class="fi-crud" id="f-taux" name="taux_interet" type="number" step="0.1"
                    placeholder="ex: 8.5" value="<?= htmlspecialchars($editDemande['taux_interet'] ?? '') ?>"
                    required />
                  <div class="field-hint">0 à 30 %</div>
                  <div class="err-msg" id="e-taux"></div>
                </div>
                <div class="fg-crud">
                  <label class="fl-crud">Date de demande *</label>
                  <input class="fi-crud" id="f-date" name="date_demande" type="date"
                    value="<?= htmlspecialchars($editDemande['date_demande'] ?? date('Y-m-d')) ?>" required />
                  <div class="err-msg" id="e-date"></div>
                </div>
              </div>

              <div class="form-row-2">
                <div class="fg-crud">
                  <label class="fl-crud">Statut *</label>
                  <select class="fs-crud" name="statut">
                    <?php foreach (['en_cours' => 'En cours', 'traitee' => 'Traitée', 'annulee' => 'Annulée'] as $v => $l): ?>
                      <option value="<?= $v ?>" <?= ($editDemande['statut'] ?? 'en_cours') === $v ? 'selected' : '' ?>><?= $l ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fg-crud">
                  <label class="fl-crud">Résultat *</label>
                  <select class="fs-crud" name="resultat">
                    <?php foreach (['en_attente' => 'En attente', 'approuvee' => 'Approuvée', 'refusee' => 'Refusée'] as $v => $l): ?>
                      <option value="<?= $v ?>" <?= ($editDemande['resultat'] ?? 'en_attente') === $v ? 'selected' : '' ?>>
                        <?= $l ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="fg-crud">
                <label class="fl-crud">Motif du résultat</label>
                <textarea class="fta-crud" name="motif_resultat"
                  placeholder="Explication (optionnel)..."><?= htmlspecialchars($editDemande['motif_resultat'] ?? '') ?></textarea>
              </div>

              <?php if ($editDemande): ?>
                <div class="fg-crud">
                  <label class="fl-crud">Date de traitement</label>
                  <input class="fi-crud" name="date_traitement" type="date"
                    value="<?= htmlspecialchars($editDemande['date_traitement'] ?? '') ?>" />
                </div>
              <?php endif; ?>

              <button type="submit" class="btn-crud">
                <?= $editDemande ? '💾 Enregistrer' : '✅ Soumettre la demande' ?>
              </button>
              <?php if ($editDemande): ?>
                <a href="<?= $self ?>" class="sc-link" style="margin-left:.8rem">✕ Annuler</a>
              <?php endif; ?>
            </form>
          </div>

          <!-- Tableau des demandes -->
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">📋 Mes demandes <span class="claim-st cs-prog">
                  <?= count($demandes) ?>
                </span></div>
            </div>
            <?php if (empty($demandes)): ?>
              <div class="tx-item">
                <div class="tx-name" style="color:var(--muted)">Aucune demande.</div>
              </div>
            <?php else: ?>
              <div style="overflow-x:auto">
                <table class="tbl-crud">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Montant</th>
                      <th>Durée</th>
                      <th>Taux</th>
                      <th>Date</th>
                      <th>Statut</th>
                      <th>Résultat</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($demandes as $d): ?>
                      <tr>
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
                        <td><span class="claim-st <?= $sc[$d['statut']] ?? 'cs-open' ?>"><?= $sl[$d['statut']] ?? $d['statut'] ?></span></td>
                        <td><span class="claim-st <?= $rc[$d['resultat']] ?? 'cs-open' ?>"><?= $rl[$d['resultat']] ?? $d['resultat'] ?></span></td>
                        <td>
                          <div class="td-acts-row">
                            <a href="<?= $self ?>?edit_d=<?= $d['id'] ?>" class="btn-edt">✏️</a>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Supprimer ?')">
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
        </div><!-- /tab-demande -->

        <!-- ══════════════════════
             TAB : GARANTIES
        ══════════════════════ -->
        <div id="tab-garantie" style="display:<?= $activeTab === 'garantie' ? 'block' : 'none' ?>">

          <!-- Formulaire garantie -->
          <div class="sc" style="margin-bottom:1.2rem">
            <div class="sc-hd">
              <div class="sc-title">
                <?= $editGarantie ? '✏️ Modifier garantie #' . (int) $editGarantie['id'] : '🔒 Ajouter une garantie' ?>
              </div>
            </div>
            <form method="POST" action="<?= $self ?>" id="form-garantie" novalidate>
              <input type="hidden" name="action" value="<?= $editGarantie ? 'update_garantie' : 'create_garantie' ?>" />
              <?php if ($editGarantie): ?><input type="hidden" name="id"
                  value="<?= (int) $editGarantie['id'] ?>" /><?php endif; ?>

              <?php if (!$editGarantie): ?>
                <div class="fg-crud">
                  <label class="fl-crud">Demande associée *</label>
                  <select class="fs-crud" name="demande_credit_id" id="f-g-demande" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($demandesSelect as $ds): ?>
                      <option value="<?= $ds['id'] ?>">#<?= $ds['id'] ?> — <?= number_format($ds['montant'], 0, ',', ' ') ?>
                        TND — <?= $ds['date_demande'] ?></option>
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
                    <?php foreach (['vehicule' => '🚗 Véhicule', 'immobilier' => '🏠 Immobilier', 'garant' => '🤝 Garant', 'autre' => '📄 Autre'] as $v => $l): ?>
                      <option value="<?= $v ?>" <?= ($editGarantie['type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="err-msg" id="e-g-type"></div>
                </div>
                <div class="fg-crud">
                  <label class="fl-crud" id="lbl-document">Document justificatif *</label>
                  <input class="fi-crud" id="f-g-doc" name="document" type="text" placeholder="Référence officielle"
                    value="<?= htmlspecialchars($editGarantie['document'] ?? '') ?>" required />
                  <div class="field-hint" id="hint-document">N° carte grise, titre propriété, nom garant…</div>
                  <div class="err-msg" id="e-g-doc"></div>
                </div>
              </div>

              <div class="form-row-2">
                <div class="fg-crud">
                  <label class="fl-crud">Valeur estimée (TND) *</label>
                  <input class="fi-crud" id="f-g-valeur" name="valeur_estimee" type="number" min="0" step="100"
                    placeholder="ex: 30000" value="<?= htmlspecialchars($editGarantie['valeur_estimee'] ?? '') ?>"
                    required />
                  <div class="err-msg" id="e-g-valeur"></div>
                </div>
                <div class="fg-crud">
                  <label class="fl-crud">Description</label>
                  <input class="fi-crud" name="description" type="text" placeholder="Détails…"
                    value="<?= htmlspecialchars($editGarantie['description'] ?? '') ?>" />
                </div>
              </div>

              <button type="submit" class="btn-crud">
                <?= $editGarantie ? '💾 Enregistrer' : '🔒 Ajouter la garantie' ?>
              </button>
              <?php if ($editGarantie): ?>
                <a href="<?= $self ?>?tab=garantie" class="sc-link" style="margin-left:.8rem">✕ Annuler</a>
              <?php endif; ?>
            </form>
          </div>

          <!-- Tableau garanties -->
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">🔒 Mes garanties <span class="claim-st cs-prog">
                  <?= count($garanties) ?>
                </span></div>
            </div>
            <?php if (empty($garanties)): ?>
              <div class="tx-item">
                <div class="tx-name" style="color:var(--muted)">Aucune garantie.</div>
              </div>
            <?php else: ?>
              <div style="overflow-x:auto">
                <table class="tbl-crud">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Demande</th>
                      <th>Type</th>
                      <th>Document</th>
                      <th>Valeur</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($garanties as $g): ?>
                      <tr>
                        <td>
                          <?= (int) $g['id'] ?>
                        </td>
                        <td><span class="claim-st cs-open">#
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
                        <td>
                          <div class="td-acts-row">
                            <a href="<?= $self ?>?edit_g=<?= $g['id'] ?>&tab=garantie" class="btn-edt">✏️</a>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Supprimer ?')">
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
        </div><!-- /tab-garantie -->

        <!-- ══════════════════
             TAB : SIMULATEUR
        ══════════════════ -->
        <div id="tab-simulateur" style="display:<?= $activeTab === 'simulateur' ? 'block' : 'none' ?>">
          <div class="sc cr-sim">
            <div class="sc-title" style="margin-bottom:1rem">🧮 Simulateur de Crédit</div>
            <div class="slider-row">
              <div class="sl-lbl"><span>Montant</span><span id="lv">20 000 TND</span></div>
              <input type="range" min="1000" max="100000" step="1000" value="20000" oninput="calcCredit()" />
            </div>
            <div class="slider-row">
              <div class="sl-lbl"><span>Durée</span><span id="dv">36 mois</span></div>
              <input type="range" min="6" max="84" step="6" value="36" oninput="calcCredit()" />
            </div>
            <div class="slider-row">
              <div class="sl-lbl"><span>Taux annuel</span><span id="rv">8.5%</span></div>
              <input type="range" min="5" max="20" step="0.5" value="8.5" oninput="calcCredit()" />
            </div>
            <div class="cr-result">
              <div class="sl-lbl" style="justify-content:center">Mensualité estimée</div>
              <div class="cr-monthly" id="mp">631 TND</div>
              <div class="cr-detail" id="cd">Total : 22 716 TND · Coût : 2 716 TND</div>
            </div>
            <button class="apply-btn" onclick="switchTab('demande',null)">Faire ma demande →</button>
          </div>
        </div>

      </div><!-- /page-credits -->

      <div class="page" id="page-reclamations">
        <div class="sc">
          <div class="sc-title">📣 Réclamations</div>
        </div>
      </div>

      <div class="page" id="page-profil">
        <div class="sc">
          <div class="sc-title" style="margin-bottom:1rem">👤 Mon Profil</div>
          <div class="tx-item">
            <div style="flex:1">
              <div class="tx-name">Nom Prénom</div>
              <div class="tx-date">client@legalfinai.com</div>
            </div>
            <span class="claim-st cs-closed">KYC Vérifié</span>
          </div>
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
      const titles = { dashboard: 'Tableau de bord', credits: 'Crédits & Épargne', reclamations: 'Réclamations', profil: 'Mon Profil' };
      document.getElementById('page-title').textContent = titles[id] || id;
    }

    function switchTab(name, el) {
      ['demande', 'garantie', 'simulateur'].forEach(t => {
        document.getElementById('tab-' + t).style.display = (t === name) ? 'block' : 'none';
      });
      document.querySelectorAll('.tab-c').forEach(t => t.classList.remove('on'));
      if (el) el.classList.add('on');
      else document.querySelectorAll('.tab-c')[{ demande: 0, garantie: 1, simulateur: 2 }[name]]?.classList.add('on');
    }

    document.getElementById('form-demande').addEventListener('submit', function (e) {
      let ok = true;
      const set = (id, msg) => { document.getElementById(id).textContent = msg; if (msg) ok = false; };
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
      set('e-g-doc', (v => (!v || v.length < 3) ? 'Document requis (min 3 car.).' : '')(document.getElementById('f-g-doc').value.trim()));
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
  </script>
</body>

</html>