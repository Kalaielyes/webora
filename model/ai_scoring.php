<?php
function analyserSolvabilite(array $data): array
{
    $apiKey = getenv('OPENROUTER_API_KEY') ?: '';
    $model  = getenv('OPENROUTER_MODEL') ?: 'openrouter/auto';

    $montant = (float) ($data['montant'] ?? 0);
    $duree = max(1, (int) ($data['duree_mois'] ?? 1));
    $taux = (float) ($data['taux_interet'] ?? 0);
    $mensualite = round($montant / $duree, 2);
    $ratioMensualite = $montant > 0 ? round(($mensualite / $montant) * 100, 2) : 0;

    if ($apiKey === '') {
        return scoreDemandeLocalement($data, 'Score local: cle IA absente.');
    }

    $prompt = "Tu es un analyste bancaire expert. Analyse cette demande de credit et retourne UNIQUEMENT un JSON valide, rien d'autre. Pas de markdown, pas de backticks.

Donnees de la demande :
- Montant demande : {$montant} TND
- Duree : {$duree} mois
- Taux d'interet : {$taux}%
- Mensualite estimee : {$mensualite} TND
- Ratio mensualite/montant : {$ratioMensualite}%
- Pays client : " . ($data['country_code'] ?? 'TN') . "
- Statut initial : " . ($data['statut'] ?? 'en_cours') . "

Regle : Base ton analyse uniquement sur les donnees fournies. Ne demande pas d'informations supplementaires.
Retourne exactement ce JSON :
{
  \"score\": <nombre entre 0 et 100>,
  \"recommendation\": \"approuvee\" ou \"refusee\",
  \"motif\": \"<explication courte en francais, max 200 caracteres>\",
  \"risque\": \"faible\" ou \"moyen\" ou \"eleve\"
}";

    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://openrouter.ai/api/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        return scoreDemandeLocalement($data, 'Score local: service IA indisponible.');
    }

    $body = json_decode($response, true);
    $text = trim((string) ($body['choices'][0]['message']['content'] ?? ''));
    $text = preg_replace('/```json|```/i', '', $text);
    if (preg_match('/\{.*\}/s', $text, $matches)) {
        $text = $matches[0];
    }

    $result = json_decode(trim($text), true);
    if (!$result || !isset($result['score'])) {
        return scoreDemandeLocalement($data, 'Score local: reponse IA invalide.');
    }

    return normaliserScoring($result);
}

function scoreDemandeLocalement(array $data, string $prefix = 'Score local.'): array
{
    $montant = max(0, (float) ($data['montant'] ?? 0));
    $duree = max(1, (int) ($data['duree_mois'] ?? 1));
    $taux = max(0, (float) ($data['taux_interet'] ?? 0));
    $mensualite = $montant / $duree;

    $score = 100;

    if ($montant > 100000) {
        $score -= 35;
    } elseif ($montant > 50000) {
        $score -= 20;
    } elseif ($montant > 25000) {
        $score -= 10;
    }

    if ($duree < 12 && $montant > 10000) {
        $score -= 20;
    } elseif ($duree > 84) {
        $score -= 10;
    }

    if ($taux > 18) {
        $score -= 25;
    } elseif ($taux > 12) {
        $score -= 12;
    }

    if ($mensualite > 2500) {
        $score -= 25;
    } elseif ($mensualite > 1200) {
        $score -= 12;
    }

    $score = max(0, min(100, (int) round($score)));
    $recommendation = $score >= 55 ? 'approuvee' : 'refusee';
    $risque = $score >= 75 ? 'faible' : ($score >= 55 ? 'moyen' : 'eleve');
    $motif = $recommendation === 'approuvee'
        ? "$prefix Profil acceptable selon montant, duree, taux et mensualite."
        : "$prefix Risque trop eleve selon montant, duree, taux ou mensualite.";

    return [
        'score' => $score,
        'recommendation' => $recommendation,
        'motif' => substr($motif, 0, 200),
        'risque' => $risque,
    ];
}

function normaliserScoring(array $result): array
{
    $score = max(0, min(100, (int) round((float) ($result['score'] ?? 50))));
    $recommendation = $result['recommendation'] ?? '';
    if (!in_array($recommendation, ['approuvee', 'refusee'], true)) {
        $recommendation = $score >= 55 ? 'approuvee' : 'refusee';
    }

    $risque = $result['risque'] ?? '';
    if (!in_array($risque, ['faible', 'moyen', 'eleve'], true)) {
        $risque = $score >= 75 ? 'faible' : ($score >= 55 ? 'moyen' : 'eleve');
    }

    $motif = trim((string) ($result['motif'] ?? 'Decision calculee automatiquement.'));

    return [
        'score' => $score,
        'recommendation' => $recommendation,
        'motif' => substr($motif, 0, 200),
        'risque' => $risque,
    ];
}
?>
