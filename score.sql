CREATE TABLE score (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    trust_score INT DEFAULT 0,
    score_details JSON,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_score_user
        FOREIGN KEY (user_id) REFERENCES utilisateur(id)
        ON DELETE CASCADE
);