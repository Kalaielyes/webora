<?php
class DemandeChequier {
    private ?int $id_demande = null;
    private ?string $nom_et_prenom = null;
    private ?int $id_compte = null;
    private ?string $motif = null;
    private ?string $type_chequier = null;
    private ?string $nombre_cheques = null;
    private ?float $montant_max_par_cheque = null;
    private ?string $mode_reception = null;
    private ?string $adresse_agence = null;
    private ?string $telephone = null;
    private ?string $email = null;
    private ?string $commentaire = null;
    private ?string $date_demande = null;
    private ?string $statut = 'En attente';

    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->nom_et_prenom = $data['nom_et_prenom'] ?? null;
            $this->id_compte = isset($data['id_compte']) ? (int)$data['id_compte'] : null;
            $this->motif = $data['motif'] ?? null;
            $this->type_chequier = $data['type_chequier'] ?? null;
            $this->nombre_cheques = $data['nombre_cheques'] ?? null;
            $this->montant_max_par_cheque = isset($data['montant_max_par_cheque']) ? (float)$data['montant_max_par_cheque'] : null;
            $this->mode_reception = $data['mode_reception'] ?? null;
            $this->adresse_agence = $data['adresse_agence'] ?? null;
            $this->telephone = $data['telephone'] ?? null;
            $this->email = $data['email'] ?? null;
            $this->commentaire = $data['commentaire'] ?? null;
            $this->statut = $data['statut'] ?? 'En attente';
        }
    }

    public function getNomEtPrenom() { return $this->nom_et_prenom; }
    public function getIdCompte() { return $this->id_compte; }
    public function getMotif() { return $this->motif; }
    public function getTypeChequier() { return $this->type_chequier; }
    public function getNombreCheques() { return $this->nombre_cheques; }
    public function getMontantMaxParCheque() { return $this->montant_max_par_cheque; }
    public function getModeReception() { return $this->mode_reception; }
    public function getAdresseAgence() { return $this->adresse_agence; }
    public function getTelephone() { return $this->telephone; }
    public function getEmail() { return $this->email; }
    public function getCommentaire() { return $this->commentaire; }
    public function getDateDemande() { return $this->date_demande; }
    public function getIdDemande() { return $this->id_demande; }
    public function getStatut() { return $this->statut; }
}
