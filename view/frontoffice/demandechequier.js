document.addEventListener("DOMContentLoaded", function () {

  const form = document.getElementById("demandeForm");
  if (!form) return;

  form.addEventListener("submit", function (e) {

    let valide = true;

    // Supprimer anciens messages
    document.querySelectorAll(".error-msg").forEach(el => {
      el.textContent = "";
      el.style.display = "none";
    });

    function erreur(id, message) {
      const el = document.getElementById(id);
      if (el) {
        el.textContent = message;
        el.style.display = "block";
      }
      valide = false;
    }

    // ===============================
    // NOM PRENOM
    // ===============================
    const nom = document.getElementById("nomPrenom").value.trim();
    if (!/^[a-zA-ZÀ-ÿ\s]{3,40}$/.test(nom)) {
      erreur("errNomPrenom", "3-40 lettres uniquement.");
    }

    // ===============================
    // COMPTE
    // ===============================
    const compte = document.getElementById("compte").value;
    if (compte === "") {
      erreur("errCompte", "Sélectionnez un compte.");
    }

    // ===============================
    // MONTANT
    // ===============================
    const montant = parseFloat(document.getElementById("montantMax").value);
    if (isNaN(montant) || montant <= 0 || montant > 30000) {
      erreur("errMontant", "Montant entre 1 et 30000.");
    }

    // ===============================
    // MOTIF
    // ===============================
    const motif = document.getElementById("motif").value.trim();
    if (!/^[a-zA-ZÀ-ÿ\s]{3,20}$/.test(motif)) {
      erreur("errMotif", "3-20 lettres uniquement.");
    }

    // ===============================
    // ADRESSE
    // ===============================
    const adresse = document.getElementById("adresseInput").value.trim();
    if (adresse.length < 5 || adresse.length > 100) {
      erreur("errAdresse", "Adresse invalide (5-100 caractères).");
    }

    // ===============================
    // TELEPHONE
    // ===============================
    const tel = document.getElementById("telephone").value.trim();
    if (!/^\d{8}$/.test(tel)) {
      erreur("errTelephone", "Téléphone = 8 chiffres.");
    }

    // ===============================
    // EMAIL
    // ===============================
    const email = document.getElementById("email").value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      erreur("errEmail", "Email invalide.");
    }

    // ===============================
    // COMMENTAIRE
    // ===============================
    const comm = document.getElementById("commentaire").value.trim();
    if (comm.length < 3 || comm.length > 100) {
      erreur("errCommentaire", "3-100 caractères.");
    }

    // ===============================
    // SI ERREUR → BLOQUER
    // ===============================
    if (!valide) {
      e.preventDefault();
    }

  });

});