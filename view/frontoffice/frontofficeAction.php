<?php
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/config.php';
Session::requireLogin();

// Fetch real user accounts for the investment modal
$_action_user_id = (int)(Session::get('user_id') ?? 0);
$_action_user_accounts = [];
if ($_action_user_id > 0) {
    try {
        $_pdo = Config::getConnexion();
        $_stmt = $_pdo->prepare("SELECT id_compte, iban, type_compte, solde, devise FROM comptebancaire WHERE id_utilisateur = ? AND statut = 'actif' ORDER BY type_compte");
        $_stmt->execute([$_action_user_id]);
        $_action_user_accounts = $_stmt->fetchAll();
    } catch (Exception $_e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>MonEspace — Mes Actions</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= APP_URL ?>/view/assets/css/frontoffice/Utilisateur.css">
<link rel="stylesheet" href="action.css?v=6">
<script>
  (function() {
    var t = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
    var p = localStorage.getItem('privacy_mode') || 'visible';
    document.documentElement.setAttribute('data-privacy', p);
  })();
</script>
<style>
  *, *::before, *::after {
    transition: background-color 0.4s ease, border-color 0.4s ease, color 0.4s ease, box-shadow 0.4s ease;
  }
  html[data-privacy="hidden"] .privacy-blur {
    filter: blur(8px);
    opacity: 0.7;
    user-select: none;
  }
  html[data-privacy="hidden"] .privacy-blur:hover {
    filter: none;
    opacity: 1;
  }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
  }
  .modal-overlay.active {
    display: flex;
  }
  .modal-card {
    width: min(560px, 100%);
    background: var(--surface);
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 24px;
    box-shadow: 0 30px 75px rgba(15, 23, 42, 0.35);
    padding: 1.3rem;
  }
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text);
  }
  .modal-close {
    background: transparent;
    border: none;
    color: rgba(241, 245, 249, 0.9);
    font-size: 1.25rem;
    cursor: pointer;
  }
  .modal-row {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .amount-chip {
    cursor: pointer;
  }
  .amount-chip.selected {
    background: rgba(37, 99, 235, 1);
    color: #fff;
    border-color: rgba(37, 99, 235, 1);
  }
  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
  }
  .modal-error {
    color: #ef4444;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: none;
  }
  .modal-success {
    color: #22c55e;
    font-size: 0.95rem;
    margin-top: 0.5rem;
    display: none;
  }
  .meeting-success-box {
    margin-top: 0.75rem;
    padding: 0.75rem;
    border-radius: 10px;
    border: 1px solid rgba(34, 197, 94, 0.35);
    background: rgba(34, 197, 94, 0.08);
    display: none;
    font-size: 0.82rem;
    line-height: 1.5;
  }
  .meeting-success-box a {
    color: #93c5fd;
    word-break: break-all;
  }
