<?php
class Chequier {
    private ?int $id_chequier = null;
    private ?string $numero_chequier = null;
    private ?string $date_creation = null;
    private ?string $date_expiration = null;
    private ?string $statut = 'actif';
    private ?int $nombre_feuilles = 25;
    private ?int $id_demande = null;
    private ?int $id_Compte = null;

    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->id_chequier = isset($data['id_chequier']) ? (int)$data['id_chequier'] : null;
            $this->numero_chequier = $data['numero_chequier'] ?? null;
            $this->date_creation = $data['date_creation'] ?? null;
            $this->date_expiration = $data['date_expiration'] ?? null;
            $this->statut = $data['statut'] ?? 'actif';
            $this->nombre_feuilles = isset($data['nombre_feuilles']) ? (int)$data['nombre_feuilles'] : 25;
            $this->id_demande = isset($data['id_demande']) ? (int)$data['id_demande'] : null;
            $this->id_Compte = isset($data['id_Compte']) ? (int)$data['id_Compte'] : (isset($data['id_compte']) ? (int)$data['id_compte'] : null);
        }
    }
    public function getIdChequier() { return $this->id_chequier; }
    public function getNumeroChequier() { return $this->numero_chequier; }
    public function getDateCreation() { return $this->date_creation; }
    public function getDateExpiration() { return $this->date_expiration; }
    public function getStatut() { return $this->statut; }
    public function getNombreFeuilles() { return $this->nombre_feuilles; }
    public function getIdDemande() { return $this->id_demande; }
    public function getIdCompte() { return $this->id_Compte; }
    public function setNumeroChequier(string $numero) { $this->numero_chequier = $numero; }
    public function setDateCreation(string $date) { $this->date_creation = $date; }
    public function setDateExpiration(string $date) { $this->date_expiration = $date; }
    public function setStatut(string $statut) { $this->statut = $statut; }
    public function setNombreFeuilles(int $nb) { $this->nombre_feuilles = $nb; }
    public function setIdDemande(int $id) { $this->id_demande = $id; }
    public function setIdCompte(int $id) { $this->id_Compte = $id; }
}
