-- cruce_origen_id se agrega en 013, cuando ya existe la tabla cruce (referencia circular).
-- RESTRICT en la variedad: no se elimina una variedad asignada a algún canario (RF032).
CREATE TABLE canario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    criadero_id INT NOT NULL,
    anillo_socio VARCHAR(50) NOT NULL,
    anillo_anio SMALLINT UNSIGNED NOT NULL,
    anillo_correlativo INT UNSIGNED NOT NULL,
    anillo_club VARCHAR(50) NOT NULL,
    fecha_anillado DATE NULL,
    fecha_nacimiento DATE NULL,
    sexo ENUM('macho', 'hembra', 'sin_determinar') NOT NULL,
    variedad_codigo CHAR(5) NULL,
    padre_id INT NULL,
    madre_id INT NULL,
    procedencia VARCHAR(150) NULL,
    estado ENUM('activo', 'reservado', 'baja') NOT NULL DEFAULT 'activo',
    CONSTRAINT uq_canario_anillo UNIQUE (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club),
    INDEX ix_canario_correlativo (criadero_id, anillo_correlativo),
    INDEX ix_canario_variedad (criadero_id, variedad_codigo),
    CONSTRAINT fk_canario_criadero FOREIGN KEY (criadero_id) REFERENCES criadero (id) ON DELETE CASCADE,
    CONSTRAINT fk_canario_variedad FOREIGN KEY (variedad_codigo) REFERENCES variedad (codigo) ON DELETE RESTRICT,
    CONSTRAINT fk_canario_padre FOREIGN KEY (padre_id) REFERENCES canario (id) ON DELETE SET NULL,
    CONSTRAINT fk_canario_madre FOREIGN KEY (madre_id) REFERENCES canario (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
