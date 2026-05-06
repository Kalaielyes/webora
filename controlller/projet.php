<?php

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/projet.php';
require_once __DIR__ . '/../model/investissement.php';
require_once __DIR__ . '/../model/score.php';
require_once __DIR__ . '/../model/meeting.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// For development: allow debug mode to set user_id via query param
if (isset($_GET['debug_user_id']) && is_numeric($_GET['debug_user_id'])) {
    $_SESSION['user_id'] = (int)$_GET['debug_user_id'];
}

$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// If no user, attempt to use the first available user (development fallback)
if ($userId === null) {
    try {
        $pdo = Config::getConnexion();
        $stmt = $pdo->query("SELECT id FROM utilisateur LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $userId = (int)$row['id'];
            $_SESSION['user_id'] = $userId;
        }
    } catch (Exception $e) {
        // Ignore errors, proceed with null check below
    }
}

if ($userId === null) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

function getConnexion(): PDO
{
    return Config::getConnexion();
}

function createProject(array $data): int
{
    $sql = "INSERT INTO projet (titre, description, montant_objectif, secteur, date_limite, date_creation, status, id_createur, request_code, taux_rentabilite, temps_retour_brut)
            VALUES (:titre, :description, :montant, :secteur, :date_limite, CURDATE(), :status, :idCreateur, '', :taux_rentabilite, :temps_retour_brut)";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute([
        'titre' => $data['titre'],
        'description' => $data['description'],
        'montant' => $data['montant'],
        'secteur' => $data['secteur'],
        'date_limite' => $data['date_limite'],
        'status' => $data['status'],
        'idCreateur' => $data['id_createur'],
        'taux_rentabilite' => $data['taux_rentabilite'] ?? 0,
        'temps_retour_brut' => $data['temps_retour_brut'] ?? 0,
    ]);
    $projectId = (int)getConnexion()->lastInsertId();
    $requestCode = sprintf('REQ-%s-%03d', date('Ymd'), $projectId);
    $update = getConnexion()->prepare("UPDATE projet SET request_code = :request_code WHERE id_projet = :projectId");
    $update->execute(['request_code' => $requestCode, 'projectId' => $projectId]);
    return $projectId;
}

function getProjectRequestsByUser(int $userId): array
{
    $sql = "SELECT id_projet, request_code, titre, description, montant_objectif, secteur, date_limite, date_creation, status, taux_rentabilite, temps_retour_brut
            FROM projet
            WHERE id_createur = :userId
            ORDER BY date_creation DESC";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute(['userId' => $userId]);
    $projects = $stmt->fetchAll();
    
    foreach ($projects as &$project) {
        $totalInvested = Investissement::getTotalInvestedForProject($project['id_projet']);
        $project['total_investi'] = $totalInvested;
        $project['montant_restant'] = max(0, $project['montant_objectif'] - $totalInvested);
        $project['progression'] = $project['montant_objectif'] > 0 ? round(($totalInvested / $project['montant_objectif']) * 100, 1) : 0;
    }
    
    return $projects;
}

function getAvailableProjects(): array
{
    $sql = "SELECT p.id_projet, p.request_code, p.titre, p.description, p.montant_objectif, p.secteur, p.date_limite, p.date_creation, p.status, p.taux_rentabilite, p.temps_retour_brut, u.nom AS createur_nom
            FROM projet p
            LEFT JOIN utilisateur u ON p.id_createur = u.id
            WHERE p.status = 'VALIDE'
            ORDER BY p.date_creation DESC";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute();
    $projects = $stmt->fetchAll();

    // Add total invested, remaining amount, and progress for each project
    foreach ($projects as &$project) {
        $totalInvested = Investissement::getTotalInvestedForProject($project['id_projet']);
        $project['total_investi'] = $totalInvested;
        $project['montant_restant'] = max(0, $project['montant_objectif'] - $totalInvested);
        $project['progression'] = $project['montant_objectif'] > 0 ? round(($totalInvested / $project['montant_objectif']) * 100, 1) : 0;
    }

    return $projects;
}

function getProjectById(int $projectId): ?array
{
    $sql = "SELECT p.id_projet, p.request_code, p.titre, p.description, p.montant_objectif, p.secteur, p.date_limite, p.date_creation, p.status, p.taux_rentabilite, p.temps_retour_brut, u.nom AS createur_nom
            FROM projet p
            LEFT JOIN utilisateur u ON p.id_createur = u.id
            WHERE p.id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute(['projectId' => $projectId]);
    $result = $stmt->fetch();
    return $result === false ? null : $result;
}

