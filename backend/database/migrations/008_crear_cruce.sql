-- fecha_fin NULL = cruce activo (RF022).
CREATE TABLE cruce (
    id INT AUTO_INCREMENT PRIMARY KEY,
    criadero_id INT NOT NULL,
    macho_id INT NOT NULL,
    hembra_id INT NOT NULL,
    jaula_id INT NOT NULL,
    temporada SMALLINT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    CONSTRAINT chk_cruce_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
    CONSTRAINT fk_cruce_criadero FOREIGN KEY (criadero_id) REFERENCES criadero (id) ON DELETE CASCADE,
    CONSTRAINT fk_cruce_macho FOREIGN KEY (macho_id) REFERENCES canario (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cruce_hembra FOREIGN KEY (hembra_id) REFERENCES canario (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cruce_jaula FOREIGN KEY (jaula_id) REFERENCES jaula (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
