<?php

class RecommendationAI
{
    public static function rankProjectsForUser(array $userProfile, array $projects): array
    {
        $apiKey = getenv('OPENAI_API_KEY') ?: '';
        if ($apiKey === '' || empty($projects)) {
            return $projects;
        }

        $baseUrl = rtrim(getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1', '/');
        $model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';

        $payload = [
            'model' => $model,
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a recommendation engine. Return JSON only with key "ordered_ids" containing a ranked array of project ids.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'task' => 'Rank projects for this user for investment relevance.',
                        'user_profile' => $userProfile,
                        'projects' => array_map(static function (array $project): array {
                            return [
                                'id_projet' => (int)$project['id_projet'],
                                'titre' => $project['titre'] ?? '',
                                'description' => $project['description'] ?? '',
                                'secteur' => $project['secteur'] ?? '',
                                'taux_rentabilite' => (float)($project['taux_rentabilite'] ?? 0),
                                'temps_retour_brut' => (float)($project['temps_retour_brut'] ?? 0),
                                'montant_objectif' => (float)($project['montant_objectif'] ?? 0),
                            ];
                        }, $projects),
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];

        $ch = curl_init($baseUrl . '/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return $projects;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return $projects;
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            return $projects;
        }

        $json = json_decode($content, true);
        if (!is_array($json) || !isset($json['ordered_ids']) || !is_array($json['ordered_ids'])) {
            return $projects;
        }

        $rank = [];
        foreach ($json['ordered_ids'] as $index => $projectId) {
            $rank[(int)$projectId] = (int)$index;
        }

        usort($projects, static function (array $a, array $b) use ($rank): int {
            $ra = $rank[(int)$a['id_projet']] ?? PHP_INT_MAX;
            $rb = $rank[(int)$b['id_projet']] ?? PHP_INT_MAX;
            if ($ra === $rb) {
                return 0;
            }
            return $ra < $rb ? -1 : 1;
        });

        return $projects;
    }
}
