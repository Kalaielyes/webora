<?php
// controller/ExportController.php
session_start();
require_once __DIR__ . '/../models/Demande_Credit.php';
require_once __DIR__ . '/../models/Garantie.php';
require_once __DIR__ . '/../models/config.php';

$type = $_GET['type'] ?? 'demandes'; // 'demandes' or 'garanties'

$demandeModel = new DemandeCredit();
$garantieModel = new Garantie();

$filename = 'export_' . $type . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// UTF-8 BOM so Excel opens it correctly
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

if ($type === 'garanties') {
    $rows = $garantieModel->getAll();
    fputcsv($out, ['ID', 'Demande ID', 'Montant Demande (TND)', 'Type', 'Description', 'Document', 'Valeur EstimÃ©e (TND)', 'Statut'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['demande_credit_id'],
            $r['dc_montant'] ?? '',
            $r['type'],
            $r['description'],
            $r['document'],
            $r['valeur_estimee'],
            $r['statut'] ?? 'en_attente',
        ], ';');
    }
} else {
    $rows = $demandeModel->getAll();
    fputcsv($out, ['ID', 'Montant (TND)', 'DurÃ©e (mois)', 'Taux (%)', 'Statut', 'RÃ©sultat', 'Motif', 'Date Demande', 'Date Traitement', 'Client ID', 'Compte ID'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['montant'],
            $r['duree_mois'],
            $r['taux_interet'],
            $r['statut'],
            $r['resultat'],
            $r['motif_resultat'],
            $r['date_demande'],
            $r['date_traitement'] ?? '',
            $r['client_id'],
            $r['compte_id'],
        ], ';');
    }
}

fclose($out);
exit;
