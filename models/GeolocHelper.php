<?php
/**
 * Helper pour la géolocalisation et les opérations courantes
 * Place ce fichier à: /model/GeolocHelper.php
 */

require_once __DIR__ . '/ClientGeolocation.php';

class GeolocHelper
{
    /**
     * Crée une offre complète de crédit pour un client
     * Intègre tous les paramètres géographiques et de risque
     */
    public static function createCompleteLoanOffer(
        float $requestedAmount,
        string $clientIp = null,
        string $riskProfile = 'medium',
        int $preferredMonths = 36
    ): array
    {
        // Récupérer la localisation
        $location = ClientGeolocation::getClientLocation($clientIp);
        $rules = ClientGeolocation::getRegionalRules($location['country_code']);
        
        // Valider le montant
        if (!ClientGeolocation::isMontantValid($requestedAmount, $location['country_code'])) {
            return [
                'success' => false,
                'error' => 'Montant invalide pour cette région',
                'min' => $rules['min_montant'],
                'max' => $rules['max_montant'],
                'currency' => $location['currency'],
            ];
        }
        
        // Ajuster la durée selon les règles
        $months = min($preferredMonths, $rules['duree_max_mois']);
        
        // Obtenir le taux personnalisé
        $rate = ClientGeolocation::getRecommendedRate($location['country_code'], $riskProfile);
        
        // Calculer les amortissements
        $monthlyRate = $rate / 100 / 12;
        $numerator = $requestedAmount * $monthlyRate * pow(1 + $monthlyRate, $months);
        $denominator = pow(1 + $monthlyRate, $months) - 1;
        $monthlyPayment = $numerator / $denominator;
        $totalWithInterest = $monthlyPayment * $months;
        $interestCost = $totalWithInterest - $requestedAmount;
        
        return [
            'success' => true,
            'location' => [
                'country_code' => $location['country_code'],
                'country_name' => $location['country_name'],
                'city' => $location['city'],
                'currency' => $location['currency'],
                'currency_symbol' => $location['currency_symbol'],
            ],
            'offer' => [
                'amount_requested' => round($requestedAmount, 2),
                'annual_rate' => round($rate, 2),
                'monthly_payment' => round($monthlyPayment, 2),
                'duration_months' => $months,
                'total_with_interest' => round($totalWithInterest, 2),
                'interest_cost' => round($interestCost, 2),
                'total_cost_percentage' => round(($interestCost / $requestedAmount) * 100, 2),
            ],
            'rules' => [
                'min_amount' => $rules['min_montant'],
                'max_amount' => $rules['max_montant'],
                'max_duration' => $rules['duree_max_mois'],
                'default_rate' => $rules['taux_defaut'],
            ],
            'risk_profile' => $riskProfile,
            'ip_address' => $location['ip'],
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Valide une demande de crédit complète
     */
    public static function validateCreditRequest(array $data): array
    {
        $errors = [];
        
        // Valider le montant
        if (empty($data['montant']) || !is_numeric($data['montant'])) {
            $errors['montant'] = 'Montant invalide';
        } else {
            $montant = (float)$data['montant'];
            $countryCode = $data['country_code'] ?? 'TN';
            
            if (!ClientGeolocation::isMontantValid($montant, $countryCode)) {
                $rules = ClientGeolocation::getRegionalRules($countryCode);
                $errors['montant'] = sprintf(
                    'Le montant doit être entre %s et %s pour cette région',
                    number_format($rules['min_montant'], 0, ',', ' '),
                    number_format($rules['max_montant'], 0, ',', ' ')
                );
            }
        }
        
        // Valider la durée
        if (empty($data['duree_mois']) || !is_numeric($data['duree_mois'])) {
            $errors['duree_mois'] = 'Durée invalide';
        } else {
            $duree = (int)$data['duree_mois'];
            $countryCode = $data['country_code'] ?? 'TN';
            $rules = ClientGeolocation::getRegionalRules($countryCode);
            
            if ($duree < 6 || $duree > $rules['duree_max_mois']) {
                $errors['duree_mois'] = sprintf(
                    'La durée doit être entre 6 et %d mois pour cette région',
                    $rules['duree_max_mois']
                );
            }
        }
        
        // Valider le taux
        if (empty($data['taux_interet']) || !is_numeric($data['taux_interet'])) {
            $errors['taux_interet'] = 'Taux invalide';
        }
        
        return $errors;
    }

    /**
     * Formate une offre pour affichage
     */
    public static function formatOfferForDisplay(array $offer, string $locale = 'fr_FR'): string
    {
        if (!$offer['success']) {
            return sprintf(
                '❌ %s - Montant: %s à %s %s',
                $offer['error'],
                number_format($offer['min'], 0, ',', ' '),
                number_format($offer['max'], 0, ',', ' '),
                $offer['currency']
            );
        }

        $o = $offer['offer'];
        $l = $offer['location'];
        
        return sprintf(
            "✅ Offre validée pour %s\n" .
            "💰 Montant: %s %s\n" .
            "📊 Taux: %.2f%%\n" .
            "📅 Durée: %d mois\n" .
            "💳 Mensualité: %s %s\n" .
            "💸 Frais totaux: %s %s (%.2f%%)\n" .
            "✓ Coût total: %s %s",
            $l['country_name'],
            number_format($o['amount_requested'], 0, ',', ' '),
            $l['currency_symbol'],
            $o['annual_rate'],
            $o['duration_months'],
            number_format($o['monthly_payment'], 2, ',', ' '),
            $l['currency_symbol'],
            number_format($o['interest_cost'], 2, ',', ' '),
            $l['currency_symbol'],
            $o['total_cost_percentage'],
            number_format($o['total_with_interest'], 2, ',', ' '),
            $l['currency_symbol']
        );
    }

    /**
     * Exporte une offre en JSON pour API
     */
    public static function exportOfferAsJson(array $offer): string
    {
        return json_encode($offer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Exporte une offre en HTML pour email
     */
    public static function exportOfferAsHtml(array $offer): string
    {
        if (!$offer['success']) {
            return sprintf(
                '<div style="color: red; padding: 10px; background: #ffe6e6; border-radius: 5px;">%s</div>',
                htmlspecialchars($offer['error'])
            );
        }

        $o = $offer['offer'];
        $l = $offer['location'];

        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #667eea;">✅ Votre offre de crédit personnalisée</h2>
    
    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
        <p><strong>Localisation:</strong> {$l['country_name']}</p>
        <p><strong>Devise:</strong> {$l['currency']} ({$l['currency_symbol']})</p>
    </div>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background: #667eea; color: white;">
            <th style="padding: 10px; text-align: left;">Paramètre</th>
            <th style="padding: 10px; text-align: right;">Valeur</th>
        </tr>
        <tr style="background: #f9f9f9;">
            <td style="padding: 10px; border-bottom: 1px solid #ddd;">Montant demandé</td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: right;">
                {$l['currency_symbol']} {$o['amount_requested']}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #ddd;">Taux annuel</td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: right;">
                {$o['annual_rate']}%
            </td>
        </tr>
        <tr style="background: #f9f9f9;">
            <td style="padding: 10px; border-bottom: 1px solid #ddd;">Durée</td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: right;">
                {$o['duration_months']} mois
            </td>
        </tr>
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Mensualité</td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: right; font-weight: bold; color: #28a745;">
                {$l['currency_symbol']} {$o['monthly_payment']}
            </td>
        </tr>
        <tr style="background: #f9f9f9;">
            <td style="padding: 10px; border-bottom: 1px solid #ddd;">Frais d'intérêts</td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: right; color: #d32f2f;">
                {$l['currency_symbol']} {$o['interest_cost']}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px; font-weight: bold;">Coût total</td>
            <td style="padding: 10px; text-align: right; font-weight: bold;">
                {$l['currency_symbol']} {$o['total_with_interest']}
            </td>
        </tr>
    </table>
    
    <p style="font-size: 12px; color: #666; margin-top: 15px;">
        Offre générée le {$offer['generated_at']} | Profil de risque: {$offer['risk_profile']}
    </p>
</div>
HTML;
    }

    /**
     * Vérifie si un client peut accéder aux services de crédit
     */
    public static function isClientEligible(string $countryCode): array
    {
        // Liste des pays autorisés
        $eligibleCountries = [
            'TN', 'FR', 'US', 'CH', 'DE', 'IT', 'ES', 'GB', 'CA', 'BE', 'DZ', 'MA', 'LY'
        ];
        
        $isEligible = in_array(strtoupper($countryCode), $eligibleCountries);
        
        return [
            'eligible' => $isEligible,
            'country_code' => $countryCode,
            'message' => $isEligible 
                ? 'Client éligible pour les services de crédit'
                : 'Pays non autorisé - Veuillez contacter le support',
        ];
    }

    /**
     * Obtient les statistiques par région
     */
    public static function getRegionalStats(): array
    {
        $regions = ['TN', 'FR', 'US', 'CH', 'DE', 'IT', 'ES', 'GB', 'CA', 'BE', 'DZ', 'MA', 'LY'];
        $stats = [];
        
        foreach ($regions as $country) {
            $rules = ClientGeolocation::getRegionalRules($country);
            $stats[$country] = [
                'country_code' => $country,
                'rules' => $rules,
                'recommended_rate' => ClientGeolocation::getRecommendedRate($country, 'medium'),
            ];
        }
        
        return $stats;
    }
}
