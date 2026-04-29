<?php
session_start();
require_once __DIR__ . '/../model/Demande_Credit.php';
require_once __DIR__ . '/../model/Garantie.php';
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/mailcredit.php';

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
            'create_garantie' => $this->createGarantie(),
            'update_garantie' => $this->updateGarantie(),
            'delete_garantie' => $this->deleteGarantie(),
            'download_garantie_file' => $this->downloadGarantieFile(),
            default => $this->renderView(),
        };
    }

    // ── DemandeCredit ─────────────────────────────────────────────────────────
    private function createDemande(): void
    {
        $data = $this->collectDemandePost();
        $errors = array_values($this->demandeModel->validate($data));
        if ($errors) {
            $this->renderView(errors: $errors);
            return;
        }

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