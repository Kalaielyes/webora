<?php
// =============================================================
//  model/config.php — NexaBank
//  Connexion PDO + constantes URL
// =============================================================

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $modelDir = rtrim(str_replace('\\', '/', __DIR__), '/');
    $appRoot  = dirname($modelDir);          // un niveau au-dessus de /model
    $webPath  = '/' . trim(str_replace($docRoot, '', $appRoot), '/');

    define('BASE_URL',  $protocol . '://' . $host . $webPath);
    define('VIEW_URL',  BASE_URL . '/view');
    define('MODEL_URL', BASE_URL . '/model');
    define('APP_URL',   BASE_URL . '/index.php');
}

class config {

    private static ?PDO $pdo = null;

    // ─────────────────────────────────────────────
    //  Connexion PDO — base : webora
    // ─────────────────────────────────────────────
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
            }
        }
        return self::$pdo;
    }
}
