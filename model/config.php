<?php


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
                try {
                    self::$pdo->exec("ALTER TABLE cartebancaire ADD COLUMN cvv_display VARCHAR(4) NOT NULL DEFAULT ''");
                } catch (PDOException $me) {
                    // Column already exists — ignore duplicate column error
                }
                
                try {
                    self::$pdo->exec("ALTER TABLE utilisateur ADD COLUMN association VARCHAR(150) NULL");
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