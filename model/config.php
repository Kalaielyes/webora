<?php


class Config
{
    // Stripe API keys — test mode
    // WARNING: do not commit real secret keys to version control
    public const STRIPE_PUBLISHABLE_KEY = 'pk_test_51TQTz7Cz4nvPjL6ZoAoVbcjpuFDbesqOzGqOYMVyS8w20gZy1SN9XAYkCWt7B2cJN0d7DwZWt0rjlTR7I2m2RMl400kp6PpOQu';
    public const STRIPE_SECRET_KEY      = 'sk_test_51TQTz7Cz4nvPjL6ZUYTiUqdVxKs4E58t6XqZEEkgt3MKYVvyWr1tD4KyUG8QV2NoML1gHjroZXAbUzig7bXiedKT00MDTdVXuE';

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
                try {
                    self::$pdo->exec("ALTER TABLE cartebancaire ADD COLUMN cvv_display VARCHAR(4) NOT NULL DEFAULT ''");
                } catch (PDOException $me) {
                    // Column already exists — ignore duplicate column error
                }
                
                try {
                    self::$pdo->exec("ALTER TABLE utilisateur ADD COLUMN association VARCHAR(150) NULL");
                } catch (PDOException $me) {
                }

                try {
                    self::$pdo->exec("ALTER TABLE utilisateur ADD COLUMN association TINYINT(1) NOT NULL DEFAULT 0");
                } catch (PDOException $me) {
                }

            } catch (PDOException $e) {
                error_log('[NexaBank] DB Connection failed: ' . $e->getMessage());
                die(json_encode(['error' => 'Database unavailable. Please try later.']));
            }
        }
        return self::$pdo;
    }

 
}