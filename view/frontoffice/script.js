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
  const controllerUrl = new URL('../../controlller/projet.php', window.location.href).href;
  const investmentControllerUrl = new URL('../../controlller/investissement.php', window.location.href).href;
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
    if (modalError) {
      modalError.style.display = 'none';
      modalError.textContent = '';
    }
    investModal.classList.add('active');
    investModal.setAttribute('aria-hidden', 'false');
  };

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
            <div style="display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:.6rem;font-size:.8rem;color:rgba(148,163,184,1);">
              <span>Montant: ${project.montant_objectif} TND</span>
              <span>Date limite: ${project.date_limite}</span>
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

  const renderProjects = (projects) => {
    if (!projectsGrid) return;
    projectsGrid.innerHTML = '';
    if (projects.length === 0) {
      projectsGrid.innerHTML = `
        <div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(148,163,184,1);padding:1.3rem;">
          Aucun projet disponible actuellement.
        </div>
      `;
      return;
    }

    projects.forEach(project => {
      const totalInvested = project.total_investi || 0;
      const remaining = project.montant_restant || project.montant_objectif;
      const progress = project.progression || 0;
      const card = document.createElement('div');
      card.className = 'project-card featured';
      card.dataset.projectId = project.id_projet;
      card.innerHTML = `
        <div class="pc-badge-featured">${project.status === 'EN_ATTENTE' ? 'Nouvelle' : 'Projet'}</div>
        <div class="pc-sector pc-s-energie"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>${project.secteur || 'Autre'}</div>
        <div class="pc-title">${project.titre}</div>
        <div class="pc-desc">${project.description}</div>
        <div class="pc-progress-bar"><div class="pc-progress-fill" style="width:${progress}%;background:var(--green)"></div></div>
        <div class="pc-progress-info"><span><span class="pc-progress-amt">${remaining.toLocaleString()} TND restant</span> / ${project.montant_objectif.toLocaleString()} TND</span><span style="color:var(--green);font-weight:600">${progress}% financé</span></div>
        <div class="pc-footer">
          <div><div class="pc-investors"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div><div class="pc-deadline">Échéance: ${project.date_limite}</div></div>
          <button class="btn-invest" type="button" data-project-id="${project.id_projet}">Investir</button>
        </div>
      `;
      projectsGrid.appendChild(card);
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
      const item = document.createElement('div');
      item.className = 'invest-item';
      item.innerHTML = `
        <div class="invest-icon" style="background:rgba(99,102,241,.12)"><svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20v-6m0 0V4m0 10l-6-6m6 6l6-6"/></svg></div>
        <div class="invest-info"><div class="invest-title">${investment.projet_titre || 'Projet'} <span class="invest-tag">${investment.status}</span></div><div class="invest-meta">Investi le ${investment.date_investissement}</div></div>
        <div class="invest-right"><div class="invest-amt">${investment.montant_investi} TND</div><div class="invest-status ${investment.status === 'VALIDE' ? 'st-valide' : investment.status === 'REFUSE' ? 'st-refuse' : 'st-attente'}">${investment.status}</div></div>
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
});
