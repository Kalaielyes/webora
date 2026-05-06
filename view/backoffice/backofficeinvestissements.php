<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>BankFlow Admin — Gestion des Investissements</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="action.css">
<style>
  .meeting-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 15000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  .meeting-modal-overlay.active { display: flex; }
  .meeting-modal {
    width: min(520px, 100%);
    background: #fff;
    border-radius: 12px;
    border: 1px solid var(--border);
    box-shadow: 0 25px 70px rgba(15, 23, 42, 0.25);
    padding: 1rem;
  }
  .meeting-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .8rem;
  }
  .meeting-modal-title {
    font-family: var(--fh);
    font-size: .95rem;
    font-weight: 700;
  }
  .meeting-grid {
    display: grid;
    gap: .75rem;
  }
  .meeting-row label {
    display: block;
    font-size: .72rem;
    color: var(--muted);
    margin-bottom: .3rem;
    text-transform: uppercase;
    letter-spacing: .05em;
  }
  .meeting-row input,
  .meeting-row textarea {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: .55rem .7rem;
    font-family: var(--fb);
    font-size: .82rem;
    outline: none;
  }
  .meeting-row input:focus,
  .meeting-row textarea:focus {
    border-color: var(--blue);
  }
  .meeting-actions {
    margin-top: .9rem;
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
  }
  .meeting-msg {
    margin-top: .55rem;
    font-size: .76rem;
    display: none;
  }
  .meeting-msg.error { color: var(--rose); }
  .meeting-msg.success { color: var(--green); }
</style>
</head>
<body>
<?php
require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../model/investissement.php';

$pdo = Config::getConnexion();
$projectsById = [];
$investments = [];
$pendingInvestmentsCount = 0;

try {
    // Fetch all projects to have details for investments
    $stmt = $pdo->query("
        SELECT p.id_projet, p.titre, p.montant_objectif, p.secteur,
               p.date_limite, p.date_creation, p.status, p.taux_rentabilite, p.temps_retour_brut,
               u.nom AS createur_nom,
               COALESCE((SELECT SUM(i.montant_investi) FROM investissement i WHERE i.id_projet = p.id_projet AND i.status = 'VALIDE'), 0) AS total_investi
        FROM projet p
        LEFT JOIN utilisateur u ON p.id_createur = u.id
    ");
    $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allProjects as $p) {
        $p['montant_restant'] = max(0, $p['montant_objectif'] - $p['total_investi']);
        $p['progression']    = $p['montant_objectif'] > 0
            ? round(($p['total_investi'] / $p['montant_objectif']) * 100, 1) : 0;
        $projectsById[$p['id_projet']] = $p;
    }

    $investments = Investissement::getAllInvestments();
    foreach ($investments as $investment) {
        if ($investment['status'] === 'EN_ATTENTE') {
            $pendingInvestmentsCount++;
        }
    }
} catch (Exception $e) {
    $investments = [];
}
?>
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">ADMIN</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av">AD</div>
    <div>
      <div class="sb-aname">Sara</div>
      <div class="sb-arole">Super administrateur</div>
    </div>
  </div>
  <div class="sb-nav">
    <div class="nav-dropdown" id="actions-dropdown">
      <div class="nav-item active" id="nav-actions-parent">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        Actions
        <svg class="dropdown-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto;transition:transform .2s;transform:rotate(180deg)"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="nav-submenu" id="actions-submenu" style="display:block">
        <a class="nav-item sub-item" href="backofficecondidature.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Projets
        </a>
        <a class="nav-item sub-item active" href="backofficeinvestissements.php" style="padding-left:2.8rem;font-size:.75rem">
          <div class="sub-dot"></div> Investissements
        </a>
      </div>
    </div>
    <a class="nav-item" href="statistiques.php">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
      Statistiques
    </a>

    <div class="nav-section">Paramètres</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/></svg>
      Paramètres
    </a>
  </div>
  <div class="sb-footer">
    <div class="sb-status"><div class="status-dot"></div> Système opérationnel</div>
  </div>
