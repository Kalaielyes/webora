<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/recommendation_ai.php';

// ═══════════════════════════════════════════════════════════════════
//  Investissement — investment CRUD & related DB queries
// ═══════════════════════════════════════════════════════════════════

class Investissement
{
    private ?int $id_investissement;
    private ?int $id_projet;
    private ?int $id_investisseur;
    private ?float $montant_investi;
    private ?string $date_investissement;
    private ?string $status;
    private ?string $commentaire;

    public function __construct(
        ?int $id_investissement = null,
        ?int $id_projet = null,
        ?int $id_investisseur = null,
        ?float $montant_investi = null,
        ?string $date_investissement = null,
        ?string $status = null,
        ?string $commentaire = null
    ) {
        $this->id_investissement = $id_investissement;
        $this->id_projet = $id_projet;
        $this->id_investisseur = $id_investisseur;
        $this->montant_investi = $montant_investi;
        $this->date_investissement = $date_investissement;
        $this->status = $status;
        $this->commentaire = $commentaire;
    }

    // Getters
    public function getIdInvestissement(): ?int { return $this->id_investissement; }
    public function getIdProjet(): ?int { return $this->id_projet; }
    public function getIdInvestisseur(): ?int { return $this->id_investisseur; }
    public function getMontantInvesti(): ?float { return $this->montant_investi; }
    public function getDateInvestissement(): ?string { return $this->date_investissement; }
    public function getStatus(): ?string { return $this->status; }
    public function getCommentaire(): ?string { return $this->commentaire; }

    // Setters
    public function setIdInvestissement(?int $v): void { $this->id_investissement = $v; }
    public function setIdProjet(?int $v): void { $this->id_projet = $v; }
    public function setIdInvestisseur(?int $v): void { $this->id_investisseur = $v; }
    public function setMontantInvesti(?float $v): void { $this->montant_investi = $v; }
    public function setDateInvestissement(?string $v): void { $this->date_investissement = $v; }
    public function setStatus(?string $v): void { $this->status = $v; }
    public function setCommentaire(?string $v): void { $this->commentaire = $v; }

    // ── DB helpers ───────────────────────────────────────────────────

    public static function getConnexion(): PDO
    {
        return Config::getConnexion();
    }

