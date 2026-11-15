-- backend/database/seeds/90_datos_prueba.sql

INSERT INTO usuario (nombre, correo_electronico, contrasena_hash, rol, estado_cuenta, numero_socio, club) 
VALUES ('Criador Juan', 'juan@criadero.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'criador', 'activa', 'S-001', 'Club Canaricultores Sur')
ON DUPLICATE KEY UPDATE nombre=nombre;

SET @usuario_id = LAST_INSERT_ID();

INSERT INTO criadero (usuario_id, nombre) 
VALUES (@usuario_id, 'Criadero El Canario Fiel')
ON DUPLICATE KEY UPDATE nombre=nombre;

SET @criadero_id = LAST_INSERT_ID();

INSERT INTO jaula (criadero_id, numero) 
VALUES (@criadero_id, '101'), (@criadero_id, '102')
ON DUPLICATE KEY UPDATE numero=numero;

-- Insertar canarios de prueba
INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, sexo, estado)
VALUES 
(@criadero_id, 'S-001', 2026, 150, 'Club Sur', 'macho', 'activo'),
(@criadero_id, 'S-001', 2026, 151, 'Club Sur', 'hembra', 'activo')
ON DUPLICATE KEY UPDATE anillo_correlativo=anillo_correlativo;