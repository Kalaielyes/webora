<?php

require_once __DIR__ . '/config.php';

class Projet
{
    private ?int $id_projet;
    private ?string $titre;
    private ?string $description;
    private ?float $montant_objectif;
    private ?string $date_limite;
    private ?string $date_creation;
    private ?string $status;
    private ?int $id_createur;
    private ?float $taux_rentabilite;
    private ?int $temps_retour_brut;
    private ?string $secteur;

    public function __construct(
        ?int $id_projet = null,
        ?string $titre = null,
        ?string $description = null,
        ?float $montant_objectif = null,
        ?string $date_limite = null,
        ?string $date_creation = null,
        ?string $status = null,
        ?int $id_createur = null,
        ?float $taux_rentabilite = null,
        ?int $temps_retour_brut = null,
        ?string $secteur = null
    ) {
        $this->id_projet = $id_projet;
        $this->titre = $titre;
        $this->description = $description;
        $this->montant_objectif = $montant_objectif;
        $this->date_limite = $date_limite;
        $this->date_creation = $date_creation;
        $this->status = $status;
        $this->id_createur = $id_createur;
        $this->taux_rentabilite = $taux_rentabilite;
        $this->temps_retour_brut = $temps_retour_brut;
        $this->secteur = $secteur;
    }

    // Getters
    public function getIdProjet(): ?int { return $this->id_projet; }
    public function getTitre(): ?string { return $this->titre; }
    public function getDescription(): ?string { return $this->description; }
    public function getMontantObjectif(): ?float { return $this->montant_objectif; }
    public function getDateLimite(): ?string { return $this->date_limite; }
    public function getDateCreation(): ?string { return $this->date_creation; }
    public function getStatus(): ?string { return $this->status; }
    public function getIdCreateur(): ?int { return $this->id_createur; }
    public function getTauxRentabilite(): ?float { return $this->taux_rentabilite; }
    public function getTempsRetourBrut(): ?int { return $this->temps_retour_brut; }
    public function getSecteur(): ?string { return $this->secteur; }

    // Setters
    public function setIdProjet(?int $id_projet): void { $this->id_projet = $id_projet; }
    public function setTitre(?string $titre): void { $this->titre = $titre; }
    public function setDescription(?string $description): void { $this->description = $description; }
    public function setMontantObjectif(?float $montant_objectif): void { $this->montant_objectif = $montant_objectif; }
    public function setDateLimite(?string $date_limite): void { $this->date_limite = $date_limite; }
    public function setDateCreation(?string $date_creation): void { $this->date_creation = $date_creation; }
    public function setStatus(?string $status): void { $this->status = $status; }
    public function setIdCreateur(?int $id_createur): void { $this->id_createur = $id_createur; }
    public function setTauxRentabilite(?float $taux_rentabilite): void { $this->taux_rentabilite = $taux_rentabilite; }
    public function setTempsRetourBrut(?int $temps_retour_brut): void { $this->temps_retour_brut = $temps_retour_brut; }
    public function setSecteur(?string $secteur): void { $this->secteur = $secteur; }

    // Database Methods (l'apelle des fonctions)
    public static function getConnexion(): PDO
    {
        return Config::getConnexion();
    }

    public static function getAvailableProjects(): array
    {
        $sql = "SELECT * FROM projet WHERE status = 'VALIDE' OR status = 'EN_COURS'";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getInvestmentsByUser(int $userId): array
    {
        $sql = "SELECT * FROM projet p JOIN investissement i ON p.id_projet = i.id_projet WHERE i.id_investisseur = :userId";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getProjectRequestsByUser(int $userId): array
    {
        $sql = "SELECT * FROM projet WHERE id_createur = :userId";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getProjectById(int $projectId): ?array
    {
        $sql = "SELECT * FROM projet WHERE id_projet = :projectId";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['projectId' => $projectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function createProject(array $data): int
    {
        $sql = "INSERT INTO projet (titre, description, montant_objectif, date_limite, secteur, id_createur, status) 
                VALUES (:titre, :description, :montant_objectif, :date_limite, :secteur, :id_createur, 'EN_ATTENTE')";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute([
            'titre' => $data['titre'],
            'description' => $data['description'],
            'montant_objectif' => $data['montant_objectif'],
            'date_limite' => $data['date_limite'],
            'secteur' => $data['secteur'],
            'id_createur' => $data['id_createur']
        ]);
        return (int)self::getConnexion()->lastInsertId();
    }
}
