-- origen: deducido por el sistema (RF034) o indicado por el criador (RF009).
CREATE TABLE factor_portado (
    canario_id INT NOT NULL,
    factor_id INT NOT NULL,
    origen ENUM('deducido', 'criador') NOT NULL,
    PRIMARY KEY (canario_id, factor_id),
    CONSTRAINT fk_factor_portado_canario FOREIGN KEY (canario_id) REFERENCES canario (id) ON DELETE CASCADE,
    CONSTRAINT fk_factor_portado_factor FOREIGN KEY (factor_id) REFERENCES factor (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
