<?php
/**
 * CarteBancaire.php — MODEL (Anemic DTO)
 * Blueprint for a bank card.
 * Purely holds data, no database operations.
 */

class CarteBancaire {

    // ── Valid values ──────────────────────────────────────────────────────────
    public const RESEAUX = ['visa', 'mastercard', 'cb', 'amex'];
    public const TYPES   = ['debit', 'credit', 'prepayee'];
    public const STYLES  = ['standard', 'gold', 'platinum', 'titanium'];
    public const STATUTS = [
        'active', 'inactive', 'bloquee', 'expiree',
        'demande_cloture', 'demande_blocage', 'demande_suppression'
    ];

    // ── Properties (map 1-to-1 to DB columns) ────────────────────────────────
    private $id_carte;
    private $id_compte;
    private $numero_carte;
    private $type_carte;            // debit, credit, prepayee
    private $titulaire_nom;
    private $reseau;                // visa, mastercard, etc.
    private $date_expiration;       // format YYYY-MM
    private $cvv_hash;              // encrypted CVV (for security)
    private $plafond_paiement_jour;
    private $plafond_retrait_jour;
    private $statut;                // active, inactive, bloquee, expiree
    private $style;                 // standard, gold, platinum, titanium
    private $date_emission;
    private $date_activation;
    private $motif_blocage;         // string info if card is blocked
    private $cvv_display;           // plaintext CVV (for front-end)

    // ── Constructor ───────────────────────────────────────────────────────────
    public function __construct(
        $id_carte,
        $id_compte,
        $numero_carte,
        $type_carte,
        $titulaire_nom,
        $reseau,
        $date_expiration,
        $cvv_hash              = '',
        $plafond_paiement_jour = 1000.0,
        $plafond_retrait_jour  = 500.0,
        $statut                = 'inactive',
        $style                 = 'standard',
        $date_emission         = null,
        $date_activation       = null,
        $motif_blocage         = null,
        $cvv_display           = ''
    ) {
        $this->id_carte              = $id_carte;
        $this->id_compte             = $id_compte;
        $this->numero_carte          = $numero_carte;
        $this->type_carte            = $type_carte;
        $this->titulaire_nom         = $titulaire_nom;
        $this->reseau                = $reseau;
        $this->date_expiration       = $date_expiration;
        $this->cvv_hash              = $cvv_hash;
        $this->plafond_paiement_jour = $plafond_paiement_jour;
        $this->plafond_retrait_jour  = $plafond_retrait_jour;
        $this->statut                = $statut;
        $this->style                 = $style;
        $this->date_emission         = $date_emission ?? date('Y-m-d');
        $this->date_activation       = $date_activation;
        $this->motif_blocage         = $motif_blocage;
        $this->cvv_display           = $cvv_display;
    }

    // ── Getters ───────────────────────────────────────────────────────────────
    public function getIdCarte()              { return $this->id_carte; }
    public function getIdCompte()             { return $this->id_compte; }
    public function getNumeroCarte()          { return $this->numero_carte; }
    public function getTypeCarte()            { return $this->type_carte; }
    public function getTitulaireNom()         { return $this->titulaire_nom; }
    public function getReseau()               { return $this->reseau; }
    public function getDateExpiration()       { return $this->date_expiration; }
    public function getCvvHash()              { return $this->cvv_hash; }
    public function getCvvDisplay()           { return $this->cvv_display; }
    public function getPlafondPaiementJour()  { return $this->plafond_paiement_jour; }
    public function getPlafondRetraitJour()   { return $this->plafond_retrait_jour; }
    public function getStatut()               { return $this->statut; }
    public function getStyle()                { return $this->style; }
    public function getDateEmission()         { return $this->date_emission; }
    public function getDateActivation()       { return $this->date_activation; }
    public function getMotifBlocage()         { return $this->motif_blocage; }

    // ── Formatted Data Helper ─────────────────────────────────────────────────
    public function getObfuscatedNumber(): string {
        $parts = explode(' ', $this->numero_carte);
        if (count($parts) === 4) {
            return '**** **** **** ' . end($parts);
        }
        return '**** ' . substr($this->numero_carte, -4);
    }

    // ── Setters ───────────────────────────────────────────────────────────────
    public function setIdCarte($v)              { $this->id_carte = $v; }
    public function setIdCompte($v)             { $this->id_compte = $v; }
    public function setNumeroCarte($v)          { $this->numero_carte = $v; }
    public function setTypeCarte($v)            { $this->type_carte = $v; }
    public function setTitulaireNom($v)         { $this->titulaire_nom = $v; }
    public function setReseau($v)               { $this->reseau = $v; }
    public function setDateExpiration($v)       { $this->date_expiration = $v; }
    public function setCvvHash($v)              { $this->cvv_hash = $v; }
    public function setPlafondPaiementJour($v)  { $this->plafond_paiement_jour = $v; }
    public function setPlafondRetraitJour($v)   { $this->plafond_retrait_jour = $v; }
    public function setStatut($v)               { $this->statut = $v; }
    public function setStyle($v)                { $this->style = $v; }
    public function setDateEmission($v)         { $this->date_emission = $v; }
    public function setDateActivation($v)       { $this->date_activation = $v; }
    public function setMotifBlocage($v)         { $this->motif_blocage = $v; }
}
?>
