<?php
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Cagnotte.php';
require_once __DIR__ . '/AchievementService.php';

class CagnotteController
{
    private const ALLOWED_STATUSES = ['en_attente', 'acceptee', 'refusee', 'suspendue', 'cloturee'];

    private string $lastError = '';

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function isUserAssociation(int $userId): bool
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare("SELECT association FROM utilisateur WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        return (bool)($row['association'] ?? 0);
    }

    public function getUserById(int $userId): ?array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, association FROM utilisateur WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    private function fail(string $message): bool
    {
        $this->lastError = $message;
        return false;
    }

    private function normalizeStatus(string $status): string
    {
        $s = strtolower(trim($status));
        if ($s === 'en attente')  return 'en_attente';
        if ($s === 'active')      return 'acceptee';
        if ($s === 'acceptée')    return 'acceptee';
        if ($s === 'refusée')     return 'refusee';
        if ($s === 'inactive')    return 'suspendue';
        if ($s === 'terminee')    return 'cloturee';
        if ($s === 'clôturée')    return 'cloturee';
        return $s;
    }

    private function sanitizeStatus(string $status): string
    {
        $normalized = $this->normalizeStatus($status);
        return in_array($normalized, self::ALLOWED_STATUSES, true) ? $normalized : 'en_attente';
    }

    private function normalizeCategory(string $category): ?string
    {
        $c   = strtolower(trim($category));
        $map = [
            'sante'       => 'sante',  'santé'      => 'sante',
            'medical'     => 'sante',  'médical'    => 'sante',
            'education'   => 'education', 'éducation' => 'education',
            'solidarite'  => 'solidarite', 'solidarité' => 'solidarite',
            'humanitaire' => 'solidarite',
            'autre'       => 'autre',  'urgence'    => 'autre',
        ];
        return $map[$c] ?? null;
    }

    private function parseDateYmd(string $value): ?\DateTime
    {
        $raw = trim($value);
        if ($raw === '') return null;
        $dt = DateTime::createFromFormat('Y-m-d', $raw);
        if (!$dt || $dt->format('Y-m-d') !== $raw) return null;
        return $dt;
    }

