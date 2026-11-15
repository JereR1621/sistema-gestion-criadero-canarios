-- DAT-001, backend.md §3. fecha_consentimiento: POL-001. fecha_cierre: RF005.
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol ENUM('criador', 'admin') NOT NULL DEFAULT 'criador',
    estado ENUM('activa', 'desactivada', 'cerrada') NOT NULL DEFAULT 'activa',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_consentimiento DATETIME NULL,
    fecha_cierre DATETIME NULL,
    numero_socio VARCHAR(50) NULL,
    club VARCHAR(100) NULL,
    CONSTRAINT uq_usuario_correo UNIQUE (correo),
    CONSTRAINT chk_usuario_cierre CHECK (estado = 'cerrada' OR fecha_cierre IS NULL),
    CONSTRAINT chk_usuario_socio_club CHECK (rol = 'criador' OR (numero_socio IS NULL AND club IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
