<?php
/**
 * CompteBancaire.php — MODEL (Anemic DTO)
 * Blueprint for a bank account.
 * Purely holds data, no database operations.
 */

class CompteBancaire {

    // ── Valid values ──────────────────────────────────────────────────────────
    public const TYPES   = ['courant', 'epargne', 'devise', 'professionnel'];
    public const DEVISES = ['TND', 'EUR', 'USD', 'GBP'];

    // ── Properties (map 1-to-1 to DB columns) ────────────────────────────────
    private $id_compte;
    private $id_utilisateur;
    private $iban;
    private $type_compte;        // courant | epargne | devise | professionnel
    private $solde;
    private $devise;
    private $plafond_virement;
    private $statut;             // en_attente | actif | bloque | cloture | demande_cloture | demande_activation_courant
    private $date_ouverture;
    private $date_fermeture;

    // ── Constructor ───────────────────────────────────────────────────────────
    public function __construct(
        $id_compte,
        $id_utilisateur,
        $iban,
        $type_compte,
        $solde            = 0.000,
        $devise           = 'TND',
        $plafond_virement = 5000.000,
        $statut           = 'en_attente',
        $date_ouverture   = null,
        $date_fermeture   = null
    ) {
        $this->id_compte        = $id_compte;
        $this->id_utilisateur   = $id_utilisateur;
        $this->iban             = $iban;
        $this->type_compte      = $type_compte;
        $this->solde            = $solde;
        $this->devise           = $devise;
        $this->plafond_virement = $plafond_virement;
        $this->statut           = $statut;
        $this->date_ouverture   = $date_ouverture ?? date('Y-m-d');
        $this->date_fermeture   = $date_fermeture;
    }

    // ── Getters ───────────────────────────────────────────────────────────────
    public function getIdCompte()        { return $this->id_compte; }
    public function getIdUtilisateur()   { return $this->id_utilisateur; }
    public function getIban()            { return $this->iban; }
    public function getTypeCompte()      { return $this->type_compte; }
    public function getSolde()           { return $this->solde; }
    public function getDevise()          { return $this->devise; }
    public function getPlafondVirement() { return $this->plafond_virement; }
    public function getStatut()          { return $this->statut; }
    public function getDateOuverture()   { return $this->date_ouverture; }
    public function getDateFermeture()   { return $this->date_fermeture; }

    // ── Setters ───────────────────────────────────────────────────────────────
    public function setIdCompte($v)         { $this->id_compte = $v; }
    public function setIdUtilisateur($v)    { $this->id_utilisateur = $v; }
    public function setIban($v)             { $this->iban = $v; }
    public function setTypeCompte($v)       { $this->type_compte = $v; }
    public function setSolde($v)            { $this->solde = $v; }
    public function setDevise($v)           { $this->devise = $v; }
    public function setPlafondVirement($v)  { $this->plafond_virement = $v; }
    public function setStatut($v)           { $this->statut = $v; }
    public function setDateOuverture($v)    { $this->date_ouverture = $v; }
    public function setDateFermeture($v)    { $this->date_fermeture = $v; }
}
?>