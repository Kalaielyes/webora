<?php
session_start();
require_once __DIR__ . '/../model/Demande_Credit.php';
require_once __DIR__ . '/../model/Garantie.php';
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/mailcredit.php';
require_once __DIR__ . '/../model/ai_scoring.php';
require_once __DIR__ . '/../model/ClientGeolocation.php';
require_once __DIR__ . '/../model/GeolocHelper.php';
require_once __DIR__ . '/../model/DocuSealService.php';

class CreditController
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
            'send_signature' => $this->sendSignature(),
            'refuse_demande' => $this->refuseDemande(),
            'bulk_approve_demandes' => $this->bulkApproveDemandes(),
            'bulk_delete_demandes' => $this->bulkDeleteDemandes(),
            'create_garantie' => $this->createGarantie(),
            'update_garantie' => $this->updateGarantie(),
            'delete_garantie' => $this->deleteGarantie(),
            'update_garantie_status' => $this->updateGarantieStatus(),
            'get_client_location'    => $this->getClientLocation(),    // ← ADD THIS
    'get_loan_simulation'    => $this->getLoanSimulation(),
            'check_signature_status' => $this->checkSignatureStatus(),
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
    if (in_array($data['resultat'], ['approuvee', 'refusee'], true)) {
        $data['statut'] = 'traitee';
        $data['date_traitement'] = date('Y-m-d');
    }
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


    if (($data['resultat'] ?? '') === 'approuvee') {
        $newId = (int) config::getConnexion()->lastInsertId();
        $signResult = $newId > 0
            ? $this->sendSignatureForDemande($newId)
            : ['success' => false, 'error' => 'ID nouvelle demande introuvable.'];

        if (empty($signResult['success'])) {
            $scoreMsg = "Score IA: {$scoring['score']}/100 - {$scoring['recommendation']} ({$scoring['risque']})";
            $this->renderView(errors: ["Demande soumise et approuvee. $scoreMsg. Signature non envoyee : " . ($signResult['error'] ?? 'erreur')]);
            return;
        }
    }
    $scoreMsg = "Score IA: {$scoring['score']}/100 — {$scoring['recommendation']} ({$scoring['risque']})";
    $this->renderView(success: "Demande soumise. $scoreMsg");
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
            $this->renderView(errors: ['ID demande invalide.']);
            return;
        }

        $demande = $this->demandeModel->getById($id);
        if (!$demande) {
            $this->renderView(errors: ["Demande #$id introuvable."]);
            return;
        }

        $data = [
            'montant' => $demande['montant'],
            'duree_mois' => $demande['duree_mois'],
            'taux_interet' => $demande['taux_interet'],
            'statut' => 'traitee',
            'resultat' => 'approuvee',
            'motif_resultat' => $demande['motif_resultat'] ?? '',
            'date_traitement' => date('Y-m-d'),
        ];

        if ($this->demandeModel->update($id, $data)) {
            $signResult = $this->sendSignatureForDemande($id);

            if ($signResult['success']) {
                $this->renderView(success: "Demande #$id approuvee et contrat envoye pour signature a " . ($signResult['email'] ?? 'email client') . ".");
            } else {
                $this->renderView(errors: ["Demande #$id approuvee, mais signature non envoyee : " . ($signResult['error'] ?? 'erreur')]);
            }
            return;
        } else {
            $this->renderView(errors: ['Erreur lors de la mise à jour.']);
        }
    }

    private function sendSignature(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->renderView(errors: ['ID demande invalide.']);
            return;
        }

        $result = $this->sendSignatureForDemande($id);

        if ($result['success']) {
            $this->renderView(success: 'Contrat de la demande #' . $id . ' envoye pour signature electronique a ' . ($result['email'] ?? 'email client') . '.');
            return;
        }

        $this->renderView(errors: ['Signature non envoyee : ' . ($result['error'] ?? 'erreur inconnue')]);
    }

    private function sendSignatureForDemande(int $id): array
    {
        $demande = $this->demandeModel->getById($id);

        if (!$demande) {
            return ['success' => false, 'error' => "Demande #$id introuvable."];
        }

        if (($demande['resultat'] ?? '') !== 'approuvee') {
            return ['success' => false, 'error' => 'La demande doit etre approuvee avant signature.'];
        }

        $clientEmail = trim((string) ($demande['client_email'] ?? ''));
        if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email client invalide ou manquant.'];
        }

        $clientName = $_SESSION['user_name']
            ?? trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? ''))
            ?: 'Client LegalFin';

        $signResult = DocuSealService::sendContratForSignature($demande, $clientEmail, $clientName);

        if (empty($signResult['success'])) {
            return $signResult;
        }

        $signResult['email'] = $clientEmail;
        $_SESSION['docuseal_submission_' . $id] = $signResult['submission_id'];

        try {
            $this->ensureDocuSealColumn();
            $pdo = config::getConnexion();
            $pdo->prepare("UPDATE DemandeCredit SET docuseal_submission_id = ? WHERE id = ?")
                ->execute([$signResult['submission_id'], $id]);
        } catch (PDOException $e) {
            error_log('[DocuSeal] Impossible de sauvegarder submission_id #' . $id . ': ' . $e->getMessage());
        }

        return $signResult;
    }

    private function ensureDocuSealColumn(): void
    {
        $pdo = config::getConnexion();
        $stmt = $pdo->query("SHOW COLUMNS FROM DemandeCredit LIKE 'docuseal_submission_id'");

        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE DemandeCredit ADD COLUMN docuseal_submission_id BIGINT NULL AFTER client_email");
        }
    }

    private function checkSignatureStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $submissionId = (int)($_POST['submission_id'] ?? 0);

        if ($submissionId) {
            echo json_encode(DocuSealService::checkSignatureStatus($submissionId));
        } else {
            echo json_encode(['success' => false, 'error' => 'ID manquant']);
        }

        exit;
    }

    private function refuseDemande(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $this->renderView(errors: ['ID demande invalide.']);
            return;
        }

        $demande = $this->demandeModel->getById($id);
        if (!$demande) {
            $this->renderView(errors: ["Demande #$id introuvable."]);
            return;
        }

        $data = [
            'montant' => $demande['montant'],
            'duree_mois' => $demande['duree_mois'],
            'taux_interet' => $demande['taux_interet'],
            'statut' => 'traitee',
            'resultat' => 'refusee',
            'motif_resultat' => $demande['motif_resultat'] ?? '',
            'date_traitement' => date('Y-m-d'),
        ];

        if ($this->demandeModel->update($id, $data)) {
            $this->renderView(success: "Demande #$id refusee.");
        } else {
            $this->renderView(errors: ['Erreur lors de la mise à jour.']);
        }
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
            
            $data = [
                'montant' => $demande['montant'],
                'duree_mois' => $demande['duree_mois'],
                'taux_interet' => $demande['taux_interet'],
                'statut' => 'traitee',
                'resultat' => 'approuvee',
                'motif_resultat' => $demande['motif_resultat'] ?? '',
                'date_traitement' => date('Y-m-d'),
            ];
            
            if ($this->demandeModel->update($id, $data)) {
                $this->sendSignatureForDemande($id);
                $count++;
            }
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
    private function createGarantie(): void
    {
        $data = $this->collectGarantiePost();
        $errors = array_values($this->garantieModel->validate($data));

        if ($errors) {
            $this->renderView(errors: $errors, activeTab: 'garantie');
            return;
        }
        $this->garantieModel->create($data);
        $this->renderView(success: 'Garantie ajoutée.', activeTab: 'garantie');
    }

    private function updateGarantie(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->collectGarantiePost();
        $errors = array_values($this->garantieModel->validate($data, requireDemande: false));
        if ($id <= 0)
            $errors[] = 'ID garantie invalide.';

        if ($errors) {
            $this->renderView(errors: $errors, editGarantieId: $id, activeTab: 'garantie');
            return;
        }
        $this->garantieModel->update($id, $data);
        $this->renderView(success: "Garantie #$id mise à jour.", activeTab: 'garantie');
    }

    private function deleteGarantie(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0)
            $this->garantieModel->delete($id);
        $this->renderView(success: "Garantie #$id supprimée.", activeTab: 'garantie');
    }

    private function updateGarantieStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        
        if ($id <= 0) {
            $this->renderView(errors: ['ID garantie invalide.'], activeTab: 'gar');
            return;
        }

        if (!in_array($statut, ['en_attente', 'approuvee', 'refusee'], true)) {
            $this->renderView(errors: ['Statut invalide.'], activeTab: 'garantie');
            return;
        }

        if ($this->garantieModel->updateStatus($id, $statut)) {
            $statusLabel = match($statut) {
                'en_attente' => 'En attente',
                'approuvee' => 'Approuvée',
                'refusee' => 'Refusée',
            };
            $this->renderView(success: "Garantie #$id marquée comme $statusLabel.", activeTab: 'garantie');
        } else {
            $this->renderView(errors: ['Erreur lors de la mise à jour.'], activeTab: 'garantie');
        }
    }

    // ── Geolocation AJAX ──────────────────────────────────────────────────────────
