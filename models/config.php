<?php
/**
 * Legafin — Database Configuration + Auto-login
 * No login page: the single DB user is loaded automatically into $_SESSION.
 */

require_once __DIR__ . '/Security.php';

// ── APP_URL — auto-detected, no trailing slash ────────────────────────────────
if (!defined('APP_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base   = preg_replace('#/(views|controllers|models|index\.php).*$#', '', $script);
    define('APP_URL', $scheme . '://' . $host . $base);
}

// ── GEMINI API KEY ───────────────────────────────────────────────────────────
if (!defined('GEMINI_API_KEY')) {
    //define('GEMINI_API_KEY', 'AIzaSyAY_Ali_J-Wrlxu1voyK93kcutbG1K-WWo');
}

class Config
{
    private static ?PDO $pdo = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnexion(): ?PDO
    {
        if (self::$pdo === null) {
            $host    = 'localhost';
            $dbname  = 'webora';
            $user    = 'root';
            $pass    = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('[Legafin] DB Connection failed: ' . $e->getMessage());
                die(json_encode(['error' => 'Database unavailable. Please try later.']));
            }
        }
        return self::$pdo;
    }

    /**
     * Auto-login: load the first (and only) user from the DB into $_SESSION.
     * Call this once at the top of every view/controller entry point.
     * Gives full access to both frontoffice and backoffice — no role restriction.
     */
    public static function autoLogin(): void
    {
        Security::startSession();
        
        // Security Headers
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");

        $pdo  = self::getConnexion();
        $stmt = $pdo->query("SELECT * FROM utilisateur LIMIT 1");
        $row  = $stmt->fetch();

        if (!$row) {
            die('Aucun utilisateur trouvé dans la base de données.');
        }

        // Force ADMIN and always refresh session data so manual DB changes reflect immediately
        $_SESSION['user'] = [
            'id'         => $row['id'],
            'nom'        => $row['nom']        ?? '',
            'prenom'     => $row['prenom']     ?? '',
            'email'          => $row['email']          ?? '',
            'role'           => 'ADMIN',
            'status_kyc'     => $row['status_kyc']     ?? '',
            'face_descriptor' => $row['face_descriptor'] ?? null,
        ];
    }
}
