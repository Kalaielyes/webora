
document.addEventListener('DOMContentLoaded', function() {
  const successMsg = document.getElementById('phpSuccessMsg');
  if (successMsg) {
    setTimeout(() => { 
      successMsg.style.display = 'none'; 
    }, 5000);
  }
});


(function() {
  const now  = new Date();
  const year = now.getFullYear();
  const rand = String(Math.floor(Math.random() * 99999) + 1).padStart(5, '0');
  const f    = document.getElementById('idDemande');
  if (f) f.value = year + '-' + rand;
})();


function selectMode(el, mode) {
  document.querySelectorAll('.radio-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  const addr  = document.getElementById('fieldAdresse');
  const label = addr.querySelector('label');
  const input = addr.querySelector('input');
  if (mode === 'agence') {
    label.textContent    = "Adresse de l'agence";
    input.placeholder    = "Ex : Agence Tunis Centre, Av. Habib Bourguiba";
    input.name           = "adresse_agence";
  } else {
    label.textContent    = "Adresse de livraison";
    input.placeholder    = "Ex : 12, Rue de la Liberté, Tunis 1002";
    input.name           = "adresse_livraison";
  }
}


function validerDemande(e) {
  let valide = true;
  document.querySelectorAll('#demandeForm .error-msg').forEach(el => {
    el.style.display = 'none';
    el.textContent = '';
  });

const nomPrenom = document.getElementById('nomPrenom').value.trim();
if (nomPrenom.length === 0) {
  afficherErreur('errNomPrenom', 'Le nom et prénom est obligatoire.');
  valide = false;

} else if (nomPrenom.length > 40) {
  afficherErreur('errNomPrenom', 'Le nom et prénom ne doit pas dépasser 40 caractères.');
  valide = false;

} else if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(nomPrenom)) {
  afficherErreur('errNomPrenom', 'Le nom et prénom ne doit contenir que des lettres.');
  valide = false;
}

  const compte = document.getElementById('compte').value.trim();
  if (compte === "") {
    afficherErreur('errCompte', 'Veuillez sélectionner un compte.');
    valide = false;
  }

  const montantStr = document.getElementById('montantMax').value.trim();
  const montant = parseFloat(montantStr);
  if (!montantStr || isNaN(montant) || montant <= 0) {
    afficherErreur('errMontant', 'Le montant doit être un chiffre positif.');
    valide = false;
  } else if (montant > 30000) {
    afficherErreur('errMontant', 'Le montant maximum autorisé est de 30000 TND.');
    valide = false;
  }

  const motif = document.getElementById('motif').value.trim();
  if (motif.length === 0) {
    afficherErreur('errMotif', 'Le motif est obligatoire.');
    valide = false;
  } else if (motif.length > 20) {
    afficherErreur('errMotif', 'Le motif ne doit pas dépasser 20 caractères.');
    valide = false;
  } else if (!/^[a-zA-ZÀ-ÿ\s]*$/.test(motif)) {
    afficherErreur('errMotif', 'Le motif doit contenir uniquement des lettres.');
    valide = false;
  }

  const adresse = document.getElementById('adresseInput').value.trim();
  if (adresse.length === 0) {
    afficherErreur('errAdresse', 'L\'adresse est obligatoire.');
    valide = false;
  } else if (adresse.length > 20) {
    afficherErreur('errAdresse', 'L\'adresse ne doit pas dépasser 20 caractères.');
    valide = false;
  }

  const tel = document.getElementById('telephone').value.trim();
  if (!/^\d{8}$/.test(tel)) {
    afficherErreur('errTelephone', 'Le téléphone doit contenir exactement 8 chiffres.');
    valide = false;
  }

  const email = document.getElementById('email').value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    afficherErreur('errEmail', 'Veuillez entrer une adresse email valide.');
    valide = false;
  } else if (!email.toLowerCase().endsWith('@gmail.com')) {
    afficherErreur('errEmail', 'L\'email doit obligatoirement être une adresse @gmail.com.');
    valide = false;
  }

  const commentaire = document.getElementById('commentaire').value.trim();
  if (commentaire.length > 40) {
    afficherErreur('errCommentaire', 'Le commentaire ne doit pas dépasser 40 caractères.');
    valide = false;
  } else if (commentaire.length > 0 && !/^[a-zA-ZÀ-ÿ\s]*$/.test(commentaire)) {
    afficherErreur('errCommentaire', 'Le commentaire doit contenir uniquement des lettres.');
    valide = false;
  }

  if (!valide) {
    e.preventDefault();
  } else {

  }
  return valide;
}

function afficherErreur(id, msg) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = msg;
    el.style.display = 'block';
  }
}


let _nmrCounter = 1000;

