<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$score = 0;
$reasons = [];

$email = $input['email'] ?? '';
$nom = $input['nom'] ?? '';
$cin = $input['cin'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// Mocking logic

// 1. Discardable email check
$disposable_domains = ['yopmail.com', 'tempmail.com', '10minutemail.com', 'mailinator.com', 'test.com'];
$domain = substr(strrchr($email, "@"), 1);
if (in_array(strtolower($domain), $disposable_domains)) {
    $score += 50;
    $reasons[] = "L'adresse email appartient à un domaine jetable ou suspect ($domain).";
}

// 2. Suspicious names
$suspicious_names = ['test', 'admin', 'fake', 'null', 'undefined', 'anonymous'];
if (in_array(strtolower($nom), $suspicious_names)) {
    $score += 35;
    $reasons[] = "Le nom utilisé semble être un nom d'emprunt ou de test ('$nom').";
}

// 3. CIN format check (Mock logic: if CIN starts with '000')
if (str_starts_with($cin, '000')) {
    $score += 20;
    $reasons[] = "Anomalie sur la structure du numéro CIN.";
}

// 4. Base randomness to simulate real API feeling
if ($score === 0) {
    $score = rand(2, 18);
    if ($score > 12) {
        $reasons[] = "Activité réseau légèrement inhabituelle pour ce profil (risque mineur).";
    }
}

// 5. Extreme case
if ($email === 'fraud@yopmail.com') {
    $score = 95;
    $reasons[] = "Présent sur les listes de sanctions internationales OFAC/INTERPOL.";
    $reasons[] = "L'adresse email appartient à un domaine jetable ou suspect (yopmail.com).";
}

$score = min(100, $score);

echo json_encode([
    'success' => true,
    'aml_score' => $score,
    'aml_reasons' => $reasons,
    'timestamp' => date('c'),
    'reference' => 'req_' . uniqid()
]);
