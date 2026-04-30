<?php
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    require_once __DIR__ . '/../models/config.php';
    require_once __DIR__ . '/CompteController.php';
    require_once __DIR__ . '/CarteController.php';

    Config::autoLogin();
    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $user   = $_SESSION['user'] ?? [];

    if (!$userId) { ob_end_clean(); echo json_encode(['reply' => 'Session expirée. Veuillez rafraîchir la page.']); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ob_end_clean(); echo json_encode(['reply' => 'Méthode non autorisée.']); exit; }

    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input) || empty($input)) {
        $input = $_POST;
        if (isset($input['history']) && is_string($input['history']))
            $input['history'] = json_decode($input['history'], true) ?? [];
    }

    // CSRF verification
    require_once __DIR__ . '/../models/Security.php';
    if (!Security::verifyCsrfToken($input['csrf_token'] ?? null)) {
        ob_end_clean();
        echo json_encode(['reply' => 'Erreur de sécurité : Jeton CSRF invalide.']);
        exit;
    }

    $message = trim($input['message'] ?? '');
    $lang    = trim($input['lang'] ?? 'fr-FR');
    $history = is_array($input['history'] ?? null) ? $input['history'] : [];
    if ($message === '') { ob_end_clean(); echo json_encode(['reply' => 'Veuillez entrer un message.']); exit; }

    $comptes   = CompteController::findByUtilisateur($userId);
    $allCartes = [];
    foreach ($comptes as $c)
        foreach (CarteController::findByCompte($c->getIdCompte()) as $carte)
            $allCartes[] = ['carte' => $carte, 'compte' => $c];

    // Try Gemini (server-key, no referrer restriction) → fallback to conversational engine
    $reply = callGemini($message, $history, $user, $comptes, $allCartes, $lang);
    if ($reply === null)
        $reply = conversationalReply($message, $history, $user, $comptes, $allCartes, $lang);

    if (ob_get_level()) ob_end_clean();
    echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    @file_put_contents(__DIR__ . '/chatbot_debug.log', date('[Y-m-d H:i:s]') . " FATAL: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['reply' => '⚠️ Erreur interne. Notre assistant rencontre une difficulté technique.']);
}
exit;


