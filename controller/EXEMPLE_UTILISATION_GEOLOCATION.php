<?php
/**
 * Exemple d'utilisation de ClientGeolocation dans CreditController
 * Ajouter ces Ã©lÃ©ments Ã  votre CreditController.php
 */

// 1. Ajouter l'import en haut du fichier
// require_once __DIR__ . '/../models/ClientGeolocation.php';

// 2. Ajouter une mÃ©thode pour initialiser les donnÃ©es gÃ©ographiques
public function initializeWithGeolocation(): void
{
    // RÃ©cupÃ©rer la localisation du client
    $location = ClientGeolocation::getClientLocation();
    
    // Stocker dans la session
    $_SESSION['client_geolocation'] = $location;
    
    // RÃ©cupÃ©rer les rÃ¨gles de crÃ©dit pour ce pays
    $rules = ClientGeolocation::getRegionalRules($location['country_code']);
    $_SESSION['regional_rules'] = $rules;
}

// 3. Modifier la mÃ©thode createDemande() pour utiliser la gÃ©olocalisation
private function createDemande(): void
{
    // RÃ©cupÃ©rer les donnÃ©es du formulaire
    $data = $this->collectDemandePost();
    
    // RÃ©cupÃ©rer la gÃ©olocalisation si pas dÃ©jÃ  en session
    if (empty($_SESSION['client_geolocation'])) {
        $this->initializeWithGeolocation();
    }
    
    $location = $_SESSION['client_geolocation'] ?? ClientGeolocation::getClientLocation();
    $rules = ClientGeolocation::getRegionalRules($location['country_code']);
    
    // Ajouter les donnÃ©es de gÃ©olocalisation Ã  $data
    $data['country_code'] = $location['country_code'];
    $data['currency'] = $location['currency'];
    $data['ip_client'] = $location['ip'];
    
    // Valider le montant selon les rÃ¨gles rÃ©gionales
    if (!ClientGeolocation::isMontantValid((float)$data['montant'], $location['country_code'])) {
        $errors = [
            'montant' => sprintf(
                'Le montant doit Ãªtre entre %s et %s %s pour %s',
                number_format($rules['min_montant'], 0, ',', ' '),
                number_format($rules['max_montant'], 0, ',', ' '),
                $location['currency'],
                $location['country_name']
            )
        ];
        $this->renderView(errors: array_values($errors));
        return;
    }
    
    // Valider la validation standard
    $errors = array_values($this->demandeModel->validate($data));
    if ($errors) {
        $this->renderView(errors: $errors);
        return;
    }
    
    // CrÃ©er la demande
    if ($this->demandeModel->create($data)) {
        $_SESSION['success'] = 'Demande de crÃ©dit crÃ©Ã©e avec succÃ¨s!';
    } else {
        $_SESSION['error'] = 'Erreur lors de la crÃ©ation de la demande.';
    }
    
    // Rediriger
    header('Location: ' . APP_URL);
    exit();
}

// 4. MÃ©thode pour afficher les informations de gÃ©olocalisation en front-end
public function getClientLocationInfo(): array
{
    $location = $_SESSION['client_geolocation'] ?? ClientGeolocation::getClientLocation();
    $rules = ClientGeolocation::getRegionalRules($location['country_code']);
    
    return [
        'location' => $location,
        'rules' => $rules,
        'recommended_rate' => ClientGeolocation::getRecommendedRate($location['country_code']),
    ];
}

// 5. MÃ©thode pour adapter les tarifs en fonction du profil de risque
public function getPersonalizedOffer(float $montant, string $riskProfile = 'medium'): array
{
    $location = $_SESSION['client_geolocation'] ?? ClientGeolocation::getClientLocation();
    $rules = ClientGeolocation::getRegionalRules($location['country_code']);
    
    // VÃ©rifier la validitÃ© du montant
    if (!ClientGeolocation::isMontantValid($montant, $location['country_code'])) {
        return ['error' => 'Montant non valide pour cette rÃ©gion'];
    }
    
    // Calculer le taux personnalisÃ©
    $taux = ClientGeolocation::getRecommendedRate($location['country_code'], $riskProfile);
    
    // Calculer la durÃ©e optimale (en mois)
    $dureeOptimale = min(36, $rules['duree_max_mois']);
    
    // Calculer les mensualitÃ©s
    $monthlyRate = $taux / 100 / 12;
    $numerator = $montant * $monthlyRate * pow(1 + $monthlyRate, $dureeOptimale);
    $denominator = pow(1 + $monthlyRate, $dureeOptimale) - 1;
    $mensualite = $numerator / $denominator;
    
    // Montant total avec intÃ©rÃªts
    $totalAvecInterets = $mensualite * $dureeOptimale;
    $fraisInterets = $totalAvecInterets - $montant;
    
    return [
        'montant' => $montant,
        'currency' => $location['currency'],
        'currency_symbol' => $location['currency_symbol'],
        'country' => $location['country_name'],
        'taux_annuel' => $taux,
        'duree_mois' => $dureeOptimale,
        'mensualite' => round($mensualite, 2),
        'total_avec_interets' => round($totalAvecInterets, 2),
        'frais_interets' => round($fraisInterets, 2),
        'date_fin_prevue' => date('Y-m-d', strtotime("+$dureeOptimale months")),
    ];
}
