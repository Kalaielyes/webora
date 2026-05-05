<?php

class Config
{
    private static ?PDO $pdo = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnexion(): ?PDO
    {
        if (self::$pdo === null) {
            $host    = '127.0.0.1';
            $dbname  = 'webora';
            $user    = 'root';
            $pass    = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;port=3307;dbname=$dbname;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('[NexaBank] DB Connection failed: ' . $e->getMessage());
                die(json_encode(['error' => 'Database unavailable. Please try later.']));
            }
        }
        return self::$pdo;
    }
}

// Twilio Configuration
if (file_exists(__DIR__ . '/../controller/helpers/config.local.php')) {
    require_once __DIR__ . '/../controller/helpers/config.local.php';
} else {
    define('TWILIO_SID', 'YOUR_TWILIO_SID_HERE');
    define('TWILIO_TOKEN', 'YOUR_TWILIO_TOKEN_HERE');
    define('TWILIO_FROM', 'YOUR_TWILIO_PHONE_NUMBER_HERE');
}