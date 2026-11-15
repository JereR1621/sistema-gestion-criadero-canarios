-- Composición de la variedad: los factores que manifiesta.
CREATE TABLE variedad_factor (
    variedad_codigo CHAR(5) NOT NULL,
    factor_id INT NOT NULL,
    PRIMARY KEY (variedad_codigo, factor_id),
    CONSTRAINT fk_variedad_factor_variedad FOREIGN KEY (variedad_codigo) REFERENCES variedad (codigo) ON DELETE CASCADE,
    CONSTRAINT fk_variedad_factor_factor FOREIGN KEY (factor_id) REFERENCES factor (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
