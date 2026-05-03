<?php
require_once __DIR__ . "/../model/cagnotte.php";
require_once __DIR__ . "/../model/config.php";
require_once __DIR__ . "/AchievementService.php";

class cagnottecontroller {

    private const ALLOWED_STATUSES = ['en_attente', 'acceptee', 'refusee', 'suspendue', 'cloturee'];

    private $lastError = '';

    public function getLastError() {
        return $this->lastError;
    }

    public function isUserAssociation($userId) {
        if (!is_numeric($userId)) return false;
        $pdo = Config::getConnexion();
        $stmt = $pdo->prepare("SELECT association FROM utilisateur WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$userId]);
        $row = $stmt->fetch();
        return (bool)($row['association'] ?? 0);
    }

    public function getUserById($userId) {
        if (!is_numeric($userId)) return null;
        $pdo = Config::getConnexion();
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, association FROM utilisateur WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getSelectableUsers() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT id, nom, prenom, email, association FROM utilisateur ORDER BY prenom ASC, nom ASC, id ASC");
        return $stmt->fetchAll();
    }

    private function fail($message) {
        $this->lastError = $message;
        return false;
    }

    private function normalizeStatus($status) {
        $s = strtolower(trim((string)$status));
        if ($s === 'en attente') return 'en_attente';
        if ($s === 'active') return 'acceptee';
        if ($s === 'acceptée') return 'acceptee';
        if ($s === 'refusée') return 'refusee';
        if ($s === 'inactive') return 'suspendue';
        if ($s === 'terminee') return 'cloturee';
        if ($s === 'clôturée') return 'cloturee';
        return $s;
    }

    private function sanitizeStatus($status) {
        $normalized = $this->normalizeStatus($status);
        return in_array($normalized, self::ALLOWED_STATUSES, true) ? $normalized : 'en_attente';
    }

    private function normalizeCategory($category) {
        $c = strtolower(trim((string)$category));
        $map = [
            'sante' => 'sante',
            'santé' => 'sante',
            'medical' => 'sante',
            'médical' => 'sante',
            'education' => 'education',
            'éducation' => 'education',
            'solidarite' => 'solidarite',
            'solidarité' => 'solidarite',
            'humanitaire' => 'solidarite',
            'autre' => 'autre',
            'urgence' => 'autre'
        ];
        return $map[$c] ?? null;
    }

    private function parseDateYmd($value) {
        $raw = trim((string)$value);
        if ($raw === '') return null;
        $dt = DateTime::createFromFormat('Y-m-d', $raw);
        if (!$dt || $dt->format('Y-m-d') !== $raw) return null;
        return $dt;
    }

    private function validateCagnottePayload($data, $isCreate = true) {
        $this->lastError = '';

        $titre = trim((string)($data['titre'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $category = $this->normalizeCategory($data['categorie'] ?? '');
        $objectif = $data['objectif_montant'] ?? null;
        $dateDebut = $this->parseDateYmd($data['date_debut'] ?? '');
        $dateFin = $this->parseDateYmd($data['date_fin'] ?? '');

        if ($titre === '' || mb_strlen($titre) < 10) return $this->fail("Titre invalide (minimum 10 caractères)");
        if ($description === '' || mb_strlen($description) < 30) return $this->fail("Description invalide (minimum 30 caractères)");
        if ($category === null) return $this->fail("Catégorie invalide");
        if (!is_numeric($objectif) || (float)$objectif < 100) return $this->fail("Objectif invalide (minimum 100)");
        if ($dateDebut === null) return $this->fail("Date de début invalide");
        if ($dateFin === null) return $this->fail("Date de fin invalide");

        if ($dateFin < $dateDebut) return $this->fail("La date de fin doit être supérieure ou égale à la date de début");

        return [
            'titre' => htmlspecialchars($titre),
            'description' => htmlspecialchars($description),
            'categorie' => $category,
            'objectif_montant' => (float)$objectif,
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d')
        ];
    }

    private function normalizeCagnotteRow($row) {
        if (!is_array($row)) return $row;
        if (isset($row['statut'])) {
            $row['statut'] = $this->sanitizeStatus($row['statut']);
        }
        return $row;
    }

    private function normalizeCagnotteRows($rows) {
        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->normalizeCagnotteRow($r);
        }
        return $out;
    }

    public function sanitizeCategoryFilter($category) {
        $raw = trim((string)$category);
        if ($raw === '') return '';
        return $this->normalizeCategory($raw) ?? '';
    }

    public function buildAdminFilters($filters = []) {
        $status = trim((string)($filters['status'] ?? ''));
        $query = trim((string)($filters['query'] ?? ''));
        $category = $this->sanitizeCategoryFilter($filters['category'] ?? '');

        return [
            'status' => $status === '' ? '' : $this->sanitizeStatus($status),
            'query' => $query,
            'category' => $category
        ];
    }

    public function ensureDefaultUser() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE nom = 'Doe' AND prenom = 'John' LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            return $user['id'];
        }
        
        $sql = "INSERT INTO utilisateur (nom, prenom, email, mdp, numTel, date_naissance, adresse, cin, role, association) 
            VALUES ('Doe', 'John', 'john.doe@example.com', 'password123', '00000000', '1990-01-01', '123 Placeholder St', '00000000', 'CLIENT', 1)";
        $pdo->exec($sql);
        return $pdo->lastInsertId();
    }

    public function ajouterCagnotte($data, $userId = null) {
        $validated = $this->validateCagnottePayload($data);
        if ($validated === false) return false;

        $id_createur = is_numeric($userId) ? (int)$userId : (int)$this->ensureDefaultUser();
        if (!$this->isUserAssociation($id_createur)) {
            return $this->fail("Seules les associations peuvent créer une cagnotte");
        }
        $pdo = Config::getConnexion();

        $sql = "INSERT INTO cagnotte (id_createur, titre, description, categorie, objectif_montant, statut, date_debut, date_fin)
            VALUES (:id_createur, :titre, :description, :categorie, :objectif_montant, 'en_attente', :date_debut, :date_fin)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_createur' => $id_createur,
            'titre' => $validated['titre'],
            'description' => $validated['description'],
            'categorie' => $validated['categorie'],
            'objectif_montant' => $validated['objectif_montant'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin']
        ]);

        try {
            $achievementService = new AchievementService();
            $achievementService->runPostCampaignCreated($id_createur);
        } catch (Throwable $e) {
            // Never break campaign creation on side-feature failures.
        }
        
        return true;
    }

