<?php
/**
 * Cheque Model - webora Integration
 * Manages individual cheque records
 */

class Cheque {
    private ?int $id_cheque = null;
    private ?int $id_chequier = null;
    private ?string $numero_cheque = null;
    private ?float $montant = null;
    private ?string $date_emission = null;
    private ?string $beneficiaire = null;
    private ?string $rib_beneficiaire = null;
    private ?string $cin_beneficiaire = null;
    private ?string $lettres = null;
    private ?string $agence = null;

    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->id_cheque = isset($data['id_cheque']) ? (int)$data['id_cheque'] : null;
            $this->id_chequier = isset($data['id_chequier']) ? (int)$data['id_chequier'] : null;
            $this->numero_cheque = $data['numero_cheque'] ?? null;
            $this->montant = isset($data['montant']) ? (float)$data['montant'] : null;
            $this->date_emission = $data['date_emission'] ?? null;
            $this->beneficiaire = $data['beneficiaire'] ?? null;
            $this->rib_beneficiaire = $data['rib_beneficiaire'] ?? null;
            $this->cin_beneficiaire = $data['cin_beneficiaire'] ?? null;
            $this->lettres = $data['lettres'] ?? null;
            $this->agence = $data['agence'] ?? null;
        }
    }

    // ── Getters ─────────────────────────────────────────────────────────────
    public function getIdCheque() { return $this->id_cheque; }
    public function getIdChequier() { return $this->id_chequier; }
    public function getNumeroCheque() { return $this->numero_cheque; }
    public function getMontant() { return $this->montant; }
    public function getDateEmission() { return $this->date_emission; }
    public function getBeneficiaire() { return $this->beneficiaire; }
    public function getRibBeneficiaire() { return $this->rib_beneficiaire; }
    public function getCinBeneficiaire() { return $this->cin_beneficiaire; }
    public function getLettres() { return $this->lettres; }
    public function getAgence() { return $this->agence; }

    // ── Setters ─────────────────────────────────────────────────────────────
    public function setIdChequier(int $val) { $this->id_chequier = $val; }
    public function setNumeroCheque(string $val) { $this->numero_cheque = $val; }
    public function setMontant(float $val) { $this->montant = $val; }
    public function setDateEmission(string $val) { $this->date_emission = $val; }
    public function setBeneficiaire(string $val) { $this->beneficiaire = $val; }
    public function setRibBeneficiaire(string $val) { $this->rib_beneficiaire = $val; }
    public function setCinBeneficiaire(string $val) { $this->cin_beneficiaire = $val; }
    public function setLettres(string $val) { $this->lettres = $val; }
    public function setAgence(string $val) { $this->agence = $val; }
}
