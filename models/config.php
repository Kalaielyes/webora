<?php
<<<<<<< HEAD
/**
 * Legafin — Database Configuration + Auto-login
 * No login page: the single DB user is loaded automatically into $_SESSION.
 */

date_default_timezone_set('Africa/Tunis');
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
    define('GEMINI_API_KEY', 'AIzaSyBsOs75Aq5Dc69ZrbbJBqTvWNTVrP2Ewi4');
}
=======
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $modelDir = rtrim(str_replace('\\', '/', __DIR__), '/');
    $appRoot  = dirname($modelDir);          
    $webPath  = '/' . trim(str_replace($docRoot, '', $appRoot), '/');
    define('BASE_URL',  $protocol . '://' . $host . $webPath);
<<<<<<< HEAD
    define('VIEW_URL',  BASE_URL . '/views');
    define('MODEL_URL', BASE_URL . '/models');

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
=======
    define('VIEW_URL',  BASE_URL . '/view');
    define('MODEL_URL', BASE_URL . '/model');
    define('APP_URL',   BASE_URL . '/index.php');
}
class config {
    private static ?PDO $pdo = null;
    public static function getConnexion() : PDO {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $name = getenv('DB_NAME') ?: 'webora';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
                    $user, $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (Exception $e) {
                error_log('[NexaBank] DB error: ' . $e->getMessage());
                die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
                    <h2>NexaBank</h2><p>Service temporarily unavailable.</p>
                </div>');
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)
            }
        }
        return self::$pdo;
    }
<<<<<<< HEAD

    /**
     * Auto-login: load the first (and only) user from the DB into $_SESSION.
     * Call this once at the top of every view/controller entry point.
     * Gives full access to both frontoffice and backoffice — no role restriction.
     */
    public static function autoLogin(): void
    {
        require_once __DIR__ . '/Session.php';
        Session::start();
        
        // If already logged in via the new system, don't overwrite
        if (isset($_SESSION['user_id']) || isset($_SESSION['user'])) {
            return;
        }

        // Security Headers
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");

        $pdo  = self::getConnexion();
        $stmt = $pdo->query("SELECT * FROM utilisateur LIMIT 1");
        $row  = $stmt->fetch();

        if (!$row) {
            return; // No user to auto-login
        }

        $_SESSION['user'] = [
            'id'         => $row['id'],
            'nom'        => $row['nom']        ?? '',
            'prenom'     => $row['prenom']     ?? '',
            'email'      => $row['email']       ?? '',
            'role'       => $row['role']        ?? 'CLIENT',
            'status_kyc' => $row['status_kyc']  ?? '',
        ];
        
        // Also set new system keys for compatibility
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role']    = $row['role'] ?? 'CLIENT';
    }
=======
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)
}
