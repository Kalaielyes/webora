<?php
require_once __DIR__ . '/config.php';

class Garantie
{

    private PDO $pdo;

    private array $types = ['vehicule', 'immobilier', 'garant', 'autre'];

    public function __construct()
    {
        $this->pdo = config::getConnexion();
    }

    public function getAll(): array
    {
        return $this->pdo->query("
            SELECT g.*, d.montant AS dc_montant
            FROM Garantie g
            LEFT JOIN DemandeCredit d ON g.demande_credit_id = d.id
            ORDER BY g.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Garantie WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByDemande(int $demandeId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Garantie WHERE demande_credit_id = ?");
        $stmt->execute([$demandeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
{
    $stmt = $this->pdo->prepare("
        INSERT INTO Garantie (type, description, document, valeur_estimee, demande_credit_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([
        $data['type'],
        $data['description'],
        $data['document'],   // contiendra le chemin du fichier uploadé
        $data['valeur_estimee'],
        $data['demande_credit_id'],
    ]);
}

/**
 * Gère l'upload du fichier de garantie.
 * Retourne le chemin relatif du fichier ou une erreur.
 */
public function handleUpload(array $file): string|false
{
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        return false;
    }

    // Check if temp file exists
    if (!is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    // Get file extension
    $pathInfo = pathinfo($file['name']);
    $ext = strtolower($pathInfo['extension'] ?? '');
    
    if (!in_array($ext, $allowedExtensions, true)) {
        return false;
    }

    $uploadDir = __DIR__ . '/../uploads/garanties/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'gar_' . uniqid() . '.' . $ext;
    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return false;
    }

    return 'uploads/garanties/' . $filename;
}

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE Garantie SET type = ?, description = ?, document = ?, valeur_estimee = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['type'],
            $data['description'],
            $data['document'],
            $data['valeur_estimee'],
            $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM Garantie WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteByDemande(int $demandeId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM Garantie WHERE demande_credit_id = ?");
        return $stmt->execute([$demandeId]);
    }

    // ── Validation serveur ────────────────────────────────────────────────────
    // Retourne tableau associatif champ => message. Vide = données valides.
    public function validate(array $data, bool $requireDemande = true): array
    {
        $errors = [];

        // Demande associée
        if ($requireDemande) {
            if (empty($data['demande_credit_id']) || (int) $data['demande_credit_id'] <= 0)
                $errors['demande_credit_id'] = 'Veuillez sélectionner une demande de crédit valide.';
            else {
                // Vérifier que la demande existe en base
                $stmt = $this->pdo->prepare("SELECT id FROM DemandeCredit WHERE id = ?");
                $stmt->execute([(int) $data['demande_credit_id']]);
                if (!$stmt->fetch())
                    $errors['demande_credit_id'] = 'La demande de crédit sélectionnée n\'existe pas.';
            }
        }

        // Type
        if (!in_array($data['type'] ?? '', $this->types, true))
            $errors['type'] = 'Type de garantie invalide. Valeurs acceptées : ' . implode(', ', $this->types) . '.';

        // Document
        $doc = trim($data['document'] ?? '');
        if (empty($doc) || mb_strlen($doc) < 3)
            $errors['document'] = 'La référence du document est obligatoire (minimum 3 caractères).';
        elseif (mb_strlen($doc) > 255)
            $errors['document'] = 'La référence du document ne peut pas dépasser 255 caractères.';

        // Valeur estimée
        $val = $data['valeur_estimee'] ?? '';
        if (!is_numeric($val) || (float) $val < 0)
            $errors['valeur_estimee'] = 'La valeur estimée doit être un nombre positif ou nul (en TND).';
        elseif ((float) $val > 100_000_000)
            $errors['valeur_estimee'] = 'La valeur estimée semble irréaliste (> 100 000 000 TND).';

        // Description (optionnelle mais limitée)
        if (!empty($data['description']) && mb_strlen($data['description']) > 500)
            $errors['description'] = 'La description ne peut pas dépasser 500 caractères.';

        return $errors;
    }

    /**
     * Update the status of a guarantee
     * @param int $id Guarantee ID
     * @param string $statut Status value (en_attente, approuvee, refusee)
     * @return bool
     */
    public function updateStatus(int $id, string $statut): bool
    {
        $validStatuts = ['en_attente', 'approuvee', 'refusee'];
        if (!in_array($statut, $validStatuts, true))
            return false;
        
        $stmt = $this->pdo->prepare("UPDATE Garantie SET statut = ? WHERE id = ?");
        return $stmt->execute([$statut, $id]);
    }

    /**
     * Get all guarantees with a specific status
     * @param string $statut Status value (en_attente, approuvee, refusee)
     * @return array
     */
    public function getByStatus(string $statut): array
    {
        $stmt = $this->pdo->prepare("
            SELECT g.*, d.montant AS dc_montant
            FROM Garantie g
            LEFT JOIN DemandeCredit d ON g.demande_credit_id = d.id
            WHERE g.statut = ?
            ORDER BY g.id DESC
        ");
        $stmt->execute([$statut]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTypes(): array
    {
        return $this->types;
    }
}