    public function supprimerCagnotte($id) {
        if (!is_numeric($id)) return false;
        $pdo = Config::getConnexion();
        
        $stmtDon = $pdo->prepare("DELETE FROM don WHERE id_cagnotte = :id");
        $stmtDon->execute(['id' => $id]);

        $sql = "DELETE FROM cagnotte WHERE id_cagnotte = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function modifierCagnotte($id, $data) {
        if (!is_numeric($id)) return $this->fail("ID invalide");
        $validated = $this->validateCagnottePayload($data, false);
        if ($validated === false) return false;

        $pdo = Config::getConnexion();
        $sql = "UPDATE cagnotte 
                SET titre = :titre,
                    description = :description,
                    categorie = :categorie,
                    objectif_montant = :objectif_montant,
                    date_debut = :date_debut,
                    date_fin = :date_fin
                WHERE id_cagnotte = :id_cagnotte";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'titre' => $validated['titre'],
            'description' => $validated['description'],
            'categorie' => $validated['categorie'],
            'objectif_montant' => $validated['objectif_montant'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'id_cagnotte' => (int)$id
        ]);
    }

    public function getAllCagnottes($status = null) {
        return $this->getFilteredCagnottes(['status' => $status]);
    }

    public function getFilteredCagnottes($filters = []) {
        $pdo = Config::getConnexion();
        $filters = $this->buildAdminFilters($filters);
        $sql = "SELECT c.*, u.nom, u.prenom, u.association, COALESCE(SUM(d.montant), 0) as total_collecte, COUNT(d.id_don) as nb_dons 
                FROM cagnotte c 
                LEFT JOIN utilisateur u ON c.id_createur = u.id 
                LEFT JOIN don d ON c.id_cagnotte = d.id_cagnotte AND d.statut = 'confirme' ";
        $where = [];
        $params = [];

        if ($filters['status'] !== '') {
            $where[] = "c.statut = :statut";
            $params['statut'] = $filters['status'];
        }
        if ($filters['category'] !== '') {
            $where[] = "c.categorie = :categorie";
            $params['categorie'] = $filters['category'];
        }
        if ($filters['query'] !== '') {
            $where[] = "(c.titre LIKE :query_titre OR c.categorie LIKE :query_categorie OR CONCAT(COALESCE(u.nom, ''), ' ', COALESCE(u.prenom, '')) LIKE :query_createur OR CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) LIKE :query_createur_inv)";
            $queryValue = '%' . $filters['query'] . '%';
            $params['query_titre'] = $queryValue;
            $params['query_categorie'] = $queryValue;
            $params['query_createur'] = $queryValue;
            $params['query_createur_inv'] = $queryValue;
        }
        if (!empty($where)) {
            $sql .= "WHERE " . implode(' AND ', $where) . " ";
        }
        $sql .= "GROUP BY c.id_cagnotte ORDER BY c.date_debut DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $this->normalizeCagnotteRows($stmt->fetchAll());
    }

    public function getCagnotteStatusCounts() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT statut, COUNT(*) as total FROM cagnotte GROUP BY statut");
        $counts = [
            'en_attente' => 0,
            'acceptee' => 0,
            'refusee' => 0,
            'suspendue' => 0,
            'cloturee' => 0
        ];
        foreach ($stmt->fetchAll() as $row) {
            $status = $this->sanitizeStatus($row['statut'] ?? 'en_attente');
            $counts[$status] = (int)($row['total'] ?? 0);
        }
        return $counts;
    }

    public function getCagnotteCategoryCounts() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT categorie, COUNT(*) as total FROM cagnotte GROUP BY categorie ORDER BY total DESC, categorie ASC");
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $key = $this->sanitizeCategoryFilter($row['categorie'] ?? 'autre');
            if ($key === '') {
                $key = 'autre';
            }
            $out[] = [
                'categorie' => $key,
                'total' => (int)($row['total'] ?? 0)
            ];
        }
        return $out;
    }

    public function getAdminOverviewStats() {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT COUNT(*) as total_cagnottes, COALESCE(SUM(objectif_montant), 0) as total_objectif, COALESCE(SUM(montant_collecte), 0) as total_collecte FROM cagnotte");
        $row = $stmt->fetch() ?: [];
        return [
            'total_cagnottes' => (int)($row['total_cagnottes'] ?? 0),
            'total_objectif' => (float)($row['total_objectif'] ?? 0),
            'total_collecte' => (float)($row['total_collecte'] ?? 0)
        ];
    }

    public function getUserCagnottes($userId) {
        $pdo = Config::getConnexion();
        $sql = "SELECT c.*, u.association, COALESCE(SUM(d.montant), 0) as total_collecte, COUNT(d.id_don) as nb_dons 
                FROM cagnotte c 
            LEFT JOIN utilisateur u ON c.id_createur = u.id
                LEFT JOIN don d ON c.id_cagnotte = d.id_cagnotte AND d.statut = 'confirme' 
                WHERE c.id_createur = :userId 
                GROUP BY c.id_cagnotte ORDER BY c.date_debut DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $this->normalizeCagnotteRows($stmt->fetchAll());
    }

    public function getCagnotteById($id) {
        $pdo = Config::getConnexion();
        $sql = "SELECT c.*, u.nom, u.prenom, u.association, COALESCE(SUM(d.montant), 0) as total_collecte, COUNT(d.id_don) as nb_dons 
                FROM cagnotte c 
                LEFT JOIN utilisateur u ON c.id_createur = u.id 
                LEFT JOIN don d ON c.id_cagnotte = d.id_cagnotte AND d.statut = 'confirme' 
                WHERE c.id_cagnotte = :id
                GROUP BY c.id_cagnotte";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $this->normalizeCagnotteRow($stmt->fetch());
    }

    public function updateStatusAdmin($id, $newStatus) {
        $newStatus = $this->normalizeStatus($newStatus);
        if (!in_array($newStatus, self::ALLOWED_STATUSES, true)) return false;
        
        $pdo = Config::getConnexion();
        
        // Get campaign creator before updating status
        $stmt = $pdo->prepare("SELECT id_createur FROM cagnotte WHERE id_cagnotte = :id");
        $stmt->execute(['id' => $id]);
        $campaign = $stmt->fetch();
        
        $sql = "UPDATE cagnotte SET statut = :statut WHERE id_cagnotte = :id";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['statut' => $newStatus, 'id' => $id]);
        
        // Trigger achievement check if campaign is marked as funded
        if ($result && $newStatus === 'financee' && $campaign) {
            try {
                require_once __DIR__ . '/AchievementService.php';
                $achievementService = new AchievementService();
                $achievementService->checkCampaignFundedAchievements((int)$id, (int)$campaign['id_createur']);
            } catch (Throwable $e) {
                // Keep status update successful even if achievement processing fails
            }
        }
        
        return $result;
    }

    public function updateStatusUser($id, $newStatus, $userId) {
        $newStatus = $this->normalizeStatus($newStatus);
        if (!in_array($newStatus, ['acceptee', 'suspendue'], true)) return false;
        
        $pdo = Config::getConnexion();
        $stmt = $pdo->prepare("SELECT statut FROM cagnotte WHERE id_cagnotte = :id AND id_createur = :userId");
        $stmt->execute(['id' => $id, 'userId' => $userId]);
        $cagnotte = $stmt->fetch();
        
        $current = $cagnotte ? $this->sanitizeStatus($cagnotte['statut']) : '';
        if (!$cagnotte || $current === 'en_attente' || $current === 'refusee') {
            return false;
        }

        $sql = "UPDATE cagnotte SET statut = :statut WHERE id_cagnotte = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['statut' => $newStatus, 'id' => $id]);
    }
}
?>