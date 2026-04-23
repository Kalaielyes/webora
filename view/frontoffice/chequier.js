
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
