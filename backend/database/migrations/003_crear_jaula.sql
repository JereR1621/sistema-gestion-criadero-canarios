CREATE TABLE jaula (
    id INT AUTO_INCREMENT PRIMARY KEY,
    criadero_id INT NOT NULL,
    numero INT UNSIGNED NOT NULL,
    CONSTRAINT uq_jaula_criadero_numero UNIQUE (criadero_id, numero),
    CONSTRAINT fk_jaula_criadero FOREIGN KEY (criadero_id) REFERENCES criadero (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