function updateProject(int $projectId, int $userId, array $data): bool
{
    $sql = "UPDATE projet
            SET titre = :titre,
                description = :description,
                montant_objectif = :montant,
                secteur = :secteur,
                date_limite = :date_limite,
                status = :status,
                taux_rentabilite = :taux_rentabilite,
                temps_retour_brut = :temps_retour_brut
            WHERE id_projet = :projectId
              AND id_createur = :userId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute([
        'titre' => $data['titre'],
        'description' => $data['description'],
        'montant' => $data['montant'],
        'secteur' => $data['secteur'],
        'date_limite' => $data['date_limite'],
        'status' => $data['status'],
        'taux_rentabilite' => $data['taux_rentabilite'] ?? 0,
        'temps_retour_brut' => $data['temps_retour_brut'] ?? 0,
        'projectId' => $projectId,
        'userId' => $userId,
    ]);
}

function adminUpdateProject(int $projectId, array $data): bool
{
    $sql = "UPDATE projet
            SET titre = :titre,
                description = :description,
                montant_objectif = :montant,
                secteur = :secteur,
                date_limite = :date_limite,
                status = :status,
                taux_rentabilite = :taux_rentabilite,
                temps_retour_brut = :temps_retour_brut
            WHERE id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute([
        'titre' => $data['titre'],
        'description' => $data['description'],
        'montant' => $data['montant'],
        'secteur' => $data['secteur'],
        'date_limite' => $data['date_limite'],
        'status' => $data['status'],
        'taux_rentabilite' => $data['taux_rentabilite'] ?? 0,
        'temps_retour_brut' => $data['temps_retour_brut'] ?? 0,
        'projectId' => $projectId,
    ]);
}

function getProjectProgressByProjectId(int $projectId): ?array
{
    $sql = "SELECT id, projet_id, pourcentage, description, date_update
            FROM projet_progress
            WHERE projet_id = :projectId
            ORDER BY date_update DESC, id DESC
            LIMIT 1";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute(['projectId' => $projectId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
}

function upsertProjectProgress(int $projectId, float $pourcentage, string $description): bool
{
    $existing = getProjectProgressByProjectId($projectId);
    if ($existing !== null) {
        $sql = "UPDATE projet_progress
                SET pourcentage = :pourcentage,
                    description = :description,
                    date_update = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = getConnexion()->prepare($sql);
        return $stmt->execute([
            'pourcentage' => $pourcentage,
            'description' => $description,
            'id' => $existing['id'],
        ]);
    }

    $sql = "INSERT INTO projet_progress (projet_id, pourcentage, description)
            VALUES (:projectId, :pourcentage, :description)";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute([
        'projectId' => $projectId,
        'pourcentage' => $pourcentage,
        'description' => $description,
    ]);
}

function validateProjectProgressInput(mixed $pourcentageRaw, string $descriptionRaw): array
{
    if ($pourcentageRaw === '' || $pourcentageRaw === null) {
        return ['valid' => false, 'message' => 'Le champ pourcentage est obligatoire.'];
    }

    if (!is_numeric($pourcentageRaw)) {
        return ['valid' => false, 'message' => 'Le pourcentage doit etre une valeur numerique.'];
    }

    $pourcentage = (float)$pourcentageRaw;
    if ($pourcentage < 0 || $pourcentage > 100) {
        return ['valid' => false, 'message' => 'Le pourcentage doit etre compris entre 0 et 100.'];
    }

    $description = trim($descriptionRaw);
    if ($description === '') {
        return ['valid' => false, 'message' => 'La description de progression est obligatoire.'];
    }

    if (mb_strlen($description) < 5) {
        return ['valid' => false, 'message' => 'La description doit contenir au moins 5 caracteres.'];
    }

    if (mb_strlen($description) > 2000) {
        return ['valid' => false, 'message' => 'La description ne doit pas depasser 2000 caracteres.'];
    }

    return [
        'valid' => true,
        'pourcentage' => round($pourcentage, 2),
        'description' => $description,
    ];
}

function adminDeleteProject(int $projectId): bool
{
    $sql = "DELETE FROM projet WHERE id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute(['projectId' => $projectId]);
}

function changeProjectStatus(int $projectId, string $status): bool
{
    $allowedStatuses = ['EN_COURS', 'TERMINE', 'ANNULE', 'EN_ATTENTE', 'VALIDE', 'REFUSE'];
    if (!in_array($status, $allowedStatuses, true)) {
        return false;
    }
    $sql = "UPDATE projet SET status = :status WHERE id_projet = :projectId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute(['status' => $status, 'projectId' => $projectId]);
}

function deleteProject(int $projectId, int $userId): bool
{
    $sql = "DELETE FROM projet WHERE id_projet = :projectId AND id_createur = :userId";
    $stmt = getConnexion()->prepare($sql);
    return $stmt->execute(['projectId' => $projectId, 'userId' => $userId]);
}

