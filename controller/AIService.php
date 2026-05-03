<?php

class AIService
{
    private string $endpoint;
    private string $model;

    public function __construct(
        string $endpoint = 'http://51.83.4.21:11434/api/generate',
        string $model = 'llama3.1-cpu-only'
    ) {
        $this->endpoint = $endpoint;
        $this->model = $model;
    }

    public function suggestMostUrgentCampaign(array $campaignData): array
    {
        $prompt = $this->buildPrompt($campaignData);

        $payload = [
            'model' => $this->model,
            'stream' => false,
            'format' => 'json',
            'prompt' => $prompt,
            'options' => [
                'temperature' => 0.1,
            ],
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => 'AI cURL error: ' . $curlErr];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return ['ok' => false, 'error' => 'AI HTTP error: ' . $httpCode, 'raw' => $raw];
        }

        $outer = json_decode($raw, true);
        if (!is_array($outer)) {
            return ['ok' => false, 'error' => 'AI outer JSON parse failed'];
        }

        $responseText = trim((string)($outer['response'] ?? ''));
        if ($responseText === '') {
            return ['ok' => false, 'error' => 'AI empty response'];
        }

        $inner = json_decode($responseText, true);
        if (!is_array($inner)) {
            return ['ok' => false, 'error' => 'AI inner JSON parse failed', 'response_text' => $responseText];
        }

        $normalized = [
            'campaign_id' => isset($inner['campaign_id']) ? (int)$inner['campaign_id'] : 0,
            'urgency_score' => isset($inner['urgency_score']) ? (int)$inner['urgency_score'] : 0,
            'explanation' => trim((string)($inner['explanation'] ?? '')),
        ];

        return [
            'ok' => true,
            'prompt' => $prompt,
            'result' => $normalized,
            'raw' => $outer,
        ];
    }

    public function buildPrompt(array $campaignData): string
    {
        $rules = [
            'You are an urgency ranking engine for donation campaigns.',
            'Select exactly one campaign as the most urgent.',
            'Use these factors: remaining amount, percentage funded, days left, and campaign size.',
            'Return strict JSON only with keys: campaign_id, urgency_score, explanation.',
            'urgency_score must be an integer from 1 to 100.',
            'explanation must be max 120 words.',
            'Do not include markdown, code fences, or extra keys.',
        ];

        return implode("\n", $rules)
            . "\n\nCampaign dataset JSON:\n"
            . json_encode($campaignData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            . "\n\nReturn only JSON.";
    }
}
