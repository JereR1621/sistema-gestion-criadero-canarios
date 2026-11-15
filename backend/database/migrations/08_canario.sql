CREATE TABLE IF NOT EXISTS canario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    criadero_id INT NOT NULL,
    anillo_socio VARCHAR(50) NOT NULL,
    anillo_anio INT NOT NULL,
    anillo_correlativo INT NOT NULL,
    anillo_club VARCHAR(50) NOT NULL,
    fecha_anillado DATE DEFAULT NULL,
    fecha_nacimiento DATE DEFAULT NULL,
    sexo ENUM('macho', 'hembra') DEFAULT NULL,
    variedad_codigo VARCHAR(20) DEFAULT NULL,
    padre_id INT DEFAULT NULL,
    madre_id INT DEFAULT NULL,
    procedencia VARCHAR(150) DEFAULT NULL,
    estado ENUM('activo', 'reservado', 'baja') NOT NULL DEFAULT 'activo',
    CONSTRAINT unq_anillo_criadero UNIQUE (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club),
    FOREIGN KEY (criadero_id) REFERENCES criadero(id) ON DELETE CASCADE,
    FOREIGN KEY (variedad_codigo) REFERENCES variedad_color(codigo) ON DELETE SET NULL,
    FOREIGN KEY (padre_id) REFERENCES canario(id) ON DELETE SET NULL,
    FOREIGN KEY (madre_id) REFERENCES canario(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enlazar FK cruzadas de cruce con canario
ALTER TABLE cruce 
    ADD CONSTRAINT fk_cruce_macho_canario FOREIGN KEY (macho_id) REFERENCES canario(id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_cruce_hembra_canario FOREIGN KEY (hembra_id) REFERENCES canario(id) ON DELETE RESTRICT;