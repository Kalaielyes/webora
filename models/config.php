<?php
/**
 * Legafin — Database Configuration + Auto-login
 * No login page: the single DB user is loaded automatically into $_SESSION.
 */

date_default_timezone_set('Africa/Tunis');
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/EnvLoader.php';

// Load .env from root
EnvLoader::load(dirname(__DIR__) . '/.env');

// ── APP_URL — auto-detected, no trailing slash ────────────────────────────────
if (!defined('APP_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base   = preg_replace('#/(view|controller|models|index\.php).*$#', '', $script);
    define('APP_URL', $scheme . '://' . $host . $base);
    define('ASSETS_URL', APP_URL . '/view/assets');
}

// ── GEMINI API KEY ───────────────────────────────────────────────────────────
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', 'AIzaSyDFBh_5HHM6NBUWuximKKlRyybnZLZQN0U');
}

// ── STRIPE KEYS (don module) ─────────────────────────────────────────────────
if (!defined('STRIPE_PUBLISHABLE_KEY')) {
    define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY']
        ?? 'pk_test_51TQTz7Cz4nvPjL6ZoAoVbcjpuFDbesqOzGqOYMVyS8w20gZy1SN9XAYkCWt7B2cJN0d7DwZWt0rjlTR7I2m2RMl400kp6PpOQu');
}
if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY']
        ?? 'sk_test_51TQTz7Cz4nvPjL6ZUYTiUqdVxKs4E58t6XqZEEkgt3MKYVvyWr1tD4KyUG8QV2NoML1gHjroZXAbUzig7bXiedKT00MDTdVXuE');
}

// ── OLLAMA AI ENDPOINT (don module) ─────────────────────────────────────────
if (!defined('OLLAMA_URL')) {
    define('OLLAMA_URL', $_ENV['OLLAMA_URL'] ?? 'http://51.83.4.21:11434/api/generate');
}
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $modelDir = rtrim(str_replace('\\', '/', __DIR__), '/');
    $appRoot  = dirname($modelDir);          
    $webPath  = '/' . trim(str_replace($docRoot, '', $appRoot), '/');
    define('BASE_URL',  $protocol . '://' . $host . $webPath);
    define('VIEW_URL',  BASE_URL . '/view');
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
            $host    = $_ENV['DB_HOST'] ?? 'localhost';
            $port    = $_ENV['DB_PORT'] ?? 3306;  // XAMPP MySQL port
            $dbname  = $_ENV['DB_NAME'] ?? 'webora';
            $user    = $_ENV['DB_USER'] ?? 'root';
            $pass    = $_ENV['DB_PASS'] ?? '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('[Legafin] DB Connection failed: ' . $e->getMessage());
                error_log('[Legafin] DB Connection failed: ' . $e->getMessage());
                die($e->getMessage());
            }
        }
        return self::$pdo;
    }

    public static function testConnexion(): array
    {
        try {
            self::getConnexion()->query('SELECT 1');
            return ['ok' => true, 'error' => ''];
        } catch (\PDOException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

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

        $_SESSION['user'] = $row;
        
        // Also set new system keys for compatibility
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role']    = $row['role'] ?? 'CLIENT';
    }
    public static function getMeetingSettings(): array
    {
        return [
            'jitsi_base_url'    => self::getEnv('JITSI_BASE_URL', 'https://meet.jit.si'),
            'zoom_account_id'   => self::getEnv('ZOOM_ACCOUNT_ID', ''),
            'zoom_client_id'    => self::getEnv('ZOOM_CLIENT_ID', ''),
            'zoom_client_secret'=> self::getEnv('ZOOM_CLIENT_SECRET', ''),
            'zoom_user_id'      => self::getEnv('ZOOM_USER_ID', 'me'),
            'sender_name'       => self::getEnv('MEETING_SENDER_NAME', 'Webora'),
            'sender_email'      => self::getEnv('MEETING_SENDER_EMAIL', 'noreply@example.com'),
        ];
    }

    private static function getEnv(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return (string)$value;
    }
}
