<?php

class Cagnotte {
    private $id_cagnotte;
    private $id_createur;
    private $titre;
    private $description;
    private $categorie;
    private $objectif_montant;
    private $statut;
    private $date_debut;
    private $date_fin;
    private $montant_collecte;

    public function __construct(
        $id_cagnotte = null,
        $id_createur = null,
        $titre = '',
        $description = null,
        $categorie = null,
        $objectif_montant = null,
        $statut = 'en_attente',
        $date_debut = null,
        $date_fin = null,
        $montant_collecte = 0.0
    ) {
        $this->id_cagnotte      = $id_cagnotte;
        $this->id_createur      = $id_createur;
        $this->titre            = $titre;
        $this->description      = $description;
        $this->categorie        = $categorie;
        $this->objectif_montant = $objectif_montant;
        $this->statut           = $statut;
        $this->date_debut       = $date_debut;
        $this->date_fin         = $date_fin;
        $this->montant_collecte = $montant_collecte;
    }

    public function __destruct() {}

    public function getId()                { return $this->id_cagnotte; }
    public function getIdCagnotte()        { return $this->id_cagnotte; }
    public function getIdCreateur()        { return $this->id_createur; }
    public function getTitre()             { return $this->titre; }
    public function getDescription()       { return $this->description; }
    public function getCategorie()         { return $this->categorie; }
    public function getObjectif()          { return $this->objectif_montant; }
    public function getObjectifMontant()   { return $this->objectif_montant; }
    public function getStatut()            { return $this->statut; }
    public function getDateDebut()         { return $this->date_debut; }
    public function getDateFin()           { return $this->date_fin; }
    public function getMontantActuel()     { return $this->montant_collecte; }
    public function getMontantCollecte()   { return $this->montant_collecte; }

    public function setId($id_cagnotte)                  { $this->id_cagnotte = $id_cagnotte; }
    public function setIdCagnotte($id_cagnotte)          { $this->id_cagnotte = $id_cagnotte; }
    public function setIdCreateur($id_createur)          { $this->id_createur = $id_createur; }
    public function setTitre($titre)                     { $this->titre = $titre; }
    public function setDescription($description)         { $this->description = $description; }
    public function setCategorie($categorie)             { $this->categorie = $categorie; }
    public function setObjectif($objectif_montant)       { $this->objectif_montant = $objectif_montant; }
    public function setObjectifMontant($objectif_montant){ $this->objectif_montant = $objectif_montant; }
    public function setStatut($statut)                   { $this->statut = $statut; }
    public function setDateDebut($date_debut)            { $this->date_debut = $date_debut; }
    public function setDateFin($date_fin)                { $this->date_fin = $date_fin; }
    public function setMontantActuel($montant_collecte)  { $this->montant_collecte = $montant_collecte; }
    public function setMontantCollecte($montant_collecte){ $this->montant_collecte = $montant_collecte; }
}