</style>
</head>
<body>

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes Actions</div>
    <div class="topbar-right">
      <button class="theme-toggle tb-btn" id="privacy-toggle" onclick="togglePrivacy()" title="Mode Incognito (Cacher les données)" style="background:transparent; border:1px solid rgba(148,163,184,.2); color:var(--muted); width:36px; height:36px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.2s; padding:0; margin-right: .5rem;">
        <svg id="privacy-icon-off" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        <svg id="privacy-icon-on" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
      </button>
      <button id="theme-toggle" class="btn-ghost" style="margin-right:.5rem;display:flex;align-items:center;gap:.4rem;padding:.4rem .7rem;">
        <svg id="sun-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg id="moon-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        <span id="theme-text">Mode sombre</span>
      </button>
    </div>
  </div>

  <div class="content">
    <!-- TABS -->
    <div class="tab-row">
      <div class="tab-pill" data-tab="ai-recommendations" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.15) 100%); color: #e879f9; border: 1px solid rgba(168, 85, 247, 0.3);">✨ Recommandé pour vous</div>
      <div class="tab-pill active" data-tab="projets">Projets disponibles</div>
      <div class="tab-pill" data-tab="invest">Mes investissements</div>
    </div>

    <!-- ===== TAB: PROJETS DISPONIBLES ===== -->
    <div class="tab-panel active" id="panel-projets">
      <div class="section-head" style="margin-bottom:.9rem;display:flex;justify-content:space-between;align-items:center;">
        <div class="section-title">Projets ouverts à l'investissement</div>
        <select class="btn-ghost" id="sector-filter" style="padding:.4rem .8rem;background:rgba(255,255,255,.05);color:rgba(148,163,184,1);border:1px solid rgba(148,163,184,.2);border-radius:6px;outline:none;font-family:inherit;font-size:.85rem;cursor:pointer;">
          <option value="">Tous les secteurs</option>
          <option value="Tech">Tech</option>
          <option value="Immobilier">Immobilier</option>
          <option value="Agriculture">Agriculture</option>
          <option value="Santé">Santé</option>
          <option value="Énergie">Énergie</option>
          <option value="Finance">Finance</option>
          <option value="Autre">Autre</option>
        </select>
      </div>
      <div class="projects-grid" id="project-list" style="min-height:260px;">
        <div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(148,163,184,1);padding:1.3rem;">
          Chargement des projets depuis la base de données...
        </div>
      </div>
    </div>

    <!-- ===== TAB: AI RECOMMENDATIONS ===== -->
    <div class="tab-panel" id="panel-ai-recommendations">
      <div class="section-head" style="margin-bottom:.9rem;display:flex;justify-content:space-between;align-items:center;">
        <div class="section-title" style="display:flex;align-items:center;gap:.5rem;">
          <span style="font-size:1.4rem;">✨</span> 
          <span style="background: linear-gradient(90deg, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sélectionnés pour vous</span>
        </div>
      </div>
      <div style="background:rgba(139, 92, 246, 0.05);border:1px solid rgba(139, 92, 246, 0.2);padding:1rem;border-radius:12px;color:rgba(167, 139, 250, 0.9);font-size:.85rem;margin-bottom:1.5rem;">
        Basé sur vos secteurs préférés et les projets les plus rentables du moment.
      </div>
      <div class="projects-grid" id="ai-project-list" style="min-height:260px;">
        <div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(148,163,184,1);padding:1.3rem;">
          Analyse de vos préférences en cours...
        </div>
      </div>
    </div>

    <!-- ===== TAB: MES INVESTISSEMENTS ===== -->
    <div class="tab-panel" id="panel-invest">
      <div class="invest-tab-layout">
        <div>
          <div class="section-head" style="margin-bottom:.9rem">
            <div class="section-title">Mes investissements</div>
            <button class="btn-ghost">Voir tout</button>
          </div>
          <div class="invest-list">
            <div class="invest-item">
              <div class="invest-icon" style="background:rgba(245,158,11,.1)"><svg width="16" height="16" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
              <div class="invest-info"><div class="invest-title">Solar Farm Sfax <span class="invest-tag">Énergie</span></div><div class="invest-meta">Investi le 02 avr. 2025</div></div>
              <div class="invest-right"><div class="invest-amt privacy-blur">25 000 TND</div><div class="invest-status st-valide">✓ Validé</div></div>
            </div>
            
          </div>
        </div>
      </div>
    </div>


  </div><!-- /content -->
</div><!-- /main -->

<div id="invest-modal" class="modal-overlay" aria-hidden="true">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <div class="modal-title">Investir dans un projet</div>
        <div style="font-size:.9rem;color:rgba(148,163,184,1);">Complétez le montant et confirmez.</div>
      </div>
      <button type="button" id="modal-close-btn" class="modal-close" aria-label="Fermer">×</button>
    </div>
    <form id="invest-form" autocomplete="off">
      <input type="hidden" id="investment-action" name="action" value="submit_investment" />
      <input type="hidden" id="investment-id" name="investment_id" value="" />
      <input type="hidden" name="status" value="EN_ATTENTE" />
      <div class="modal-row">
        <div class="form-row">
          <div class="form-label">Projet sélectionné</div>
          <select id="investment-project-select" class="form-input" name="project_id">
            <option value="">— Sélectionner un projet —</option>
          </select>
        </div>
      </div>
      <div class="modal-row" id="investment-desc-container" style="display:none">
        <div class="form-row">
          <div class="form-label">Détails du projet</div>
          <div id="investment-description" style="font-size:.82rem;color:rgba(148,163,184,1);line-height:1.5;background:rgba(255,255,255,.03);padding:.8rem;border-radius:12px;border:1px solid rgba(148,163,184,.1);white-space:pre-wrap;"></div>
        </div>
      </div>
      <div class="modal-row">
        <div class="form-label">Montant rapide</div>
        <div class="form-amounts" id="quick-amounts">
          <div class="amount-chip" data-value="500">500</div>
          <div class="amount-chip" data-value="1000">1 000</div>
          <div class="amount-chip" data-value="5000">5 000</div>
          <div class="amount-chip" data-value="10000">10 000</div>
        </div>
      </div>
      <div class="modal-row">
        <div class="form-row">
          <div class="form-label">Montant personnalisé (TND)</div>
          <input id="investment-amount" class="form-input" type="number" name="montant" placeholder="Ex: 2 500" />
          <div class="form-hint">Min: 500 TND · Max: 100 000 TND</div>
        </div>
      </div>
      <div class="modal-row">
        <div class="form-row">
          <div class="form-label">Financer depuis</div>
          <select class="form-input" id="invest-account-select" name="financement_source" style="cursor:pointer">
            <?php if (empty($_action_user_accounts)): ?>
              <option value="">Aucun compte actif disponible</option>
            <?php else: ?>
              <?php foreach ($_action_user_accounts as $_acc): ?>
                <option value="<?= (int)$_acc['id_compte'] ?>"
                  data-solde="<?= htmlspecialchars(number_format((float)$_acc['solde'], 3, ',', ' ')) ?>"
                  data-devise="<?= htmlspecialchars($_acc['devise'] ?? 'TND') ?>">
                  <?= htmlspecialchars(ucfirst(strtolower($_acc['type_compte'] ?? ''))) ?>
                  — <?= htmlspecialchars(substr($_acc['iban'], 0, 4) . ' ' . substr($_acc['iban'], 4, 4) . '…' . substr($_acc['iban'], -4)) ?>
                  — <?= htmlspecialchars(number_format((float)$_acc['solde'], 3, ',', ' ')) ?> <?= htmlspecialchars($_acc['devise'] ?? 'TND') ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
          <div id="invest-account-balance" style="font-size:.75rem;color:rgba(148,163,184,1);margin-top:.4rem;"></div>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="modal-cancel-btn">Annuler</button>
        <button type="submit" class="form-submit">Confirmer l'investissement</button>
      </div>
      <div id="modal-error" class="modal-error"></div>
      <div id="modal-success" class="modal-success"></div>
    </form>
  </div>
