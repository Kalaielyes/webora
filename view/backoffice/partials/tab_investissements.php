<?php
// This partial is included inside backofficecondidature.php
// Variables $investments, $projectsById, $scoresByUserId are available from parent
?>
<div class="two-col-layout">
  <div class="table-card">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Liste des Investissements</div>
      <div class="filters">
        <button class="filter-btn active">Tous</button>
        <button class="filter-btn">En attente</button>
        <button class="filter-btn">Approuvés</button>
        <button class="filter-btn">Refusés</button>
      </div>
    </div>
    <table id="investments-table">
      <thead>
        <tr>
          <th>Investisseur</th><th>Score</th><th>Projet</th><th>Montant</th><th>Date</th><th>Statut</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="investments-tbody">
        <?php if (empty($investments)): ?>
          <tr><td colspan="7" style="text-align:center;color:rgba(148,163,184,1);padding:2rem 0;">Aucun investissement trouvé.</td></tr>
        <?php else: ?>
          <?php foreach ($investments as $investment): ?>
            <?php
              $statusClass = 'b-attente'; $statusLabel = 'En attente'; $statusColor = 'amber';
              if ($investment['status'] === 'VALIDE') { $statusClass = 'b-approuve'; $statusLabel = 'Validé'; $statusColor = 'green'; }
              elseif ($investment['status'] === 'REFUSE') { $statusClass = 'b-refuse'; $statusLabel = 'Refusé'; $statusColor = 'rose'; }
              elseif ($investment['status'] === 'ANNULE') { $statusClass = 'b-refuse'; $statusLabel = 'Annulé'; $statusColor = 'rose'; }
              $proj = $projectsById[$investment['id_projet']] ?? null;
              $p_creator = $proj['createur_nom'] ?? 'N/A';
              $p_sector = $proj['secteur'] ?? '—';
              $p_date_crea = $proj['date_creation'] ?? '—';
              $p_date_lim = $proj['date_limite'] ?? '—';
              $p_obj = $proj['montant_objectif'] ?? '0';
              $p_col = $proj['total_investi'] ?? '0';
              $p_rest = $proj['montant_restant'] ?? '0';
              $p_prog = $proj['progression'] ?? '0';
              $p_taux = $proj['taux_rentabilite'] ?? '0';
              $p_temps = $proj['temps_retour_brut'] ?? '0';
              $uScore = $scoresByUserId[$investment['id_investisseur']] ?? null;
              $scoreVal = (int)($uScore['trust_score'] ?? 0);
              $scoreDetails = $uScore['score_details'] ?? '[]';
              $badgeClass = $scoreVal >= 75 ? 'score-green' : ($scoreVal >= 45 ? 'score-amber' : 'score-rose');
            ?>
            <tr class="investment-row"
                data-investment-id="<?= $investment['id_investissement'] ?>"
                data-status="<?= htmlspecialchars($investment['status'], ENT_QUOTES) ?>"
                data-inv-nom="<?= htmlspecialchars($investment['nom'] . ' ' . $investment['prenom'], ENT_QUOTES) ?>"
                data-inv-email="<?= htmlspecialchars($investment['email'] ?? '', ENT_QUOTES) ?>"
                data-inv-montant="<?= htmlspecialchars((string)$investment['montant_investi'], ENT_QUOTES) ?>"
                data-inv-date="<?= htmlspecialchars($investment['date_investissement'] ?? '', ENT_QUOTES) ?>"
                data-inv-commentaire="<?= htmlspecialchars($investment['commentaire'] ?? '', ENT_QUOTES) ?>"
                data-p-titre="<?= htmlspecialchars($investment['projet_titre'] ?? 'N/A', ENT_QUOTES) ?>"
                data-p-creator="<?= htmlspecialchars($p_creator, ENT_QUOTES) ?>"
                data-p-sector="<?= htmlspecialchars($p_sector, ENT_QUOTES) ?>"
                data-p-date-crea="<?= htmlspecialchars($p_date_crea, ENT_QUOTES) ?>"
                data-p-date-lim="<?= htmlspecialchars($p_date_lim, ENT_QUOTES) ?>"
                data-p-obj="<?= htmlspecialchars($p_obj, ENT_QUOTES) ?>"
                data-p-col="<?= htmlspecialchars($p_col, ENT_QUOTES) ?>"
                data-p-rest="<?= htmlspecialchars($p_rest, ENT_QUOTES) ?>"
                data-p-prog="<?= htmlspecialchars($p_prog, ENT_QUOTES) ?>"
                data-p-taux="<?= htmlspecialchars($p_taux, ENT_QUOTES) ?>"
                data-p-temps="<?= htmlspecialchars($p_temps, ENT_QUOTES) ?>"
                data-score="<?= $scoreVal ?>"
                data-score-details="<?= htmlspecialchars($scoreDetails, ENT_QUOTES) ?>"
            >
              <td><div class="td-name"><?= htmlspecialchars($investment['nom'] . ' ' . $investment['prenom']) ?></div><div class="td-sub"><?= htmlspecialchars($investment['email']) ?></div></td>
              <td><span class="score-badge <?= $badgeClass ?>"><?= $scoreVal ?>/100</span></td>
              <td><?= htmlspecialchars($investment['projet_titre'] ?: 'N/A') ?></td>
              <td><span class="td-mono"><?= number_format((float)$investment['montant_investi'], 2, ',', ' ') ?> TND</span></td>
              <td><span class="td-mono"><?= htmlspecialchars($investment['date_investissement']) ?></span></td>
              <td><span class="badge <?= $statusClass ?>"><span class="badge-dot" style="background:var(--<?= $statusColor ?>)"></span><?= $statusLabel ?></span></td>
              <td><div class="action-group">
                <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                <button class="act-btn approve"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></button>
                <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
              </div></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="detail-panel">
    <div class="dp-header">
      <div>
        <div class="dp-title" id="inv-dp-title">Sélectionnez un investissement</div>
        <div class="dp-id" id="inv-dp-id">#N/A</div>
        <span class="badge b-attente" id="inv-dp-status"><span class="badge-dot" id="inv-dp-status-dot" style="background:var(--amber)"></span> <span id="inv-dp-status-text">En attente</span></span>
      </div>
    </div>
    <div>
      <div class="dp-section">Informations projet</div>
      <div class="dp-row"><span class="dp-key">Créateur</span><span class="dp-val" id="inv-dp-creator">N/A</span></div>
      <div class="dp-row"><span class="dp-key">Secteur</span><span class="dp-val" id="inv-dp-secteur">—</span></div>
      <div class="dp-row"><span class="dp-key">Date création</span><span class="dp-val" id="inv-dp-date-creation">—</span></div>
      <div class="dp-row"><span class="dp-key">Date limite</span><span class="dp-val" id="inv-dp-date-limite">—</span></div>
      <div class="dp-row"><span class="dp-key">Objectif</span><span class="dp-val" id="inv-dp-objectif">—</span></div>
      <div class="dp-row"><span class="dp-key">TRI</span><span class="dp-val" id="inv-dp-taux">0%</span></div>
      <div class="dp-row"><span class="dp-key">Retour Brut</span><span class="dp-val" id="inv-dp-temps">0 mois</span></div>
      <div class="dp-row">
        <span class="dp-key">Score Confiance</span>
        <div style="display:flex;align-items:center;gap:.5rem;">
          <span class="dp-val" id="inv-dp-score">0/100</span>
          <button class="act-btn" id="btn-score-details" title="Détails du score" style="width:24px;height:24px;">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
    </div>
    <div>
      <div class="dp-section">Collecte</div>
      <div class="dp-row"><span class="dp-key">Montant collecté</span><span class="dp-val" id="inv-dp-collect">0 TND</span></div>
      <div class="dp-row"><span class="dp-key">Restant</span><span class="dp-val" id="inv-dp-restant">0 TND</span></div>
      <div class="dp-row"><span class="dp-key">Progression</span><div class="progress-wrap"><div class="progress-bar"><div id="inv-dp-progress-fill" class="progress-fill" style="width:0%;background:var(--green)"></div></div><span id="inv-dp-progress-pct" class="progress-pct">0%</span></div></div>
    </div>
    <div class="dp-actions">
      <button class="dp-action-btn da-approve" id="btn-approve"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Valider</button>
      <button class="dp-action-btn da-danger" id="btn-refuse"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Refuser</button>
      <button class="dp-action-btn da-warning" id="btn-meeting"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>Planifier une reunion</button>
      <button class="dp-action-btn da-neutral" id="btn-join-now"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>Rejoindre maintenant</button>
      <button class="dp-action-btn da-neutral" id="btn-inv-export">Exporter le rapport</button>
    </div>
  </div>
</div>
