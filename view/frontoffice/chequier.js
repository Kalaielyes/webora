
function openChequierModal(num, name, iban, statut, feuilles, dateCreation, dateExp, id) {

  document.getElementById('chqModalId').textContent    = num;
  document.getElementById('chqModalName').textContent     = name;
  document.getElementById('chqModalFeuilles').textContent = feuilles;
  document.getElementById('chqModalExpLabel').textContent = 'Exp. ' + dateExp;
  document.getElementById('chqModalNumVal').textContent      = num;
  document.getElementById('chqModalFeuillesVal').textContent = feuilles + ' feuilles';
  document.getElementById('chqModalIban').textContent        = iban || 'Non renseigné';
  document.getElementById('chqModalDateCreation').textContent = dateCreation;
  document.getElementById('chqModalDateExp').textContent      = dateExp;
  const statLower = (statut || '').toLowerCase();
  let badgeClass = 'b-actif', dotColor = 'var(--green)';
  if (statLower === 'bloque' || statLower === 'bloqué') { badgeClass = 'b-refusee'; dotColor = 'var(--rose)'; }
  if (statLower === 'expire' || statLower === 'expiré') { badgeClass = 'b-refusee'; dotColor = 'var(--muted)'; }
  document.getElementById('chqModalStatutBadge').innerHTML =
    `<span class="badge ${badgeClass}" style="font-size:.65rem"><span class="badge-dot" style="background:${dotColor}"></span>${statut}</span>`;
  document.getElementById('chqModalBenef').value   = '';
  document.getElementById('chqModalMontant').value = '';
  document.getElementById('chqModalDate').value    = new Date().toISOString().split('T')[0];
  window._chqNum  = num;
  window._chqName = name;
  window._chqIban = iban;
  window._chqId   = id;

  const modal = document.getElementById('chequierModal');
  if (modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}


function closeChequierModal() {
  const modal = document.getElementById('chequierModal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}


function afficherErreur(elementId, message) {
  const el = document.getElementById(elementId);
  if (el) {
    el.textContent = message;
    el.style.display = 'block';
  }
}

function validerSaisieChequier() {
  let valide = true;
  
  // Reset all error messages
  document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

  const numInput = document.getElementById('input_numero_chequier');
  const dateCreateInput = document.getElementById('modal_date_creation');
  const sheetsInput = document.getElementById('modal_nb_feuilles');
  const dateExpInput = document.getElementById('modal_date_exp');

  // Validate Number
  if (numInput && numInput.value.trim() === "") {
    const errNum = document.getElementById('errModalNum');
    if (errNum) {
      errNum.textContent = "Le numéro de chéquier est obligatoire.";
      errNum.style.display = 'block';
    }
    valide = false;
  }

  // Validate Creation Date
  if (dateCreateInput && dateCreateInput.value.trim() === "") {
    const errCreate = document.getElementById('errModalDateCreate');
    if (errCreate) {
      errCreate.textContent = "La date de création est obligatoire.";
      errCreate.style.display = 'block';
    }
    valide = false;
  }

  // Validate Sheets
  if (sheetsInput && sheetsInput.value.trim() === "") {
    const errSheets = document.getElementById('errModalSheets');
    if (errSheets) {
      errSheets.textContent = "Le nombre de feuilles est obligatoire.";
      errSheets.style.display = 'block';
    }
    valide = false;
  }

  // Validate Expiration Date
if (dateExpInput) {
  if (dateExpInput.value.trim() === "") {
    const errDateExp = document.getElementById('errModalDateExp');
    if (errDateExp) {
      errDateExp.textContent = "La date d'expiration est obligatoire.";
      errDateExp.style.display = 'block';
    }
    valide = false;
  } else {
    const selectedDate = new Date(dateExpInput.value);
    const maxDate = new Date('2028-12-31T23:59:59');

    if (selectedDate > maxDate) {
      const errDateExp = document.getElementById('errModalDateExp');
      if (errDateExp) {
        errDateExp.textContent = "La date d'expiration ne peut pas dépasser l'année 2028.";
        errDateExp.style.display = 'block';
      }
      valide = false;
    }
  }
}
  
  return valide;
}

/* ── Attestation PDF ── */
function generateAttestation(idChequier) {
    window.open(`attestation_pdf.php?id=${idChequier}`, '_blank');
}

function generateChequeAttestation(idCheque) {
    window.open(`attestation_pdf.php?id_cheque=${idCheque}`, '_blank');
}
