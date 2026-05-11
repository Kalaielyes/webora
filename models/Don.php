<?php

class Don {
    private $id_don;
    private $id_cagnotte;
    private $id_donateur;
    private $montant;
    private $est_anonyme;
    private $message;
    private $moyen_paiement;
    private $statut;
    private $date_don;
    // Banking fields (populated when moyen_paiement = 'virement')
    private $id_compte;
    private $devise_don;
    private $montant_converti;

    public function __construct(
        $id_don          = null,
        $id_cagnotte     = null,
        $id_donateur     = null,
        $montant         = null,
        $est_anonyme     = 0,
        $message         = null,
        $moyen_paiement  = null,
        $statut          = 'en_attente',
        $date_don        = null,
        $id_compte       = null,
        $devise_don      = 'TND',
        $montant_converti = null
    ) {
        $this->id_don           = $id_don;
        $this->id_cagnotte      = $id_cagnotte;
        $this->id_donateur      = $id_donateur;
        $this->montant          = $montant;
        $this->est_anonyme      = $est_anonyme;
        $this->message          = $message;
        $this->moyen_paiement   = $moyen_paiement;
        $this->statut           = $statut;
        $this->date_don         = $date_don;
        $this->id_compte        = $id_compte;
        $this->devise_don       = $devise_don;
        $this->montant_converti = $montant_converti;
    }

    public function __destruct() {}

    public function getId()               { return $this->id_don; }
    public function getIdDon()            { return $this->id_don; }
    public function getIdCagnotte()       { return $this->id_cagnotte; }
    public function getIdDonateur()       { return $this->id_donateur; }
    public function getMontant()          { return $this->montant; }
    public function getEstAnonyme()       { return $this->est_anonyme; }
    public function getMessage()          { return $this->message; }
    public function getMoyenPaiement()    { return $this->moyen_paiement; }
    public function getStatut()           { return $this->statut; }
    public function getDateDon()          { return $this->date_don; }
    public function getIdCompte()         { return $this->id_compte; }
    public function getDeviseDon()        { return $this->devise_don; }
    public function getMontantConverti()  { return $this->montant_converti; }

    public function setId($id_don)                      { $this->id_don = $id_don; }
    public function setIdDon($id_don)                   { $this->id_don = $id_don; }
    public function setIdCagnotte($id_cagnotte)         { $this->id_cagnotte = $id_cagnotte; }
    public function setIdDonateur($id_donateur)         { $this->id_donateur = $id_donateur; }
    public function setMontant($montant)                { $this->montant = $montant; }
    public function setEstAnonyme($est_anonyme)         { $this->est_anonyme = $est_anonyme; }
    public function setMessage($message)                { $this->message = $message; }
    public function setMoyenPaiement($moyen_paiement)   { $this->moyen_paiement = $moyen_paiement; }
    public function setStatut($statut)                  { $this->statut = $statut; }
    public function setDateDon($date_don)               { $this->date_don = $date_don; }
    public function setIdCompte($id_compte)             { $this->id_compte = $id_compte; }
    public function setDeviseDon($devise_don)           { $this->devise_don = $devise_don; }
    public function setMontantConverti($montant_converti){ $this->montant_converti = $montant_converti; }
}
