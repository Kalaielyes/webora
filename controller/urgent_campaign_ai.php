<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/AIService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['frontoffice_user_id']) || !is_numeric($_SESSION['frontoffice_user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = trim((string)($_POST['action'] ?? ''));
if ($action !== 'suggest_urgent') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid action']);
    exit;
}

try {
    $pdo = Config::getConnexion();
    $sql = "SELECT c.id_cagnotte,
                   c.titre,
                   c.description,
                   COALESCE(c.objectif_montant,0) AS goal_amount,
                   COALESCE(c.montant_collecte,0) AS raised_amount,
                   COALESCE(c.date_fin, CURDATE()) AS date_fin,
                   COUNT(d.id_don) AS confirmed_donations
            FROM cagnotte c
            LEFT JOIN don d ON d.id_cagnotte = c.id_cagnotte AND d.statut = 'confirme'
            WHERE c.statut = 'acceptee'
            GROUP BY c.id_cagnotte
            ORDER BY c.date_fin ASC, c.id_cagnotte ASC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll() ?: [];

    if (empty($rows)) {
        echo json_encode(['ok' => false, 'error' => 'No active campaigns found']);
        exit;
    }

    $today = new DateTimeImmutable('today');
    $campaigns = [];
    foreach ($rows as $row) {
        $goal = (float)$row['goal_amount'];
        $raised = (float)$row['raised_amount'];
        $remaining = max(0, $goal - $raised);
        $fundedPct = $goal > 0 ? round(($raised / $goal) * 100, 2) : 0.0;

        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', substr((string)$row['date_fin'], 0, 10));
        if (!$endDate) {
            $endDate = $today;
        }
        $daysLeft = (int)$today->diff($endDate)->format('%r%a');

        $campaigns[] = [
            'campaign_id' => (int)$row['id_cagnotte'],
            'title' => (string)$row['titre'],
            'goal_amount' => $goal,
            'raised_amount' => $raised,
            'remaining_amount' => $remaining,
            'percentage_funded' => $fundedPct,
            'days_left' => $daysLeft,
            'confirmed_donations' => (int)$row['confirmed_donations'],
        ];
    }

    $ai = new AIService();
    $aiResponse = $ai->suggestMostUrgentCampaign($campaigns);

    $selected = null;
    $explanation = '';
    $urgencyScore = 0;
    $prompt = $ai->buildPrompt($campaigns);

    if (!empty($aiResponse['ok'])) {
        $suggestedId = (int)($aiResponse['result']['campaign_id'] ?? 0);
        $urgencyScore = max(1, min(100, (int)($aiResponse['result']['urgency_score'] ?? 0)));
        $explanation = trim((string)($aiResponse['result']['explanation'] ?? ''));

        foreach ($campaigns as $campaign) {
            if ((int)$campaign['campaign_id'] === $suggestedId) {
                $selected = $campaign;
                break;
            }
        }

        if ($explanation === '') {
            $explanation = 'This campaign has the strongest urgency profile based on time and funding gap.';
        }
    }

    if ($selected === null) {
        // Deterministic fallback ranking for resilience.
        usort($campaigns, static function (array $a, array $b): int {
            $aDays = max(0, (int)$a['days_left']);
            $bDays = max(0, (int)$b['days_left']);
            $aUrgency = ((float)$a['remaining_amount'] * 0.65) + ((100 - (float)$a['percentage_funded']) * 30) + (($aDays <= 7 ? 40 : 0));
            $bUrgency = ((float)$b['remaining_amount'] * 0.65) + ((100 - (float)$b['percentage_funded']) * 30) + (($bDays <= 7 ? 40 : 0));
            if ($aUrgency === $bUrgency) {
                return $aDays <=> $bDays;
            }
            return $bUrgency <=> $aUrgency;
        });

        $selected = $campaigns[0];
        $urgencyScore = max(1, min(100, (int)round((100 - (float)$selected['percentage_funded']) + (max(0, 15 - (int)$selected['days_left']) * 2))));
        $explanation = 'Selected by fallback urgency ranking: large remaining amount, lower funding percentage, and limited time left.';
    }

    $words = preg_split('/\s+/', trim($explanation));
    if (is_array($words) && count($words) > 120) {
        $explanation = implode(' ', array_slice($words, 0, 120));
    }

    echo json_encode([
        'ok' => true,
        'data' => [
            'campaign_id' => (int)$selected['campaign_id'],
            'title' => $selected['title'],
            'urgency_score' => $urgencyScore,
            'explanation' => $explanation,
            'metrics' => [
                'goal_amount' => (float)$selected['goal_amount'],
                'raised_amount' => (float)$selected['raised_amount'],
                'remaining_amount' => (float)$selected['remaining_amount'],
                'percentage_funded' => (float)$selected['percentage_funded'],
                'days_left' => (int)$selected['days_left'],
            ],
        ],
        'prompt_example' => $prompt,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
}