</div>

<script>
console.log('[frontoffice] script loaded');
document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.tab-pill');
  const panels = document.querySelectorAll('.tab-panel');
  const form = document.getElementById('demande-form');
  const successBanner = document.getElementById('success-banner');
  const successRef = document.getElementById('success-ref');
  const formError = document.getElementById('form-error');
  const requestsList = document.getElementById('requests-list');
  const reqCount = document.getElementById('req-count');
  const submitButton = document.getElementById('btn-submit-demande');
  const updateButton = document.getElementById('btn-update-demande');
  const cancelButton = document.getElementById('btn-cancel-edit');
  const actionInput = form ? form.querySelector('input[name="action"]') : null;
  const projectIdInput = form ? form.querySelector('input[name="project_id"]') : null;
  const statusInput = form ? form.querySelector('input[name="status"]') : null;
  const controllerUrl = new URL('../../controller/projet.php', window.location.href).href;
  const investmentControllerUrl = new URL('../../controller/investissement.php', window.location.href).href;
  const projectsGrid = document.getElementById('project-list');
  const investModal = document.getElementById('invest-modal');
  const investForm = document.getElementById('invest-form');
  const investmentProjectSelect = document.getElementById('investment-project-select');
  const investmentAmountInput = document.getElementById('investment-amount');
  const investmentActionInput = document.getElementById('investment-action');
  const investmentIdInput = document.getElementById('investment-id');
  const modalError = document.getElementById('modal-error');
  const modalSuccess = document.getElementById('modal-success');
  const quickAmounts = document.getElementById('quick-amounts');
  const modalCloseBtn = document.getElementById('modal-close-btn');
  const modalCancelBtn = document.getElementById('modal-cancel-btn');
  const investmentDescription = document.getElementById('investment-description');
  const investmentDescContainer = document.getElementById('investment-desc-container');
  const investFallbackButton = document.querySelector('#panel-invest .invest-form-card .form-submit');
  if (form) {
    form.action = controllerUrl;
  }

  if (projectsGrid) {
    console.log('[frontoffice] attached project click listener');
    projectsGrid.addEventListener('click', (event) => {
      const button = event.target.closest('.btn-invest');
      if (!button) return;
      event.stopPropagation();
      const projectId = button.dataset.projectId || button.closest('.project-card')?.dataset.projectId;
      console.log('[frontoffice] invest button clicked', projectId);
      if (projectId) {
        openInvestmentModal(Number(projectId));
      }
    });
  }

  const aiProjectList = document.getElementById('ai-project-list');
  if (aiProjectList) {
    aiProjectList.addEventListener('click', (event) => {
      const button = event.target.closest('.btn-invest');
      if (!button) return;
      event.stopPropagation();
      const projectId = button.dataset.projectId || button.closest('.project-card')?.dataset.projectId;
      console.log('[frontoffice] invest button clicked', projectId);
      if (projectId) {
        openInvestmentModal(Number(projectId));
      }
    });
  }

  if (investFallbackButton) {
    investFallbackButton.addEventListener('click', (event) => {
      event.preventDefault();
      openInvestmentModal();
    });
  }

  let availableProjects = [];
  let selectedProjectId = null;
  let editProjectId = null;
  let editInvestmentId = null;

  const activateTab = (tabName) => {
    tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === tabName));
    panels.forEach(panel => panel.classList.toggle('active', panel.id === `panel-${tabName}`));
    if (tabName === 'projets') {
      loadAvailableProjects();
    }
    if (tabName === 'invest') {
      loadUserInvestments();
    }
    if (tabName === 'ai-recommendations') {
      loadRecommendedProjects();
    }
  };

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      activateTab(tab.dataset.tab);
    });
  });

  const setEditMode = (project) => {
    if (!form || !actionInput || !projectIdInput) return;
    editProjectId = project.id_projet;
    actionInput.value = 'update_project';
    projectIdInput.value = project.id_projet;
    document.getElementById('f-titre').value = project.titre;
    document.getElementById('f-secteur').value = project.secteur;
    document.getElementById('f-desc').value = project.description;
    document.getElementById('f-montant').value = project.montant_objectif;
    document.getElementById('f-date').value = project.date_limite;
    statusInput.value = project.status || 'EN_ATTENTE';
    submitButton.style.display = 'none';
    updateButton.style.display = 'inline-flex';
    cancelButton.style.display = 'inline-flex';
    if (successBanner) successBanner.style.display = 'none';
  };

  const resetFormMode = () => {
    if (!form || !actionInput || !projectIdInput) return;
    editProjectId = null;
    actionInput.value = 'submit_demande';
    projectIdInput.value = '';
    statusInput.value = 'EN_ATTENTE';
    submitButton.style.display = 'inline-flex';
    updateButton.style.display = 'none';
    cancelButton.style.display = 'none';
    form.reset();
  };

  const populateInvestmentProjectSelect = () => {
    if (!investmentProjectSelect) return;
    investmentProjectSelect.innerHTML = '<option value="">— Sélectionner un projet —</option>';
    availableProjects.forEach(project => {
      const option = document.createElement('option');
      option.value = project.id_projet;
      option.textContent = `${project.titre} — ${project.secteur || 'Autre'}`;
      investmentProjectSelect.appendChild(option);
    });
    if (selectedProjectId) {
      investmentProjectSelect.value = selectedProjectId;
    }
  };

   const openInvestmentModal = (projectId = null, investment = null) => {
    console.log('[frontoffice] openInvestmentModal', projectId, investment, investModal, investmentProjectSelect, investmentAmountInput);
    if (!investModal || !investmentProjectSelect || !investmentAmountInput) return;
    selectedProjectId = projectId;
    populateInvestmentProjectSelect();
    
    if (investment) {
      editInvestmentId = investment.id_investissement;
      investmentActionInput.value = 'update_investment';
      investmentIdInput.value = editInvestmentId;
      investmentProjectSelect.value = investment.id_projet;
      investmentAmountInput.value = investment.montant_investi;
      if (modalSuccess) {
        modalSuccess.textContent = 'Mode modification activé';
        modalSuccess.style.display = 'block';
      }
    } else {
      editInvestmentId = null;
      investmentActionInput.value = 'submit_investment';
      investmentIdInput.value = '';
      if (projectId) {
        investmentProjectSelect.value = projectId;
      } else {
        investmentProjectSelect.value = '';
      }
      investmentAmountInput.value = '';
      if (modalSuccess) {
        modalSuccess.style.display = 'none';
        modalSuccess.textContent = '';
      }
    }

    // Update description in modal
    updateModalDescription(investmentProjectSelect.value);

    if (modalError) {
      modalError.style.display = 'none';
      modalError.textContent = '';
    }
    investModal.classList.add('active');
    investModal.setAttribute('aria-hidden', 'false');
  };

  const updateModalDescription = (projectId) => {
    if (!investmentDescription || !investmentDescContainer) return;
    const project = availableProjects.find(p => p.id_projet == projectId);
    if (project && project.description) {
      investmentDescription.textContent = project.description;
      investmentDescContainer.style.display = 'block';
    } else {
      investmentDescription.textContent = '';
      investmentDescContainer.style.display = 'none';
    }
  };

  if (investmentProjectSelect) {
    investmentProjectSelect.addEventListener('change', (e) => {
      updateModalDescription(e.target.value);
    });
  }

  const closeInvestmentModal = () => {
    if (!investModal) return;
    investModal.classList.remove('active');
    investModal.setAttribute('aria-hidden', 'true');
    if (investForm) {
      investForm.reset();
    }
    selectedProjectId = null;
    if (investmentProjectSelect) {
      investmentProjectSelect.value = '';
    }
  };

  if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeInvestmentModal);
  if (modalCancelBtn) modalCancelBtn.addEventListener('click', closeInvestmentModal);
  if (investModal) {
    investModal.addEventListener('click', (event) => {
      if (event.target === investModal) {
        closeInvestmentModal();
      }
    });
  }

  if (quickAmounts) {
    quickAmounts.addEventListener('click', (event) => {
      const chip = event.target.closest('.amount-chip');
      if (!chip) return;
      const value = chip.dataset.value;
      if (!investmentAmountInput || !value) return;
      investmentAmountInput.value = value;
      quickAmounts.querySelectorAll('.amount-chip').forEach(node => node.classList.remove('selected'));
      chip.classList.add('selected');
    });
  }

  // Account balance display
  const investAccountSelect = document.getElementById('invest-account-select');
  const investAccountBalance = document.getElementById('invest-account-balance');
  const updateAccountBalanceDisplay = () => {
    if (!investAccountSelect || !investAccountBalance) return;
    const selected = investAccountSelect.options[investAccountSelect.selectedIndex];
    if (!selected || !selected.dataset.solde) {
      investAccountBalance.textContent = '';
      return;
    }
    const solde = selected.dataset.solde;
    const devise = selected.dataset.devise || 'TND';
    investAccountBalance.innerHTML = `💰 Solde disponible : <strong style="color:#2DD4BF">${solde} ${devise}</strong>`;
  };
  if (investAccountSelect) {
    investAccountSelect.addEventListener('change', updateAccountBalanceDisplay);
    updateAccountBalanceDisplay(); // run on load
  }

  const renderRequests = (requests) => {
    requestsList.innerHTML = '';
    if (reqCount) {
      reqCount.textContent = requests.length;
    }

    if (requests.length === 0) {
      requestsList.innerHTML = `
        <div class="request-empty" style="padding:1rem;border:1px solid rgba(148,163,184,.2);border-radius:12px;text-align:center;color:rgba(148,163,184,1);">
          <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="display:block;margin:0 auto .6rem"><path d="M9 12h6M9 16h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
          Aucune demande soumise pour le moment.
        </div>
      `;
      return;
    }

    requests.forEach(project => {
      const item = document.createElement('div');
      item.className = 'request-card';
      item.style = 'background:rgba(15,23,42,.9);border:1px solid rgba(148,163,184,.12);border-radius:16px;padding:1rem;cursor:pointer;';
      item.innerHTML = `
        <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:start;">
          <div>
            <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.35rem;">
              <div style="font-weight:700;color:#fff;">${project.titre}</div>
              <div style="padding:.2rem .6rem;border-radius:999px;background:rgba(59,130,246,.15);color:#93c5fd;font-size:.75rem;">${project.request_code || 'REQ-000'}</div>
            </div>
            <div style="font-size:.85rem;color:rgba(148,163,184,1);margin-bottom:.65rem;">${project.description}</div>
            <div style="display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:.6rem;font-size:.8rem;color:rgba(148,163,184,1);margin-bottom:.5rem;">
              <span>Objectif: <span class="privacy-blur">${project.montant_objectif} TND</span></span>
              <span>Collecté: <span class="privacy-blur">${project.total_investi || 0} TND</span></span>
              <span>TRI: ${project.taux_rentabilite || 0}%</span>
              <span>Retour: ${project.temps_retour_brut || 0} mois</span>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:rgba(148,163,184,1);">
              <div style="flex:1;height:6px;background:rgba(255,255,255,.05);border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:${project.progression || 0}%;background:var(--blue);"></div>
              </div>
              <span style="min-width:35px">${project.progression || 0}%</span>
            </div>
          </div>
          <div style="text-align:right;min-width:115px;">
            <div style="font-size:.85rem;color:rgba(148,163,184,1);margin-bottom:.45rem;">Status</div>
            <div style="font-weight:700;color:#fff;">${project.status}</div>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;flex-wrap:wrap;">
          <button type="button" class="btn-ghost btn-edit" data-id="${project.id_projet}" style="padding:.65rem .9rem;">Modifier</button>
          <button type="button" class="btn-ghost btn-delete" data-id="${project.id_projet}" style="padding:.65rem .9rem;">Supprimer</button>
        </div>
      `;
      item.addEventListener('click', (event) => {
        if (!event.target.closest('button')) {
          loadProject(project.id_projet);
        }
      });
      requestsList.appendChild(item);
    });

    requestsList.querySelectorAll('.btn-edit').forEach(button => {
      button.addEventListener('click', () => {
        loadProject(button.dataset.id);
      });
    });

    requestsList.querySelectorAll('.btn-delete').forEach(button => {
      button.addEventListener('click', () => {
        deleteProject(button.dataset.id);
      });
    });
  };

  const renderProjects = (projects, container = projectsGrid) => {
    if (!container) return;
    container.innerHTML = '';
    if (projects.length === 0) {
      container.innerHTML = `
        <div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(148,163,184,1);padding:1.3rem;">
          Aucun projet disponible actuellement.
        </div>
      `;
      return;
    }

    projects.forEach(project => {
      const remaining = project.montant_restant || project.montant_objectif;
      const progress = project.progression || 0;
      const card = document.createElement('div');
      card.className = 'project-card featured';
      card.dataset.projectId = project.id_projet;
      card.innerHTML = `

        
        <div style="flex: 2; min-width: 200px;">
          <div class="pc-sector pc-s-energie" style="margin-bottom: 0.4rem;"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>${project.secteur || 'Autre'}</div>
          <div class="pc-title" style="margin-bottom: 0;">${project.titre}</div>
          <div class="pc-deadline" style="margin-top: 0.3rem;">Échéance: ${project.date_limite}</div>
        </div>

        <div style="flex: 1; display: flex; flex-direction: column; gap: 0.3rem; min-width: 100px;">
          <div style="font-size: 0.82rem; color: var(--muted);"><strong style="color:var(--text);">TRI:</strong> ${project.taux_rentabilite || 0}%</div>
          <div style="font-size: 0.82rem; color: var(--muted);"><strong style="color:var(--text);">Retour:</strong> ${project.temps_retour_brut || 0} mois</div>
        </div>

        <div style="flex: 2; min-width: 180px;">
          <div class="pc-progress-info" style="margin-bottom: 0.4rem;">
            <span style="font-size: 0.75rem;"><span class="privacy-blur">${remaining.toLocaleString()} TND</span> restant</span>
            <span style="color:var(--green);font-weight:600;font-size:0.75rem;">${progress}%</span>
          </div>
          <div class="pc-progress-bar" style="margin-bottom: 0;"><div class="pc-progress-fill" style="width:${progress}%;background:var(--green)"></div></div>
        </div>

        <div style="flex-shrink: 0; padding-left: 1rem;">
          <button class="btn-invest" type="button" data-project-id="${project.id_projet}">Investir</button>
        </div>
      `;
      container.appendChild(card);
    });
  };

  const loadAvailableProjects = async () => {
    if (!projectsGrid) return;
    projectsGrid.innerHTML = '<div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(148,163,184,1);padding:1.3rem;">Chargement des projets...</div>';
    try {
      const response = await fetch(`${controllerUrl}?action=list_available_projects`);
      const result = await response.json();
      if (result.success) {
        availableProjects = result.data || [];
        populateInvestmentProjectSelect();
        renderProjects(availableProjects);
      } else {
        projectsGrid.innerHTML = `<div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(239,68,68,1);padding:1.3rem;">${result.message}</div>`;
      }
    } catch (error) {
      projectsGrid.innerHTML = `<div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(239,68,68,1);padding:1.3rem;">Erreur de chargement.</div>`;
    }
  };

  const loadRecommendedProjects = async () => {
    const aiGrid = document.getElementById('ai-project-list');
    if (!aiGrid) return;
    aiGrid.innerHTML = '<div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(148,163,184,1);padding:1.3rem;">Analyse de vos préférences en cours...</div>';
    try {
      const response = await fetch(`${controllerUrl}?action=list_recommendations`);
      const result = await response.json();
      if (result.success) {
        // Also update availableProjects to ensure the investment modal works correctly
        // We merge with existing available projects to avoid duplication issues in select
        result.data.forEach(rp => {
          if (!availableProjects.find(ap => ap.id_projet === rp.id_projet)) {
            availableProjects.push(rp);
          }
        });
        populateInvestmentProjectSelect();
        renderProjects(result.data, aiGrid);
      } else {
        aiGrid.innerHTML = `<div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(239,68,68,1);padding:1.3rem;">${result.message}</div>`;
      }
    } catch (error) {
      aiGrid.innerHTML = `<div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(239,68,68,1);padding:1.3rem;">Erreur de chargement.</div>`;
    }
  };

  const sectorFilter = document.getElementById('sector-filter');
  if (sectorFilter) {
    sectorFilter.addEventListener('change', (e) => {
      const selectedSector = e.target.value;
      if (!selectedSector) {
        renderProjects(availableProjects);
      } else {
        const filtered = availableProjects.filter(p => p.secteur === selectedSector);
        renderProjects(filtered);
      }
    });
  }

  const loadRequests = async () => {
    if (!requestsList) return;
    requestsList.innerHTML = '<div style="padding:1rem;color:rgba(148,163,184,1)">Chargement...</div>';
    try {
      const response = await fetch(`${controllerUrl}?action=list_requests`);
      const result = await response.json();
      if (result.success) {
        renderRequests(result.data);
      } else {
        requestsList.innerHTML = `<div style="color:var(--red);">${result.message}</div>`;
      }
    } catch (error) {
      requestsList.innerHTML = `<div style="color:var(--red);">Erreur de chargement.</div>`;
    }
  };

  const investmentsList = document.querySelector('.invest-list');

  const renderInvestments = (investments) => {
    if (!investmentsList) return;
    investmentsList.innerHTML = '';
    if (!investments || investments.length === 0) {
      investmentsList.innerHTML = `<div class="invest-item" style="padding:1rem;border:1px solid rgba(148,163,184,.18);border-radius:16px;color:rgba(148,163,184,1);">Aucun investissement enregistré pour le moment.</div>`;
      return;
    }

    investments.forEach(investment => {
      const progressValue = Number(investment.progress_pourcentage || 0);
      const progressDescription = investment.progress_description || 'Aucune description de progression.';
      const progressDate = investment.progress_date_update
        ? new Date(investment.progress_date_update).toLocaleString('fr-FR')
        : 'Non renseignée';
      const item = document.createElement('div');
      item.className = 'invest-item';
      item.innerHTML = `
        <div class="invest-icon" style="background:rgba(99,102,241,.12)"><svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20v-6m0 0V4m0 10l-6-6m6 6l6-6"/></svg></div>
        <div class="invest-info">
          <div class="invest-title">${investment.projet_titre || 'Projet'} <span class="invest-tag">${investment.status}</span></div>
          <div class="invest-meta">Investi le ${investment.date_investissement}</div>
          <div style="margin-top:.45rem;font-size:.78rem;color:rgba(148,163,184,1);">Progression projet: <strong style="color:#f8fafc;">${progressValue}%</strong></div>
          <div style="margin-top:.35rem;height:6px;background:rgba(148,163,184,.2);border-radius:999px;overflow:hidden;">
            <div style="height:100%;width:${Math.min(100, Math.max(0, progressValue))}%;background:linear-gradient(90deg,#22c55e,#16a34a);"></div>
          </div>
          <div style="margin-top:.45rem;font-size:.74rem;color:rgba(148,163,184,1);line-height:1.4;">${progressDescription}</div>
          <div style="margin-top:.25rem;font-size:.72rem;color:rgba(100,116,139,1);">Maj: ${progressDate}</div>
        </div>
        <div class="invest-right"><div class="invest-amt privacy-blur">${investment.montant_investi} TND</div><div class="invest-status ${investment.status === 'VALIDE' ? 'st-valide' : investment.status === 'REFUSE' ? 'st-refuse' : 'st-attente'}">${investment.status}</div></div>
        <div class="invest-actions" style="display:flex;gap:.5rem;margin-left:1rem;align-items:center;">
          <button type="button" class="btn-ghost btn-edit-investment" data-id="${investment.id_investissement}" style="padding:.55rem .8rem;">Modifier</button>
          <button type="button" class="btn-ghost btn-delete-investment" data-id="${investment.id_investissement}" style="padding:.55rem .8rem;">Supprimer</button>
        </div>
      `;
      investmentsList.appendChild(item);
    });

    investmentsList.querySelectorAll('.btn-edit-investment').forEach(button => {
      button.addEventListener('click', () => {
        openInvestmentForEdit(button.dataset.id);
      });
    });

    investmentsList.querySelectorAll('.btn-delete-investment').forEach(button => {
      button.addEventListener('click', () => {
        deleteInvestment(button.dataset.id);
      });
    });
  };

  const openInvestmentForEdit = async (investmentId) => {
    try {
      const response = await fetch(`${investmentControllerUrl}?action=get_investment&id=${encodeURIComponent(investmentId)}`);
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'Impossible de charger l\'investissement.');
      }
      openInvestmentModal(result.data.id_projet, result.data);
    } catch (error) {
      if (modalError) {
        modalError.textContent = error.message;
        modalError.style.display = 'block';
      }
    }
  };

  const deleteInvestment = async (investmentId) => {
    if (!confirm('Supprimer cet investissement ?')) return;
    try {
      const formData = new FormData();
      formData.append('action', 'delete_investment');
      formData.append('investment_id', investmentId);
      const response = await fetch(investmentControllerUrl, { method: 'POST', body: formData });
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'Impossible de supprimer l\'investissement.');
      }
      await loadUserInvestments();
    } catch (error) {
      if (modalError) {
        modalError.textContent = error.message;
        modalError.style.display = 'block';
      }
    }
  };

  const loadUserInvestments = async () => {
    if (!investmentsList) return;
    investmentsList.innerHTML = '<div style="padding:1rem;color:rgba(148,163,184,1)">Chargement des investissements...</div>';
    try {
      const response = await fetch(`${investmentControllerUrl}?action=list_investments`);
      const result = await response.json();
      if (result.success) {
        renderInvestments(result.data);
      } else {
        investmentsList.innerHTML = `<div style="color:var(--red);">${result.message}</div>`;
      }
    } catch (error) {
      investmentsList.innerHTML = `<div style="color:var(--red);">Erreur de chargement.</div>`;
    }
  };

  const loadProject = async (id) => {
    try {
      const response = await fetch(`${controllerUrl}?action=get_project&id=${encodeURIComponent(id)}`);
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'Impossible de charger le projet.');
      }
      setEditMode(result.data);
      activateTab('add');
    } catch (error) {
      formError.textContent = error.message;
      formError.style.display = 'block';
    }
  };

  const deleteProject = async (id) => {
    if (!confirm('Supprimer cette demande ?')) return;
    try {
      const formData = new FormData();
      formData.append('action', 'delete_project');
      formData.append('project_id', id);
      const response = await fetch(controllerUrl, { method: 'POST', body: formData });
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'Impossible de supprimer le projet.');
      }
      await loadRequests();
      if (editProjectId === Number(id)) {
        resetFormMode();
      }
      if (successBanner) {
        successBanner.style.display = 'flex';
      }
      if (successRef) {
        successRef.textContent = result.message;
      }
    } catch (error) {
      formError.textContent = error.message;
      formError.style.display = 'block';
    }
  };

  if (cancelButton) {
    cancelButton.addEventListener('click', () => {
      resetFormMode();
      if (formError) {
        formError.style.display = 'none';
      }
    });
  }

  if (updateButton) {
    updateButton.addEventListener('click', async (event) => {
      event.preventDefault();
      if (!form) return;
      const formData = new FormData(form);
      try {
        const response = await fetch(controllerUrl, { method: 'POST', body: formData });
        const result = await response.json();
        if (!result.success) {
          throw new Error(result.message || 'Erreur de mise à jour.');
        }
        if (successBanner) {
          successBanner.style.display = 'flex';
        }
        if (successRef) {
          successRef.textContent = result.message;
        }
        resetFormMode();
        await loadRequests();
      } catch (error) {
        formError.textContent = error.message;
        formError.style.display = 'block';
      }
    });
  }

  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      formError.style.display = 'none';
      formError.textContent = '';
      const formData = new FormData(form);
      try {
        const response = await fetch(controllerUrl, {
          method: 'POST',
          body: formData,
        });
        if (!response.ok) {
          throw new Error(`Erreur de requête (${response.status})`);
        }
        const result = await response.json();
        if (result.success) {
          if (successBanner) {
            successBanner.style.display = 'flex';
          }
          if (successRef) {
            successRef.textContent = result.reference || result.message || 'Opération réussie.';
          }
          resetFormMode();
          loadRequests();
          return;
        }
        throw new Error(result.message || 'Une erreur est survenue.');
      } catch (error) {
        formError.textContent = error.message;
        formError.style.display = 'block';
      }
    });
  }

  if (investForm) {
    investForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (modalError) {
        modalError.style.display = 'none';
        modalError.textContent = '';
      }
      if (modalSuccess) {
        modalSuccess.style.display = 'none';
        modalSuccess.textContent = '';
      }
      const formData = new FormData(investForm);
      try {
        const response = await fetch(investmentControllerUrl, {
          method: 'POST',
          body: formData,
        });
        const result = await response.json();
        if (!result.success) {
          throw new Error(result.message || 'Impossible d\'effectuer l\'investissement.');
        }
        if (modalSuccess) {
          modalSuccess.textContent = result.message || 'Investissement enregistré.';
          modalSuccess.style.display = 'block';
        }
        loadUserInvestments();
        setTimeout(() => {
          closeInvestmentModal();
        }, 900);
      } catch (error) {
        if (modalError) {
          modalError.textContent = error.message;
          modalError.style.display = 'block';
        }
      }
    });
  }

  loadRequests();
  const initialTab = document.querySelector('.tab-pill.active')?.dataset.tab;
  if (initialTab === 'projets') {
    loadAvailableProjects();
  } else if (initialTab === 'invest') {
    loadUserInvestments();
  }
  
  // Theme Toggle Logic
  const themeToggle = document.getElementById('theme-toggle');
  const themeText = document.getElementById('theme-text');
  const sunIcon = document.getElementById('sun-icon');
  const moonIcon = document.getElementById('moon-icon');

  const setTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    if (theme === 'light') {
      themeText.textContent = 'Mode clair';
      sunIcon.style.display = 'block';
      moonIcon.style.display = 'none';
    } else {
      themeText.textContent = 'Mode sombre';
      sunIcon.style.display = 'none';
      moonIcon.style.display = 'block';
    }
  };

  const savedTheme = localStorage.getItem('theme') || 'dark';
  setTheme(savedTheme);

    if (themeToggle) {
      themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        setTheme(currentTheme === 'dark' ? 'light' : 'dark');
      });
    }

    const applyPrivacy = (mode) => {
      document.documentElement.setAttribute('data-privacy', mode);
      localStorage.setItem('privacy_mode', mode);
      const iconOff = document.getElementById('privacy-icon-off');
      const iconOn = document.getElementById('privacy-icon-on');
      if (mode === 'hidden') {
        if (iconOff) iconOff.style.display = 'none';
        if (iconOn) iconOn.style.display = 'block';
      } else {
        if (iconOff) iconOff.style.display = 'block';
        if (iconOn) iconOn.style.display = 'none';
      }
    };

    window.togglePrivacy = () => {
      const current = document.documentElement.getAttribute('data-privacy') || 'visible';
      applyPrivacy(current === 'visible' ? 'hidden' : 'visible');
    };

    const savedPrivacy = localStorage.getItem('privacy_mode') || 'visible';
    applyPrivacy(savedPrivacy);
  });
</script>

</body>
</html>

