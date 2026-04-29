<?php
session_start();
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/cagnottecontroller.php';
require_once __DIR__ . '/doncontroller.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$action = trim($_POST['action'] ?? '');

function stripeRequest(string $method, string $endpoint, array $params = []): array {
    $url = 'https://api.stripe.com/v1/' . ltrim($endpoint, '/');
    $ch  = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => Config::STRIPE_SECRET_KEY . ':',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
    }
    curl_setopt_array($ch, $opts);
    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['_http' => 0, 'error' => ['message' => 'cURL error: ' . $curlErr]];
    }
    $data          = json_decode($body, true) ?? [];
    $data['_http'] = $httpCode;
    return $data;
}

if ($action === 'create_intent') {
    $montant    = isset($_POST['montant'])     ? (float)$_POST['montant']    : 0;
    $idCagnotte = isset($_POST['id_cagnotte']) ? (int)$_POST['id_cagnotte'] : 0;

    if ($montant < 1 || $idCagnotte <= 0) {
        echo json_encode(['error' => 'Données invalides']);
        exit;
    }

    $amountCents = (int)round($montant * 100);

    $result = stripeRequest('POST', 'payment_intents', [
        'amount'                 => $amountCents,
        'currency'               => 'eur',
        'payment_method_types[]' => 'card',
        'metadata[id_cagnotte]'  => $idCagnotte,
    ]);

    if ($result['_http'] !== 200 || isset($result['error'])) {
        echo json_encode(['error' => $result['error']['message'] ?? 'Erreur Stripe']);
        exit;
    }

    echo json_encode(['client_secret' => $result['client_secret']]);
    exit;
}

if ($action === 'confirm_don') {
    $paymentIntentId = trim($_POST['payment_intent_id'] ?? '');
    $montant         = isset($_POST['montant'])     ? (float)$_POST['montant']   : 0;
    $idCagnotte      = isset($_POST['id_cagnotte']) ? (int)$_POST['id_cagnotte'] : 0;
    $message         = trim($_POST['message'] ?? '');

    if (empty($paymentIntentId) || $montant <= 0 || $idCagnotte <= 0) {
        echo json_encode(['error' => 'Données invalides']);
        exit;
    }

    if (!preg_match('/^pi_[a-zA-Z0-9_]+$/', $paymentIntentId)) {
        echo json_encode(['error' => 'Identifiant de paiement invalide']);
        exit;
    }

    $intent = stripeRequest('GET', 'payment_intents/' . $paymentIntentId);

    if ($intent['_http'] !== 200 || isset($intent['error'])) {
        echo json_encode(['error' => 'Impossible de vérifier le paiement Stripe']);
        exit;
    }

    $status = $intent['status'] ?? '';
    if (!in_array($status, ['succeeded', 'processing'], true)) {
        echo json_encode(['error' => 'Paiement non confirmé (statut : ' . htmlspecialchars($status) . ')']);
        exit;
    }

    $donateurId = isset($_SESSION['frontoffice_user_id']) && is_numeric($_SESSION['frontoffice_user_id'])
        ? (int)$_SESSION['frontoffice_user_id']
        : null;

    $donCtrl = new doncontroller();
    $ok = $donCtrl->ajouterDon([
        'montant'        => $montant,
        'id_cagnotte'    => $idCagnotte,
        'moyen_paiement' => 'carte',
        'message'        => $message !== '' ? $message : null,
    ], $donateurId);

    if ($ok) {
        echo json_encode(['ok' => true, 'montant' => $montant]);
    } else {
        $err = method_exists($donCtrl, 'getLastError') ? $donCtrl->getLastError() : null;
        echo json_encode(['error' => $err ? $err : 'Erreur lors de l\'enregistrement du don']);
    }
    exit;
}

echo json_encode(['error' => 'Action inconnue']);
