<?php
function analyserSolvabilite(array $data): array
{
    $apiKey = 'zid el api';
    $model  = 'openrouter/free';

    $prompt = "Tu es un analyste bancaire expert. Analyse cette demande de crédit et retourne UNIQUEMENT un JSON valide, rien d'autre. Pas de markdown, pas de backticks.

Données de la demande :
- Montant demandé : {$data['montant']} TND
- Durée : {$data['duree_mois']} mois
- Taux d'intérêt : {$data['taux_interet']}%
- Mensualité estimée : " . round($data['montant'] / $data['duree_mois'], 2) . " TND

Retourne exactement ce JSON :
{
  \"score\": <nombre entre 0 et 100>,
  \"recommendation\": \"approuvee\" ou \"refusee\" ou \"en_attente\",
  \"motif\": \"<explication courte en français, max 200 caractères>\",
  \"risque\": \"faible\" ou \"moyen\" ou \"eleve\"
}";

    $payload = json_encode([
        'model'    => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://openrouter.ai/api/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        return [
            'score'          => 50,
            'recommendation' => 'en_attente',
            'motif'          => 'Analyse IA indisponible, traitement manuel requis.',
            'risque'         => 'moyen'
        ];
    }

    $body   = json_decode($response, true);
    $text   = $body['choices'][0]['message']['content'] ?? '';
    $text   = preg_replace('/```json|```/i', '', $text);
    $text   = trim($text);
    $result = json_decode($text, true);

    if (!$result || !isset($result['score'])) {
        return [
            'score'          => 50,
            'recommendation' => 'en_attente',
            'motif'          => 'Réponse IA invalide, traitement manuel requis.',
            'risque'         => 'moyen'
        ];
    }

    return $result;
}
?>