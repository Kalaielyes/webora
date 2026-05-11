<?php
/**
 * Database Setup & Migration Helper
 * Ensures all required tables for cheque module exist in webora database
 */

function ensureChequeTables() {
    $db = Config::getConnexion();
    
    $tables = [
        // Chequer table
        'chequier' => "
            CREATE TABLE IF NOT EXISTS `chequier` (
                `id_chequier` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `numero_chequier` VARCHAR(50) UNIQUE,
                `date_creation` DATE,
                `date_expiration` DATE,
                `statut` ENUM('actif', 'inactif', 'expiré') DEFAULT 'actif',
                `nombre_feuilles` INT DEFAULT 25,
                `id_demande` INT(11),
                `id_Compte` INT(11),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`id_demande`) REFERENCES `demande_chequier`(`id_demande`) ON DELETE SET NULL,
                FOREIGN KEY (`id_Compte`) REFERENCES `comptebancaire`(`id_Compte`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",

        // Cheque table
        'cheque' => "
            CREATE TABLE IF NOT EXISTS `cheque` (
                `id_cheque` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `id_chequier` INT(11),
                `numero_cheque` VARCHAR(30),
                `montant` DECIMAL(12, 3),
                `date_emission` DATE,
                `beneficiaire` VARCHAR(200),
                `rib_beneficiaire` VARCHAR(50),
                `cin_beneficiaire` VARCHAR(20),
                `lettres` TEXT,
                `agence` VARCHAR(100),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`id_chequier`) REFERENCES `chequier`(`id_chequier`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",

        // Demande Chequier table
        'demande_chequier' => "
            CREATE TABLE IF NOT EXISTS `demande_chequier` (
                `id_demande` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `nom et prenom` VARCHAR(200),
                `id_compte` INT(11),
                `motif` VARCHAR(200),
                `type_chequier` VARCHAR(50),
                `nombre_cheques` VARCHAR(10),
                `montant_max_par_cheque` DECIMAL(12, 3),
                `mode_reception` VARCHAR(50),
                `adresse_agence` VARCHAR(200),
                `telephone` VARCHAR(20),
                `email` VARCHAR(100),
                `commentaire` TEXT,
                `statut` ENUM('En attente', 'Acceptée', 'Rejetée') DEFAULT 'En attente',
                `date_demande` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`id_compte`) REFERENCES `comptebancaire`(`id_Compte`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",

        // Admin Faces table (for biometric)
        'admin_faces' => "
            CREATE TABLE IF NOT EXISTS `admin_faces` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `admin_id` INT(11) NOT NULL,
                `face_descriptor` LONGTEXT,
                `pin_code` VARCHAR(255),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `admin_id_unique` (`admin_id`),
                FOREIGN KEY (`admin_id`) REFERENCES `utilisateur`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",

        // Security Log table
        'security_log' => "
            CREATE TABLE IF NOT EXISTS `security_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `admin_id` INT(11),
                `ip` VARCHAR(45),
                `event` VARCHAR(100),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`admin_id`) REFERENCES `utilisateur`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",

        // Audit Log table
        'audit_log' => "
            CREATE TABLE IF NOT EXISTS `audit_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `admin_id` INT(11),
                `action` VARCHAR(100),
                `target_user_id` INT(11),
                `details` TEXT,
                `ip_address` VARCHAR(45),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`admin_id`) REFERENCES `utilisateur`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        "
    ];

    foreach ($tables as $tableName => $sql) {
        try {
            $db->exec($sql);
            echo "✅ Table `{$tableName}` ensured.\n";
        } catch (PDOException $e) {
            echo "⚠️ Error creating table `{$tableName}`: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ All cheque module tables are ready!\n";
}

/**
 * Run this function once to set up all tables
 * Usage: php database_setup.php
 */
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/models/config.php';
    ensureChequeTables();
}
