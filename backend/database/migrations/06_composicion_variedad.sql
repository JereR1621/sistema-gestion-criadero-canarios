CREATE TABLE IF NOT EXISTS composicion_variedad (
    variedad_codigo VARCHAR(20) NOT NULL,
    factor_id INT NOT NULL,
    PRIMARY KEY (variedad_codigo, factor_id),
    FOREIGN KEY (variedad_codigo) REFERENCES variedad_color(codigo) ON DELETE CASCADE,
    FOREIGN KEY (factor_id) REFERENCES factor_genetico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;