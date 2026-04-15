<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>BankFlow Admin — Gestion des Actions</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="action.css">
</head>
<body>
<?php
require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../model/investissement.php';

$pdo = Config::getConnexion();
$projects = [];
$investments = [];
$pendingCount = 0;
$totalCount = 0;
$pendingInvestmentsCount = 0;

try {
    $stmt = $pdo->query("SELECT p.id_projet, p.titre, p.description, p.montant_objectif, p.secteur, p.date_limite, p.status, COALESCE(p.request_code, CONCAT('REQ-', p.id_projet)) AS request_code, u.nom AS createur_nom
                          FROM projet p
                          LEFT JOIN utilisateur u ON p.id_createur = u.id
                          ORDER BY p.date_creation DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCount = count($projects);
    foreach ($projects as $project) {
        if ($project['status'] === 'EN_ATTENTE') {
            $pendingCount++;
        }
    }
} catch (Exception $e) {
    $projects = [];
}

try {
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
      <div class="sb-aname">Admin Dashboard</div>
      <div class="sb-arole">Super administrateur</div>
    </div>
  </div>
  <div class="sb-nav">
    <div class="nav-section">Vue d'ensemble</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Tableau de bord
    </a>
    <div class="nav-section">Gestion</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Utilisateurs
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      Comptes
    </a>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      Actions
      <span class="nav-badge-amber">5</span>
    </a>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
      Virements
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
      <div class="page-title">Gestion des Actions</div>
      <div class="breadcrumb">Admin / Actions</div>
    </div>
    <div class="tb-right">
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input placeholder="Rechercher un projet…"/>
      </div>
      
    </div>
  </div>
  <div class="content">
    <div class="approve-banner">
      <div class="ab-icon">
        <svg width="16" height="16" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      </div>
      <div class="ab-text">
        <div class="ab-title"><?= $pendingCount ?> projets en attente d'approbation</div>
        <div class="ab-sub">Ces projets ont été soumis par des créateurs et nécessitent votre validation avant publication.</div>
      </div>
      <div class="ab-actions">
        <button class="btn-approve">Voir les demandes</button>
        <button class="btn-refuse">Ignorer</button>
      </div>
    </div>
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--blue-light)">
          <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val"><?= $totalCount ?></div>
          <div class="kpi-label">Projets total</div>
          <div class="kpi-sub" style="color:var(--green)">↑ <?= max(0, $pendingCount) ?> en attente</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--amber-light)">
          <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">1</div>
          <div class="kpi-label">En attente</div>
          <div class="kpi-sub" style="color:var(--amber)">Approbation requise</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--green-light)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val" style="font-size:1.15rem">0 TND</div>
          <div class="kpi-label">Total investi</div>
          <div class="kpi-sub" style="color:var(--green)">↑ +0% vs N-1</div>
        </div>
      </div>
      <div class="kpi">
        <div class="kpi-icon" style="background:var(--violet-light)">
          <svg width="18" height="18" fill="none" stroke="var(--violet)" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="kpi-data">
          <div class="kpi-val">0</div>
          <div class="kpi-label">Investisseurs actifs</div>
          <div class="kpi-sub" style="color:var(--green)">↑ 0 nouveaux</div>
        </div>
      </div>
    </div>
    <div class="two-col-layout">
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Projets & Investissements</div>
          <div style="display:flex;gap:1rem;align-items:center;">
            <button class="tab-toggle active" data-tab="projects" style="padding:.6rem 1.2rem;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);border-radius:6px;color:var(--blue);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .2s;">Projets (<?= $totalCount ?>)</button>
            <button class="tab-toggle" data-tab="investments" style="padding:.6rem 1.2rem;background:transparent;border:1px solid rgba(148,163,184,.2);border-radius:6px;color:rgba(148,163,184,1);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .2s;">Investissements (<?= count($investments) ?>)</button>
          </div>
          <div class="filters">
            <button class="filter-btn active">Tous</button>
            <button class="filter-btn">En attente</button>
            <button class="filter-btn">Approuvés</button>
            <button class="filter-btn">Refusés</button>
          </div>
        </div>
        <table id="projects-table">
          <thead>
            <tr>
              <th>Projet</th><th>Secteur</th><th>Objectif</th><th>Collecté</th><th>Restant</th><th>Progression</th><th>Statut</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="projects-tbody">
            <?php if (empty($projects)): ?>
              <tr>
                <td colspan="8" style="text-align:center;color:rgba(148,163,184,1);padding:2rem 0;">Aucun projet trouvé dans la base de données.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($projects as $project): ?>
                <?php
                  $statusClass = 'b-attente';
                  $statusLabel = 'En attente';
                  $statusColor = 'amber';
                  if ($project['status'] === 'EN_COURS') {
                      $statusClass = 'b-en-cours';
                      $statusLabel = 'En cours';
                      $statusColor = 'blue';
                  } elseif ($project['status'] === 'TERMINE') {
                      $statusClass = 'b-approuve';
                      $statusLabel = 'Terminé';
                      $statusColor = 'green';
                  } elseif ($project['status'] === 'ANNULE') {
                      $statusClass = 'b-refuse';
                      $statusLabel = 'Annulé';
                      $statusColor = 'rose';
                  }
                  $collected = $project['total_investi'] ?? 0;
                  $remaining = $project['montant_restant'] ?? $project['montant_objectif'];
                  $progress = $project['progression'] ?? 0;
                  $sectorClass = strtolower(str_replace('é','e', str_replace(' ','', $project['secteur'])));
                ?>
                <tr class="project-row" data-project-id="<?= $project['id_projet'] ?>">
                  <td><div class="td-name"><?= htmlspecialchars($project['titre']) ?></div><div class="td-sub">Par: <?= htmlspecialchars($project['createur_nom'] ?: 'N/A') ?> · <?= htmlspecialchars($project['request_code']) ?></div></td>
                  <td><span class="t-<?= htmlspecialchars($sectorClass) ?>"><?= htmlspecialchars($project['secteur']) ?></span></td>
                  <td><span style="font-family:var(--fm)"><?= number_format((float)$project['montant_objectif'], 0, ',', ' ') ?></span></td>
                  <td><span style="font-family:var(--fm);font-weight:500"><?= number_format($collected, 0, ',', ' ') ?></span></td>
                  <td><span style="font-family:var(--fm);color:var(--orange)"><?= number_format($remaining, 0, ',', ' ') ?></span></td>
                  <td><div class="progress-wrap"><div class="progress-bar"><div class="progress-fill" style="width:<?= $progress ?>%;background:var(--green)"></div></div><span class="progress-pct"><?= $progress ?>%</span></div></td>
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
        <table id="investments-table" style="display:none;">
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
                ?>
                <tr class="investment-row" data-investment-id="<?= $investment['id_investissement'] ?>">
                  <td><div class="td-name"><?= htmlspecialchars($investment['nom'] . ' ' . $investment['prenom']) ?></div><div class="td-sub"><?= htmlspecialchars($investment['email']) ?></div></td>
                  <td><?= htmlspecialchars($investment['projet_titre'] ?: 'N/A') ?></td>
                  <td><span style="font-family:var(--fm);font-weight:600"><?= number_format((float)$investment['montant_investi'], 2, ',', ' ') ?> TND</span></td>
                  <td><?= htmlspecialchars($investment['date_investissement']) ?></td>
                  <td><span class="badge <?= $statusClass ?>"><span class="badge-dot" style="background:var(--<?= $statusColor ?>)"></span><?= $statusLabel ?></span></td>
                  <td><div class="action-group">
                    <button class="act-btn"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    <button class="act-btn approve"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></button>
                    <button class="act-btn danger"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1=\"4.93\" y1=\"4.93\" x2=\"19.07\" y2=\"19.07\"/></svg></button>
                  </div></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="detail-panel">
        <div class="dp-header">
          <div class="dp-icon">
            <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          </div>
          <div>
            <div class="dp-title" id="dp-title">Sélectionnez un projet</div>
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
          <button class="dp-action-btn da-neutral" id="btn-export">Exporter le rapport</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const controllerUrl = new URL('../../controlller/projet.php', window.location.href).href;
  const rows = document.querySelectorAll('.project-row');
  const selected = {
    id: null,
    status: null,
  };
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
    collect: document.getElementById('dp-collect'),
    restant: document.getElementById('dp-restant'),
    progressFill: document.getElementById('dp-progress-fill'),
    progressPct: document.getElementById('dp-progress-pct'),
    approve: document.getElementById('btn-approve'),
    refuse: document.getElementById('btn-refuse'),
  };

  const statusLabels = {
    EN_ATTENTE: 'En attente',
    EN_COURS: 'En cours',
    TERMINE: 'Terminé',
    ANNULE: 'Annulé',
    VALIDE: 'Validé',
    REFUSE: 'Refusé'
  };

  const statusClasses = {
    EN_ATTENTE: 'b-attente',
    EN_COURS: 'b-en-cours',
    TERMINE: 'b-approuve',
    ANNULE: 'b-refuse',
    VALIDE: 'b-approuve',
    REFUSE: 'b-refuse'
  };

  const statusColors = {
    EN_ATTENTE: 'amber',
    EN_COURS: 'blue',
    TERMINE: 'green',
    ANNULE: 'rose',
    VALIDE: 'green',
    REFUSE: 'rose'
  };

  const loadProjectDetails = async (projectId) => {
    try {
      const response = await fetch(`${controllerUrl}?action=get_project&id=${projectId}`);
      const result = await response.json();
      if (!result.success || !result.data) {
        alert('Impossible de charger les détails du projet.');
        return;
      }
      const project = result.data;
      selected.id = project.id_projet;
      selected.status = project.status;
      dp.title.textContent = project.titre || 'Projet sélectionné';
      dp.id.textContent = project.request_code || '#N/A';
      dp.creator.textContent = project.createur_nom || 'N/A';
      dp.secteur.textContent = project.secteur || '—';
      dp.dateCreation.textContent = project.date_creation || '—';
      dp.dateLimite.textContent = project.date_limite || '—';
      dp.objectif.textContent = project.montant_objectif ? project.montant_objectif + ' TND' : '—';
      dp.collect.textContent = '0 TND';
      dp.restant.textContent = project.montant_objectif ? project.montant_objectif + ' TND' : '0 TND';
      dp.progressFill.style.width = '0%';
      dp.progressPct.textContent = '0%';
      const status = project.status || 'EN_ATTENTE';
      dp.status.className = 'badge ' + (statusClasses[status] || 'b-attente');
      dp.statusDot.style.background = `var(--${statusColors[status] || 'amber'})`;
      if (dp.statusText) {
        dp.statusText.textContent = statusLabels[status] || status;
      }
    } catch (error) {
      console.error('Erreur lors du chargement des détails:', error);
      alert('Une erreur est survenue lors du chargement du projet.');
    }
  };

  const selectRow = (row) => {
    document.querySelectorAll('.project-row.selected').forEach(el => el.classList.remove('selected'));
    if (row) {
      row.classList.add('selected');
      const projectId = row.dataset.projectId;
      if (projectId) {
        loadProjectDetails(projectId);
      }
    }
  };

  rows.forEach(row => {
    row.addEventListener('click', () => selectRow(row));
  });

  const changeStatus = async (statusValue) => {
    if (!selected.id) return alert('Sélectionnez d\'abord un projet.');
    try {
      const data = new FormData();
      data.append('action', 'admin_update_status');
      data.append('project_id', selected.id);
      data.append('new_status', statusValue);
      const response = await fetch(controllerUrl, { method: 'POST', body: data });
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'Erreur de mise à jour du statut.');
      }
      // Reload the table data to reflect status changes
      const statusMap = {
        VALIDE: { class: 'b-approuve', color: 'green', label: 'Validé' },
        REFUSE: { class: 'b-refuse', color: 'rose', label: 'Refusé' },
        EN_ATTENTE: { class: 'b-attente', color: 'amber', label: 'En attente' },
        EN_COURS: { class: 'b-en-cours', color: 'blue', label: 'En cours' },
        TERMINE: { class: 'b-approuve', color: 'green', label: 'Terminé' },
        ANNULE: { class: 'b-refuse', color: 'rose', label: 'Annulé' }
      };
      const statusInfo = statusMap[statusValue] || statusMap['EN_ATTENTE'];
      const selectedRow = document.querySelector('.project-row.selected');
      if (selectedRow) {
        const statusBadge = selectedRow.querySelector('.badge');
        if (statusBadge) {
          statusBadge.className = 'badge ' + statusInfo.class;
          const dot = statusBadge.querySelector('.badge-dot');
          if (dot) dot.style.background = `var(--${statusInfo.color})`;
          const labelSpan = statusBadge.querySelector('span:last-child');
          if (!labelSpan) {
            statusBadge.innerHTML += ` <span>${statusInfo.label}</span>`;
          } else {
            const textNode = statusBadge.childNodes[statusBadge.childNodes.length - 1];
            if (textNode.nodeType === Node.TEXT_NODE) {
              textNode.textContent = statusInfo.label;
            }
          }
        }
        selected.status = statusValue;
        dp.status.className = 'badge ' + statusInfo.class;
        dp.statusDot.style.background = `var(--${statusInfo.color})`;
        if (dp.statusText) {
          dp.statusText.textContent = statusInfo.label;
        }
        alert('Statut mis à jour avec succès!');
      }
    } catch (error) {
      alert(error.message);
    }
  };

  dp.approve.addEventListener('click', () => changeStatus('VALIDE'));
  dp.refuse.addEventListener('click', () => changeStatus('REFUSE'));

  // Tab switching logic
  const tabToggles = document.querySelectorAll('.tab-toggle');
  const projectsTable = document.getElementById('projects-table');
  const investmentsTable = document.getElementById('investments-table');
  
  tabToggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      const tab = toggle.dataset.tab;
      tabToggles.forEach(t => {
        t.classList.toggle('active', t.dataset.tab === tab);
        if (t.dataset.tab === tab) {
          t.style.background = 'rgba(59,130,246,.1)';
          t.style.color = 'var(--blue)';
          t.style.borderColor = 'rgba(59,130,246,.2)';
        } else {
          t.style.background = 'transparent';
          t.style.color = 'rgba(148,163,184,1)';
          t.style.borderColor = 'rgba(148,163,184,.2)';
        }
      });
      
      if (tab === 'projects') {
        projectsTable.style.display = '';
        investmentsTable.style.display = 'none';
      } else {
        projectsTable.style.display = 'none';
        investmentsTable.style.display = '';
      }
    });
  });

  // Investment selected state
  let selectedInvestment = { id: null, status: null };
  const investmentRows = document.querySelectorAll('.investment-row');
  const investmentControllerUrl = new URL('../../controlller/investissement.php', window.location.href).href;

  const selectInvestmentRow = (row) => {
    document.querySelectorAll('.investment-row.selected').forEach(el => el.classList.remove('selected'));
    if (row) {
      row.classList.add('selected');
      const investmentId = row.dataset.investmentId;
      selectedInvestment.id = investmentId;
      const statusBadge = row.querySelector('.badge');
      if (statusBadge) {
        selectedInvestment.status = statusBadge.querySelector('[class*="b-"]')?.className;
      }
      // Update detail panel for investment
      const cells = row.querySelectorAll('td');
      if (cells.length >= 4) {
        dp.title.textContent = cells[1].textContent || 'Investissement';
        dp.id.textContent = '#INV-' + investmentId;
        dp.creator.textContent = cells[0].querySelector('.td-name')?.textContent || 'N/A';
        dp.secteur.textContent = cells[1].textContent || '—';
        dp.dateCreation.textContent = cells[3].textContent || '—';
        dp.collect.textContent = cells[2].textContent || '0 TND';
        dp.restant.textContent = '0 TND';
        dp.progressFill.style.width = '0%';
        dp.progressPct.textContent = '0%';
      }
    }
  };

  investmentRows.forEach(row => {
    row.addEventListener('click', () => selectInvestmentRow(row));
  });

  const changeInvestmentStatus = async (statusValue) => {
    if (!selectedInvestment.id) return alert('Sélectionnez d\'abord un investissement.');
    try {
      const data = new FormData();
      data.append('action', 'admin_update_investment_status');
      data.append('investment_id', selectedInvestment.id);
      data.append('new_status', statusValue);
      const response = await fetch(investmentControllerUrl, { method: 'POST', body: data });
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'Erreur de mise à jour du statut.');
      }
      
      const statusMap = {
        VALIDE: { class: 'b-approuve', color: 'green', label: 'Validé' },
        REFUSE: { class: 'b-refuse', color: 'rose', label: 'Refusé' },
        EN_ATTENTE: { class: 'b-attente', color: 'amber', label: 'En attente' },
        ANNULE: { class: 'b-refuse', color: 'rose', label: 'Annulé' }
      };
      const statusInfo = statusMap[statusValue] || statusMap['EN_ATTENTE'];
      
      const selectedRow = document.querySelector('.investment-row.selected');
      if (selectedRow) {
        const statusBadge = selectedRow.querySelector('.badge');
        if (statusBadge) {
          statusBadge.className = 'badge ' + statusInfo.class;
          const dot = statusBadge.querySelector('.badge-dot');
          if (dot) dot.style.background = `var(--${statusInfo.color})`;
          const textSpan = statusBadge.querySelector('span:last-child');
          if (textSpan) textSpan.textContent = statusInfo.label;
        }
      }
      selectedInvestment.status = statusValue;
      dp.status.className = 'badge ' + statusInfo.class;
      dp.statusDot.style.background = `var(--${statusInfo.color})`;
      if (dp.statusText) {
        dp.statusText.textContent = statusInfo.label;
      }
      alert('Statut de l\'investissement mis à jour avec succès!');
    } catch (error) {
      alert(error.message);
    }
  };

  // Override approve/refuse buttons for investments
  const originalApprove = dp.approve.onclick;
  const originalRefuse = dp.refuse.onclick;
  
  dp.approve.addEventListener('click', () => {
    const activeTab = document.querySelector('.tab-toggle.active').dataset.tab;
    if (activeTab === 'investments') {
      changeInvestmentStatus('VALIDE');
    } else {
      changeStatus('VALIDE');
    }
  });
  
  dp.refuse.addEventListener('click', () => {
    const activeTab = document.querySelector('.tab-toggle.active').dataset.tab;
    if (activeTab === 'investments') {
      changeInvestmentStatus('REFUSE');
    } else {
      changeStatus('REFUSE');
    }
  });

  if (rows.length > 0) {
    selectRow(rows[0]);
  }
});
</script>
</body>
</html>