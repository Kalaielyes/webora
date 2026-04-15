<?php

require_once __DIR__ . '/config.php';

class Investissement
{
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
        return $stmt->fetchAll();
    }

    public static function getInvestmentById(int $investmentId): ?array
    {
        $sql = "SELECT i.id_investissement, i.id_projet, p.titre AS projet_titre, i.id_investisseur, i.montant_investi, i.date_investissement, i.status, i.commentaire
                FROM investissement i
                LEFT JOIN projet p ON i.id_projet = p.id_projet
                WHERE i.id_investissement = :investmentId";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['investmentId' => $investmentId]);
        $result = $stmt->fetch();
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
        return $stmt->fetchAll();
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
                WHERE id_projet = :projectId AND status = 'APPROUVE'";
        $stmt = self::getConnexion()->prepare($sql);
        $stmt->execute(['projectId' => $projectId]);
        $result = $stmt->fetch();
        return (float)($result['total_invested'] ?? 0);
    }
}
