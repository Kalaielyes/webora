<?php


class Config
{
    private static ?PDO $pdo = null;

    private function construct() {}
    private function clone() {}

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
                // Auto-migrate: add cvv_display column if not exists
                try {
                    self::$pdo->exec("ALTER TABLE cartebancaire ADD COLUMN cvv_display VARCHAR(4) NOT NULL DEFAULT ''");
                } catch (PDOException $me) {
                    // Column already exists — ignore duplicate column error
                }
                try {
                    self::$pdo->exec("ALTER TABLE projet ADD COLUMN secteur VARCHAR(50) NOT NULL DEFAULT ''");
                } catch (PDOException $me) {
                    // Column already exists — ignore duplicate column error
                }
                try {
                    self::$pdo->exec("ALTER TABLE projet ADD COLUMN date_limite DATE NOT NULL DEFAULT '1970-01-01'");
                } catch (PDOException $me) {
                    // Column already exists — ignore duplicate column error
                }
                try {
                    self::$pdo->exec("ALTER TABLE projet ADD COLUMN request_code VARCHAR(50) NOT NULL DEFAULT ''");
                } catch (PDOException $me) {
                    // Column already exists — ignore duplicate column error
                }
                try {
                    self::$pdo->exec("ALTER TABLE projet MODIFY COLUMN status ENUM('EN_COURS','TERMINE','ANNULE','EN_ATTENTE','VALIDE','REFUSE') NOT NULL DEFAULT 'EN_ATTENTE'");
                } catch (PDOException $me) {
                    // Column already exists or enum already includes the values
                }
                try {
                    self::$pdo->exec("CREATE TABLE IF NOT EXISTS investissement (
                        id_investissement INT AUTO_INCREMENT PRIMARY KEY,
                        id_projet INT NOT NULL,
                        id_investisseur INT NOT NULL,
                        montant_investi DECIMAL(15,2) NOT NULL DEFAULT 0,
                        date_investissement DATE NOT NULL DEFAULT '1970-01-01',
                        status ENUM('EN_ATTENTE','VALIDE','REFUSE','ANNULE') NOT NULL DEFAULT 'EN_ATTENTE',
                        commentaire TEXT NOT NULL,
                        INDEX idx_investisseur (id_investisseur),
                        INDEX idx_projet (id_projet)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                } catch (PDOException $me) {
                    // Table already exists or another compatible condition is already met
                }
            } catch (PDOException $e) {
                error_log('[NexaBank] DB Connection failed: ' . $e->getMessage());
                die(json_encode(['error' => 'Database unavailable. Please try later.']));
            }
        }
        return self::$pdo;
    }

 
}