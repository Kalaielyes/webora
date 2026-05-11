let _nmrCounter = 1000;
function openChequeModal(numChequier, titulaire, rib, idChequier, editId = null) {
  _nmrCounter++;
  const now  = new Date();
  const year = now.getFullYear();
  const chkId  = year + '-' + String(Math.floor(Math.random() * 99999) + 1).padStart(5, '0');
  const chkNmr = year + '-' + String(_nmrCounter).padStart(6, '0');

  const chkIdEl = document.getElementById('chkId');
  const chkNmrEl = document.getElementById('chkNmr');
  if (chkIdEl) chkIdEl.value = chkId;
  if (chkNmrEl) chkNmrEl.value = chkNmr;

  const chkDateEl = document.getElementById('chkDate');
  const chkMontantEl = document.getElementById('chkMontant');
  const chkLettresEl = document.getElementById('chkLettres');
  const chkBenefEl = document.getElementById('chkBenef');
  const chkCinEl = document.getElementById('chkCin');
  const chkRibEl = document.getElementById('chkRib');
  const chkAgenceEl = document.getElementById('chkAgence');

  // Reset par défaut
  if (chkDateEl) chkDateEl.value = now.toISOString().split('T')[0];
  if (chkMontantEl) chkMontantEl.value = '';
  if (chkLettresEl) chkLettresEl.value = '';
  if (chkBenefEl) chkBenefEl.value = '';
  if (chkCinEl) chkCinEl.value = '';
  if (chkRibEl) chkRibEl.value = '';
  if (chkAgenceEl) chkAgenceEl.value = '';

  const modalChequierId = document.getElementById('modalChequierId');
  const previewId = document.getElementById('previewId');
  const previewNmr = document.getElementById('previewNmr');
  const previewMicrNmr = document.getElementById('previewMicrNmr');
  const previewDate = document.getElementById('previewDate');
  const previewMontant = document.getElementById('previewMontant');
  const previewLettres = document.getElementById('previewLettres');
  const previewBenef = document.getElementById('previewBenef');
  const previewAgence = document.getElementById('previewAgence');
  const previewCin = document.getElementById('previewCin');
  const previewRib = document.getElementById('previewRib');
  const previewMicrRib = document.getElementById('previewMicrRib');
  const signatureName = document.getElementById('signatureName');

  if (modalChequierId) modalChequierId.textContent = numChequier;
  if (previewId) previewId.textContent = 'CHK-' + chkId;
  if (previewNmr) previewNmr.textContent = 'NMR-' + chkNmr;
  if (previewMicrNmr) previewMicrNmr.textContent = '⑆ ' + chkNmr + ' ⑆';
  if (previewDate) previewDate.textContent = now.toLocaleDateString('fr-FR');
  if (previewMontant) previewMontant.textContent = '0,000';
  if (previewLettres) previewLettres.textContent = '________________________________';
  if (previewBenef) previewBenef.textContent = '________________________________';
  if (previewAgence) previewAgence.textContent = '—';
  if (previewCin) previewCin.textContent = '—';
  if (previewRib) previewRib.textContent = '—';
  if (signatureName) signatureName.textContent = titulaire || 'Mouna Ncib';

  if (rib && previewMicrRib) {
    const ribClean = rib.replace(/\s/g, '').substring(4); /* retire TN59 */
    previewMicrRib.textContent = '⑆ ' + ribClean + ' ⑆';
  }

  const form = document.getElementById('chequeEmissionForm');
  const actionTypeInput = form ? form.querySelector('input[name="action_type"]') : null;
  const btnSubmit = form ? form.querySelector('button[type="submit"]') : null;
  const titleEl = document.querySelector('.modal-title');

  // Si on est en mode modification
  if (editId && window.currentChequesHistory) {
    const chq = window.currentChequesHistory.find(c => c.id_cheque == editId);
    if (chq) {
      if (titleEl) titleEl.textContent = "Modifier un chèque";
      if (chkIdEl) chkIdEl.value = "CHK-MOD-" + chq.id_cheque;
      if (chkNmrEl) chkNmrEl.value = chq.numero_cheque;
      if (chkMontantEl) chkMontantEl.value = chq.montant;
      if (chkDateEl) chkDateEl.value = chq.date_emission;
      if (chkLettresEl) chkLettresEl.value = chq.lettres;
      if (chkAgenceEl) chkAgenceEl.value = chq.agence;
      if (chkBenefEl) chkBenefEl.value = chq.beneficiaire;
      if (chkCinEl) chkCinEl.value = chq.cin_beneficiaire;
      if (chkRibEl) chkRibEl.value = chq.rib_beneficiaire;
      
      if (actionTypeInput) actionTypeInput.value = 'update_cheque';
      
      let idInput = document.getElementById('hiddenChequeId');
      if (!idInput && form) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_cheque';
        idInput.id = 'hiddenChequeId';
        form.appendChild(idInput);
      }
      if (idInput) idInput.value = chq.id_cheque;
      
      if (btnSubmit) {
        btnSubmit.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Mettre à jour le chèque`;
      }
    }
  } else {
    // Mode création (par défaut)
    if (titleEl) titleEl.textContent = "Saisir un chèque";
    if (actionTypeInput) actionTypeInput.value = 'emettre_cheque';
    const idInput = document.getElementById('hiddenChequeId');
    if (idInput) idInput.remove();
    if (btnSubmit) {
      btnSubmit.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Émettre le chèque`;
    }
  }

  const banner = document.getElementById('successBanner');
  if (banner) banner.classList.remove('show');

  const modal = document.getElementById('chequeModal');
  const hiddenId = document.getElementById('hiddenChequierId');
  const visibleRef = document.getElementById('chkRefChequier');
  if (hiddenId) hiddenId.value = idChequier;
  if (visibleRef) visibleRef.value = numChequier;

  if (modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  
  // Update preview après avoir rempli les champs
  updatePreview();
}

function closeChequeModal() {
  const modal = document.getElementById('chequeModal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}
function openHistoriqueModal(num, id) {
  const modal = document.getElementById('historiqueChequeModal');
  const listContainer = document.getElementById('historiqueList');
  const chqNumSpan = document.getElementById('histChqNum');
  const content = document.getElementById('historiqueContent');

  if (chqNumSpan) chqNumSpan.textContent = num;
  if (content) content.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--text-muted);">Chargement...</div>';

  if (modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  // Fetch history via AJAX using numerical ID
  fetch(`frontoffice_chequier.php?action=get_history&id=${id}`)
    .then(response => response.json())
    .then(data => {
      window.currentChequesHistory = data;
      if (data.length === 0) {
        content.innerHTML = '<div class="info-banner" style="background:var(--surface2); color:var(--muted);">Aucun chèque émis pour ce chéquier.</div>';
      } else {
        let html = `
          <div class="hist-list" style="display:flex; flex-direction:column; gap:0.8rem;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
              <thead style="background:var(--surface2); text-align:left;">
                <tr>
                  <th style="padding:0.75rem; border-bottom:1px solid var(--border);">N° Chèque</th>
                  <th style="padding:0.75rem; border-bottom:1px solid var(--border);">Bénéficiaire</th>
                  <th style="padding:0.75rem; border-bottom:1px solid var(--border);">Montant</th>
                  <th style="padding:0.75rem; border-bottom:1px solid var(--border);">Date / Actions</th>
                </tr>
              </thead>
              <tbody>
        `;
        data.forEach(chq => {
          html += `
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:0.75rem; font-family:var(--fm); color:var(--blue);">${chq.numero_cheque}</td>
              <td style="padding:0.75rem;">
                <div style="font-weight:600;">${chq.beneficiaire}</div>
                <div style="font-size:0.7rem; color:var(--muted);">${chq.rib_beneficiaire}</div>
              </td>
              <td style="padding:0.75rem; font-weight:600;">${parseFloat(chq.montant).toLocaleString('fr-TN', {minimumFractionDigits:3})} TND</td>
              <td style="padding:0.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
                  <span style="color:var(--muted);">${new Date(chq.date_emission).toLocaleDateString('fr-FR')}</span>
                  <div style="display:flex; gap:0.4rem;">
                    <button class="ca-act-btn" style="background:rgba(99,102,241,0.1); color:var(--blue); border:none; padding:4px 6px; border-radius:5px; cursor:pointer;" onclick="editCheque(${chq.id_cheque})" title="Modifier">
                      <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="ca-act-btn" style="background:rgba(220,38,38,0.1); color:var(--rose); border:none; padding:4px 6px; border-radius:5px; cursor:pointer;" onclick="deleteCheque(${chq.id_cheque}, ${id})" title="Supprimer">
                      <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path></svg>
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          `;
        });
        html += '</tbody></table></div>';
        content.innerHTML = html;
      }
    })
    .catch(err => {
      content.innerHTML = '<div class="info-banner" style="background:var(--rose-light); color:var(--rose);">Erreur lors du chargement de l\'historique.</div>';
    });
}

function closeHistoriqueModal() {
  const modal = document.getElementById('historiqueChequeModal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}
function deleteCheque(idCheque, idChequier) {
  if (confirm("Voulez-vous vraiment supprimer ce chèque ? Cette action recréditera une feuille au chéquier.")) {
    fetch(`frontoffice_chequier.php?action=delete_cheque&id=${idCheque}`)
      .then(response => response.json())
      .then(data => {
        if(data.success) {
          // Rafraichir la modale d'historique
          const chqNumSpan = document.getElementById('histChqNum');
          openHistoriqueModal(chqNumSpan.textContent, idChequier);
          // Recharger la page après un court délai pour actualiser les compteurs
          setTimeout(() => window.location.reload(), 1500);
        } else {
          alert("Erreur lors de la suppression.");
        }
      })
      .catch(err => alert("Erreur réseau."));
  }
}

/**
 * Modifier un chèque existant
 */
function editCheque(idCheque) {
  const chq = window.currentChequesHistory.find(c => c.id_cheque == idCheque);
  if (!chq) return;

  closeHistoriqueModal();

  const numChequier = document.getElementById('histChqNum').textContent;
  // On passe idCheque comme 5ème paramètre pour que openChequeModal (via verification) le reçoive
  openChequeModal(numChequier, chq.beneficiaire, chq.rib_beneficiaire, chq.id_chequier, idCheque);
}
const histOverlay = document.getElementById('historiqueChequeModal');
if (histOverlay) {
  histOverlay.addEventListener('click', function(e) {
    if (e.target === this) closeHistoriqueModal();
  });
}
const modalOverlay = document.getElementById('chequeModal');
if (modalOverlay) {
  modalOverlay.addEventListener('click', function(e) {
    if (e.target === this) closeChequeModal();
  });
}

/**
 * Mise à jour live de l'aperçu visuel du chèque
 */
function updatePreview() {
  const montantStr = document.getElementById('chkMontant').value;
  const montant = parseFloat(montantStr);
  const lettres = document.getElementById('chkLettres').value;
  const benef   = document.getElementById('chkBenef').value;
  const cin     = document.getElementById('chkCin').value;
  const rib     = document.getElementById('chkRib').value;
  const agence  = document.getElementById('chkAgence').value;
  const date    = document.getElementById('chkDate').value;

  const previewMontant = document.getElementById('previewMontant');
  const previewLettres = document.getElementById('previewLettres');
  const previewBenef = document.getElementById('previewBenef');
  const previewCin = document.getElementById('previewCin');
  const previewRib = document.getElementById('previewRib');
  const previewAgence = document.getElementById('previewAgence');
  const previewDate = document.getElementById('previewDate');

  if (previewMontant) {
    previewMontant.textContent =
      isNaN(montant) || montant === 0 ? '0,000' :
      montant.toLocaleString('fr-TN', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
  }

  if (previewLettres) previewLettres.textContent = lettres || '________________________________';
  if (previewBenef) previewBenef.textContent = benef || '________________________________';
  if (previewCin) previewCin.textContent = cin || '—';
  if (previewRib) previewRib.textContent = rib || '—';
  if (previewAgence) previewAgence.textContent = agence || '—';

  if (date && previewDate) {
    const d = new Date(date + 'T00:00:00');
    previewDate.textContent = d.toLocaleDateString('fr-FR');
  }
}

function lancerSaisirCheque() {
  closeChequierModal();
  if (typeof window._chqNum !== 'undefined') {
    openChequeModal(window._chqNum, window._chqName, window._chqIban, window._chqId);
  }
}


document.addEventListener('DOMContentLoaded', () => {
  const toasts = document.querySelectorAll('.toast');
  toasts.forEach(t => {
    setTimeout(() => {
      t.style.opacity = '0';
      setTimeout(() => t.remove(), 500);
    }, 5000);
  });
});
