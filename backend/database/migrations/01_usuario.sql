CREATE TABLE IF NOT EXISTS usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo_electronico VARCHAR(150) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol ENUM('criador', 'administrador') NOT NULL DEFAULT 'criador',
    estado_cuenta ENUM('activa', 'desactiva') NOT NULL DEFAULT 'activa',
    fecha_cierre DATETIME DEFAULT NULL,
    numero_socio VARCHAR(50) DEFAULT NULL,
    club VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;