<?php
function analyserSolvabilite(array $data): array
{
    $apiKey = 'AIzaSyDXclfGfwzDhRM007zLy3LRxD_DO1ANFU0';
    $model  = 'gemini-2.5-flash';

    $prompt = "Tu es un analyste bancaire expert. Analyse cette demande de crédit et retourne UNIQUEMENT un JSON valide, rien d'autre.

Données de la demande :
- Montant demandé : {$data['montant']} TND
- Durée : {$data['duree_mois']} mois
- Taux d'intérêt : {$data['taux_interet']}%
- Mensualité estimée : " . round($data['montant'] / $data['duree_mois'], 2) . " TND

Retourne exactement ce JSON (sans markdown, sans backticks) :
{
  \"score\": <nombre entre 0 et 100>,
  \"recommendation\": \"approuvee\" ou \"refusee\" ou \"en_attente\",
  \"motif\": \"<explication courte en français, max 200 caractères>\",
  \"risque\": \"faible\" ou \"moyen\" ou \"eleve\"
}";

    $payload = json_encode([
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
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
    $text   = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
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