    private function validateCagnottePayload(array $data): array|bool
    {
        $this->lastError = '';

        $titre       = trim((string)($data['titre'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $category    = $this->normalizeCategory((string)($data['categorie'] ?? ''));
        $objectif    = $data['objectif_montant'] ?? null;
        $dateDebut   = $this->parseDateYmd((string)($data['date_debut'] ?? ''));
        $dateFin     = $this->parseDateYmd((string)($data['date_fin'] ?? ''));

        if ($titre === '' || mb_strlen($titre) < 10)         return $this->fail("Titre invalide (minimum 10 caractères)");
        if ($description === '' || mb_strlen($description) < 30) return $this->fail("Description invalide (minimum 30 caractères)");
        if ($category === null)                               return $this->fail("Catégorie invalide");
        if (!is_numeric($objectif) || (float)$objectif < 100) return $this->fail("Objectif invalide (minimum 100)");
        if ($dateDebut === null)                              return $this->fail("Date de début invalide");
        if ($dateFin   === null)                              return $this->fail("Date de fin invalide");
        if ($dateFin < $dateDebut)                           return $this->fail("La date de fin doit être supérieure ou égale à la date de début");

        return [
            'titre'            => htmlspecialchars($titre),
            'description'      => htmlspecialchars($description),
            'categorie'        => $category,
            'objectif_montant' => (float)$objectif,
            'date_debut'       => $dateDebut->format('Y-m-d'),
            'date_fin'         => $dateFin->format('Y-m-d'),
        ];
    }

    private function normalizeCagnotteRow(?array $row): ?array
    {
        if (!is_array($row)) return $row;
        if (isset($row['statut'])) {
            $row['statut'] = $this->sanitizeStatus($row['statut']);
        }
        return $row;
    }

    private function normalizeCagnotteRows(array $rows): array
    {
        return array_map([$this, 'normalizeCagnotteRow'], $rows);
    }

    public function sanitizeCategoryFilter(string $category): string
    {
        $raw = trim($category);
        if ($raw === '') return '';
        return $this->normalizeCategory($raw) ?? '';
    }

    public function buildAdminFilters(array $filters = []): array
    {
        $status   = trim((string)($filters['status']   ?? ''));
        $query    = trim((string)($filters['query']    ?? ''));
        $category = $this->sanitizeCategoryFilter((string)($filters['category'] ?? ''));

        return [
            'status'   => $status === '' ? '' : $this->sanitizeStatus($status),
            'query'    => $query,
            'category' => $category,
        ];
    }

    public function ajouterCagnotte(array $data, int $userId): bool
    {
        $validated = $this->validateCagnottePayload($data);
        if ($validated === false) return false;

        if (!$this->isUserAssociation($userId)) {
            return $this->fail("Seules les associations peuvent créer une cagnotte");
        }

        $pdo  = Config::getConnexion();
        $sql  = "INSERT INTO cagnotte (id_createur, titre, description, categorie, objectif_montant, statut, date_debut, date_fin)
                 VALUES (:id_createur, :titre, :description, :categorie, :objectif_montant, 'en_attente', :date_debut, :date_fin)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_createur'      => $userId,
            'titre'            => $validated['titre'],
            'description'      => $validated['description'],
            'categorie'        => $validated['categorie'],
            'objectif_montant' => $validated['objectif_montant'],
            'date_debut'       => $validated['date_debut'],
            'date_fin'         => $validated['date_fin'],
        ]);

        try {
            (new AchievementService())->runPostCampaignCreated($userId);
        } catch (Throwable $e) {
            // Never break campaign creation on side-feature failures.
        }

        return true;
    }

    public function supprimerCagnotte(int $id): bool
    {
        $pdo = Config::getConnexion();
        $pdo->prepare("DELETE FROM don WHERE id_cagnotte = :id")->execute(['id' => $id]);
        return $pdo->prepare("DELETE FROM cagnotte WHERE id_cagnotte = :id")->execute(['id' => $id]);
    }

    public function modifierCagnotte(int $id, array $data): bool
    {
        $validated = $this->validateCagnottePayload($data);
        if ($validated === false) return false;

        $pdo  = Config::getConnexion();
        $sql  = "UPDATE cagnotte
                 SET titre = :titre, description = :description, categorie = :categorie,
                     objectif_montant = :objectif_montant, date_debut = :date_debut, date_fin = :date_fin
                 WHERE id_cagnotte = :id_cagnotte";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'titre'            => $validated['titre'],
            'description'      => $validated['description'],
            'categorie'        => $validated['categorie'],
            'objectif_montant' => $validated['objectif_montant'],
            'date_debut'       => $validated['date_debut'],
            'date_fin'         => $validated['date_fin'],
            'id_cagnotte'      => $id,
        ]);
    }

    public function getAllCagnottes(?string $status = null): array
    {
        return $this->getFilteredCagnottes(['status' => $status]);
    }

    public function getFilteredCagnottes(array $filters = []): array
    {
        $pdo     = Config::getConnexion();
        $filters = $this->buildAdminFilters($filters);

        $sql    = "SELECT c.*, u.nom, u.prenom, u.association,
                          COALESCE(SUM(d.montant), 0) AS total_collecte,
                          COUNT(d.id_don) AS nb_dons
                   FROM cagnotte c
                   LEFT JOIN utilisateur u ON c.id_createur = u.id
                   LEFT JOIN don d ON c.id_cagnotte = d.id_cagnotte AND d.statut = 'confirme' ";
        $where  = [];
        $params = [];

        if ($filters['status'] !== '') {
            $where[]           = "c.statut = :statut";
            $params['statut']  = $filters['status'];
        }
        if ($filters['category'] !== '') {
            $where[]             = "c.categorie = :categorie";
            $params['categorie'] = $filters['category'];
        }
        if ($filters['query'] !== '') {
            $where[] = "(c.titre LIKE :q_titre OR c.categorie LIKE :q_cat
                         OR CONCAT(COALESCE(u.nom,''),' ',COALESCE(u.prenom,'')) LIKE :q_cr
                         OR CONCAT(COALESCE(u.prenom,''),' ',COALESCE(u.nom,'')) LIKE :q_cr_inv)";
            $qv                  = '%' . $filters['query'] . '%';
            $params['q_titre']   = $qv;
            $params['q_cat']     = $qv;
            $params['q_cr']      = $qv;
            $params['q_cr_inv']  = $qv;
        }
        if (!empty($where)) {
            $sql .= "WHERE " . implode(' AND ', $where) . " ";
        }
        $sql .= "GROUP BY c.id_cagnotte ORDER BY c.date_debut DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $this->normalizeCagnotteRows($stmt->fetchAll());
    }

    public function getCagnotteStatusCounts(): array
    {
        $pdo    = Config::getConnexion();
        $stmt   = $pdo->query("SELECT statut, COUNT(*) as total FROM cagnotte GROUP BY statut");
        $counts = ['en_attente' => 0, 'acceptee' => 0, 'refusee' => 0, 'suspendue' => 0, 'cloturee' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $s          = $this->sanitizeStatus($row['statut'] ?? 'en_attente');
            $counts[$s] = (int)($row['total'] ?? 0);
        }
        return $counts;
    }

    public function getCagnotteCategoryCounts(): array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->query("SELECT categorie, COUNT(*) as total FROM cagnotte GROUP BY categorie ORDER BY total DESC");
        $out  = [];
        foreach ($stmt->fetchAll() as $row) {
            $key   = $this->sanitizeCategoryFilter($row['categorie'] ?? 'autre') ?: 'autre';
            $out[] = ['categorie' => $key, 'total' => (int)($row['total'] ?? 0)];
        }
        return $out;
    }

    public function getAdminOverviewStats(): array
    {
        $pdo  = Config::getConnexion();
        $stmt = $pdo->query("SELECT COUNT(*) as total_cagnottes,
                                    COALESCE(SUM(objectif_montant),0) as total_objectif,
                                    COALESCE(SUM(montant_collecte),0) as total_collecte
                             FROM cagnotte");
        $row  = $stmt->fetch() ?: [];
        return [
            'total_cagnottes' => (int)($row['total_cagnottes'] ?? 0),
            'total_objectif'  => (float)($row['total_objectif'] ?? 0),
            'total_collecte'  => (float)($row['total_collecte'] ?? 0),
        ];
    }

    public function getUserCagnottes(int $userId): array
    {
        $pdo  = Config::getConnexion();
        $sql  = "SELECT c.*, u.association,
                        COALESCE(SUM(d.montant), 0) AS total_collecte,
                        COUNT(d.id_don) AS nb_dons
                 FROM cagnotte c
                 LEFT JOIN utilisateur u ON c.id_createur = u.id
                 LEFT JOIN don d ON c.id_cagnotte = d.id_cagnotte AND d.statut = 'confirme'
                 WHERE c.id_createur = :userId
                 GROUP BY c.id_cagnotte ORDER BY c.date_debut DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $this->normalizeCagnotteRows($stmt->fetchAll());
    }

    public function getCagnotteById(int $id): ?array
    {
        $pdo  = Config::getConnexion();
        $sql  = "SELECT c.*, u.nom, u.prenom, u.association,
                        COALESCE(SUM(d.montant), 0) AS total_collecte,
                        COUNT(d.id_don) AS nb_dons
                 FROM cagnotte c
                 LEFT JOIN utilisateur u ON c.id_createur = u.id
                 LEFT JOIN don d ON c.id_cagnotte = d.id_cagnotte AND d.statut = 'confirme'
                 WHERE c.id_cagnotte = :id
                 GROUP BY c.id_cagnotte";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $this->normalizeCagnotteRow($stmt->fetch() ?: null);
    }

    public function updateStatusAdmin(int $id, string $newStatus): bool
    {
        $newStatus = $this->normalizeStatus($newStatus);
        if (!in_array($newStatus, self::ALLOWED_STATUSES, true)) return false;

        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare("UPDATE cagnotte SET statut = :statut WHERE id_cagnotte = :id");
        return $stmt->execute(['statut' => $newStatus, 'id' => $id]);
    }

    public function updateStatusUser(int $id, string $newStatus, int $userId): bool
    {
        $newStatus = $this->normalizeStatus($newStatus);
        $allowed   = ['suspendue', 'cloturee'];
        if (!in_array($newStatus, $allowed, true)) {
            return $this->fail("Statut non autorisé pour l'utilisateur");
        }
        $pdo  = Config::getConnexion();
        $stmt = $pdo->prepare("UPDATE cagnotte SET statut = :statut WHERE id_cagnotte = :id AND id_createur = :uid");
        return $stmt->execute(['statut' => $newStatus, 'id' => $id, 'uid' => $userId]);
    }
}
