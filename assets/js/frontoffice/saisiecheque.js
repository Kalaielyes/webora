function submitCheque(event) {
  if (event) event.preventDefault();

  let valide = true;
  document.querySelectorAll('.cheque-form .error-msg').forEach(el => {
    el.style.display = 'none';
    el.textContent = '';
  });

  const montantStr = document.getElementById('chkMontant').value.trim();
  const montant = parseFloat(montantStr);
  if (!montantStr || isNaN(montant) || montant <= 0 || montant > 8000) {
    afficherErreur('errChkMontant', 'Obligatoire, strictement positif et max 8000.');
    valide = false;
  }

  const date = document.getElementById('chkDate').value.trim();
  if (!date) {
    afficherErreur('errChkDate', 'La date d\'émission est obligatoire.');
    valide = false;
  }

  const lettres = document.getElementById('chkLettres').value.trim();
  if (!lettres || lettres.length > 40 || !/^[a-zA-ZÀ-ÿ\s]+$/.test(lettres)) {
    afficherErreur('errChkLettres', 'Obligatoire, max 40 caractères et lettres uniquement.');
    valide = false;
  }

  const agence = document.getElementById('chkAgence').value.trim();
  if (!agence || agence.length > 20) {
    afficherErreur('errChkAgence', 'L\'agence est obligatoire, max 20 caractères.');
    valide = false;
  }

  const benef = document.getElementById('chkBenef').value.trim();
  if (!benef || benef.length > 10 || !/^[a-zA-ZÀ-ÿ\s]+$/.test(benef)) {
    afficherErreur('errChkBenef', 'Nom obligatoire, max 10 caractères et lettres uniquement.');
    valide = false;
  }

  const cin = document.getElementById('chkCin').value.trim();
  if (!/^\d{8}$/.test(cin)) {
    afficherErreur('errChkCin', 'La pièce d\'identité doit contenir exactement 8 chiffres.');
    valide = false;
  }

  const rib = document.getElementById('chkRib').value.trim();
  if (!/^\d{20}$/.test(rib)) {
    afficherErreur('errChkRib', 'Le RIB doit contenir exactement 20 chiffres.');
    valide = false;
  }

  if (!valide) return false;

  /* Afficher le message de succès et cacher le formulaire */
  const successBanner = document.getElementById('successBanner');
  const chequeForm = document.querySelector('.cheque-form');
  
  if (successBanner) {
    successBanner.style.display = 'flex';
  }
  if (chequeForm) {
    chequeForm.style.display = 'none';
  }

  /* Soumettre le formulaire après un court délai pour que l'utilisateur voit le message */
  setTimeout(() => {
    document.getElementById('chequeEmissionForm').submit();
  }, 1500);

  return false;
}
