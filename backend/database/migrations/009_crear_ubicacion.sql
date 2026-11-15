-- jaula_id NULL = jaula grande; fecha_hasta NULL = ubicación actual.
-- RESTRICT en la jaula: con SET NULL, borrar una jaula pasaría su historial a la jaula grande.
CREATE TABLE ubicacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    canario_id INT NOT NULL,
    jaula_id INT NULL,
    fecha_desde DATE NOT NULL,
    fecha_hasta DATE NULL,
    CONSTRAINT chk_ubicacion_fechas CHECK (fecha_hasta IS NULL OR fecha_hasta >= fecha_desde),
    CONSTRAINT fk_ubicacion_canario FOREIGN KEY (canario_id) REFERENCES canario (id) ON DELETE CASCADE,
    CONSTRAINT fk_ubicacion_jaula FOREIGN KEY (jaula_id) REFERENCES jaula (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
