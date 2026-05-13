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

$self = $controllerSelf ?? $_SERVER['SCRIPT_NAME'];
$viewRoot = defined('VIEW_URL') ? VIEW_URL : '';
$controllerRoot = defined('BASE_URL') ? BASE_URL . '/controller' : '';
$showCreditsPage = in_array($activeTab, ['dem', 'gar'], true) || $success || $errors;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>LegalFin — Back Office Admin</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($viewRoot) ?>/backoffice/creditttttttttttttttt.css" />
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@500;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    window.CONTROLLER_PATH = '<?= htmlspecialchars($self) ?>';
  </script>
  <script src="<?= htmlspecialchars($viewRoot) ?>/frontoffice/geolocation-form.js"></script>
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
        <span class="sb-badge ba"><?= count($demandes) ?></span>
      </a>
      <a class="sb-item" onclick="showPage('statistique',this)">
        <span class="sb-ico">📊</span> Statistiques
      </a>
      <div class="sb-sec">Support</div>
      <a class="sb-item" onclick="showPage('reclamations',this)"><span class="sb-ico">📢</span> Réclamations <span class="sb-badge br">5</span></a>
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
        <a href="../view/backoffice/backoffice_utilisateur.php" class="btn-frontoffice">Dashboard Admin →</a>
      </div>
    </header>

    <div class="content">

      <!-- DASHBOARD -->
      <div class="page <?= $showCreditsPage ? '' : 'on' ?>" id="page-dashboard">
        <div class="kpi-grid">
          <div class="kpi kb">
            <div class="kpi-top"><span class="kpi-ico">📋</span></div>
            <div class="kpi-val" style="color:var(--blue)"><?= $stats['total'] ?></div>
            <div class="kpi-lbl">Total demandes</div>
          </div>
          <div class="kpi ke">
            <div class="kpi-top"><span class="kpi-ico">✅</span></div>
            <div class="kpi-val" style="color:var(--emerald)"><?= $stats['approuvee'] ?></div>
            <div class="kpi-lbl">Approuvées</div>
          </div>
          <div class="kpi ka">
            <div class="kpi-top"><span class="kpi-ico">⏳</span></div>
            <div class="kpi-val" style="color:var(--amber)"><?= $stats['attente'] ?></div>
            <div class="kpi-lbl">En attente</div>
          </div>
          <div class="kpi kr">
            <div class="kpi-top"><span class="kpi-ico">💰</span></div>
            <div class="kpi-val" style="color:var(--rose)"><?= number_format($stats['encours'], 0, ',', ' ') ?> TND</div>
            <div class="kpi-lbl">Encours approuvé</div>
          </div>
        </div>
      </div>

      <!-- CRÉDITS -->
      <div class="page <?= $showCreditsPage ? 'on' : '' ?>" id="page-credits">

        <?php if (isset($dbStatus) && !$dbStatus['ok']): ?>
          <div class="alert-err">⚠️ Base de données indisponible : <?= htmlspecialchars($dbStatus['error']) ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
          <div class="no-db">⚠️ Base de données indisponible.</div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert-ok">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert-err">⚠️ Erreurs :
            <ul>
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="carte-stats" style="margin-bottom:1rem">
          <div class="cs-card">
            <div class="cs-val" style="color:var(--amber)"><?= $stats['attente'] ?></div>
            <div class="cs-lbl">En attente</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--emerald)"><?= $stats['approuvee'] ?></div>
            <div class="cs-lbl">Approuvées</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--rose)"><?= $stats['refusee'] ?></div>
            <div class="cs-lbl">Refusées</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--blue)"><?= number_format($stats['encours'], 0, ',', ' ') ?> TND</div>
            <div class="cs-lbl">Encours total</div>
          </div>
        </div>

        <div class="tabs-a">
          <div class="tab-a <?= $activeTab === 'dem' ? 'on' : '' ?>" onclick="switchTab('dem',this)">📋 Demandes de crédit</div>
          <div class="tab-a <?= $activeTab === 'gar' ? 'on' : '' ?>" onclick="switchTab('gar',this)">🔒 Garanties</div>
        </div>

        <div id="tab-dem" style="display:<?= $activeTab === 'dem' ? 'block' : 'none' ?>">
          <div class="dt-wrap" style="margin-bottom:1.2rem">
            <div class="dt-hd">
              <div style="display:flex;align-items:center;gap:.7rem">
                <div class="dt-title">📈 Dossiers Crédit & Garanties</div>
                <span class="badge b-blue">DemandeCredit / Garantie</span>
              </div>
              <div class="dt-acts">
                <input type="text" id="search-dem" class="fi" placeholder="🔍 Rechercher..." style="width:180px" oninput="filterTable('tbl-dem','search-dem')" />
                <select id="filter-res" class="fs" style="width:130px" onchange="filterTable('tbl-dem','search-dem')">
                  <option value="">Tous</option>
                  <option value="en_attente">⏳ En attente</option>
                  <option value="approuvee">✅ Approuvée</option>
                  <option value="refusee">❌ Refusée</option>
                </select>
              </div>
            </div>

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
                    <th>Signature</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($demandes)): ?>
                    <tr><td colspan="12" style="text-align:center;padding:2rem;color:var(--muted);font-size:.8rem">Aucun dossier</td></tr>
                  <?php else: foreach ($demandes as $d): ?>
                    <tr data-resultat="<?= $d['resultat'] ?>">
                      <td><input type="checkbox" class="dem-checkbox" value="<?= $d['id'] ?>"></td>
                      <td><?= (int) $d['id'] ?></td>
                      <td style="text-align:center;cursor:pointer;" onclick="openMapModal('<?= htmlspecialchars($d['country_code'] ?? 'TN') ?>', '<?= htmlspecialchars($d['ip_client'] ?? '') ?>')" title="Voir sur la carte">
                        <?php
                          $flags = ['TN'=>'🇹🇳','FR'=>'🇫🇷','US'=>'🇺🇸','GB'=>'🇬🇧','DE'=>'🇩🇪','IT'=>'🇮🇹','ES'=>'🇪🇸','CH'=>'🇨🇭','CA'=>'🇨🇦','BE'=>'🇧🇪','DZ'=>'🇩🇿','MA'=>'🇲🇦','LY'=>'🇱🇾'];
                          $cc = $d['country_code'] ?? 'TN';
                          echo ($flags[$cc] ?? '🌐') . ' ' . htmlspecialchars($cc);
                        ?>
                        <span style="font-size:0.6rem;display:block;color:var(--muted)">🗺️ voir carte</span>
                      </td>
                      <td><strong><?= number_format($d['montant'], 0, ',', ' ') ?> TND</strong></td>
                      <td><?= (int) $d['duree_mois'] ?> mois</td>
                      <td><?= $d['taux_interet'] ?>%</td>
                      <td><?= htmlspecialchars($d['date_demande']) ?></td>
                      <td><span class="badge <?= $sc[$d['statut']] ?? 'b-wait' ?>"><?= $sl[$d['statut']] ?? $d['statut'] ?></span></td>
                      <td><span class="badge <?= $rc[$d['resultat']] ?? 'b-wait' ?>"><?= $rl[$d['resultat']] ?? $d['resultat'] ?></span></td>
                      <td style="color:var(--muted);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:pointer;" title="Click to view full motif" onclick="openMotifModal('<?= htmlspecialchars(addslashes($d['motif_resultat'] ?? ''), ENT_QUOTES) ?>')">
                        <?= htmlspecialchars(mb_substr($d['motif_resultat'] ?? '', 0, 40)) ?><?= mb_strlen($d['motif_resultat'] ?? '') > 40 ? '...' : '' ?>
                      </td>
                      <td style="text-align:center;">
                        <?php if ($d['resultat'] === 'approuvee'): ?>
                          <?php $submId = $d['docuseal_submission_id'] ?? ($_SESSION['docuseal_submission_' . $d['id']] ?? null); ?>
                          <?php if ($submId): ?>
                            <span class="badge b-wait sig-status-badge" id="sig-badge-<?= $d['id'] ?>" data-submission-id="<?= $submId ?>" style="cursor:pointer;" onclick="checkSigStatus(<?= $d['id'] ?>, '<?= $submId ?>')" title="Cliquer pour vérifier">📝 En attente</span>
                          <?php else: ?>
                            <form method="POST" action="<?= $self ?>" style="display:inline;">
                              <input type="hidden" name="action" value="send_signature" />
                              <input type="hidden" name="id" value="<?= (int) $d['id'] ?>" />
                              <button type="submit" class="badge b-off" style="border:0;cursor:pointer;font-size:.7rem;">Envoyer</button>
                            </form>
                          <?php endif; ?>
                        <?php else: ?>
                          <span style="color:var(--muted);font-size:.75rem;">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="td-acts">
                          <a href="<?= $self ?>?edit_d=<?= $d['id'] ?>" class="btn-edt">📝 Modifier</a>
                          <form method="POST" action="<?= $self ?>" style="display:inline" onsubmit="return confirm('Approuver ?')">
                            <input type="hidden" name="action" value="approve_demande" /><input type="hidden" name="id" value="<?= $d['id'] ?>" /><button type="submit" class="btn-quick-approve">✅ Approuver</button>
                          </form>
                          <form method="POST" action="<?= $self ?>" style="display:inline" onsubmit="return confirm('Refuser ?')">
                            <input type="hidden" name="action" value="refuse_demande" /><input type="hidden" name="id" value="<?= $d['id'] ?>" /><button type="submit" class="btn-quick-refuse">❌ Refuser</button>
                          </form>
                          <form method="POST" action="<?= $self ?>" style="display:inline" onsubmit="return confirm('Supprimer ?')">
                            <input type="hidden" name="action" value="delete_demande" /><input type="hidden" name="id" value="<?= $d['id'] ?>" /><button type="submit" class="btn-del">🗑️</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <?php if ($editDemande): ?>
            <div class="dt-wrap">
              <div class="dt-hd"><div class="dt-title">📝 Modifier demande #<?= (int) $editDemande['id'] ?></div></div>
              <div style="padding:1.2rem">
                <form method="POST" action="<?= $self ?>" id="form-edit-dem" novalidate>
                  <input type="hidden" name="action" value="update_demande" /><input type="hidden" name="id" value="<?= (int) $editDemande['id'] ?>" />
                  <div class="form-row-2">
                    <div class="fg-crud"><label class="fl-crud">Montant (TND) *</label><input class="fi-crud" id="a-montant" name="montant" type="number" step="100" value="<?= htmlspecialchars($editDemande['montant']) ?>" /><div class="err-msg" id="ae-montant"></div></div>
                    <div class="fg-crud"><label class="fl-crud">Durée (mois) *</label><input class="fi-crud" id="a-duree" name="duree_mois" type="number" value="<?= htmlspecialchars($editDemande['duree_mois']) ?>" /><div class="err-msg" id="ae-duree"></div></div>
                  </div>
                  <div class="form-row-2">
                    <div class="fg-crud"><label class="fl-crud">Taux (%) *</label><input class="fi-crud" id="a-taux" name="taux_interet" type="number" step="0.1" value="<?= htmlspecialchars($editDemande['taux_interet']) ?>" /><div class="err-msg" id="ae-taux"></div></div>
                    <div class="fg-crud"><label class="fl-crud">Date de traitement</label><input class="fi-crud" name="date_traitement" type="date" value="<?= htmlspecialchars($editDemande['date_traitement'] ?? '') ?>" /></div>
                  </div>
                  <div class="decision-row">
                    <div class="decision-title">🏛️ Décision administrative</div>
                    <div class="form-row-2">
                      <div class="fg-crud"><label class="fl-crud">Statut *</label><select class="fs-crud" name="statut"><?php foreach (['en_cours' => 'En cours', 'traitee' => 'Traitée', 'annulee' => 'Annulée'] as $v => $l): ?><option value="<?= $v ?>" <?= $editDemande['statut'] === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                      <div class="fg-crud"><label class="fl-crud">Résultat *</label><select class="fs-crud" name="resultat" id="a-resultat"><?php foreach (['en_attente' => '⏳ En attente', 'approuvee' => '✅ Approuvée', 'refusee' => '❌ Refusée'] as $v => $l): ?><option value="<?= $v ?>" <?= $editDemande['resultat'] === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="fg-crud"><label class="fl-crud">Motif * (obligatoire si approuvée / refusée)</label><textarea class="fta-crud" id="a-motif" name="motif_resultat"><?= htmlspecialchars($editDemande['motif_resultat'] ?? '') ?></textarea><div class="err-msg" id="ae-motif"></div></div>
                  </div>
                  <div style="display:flex;gap:.6rem;align-items:center;margin-top:.9rem"><button type="submit" class="btn-crud">💾 Enregistrer</button><button type="button" class="btn-crud-red" onclick="quickDecision('refusee')">❌ Refuser</button><button type="button" class="btn-crud-green" onclick="quickDecision('approuvee')">✅ Approuver</button><a href="<?= $self ?>" class="fl" style="margin-left:.4rem;cursor:pointer">✖ Annuler</a></div>
                </form>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div id="tab-gar" style="display:<?= $activeTab === 'gar' ? 'block' : 'none' ?>">
          <?php if ($editGarantie): ?>
          <div class="dt-wrap" style="margin-bottom:1.2rem">
            <div class="dt-hd"><div class="dt-title">📝 Modifier garantie #<?= (int) $editGarantie['id'] ?></div></div>
            <div style="padding:1.2rem">
              <form method="POST" action="<?= $self ?>" id="form-gar" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="update_garantie" /><input type="hidden" name="id" value="<?= (int) $editGarantie['id'] ?>" />
                <div class="form-row-2">
                  <div class="fg-crud"><label class="fl-crud">Type *</label><select class="fs-crud" name="type" id="g-type" onchange="updateGarDocLabel(this.value)" required><option value="">— Choisir —</option><?php foreach (['vehicule' => '🚗 Véhicule', 'immobilier' => '🏠 Immobilier', 'garant' => '🤝 Garant', 'autre' => '📄 Autre'] as $v => $l): ?><option value="<?= $v ?>" <?= ($editGarantie['type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select><div class="err-msg" id="ge-type"></div></div>
                  <div class="fg-crud"><label class="fl-crud" id="g-doc-lbl">Document *</label><input class="fi-crud" name="document" id="g-doc" type="text" value="<?= htmlspecialchars($editGarantie['document'] ?? '') ?>" required /><div class="field-hint" id="g-doc-hint">N° carte grise, titre propriété...</div><div class="err-msg" id="ge-doc"></div></div>
                </div>
                <div class="form-row-2">
                  <div class="fg-crud"><label class="fl-crud">Valeur estimée (TND) *</label><input class="fi-crud" name="valeur_estimee" id="g-val" type="number" value="<?= htmlspecialchars($editGarantie['valeur_estimee'] ?? '') ?>" required /><div class="err-msg" id="ge-val"></div></div>
                  <div class="fg-crud"><label class="fl-crud">Description</label><input class="fi-crud" name="description" type="text" value="<?= htmlspecialchars($editGarantie['description'] ?? '') ?>" /></div>
                </div>
                <div style="display:flex;gap:.6rem;align-items:center;margin-top:.9rem"><button type="submit" class="btn-crud">💾 Enregistrer</button><a href="<?= $self ?>?tab=gar" class="fl" style="cursor:pointer">✖ Annuler</a></div>
              </form>
            </div>
          </div>
          <?php endif; ?>

          <div class="dt-wrap">
            <div class="dt-hd"><div class="dt-title">🔒 Gestion des garanties <span class="badge b-blue"><?= count($garanties) ?></span></div><input type="text" id="search-gar" class="fi" placeholder="🔍 Rechercher..." style="width:180px" oninput="filterTable('tbl-gar','search-gar')" /></div>
            <div style="overflow-x:auto">
              <table id="tbl-gar">
                <thead><tr><th>#</th><th>Demande</th><th>Type</th><th>Document</th><th>Valeur</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php if (empty($garanties)): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted);font-size:.8rem">Aucune garantie.</td></tr>
                  <?php else: foreach ($garanties as $g): $gStatus = $g['statut'] ?? 'en_attente'; $gStatusLabel = $rl[$gStatus] ?? $gStatus; $gStatusBadge = $rc[$gStatus] ?? 'b-wait'; ?>
                    <tr>
                      <td><?= (int) $g['id'] ?></td>
                      <td><span class="badge b-wait">#<?= (int) $g['demande_credit_id'] ?><?= $g['dc_montant'] ? ' — ' . number_format($g['dc_montant'], 0, ',', ' ') . ' TND' : '' ?></span></td>
                      <td><?= $tgL[$g['type']] ?? htmlspecialchars($g['type']) ?></td>
                      <td><?php if (!empty($g['document']) && str_starts_with($g['document'], 'uploads/')): ?><a href="<?= htmlspecialchars($controllerRoot) ?>/AdminCreditController.php?action=download_garantie_file&id=<?= (int)$g['id'] ?>" target="_blank" style="color:var(--blue);text-decoration:none;font-size:.75rem;">📎 Voir document</a><?php else: ?><?= htmlspecialchars($g['document'] ?: '—') ?><?php endif; ?></td>
                      <td><strong><?= number_format($g['valeur_estimee'], 0, ',', ' ') ?> TND</strong></td>
                      <td><span class="badge <?= $gStatusBadge ?>"><?= $gStatusLabel ?></span></td>
                      <td>
                        <div class="td-acts">
                          <a href="<?= $self ?>?edit_g=<?= $g['id'] ?>&tab=gar" class="btn-edt">📝 Modifier</a>
                          <form method="POST" action="<?= $self ?>" style="display:inline" onsubmit="return confirm('Supprimer ?')"><input type="hidden" name="action" value="delete_garantie" /><input type="hidden" name="id" value="<?= $g['id'] ?>" /><button type="submit" class="btn-del">🗑️</button></form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="page" id="page-statistique">
        <?php
          $totalDecided = ($stats['approuvee'] + $stats['refusee']);
          $approvalRate = $totalDecided > 0 ? round(($stats['approuvee'] / $totalDecided) * 100, 1) : 0;
          $refusalRate  = $totalDecided > 0 ? round(($stats['refusee']  / $totalDecided) * 100, 1) : 0;
          $approuveesList = array_filter($demandes, fn($d) => $d['resultat'] === 'approuvee');
          $avgMontant = count($approuveesList) ? array_sum(array_column($approuveesList, 'montant')) / count($approuveesList) : 0;
          $avgDuree   = count($approuveesList) ? array_sum(array_column($approuveesList, 'duree_mois')) / count($approuveesList) : 0;
          $avgTaux    = count($demandes) ? array_sum(array_column($demandes, 'taux_interet')) / count($demandes) : 0;

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
              if ($d['resultat'] === 'refusee') $monthly[$mKey]['refusee']++;
            }
          }
          $garTypes = []; foreach ($garanties as $g) { $t = $g['type'] ?? 'autre'; $garTypes[$t] = ($garTypes[$t] ?? 0) + 1; }
          $statusBreakdown = ['en_cours' => 0, 'traitee' => 0, 'annulee' => 0]; foreach ($demandes as $d) { $s = $d['statut'] ?? 'en_cours'; $statusBreakdown[$s]++; }
          $monthlyJson = json_encode(array_values($monthly)); $garTypesJson = json_encode($garTypes); $statusJson = json_encode($statusBreakdown);
        ?>

        <div class="stat-kpi-grid">
          <div class="stat-kpi-card sk-blue"><div class="sk-icon">📋</div><div class="sk-value"><?= $stats['total'] ?></div><div class="sk-label">Total demandes</div></div>
          <div class="stat-kpi-card sk-green"><div class="sk-icon">✅</div><div class="sk-value"><?= $approvalRate ?>%</div><div class="sk-label">Approba.</div></div>
          <div class="stat-kpi-card sk-amber"><div class="sk-icon">⏳</div><div class="sk-value"><?= $stats['attente'] ?></div><div class="sk-label">En attente</div></div>
          <div class="stat-kpi-card sk-rose"><div class="sk-icon">💰</div><div class="sk-value"><?= number_format($stats['encours'], 0, ',', ' ') ?></div><div class="sk-label">Encours</div></div>
        </div>

        <div class="stat-charts-row">
          <div class="stat-chart-card" style="flex:2">
            <div class="sc-header"><div class="sc-title">📅 Activité mensuelle</div></div>
            <div class="sc-body"><canvas id="chartMonthly" height="180"></canvas></div>
          </div>
          <div class="stat-chart-card" style="flex:1">
            <div class="sc-header"><div class="sc-title">🎯 Résultats</div></div>
            <div class="sc-body"><canvas id="chartResultat" width="180" height="180"></canvas></div>
          </div>
        </div>
      </div>

      <div class="page" id="page-reclamations">
        <div class="dt-wrap"><div class="dt-hd"><div class="dt-title">📢 Réclamations</div></div></div>
      </div>

    </div>
  </div>

  <!-- MODALS -->
  <div id="motif-modal" class="modal-crud" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div class="sc" style="max-width:500px;width:100%;position:relative;">
      <div class="sc-hd"><div class="sc-title">📜 Motif de décision</div><button onclick="closeMotifModal()" style="background:none;border:none;color:white;cursor:pointer;font-size:1.2rem;">✖</button></div>
      <div style="padding:1.5rem;color:var(--muted);line-height:1.6;" id="motif-text"></div>
    </div>
  </div>

  <div id="map-modal" class="modal-crud" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div class="sc" style="max-width:800px;width:100%;position:relative;">
      <div class="sc-hd"><div class="sc-title" id="map-modal-title">🗺️ Carte Client</div><button onclick="closeMapModal()" style="background:none;border:none;color:white;cursor:pointer;font-size:1.2rem;">✖</button></div>
      <div style="height:450px;"><iframe id="map-iframe" width="100%" height="100%" frameborder="0" style="border:0"></iframe></div>
    </div>
  </div>

  <script>
    function showPage(id, el) {
      document.querySelectorAll('.page').forEach(p => p.classList.remove('on'));
      document.getElementById('page-' + id).classList.add('on');
      document.querySelectorAll('.sb-item').forEach(s => s.classList.remove('on'));
      if (el) el.classList.add('on');
      if (id === 'statistique') renderCharts();
    }
    function switchTab(name, el) {
      ['dem', 'gar'].forEach(t => document.getElementById('tab-' + t).style.display = (t === name ? 'block' : 'none'));
      document.querySelectorAll('.tab-a').forEach(t => t.classList.remove('on'));
      if (el) el.classList.add('on');
    }
    function filterTable(tblId, searchId) {
      const term = document.getElementById(searchId)?.value.toLowerCase() || '';
      document.querySelectorAll('#' + tblId + ' tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    }
    function openMotifModal(motif) { document.getElementById('motif-text').textContent = motif || '(Pas de motif)'; document.getElementById('motif-modal').style.display = 'flex'; }
    function closeMotifModal() { document.getElementById('motif-modal').style.display = 'none'; }
    function openMapModal(countryCode, ip) {
      const coords = {TN:[33.8869,9.5375],FR:[46.2276,2.2137],US:[37.0902,-95.7129],GB:[55.3781,-3.436],DE:[51.1657,10.4515],IT:[41.8719,12.5674],ES:[40.4637,-3.7492]};
      const [lat, lon] = coords[countryCode] || [33.8869, 9.5375];
      document.getElementById('map-modal-title').textContent = '🗺️ ' + countryCode + (ip ? ' — IP: ' + ip : '');
      document.getElementById('map-iframe').src = `https://www.openstreetmap.org/export/embed.html?bbox=${lon-2},${lat-2},${lon+2},${lat+2}&layer=mapnik&marker=${lat},${lon}`;
      document.getElementById('map-modal').style.display = 'flex';
    }
    function closeMapModal() { document.getElementById('map-modal').style.display = 'none'; document.getElementById('map-iframe').src = ''; }
    
    // Charts logic
    const monthlyData = <?= $monthlyJson ?>;
    let chartsInst = {};
    function renderCharts() {
      const ctxM = document.getElementById('chartMonthly');
      if (ctxM && !chartsInst.monthly) {
        chartsInst.monthly = new Chart(ctxM, { type:'bar', data: { labels: monthlyData.map(m=>m.label), datasets:[{label:'Total',data:monthlyData.map(m=>m.total),backgroundColor:'#3b82f6'}] }, options:{maintainAspectRatio:false} });
      }
      const ctxR = document.getElementById('chartResultat');
      if (ctxR && !chartsInst.resultat) {
        chartsInst.resultat = new Chart(ctxR, { type:'doughnut', data: { labels:['Approuvées','Attente','Refusées'], datasets:[{data:[<?= $stats['approuvee'] ?>,<?= $stats['attente'] ?>,<?= $stats['refusee'] ?>], backgroundColor:['#10b981','#f59e0b','#ef4444']}] }, options:{maintainAspectRatio:false} });
      }
    }
  </script>
</body>
</html>
