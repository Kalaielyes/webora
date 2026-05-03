<?php
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/AchievementModel.php';

class AchievementService
{
    private PDO $pdo;
    private AchievementModel $achievementModel;

    public function __construct()
    {
        $this->pdo = Config::getConnexion();
        $this->achievementModel = new AchievementModel();
    }

    public function runPostDonationCreated(int $donateurId): void
    {
        // Unlock timing is intentionally deferred until donation confirmation.
        if ($donateurId > 0) {
            // no-op for now
        }
    }

    public function runPostCampaignCreated(int $associationUserId): void
    {
        if ($associationUserId <= 0) {
            return;
        }
        $this->checkAssociationAchievements($associationUserId);
    }

    public function runPostDonationConfirmed(int $donId): void
    {
        if ($donId <= 0) {
            return;
        }

        $sql = "SELECT d.id_donateur, d.id_cagnotte, c.id_createur
                FROM don d
                INNER JOIN cagnotte c ON c.id_cagnotte = d.id_cagnotte
                WHERE d.id_don = :id_don
                  AND d.statut = 'confirme'
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_don' => $donId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }

        $donateurId = (int)$row['id_donateur'];
        $idCagnotte = (int)$row['id_cagnotte'];
        $associationUserId = (int)$row['id_createur'];

        if ($donateurId > 0) {
            $this->checkDonorAchievements($donateurId);
        }

        if ($associationUserId > 0) {
            $this->checkAssociationAchievements($associationUserId);
            $this->checkCampaignFundedAchievements($idCagnotte, $associationUserId);
        }
    }

    public function checkDonorAchievements(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $metrics = $this->getDonorMetrics($userId);
        $achievements = $this->achievementModel->listAllForRole('donor', true);
        return $this->evaluateAndUnlock($userId, $achievements, $metrics);
    }

    public function checkAssociationAchievements(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $metrics = $this->getAssociationMetrics($userId);
        $achievements = $this->achievementModel->listAllForRole('association', true);
        return $this->evaluateAndUnlock($userId, $achievements, $metrics);
    }

    public function checkCampaignFundedAchievements(int $campaignId, int $associationUserId): array
    {
        if ($campaignId <= 0 || $associationUserId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT id_createur,
                                            COALESCE(objectif_montant,0) AS objectif,
                                            COALESCE(montant_collecte,0) AS collecte
                                     FROM cagnotte
                                     WHERE id_cagnotte = :id
                                     LIMIT 1");
        $stmt->execute(['id' => $campaignId]);
        $campaign = $stmt->fetch();
        if (!$campaign) {
            return [];
        }

        if ((int)$campaign['id_createur'] !== $associationUserId) {
            return [];
        }

        if ((float)$campaign['collecte'] < (float)$campaign['objectif']) {
            return [];
        }

        return $this->checkAssociationAchievements($associationUserId);
    }

    public function checkAndUnlock(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $totalPoints = 0;

        try {
            $donorAchievements = $this->checkDonorAchievements($userId);
            foreach ($donorAchievements as $achievement) {
                $totalPoints += (int)($achievement['points'] ?? 0);
            }
        } catch (Throwable $e) {
            // continue if donor check fails
        }

        try {
            $associationAchievements = $this->checkAssociationAchievements($userId);
            foreach ($associationAchievements as $achievement) {
                $totalPoints += (int)($achievement['points'] ?? 0);
            }
        } catch (Throwable $e) {
            // continue if association check fails
        }

        return $totalPoints;
    }

    public function getUserProgress(int $userId, string $roleType): array
    {
        if ($roleType === 'association') {
            return $this->getAssociationMetrics($userId);
        }
        return $this->getDonorMetrics($userId);
    }

    private function evaluateAndUnlock(int $userId, array $achievements, array $metrics): array
    {
        $newUnlocks = [];

        foreach ($achievements as $achievement) {
            $conditionType = (string)($achievement['condition_type'] ?? '');
            $threshold = (float)($achievement['condition_value'] ?? 0);
            $current = (float)($metrics[$conditionType] ?? 0);

            if ($current >= $threshold) {
                $unlocked = $this->achievementModel->unlockAchievement($userId, (int)$achievement['id']);
                if ($unlocked) {
                    $newUnlocks[] = [
                        'id' => (int)$achievement['id'],
                        'title' => (string)$achievement['title'],
                        'points' => (int)$achievement['points'],
                    ];
                }
            }
        }

        return $newUnlocks;
    }

    private function getDonorMetrics(int $userId): array
    {
        $sql = "SELECT COUNT(*) AS donation_count,
                       COALESCE(SUM(montant),0) AS amount_total,
                       COUNT(DISTINCT id_cagnotte) AS supported_campaign_count
                FROM don
                WHERE id_donateur = :user_id
                  AND statut = 'confirme'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch() ?: [];

        return [
            'donation_count' => (int)($row['donation_count'] ?? 0),
            'amount_total' => (float)($row['amount_total'] ?? 0),
            'supported_campaign_count' => (int)($row['supported_campaign_count'] ?? 0),
        ];
    }

    private function getAssociationMetrics(int $userId): array
    {
        $sql = "SELECT COUNT(*) AS campaign_count,
                       COALESCE(SUM(montant_collecte),0) AS raised_amount_total,
                       SUM(CASE WHEN COALESCE(montant_collecte,0) >= COALESCE(objectif_montant,0)
                                AND COALESCE(objectif_montant,0) > 0 THEN 1 ELSE 0 END) AS funded_campaign_count
                FROM cagnotte
                WHERE id_createur = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch() ?: [];

        return [
            'campaign_count' => (int)($row['campaign_count'] ?? 0),
            'raised_amount_total' => (float)($row['raised_amount_total'] ?? 0),
            'funded_campaign_count' => (int)($row['funded_campaign_count'] ?? 0),
        ];
    }
}
