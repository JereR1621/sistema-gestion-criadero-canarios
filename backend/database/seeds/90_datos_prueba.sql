-- Datos INVENTADOS para probar en tu computador. Nunca se cargan en producción.
-- Ids fijos desde 1001: volver a correr --seeds no duplica ni falla.
-- Cuentas: ana@ejemplo.local y bruno@ejemplo.local, contraseña Prueba2026.

INSERT INTO usuario (id, nombre, correo, contrasena_hash, rol, fecha_consentimiento, numero_socio, club) VALUES
    (1001, 'Ana Prueba', 'ana@ejemplo.local', '$2y$10$hn3nE92toMEpKWeohtpwUu42//uL36TiaCmeSf1ShsvZrLz4qVIVm', 'criador', '2026-11-01 10:00:00', 'S-0101', 'Club de Prueba Norte'),
    (1002, 'Bruno Prueba', 'bruno@ejemplo.local', '$2y$10$hn3nE92toMEpKWeohtpwUu42//uL36TiaCmeSf1ShsvZrLz4qVIVm', 'criador', '2026-11-01 11:00:00', 'S-0202', 'Club de Prueba Sur')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO criadero (id, usuario_id) VALUES (1001, 1001), (1002, 1002)
ON DUPLICATE KEY UPDATE id = id;

-- La jaula 1 existe en los dos criaderos: el número es único solo dentro de cada criadero.
INSERT INTO jaula (id, criadero_id, numero) VALUES
    (1001, 1001, 1), (1002, 1001, 2), (1003, 1001, 3),
    (1004, 1002, 1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO canario (id, criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, fecha_anillado, sexo, estado) VALUES
    (1001, 1001, 'S-0101', 2025, 1, 'Club de Prueba Norte', '2025-10-20', 'macho', 'activo'),
    (1002, 1001, 'S-0101', 2025, 2, 'Club de Prueba Norte', '2025-10-20', 'hembra', 'activo'),
    (1004, 1001, 'S-0101', 2025, 3, 'Club de Prueba Norte', '2025-10-25', 'hembra', 'reservado'),
    (1005, 1002, 'S-0202', 2025, 1, 'Club de Prueba Sur', '2025-11-02', 'macho', 'activo')
ON DUPLICATE KEY UPDATE id = id;

-- Cruce activo (sin fecha_fin) de la temporada 2026 en la jaula 1 de Ana.
INSERT INTO cruce (id, criadero_id, macho_id, hembra_id, jaula_id, temporada, fecha_inicio) VALUES
    (1001, 1001, 1001, 1002, 1001, 2026, '2026-09-01')
ON DUPLICATE KEY UPDATE id = id;

-- Pichón del cruce: sus padres son el macho y la hembra del cruce.
INSERT INTO canario (id, criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, fecha_anillado, fecha_nacimiento, sexo, padre_id, madre_id, cruce_origen_id) VALUES
    (1003, 1001, 'S-0101', 2026, 1, 'Club de Prueba Norte', '2026-10-12', '2026-10-01', 'sin_determinar', 1001, 1002, 1001)
ON DUPLICATE KEY UPDATE id = id;

-- Ubicaciones actuales (fecha_hasta NULL); jaula_id NULL = jaula grande.
INSERT INTO ubicacion (id, canario_id, jaula_id, fecha_desde) VALUES
    (1001, 1001, 1001, '2026-09-01'),
    (1002, 1002, 1001, '2026-09-01'),
    (1003, 1003, 1001, '2026-10-12'),
    (1004, 1004, NULL, '2025-10-25'),
    (1005, 1005, 1004, '2025-11-02')
ON DUPLICATE KEY UPDATE id = id;
