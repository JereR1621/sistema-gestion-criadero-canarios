CREATE TABLE factor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo_herencia ENUM('ligado_sexo', 'recesivo', 'dominante') NOT NULL,
    CONSTRAINT uq_factor_nombre UNIQUE (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