    public static function createInvestment(array $data): int
    {
        $sql = "INSERT INTO investissement (id_projet, id_investisseur, montant_investi, date_investissement, status, commentaire)
                VALUES (:projectId, :investisseurId, :montant, CURDATE(), :status, :commentaire)";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute([
            'projectId'     => $data['project_id'],
            'investisseurId'=> $data['investisseur_id'],
            'montant'       => $data['montant'],
            'status'        => $data['status'],
            'commentaire'   => $data['commentaire'] ?? '',
        ]);
        return (int)self::getConnexion()->lastInsertId();
    }

    public static function getInvestmentsByUser(int $userId): array
    {
        $sql = "SELECT i.id_investissement, i.id_projet, p.titre AS projet_titre,
                       i.montant_investi, i.date_investissement, i.status, i.commentaire,
                       COALESCE(pp_latest.pourcentage, 0)  AS progress_pourcentage,
                       COALESCE(pp_latest.description, '') AS progress_description,
                       pp_latest.date_update               AS progress_date_update
                FROM investissement i
                LEFT JOIN projet p ON i.id_projet = p.id_projet
                LEFT JOIN (
                    SELECT pp1.projet_id, pp1.pourcentage, pp1.description, pp1.date_update
                    FROM projet_progress pp1
                    INNER JOIN (
                        SELECT projet_id, MAX(id) AS max_id
                        FROM projet_progress
                        GROUP BY projet_id
                    ) latest ON latest.projet_id = pp1.projet_id AND latest.max_id = pp1.id
                ) pp_latest ON pp_latest.projet_id = i.id_projet
                WHERE i.id_investisseur = :userId
                ORDER BY i.date_investissement DESC";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getInvestmentById(int $investmentId): ?array
    {
        $sql = "SELECT i.id_investissement, i.id_projet, p.titre AS projet_titre,
                       i.id_investisseur, i.montant_investi, i.date_investissement,
                       i.status, i.commentaire
                FROM investissement i
                LEFT JOIN projet p ON i.id_projet = p.id_projet
                WHERE i.id_investissement = :investmentId";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['investmentId' => $investmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public static function updateInvestment(int $investmentId, int $userId, array $data): bool
    {
        $sql = "UPDATE investissement
                SET id_projet       = :projectId,
                    montant_investi = :montant,
                    status          = :status,
                    commentaire     = :commentaire
                WHERE id_investissement = :investmentId
                  AND id_investisseur   = :userId";
        $stmt = self::getConnexion()->prepare($sql);
        return $stmt->execute([
            'projectId'     => $data['project_id'],
            'montant'       => $data['montant'],
            'status'        => $data['status'],
            'commentaire'   => $data['commentaire'] ?? '',
            'investmentId'  => $investmentId,
            'userId'        => $userId,
        ]);
    }

    public static function deleteInvestment(int $investmentId, int $userId): bool
    {
        $sql = "DELETE FROM investissement
                WHERE id_investissement = :investmentId AND id_investisseur = :userId";
        $stmt = self::getConnexion()->prepare($sql);
        return $stmt->execute(['investmentId' => $investmentId, 'userId' => $userId]);
    }

    public static function getAllInvestments(): array
    {
        $sql = "SELECT i.id_investissement, i.id_projet, p.titre AS projet_titre,
                       i.id_investisseur, u.nom, u.prenom, u.email,
                       i.montant_investi, i.date_investissement, i.status, i.commentaire
                FROM investissement i
                LEFT JOIN projet p ON i.id_projet = p.id_projet
                LEFT JOIN utilisateur u ON i.id_investisseur = u.id
                ORDER BY i.date_investissement DESC";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateInvestmentStatus(int $investmentId, string $newStatus): bool
    {
        $sql = "UPDATE investissement SET status = :newStatus WHERE id_investissement = :investmentId";
        $stmt = self::getConnexion()->prepare($sql);
        return $stmt->execute(['newStatus' => $newStatus, 'investmentId' => $investmentId]);
    }

    public static function getTotalInvestedForProject(int $projectId): float
    {
        $sql = "SELECT SUM(montant_investi) AS total_invested
                FROM investissement
                WHERE id_projet = :projectId AND status = 'VALIDE'";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['projectId' => $projectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_invested'] ?? 0);
    }
}

// ═══════════════════════════════════════════════════════════════════
//  Projet — project CRUD, recommendations & related DB queries
// ═══════════════════════════════════════════════════════════════════

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
    // Extended fields
    private ?string $icon_path;
    private ?float $progress;
    private ?string $progress_description;

    public function __construct(
        ?int    $id_projet          = null,
        ?string $titre              = null,
        ?string $description        = null,
        ?float  $montant_objectif   = null,
        ?string $date_limite        = null,
        ?string $date_creation      = null,
        ?string $status             = null,
        ?int    $id_createur        = null,
        ?float  $taux_rentabilite   = null,
        ?int    $temps_retour_brut  = null,
        ?string $secteur            = null
    ) {
        $this->id_projet         = $id_projet;
        $this->titre             = $titre;
        $this->description       = $description;
        $this->montant_objectif  = $montant_objectif;
        $this->date_limite       = $date_limite;
        $this->date_creation     = $date_creation;
        $this->status            = $status;
        $this->id_createur       = $id_createur;
        $this->taux_rentabilite  = $taux_rentabilite;
        $this->temps_retour_brut = $temps_retour_brut;
        $this->secteur           = $secteur;
    }

    // Getters
    public function getIdProjet(): ?int           { return $this->id_projet; }
    public function getTitre(): ?string           { return $this->titre; }
    public function getDescription(): ?string     { return $this->description; }
    public function getMontantObjectif(): ?float  { return $this->montant_objectif; }
    public function getDateLimite(): ?string      { return $this->date_limite; }
    public function getDateCreation(): ?string    { return $this->date_creation; }
    public function getStatus(): ?string          { return $this->status; }
    public function getIdCreateur(): ?int         { return $this->id_createur; }
    public function getTauxRentabilite(): ?float  { return $this->taux_rentabilite; }
    public function getTempsRetourBrut(): ?int    { return $this->temps_retour_brut; }
    public function getSecteur(): ?string         { return $this->secteur; }
    public function getIconPath(): ?string        { return $this->icon_path; }
    public function getProgress(): ?float         { return $this->progress; }
    public function getProgressDescription(): ?string { return $this->progress_description; }

    // Setters
    public function setIdProjet(?int $v): void            { $this->id_projet = $v; }
    public function setTitre(?string $v): void            { $this->titre = $v; }
    public function setDescription(?string $v): void      { $this->description = $v; }
    public function setMontantObjectif(?float $v): void   { $this->montant_objectif = $v; }
    public function setDateLimite(?string $v): void       { $this->date_limite = $v; }
    public function setDateCreation(?string $v): void     { $this->date_creation = $v; }
    public function setStatus(?string $v): void           { $this->status = $v; }
    public function setIdCreateur(?int $v): void          { $this->id_createur = $v; }
    public function setTauxRentabilite(?float $v): void   { $this->taux_rentabilite = $v; }
    public function setTempsRetourBrut(?int $v): void     { $this->temps_retour_brut = $v; }
    public function setSecteur(?string $v): void          { $this->secteur = $v; }
    public function setIconPath(?string $v): void         { $this->icon_path = $v; }
    public function setProgress(?float $v): void          { $this->progress = $v; }
    public function setProgressDescription(?string $v): void { $this->progress_description = $v; }

    // ── DB helpers ───────────────────────────────────────────────────

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
        $sql = "SELECT * FROM projet p
                JOIN investissement i ON p.id_projet = i.id_projet
                WHERE i.id_investisseur = :userId";
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
            'titre'            => $data['titre'],
            'description'      => $data['description'],
            'montant_objectif' => $data['montant_objectif'],
            'date_limite'      => $data['date_limite'],
            'secteur'          => $data['secteur'],
            'id_createur'      => $data['id_createur'],
        ]);
        return (int)self::getConnexion()->lastInsertId();
    }

    public static function getRecommendedProjectsForUser(int $userId): array
    {
        $conn = self::getConnexion();

        $sqlSectors = "SELECT DISTINCT p.secteur
                       FROM investissement i
                       JOIN projet p ON i.id_projet = p.id_projet
                       WHERE i.id_investisseur = :userId
                         AND p.secteur IS NOT NULL AND p.secteur != ''";
        $stmtSectors = $conn->prepare($sqlSectors);
        $stmtSectors->execute(['userId' => $userId]);
        $sectors = $stmtSectors->fetchAll(PDO::FETCH_COLUMN);

        $sqlProjects = "SELECT p.*, u.nom AS createur_nom
                        FROM projet p
                        LEFT JOIN utilisateur u ON p.id_createur = u.id
                        WHERE p.status = 'VALIDE'
                        ORDER BY p.taux_rentabilite DESC, p.temps_retour_brut ASC
                        LIMIT 20";
        $stmt = $conn->prepare($sqlProjects);
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($projects)) {
            return [];
        }

        $sqlUserInvest = "SELECT COUNT(*) AS total_count,
                                 COALESCE(SUM(montant_investi), 0) AS total_amount,
                                 COALESCE(AVG(montant_investi), 0) AS avg_amount
                          FROM investissement
                          WHERE id_investisseur = :userId";
        $stmtUserInvest = $conn->prepare($sqlUserInvest);
        $stmtUserInvest->execute(['userId' => $userId]);
        $investStats = $stmtUserInvest->fetch(PDO::FETCH_ASSOC) ?: [
            'total_count'  => 0,
            'total_amount' => 0,
            'avg_amount'   => 0,
        ];

        $userProfile = [
            'user_id'           => $userId,
            'preferred_sectors' => array_values(array_filter($sectors, static fn($s) => is_string($s) && $s !== '')),
            'investment_count'  => (int)($investStats['total_count']  ?? 0),
            'total_invested'    => (float)($investStats['total_amount'] ?? 0),
            'average_ticket'    => (float)($investStats['avg_amount']  ?? 0),
        ];

        $rankedProjects = RecommendationAI::rankProjectsForUser($userProfile, $projects);
        return array_slice($rankedProjects, 0, 10);
    }
}
