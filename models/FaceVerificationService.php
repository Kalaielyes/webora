<?php

class FaceVerificationService {

    /**
     * Face++ API credentials.
     * Get yours FREE at: https://console.faceplusplus.com/register
     * No credit card required. 1000 requests/month on free plan.
     */
    private string $apiKey    = 'a49hMYZZscFR1sU7ecxqq4hUxIcZDGHj';
    private string $apiSecret = '13Fd_JFyzALcmrdPN3sTvlcY9NxA-bAW';
    private string $endpoint  = 'https://api-us.faceplusplus.com/facepp/v3/compare';

    /**
     * Compare a face in an ID document against a selfie.
     *
     * @param string $idDocPath    Relative path to the ID document image.
     * @param string $selfiePath   Relative path to the selfie image.
     * @return array ['success' => bool, 'score' => float, 'error' => string]
     */
    public function compareFaces(string $idDocPath, string $selfiePath): array {
// Determine absolute paths. If the provided path works as-is, use it. Otherwise, assume it is relative to the project root.
        $idFullPath = file_exists($idDocPath) ? $idDocPath : __DIR__ . '/../' . ltrim($idDocPath, '/\\');
        $selfieFullPath = file_exists($selfiePath) ? $selfiePath : __DIR__ . '/../' . ltrim($selfiePath, '/\\');

        error_log("[FaceVerification] Checking ID path: " . $idFullPath . " | exists: " . (file_exists($idFullPath) ? "YES" : "NO"));
        error_log("[FaceVerification] Checking Selfie path: " . $selfieFullPath . " | exists: " . (file_exists($selfieFullPath) ? "YES" : "NO"));

        // Verify ID document exists
        if (!file_exists($idFullPath)) {
            return ['success' => false, 'score' => 0, 'error' => 'Document ID introuvable. Path: ' . $idFullPath];
        }

        // Verify selfie exists
        if (!file_exists($selfieFullPath)) {
            return ['success' => false, 'score' => 0, 'error' => 'Selfie introuvable. Path: ' . $selfieFullPath];
        }

        // If no real API key, run in simulation mode for development
        if ($this->apiKey === 'YOUR_FACE_PLUS_PLUS_API_KEY') {
            return $this->simulateResponse();
        }

        $postData = [
            'api_key'        => $this->apiKey,
            'api_secret'     => $this->apiSecret,
            'image_file1'    => new CURLFile($idFullPath),
            'image_file2'    => new CURLFile($selfieFullPath),
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errDetails = json_decode($response, true);
            $errMsg = $errDetails['error_message'] ?? "Erreur API Face++ (HTTP $httpCode)";
            return ['success' => false, 'score' => 0, 'error' => $errMsg];
        }

        $json = json_decode($response, true);

        if (isset($json['error_message'])) {
            return ['success' => false, 'score' => 0, 'error' => $json['error_message']];
        }

        $score = round((float)($json['confidence'] ?? 0), 2);

        return [
            'success'  => true,
            'score'    => $score,
            'thresholds' => $json['thresholds'] ?? [],
        ];
    }

    /**
     * Simulate a realistic API response for development/testing.
     * Returns a random score between 65 and 98 to test both outcomes.
     */
    private function simulateResponse(): array {
        // Simulate processing delay
        usleep(500000);
        $score = rand(65, 98);
        return [
            'success' => true,
            'score'   => $score,
            'simulated' => true,
        ];
    }
}