</div>
<div class="main">
  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Investissements</div>
      <div class="breadcrumb">Admin / Investissements</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input id="input-search-investments" placeholder="Rechercher un investisseur…"/>
      </div>
    </div>
  </div>
  <div class="content">
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
              <th>Investisseur</th><th>Projet</th><th>Montant</th><th>Date</th><th>Statut</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="investments-tbody">
            <?php if (empty($investments)): ?>
              <tr>
                <td colspan="6" style="text-align:center;color:rgba(148,163,184,1);padding:2rem 0;">Aucun investissement trouvé dans la base de données.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($investments as $investment): ?>
                <?php
                  $statusClass = 'b-attente';
                  $statusLabel = 'En attente';
                  $statusColor = 'amber';
                  if ($investment['status'] === 'VALIDE') {
                      $statusClass = 'b-approuve';
                      $statusLabel = 'Validé';
                      $statusColor = 'green';
                  } elseif ($investment['status'] === 'REFUSE') {
                      $statusClass = 'b-refuse';
                      $statusLabel = 'Refusé';
                      $statusColor = 'rose';
                  } elseif ($investment['status'] === 'ANNULE') {
                      $statusClass = 'b-refuse';
                      $statusLabel = 'Annulé';
                      $statusColor = 'rose';
                  }
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
                >
                  <td><div class="td-name"><?= htmlspecialchars($investment['nom'] . ' ' . $investment['prenom']) ?></div><div class="td-sub"><?= htmlspecialchars($investment['email']) ?></div></td>
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
            <div class="dp-title" id="dp-title">Sélectionnez un investissement</div>
            <div class="dp-id" id="dp-id">#N/A</div>
            <span class="badge b-attente" id="dp-status"><span class="badge-dot" id="dp-status-dot" style="background:var(--amber)"></span> <span id="dp-status-text">En attente</span></span>
          </div>
        </div>
        <div>
          <div class="dp-section">Informations projet</div>
          <div class="dp-row"><span class="dp-key">Créateur</span><span class="dp-val" id="dp-creator">N/A</span></div>
          <div class="dp-row"><span class="dp-key">Secteur</span><span class="dp-val" id="dp-secteur">—</span></div>
          <div class="dp-row"><span class="dp-key">Date création</span><span class="dp-val" id="dp-date-creation">—</span></div>
          <div class="dp-row"><span class="dp-key">Date limite</span><span class="dp-val" id="dp-date-limite">—</span></div>
          <div class="dp-row"><span class="dp-key">Objectif</span><span class="dp-val" id="dp-objectif">—</span></div>
          <div class="dp-row"><span class="dp-key">TRI</span><span class="dp-val" id="dp-taux">0%</span></div>
          <div class="dp-row"><span class="dp-key">Retour Brut</span><span class="dp-val" id="dp-temps">0 mois</span></div>
        </div>
        <div>
          <div class="dp-section">Collecte</div>
          <div class="dp-row"><span class="dp-key">Montant collecté</span><span class="dp-val" id="dp-collect">0 TND</span></div>
          <div class="dp-row"><span class="dp-key">Restant</span><span class="dp-val" id="dp-restant">0 TND</span></div>
          <div class="dp-row"><span class="dp-key">Progression</span><div class="progress-wrap"><div class="progress-bar"><div id="dp-progress-fill" class="progress-fill" style="width:0%;background:var(--green)"></div></div><span id="dp-progress-pct" class="progress-pct">0%</span></div></div>
        </div>
        <div class="dp-actions">
          <button class="dp-action-btn da-approve" id="btn-approve"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Valider</button>
          <button class="dp-action-btn da-danger" id="btn-refuse"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Refuser</button>
          <button class="dp-action-btn da-warning" id="btn-meeting"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>Planifier une reunion</button>
          <button class="dp-action-btn da-neutral" id="btn-join-now"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>Rejoindre maintenant</button>
          <button class="dp-action-btn da-neutral" id="btn-export">Exporter le rapport</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="meeting-modal-overlay" class="meeting-modal-overlay" aria-hidden="true">
  <div class="meeting-modal">
    <div class="meeting-modal-head">
      <div class="meeting-modal-title">Planifier une reunion video</div>
      <button type="button" class="act-btn" id="meeting-close-btn">×</button>
    </div>
    <form id="meeting-form">
      <div class="meeting-grid">
        <div class="meeting-row">
          <label for="meeting-investor-email">Email investisseur</label>
          <input id="meeting-investor-email" name="invited_emails" type="email" required />
        </div>
        <div class="meeting-row">
          <label for="meeting-date">Date</label>
          <input id="meeting-date" name="date" type="date" required />
        </div>
        <div class="meeting-row">
          <label for="meeting-time">Heure</label>
          <input id="meeting-time" name="time" type="time" required />
        </div>
        <div class="meeting-row">
          <label for="meeting-message">Message (optionnel)</label>
          <textarea id="meeting-message" name="message" rows="3" placeholder="Details du rendez-vous"></textarea>
        </div>
      </div>
      <div class="meeting-actions">
        <button type="button" class="filter-btn" id="meeting-cancel-btn">Annuler</button>
        <button type="submit" class="btn-primary">Envoyer l'invitation</button>
      </div>
      <div id="meeting-error" class="meeting-msg error"></div>
      <div id="meeting-success" class="meeting-msg success"></div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const invControllerUrl = new URL('../../controlller/investissement.php', window.location.href).href;
  const projectControllerUrl = new URL('../../controlller/projet.php', window.location.href).href;
  const dp = {
    title: document.getElementById('dp-title'),
    id: document.getElementById('dp-id'),
    status: document.getElementById('dp-status'),
    statusDot: document.getElementById('dp-status-dot'),
    statusText: document.getElementById('dp-status-text'),
    creator: document.getElementById('dp-creator'),
    secteur: document.getElementById('dp-secteur'),
    dateCreation: document.getElementById('dp-date-creation'),
    dateLimite: document.getElementById('dp-date-limite'),
    objectif: document.getElementById('dp-objectif'),
    taux: document.getElementById('dp-taux'),
    temps: document.getElementById('dp-temps'),
    collect: document.getElementById('dp-collect'),
    restant: document.getElementById('dp-restant'),
    progressFill: document.getElementById('dp-progress-fill'),
    progressPct: document.getElementById('dp-progress-pct'),
    approve: document.getElementById('btn-approve'),
    refuse: document.getElementById('btn-refuse'),
    meeting: document.getElementById('btn-meeting'),
    joinNow: document.getElementById('btn-join-now'),
  };

  const selected = { id: null, status: null, email: '', lastMeetingLink: '' };
  const meetingModal = document.getElementById('meeting-modal-overlay');
  const meetingForm = document.getElementById('meeting-form');
  const meetingEmailInput = document.getElementById('meeting-investor-email');
  const meetingDateInput = document.getElementById('meeting-date');
  const meetingTimeInput = document.getElementById('meeting-time');
  const meetingMessageInput = document.getElementById('meeting-message');
  const meetingError = document.getElementById('meeting-error');
  const meetingSuccess = document.getElementById('meeting-success');
  const meetingCloseBtn = document.getElementById('meeting-close-btn');
  const meetingCancelBtn = document.getElementById('meeting-cancel-btn');

  const selectRow = (row) => {
    document.querySelectorAll('.investment-row.selected').forEach(el => el.classList.remove('selected'));
    if (!row) return;
    row.classList.add('selected');
    
    selected.id = row.dataset.investmentId;
    selected.status = row.dataset.status;
    selected.email = row.dataset.invEmail || '';

    dp.title.textContent = row.dataset.invNom || 'Investisseur';
    dp.id.textContent = '#INV-' + selected.id;
    dp.creator.textContent = row.dataset.pCreator || 'N/A';
    dp.secteur.textContent = row.dataset.pSector || '—';
    dp.dateCreation.textContent = row.dataset.pDateCrea || '—';
    dp.dateLimite.textContent = row.dataset.pDateLim || '—';
    dp.objectif.textContent = row.dataset.pObj ? Number(row.dataset.pObj).toLocaleString('fr-FR') + ' TND' : '—';
    dp.taux.textContent = (row.dataset.pTaux || 0) + '%';
    dp.temps.textContent = (row.dataset.pTemps || 0) + ' mois';
    dp.collect.textContent = row.dataset.pCol ? Number(row.dataset.pCol).toLocaleString('fr-FR') + ' TND' : '0 TND';
    dp.restant.textContent = row.dataset.pRest ? Number(row.dataset.pRest).toLocaleString('fr-FR') + ' TND' : '0 TND';
    
    const prog = row.dataset.pProg || '0';
    dp.progressFill.style.width = Math.min(100, prog) + '%';
    dp.progressFill.style.background = prog >= 100 ? 'var(--green)' : (prog > 50 ? 'var(--blue)' : 'var(--amber)');
    dp.progressPct.textContent = prog + '%';
    
    const status = row.dataset.status || 'EN_ATTENTE';
    const sMap = {
      EN_ATTENTE: { label: 'En attente', color: 'amber', class: 'b-attente' },
      VALIDE: { label: 'Validé', color: 'green', class: 'b-approuve' },
      REFUSE: { label: 'Refusé', color: 'rose', class: 'b-refuse' },
      ANNULE: { label: 'Annulé', color: 'rose', class: 'b-refuse' }
    };
    const s = sMap[status] || sMap.EN_ATTENTE;
    dp.status.className = 'badge ' + s.class;
    dp.statusDot.style.background = `var(--${s.color})`;
    dp.statusText.textContent = s.label;
  };

  const openMeetingModal = () => {
    if (!selected.id) {
      alert('Sélectionnez d\'abord un investissement.');
      return;
    }
    if (!meetingModal) return;
    meetingModal.classList.add('active');
    meetingModal.setAttribute('aria-hidden', 'false');
    if (meetingEmailInput) meetingEmailInput.value = selected.email || '';
    if (meetingDateInput && !meetingDateInput.value) {
      const d = new Date();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      meetingDateInput.value = `${d.getFullYear()}-${month}-${day}`;
    }
    if (meetingTimeInput && !meetingTimeInput.value) meetingTimeInput.value = '10:00';
    if (meetingMessageInput) {
      const investorName = document.getElementById('dp-title')?.textContent || 'Investisseur';
      const projectName = document.querySelector('.investment-row.selected')?.dataset.pTitre || 'Projet';
      meetingMessageInput.value = `Bonjour ${investorName},\nVotre reunion de suivi pour le projet "${projectName}" est planifiee.`;
    }
    if (meetingError) { meetingError.style.display = 'none'; meetingError.textContent = ''; }
    if (meetingSuccess) { meetingSuccess.style.display = 'none'; meetingSuccess.textContent = ''; }
  };

  const closeMeetingModal = () => {
    if (!meetingModal) return;
    meetingModal.classList.remove('active');
    meetingModal.setAttribute('aria-hidden', 'true');
    if (meetingForm) meetingForm.reset();
  };

  document.querySelectorAll('.investment-row').forEach(row => {
    row.addEventListener('click', () => selectRow(row));

    // Handle inline buttons
    const approveBtn = row.querySelector('.act-btn.approve');
    const refuseBtn = row.querySelector('.act-btn.danger');

    if (approveBtn) {
      approveBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        selected.id = row.dataset.investmentId;
        changeStatus('VALIDE');
      });
    }

    if (refuseBtn) {
      refuseBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        selected.id = row.dataset.investmentId;
        changeStatus('REFUSE');
      });
    }
  });

  const changeStatus = async (statusValue) => {
    if (!selected.id) return alert('Sélectionnez d\'abord un investissement.');
    const formData = new FormData();
    formData.append('action', 'admin_update_investment_status');
    formData.append('investment_id', selected.id);
    formData.append('new_status', statusValue);

    try {
      const res = await fetch(invControllerUrl, { method: 'POST', body: formData });
      const result = await res.json();
      if (result.success) window.location.reload();
      else alert(result.message || 'Erreur lors de la mise à jour.');
    } catch (err) { alert('Erreur réseau.'); }
  };

  dp.approve.addEventListener('click', () => changeStatus('VALIDE'));
  dp.refuse.addEventListener('click', () => changeStatus('REFUSE'));
  if (dp.meeting) dp.meeting.addEventListener('click', openMeetingModal);
  if (meetingCloseBtn) meetingCloseBtn.addEventListener('click', closeMeetingModal);
  if (meetingCancelBtn) meetingCancelBtn.addEventListener('click', closeMeetingModal);
  if (meetingModal) {
    meetingModal.addEventListener('click', (event) => {
      if (event.target === meetingModal) closeMeetingModal();
    });
  }

  if (meetingForm) {
    meetingForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (meetingError) { meetingError.style.display = 'none'; meetingError.textContent = ''; }
      if (meetingSuccess) { meetingSuccess.style.display = 'none'; meetingSuccess.textContent = ''; }

      const formData = new FormData(meetingForm);
      formData.append('action', 'create_meeting');
      // Send the selected investment id so the email includes investment details
      if (selected.id) formData.append('investment_id', selected.id);

      try {
        const response = await fetch(projectControllerUrl, { method: 'POST', body: formData });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Impossible de planifier la reunion.');
        const link = result.data?.meeting_link || '';
        const emailMessage = result.data?.email_message || '';
        // Store the meeting link so "Rejoindre maintenant" opens the SAME link
        if (link) selected.lastMeetingLink = link;
        if (meetingSuccess) {
          meetingSuccess.innerHTML = `Invitation envoyee. <a href="${link}" target="_blank" rel="noopener noreferrer">Ouvrir la reunion</a>${emailMessage ? ' | Email: ' + emailMessage : ''}`;
          meetingSuccess.style.display = 'block';
        }
      } catch (error) {
        if (meetingError) {
          meetingError.textContent = error.message || 'Erreur reseau.';
          meetingError.style.display = 'block';
        }
      }
    });
  }

  if (dp.joinNow) {
    dp.joinNow.addEventListener('click', async () => {
      if (!selected.id) {
        alert('Selectionnez un investissement.');
        return;
      }

      // If a meeting was already scheduled this session, open the same link
      if (selected.lastMeetingLink) {
        window.open(selected.lastMeetingLink, '_blank', 'noopener,noreferrer');
        return;
      }

      if (!selected.email) {
        alert('Selectionnez un investissement avec un email investisseur valide.');
        return;
      }

      // No meeting yet — create an instant one
      const formData = new FormData();
      formData.append('action', 'create_meeting_instant');
      formData.append('invited_emails', selected.email);
      formData.append('message', 'Invitation a une reunion immediate.');
      if (selected.id) formData.append('investment_id', selected.id);

      try {
        const response = await fetch(projectControllerUrl, { method: 'POST', body: formData });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Impossible de creer une reunion immediate.');
        const link = result.data?.meeting_link || '';
        if (!link) throw new Error('Lien de reunion manquant.');
        // Store so subsequent clicks reuse same link
        selected.lastMeetingLink = link;
        window.open(link, '_blank', 'noopener,noreferrer');
        if (result.data?.email_message) {
          alert('Reunion ouverte. Email envoye: ' + result.data.email_message);
        }
      } catch (error) {
        alert(error.message || 'Erreur reseau.');
      }
    });
  }

  const searchInput = document.getElementById('input-search-investments');
  searchInput.addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.investment-row').forEach(row => {
      const name = (row.dataset.invNom || '').toLowerCase();
      row.style.display = name.includes(term) ? '' : 'none';
    });
  });

  // Filter by status
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const text = btn.textContent.trim().toLowerCase();
      let target = 'all';
      if (text === 'en attente') target = 'EN_ATTENTE';
      else if (text === 'approuvés') target = 'VALIDE';
      else if (text === 'refusés') target = 'REFUSE';

      document.querySelectorAll('.investment-row').forEach(row => {
        if (target === 'all' || row.dataset.status === target || (target === 'REFUSE' && row.dataset.status === 'ANNULE')) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });

  // Export report
  document.getElementById('btn-export').addEventListener('click', () => {
    const row = document.querySelector('.investment-row.selected');
    if (!row) { alert('Sélectionnez un investissement.'); return; }
    const d = row.dataset;
    const fmt = v => Number(v||0).toLocaleString('fr-FR');
    const statusLabels = {EN_ATTENTE:'En attente',VALIDE:'Validé',REFUSE:'Refusé',ANNULE:'Annulé'};
    const now = new Date().toLocaleDateString('fr-FR', {day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
    
    const html = `
      <!DOCTYPE html><html><head><meta charset="UTF-8"/><title>Rapport INV-${d.investmentId}</title>
      <style>body{font-family:sans-serif;padding:40px;line-height:1.6}h2{color:#2563EB}table{width:100%;border-collapse:collapse;margin:20px 0}th,td{border:1px solid #ddd;padding:12px;text-align:left}th{background:#f8fafc}</style></head>
      <body><h2>Rapport d'Investissement #INV-${d.investmentId}</h2><p>Généré le ${now}</p>
      <h3>Investisseur</h3><table><tr><th>Nom</th><td>${d.invNom}</td></tr><tr><th>Email</th><td>${d.invEmail}</td></tr><tr><th>Montant</th><td>${fmt(d.invMontant)} TND</td></tr><tr><th>Date</th><td>${d.invDate}</td></tr><tr><th>Statut</th><td>${statusLabels[d.status]||d.status}</td></tr></table>
      <h3>Projet Associé</h3><table><tr><th>Titre</th><td>${d.pTitre}</td></tr><tr><th>Secteur</th><td>${d.pSector}</td></tr><tr><th>Objectif</th><td>${fmt(d.pObj)} TND</td></tr><tr><th>Collecté</th><td>${fmt(d.pCol)} TND</td></tr><tr><th>Progression</th><td>${d.pProg}%</td></tr></table></body></html>`;
    
    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Rapport_INV-${d.investmentId}.html`;
    a.click();
  });

  // Sidebar Dropdown Logic
  const actionsParent = document.getElementById('nav-actions-parent');
  const actionsSubmenu = document.getElementById('actions-submenu');
  const chevron = actionsParent.querySelector('.dropdown-chevron');
  
  actionsParent.addEventListener('click', (e) => {
    const isVisible = actionsSubmenu.style.display === 'block';
    actionsSubmenu.style.display = isVisible ? 'none' : 'block';
    chevron.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
  });

  const firstRow = document.querySelector('.investment-row');
  if (firstRow) selectRow(firstRow);
});
</script>
</body>
</html>