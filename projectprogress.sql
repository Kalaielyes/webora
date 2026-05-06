CREATE TABLE projet_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projet_id INT NOT NULL,
    pourcentage INT NOT NULL,
    description TEXT,
    date_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_projet
        FOREIGN KEY (projet_id) REFERENCES projet(id_projet)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;