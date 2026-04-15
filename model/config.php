<?php
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $modelDir = rtrim(str_replace('\\', '/', __DIR__), '/');
    $gymRoot = dirname($modelDir); // one level up = project root (where index.php lives)
    $webPath = '/' . trim(str_replace($docRoot, '', $gymRoot), '/');
    define('BASE_URL', $protocol . '://' . $host . $webPath);
    define('VIEW_URL', BASE_URL . '/view');
    define('MODEL_URL', BASE_URL . '/model');
    define('IMG_EX_URL', VIEW_URL . '/images/exercice');
    // Front-controller — use APP_URL for all redirects so everything goes through index.php
    define('APP_URL', BASE_URL . '/index.php');
}

class config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        if (!isset(self::$pdo)) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $name = getenv('DB_NAME') ?: 'testt';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;port=$port;dbname=$name;charset=utf8",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (\PDOException $e) {
                error_log('[GymPro] DB error: ' . $e->getMessage());
                throw $e;
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
}
?>