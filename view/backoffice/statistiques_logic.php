<?php
/**
 * Statistiques Logic - Unified Integration
 */
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../controller/DemandeChequierController.php';

$demandeC = new DemandeChequierController();
$demandes = $demandeC->listDemandes();

$stats = [];
$totaux = ['acceptées' => 0, 'refusées' => 0, 'en_attente' => 0];

foreach ($demandes as $demande) {
    $date = isset($demande['date_demande']) ? date('Y-m-d', strtotime($demande['date_demande'])) : 'Date inconnue';
    $statut = $demande['statut'] ?? 'Inconnu';

    if (!isset($stats[$date])) {
        $stats[$date] = ['acceptées' => 0, 'refusées' => 0, 'en_attente' => 0];
    }

    if ($statut === 'Acceptée') {
        $stats[$date]['acceptées']++;
        $totaux['acceptées']++;
    } elseif ($statut === 'Refusée') {
        $stats[$date]['refusées']++;
        $totaux['refusées']++;
    } else {
        $stats[$date]['en_attente']++;
        $totaux['en_attente']++;
    }
}

ksort($stats);

header('Content-Type: application/json');
echo json_encode([
    'stats'  => $stats,
    'totaux' => $totaux,
    'total'  => array_sum($totaux)
]);
