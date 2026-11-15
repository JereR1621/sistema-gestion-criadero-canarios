-- Catálogo de variedades de color (D.001 a D.880). sexo solo si el nombre lo indica.
CREATE TABLE variedad (
    codigo CHAR(5) PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    sexo ENUM('macho', 'hembra') NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
