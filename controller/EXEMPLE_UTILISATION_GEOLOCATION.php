<?php
/**
 * Exemple d'utilisation de ClientGeolocation dans CreditController
 * Ajouter ces éléments à votre CreditController.php
 */

// 1. Ajouter l'import en haut du fichier
// require_once __DIR__ . '/../model/ClientGeolocation.php';

// 2. Ajouter une méthode pour initialiser les données géographiques
public function initializeWithGeolocation(): void
{
    // Récupérer la localisation du client
    $location = ClientGeolocation::getClientLocation();
    
    // Stocker dans la session
    $_SESSION['client_geolocation'] = $location;
    
    // Récupérer les règles de crédit pour ce pays
    $rules = ClientGeolocation::getRegionalRules($location['country_code']);
    $_SESSION['regional_rules'] = $rules;
}

// 3. Modifier la méthode createDemande() pour utiliser la géolocalisation
private function createDemande(): void
{
    // Récupérer les données du formulaire
    $data = $this->collectDemandePost();
    
    // Récupérer la géolocalisation si pas déjà en session
    if (empty($_SESSION['client_geolocation'])) {
        $this->initializeWithGeolocation();
    }
    
    $location = $_SESSION['client_geolocation'] ?? ClientGeolocation::getClientLocation();
    $rules = ClientGeolocation::getRegionalRules($location['country_code']);
    
    // Ajouter les données de géolocalisation à $data
    $data['country_code'] = $location['country_code'];
    $data['currency'] = $location['currency'];
    $data['ip_client'] = $location['ip'];
    
    // Valider le montant selon les règles régionales
    if (!ClientGeolocation::isMontantValid((float)$data['montant'], $location['country_code'])) {
        $errors = [
            'montant' => sprintf(
                'Le montant doit être entre %s et %s %s pour %s',
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
    
    // Créer la demande
    if ($this->demandeModel->create($data)) {
        $_SESSION['success'] = 'Demande de crédit créée avec succès!';
    } else {
        $_SESSION['error'] = 'Erreur lors de la création de la demande.';
    }
    
    // Rediriger
    header('Location: ' . APP_URL);
    exit();
}

// 4. Méthode pour afficher les informations de géolocalisation en front-end
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

// 5. Méthode pour adapter les tarifs en fonction du profil de risque
public function getPersonalizedOffer(float $montant, string $riskProfile = 'medium'): array
{
    $location = $_SESSION['client_geolocation'] ?? ClientGeolocation::getClientLocation();
    $rules = ClientGeolocation::getRegionalRules($location['country_code']);
    
    // Vérifier la validité du montant
    if (!ClientGeolocation::isMontantValid($montant, $location['country_code'])) {
        return ['error' => 'Montant non valide pour cette région'];
    }
    
    // Calculer le taux personnalisé
    $taux = ClientGeolocation::getRecommendedRate($location['country_code'], $riskProfile);
    
    // Calculer la durée optimale (en mois)
    $dureeOptimale = min(36, $rules['duree_max_mois']);
    
    // Calculer les mensualités
    $monthlyRate = $taux / 100 / 12;
    $numerator = $montant * $monthlyRate * pow(1 + $monthlyRate, $dureeOptimale);
    $denominator = pow(1 + $monthlyRate, $dureeOptimale) - 1;
    $mensualite = $numerator / $denominator;
    
    // Montant total avec intérêts
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
