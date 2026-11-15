CREATE TABLE IF NOT EXISTS factor_genetico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    tipo_herencia ENUM('ligado_al_sexo', 'recesivo', 'dominante') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;