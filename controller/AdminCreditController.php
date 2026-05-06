<?php
session_start();
require_once __DIR__ . '/../model/Demande_Credit.php';
require_once __DIR__ . '/../model/Garantie.php';
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/mailcredit.php';
require_once __DIR__ . '/../model/ai_scoring.php';
require_once __DIR__ . '/../model/ClientGeolocation.php';
class AdminCreditController
{

    private DemandeCredit $demandeModel;
    private Garantie $garantieModel;

    public function __construct()
    {
        $this->demandeModel = new DemandeCredit();
        $this->garantieModel = new Garantie();
    }

    // ── Entry point ───────────────────────────────────────────────────────────
    public function handle(): void
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        match ($action) {
            'create_demande' => $this->createDemande(),
            'update_demande' => $this->updateDemande(),
            'delete_demande' => $this->deleteDemande(),
            'approve_demande' => $this->approveDemande(),
            'refuse_demande' => $this->refuseDemande(),
            'bulk_approve_demandes' => $this->bulkApproveDemandes(),
            'bulk_delete_demandes' => $this->bulkDeleteDemandes(),
            'create_garantie' => $this->createGarantie(),
            'update_garantie' => $this->updateGarantie(),
            'delete_garantie' => $this->deleteGarantie(),
            'update_garantie_status' => $this->updateGarantieStatus(),
            'download_garantie_file' => $this->downloadGarantieFile(),
            default => $this->renderView(),
        };
    }

    // ── DemandeCredit ─────────────────────────────────────────────────────────
    private function createDemande(): void
    {
        $data = $this->collectDemandePost();

        // 🌍 GÉOLOCALISATION: Détecter automatiquement le pays et la devise du client
        $location = ClientGeolocation::getClientLocation();
        $data['country_code'] = $location['country_code'];
        $data['currency'] = $location['currency'];
        $data['ip_client'] = $location['ip'];
        
        // Valider le montant selon les règles régionales
        $rules = ClientGeolocation::getRegionalRules($location['country_code']);
        if (!ClientGeolocation::isMontantValid((float)$data['montant'], $location['country_code'])) {
            $errors = [
                'montant' => sprintf(
                    'Montant invalide pour %s (%s). Min: %s | Max: %s',
                    $location['country_name'],
                    $location['currency'],
                    number_format($rules['min_montant'], 0, ',', ' '),
                    number_format($rules['max_montant'], 0, ',', ' ')
                )
            ];
            $_SESSION['client_location'] = $location;
            $this->renderView(errors: $errors);
            return;
        }

        $errors = array_values($this->demandeModel->validate($data));
        if ($errors) {
            $_SESSION['client_location'] = $location;
            $this->renderView(errors: $errors);
            return;
        }

        // 💾 Stocker la géolocalisation en session
        $_SESSION['client_location'] = $location;

        // ── Scoring IA ──────────────────────────────────────────────────────
        $scoring = analyserSolvabilite($data);
        $data['resultat']       = $scoring['recommendation'];
        $data['motif_resultat'] = '[Score IA: ' . $scoring['score'] . '/100 | Risque: ' . $scoring['risque'] . '] ' . $scoring['motif'];
        // ────────────────────────────────────────────────────────────────────
        try {
            $result = $this->demandeModel->create($data);
            if (!$result) {
                $this->renderView(errors: ['Insert retourné false.']);
                return;
            }
        } catch (\PDOException $e) {
            $this->renderView(errors: ['Erreur DB : ' . $e->getMessage()]);
            return;
        }

        $pdo = config::getConnexion();
        $admins = $pdo->query("SELECT email, prenom, nom FROM admin")->fetchAll();
        foreach ($admins as $admin) {
            sendAdminCreditNotification($admin['email'], $data);
        }
        $this->renderView(success: 'Demande soumise. Admins notifiés: ' . count($admins));
    }

    private function updateDemande(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->collectDemandePost();

        $errors = array_values($this->demandeModel->validate($data, isUpdate: true));
        if ($id <= 0)
            $errors[] = 'ID invalide.';

        if ($errors) {
            $this->renderView(errors: $errors, editDemandeId: $id, activeTab: 'demande');
            return;
        }
        $this->demandeModel->update($id, $data);
        $this->renderView(success: "Demande #$id mise à jour.", activeTab: 'demande');
    }

    private function deleteDemande(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->garantieModel->deleteByDemande($id);
            $this->demandeModel->delete($id);
        }
        $this->renderView(success: "Demande #$id et ses garanties supprimées.");
    }

    private function approveDemande(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->renderView(errors: ['ID invalide.']);
            return;
        }
        
        $demande = $this->demandeModel->getById($id);
        if (!$demande) {
            $this->renderView(errors: ["Demande #$id introuvable."]);
            return;
        }
        
        $this->demandeModel->update($id, [
            'montant' => $demande['montant'],
            'duree_mois' => $demande['duree_mois'],
            'taux_interet' => $demande['taux_interet'],
            'statut' => 'traitee',
            'resultat' => 'approuvee',
            'motif_resultat' => $demande['motif_resultat'] ?? '',
            'date_traitement' => date('Y-m-d'),
        ]);
        $this->renderView(success: "Demande #$id approuvée.");
    }

    private function refuseDemande(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->renderView(errors: ['ID invalide.']);
            return;
        }
        
        $demande = $this->demandeModel->getById($id);
        if (!$demande) {
            $this->renderView(errors: ["Demande #$id introuvable."]);
            return;
        }
        
        $this->demandeModel->update($id, [
            'montant' => $demande['montant'],
            'duree_mois' => $demande['duree_mois'],
            'taux_interet' => $demande['taux_interet'],
            'statut' => 'traitee',
            'resultat' => 'refusee',
            'motif_resultat' => $demande['motif_resultat'] ?? '',
            'date_traitement' => date('Y-m-d'),
        ]);
        $this->renderView(success: "Demande #$id refusée.");
    }

    private function bulkApproveDemandes(): void
    {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $this->renderView(errors: ['Format IDs invalide.']);
            return;
        }
        $ids = array_map(fn($id) => (int)$id, $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        
        if (empty($ids)) {
            $this->renderView(errors: ['Aucune demande sélectionnée.']);
            return;
        }

        $count = 0;
        foreach ($ids as $id) {
            $demande = $this->demandeModel->getById($id);
            if (!$demande) continue;
            
            $this->demandeModel->update($id, [
                'montant' => $demande['montant'],
                'duree_mois' => $demande['duree_mois'],
                'taux_interet' => $demande['taux_interet'],
                'statut' => 'traitee',
                'resultat' => 'approuvee',
                'motif_resultat' => $demande['motif_resultat'] ?? '',
                'date_traitement' => date('Y-m-d'),
            ]);
            $count++;
        }
        $this->renderView(success: "$count demande(s) approuvée(s).");
    }

    private function bulkDeleteDemandes(): void
    {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $this->renderView(errors: ['Format IDs invalide.']);
            return;
        }
        $ids = array_map(fn($id) => (int)$id, $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        
        if (empty($ids)) {
            $this->renderView(errors: ['Aucune demande sélectionnée.']);
            return;
        }

        $count = 0;
        foreach ($ids as $id) {
            $this->garantieModel->deleteByDemande($id);
            $this->demandeModel->delete($id);
            $count++;
        }
        $this->renderView(success: "$count demande(s) et garanties associées supprimées.");
    }

    // ── Garantie 
    private function createGarantie(): void
    {
        $data = $this->collectGarantiePost();
        $errors = array_values($this->garantieModel->validate($data));

        if ($errors) {
            $this->renderView(errors: $errors, activeTab: 'gar');
            return;
        }
        $this->garantieModel->create($data);
        $this->renderView(success: 'Garantie ajoutée.', activeTab: 'gar');
    }

    private function updateGarantie(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->collectGarantiePost();
        $errors = array_values($this->garantieModel->validate($data, requireDemande: false));
        if ($id <= 0)
            $errors[] = 'ID garantie invalide.';

        if ($errors) {
            $this->renderView(errors: $errors, editGarantieId: $id, activeTab: 'gar');
            return;
        }
        $this->garantieModel->update($id, $data);
        $this->renderView(success: "Garantie #$id mise à jour.", activeTab: 'gar');
    }

    private function deleteGarantie(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0)
            $this->garantieModel->delete($id);
        $this->renderView(success: "Garantie #$id supprimée.", activeTab: 'gar');
    }

    private function updateGarantieStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        
        if ($id <= 0 || !in_array($statut, ['en_attente', 'approuvee', 'refusee'])) {
            $this->renderView(errors: ['ID ou statut invalide.'], activeTab: 'gar');
            return;
        }
        
        $this->garantieModel->update($id, ['statut' => $statut]);
        $this->renderView(success: "Statut garantie #$id mis à jour.", activeTab: 'gar');
    }

    /**
     * Handles file download/viewing for guarantee documents
     */
    private function downloadGarantieFile(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID invalid.';
            exit;
        }

        $garantie = $this->garantieModel->getById($id);
        if (!$garantie || empty($garantie['document'])) {
            http_response_code(404);
            echo 'File not found.';
            exit;
        }

        $filePath = __DIR__ . '/../' . $garantie['document'];

        // Verify the file exists and is within the uploads directory
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'File not found on server.';
            exit;
        }

        $realPath = realpath($filePath);
        $uploadDir = realpath(__DIR__ . '/../uploads/garanties/');
        
        if ($realPath === false || strpos($realPath, $uploadDir) !== 0) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        // Get file info
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);
        $filename = basename($filePath);

        // Set headers for viewing (not downloading) for PDFs and images
        if (in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            // Force download for other file types
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }

        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=3600');

        readfile($filePath);
        exit;
    }

    // ── Render ────────────────────────────────────────────────────────────────
    private function renderView(
        array $errors = [],
        string $success = '',
        int $editDemandeId = 0,
        int $editGarantieId = 0,
        string $activeTab = 'demande'
    ): void {
        if (!$editDemandeId && isset($_GET['edit_d']))
            $editDemandeId = (int) $_GET['edit_d'];
        if (!$editGarantieId && isset($_GET['edit_g']))
            $editGarantieId = (int) $_GET['edit_g'];
        if (isset($_GET['tab'])) {
            $activeTab = $_GET['tab'];
        } elseif ($editDemandeId) {
            $activeTab = 'demande';
        } elseif ($editGarantieId) {
            $activeTab = 'garantie';
        }

        $demandes = $this->demandeModel->getAll();
        $garanties = $this->garantieModel->getAll();
        $demandesSelect = $demandes;
        $stats = $this->demandeModel->getStats();
        $editDemande = $editDemandeId ? $this->demandeModel->getById($editDemandeId) : null;
        $editGarantie = $editGarantieId ? $this->garantieModel->getById($editGarantieId) : null;
        $controllerSelf = $_SERVER['SCRIPT_NAME']; // always the controller since we're running from it
        $dbStatus = config::testConnexion();

        require __DIR__ . '/../view/backCredit/back_credit.php';
    }

    // ── Data collectors ───────────────────────────────────────────────────────
    private function collectDemandePost(): array
    {
        return [
            'montant' => trim($_POST['montant'] ?? ''),
            'duree_mois' => trim($_POST['duree_mois'] ?? ''),
            'taux_interet' => trim($_POST['taux_interet'] ?? ''),
            'statut' => $_POST['statut'] ?? 'en_cours',
            'resultat' => $_POST['resultat'] ?? 'en_attente',
            'motif_resultat' => trim($_POST['motif_resultat'] ?? ''),
            'date_demande' => trim($_POST['date_demande'] ?? date('Y-m-d')),
            'date_traitement' => trim($_POST['date_traitement'] ?? ''),
            'client_id' => 1,
            'compte_id' => 1,
        ];
    }

    private function collectGarantiePost(): array
{
    // Gestion de l'upload fichier
    $documentPath = trim($_POST['document'] ?? '');
    if (!empty($_FILES['document_file']['name']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded = $this->garantieModel->handleUpload($_FILES['document_file']);
        if ($uploaded) {
            $documentPath = $uploaded; // chemin fichier remplace la saisie manuelle
        }
    }

    return [
        'demande_credit_id' => (int) ($_POST['demande_credit_id'] ?? 0),
        'type'              => trim($_POST['type'] ?? ''),
        'description'       => trim($_POST['description'] ?? ''),
        'document'          => $documentPath,
        'valeur_estimee'    => trim($_POST['valeur_estimee'] ?? ''),
    ];
}
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
try {
    (new AdminCreditController())->handle();
} catch (PDOException $e) {
    $dbStatus = ['ok' => false, 'error' => $e->getMessage()];
    require __DIR__ . '/../view/frontCredit/front_credit.php';
}