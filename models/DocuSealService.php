<?php
/**
 * DocuSealService.php
 * Place ce fichier dans : /model/DocuSealService.php
 *
 * 🔗 Obtenir ta clé API GRATUITE (dev/test) :
 *    → https://console.docuseal.com
 *    → Créer un compte → Settings → API → Copier la clé
 *
 * Pour la production gratuite (self-hosted Docker) :
 *    → https://github.com/docusealco/docuseal
 */

class DocuSealService
{
    // ─── CONFIGURATION ────────────────────────────────────────────────────────
    // 🔑 Mets ta clé API ici (depuis https://console.docuseal.com → Settings → API)
    private const API_KEY = 'G4kc28w5k78Bcs2xAV3JQ95TZerY27K2od4Rcfdu3Jf';

    // URL de l'API (cloud global gratuit pour dev/test)
    private const API_URL = 'https://api.docuseal.com';

    // ─── MÉTHODE PRINCIPALE ───────────────────────────────────────────────────
    /**
     * Envoie un contrat de crédit au client pour signature électronique
     * Appelée automatiquement quand une demande est approuvée
     *
     * @param array $demande  Données de la demande (depuis DemandeCredit)
     * @param string $clientEmail  Email du client
     * @param string $clientName   Nom du client
     * @return array ['success' => bool, 'signing_url' => string, 'submission_id' => int]
     */
    public static function sendContratForSignature(
        array $demande,
        string $clientEmail,
        string $clientName
    ): array {
        try {
            // 1. Générer le HTML du contrat
            $contractHtml = self::generateContractHtml($demande, $clientName);

            // 2. Créer une soumission directement depuis HTML (sans template)
            $payload = [
                'name'       => 'Contrat Crédit #' . $demande['id'],
                'documents'  => [
                    [
                        'name' => 'Contrat_Credit_' . $demande['id'],
                        'html' => $contractHtml,
                        'size' => 'A4',
                    ]
                ],
                'submitters' => [
                    [
                        'role'  => 'Client',
                        'name'  => $clientName,
                        'email' => $clientEmail,
                        'values' => [
                            'Date Signature' => date('Y-m-d'),
                        ],
                    ]
                ],
                'send_email' => true,
                'message'    => [
                    'subject' => 'Votre contrat de crédit LegalFin — À signer',
                    'body'    => "Bonjour {$clientName},\n\nVotre demande de crédit #" . $demande['id'] . " d'un montant de " . number_format($demande['montant'], 0, ',', ' ') . " TND a été approuvée.\n\nVeuillez cliquer sur le lien ci-dessous pour signer votre contrat électroniquement.\n\nCordialement,\nL'équipe LegalFin"
                ]
            ];

            $response = self::apiCall('POST', '/submissions/html', $payload);

            if (isset($response['id'])) {
                return [
                    'success'        => true,
                    'submission_id'  => $response['id'],
                    'signing_url'    => $response['submitters'][0]['submission_url']
                        ?? $response['submitters'][0]['embed_src']
                        ?? '',
                    'status'         => $response['status'] ?? 'pending',
                    'message'        => 'Contrat envoyé par email au client pour signature',
                ];
            }

            return [
                'success' => false,
                'error'   => 'Réponse API invalide : ' . json_encode($response),
            ];

        } catch (Exception $e) {
            error_log('[DocuSeal] Erreur: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifie le statut d'une signature (completed / pending / declined)
     *
     * @param int $submissionId  ID retourné par sendContratForSignature
     * @return array
     */
    public static function checkSignatureStatus(int $submissionId): array
    {
        try {
            $response = self::apiCall('GET', '/submissions/' . $submissionId);
            $submitters = $response['submitters'] ?? [];
            $completed = ($response['status'] ?? '') === 'completed';

            if (!$completed && $submitters) {
                $completed = count(array_filter(
                    $submitters,
                    fn ($submitter) => !empty($submitter['completed_at']) || (($submitter['status'] ?? '') === 'completed')
                )) === count($submitters);
            }

            return [
                'success'    => true,
                'status'     => $response['status'] ?? 'unknown',
                'completed'  => $completed,
                'created_at' => $response['created_at'] ?? null,
                'updated_at' => $response['updated_at'] ?? null,
                'documents'  => $response['documents'] ?? [],
                'submitters' => $submitters,
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Télécharge le PDF signé (après signature complète)
     *
     * @param int $submissionId
     * @return string|false  Contenu binaire du PDF ou false
     */
    public static function downloadSignedPdf(int $submissionId): string|false
    {
        try {
            $status = self::checkSignatureStatus($submissionId);

            if (!$status['completed']) {
                return false;
            }

            // Récupérer l'URL du document signé
            $documents = $status['documents'] ?? [];
            if (empty($documents[0]['url'])) {
                return false;
            }

            $pdfContent = @file_get_contents($documents[0]['url']);
            return $pdfContent ?: false;

        } catch (Exception $e) {
            error_log('[DocuSeal] Download error: ' . $e->getMessage());
            return false;
        }
    }

    // ─── GÉNÉRATION DU CONTRAT HTML ────────────────────────────────────────────
    /**
     * Génère le HTML du contrat de crédit
     * DocuSeal convertit automatiquement ce HTML en PDF signable
     */
    private static function generateContractHtml(array $demande, string $clientName): string
    {
        $montant      = number_format($demande['montant'], 2, ',', ' ');
        $duree        = (int) $demande['duree_mois'];
        $taux         = $demande['taux_interet'];
        $dateContrat  = date('d/m/Y');
        $dateFin      = date('d/m/Y', strtotime("+{$duree} months"));
        $demandeId    = (int) $demande['id'];

        // Calcul mensualité
        $monthlyRate  = $taux / 100 / 12;
        $mensualite   = $monthlyRate > 0
            ? round($demande['montant'] * ($monthlyRate * pow(1 + $monthlyRate, $duree)) / (pow(1 + $monthlyRate, $duree) - 1), 2)
            : round($demande['montant'] / $duree, 2);
        $totalRembours = round($mensualite * $duree, 2);
        $coutInterets  = round($totalRembours - $demande['montant'], 2);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; color: #1a1a2e; font-size: 13px; margin: 0; padding: 20px; }
  .header { text-align: center; border-bottom: 3px solid #667eea; padding-bottom: 20px; margin-bottom: 30px; }
  .logo { font-size: 28px; font-weight: bold; color: #667eea; }
  .logo span { color: #764ba2; }
  .subtitle { color: #666; font-size: 12px; margin-top: 4px; }
  .contrat-title { font-size: 20px; font-weight: bold; text-align: center; margin: 20px 0; color: #1a1a2e; text-transform: uppercase; }
  .ref { text-align: center; color: #667eea; font-size: 12px; margin-bottom: 25px; }
  .section { margin-bottom: 25px; }
  .section-title { font-size: 13px; font-weight: bold; color: #667eea; border-left: 4px solid #667eea; padding-left: 10px; margin-bottom: 12px; text-transform: uppercase; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
  td { padding: 8px 12px; border: 1px solid #e0e0e0; font-size: 12px; }
  tr:nth-child(even) td { background: #f8f9ff; }
  td:first-child { font-weight: bold; color: #444; width: 50%; }
  .highlight { background: #f0f4ff !important; border: 2px solid #667eea !important; }
  .highlight td { color: #667eea; font-weight: bold; font-size: 14px; }
  .clause { font-size: 11px; line-height: 1.8; color: #555; margin-bottom: 8px; text-align: justify; }
  .signature-section { margin-top: 40px; border-top: 2px dashed #ccc; padding-top: 25px; }
  .sig-grid { display: flex; gap: 40px; }
  .sig-box { flex: 1; border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; }
  .sig-label { font-size: 11px; color: #666; margin-bottom: 10px; font-weight: bold; text-transform: uppercase; }
  .sig-line { border-bottom: 2px solid #333; height: 60px; margin: 10px 0; }
  .sig-name { font-size: 11px; color: #888; }
  .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

<div class="header">
  <div class="logo">Legal<span>Fin</span> Bank</div>
  <div class="subtitle">Institution financière agréée — Siège social: Tunis, Tunisie</div>
</div>

<div class="contrat-title">Contrat de Prêt Personnel</div>
<div class="ref">Référence: LF-{$demandeId}-{$dateContrat} | Date: {$dateContrat}</div>

<div class="section">
  <div class="section-title">1. Parties au contrat</div>
  <table>
    <tr><td>Organisme prêteur</td><td>LegalFin Bank — Établissement de crédit agréé</td></tr>
    <tr><td>Emprunteur</td><td>{$clientName}</td></tr>
    <tr><td>N° Demande</td><td>#DM-{$demandeId}</td></tr>
    <tr><td>Date du contrat</td><td>{$dateContrat}</td></tr>
  </table>
</div>

<div class="section">
  <div class="section-title">2. Conditions financières du prêt</div>
  <table>
    <tr class="highlight"><td>Montant du prêt</td><td>{$montant} TND</td></tr>
    <tr><td>Taux d'intérêt annuel</td><td>{$taux} %</td></tr>
    <tr><td>Durée du remboursement</td><td>{$duree} mois</td></tr>
    <tr><td>Mensualité</td><td>{$mensualite} TND</td></tr>
    <tr><td>Date de fin prévue</td><td>{$dateFin}</td></tr>
    <tr><td>Coût total des intérêts</td><td>{$coutInterets} TND</td></tr>
    <tr class="highlight"><td>Total à rembourser</td><td>{$totalRembours} TND</td></tr>
  </table>
</div>

<div class="section">
  <div class="section-title">3. Clauses et conditions générales</div>
  <p class="clause"><strong>Art. 1 — Objet :</strong> Le présent contrat a pour objet de définir les conditions du prêt personnel accordé par LegalFin Bank à l'emprunteur désigné ci-dessus.</p>
  <p class="clause"><strong>Art. 2 — Remboursement :</strong> L'emprunteur s'engage à rembourser le prêt par mensualités constantes de {$mensualite} TND, prélevées automatiquement le 1er de chaque mois sur le compte bancaire désigné.</p>
  <p class="clause"><strong>Art. 3 — Pénalités de retard :</strong> Tout retard de paiement supérieur à 10 jours entraînera des pénalités de 1,5 % du montant dû par mois de retard.</p>
  <p class="clause"><strong>Art. 4 — Remboursement anticipé :</strong> L'emprunteur peut procéder à un remboursement total ou partiel anticipé. Des frais de 1 % du capital restant dû s'appliqueront.</p>
  <p class="clause"><strong>Art. 5 — Droit applicable :</strong> Le présent contrat est soumis au droit tunisien. Tout litige sera porté devant les tribunaux compétents de Tunis.</p>
  <p class="clause"><strong>Art. 6 — Consentement électronique :</strong> La signature électronique apposée au présent document a la même valeur juridique qu'une signature manuscrite, conformément aux dispositions légales en vigueur.</p>
</div>

<div class="signature-section">
  <div class="section-title">4. Signatures</div>
  <div class="sig-grid">
    <div class="sig-box">
      <div class="sig-label">Pour LegalFin Bank</div>
      <div class="sig-line"></div>
      <div class="sig-name">Direction Générale</div>
      <div class="sig-name">{$dateContrat}</div>
    </div>
    <div class="sig-box">
      <div class="sig-label">L'emprunteur (signature électronique)</div>
      <signature-field name="Signature Client" role="Client" style="height:60px;display:block;margin:10px 0;"></signature-field>
      <date-field name="Date Signature" role="Client" style="display:block;margin-top:8px;"></date-field>
      <div class="sig-name">{$clientName}</div>
    </div>
  </div>
</div>

<div class="footer">
  Ce document est protégé par la loi. LegalFin Bank — RC: 123456 — MF: 12345678 — Agrément BCT N°2024/001
  <br>Document généré électroniquement le {$dateContrat} — Référence: LF-{$demandeId}
</div>

</body>
</html>
HTML;
    }

    // ─── APPEL API GÉNÉRIQUE ───────────────────────────────────────────────────
    /**
     * Effectue un appel HTTP à l'API DocuSeal
     */
    private static function apiCall(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim(self::getApiUrl(), '/') . $endpoint;
        $apiKey = self::getApiKey();

        $headers = [
            'X-Auth-Token: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, self::shouldVerifySsl());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, self::shouldVerifySsl() ? 2 : 0);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('cURL error: ' . $curlError);
        }

        if ($httpCode >= 400) {
            throw new Exception("API error HTTP {$httpCode}: " . $response);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $decoded ?? [];
    }

    private static function getApiKey(): string
    {
        $apiKey = getenv('DOCUSEAL_API_KEY') ?: self::API_KEY;

        if ($apiKey === '' || $apiKey === 'VOTRE_CLE_API_ICI') {
            throw new Exception('Clé API DocuSeal manquante. Définissez DOCUSEAL_API_KEY ou remplacez API_KEY dans DocuSealService.php.');
        }

        return $apiKey;
    }

    private static function getApiUrl(): string
    {
        return getenv('DOCUSEAL_API_URL') ?: self::API_URL;
    }

    private static function shouldVerifySsl(): bool
    {
        $value = getenv('DOCUSEAL_SSL_VERIFY');

        if ($value === false || $value === '') {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
