<?php

require_once __DIR__ . '/config.php';

class Score
{
    private static array $tableColumns = [];

    private static function getConnexion(): PDO
    {
        return Config::getConnexion();
    }

    private static function getColumns(string $table): array
    {
        if (isset(self::$tableColumns[$table])) {
            return self::$tableColumns[$table];
        }
        $stmt = self::getConnexion()->query("SHOW COLUMNS FROM {$table}");
        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = $row['Field'];
        }
        self::$tableColumns[$table] = $columns;
        return $columns;
    }

    private static function hasColumn(string $table, string $column): bool
    {
        return in_array($column, self::getColumns($table), true);
    }

    private static function resolveDateValue(array $row, array $candidates): ?DateTimeImmutable
    {
        foreach ($candidates as $field) {
            if (!empty($row[$field])) {
                try {
                    return new DateTimeImmutable((string)$row[$field]);
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        return null;
    }

    private static function isPositiveStatus(?string $value): bool
    {
        if ($value === null) return false;
        $v = strtoupper(trim($value));
        return in_array($v, ['VALIDE', 'VERIFIE', 'VERIFIED', 'APPROVED', 'OK', 'ACTIVE'], true);
    }

    private static function isNegativeStatus(?string $value): bool
    {
        if ($value === null) return false;
        $v = strtoupper(trim($value));
        return in_array($v, ['REFUSE', 'REJECTED', 'BLOCKED', 'SUSPENDED', 'BLACKLISTED', 'ANNULE'], true);
    }

    public static function recalculateAllUsers(): void
    {
        $stmt = self::getConnexion()->query("SELECT id FROM utilisateur");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            self::recalculateForUser((int)$id);
        }
    }

    public static function recalculateForUser(int $userId): void
    {
        if ($userId <= 0) return;
        $pdo = self::getConnexion();

        $uStmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id = :id LIMIT 1");
        $uStmt->execute(['id' => $userId]);
        $user = $uStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return;

        // 1) Compliance: KYC/AML/Account status (30)
        $complianceRaw = 0.0;
        $complianceReason = [];
        $kycField = self::hasColumn('utilisateur', 'kyc_status') ? 'kyc_status' : null;
        $amlField = self::hasColumn('utilisateur', 'aml_status') ? 'aml_status' : null;
        $statusField = self::hasColumn('utilisateur', 'status') ? 'status' : null;

        if ($kycField !== null) {
            $kyc = $user[$kycField] ?? null;
            if (self::isPositiveStatus($kyc)) $complianceRaw += 0.45;
            elseif (self::isNegativeStatus($kyc)) $complianceRaw -= 0.35;
            $complianceReason[] = "KYC=" . ($kyc ?: 'N/A');
        } else {
            $complianceRaw += 0.2;
            $complianceReason[] = "KYC non disponible";
        }

        if ($amlField !== null) {
            $aml = $user[$amlField] ?? null;
            if (self::isPositiveStatus($aml)) $complianceRaw += 0.35;
            elseif (self::isNegativeStatus($aml)) $complianceRaw -= 0.35;
            $complianceReason[] = "AML=" . ($aml ?: 'N/A');
        } else {
            $complianceRaw += 0.15;
            $complianceReason[] = "AML non disponible";
        }

        if ($statusField !== null) {
            $st = $user[$statusField] ?? null;
            if (self::isPositiveStatus($st)) $complianceRaw += 0.2;
            elseif (self::isNegativeStatus($st)) $complianceRaw -= 0.3;
            $complianceReason[] = "Compte=" . ($st ?: 'N/A');
        } else {
            $complianceRaw += 0.1;
            $complianceReason[] = "Status compte non disponible";
        }

        $complianceRaw = max(0, min(1, $complianceRaw));
        $compliancePoints = round($complianceRaw * 30, 2);

        // 2) Account age (15)
        $createdAt = self::resolveDateValue($user, ['created_at', 'date_creation', 'date_inscription']);
        $ageDays = $createdAt ? max(0, (int)$createdAt->diff(new DateTimeImmutable('now'))->format('%a')) : 0;
        $ageNorm = min(1, $ageDays / 365); // full after one year
        $agePoints = round($ageNorm * 15, 2);

        // 3) Last login recency (15)
        $lastLogin = self::resolveDateValue($user, ['last_login', 'derniere_connexion', 'updated_at']);
        $recencyPoints = 0.0;
        if ($lastLogin) {
            $days = max(0, (int)$lastLogin->diff(new DateTimeImmutable('now'))->format('%a'));
            if ($days <= 7) $recencyPoints = 15;
            elseif ($days <= 30) $recencyPoints = 12;
            elseif ($days <= 90) $recencyPoints = 8;
            elseif ($days <= 180) $recencyPoints = 4;
        }

        // 4) Investments (25)
        $iStmt = $pdo->prepare("
            SELECT COUNT(*) AS total_count,
                   SUM(CASE WHEN status = 'VALIDE' THEN 1 ELSE 0 END) AS valid_count,
                   SUM(CASE WHEN status IN ('REFUSE','ANNULE') THEN 1 ELSE 0 END) AS negative_count,
                   SUM(CASE WHEN status = 'VALIDE' THEN montant_investi ELSE 0 END) AS valid_amount
            FROM investissement
            WHERE id_investisseur = :uid
        ");
        $iStmt->execute(['uid' => $userId]);
        $inv = $iStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $invTotal = (int)($inv['total_count'] ?? 0);
        $invValid = (int)($inv['valid_count'] ?? 0);
        $invNeg = (int)($inv['negative_count'] ?? 0);
        $invAmount = (float)($inv['valid_amount'] ?? 0);
        $investNorm = min(1, ($invValid / max(1, $invTotal)) * 0.5 + min(1, $invAmount / 100000) * 0.5);
        $investPoints = round($investNorm * 25, 2);

        // 5) Projects (15)
        $pStmt = $pdo->prepare("
            SELECT COUNT(*) AS total_count,
                   SUM(CASE WHEN status IN ('VALIDE','EN_COURS','TERMINE') THEN 1 ELSE 0 END) AS positive_count,
                   SUM(CASE WHEN status IN ('REFUSE','ANNULE') THEN 1 ELSE 0 END) AS negative_count,
                   AVG(CASE WHEN montant_objectif > 0 THEN
                        LEAST(100, (SELECT COALESCE(SUM(i.montant_investi),0) * 100 / p2.montant_objectif
                                    FROM investissement i
                                    WHERE i.id_projet = p2.id_projet AND i.status='VALIDE'))
                       ELSE 0 END) AS avg_progress
            FROM projet p2
            WHERE p2.id_createur = :uid
        ");
        $pStmt->execute(['uid' => $userId]);
        $proj = $pStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $projTotal = (int)($proj['total_count'] ?? 0);
        $projPos = (int)($proj['positive_count'] ?? 0);
        $projNeg = (int)($proj['negative_count'] ?? 0);
        $projProgress = (float)($proj['avg_progress'] ?? 0);
        $projectNorm = min(1, ($projPos / max(1, $projTotal)) * 0.65 + min(1, $projProgress / 100) * 0.35);
        $projectPoints = round($projectNorm * 15, 2);

        // Penalties (cap -20)
        $penalty = 0.0;
        if (self::isNegativeStatus($user[$statusField] ?? null)) $penalty += 8;
        if (self::isNegativeStatus($user[$kycField] ?? null)) $penalty += 8;
        if (self::isNegativeStatus($user[$amlField] ?? null)) $penalty += 8;
        $penalty += min(8, $invNeg * 1.5);
        $penalty += min(8, $projNeg * 1.5);
        $penalty = min(20, $penalty);

        $rawTotal = $compliancePoints + $agePoints + $recencyPoints + $investPoints + $projectPoints - $penalty;
        $score = (int)round(max(0, min(100, $rawTotal)));

        $details = [
            ['factor' => 'compliance', 'max' => 30, 'points' => $compliancePoints, 'reason' => implode(' | ', $complianceReason)],
            ['factor' => 'account_age', 'max' => 15, 'points' => $agePoints, 'reason' => "Age compte: {$ageDays} jours"],
            ['factor' => 'last_login', 'max' => 15, 'points' => $recencyPoints, 'reason' => $lastLogin ? 'Derniere connexion recente' : 'Derniere connexion indisponible'],
            ['factor' => 'investments', 'max' => 25, 'points' => $investPoints, 'reason' => "Valides {$invValid}/{$invTotal}, montant valide {$invAmount}"],
            ['factor' => 'projects', 'max' => 15, 'points' => $projectPoints, 'reason' => "Positifs {$projPos}/{$projTotal}, progression moyenne " . round($projProgress, 1) . "%"],
            ['factor' => 'penalties', 'max' => -20, 'points' => -$penalty, 'reason' => 'Penalites appliquees selon statuts negatifs'],
        ];

        $payload = [
            'user_id' => $userId,
            'trust_score' => $score,
            'score_details' => json_encode($details, JSON_UNESCAPED_UNICODE),
        ];
        $existsStmt = $pdo->prepare("SELECT id FROM score WHERE user_id = :user_id LIMIT 1");
        $existsStmt->execute(['user_id' => $userId]);
        $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $upd = $pdo->prepare("
                UPDATE score
                SET trust_score    = :trust_score,
                    score_details  = :score_details,
                    updated_at     = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upd->execute([
                'trust_score'   => $score,
                'score_details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'id'            => (int)$existing['id'],
            ]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO score (user_id, trust_score, score_details)
                VALUES (:user_id, :trust_score, :score_details)
            ");
            $ins->execute([
                'user_id'       => $userId,
                'trust_score'   => $score,
                'score_details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public static function getAdminScores(): array
    {
        $sql = "SELECT s.user_id        AS user_id,
                       s.trust_score    AS trust_score,
                       s.score_details  AS score_details,
                       s.updated_at     AS updated_at,
                       u.nom, u.prenom, u.email
                FROM score s
                INNER JOIN utilisateur u ON u.id = s.user_id
                ORDER BY s.trust_score DESC, s.updated_at DESC";
        $stmt = self::getConnexion()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

