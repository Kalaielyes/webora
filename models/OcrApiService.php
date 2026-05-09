<?php

class OcrApiService {
    
    /**
     * Utilise l'API OCR.space (Gratuite, pas de carte bancaire requise).
     * Inscrivez-vous sur https://ocr.space/ocrapi pour avoir votre propre clé.
     */
    private string $apiKey = 'K89026360788957'; // Votre clé personnelle

    public function scanDocument(string $filePath): array {
        $fullPath = __DIR__ . '/../' . $filePath;
        
        if (!file_exists($fullPath)) {
            return ['success' => false, 'error' => 'Fichier introuvable.'];
        }

        // Préparation de la requête vers OCR.space
        $postData = [
            'apikey' => $this->apiKey,
            'language' => 'fre', // Français (reconnaît aussi l'anglais)
            'isOverlayRequired' => 'false',
            'file' => new CURLFile($fullPath),
            'detectOrientation' => 'true',
            'scale' => 'true',
            'isTable' => 'false'
        ];

        $ch = curl_init('https://api.ocr.space/parse/image');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'Erreur API (Code ' . $httpCode . ')'];
        }

        $json = json_decode($response, true);
        
        if (isset($json['OCRExitCode']) && $json['OCRExitCode'] != 1) {
            return ['success' => false, 'error' => $json['ErrorMessage'][0] ?? 'Erreur inconnue'];
        }

        $fullText = $json['ParsedResults'][0]['ParsedText'] ?? '';
        
        if (empty($fullText)) {
            return ['success' => false, 'error' => 'Aucun texte détecté sur l\'image.'];
        }

        // ── EXTRACTION DES DONNÉES ──
        return [
            'success'   => true,
            'nom'       => $this->parseValue($fullText, 'nom'),
            'prenom'    => $this->parseValue($fullText, 'prenom'),
            'cin'       => $this->parseValue($fullText, 'cin'),
            'confiance' => 0.90, // Valeur indicative
            'raw_text'  => $fullText
        ];
    }

    /**
     * Tente d'extraire des informations spécifiques via des patterns.
     */
    private function parseValue(string $text, string $type): string {
        $lines = explode("\n", $text);
        
        switch ($type) {
            case 'cin':
                // Recherche d'un numéro à 8 chiffres (Standard Tunisie)
                if (preg_match('/\b\d{8}\b/', $text, $matches)) {
                    return $matches[0];
                }
                break;

            case 'nom':
                // Cherche la ligne après un mot clé ou une ligne en majuscules
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strlen($line) > 2 && $line === strtoupper($line) && !preg_match('/\d/', $line)) {
                        return $line;
                    }
                }
                break;

            case 'prenom':
                // Très variable, on renvoie souvent le premier mot capitalisé trouvé
                foreach ($lines as $line) {
                    $words = explode(' ', trim($line));
                    foreach ($words as $w) {
                        if (strlen($w) > 2 && ctype_upper(substr($w, 0, 1)) && !ctype_upper($w)) {
                            return $w;
                        }
                    }
                }
                break;
        }

        return 'Non détecté';
    }
}