// ════════════════════════════════════════════════════════════════════════════════
// GEMINI ENGINE  (works only if API key has NO referrer restriction)
// ════════════════════════════════════════════════════════════════════════════════
function callGemini(string $msg, array $history, array $user, array $comptes, array $allCartes, string $lang = 'fr-FR'): ?string
{
    $key = defined('GEMINI_API_KEY') ? trim(GEMINI_API_KEY) : '';
    if ($key === '' || $key === 'YOUR_KEY_HERE') return null;
    if (!function_exists('curl_init')) return null;

    $ctx  = "Client: " . ($user['prenom'] ?? '') . " " . ($user['nom'] ?? '') . " | KYC: " . ($user['status_kyc'] ?? 'inconnu') . " | Date: " . date('d/m/Y H:i') . "\n";
    $ctx .= "COMPTES:\n";
    foreach ($comptes as $c)
        $ctx .= "  - " . $c->getTypeCompte() . " | Solde: " . number_format((float)$c->getSolde(),2) . " " . $c->getDevise() . " | IBAN: " . $c->getIban() . " | Statut: " . $c->getStatut() . "\n";
    $ctx .= "CARTES:\n";
    foreach ($allCartes as $item) {
        $crt = $item['carte'];
        $ctx .= "  - " . $crt->getTypeCarte() . " (" . $crt->getReseau() . ") ···" . substr($crt->getNumeroCarte(),-4) . " | " . $crt->getStatut() . "\n";
    }

    $langName = 'français';
    if ($lang === 'en-US') $langName = 'anglais (English)';
    if ($lang === 'ar-SA') $langName = 'arabe (Arabic)';

    $system = "Tu es l'assistant IA expert de la banque LegalFin. Ton rôle est de répondre à **absolument n'importe quelle question liée au domaine bancaire, financier, économique ou à l'argent en général**, quelle qu'elle soit.\n"
        . "Cependant, tu dois te LIMITER au domaine bancaire/financier. Si la question n'a **aucun rapport** (ex: sport, santé, recette, etc.), refuse poliment d'y répondre en rappelant que tu es un assistant dédié à la banque.\n"
        . "Réponds EXCLUSIVEMENT en $langName, de façon professionnelle et concise.\n"
        . "Utilise le Markdown (** gras, • listes).\n\n"
        . "CAPACITÉS :\n"
        . "- Expliquer l'ouverture de compte, gestion des cartes, virements, plafonds, KYC, etc.\n"
        . "- Répondre à toute question générale sur le monde bancaire, la finance, la bourse ou l'économie.\n"
        . "- Aider en cas de perte/vol de carte (blocage immédiat).\n\n"
        . "CONSIGNE CRUCIALE : Si l'utilisateur demande une carte pour un compte épargne, informe-le poliment que chez LegalFin, les comptes épargne ne sont pas éligibles aux cartes bancaires.\n\n"
        . "DONNÉES CLIENT EN TEMPS RÉEL :\n" . $ctx;

    $contents = [];
    foreach ($history as $h) {
        $role = ($h['role'] === 'user') ? 'user' : 'model';
        $text = trim($h['content'] ?? '');
        if ($text === '') continue;
        $last = count($contents) - 1;
        if ($last >= 0 && $contents[$last]['role'] === $role)
            $contents[$last]['parts'][0]['text'] .= "\n" . $text;
        else
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
    }
    $last = count($contents) - 1;
    if ($last >= 0 && $contents[$last]['role'] === 'user')
        $contents[$last]['parts'][0]['text'] .= "\n" . $msg;
    else
        $contents[] = ['role' => 'user', 'parts' => [['text' => $msg]]];

    $payload = json_encode([
        'system_instruction' => ['parts' => [['text' => $system]]],
        'contents'           => $contents,
        'generationConfig'   => ['temperature' => 0.7, 'maxOutputTokens' => 900],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $key);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    @file_put_contents(__DIR__ . '/chatbot_debug.log',
        date('[Y-m-d H:i:s]') . " Gemini HTTP:$httpCode cURL:" . ($curlErr ?: 'ok') . " resp:" . substr($response ?: 'EMPTY', 0, 300) . "\n", FILE_APPEND);

    if ($curlErr || !$response || $httpCode !== 200) return null;
    $res = json_decode($response, true);
    if (isset($res['error'])) { @file_put_contents(__DIR__ . '/chatbot_debug.log', "Gemini error: " . json_encode($res['error']) . "\n", FILE_APPEND); return null; }
    return $res['candidates'][0]['content']['parts'][0]['text'] ?? null;
}


// ════════════════════════════════════════════════════════════════════════════════
// CONVERSATIONAL ENGINE  — understands full questions in any language/style
// ════════════════════════════════════════════════════════════════════════════════
function conversationalReply(string $msg, array $history, array $user, array $comptes, array $allCartes, string $lang = 'fr-FR'): string
{
    $norm   = normalizeStr(mb_strtolower($msg, 'UTF-8'));
    $prenom = $user['prenom'] ?? 'Client';

    // ── Detect intent with scored matching ───────────────────────────────────
    $scores = [
        'greeting' => score($norm, ['bonjour','bonsoir','salut','hello','hi ','hey','coucou','salam','good morning','good evening','bienvenue'])
    ];

    // Get the winning intent
    $intent = array_search(max($scores), $scores);
    $maxScore = max($scores);

    // Require minimum confidence
    if ($maxScore === 0) $intent = 'unknown';

    // ── Intent handlers ───────────────────────────────────────────────────────

    if ($intent === 'greeting') {
        $h = (int)date('H');
        $g = $h < 12 ? 'Bonjour' : ($h < 18 ? 'Bon après-midi' : 'Bonsoir');
        return "$g **$prenom** ! 👋 Je suis votre assistant bancaire LegalFin.\nPosez-moi toutes vos questions sur la banque ou vos finances.";
    }

    // Si on arrive ici, cela signifie que Gemini a échoué (erreur 503, réseau, clé révoquée, etc.)
    return "Mon cerveau d'Intelligence Artificielle est actuellement surchargé en raison d'une très forte demande. ⏳\n\nVeuillez réessayer dans quelques secondes.";
}


// ── Helpers ───────────────────────────────────────────────────────────────────

function normalizeStr(string $s): string {
    $a = ['à','â','ä','á','ã','å','è','é','ê','ë','ì','í','î','ï','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','ñ','ç','œ','æ'];
    $b = ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','y','y','n','c','oe','ae'];
    return str_replace($a, $b, $s);
}

/** Returns how many keywords match — higher = more confident */
function score(string $norm, array $keywords): int {
    $s = 0;
    foreach ($keywords as $kw)
        if (strpos($norm, $kw) !== false) $s++;
    return $s;
}
