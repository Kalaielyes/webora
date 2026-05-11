<?php
$sc = ['en_cours' => 'b-wait', 'traitee' => 'b-on', 'annulee' => 'b-off'];
$sl = ['en_cours' => 'En cours', 'traitee' => 'Traitée', 'annulee' => 'Annulée'];
$rc = ['en_attente' => 'b-wait', 'approuvee' => 'b-on', 'refusee' => 'b-off'];
$rl = ['en_attente' => 'En attente', 'approuvee' => 'Approuvée', 'refusee' => 'Refusée'];
$tgL = ['vehicule' => '🚗 Véhicule', 'immobilier' => '🏠 Immobilier', 'garant' => '🤝 Garant', 'autre' => '📄 Autre'];
$self = $controllerSelf ?? $_SERVER['SCRIPT_NAME'];
$showCreditsPage = true;
?>
      <!-- CRÃ‰DITS -->
      <div class="page <?= $showCreditsPage ? 'on' : '' ?>" id="page-credits">

        <?php if (isset($dbStatus) && !$dbStatus['ok']): ?>
          <div class="alert-err">âš ï¸ Base de donnÃ©es indisponible : <?= htmlspecialchars($dbStatus['error']) ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
          </div>
        <?php endif; ?>

        <!-- KPIs crÃ©dits -->
        <div class="carte-stats" style="margin-bottom:1rem">
          <div class="cs-card">
            <div class="cs-val" style="color:var(--amber)">
              <?= $creditStats['attente'] ?>
            </div>
            <div class="cs-lbl">En attente</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--emerald)">
              <?= $creditStats['approuvee'] ?>
            </div>
            <div class="cs-lbl">ApprouvÃ©es</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--rose)">
              <?= $creditStats['refusee'] ?>
            </div>
            <div class="cs-lbl">RefusÃ©es</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color:var(--blue)">
              <?= number_format($creditStats['encours'], 0, ',', ' ') ?> TND
            </div>
            <div class="cs-lbl">Encours total</div>
          </div>
        </div>

        <!-- TABS -->
        <div class="tabs-a">
          <div class="tab-a <?= $activeTab === 'dem' ? 'on' : '' ?>" onclick="switchTab('dem',this)">ðŸ“‹ Demandes de crÃ©dit
          </div>
          <div class="tab-a <?= $activeTab === 'gar' ? 'on' : '' ?>" onclick="switchTab('gar',this)">ðŸ”’ Garanties</div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
             TAB : DEMANDES
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div id="tab-dem" style="display:<?= $activeTab === 'dem' ? 'block' : 'none' ?>">

          <!-- Tableau -->
          <div class="dt-wrap" style="margin-bottom:1.2rem">
            <div class="dt-hd">
              <div style="display:flex;align-items:center;gap:.7rem">
                <div class="dt-title">ðŸ“ˆ Dossiers CrÃ©dit & Garanties</div>
                <span class="badge b-blue">DemandeCredit / Garantie</span>
              </div>
              <div class="dt-acts">
                <input type="text" id="search-dem" class="fi" placeholder="ðŸ” Rechercher..." style="width:180px"
                  oninput="filterTable('tbl-dem','search-dem')" />
                <select id="filter-res" class="fs" style="width:130px" onchange="filterTable('tbl-dem','search-dem')">
                  <option value="">Tous</option>
                  <option value="en_attente">â³ En attente</option>
                  <option value="approuvee">âœ… ApprouvÃ©e</option>
                  <option value="refusee">âŒ RefusÃ©e</option>
                </select>
              </div>
            </div>

            <!-- BULK ACTIONS PANEL -->
            <div id="bulk-actions-panel" style="display:none;padding:0.8rem;background:var(--info-bg);border-radius:0.4rem;margin-bottom:1rem;gap:1rem;align-items:center;border-left:3px solid var(--blue)">
              <span style="color:var(--blue);font-weight:500"><span id="bulk-count">0</span> sÃ©lectionnÃ©(e)(s)</span>
              <button onclick="bulkApprove()" class="btn-quick-approve" style="padding:0.4rem 0.8rem;font-size:0.85rem">âœ… Approuver tout</button>
              <button onclick="bulkDelete()" class="btn-del" style="padding:0.4rem 0.8rem;font-size:0.85rem">ðŸ—‘ï¸ Supprimer tout</button>
            </div>
            <div style="overflow-x:auto">
              <table id="tbl-dem">
                <thead>
                  <tr>
                    <th style="width:35px"><input type="checkbox" id="check-all" onchange="toggleAllCheckboxes(this)"></th>
                    <th>#</th>
                    <th>Pays</th>
                    <th>Montant</th>
                    <th>DurÃ©e</th>
                    <th>Taux</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>RÃ©sultat</th>
                    <th>Motif</th>
                    <th>Signature</th>
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
    $flags = ['TN'=>'ðŸ‡¹ðŸ‡³','FR'=>'ðŸ‡«ðŸ‡·','US'=>'ðŸ‡ºðŸ‡¸','GB'=>'ðŸ‡¬ðŸ‡§','DE'=>'ðŸ‡©ðŸ‡ª','IT'=>'ðŸ‡®ðŸ‡¹','ES'=>'ðŸ‡ªðŸ‡¸','CH'=>'ðŸ‡¨ðŸ‡­','CA'=>'ðŸ‡¨ðŸ‡¦','BE'=>'ðŸ‡§ðŸ‡ª','DZ'=>'ðŸ‡©ðŸ‡¿','MA'=>'ðŸ‡²ðŸ‡¦','LY'=>'ðŸ‡±ðŸ‡¾'];
    $cc = $d['country_code'] ?? 'TN';
    echo ($flags[$cc] ?? 'ðŸŒ') . ' ' . htmlspecialchars($cc);
  ?>
  <span style="font-size:0.6rem;display:block;color:var(--muted)">ðŸ—ºï¸ voir carte</span>
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
                          <?= htmlspecialchars(mb_substr($d['motif_resultat'] ?? '', 0, 40)) ?>    <?= mb_strlen($d['motif_resultat'] ?? '') > 40 ? 'â€¦' : '' ?>
                        </td>
                        <!-- BADGE STATUT SIGNATURE -->
                        <td style="text-align:center;">
                          <?php if ($d['resultat'] === 'approuvee'): ?>
                            <?php
                              $submId = $d['docuseal_submission_id'] ?? ($_SESSION['docuseal_submission_' . $d['id']] ?? null);
                            ?>
                            <?php if ($submId): ?>
                              <span 
                                class="badge b-wait sig-status-badge" 
                                id="sig-badge-<?= $d['id'] ?>"
                                data-submission-id="<?= $submId ?>"
                                style="cursor:pointer;"
                                onclick="checkSigStatus(<?= $d['id'] ?>, '<?= $submId ?>')"
                                title="Cliquer pour vÃ©rifier le statut de signature">
                                âœï¸ En attente
                              </span>
                            <?php else: ?>
                              <form method="POST" action="<?= $self ?>" style="display:inline;">
                                <input type="hidden" name="action" value="send_signature" />
                                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>" />
                                <button type="submit" class="badge b-off" style="border:0;cursor:pointer;font-size:.7rem;" title="Envoyer a <?= htmlspecialchars($d['client_email'] ?? 'email client') ?>">
                                  Envoyer
                                </button>
                              </form>
                            <?php endif; ?>
                          <?php else: ?>
                            <span style="color:var(--muted);font-size:.75rem;">â€”</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="td-acts">
                            <a href="<?= $self ?>?edit_d=<?= $d['id'] ?>" class="btn-edt">âœï¸ Modifier</a>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Approuver la demande #<?= $d['id'] ?> ?')">
                              <input type="hidden" name="action" value="approve_demande" />
                              <input type="hidden" name="id" value="<?= $d['id'] ?>" />
                              <button type="submit" class="btn-quick-approve">âœ… Approuver</button>
                            </form>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Refuser la demande #<?= $d['id'] ?> ?')">
                              <input type="hidden" name="action" value="refuse_demande" />
                              <input type="hidden" name="id" value="<?= $d['id'] ?>" />
                              <button type="submit" class="btn-quick-refuse">âŒ Refuser</button>
                            </form>
                            <form method="POST" action="<?= $self ?>" style="display:inline"
                              onsubmit="return confirm('Supprimer la demande #<?= $d['id'] ?> ?')">
                              <input type="hidden" name="action" value="delete_demande" />
                              <input type="hidden" name="id" value="<?= $d['id'] ?>" />
                              <button type="submit" class="btn-del">ðŸ—‘ï¸</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Formulaire Ã©dition -->
          <?php if ($editDemande): ?>
            <div class="dt-wrap">
              <div class="dt-hd">
                <div class="dt-title">âœï¸ Modifier demande #
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
                    <div class="fg-crud"><label class="fl-crud">DurÃ©e (mois) *</label><input class="fi-crud" id="a-duree"
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
                    <div class="decision-title">ðŸ›ï¸ DÃ©cision administrative</div>
                    <div class="form-row-2">
                      <div class="fg-crud">
                        <label class="fl-crud">Statut *</label>
                        <select class="fs-crud" name="statut" id="a-statut">
                          <?php foreach (['en_cours' => 'En cours', 'traitee' => 'TraitÃ©e', 'annulee' => 'AnnulÃ©e'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $editDemande['statut'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="fg-crud">
                        <label class="fl-crud">RÃ©sultat *</label>
                        <select class="fs-crud" name="resultat" id="a-resultat">
                          <?php foreach (['en_attente' => 'â³ En attente', 'approuvee' => 'âœ… ApprouvÃ©e', 'refusee' => 'âŒ RefusÃ©e'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $editDemande['resultat'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="fg-crud">
                      <label class="fl-crud">Motif * <span
                          style="color:var(--muted);font-weight:400;text-transform:none">(obligatoire si approuvÃ©e /
                          refusÃ©e)</span></label>
                      <textarea class="fta-crud" id="a-motif" name="motif_resultat"
                        placeholder="Explication de la dÃ©cision..."><?= htmlspecialchars($editDemande['motif_resultat'] ?? '') ?></textarea>
                      <div class="err-msg" id="ae-motif"></div>
                    </div>
                  </div>

                  <div style="display:flex;gap:.6rem;align-items:center;margin-top:.9rem">
                    <button type="submit" class="btn-crud">ðŸ’¾ Enregistrer</button>
                    <button type="button" class="btn-crud-red" onclick="quickDecision('refusee')">âŒ Refuser</button>
                    <button type="button" class="btn-crud-green" onclick="quickDecision('approuvee')">âœ… Approuver</button>
                    <a href="<?= $self ?>" class="fl" style="margin-left:.4rem;cursor:pointer">âœ• Annuler</a>
                  </div>
                </form>
              </div>
            </div>
          <?php endif; ?>

        </div><!-- /tab-dem -->

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
             TAB : GARANTIES
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div id="tab-gar" style="display:<?= $activeTab === 'gar' ? 'block' : 'none' ?>">

          <!-- Formulaire Ã©dition garantie (seulement si en mode Ã©dition) -->
          <?php if ($editGarantie): ?>
          <div class="dt-wrap" style="margin-bottom:1.2rem">
            <div class="dt-hd">
              <div class="dt-title">âœï¸ Modifier garantie #<?= (int) $editGarantie['id'] ?></div>
            </div>
            <div style="padding:1.2rem">
              <form method="POST" action="<?= $self ?>" id="form-gar" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="update_garantie" />
                <input type="hidden" name="id" value="<?= (int) $editGarantie['id'] ?>" />

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Type *</label>
                    <select class="fs-crud" name="type" id="g-type" onchange="updateGarDocLabel(this.value)" required>
                      <option value="">â€” Choisir â€”</option>
                      <?php foreach (['vehicule' => 'ðŸš— VÃ©hicule', 'immobilier' => 'ðŸ  Immobilier', 'garant' => 'ðŸ¤ Garant', 'autre' => 'ðŸ“„ Autre'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($editGarantie['type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="err-msg" id="ge-type"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud" id="g-doc-lbl">Document *</label>
                    <input class="fi-crud" name="document" id="g-doc" type="text"
                        placeholder="RÃ©fÃ©rence officielle"
                        value="<?= htmlspecialchars($editGarantie['document'] ?? '') ?>" required />
                    <div class="field-hint" id="g-doc-hint">NÂ° carte grise, titre propriÃ©tÃ©, nom garantâ€¦</div>
                    <div class="err-msg" id="ge-doc"></div>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="fg-crud">
                    <label class="fl-crud">Valeur estimÃ©e (TND) *</label>
                    <input class="fi-crud" name="valeur_estimee" id="g-val" type="number" min="0" step="100" 
                        placeholder="ex: 30000" value="<?= htmlspecialchars($editGarantie['valeur_estimee'] ?? '') ?>" required />
                    <div class="err-msg" id="ge-val"></div>
                  </div>
                  <div class="fg-crud">
                    <label class="fl-crud">Description</label>
                    <input class="fi-crud" name="description" type="text" placeholder="DÃ©tailsâ€¦"
                        value="<?= htmlspecialchars($editGarantie['description'] ?? '') ?>" />
                  </div>
                </div>

                <div style="display:flex;gap:.6rem;align-items:center;margin-top:.9rem">
                  <button type="submit" class="btn-crud">ðŸ’¾ Enregistrer</button>
                  <a href="<?= $self ?>?tab=gar" class="fl" style="cursor:pointer">âœ• Annuler</a>
                </div>
              </form>
            </div>
          </div>
          <?php endif; ?>

          <!-- Tableau garanties -->
          <div class="dt-wrap">
            <div class="dt-hd">
              <div class="dt-title">ðŸ”’ Gestion des garanties <span class="badge b-blue">
                  <?= count($garanties) ?>
                </span></div>
              <input type="text" id="search-gar" class="fi" placeholder="ðŸ” Rechercher..." style="width:180px"
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
                            <?= $g['dc_montant'] ? ' â€” ' . number_format($g['dc_montant'], 0, ',', ' ') . ' TND' : '' ?>
                          </span></td>
                        <td>
                          <?= $tgL[$g['type']] ?? htmlspecialchars($g['type']) ?>
                        </td>
                        <td>
  <?php if (!empty($g['document']) && str_starts_with($g['document'], 'uploads/')): ?>
    <a href="<?= htmlspecialchars($controllerRoot) ?>/AdminCreditController.php?action=download_garantie_file&id=<?= (int)$g['id'] ?>" 
       target="_blank" 
       style="color:var(--blue);text-decoration:none;font-size:.75rem;">
      ðŸ“Ž Voir document
    </a>
  <?php else: ?>
    <?= htmlspecialchars($g['document'] ?: 'â€”') ?>
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
                          <?= htmlspecialchars($g['description'] ?: 'â€”') ?>
                        </td>
                        <td>
                          <div class="td-acts" style="flex-direction: column; gap: 0.3rem;">
                            <div style="display: flex; gap: 4px;">
                              <a href="<?= $self ?>?edit_g=<?= $g['id'] ?>&tab=gar" class="btn-edt">âœï¸ Modifier</a>
                              <form method="POST" action="<?= $self ?>" style="display:inline"
                                onsubmit="return confirm('Supprimer cette garantie ?')">
                                <input type="hidden" name="action" value="delete_garantie" />
                                <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                                <button type="submit" class="btn-del">ðŸ—‘ï¸</button>
                              </form>
                            </div>
                            <?php if ($gStatus === 'en_attente'): ?>
                            <div style="display: flex; gap: 4px; font-size: 0.65rem;">
                              <form method="POST" action="<?= $self ?>" style="display:inline">
                                <input type="hidden" name="action" value="update_garantie_status" />
                                <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                                <input type="hidden" name="statut" value="approuvee" />
                                <button type="submit" class="btn-quick-approve" title="Approuver cette garantie">âœ… Approuver</button>
                              </form>
                              <form method="POST" action="<?= $self ?>" style="display:inline">
                                <input type="hidden" name="action" value="update_garantie_status" />
                                <input type="hidden" name="id" value="<?= $g['id'] ?>" />
                                <input type="hidden" name="statut" value="refusee" />
                                <button type="submit" class="btn-quick-refuse" title="Refuser cette garantie">âŒ Refuser</button>
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

  <!-- MOTIF MODAL -->
   <!-- MAP MODAL -->
<div id="map-modal" class="modal-overlay" style="display:none;" onclick="closeMapModal()">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:700px;width:95%">
    <div class="modal-header">
      <div class="modal-title" id="map-modal-title">ðŸ—ºï¸ Localisation client</div>
      <button class="modal-close" onclick="closeMapModal()">âœ•</button>
    </div>
    <div class="modal-body" style="padding:0">
      <iframe id="map-iframe" src="" width="100%" height="420" style="border:none;border-radius:0 0 0.5rem 0.5rem;" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
</div>
  <div id="motif-modal" class="modal-overlay" style="display:none;" onclick="closeMotifModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="modal-header">
        <div class="modal-title">ðŸ“‹ Motif complet</div>
        <button class="modal-close" onclick="closeMotifModal()">âœ•</button>
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
      const titles = { dashboard: 'Dashboard Administration', credits: 'Dossiers CrÃ©dit', statistique: 'Statistiques', reclamations: 'RÃ©clamations' };
      const bcs = { dashboard: "Admin â€º Vue d'ensemble", credits: 'Admin â€º CrÃ©dits', statistique: 'Admin â€º Statistiques', reclamations: 'Admin â€º RÃ©clamations' };
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
      set('ae-duree', (v => (!v || v < 6 || v > 360) ? 'DurÃ©e invalide.' : '')(parseInt(document.getElementById('a-duree')?.value)));
      set('ae-taux', (v => (isNaN(v) || v < 0 || v > 30) ? 'Taux invalide.' : '')(parseFloat(document.getElementById('a-taux')?.value)));
      const res = document.getElementById('a-resultat')?.value;
      const mot = document.getElementById('a-motif')?.value.trim();
      set('ae-motif', (res !== 'en_attente' && !mot) ? 'Motif obligatoire pour une dÃ©cision finale.' : '');
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
      const map = { vehicule: ['NÂ° carte grise *', 'Ex: 123456TUN'], immobilier: ['NÂ° titre propriÃ©tÃ© *', 'Ex: TP-2021-88821'], garant: ['Nom complet du garant *', 'PrÃ©nom Nom'], autre: ['RÃ©fÃ©rence document *', 'Toute rÃ©fÃ©rence officielle'] };
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

    // â”€â”€â”€â”€â”€â”€ MOTIF MODAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
    // â”€â”€â”€â”€â”€â”€ MAP MODAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const countryCoords = {
  TN:{lat:33.8869,lon:9.5375,name:'Tunisie'},
  FR:{lat:46.2276,lon:2.2137,name:'France'},
  US:{lat:37.0902,lon:-95.7129,name:'Ã‰tats-Unis'},
  GB:{lat:55.3781,lon:-3.4360,name:'Royaume-Uni'},
  DE:{lat:51.1657,lon:10.4515,name:'Allemagne'},
  IT:{lat:41.8719,lon:12.5674,name:'Italie'},
  ES:{lat:40.4637,lon:-3.7492,name:'Espagne'},
  CH:{lat:46.8182,lon:8.2275,name:'Suisse'},
  CA:{lat:56.1304,lon:-106.3468,name:'Canada'},
  BE:{lat:50.5039,lon:4.4699,name:'Belgique'},
  DZ:{lat:28.0339,lon:1.6596,name:'AlgÃ©rie'},
  MA:{lat:31.7917,lon:-7.0926,name:'Maroc'},
  LY:{lat:26.3351,lon:17.2283,name:'Libye'},
};

function openMapModal(countryCode, ip) {
  const modal = document.getElementById('map-modal');
  const iframe = document.getElementById('map-iframe');
  const title  = document.getElementById('map-modal-title');
  const info   = countryCoords[countryCode] || {lat:0,lon:0,name:countryCode};
  const zoom   = 5;

  title.textContent = 'ðŸ—ºï¸ ' + info.name + (ip ? '  â€”  IP: ' + ip : '');
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
async function checkSigStatus(demandeId, submissionId) {
    const badge = document.getElementById('sig-badge-' + demandeId);
    if (badge) {
        badge.textContent = 'â³ VÃ©rification...';
        badge.className = 'badge b-wait';
    }
    try {
        const formData = new FormData();
        formData.append('action', 'check_signature_status');
        formData.append('submission_id', submissionId);
        const response = await fetch(window.CONTROLLER_PATH, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (badge) {
            if (data.completed) {
                badge.textContent = 'âœ… SignÃ©';
                badge.className = 'badge b-on';
                badge.onclick = null;
                badge.style.cursor = 'default';
            } else if (data.status === 'declined') {
                badge.textContent = 'âŒ RefusÃ©';
                badge.className = 'badge b-off';
            } else {
                badge.textContent = 'âœï¸ En attente';
                badge.className = 'badge b-wait';
            }
        }
    } catch (err) {
        console.error('[Signature] Erreur:', err);
        if (badge) badge.textContent = 'âš ï¸ Erreur';
    }
}
    // â”€â”€â”€â”€â”€â”€ CHECKBOX SELECTION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
        alert('Veuillez sÃ©lectionner au moins une demande.');
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
        alert('Veuillez sÃ©lectionner au moins une demande.');
        return;
      }
      if (!confirm(`Supprimer ${ids.length} demande(s) ? Cette action est irrÃ©versible.`)) return;
      
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
</script>

