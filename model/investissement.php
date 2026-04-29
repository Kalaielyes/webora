<?php

require_once __DIR__ . '/config.php';

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
    public function setIdInvestissement(?int $id_investissement): void { $this->id_investissement = $id_investissement; }
    public function setIdProjet(?int $id_projet): void { $this->id_projet = $id_projet; }
    public function setIdInvestisseur(?int $id_investisseur): void { $this->id_investisseur = $id_investisseur; }
    public function setMontantInvesti(?float $montant_investi): void { $this->montant_investi = $montant_investi; }
    public function setDateInvestissement(?string $date_investissement): void { $this->date_investissement = $date_investissement; }
    public function setStatus(?string $status): void { $this->status = $status; }
    public function setCommentaire(?string $commentaire): void { $this->commentaire = $commentaire; }

    // Database Methods (l'apelle des fonctions)
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
            'projectId' => $data['project_id'],
            'investisseurId' => $data['investisseur_id'],
            'montant' => $data['montant'],
            'status' => $data['status'],
            'commentaire' => $data['commentaire'] ?? '',
        ]);

        return (int)self::getConnexion()->lastInsertId();
    }

    public static function getInvestmentsByUser(int $userId): array
    {
        $sql = "SELECT i.id_investissement, i.id_projet, p.titre AS projet_titre, i.montant_investi, i.date_investissement, i.status, i.commentaire
                FROM investissement i
                LEFT JOIN projet p ON i.id_projet = p.id_projet
                WHERE i.id_investisseur = :userId
                ORDER BY i.date_investissement DESC";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getInvestmentById(int $investmentId): ?array
    {
        $sql = "SELECT i.id_investissement, i.id_projet, p.titre AS projet_titre, i.id_investisseur, i.montant_investi, i.date_investissement, i.status, i.commentaire
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
                SET id_projet = :projectId,
                    montant_investi = :montant,
                    status = :status,
                    commentaire = :commentaire
                WHERE id_investissement = :investmentId
                  AND id_investisseur = :userId";
        $stmt = self::getConnexion()->prepare($sql);
        return $stmt->execute([
            'projectId' => $data['project_id'],
            'montant' => $data['montant'],
            'status' => $data['status'],
            'commentaire' => $data['commentaire'] ?? '',
            'investmentId' => $investmentId,
            'userId' => $userId,
        ]);
    }

    public static function deleteInvestment(int $investmentId, int $userId): bool
    {
        $sql = "DELETE FROM investissement WHERE id_investissement = :investmentId AND id_investisseur = :userId";
        $stmt = self::getConnexion()->prepare($sql);
        return $stmt->execute(['investmentId' => $investmentId, 'userId' => $userId]);
    }

    public static function getAllInvestments(): array
    {
        $sql = "SELECT i.id_investissement, i.id_projet, p.titre AS projet_titre, i.id_investisseur, u.nom, u.prenom, u.email,
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
        $sql = "SELECT SUM(montant_investi) as total_invested
                FROM investissement
                WHERE id_projet = :projectId AND status = 'VALIDE'";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['projectId' => $projectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_invested'] ?? 0);
    }
}
