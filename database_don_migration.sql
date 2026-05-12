-- ============================================================
-- database_don_migration.sql
-- Donation / Cagnotte / Achievement module — schema migration
-- Safe to run ONCE. Uses IF NOT EXISTS / conditional ALTERs.
-- ============================================================

-- 1. Ensure association column exists on utilisateur
ALTER TABLE utilisateur
    ADD COLUMN IF NOT EXISTS association TINYINT(1) NOT NULL DEFAULT 0;

-- 2. Create cagnotte table if not exists
CREATE TABLE IF NOT EXISTS cagnotte (
    id_cagnotte      INT           NOT NULL AUTO_INCREMENT,
    id_createur      INT           NOT NULL,
    titre            VARCHAR(255)  NOT NULL,
    description      TEXT          NOT NULL,
    categorie        ENUM('sante','education','solidarite','autre') NOT NULL DEFAULT 'autre',
    objectif_montant DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    montant_collecte DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    statut           ENUM('en_attente','acceptee','refusee','suspendue','cloturee') NOT NULL DEFAULT 'en_attente',
    date_debut       DATE          NOT NULL,
    date_fin         DATE          NOT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cagnotte),
    INDEX idx_cagnotte_createur (id_createur),
    INDEX idx_cagnotte_statut   (statut),
    CONSTRAINT fk_cagnotte_createur FOREIGN KEY (id_createur) REFERENCES utilisateur(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create don table if not exists (includes banking columns from the start)
CREATE TABLE IF NOT EXISTS don (
    id_don           INT           NOT NULL AUTO_INCREMENT,
    id_cagnotte      INT           NOT NULL,
    id_donateur      INT           NULL,
    montant          DECIMAL(15,3) NOT NULL,
    est_anonyme      TINYINT(1)    NOT NULL DEFAULT 0,
    message          TEXT          NULL,
    moyen_paiement   ENUM('carte','virement') NOT NULL DEFAULT 'carte',
    statut           ENUM('en_attente','confirme','refuse') NOT NULL DEFAULT 'en_attente',
    date_don         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    email_sent       TINYINT(1)    NOT NULL DEFAULT 0,
    email_sent_at    DATETIME      NULL DEFAULT NULL,
    -- Banking linkage (virement only)
    id_compte        INT           NULL DEFAULT NULL,
    devise_don       VARCHAR(3)    NOT NULL DEFAULT 'TND',
    montant_converti DECIMAL(15,3) NULL DEFAULT NULL,
    PRIMARY KEY (id_don),
    INDEX idx_don_cagnotte   (id_cagnotte),
    INDEX idx_don_donateur   (id_donateur),
    INDEX idx_don_statut     (statut),
    INDEX idx_don_compte     (id_compte),
    CONSTRAINT fk_don_cagnotte FOREIGN KEY (id_cagnotte) REFERENCES cagnotte(id_cagnotte) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_don_donateur FOREIGN KEY (id_donateur) REFERENCES utilisateur(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Add banking columns to don if the table already existed without them
ALTER TABLE don
    ADD COLUMN IF NOT EXISTS email_sent       TINYINT(1)    NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS email_sent_at    DATETIME      NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS id_compte        INT           NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS devise_don       VARCHAR(3)    NOT NULL DEFAULT 'TND',
    ADD COLUMN IF NOT EXISTS montant_converti DECIMAL(15,3) NULL DEFAULT NULL;

-- 5. Add FK from don to compte_bancaire (only if not yet present)
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'don'
      AND CONSTRAINT_NAME = 'fk_don_compte'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE don ADD CONSTRAINT fk_don_compte FOREIGN KEY (id_compte) REFERENCES compte_bancaire(id_compte) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Add id_don to virement table (reverse link) if not already present
ALTER TABLE virement
    ADD COLUMN IF NOT EXISTS id_don INT NULL DEFAULT NULL;

-- 7. Add FK from virement to don (only if not yet present)
SET @fk2_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'virement'
      AND CONSTRAINT_NAME = 'fk_virement_don'
);
SET @sql2 = IF(@fk2_exists = 0,
    'ALTER TABLE virement ADD CONSTRAINT fk_virement_don FOREIGN KEY (id_don) REFERENCES don(id_don) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- 8. Create achievements table
CREATE TABLE IF NOT EXISTS achievements (
    id              INT           NOT NULL AUTO_INCREMENT,
    title           VARCHAR(120)  NOT NULL,
    description     VARCHAR(255)  NOT NULL,
    icon            VARCHAR(120)  NOT NULL DEFAULT 'fa-solid fa-star',
    role_type       ENUM('donor','association') NOT NULL,
    condition_type  ENUM('amount_total','donation_count','supported_campaign_count',
                         'campaign_count','raised_amount_total','funded_campaign_count') NOT NULL,
    condition_value DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    points          INT           NOT NULL DEFAULT 0,
    is_enabled      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_achievements_role (role_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Create user_achievements junction table
CREATE TABLE IF NOT EXISTS user_achievements (
    id             INT      NOT NULL AUTO_INCREMENT,
    user_id        INT      NOT NULL,
    achievement_id INT      NOT NULL,
    unlocked_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_achievement (user_id, achievement_id),
    INDEX idx_ua_user        (user_id),
    INDEX idx_ua_achievement (achievement_id),
    CONSTRAINT fk_ua_user        FOREIGN KEY (user_id)        REFERENCES utilisateur(id)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ua_achievement FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Seed starter achievements (safe, only if table was just created / empty)
INSERT IGNORE INTO achievements (title, description, icon, role_type, condition_type, condition_value, points, is_enabled) VALUES
-- Donor achievements
('Premier Don',       'Effectuez votre premier don',                          'fa-solid fa-heart',          'donor',       'donation_count',           1,    50,  1),
('Donateur Régulier', 'Effectuez 5 dons confirmés',                           'fa-solid fa-repeat',         'donor',       'donation_count',           5,   100,  1),
('Grand Bienfaiteur', 'Effectuez 20 dons confirmés',                          'fa-solid fa-crown',          'donor',       'donation_count',          20,   300,  1),
('Généreux',          'Donner un total de 100 TND',                           'fa-solid fa-coins',          'donor',       'amount_total',           100,   100,  1),
('Philanthrope',      'Donner un total de 1000 TND',                          'fa-solid fa-gem',            'donor',       'amount_total',          1000,   500,  1),
('Polyvalent',        'Soutenir 3 campagnes différentes',                     'fa-solid fa-layer-group',    'donor',       'supported_campaign_count',  3,   150,  1),
-- Association achievements
('Première Cagnotte', 'Créez votre première cagnotte',                        'fa-solid fa-flag',           'association', 'campaign_count',           1,    50,  1),
('Fonds Collectés',   'Collectez 500 TND sur l\'ensemble de vos cagnottes',   'fa-solid fa-piggy-bank',     'association', 'raised_amount_total',     500,   200,  1),
('Objectif Atteint',  'Atteignez l\'objectif d\'une cagnotte',                'fa-solid fa-trophy',         'association', 'funded_campaign_count',     1,   250,  1),
('Lancer Multiplex',  'Créez 5 cagnottes',                                    'fa-solid fa-rocket',         'association', 'campaign_count',           5,   200,  1);
