<?php
require_once __DIR__ . '/../../model/config.php';
require_once '../../controller/demandechequiercontroller.php';

$demandeC = new DemandeChequierController();

// Récupération des données pour les statistiques
$demandes = $demandeC->listDemandes();
$stats = [];

foreach ($demandes as $demande) {
    $date = isset($demande['date_demande']) ? date('Y-m-d', strtotime($demande['date_demande'])) : 'Date inconnue';
    $statut = $demande['statut'] ?? 'Inconnu';

    if (!isset($stats[$date])) {
        $stats[$date] = ['acceptées' => 0, 'refusées' => 0];
    }

    if ($statut === 'Acceptée') {
        $stats[$date]['acceptées']++;
    } elseif ($statut === 'Refusée') {
        $stats[$date]['refusées']++;
    }
}

header('Content-Type: application/json');
echo json_encode($stats);