function recalcProjectOwnerScore(?int $projectId = null, ?int $ownerId = null): void
{
    try {
        if ($ownerId !== null && $ownerId > 0) {
            Score::recalculateForUser($ownerId);
            return;
        }
        if ($projectId !== null && $projectId > 0) {
            $stmt = getConnexion()->prepare("SELECT id_createur FROM projet WHERE id_projet = :id LIMIT 1");
            $stmt->execute(['id' => $projectId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['id_createur'])) {
                Score::recalculateForUser((int)$row['id_createur']);
            }
        }
    } catch (Exception $e) {
        // Non-blocking for API flow.
    }
}

function parseMeetingInviteEmails(string $rawEmails): array
{
    $parts = preg_split('/[,;]+/', $rawEmails) ?: [];
    $emails = [];
    foreach ($parts as $part) {
        $email = trim($part);
        if ($email === '') {
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid invited email: {$email}");
        }
        $emails[] = mb_strtolower($email);
    }
    $emails = array_values(array_unique($emails));
    if (count($emails) === 0) {
        throw new InvalidArgumentException('At least one invited email is required.');
    }
    return $emails;
}

function createJitsiMeetingLink(string $roomName): string
{
    $settings = Config::getMeetingSettings();
    $base = rtrim($settings['jitsi_base_url'], '/');
    return $base . '/' . rawurlencode($roomName);
}

function getZoomAccessToken(): ?string
{
    $settings = Config::getMeetingSettings();
    $accountId = $settings['zoom_account_id'];
    $clientId = $settings['zoom_client_id'];
    $clientSecret = $settings['zoom_client_secret'];
    if ($accountId === '' || $clientId === '' || $clientSecret === '') {
        return null;
    }

    $tokenUrl = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . rawurlencode($accountId);
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['access_token'])) {
        return null;
    }

    return (string)$data['access_token'];
}

function createZoomMeetingLink(string $startAtIso): ?string
{
    $token = getZoomAccessToken();
    if ($token === null) {
        return null;
    }

    $settings = Config::getMeetingSettings();
    $user = $settings['zoom_user_id'];
    $payload = json_encode([
        'topic' => 'Scheduled Video Meeting',
        'type' => 2,
        'start_time' => $startAtIso,
        'duration' => 45,
        'timezone' => 'UTC',
        'settings' => [
            'join_before_host' => false,
            'waiting_room' => true,
        ],
    ]);

    $ch = curl_init('https://api.zoom.us/v2/users/' . rawurlencode($user) . '/meetings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['join_url'])) {
        return null;
    }

    return (string)$data['join_url'];
}