private function getClientLocation(): void
{
    $location = ClientGeolocation::getClientLocation();
    $rules    = ClientGeolocation::getRegionalRules($location['country_code']);
    $_SESSION['client_geolocation'] = $location;
    $_SESSION['regional_rules']     = $rules;

    header('Content-Type: application/json');
    echo json_encode(['location' => $location, 'rules' => $rules]);
    exit;
}

private function getLoanSimulation(): void
{
    $location = $_SESSION['client_geolocation'] ?? ClientGeolocation::getClientLocation();
    $montant  = (float)($_POST['montant'] ?? 0);

    $offer = GeolocHelper::createCompleteLoanOffer(
        requestedAmount: $montant,
        clientIp: $location['ip'],
        riskProfile: 'medium',
        preferredMonths: 36
    );

    header('Content-Type: application/json');
    if (!$offer['success']) {
        echo json_encode(['error' => $offer['error']]);
        exit;
    }

    $o = $offer['offer'];
    $l = $offer['location'];
    echo json_encode([
        'montant'             => $o['amount_requested'],
        'currency'            => $l['currency'],
        'currency_symbol'     => $l['currency_symbol'],
        'taux_annuel'         => $o['annual_rate'],
        'duree_mois'          => $o['duration_months'],
        'mensualite'          => $o['monthly_payment'],
        'total_avec_interets' => $o['total_with_interest'],
        'frais_interets'      => $o['interest_cost'],
        'date_fin_prevue'     => date('Y-m-d', strtotime('+' . $o['duration_months'] . ' months')),
    ]);
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
        $editDemande = $editDemandeId ? $this->demandeModel->getById($editDemandeId) : null;
        $stats = $this->demandeModel->getStats();
        $editGarantie = $editGarantieId ? $this->garantieModel->getById($editGarantieId) : null;
        $controllerSelf = $_SERVER['SCRIPT_NAME']; // always the controller since we're running from it
        $dbStatus = config::testConnexion();

        require __DIR__ . '/../view/frontCredit/front_credit.php';
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
            'client_email' => $_SESSION['user_email']
                ?? $_SESSION['email']
                ?? $_SESSION['user']['email']
                ?? 'youssefkhca@gmail.com',
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
    (new CreditController())->handle();
} catch (PDOException $e) {
    $dbStatus = ['ok' => false, 'error' => $e->getMessage()];
    require __DIR__ . '/../view/frontCredit/front_credit.php';
}