function openChequeModal(chequierId, titulaire, rib) {
  _nmrCounter++;
  const now  = new Date();
  const year = now.getFullYear();
  const chkId  = year + '-' + String(Math.floor(Math.random() * 99999) + 1).padStart(5, '0');
  const chkNmr = year + '-' + String(_nmrCounter).padStart(6, '0');

  
  document.getElementById('chkId').value  = chkId;
  document.getElementById('chkNmr').value = chkNmr;

  
  document.getElementById('chkDate').value    = now.toISOString().split('T')[0];
  document.getElementById('chkMontant').value = '';
  document.getElementById('chkLettres').value = '';
  document.getElementById('chkBenef').value   = '';
  document.getElementById('chkCin').value     = '';
  document.getElementById('chkRib').value     = '';
  document.getElementById('chkAgence').value  = '';

  
  document.getElementById('modalChequierId').textContent = chequierId;
  document.getElementById('previewId').textContent       = 'CHK-' + chkId;
  document.getElementById('previewNmr').textContent      = 'NMR-' + chkNmr;
  document.getElementById('previewMicrNmr').textContent  = '⑆ ' + chkNmr + ' ⑆';
  document.getElementById('previewDate').textContent     = now.toLocaleDateString('fr-FR');
  document.getElementById('previewMontant').textContent  = '0,000';
  document.getElementById('previewLettres').textContent  = '________________________________';
  document.getElementById('previewBenef').textContent    = '________________________________';
  document.getElementById('previewAgence').textContent   = '—';
  document.getElementById('previewCin').textContent      = '—';
  document.getElementById('previewRib').textContent      = '—';
  document.getElementById('signatureName').textContent   = titulaire || 'Mouna Ncib';

  
  if (rib) {
    const ribClean = rib.replace(/\s/g, '').substring(4); 
    document.getElementById('previewMicrRib').textContent = '⑆ ' + ribClean + ' ⑆';
  }

  
  document.getElementById('successBanner').classList.remove('show');

  
  document.getElementById('chequeModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeChequeModal() {
  document.getElementById('chequeModal').classList.remove('open');
  document.body.style.overflow = '';
}


if (document.getElementById('chequeModal')) {
  document.getElementById('chequeModal').addEventListener('click', function(e) {
    if (e.target === this) closeChequeModal();
  });
}


document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
      if (document.getElementById('chequeModal') && document.getElementById('chequeModal').classList.contains('open')) {
          closeChequeModal();
      }
      if (document.getElementById('requestsModal') && document.getElementById('requestsModal').classList.contains('open')) {
          closeModal();
      }
      if (document.getElementById('allChequiersModal') && document.getElementById('allChequiersModal').classList.contains('open')) {
          closeAllChequiersModal();
      }
  }
});


function updatePreview() {
  const montant = parseFloat(document.getElementById('chkMontant').value);
  const lettres = document.getElementById('chkLettres').value;
  const benef   = document.getElementById('chkBenef').value;
  const cin     = document.getElementById('chkCin').value;
  const rib     = document.getElementById('chkRib').value;
  const agence  = document.getElementById('chkAgence').value;
  const date    = document.getElementById('chkDate').value;

  document.getElementById('previewMontant').textContent =
    isNaN(montant) || montant === 0 ? '0,000' :
    montant.toLocaleString('fr-TN', { minimumFractionDigits: 3, maximumFractionDigits: 3 });

  document.getElementById('previewLettres').textContent = lettres || '________________________________';
  document.getElementById('previewBenef').textContent   = benef   || '________________________________';
  document.getElementById('previewCin').textContent     = cin     || '—';
  document.getElementById('previewRib').textContent     = rib     || '—';
  document.getElementById('previewAgence').textContent  = agence  || '—';

  if (date) {
    const d = new Date(date + 'T00:00:00');
    document.getElementById('previewDate').textContent = d.toLocaleDateString('fr-FR');
  }
}


function submitCheque() {
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
  } else if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
    afficherErreur('errChkDate', 'Format de date invalide (AATTendu: YYYY-MM-DD).');
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

  if (!valide) {
    return;
  }

  
  const banner = document.getElementById('successBanner');
  banner.classList.add('show');
  setTimeout(function() {
    banner.classList.remove('show');
    closeChequeModal();
  }, 2500);
}

function openModal() {
  document.getElementById('requestsModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('requestsModal').classList.remove('open');
  document.body.style.overflow = 'auto';
}

function preparerModif(data) {
  closeModal();
  const sectionTitle = document.querySelector('.section-title');
  if (sectionTitle) sectionTitle.textContent = "Modifier la demande DEM-" + data.id_demande;
  
  if (document.getElementById('idEdit')) document.getElementById('idEdit').value = data.id_demande;
  if (document.getElementById('idDemande')) document.getElementById('idDemande').value = data.id_demande; 
  if (document.getElementById('nomPrenom')) document.getElementById('nomPrenom').value = data['nom et prenom'];
  if (document.getElementById('compte')) document.getElementById('compte').value = data.id_compte;
  
  const typeSelect = document.querySelector('select[name="type_chequier"]');
  if (typeSelect) typeSelect.value = data.type_chequier;
  
  const nbSelect = document.querySelector('select[name="nombre_cheques"]');
  if (nbSelect) nbSelect.value = data.nombre_cheques;
  
  if (document.getElementById('montantMax')) document.getElementById('montantMax').value = data.montant_max_par_cheque;
  if (document.getElementById('motif')) document.getElementById('motif').value = data.motif;
  if (document.getElementById('telephone')) document.getElementById('telephone').value = data.telephone;
  if (document.getElementById('email')) document.getElementById('email').value = data.email;
  if (document.getElementById('commentaire')) document.getElementById('commentaire').value = data.commentaire;
  if (document.getElementById('adresseInput')) document.getElementById('adresseInput').value = data.adresse_agence;
  
  
  const radios = document.getElementsByName('mode_reception');
  radios.forEach(r => {
    if(r.value === data.mode_reception) {
      r.checked = true;
      selectMode(r.parentNode, r.value);
    }
  });
  
  
  const form = document.getElementById('demandeForm');
  if (form) form.scrollIntoView({behavior:'smooth'});
}

function openAllChequiersModal() {
  document.getElementById('allChequiersModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeAllChequiersModal() {
  document.getElementById('allChequiersModal').classList.remove('open');
  document.body.style.overflow = 'auto';
}