function getInvestmentDetailsForEmail(int $investmentId): array
{
    try {
        $sql = "SELECT
                    i.id_investissement,
                    CONCAT(u.nom, ' ', u.prenom) AS investor_name,
                    u.email                       AS investor_email,
                    p.titre                       AS project_title,
                    p.secteur                     AS project_sector,
                    i.montant_investi             AS amount,
                    i.date_investissement         AS investment_date,
                    i.status                      AS status
                FROM investissement i
                LEFT JOIN utilisateur u ON i.id_investisseur = u.id
                LEFT JOIN projet      p ON i.id_projet       = p.id_projet
                WHERE i.id_investissement = :id
                LIMIT 1";
        $stmt = Config::getConnexion()->prepare($sql);
        $stmt->execute(['id' => $investmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function sendMeetingEmails(array $emails, array $meetingData, array $investmentInfo = []): array
{
    require_once __DIR__ . '/../model/mailer.php';

    // ── Format date & time nicely ────────────────────────────────────────────
    $rawDate = $meetingData['date'] ?? '';
    $rawTime = $meetingData['time'] ?? '';

    $formattedDate = $rawDate;
    if ($rawDate !== '') {
        $ts = strtotime($rawDate);
        if ($ts !== false) {
            // e.g. «Mercredi 07 mai 2026»
            $days   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
            $months = ['','janvier','février','mars','avril','mai','juin',
                       'juillet','août','septembre','octobre','novembre','décembre'];
            $formattedDate = $days[date('w', $ts)] . ' ' .
                             ltrim(date('d', $ts), '0') . ' ' .
                             $months[(int)date('n', $ts)] . ' ' .
                             date('Y', $ts);
        }
    }

    $meetingLink     = htmlspecialchars($meetingData['meeting_link'] ?? '', ENT_QUOTES);
    $meetingMessage  = htmlspecialchars($meetingData['message'] ?? '', ENT_QUOTES);

    // ── Investment info block ────────────────────────────────────────────────
    $statusLabels = [
        'EN_ATTENTE' => 'En attente', 'VALIDE' => 'Validé',
        'REFUSE'     => 'Refusé',     'ANNULE'  => 'Annulé',
    ];
    $statusColors = [
        'EN_ATTENTE' => '#f59e0b', 'VALIDE' => '#10b981',
        'REFUSE'     => '#ef4444', 'ANNULE'  => '#6b7280',
    ];
    $invStatus      = $investmentInfo['status'] ?? '';
    $statusLabel    = $statusLabels[$invStatus]  ?? $invStatus;
    $statusColor    = $statusColors[$invStatus]  ?? '#6b7280';

    $hasInv = !empty($investmentInfo);
    $invBlock = '';
    if ($hasInv) {
        $amount = isset($investmentInfo['amount'])
            ? number_format((float)$investmentInfo['amount'], 2, ',', ' ') . ' TND'
            : '—';
        $invDate = '';
        if (!empty($investmentInfo['investment_date'])) {
            $ts2 = strtotime($investmentInfo['investment_date']);
            $invDate = $ts2 !== false ? date('d/m/Y', $ts2) : $investmentInfo['investment_date'];
        }
        $invName    = htmlspecialchars($investmentInfo['investor_name']    ?? '', ENT_QUOTES);
        $invEmail   = htmlspecialchars($investmentInfo['investor_email']   ?? '', ENT_QUOTES);
        $projTitle  = htmlspecialchars($investmentInfo['project_title']    ?? '', ENT_QUOTES);
        $projSector = htmlspecialchars($investmentInfo['project_sector']   ?? '', ENT_QUOTES);

        $invBlock = "
        <!-- Investment section -->
        <tr>
          <td style='padding:0 32px 8px'>
            <p style='margin:0 0 12px;font-size:13px;font-weight:700;text-transform:uppercase;
                      letter-spacing:.08em;color:#64748b;border-bottom:1px solid #e2e8f0;padding-bottom:8px'>
              📋 Détails de l'investissement
            </p>
            <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse'>
              <tr>
                <td style='padding:5px 0;font-size:13px;color:#64748b;width:45%'>Investisseur</td>
                <td style='padding:5px 0;font-size:13px;color:#0f172a;font-weight:600'>$invName</td>
              </tr>
              <tr>
                <td style='padding:5px 0;font-size:13px;color:#64748b'>Email</td>
                <td style='padding:5px 0;font-size:13px;color:#0f172a'>$invEmail</td>
              </tr>
              <tr>
                <td style='padding:5px 0;font-size:13px;color:#64748b'>Projet</td>
                <td style='padding:5px 0;font-size:13px;color:#0f172a;font-weight:600'>$projTitle</td>
              </tr>
              <tr>
                <td style='padding:5px 0;font-size:13px;color:#64748b'>Secteur</td>
                <td style='padding:5px 0;font-size:13px;color:#0f172a'>$projSector</td>
              </tr>
              <tr>
                <td style='padding:5px 0;font-size:13px;color:#64748b'>Montant investi</td>
                <td style='padding:5px 0;font-size:13px;color:#0f172a;font-weight:600'>$amount</td>
              </tr>
              <tr>
                <td style='padding:5px 0;font-size:13px;color:#64748b'>Date d'investissement</td>
                <td style='padding:5px 0;font-size:13px;color:#0f172a'>$invDate</td>
              </tr>
              <tr>
                <td style='padding:5px 0;font-size:13px;color:#64748b'>Statut</td>
                <td style='padding:5px 0'>
                  <span style='display:inline-block;padding:2px 10px;border-radius:20px;
                               background:$statusColor;color:#fff;font-size:11px;font-weight:700'>
                    $statusLabel
                  </span>
                </td>
              </tr>
            </table>
          </td>
        </tr>";
    }

    $msgRow = '';
    if ($meetingMessage !== '') {
        $msgRow = "
        <tr>
          <td style='padding:0 32px 24px'>
            <div style='background:#f0fdf4;border-left:3px solid #10b981;border-radius:4px;
                        padding:12px 16px;font-size:13px;color:#065f46;line-height:1.6'>
              <strong>Message :</strong> $meetingMessage
            </div>
          </td>
        </tr>";
    }

    $htmlBody = "
<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'/><meta name='viewport' content='width=device-width,initial-scale=1.0'/></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif'>
  <table width='100%' cellpadding='0' cellspacing='0'>
    <tr><td style='padding:32px 16px'>
      <table width='600' align='center' cellpadding='0' cellspacing='0'
             style='background:#ffffff;border-radius:12px;
                    box-shadow:0 4px 20px rgba(0,0,0,.08);overflow:hidden;max-width:100%'>

        <!-- Header -->
        <tr>
          <td style='background:linear-gradient(135deg,#1e40af,#3b82f6);
                     padding:28px 32px;text-align:center'>
            <p style='margin:0 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;
                      letter-spacing:.12em;color:#bfdbfe'>LegalFin — Invitation de réunion</p>
            <h1 style='margin:0;font-size:22px;font-weight:800;color:#ffffff'>📅 Réunion planifiée</h1>
          </td>
        </tr>

        <!-- Meeting date/time banner -->
        <tr>
          <td style='background:#eff6ff;padding:20px 32px;border-bottom:1px solid #dbeafe'>
            <table width='100%' cellpadding='0' cellspacing='0'>
              <tr>
                <td style='text-align:center;padding:0 12px'>
                  <p style='margin:0 0 4px;font-size:11px;font-weight:700;
                            text-transform:uppercase;letter-spacing:.08em;color:#93c5fd'>📆 Date</p>
                  <p style='margin:0;font-size:16px;font-weight:800;color:#1e40af'>$formattedDate</p>
                </td>
                <td style='text-align:center;padding:0 12px;
                           border-left:1px solid #bfdbfe;border-right:1px solid #bfdbfe'>
                  <p style='margin:0 0 4px;font-size:11px;font-weight:700;
                            text-transform:uppercase;letter-spacing:.08em;color:#93c5fd'>🕐 Heure</p>
                  <p style='margin:0;font-size:16px;font-weight:800;color:#1e40af'>$rawTime</p>
                </td>
                <td style='text-align:center;padding:0 12px'>
                  <p style='margin:0 0 4px;font-size:11px;font-weight:700;
                            text-transform:uppercase;letter-spacing:.08em;color:#93c5fd'>🎥 Plateforme</p>
                  <p style='margin:0;font-size:16px;font-weight:800;color:#1e40af'>Jitsi Meet</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Join button -->
        <tr>
          <td style='padding:24px 32px;text-align:center'>
            <a href='{$meetingData['meeting_link']}' target='_blank'
               style='display:inline-block;background:linear-gradient(135deg,#1e40af,#3b82f6);
                      color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;
                      padding:14px 36px;border-radius:8px;
                      box-shadow:0 4px 14px rgba(59,130,246,.4)'>
              🚀 Rejoindre la réunion
            </a>
            <p style='margin:10px 0 0;font-size:11px;color:#94a3b8'>Ou copiez ce lien : 
               <a href='{$meetingData['meeting_link']}' style='color:#3b82f6;word-break:break-all'>
                 {$meetingData['meeting_link']}
               </a>
            </p>
          </td>
        </tr>

        $invBlock

        $msgRow

        <!-- Footer -->
        <tr>
          <td style='background:#f8fafc;border-top:1px solid #e2e8f0;
                     padding:16px 32px;text-align:center'>
            <p style='margin:0;font-size:11px;color:#94a3b8'>
              Cet email a été envoyé automatiquement par <strong>LegalFin</strong>.<br/>
              Veuillez ne pas répondre à cet email.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";

    $subject = '📅 Réunion planifiée — ' . $formattedDate . ' à ' . $rawTime;

    $sent   = 0;
    $failed = 0;
    foreach ($emails as $email) {
        $recipientName = $investmentInfo['investor_name'] ?? $email;
        $ok = sendMail($email, $recipientName, $subject, $htmlBody);
        if ($ok) { $sent++; } else { $failed++; }
    }

    if ($sent > 0) {
        return [
            'sent'     => true,
            'provider' => 'gmail_smtp',
            'message'  => "$sent email(s) sent successfully.",
        ];
    }
    return [
        'sent'     => false,
        'provider' => 'gmail_smtp',
        'message'  => "All emails failed ($failed).",
    ];
}

function getLatestMeetingByInviteEmail(string $email): ?array
{
    $normalized = mb_strtolower(trim($email));
    if ($normalized === '') {
        return null;
    }

    $sql = "SELECT id_meeting, meeting_date, meeting_time, meeting_link, provider
            FROM meeting_schedule
            WHERE CONCAT(',', LOWER(invited_emails), ',') LIKE :needle
            ORDER BY id_meeting DESC
            LIMIT 1";
    $stmt = getConnexion()->prepare($sql);
    $stmt->execute(['needle' => '%,' . $normalized . ',%']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

$userId = $_SESSION['user_id'] ?? 1;
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'list_requests') {
        echo json_encode(['success' => true, 'data' => getProjectRequestsByUser($userId)]);
        exit;
    }

    if ($action === 'list_available_projects') {
        echo json_encode(['success' => true, 'data' => getAvailableProjects()]);
        exit;
    }

    if ($action === 'list_recommendations') {
        $projects = Projet::getRecommendedProjectsForUser($userId);
        
        // Calculate fields like available projects
        foreach ($projects as &$project) {
            $totalInvested = Investissement::getTotalInvestedForProject($project['id_projet']);
            $project['total_investi'] = $totalInvested;
            $project['montant_restant'] = max(0, $project['montant_objectif'] - $totalInvested);
            $project['progression'] = $project['montant_objectif'] > 0 ? round(($totalInvested / $project['montant_objectif']) * 100, 1) : 0;
        }

        echo json_encode(['success' => true, 'data' => $projects]);
        exit;
    }

    if ($action === 'get_project' && !empty($_GET['id'])) {
        $project = getProjectById((int)$_GET['id']);
        if ($project === null) {
            echo json_encode(['success' => false, 'message' => 'Projet introuvable.']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $project]);
        exit;
    }
}

if ($method === 'POST') {
    if ($action === 'create_meeting_instant') {
        try {
            $invitedEmailsRaw = trim((string)($_POST['invited_emails'] ?? ''));
            $message = trim((string)($_POST['message'] ?? 'Quick meeting invitation'));
            $date = gmdate('Y-m-d');
            $time = gmdate('H:i');

            $invitedEmails = parseMeetingInviteEmails($invitedEmailsRaw);

            // Always create a fresh room so email and join-link are always the same
            $startAt    = new DateTime('now', new DateTimeZone('UTC'));
            $startAtIso = $startAt->format('Y-m-d\TH:i:s\Z');

            $roomName    = 'webora-now-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
            $zoomLink    = createZoomMeetingLink($startAtIso);
            $meetingLink = $zoomLink ?: createJitsiMeetingLink($roomName);
            $provider    = $zoomLink ? 'zoom' : 'jitsi';

            $meetingId = Meeting::create([
                'organiser_id'   => (int)$userId,
                'invited_emails' => implode(',', $invitedEmails),
                'meeting_date'   => $date,
                'meeting_time'   => $time . ':00',
                'message'        => $message,
                'meeting_link'   => $meetingLink,
                'provider'       => $provider,
            ]);

            $investmentId = !empty($_POST['investment_id']) ? (int)$_POST['investment_id'] : 0;
            $investmentInfo = $investmentId > 0 ? getInvestmentDetailsForEmail($investmentId) : [];

            $emailStatus = sendMeetingEmails($invitedEmails, [
                'date'         => $date,
                'time'         => $time,
                'message'      => $message,
                'meeting_link' => $meetingLink,
            ], $investmentInfo);

            echo json_encode([
                'success' => true,
                'data'    => [
                    'meeting_id'     => $meetingId,
                    'meeting_link'   => $meetingLink,
                    'provider'       => $provider,
                    'date'           => $date,
                    'time'           => $time,
                    'email_sent'     => (bool)$emailStatus['sent'],
                    'email_provider' => $emailStatus['provider'],
                    'email_message'  => $emailStatus['message'],
                ],
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
        exit;
    }

    if ($action === 'create_meeting') {
        try {
            $date = trim((string)($_POST['date'] ?? ''));
            $time = trim((string)($_POST['time'] ?? ''));
            $message = trim((string)($_POST['message'] ?? ''));
            $invitedEmailsRaw = trim((string)($_POST['invited_emails'] ?? ''));

            if ($date === '' || $time === '') {
                throw new InvalidArgumentException('Date and time are required.');
            }

            $meetingDate = DateTime::createFromFormat('Y-m-d', $date);
            $meetingTime = DateTime::createFromFormat('H:i', $time);
            if (!$meetingDate || !$meetingTime) {
                throw new InvalidArgumentException('Invalid date/time format.');
            }

            $invitedEmails = parseMeetingInviteEmails($invitedEmailsRaw);
            $startAt = new DateTime($date . ' ' . $time, new DateTimeZone('UTC'));
            $startAtIso = $startAt->format('Y-m-d\TH:i:s\Z');

            $roomName = 'webora-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
            $zoomLink = createZoomMeetingLink($startAtIso);
            $meetingLink = $zoomLink ?: createJitsiMeetingLink($roomName);
            $provider = $zoomLink ? 'zoom' : 'jitsi';

            $meetingId = Meeting::create([
                'organiser_id' => (int)$userId,
                'invited_emails' => implode(',', $invitedEmails),
                'meeting_date' => $date,
                'meeting_time' => $time . ':00',
                'message' => $message,
                'meeting_link' => $meetingLink,
                'provider' => $provider,
            ]);

            $investmentId = !empty($_POST['investment_id']) ? (int)$_POST['investment_id'] : 0;
            $investmentInfo = $investmentId > 0 ? getInvestmentDetailsForEmail($investmentId) : [];

            $emailStatus = sendMeetingEmails($invitedEmails, [
                'date'         => $date,
                'time'         => $time,
                'message'      => $message,
                'meeting_link' => $meetingLink,
            ], $investmentInfo);

            echo json_encode([
                'success' => true,
                'data' => [
                    'meeting_id' => $meetingId,
                    'meeting_link' => $meetingLink,
                    'provider' => $provider,
                    'date' => $date,
                    'time' => $time,
                    'email_sent' => (bool)$emailStatus['sent'],
                    'email_provider' => $emailStatus['provider'],
                    'email_message' => $emailStatus['message'],
                ],
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
        exit;
    }

    if ($action === 'admin_recalculate_scores') {
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        try {
            if ($targetUserId > 0) {
                Score::recalculateForUser($targetUserId);
            } else {
                Score::recalculateAllUsers();
            }
            echo json_encode(['success' => true, 'message' => 'Scores recalcules.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur recalcul scores: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'submit_demande' || $action === 'update_project') {
        $titre = trim($_POST['titre'] ?? '');
        $secteur = trim($_POST['secteur'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $montant = $_POST['montant'] ?? '';
        $date_limite = $_POST['date_limite'] ?? '';
        $status = $_POST['status'] ?? 'EN_ATTENTE';
        $taux_rentabilite = $_POST['taux_rentabilite'] ?? 0;
        $temps_retour_brut = $_POST['temps_retour_brut'] ?? 0;

        if ($titre === '' || $secteur === '' || $description === '' || $montant === '' || $date_limite === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
            exit;
        }

        if (!is_numeric($montant) || $montant < 10000) {
            echo json_encode(['success' => false, 'message' => 'Le montant doit être une valeur numérique minimale de 10 000.']);
            exit;
        }

        try {
            if ($action === 'submit_demande') {
            if (!empty($_POST['project_id'])) {
                echo json_encode(['success' => false, 'message' => 'Impossible de créer une nouvelle demande pendant la modification d\'un projet existant.']);
                exit;
            }
            $projectId = createProject([
                'titre' => $titre,
                'description' => $description,
                'montant' => $montant,
                'secteur' => $secteur,
                'date_limite' => $date_limite,
                'status' => $status,
                'id_createur' => $userId,
                'taux_rentabilite' => $taux_rentabilite,
                'temps_retour_brut' => $temps_retour_brut,
            ]);

            $project = getProjectById($projectId);
            recalcProjectOwnerScore($projectId, (int)$userId);
            echo json_encode([
                'success' => true,
                'reference' => $project['request_code'] ?? sprintf('REQ-%05d', $projectId),
                'message' => 'Demande enregistrée.',
            ]);
            exit;
        }

            if ($action === 'update_project' && !empty($_POST['project_id'])) {
                $projectId = (int)$_POST['project_id'];
                if (updateProject($projectId, $userId, [
                    'titre' => $titre,
                    'description' => $description,
                    'montant' => $montant,
                    'secteur' => $secteur,
                    'date_limite' => $date_limite,
                    'status' => $status,
                    'taux_rentabilite' => $taux_rentabilite,
                    'temps_retour_brut' => $temps_retour_brut,
                ])) {
                    recalcProjectOwnerScore($projectId, $userId);
                    echo json_encode(['success' => true, 'message' => 'Demande mise à jour.']);
                    exit;
                }
                echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour la demande.']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
            exit;
        }
    }

    if ($action === 'admin_update_status' && !empty($_POST['project_id']) && !empty($_POST['new_status'])) {
        $projectId = (int)$_POST['project_id'];
        $newStatus = strtoupper(trim($_POST['new_status']));
        if (changeProjectStatus($projectId, $newStatus)) {
            recalcProjectOwnerScore($projectId, null);
            echo json_encode(['success' => true, 'message' => 'Statut du projet mis à jour.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour le statut du projet.']);
        exit;
    }

    if ($action === 'delete_project' && !empty($_POST['project_id'])) {
        $projectId = (int)$_POST['project_id'];
        if (deleteProject($projectId, $userId)) {
            recalcProjectOwnerScore(null, $userId);
            echo json_encode(['success' => true, 'message' => 'Demande supprimée.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer la demande.']);
        exit;
    }

    // ── Admin CRUD actions (no id_createur restriction) ──────────────────────
    if ($action === 'admin_create_project') {
        $titre       = trim($_POST['titre'] ?? '');
        $secteur     = trim($_POST['secteur'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $montant     = $_POST['montant'] ?? '';
        $date_limite = $_POST['date_limite'] ?? '';
        $status      = $_POST['status'] ?? 'VALIDE';
        $taux_rentabilite = $_POST['taux_rentabilite'] ?? 0;
        $temps_retour_brut = $_POST['temps_retour_brut'] ?? 0;

        if ($titre === '' || $secteur === '' || $description === '' || $montant === '' || $date_limite === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
            exit;
        }
        if (!is_numeric($montant) || (float)$montant < 1) {
            echo json_encode(['success' => false, 'message' => 'Le montant doit être une valeur numérique positive.']);
            exit;
        }
        try {
            $projectId = createProject([
                'titre'       => $titre,
                'description' => $description,
                'montant'     => $montant,
                'secteur'     => $secteur,
                'date_limite' => $date_limite,
                'status'      => $status,
                'id_createur' => $userId,
                'taux_rentabilite' => $taux_rentabilite,
                'temps_retour_brut' => $temps_retour_brut,
            ]);
            $project = getProjectById($projectId);
            recalcProjectOwnerScore($projectId, (int)$userId);
            echo json_encode(['success' => true, 'message' => 'Projet créé avec succès.', 'data' => $project]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'admin_update_project' && !empty($_POST['project_id'])) {
        $projectId   = (int)$_POST['project_id'];
        $titre       = trim($_POST['titre'] ?? '');
        $secteur     = trim($_POST['secteur'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $montant     = $_POST['montant'] ?? '';
        $date_limite = $_POST['date_limite'] ?? '';
        $status      = $_POST['status'] ?? 'EN_ATTENTE';
        $taux_rentabilite = $_POST['taux_rentabilite'] ?? 0;
        $temps_retour_brut = $_POST['temps_retour_brut'] ?? 0;

        if ($titre === '' || $secteur === '' || $description === '' || $montant === '' || $date_limite === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
            exit;
        }
        if (!is_numeric($montant) || (float)$montant < 1) {
            echo json_encode(['success' => false, 'message' => 'Le montant doit être une valeur numérique positive.']);
            exit;
        }
        try {
            if (adminUpdateProject($projectId, [
                'titre'       => $titre,
                'description' => $description,
                'montant'     => $montant,
                'secteur'     => $secteur,
                'date_limite' => $date_limite,
                'status'      => $status,
                'taux_rentabilite' => $taux_rentabilite,
                'temps_retour_brut' => $temps_retour_brut,
            ])) {
                $project = getProjectById($projectId);
                recalcProjectOwnerScore($projectId, null);
                echo json_encode(['success' => true, 'message' => 'Projet mis à jour.', 'data' => $project]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour le projet.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'admin_delete_project' && !empty($_POST['project_id'])) {
        $projectId = (int)$_POST['project_id'];
        $ownerIdForScore = null;
        try {
            $p = getProjectById($projectId);
            if ($p && isset($p['id_createur'])) {
                $ownerIdForScore = (int)$p['id_createur'];
            }
        } catch (Exception $e) {}
        try {
            if (adminDeleteProject($projectId)) {
                recalcProjectOwnerScore(null, $ownerIdForScore);
                echo json_encode(['success' => true, 'message' => 'Projet supprimé avec succès.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le projet.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'admin_get_project_progress' && !empty($_POST['project_id'])) {
        $projectId = (int)$_POST['project_id'];
        $project = getProjectById($projectId);
        if ($project === null) {
            echo json_encode(['success' => false, 'message' => 'Projet introuvable.']);
            exit;
        }
        $progress = getProjectProgressByProjectId($projectId);
        echo json_encode([
            'success' => true,
            'data' => [
                'projet_id' => (int)$project['id_projet'],
                'pourcentage' => (float)($progress['pourcentage'] ?? 0),
                'description' => $progress['description'] ?? '',
                'date_update' => $progress['date_update'] ?? null,
            ],
        ]);
        exit;
    }

    if ($action === 'admin_update_project_progress' && !empty($_POST['project_id'])) {
        $projectId = (int)$_POST['project_id'];
        $pourcentage = $_POST['pourcentage'] ?? 0;
        $description = trim($_POST['description'] ?? '');

        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identifiant de projet invalide.']);
            exit;
        }

        $project = getProjectById($projectId);
        if ($project === null) {
            echo json_encode(['success' => false, 'message' => 'Projet introuvable.']);
            exit;
        }

        $validation = validateProjectProgressInput($pourcentage, $description);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            exit;
        }

        try {
            if (upsertProjectProgress($projectId, $validation['pourcentage'], $validation['description'])) {
                recalcProjectOwnerScore($projectId, null);
                echo json_encode(['success' => true, 'message' => 'Progression du projet mise à jour.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour la progression du projet.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Action invalide.']);
