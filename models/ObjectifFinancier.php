<?php
/**
 * ObjectifFinancier.php — MODEL (DTO)
 * Independent financial goal with multi-currency support.
 */

class ObjectifFinancier {
    private $id_objectif;
    private $id_utilisateur;
    private $id_compte;
    private $titre;
    private $montant_objectif;
    private $montant_actuel;
    private $devise;
    private $date_debut;
    private $date_fin;
    private $statut; 
    private $created_at;

    public function __construct(
        $id_objectif,
        $id_utilisateur,
        $id_compte,
        $titre,
        $montant_objectif,
        $montant_actuel = 0.000,
        $devise = 'TND',
        $date_debut,
        $date_fin,
        $statut = 'en_cours',
        $created_at = null
    ) {
        $this->id_objectif      = $id_objectif;
        $this->id_utilisateur   = $id_utilisateur;
        $this->id_compte        = $id_compte;
        $this->titre            = $titre;
        $this->montant_objectif = (float)$montant_objectif;
        $this->montant_actuel   = (float)$montant_actuel;
        $this->devise           = $devise;
        $this->date_debut       = $date_debut;
        $this->date_fin         = $date_fin;
        $this->statut           = $statut;
        $this->created_at       = $created_at;
    }

    // Getters
    public function getIdObjectif()      { return $this->id_objectif; }
    public function getIdUtilisateur()   { return $this->id_utilisateur; }
    public function getIdCompte()        { return $this->id_compte; }
    public function getTitre()           { return $this->titre; }
    public function getMontantObjectif() { return $this->montant_objectif; }
    public function getMontantActuel()   { return $this->montant_actuel; }
    public function getDevise()          { return $this->devise; }
    public function getDateDebut()       { return $this->date_debut; }
    public function getDateFin()         { return $this->date_fin; }
    public function getStatut()          { return $this->statut; }
    public function getCreatedAt()       { return $this->created_at; }

    // Setters
    public function setStatut($v)        { $this->statut = $v; }
    public function setMontantActuel($v) { $this->montant_actuel = (float)$v; }
}
?>
