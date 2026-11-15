CREATE TABLE IF NOT EXISTS cruce (
    id INT AUTO_INCREMENT PRIMARY KEY,
    criadero_id INT NOT NULL,
    macho_id INT NOT NULL,
    hembra_id INT NOT NULL,
    jaula_id INT NOT NULL,
    temporada INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE DEFAULT NULL,
    CONSTRAINT chk_fechas_cruce CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
    FOREIGN KEY (criadero_id) REFERENCES criadero(id) ON DELETE CASCADE,
    FOREIGN KEY (jaula_id) REFERENCES jaula(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;