-- Seed de factores genéticos (Issue #148)
-- 22 factores con IDs fijos según versión vigente en GitHub

INSERT IGNORE INTO factor (id, nombre, tipo_herencia) VALUES
  (1, 'ROJO', 'recesivo'),
  (2, 'BLANCO RECESIVO', 'recesivo'),
  (3, 'BLANCO DOMINANTE', 'dominante'),
  (4, 'INTENSO', 'dominante'),
  (5, 'MOSAICO', 'recesivo'),
  (6, 'MARFIL', 'ligado_sexo'),
  (7, 'BRUNO', 'ligado_sexo'),
  (8, 'ÁGATA', 'ligado_sexo'),
  (9, 'ÓPALO', 'recesivo'),
  (10, 'PASTEL', 'ligado_sexo'),
  (11, 'SATINÉ', 'ligado_sexo'),
  (12, 'INO', 'recesivo'),
  (13, 'TOPACIO', 'recesivo'),
  (14, 'ÓNIX', 'recesivo'),
  (15, 'EUMO', 'recesivo'),
  (16, 'COBALTO', 'recesivo'),
  (17, 'MOGNO', 'recesivo'),
  (18, 'JASPE', 'dominante'),
  (19, 'JASPE DOBLE DOSIS', 'dominante'),
  (20, 'PICO AMARILLO', 'recesivo'),
  (21, 'URUCUM', 'recesivo'),
  (22, 'PERLA', 'recesivo');
