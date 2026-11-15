CREATE TABLE IF NOT EXISTS factor_portado (
    canario_id INT NOT NULL,
    factor_id INT NOT NULL,
    origen ENUM('deducido', 'criador') NOT NULL,
    PRIMARY KEY (canario_id, factor_id),
    FOREIGN KEY (canario_id) REFERENCES canario(id) ON DELETE CASCADE,
    FOREIGN KEY (factor_id) REFERENCES factor_genetico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;