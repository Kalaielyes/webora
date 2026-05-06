<?php

class AmlApiService {
    
    // URL relative to localhost (assuming XAMPP standard setup)
<<<<<<< HEAD
    private string $apiUrl = 'http://localhost/web/controller/api/mock_aml_api.php';
=======
    private string $apiUrl = 'http://localhost/web/controllers/api/mock_aml_api.php';
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)

    /**
     * Envoyer les données utilisateur à l'API AML pour scoring.
     * 
     * @param array $userData
     * @return array
     */
    public function analyzeUser(array $userData): array {
        $ch = curl_init($this->apiUrl);
        
        $payload = json_encode($userData);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 sec timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'Erreur de connexion à l\'API AML.',
                'aml_score' => 0,
                'aml_reasons' => []
            ];
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['aml_score'])) {
            return [
                'success' => false,
                'error' => 'Réponse API invalide.',
                'aml_score' => 0,
                'aml_reasons' => []
            ];
        }
        
        return [
            'success' => true,
            'aml_score' => (int) $data['aml_score'],
            'aml_reasons' => $data['aml_reasons'] ?? []
        ];
    }
}
