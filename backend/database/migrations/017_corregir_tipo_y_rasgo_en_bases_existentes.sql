-- Bases creadas antes de la 014: al agregar `tipo` (NOT NULL) y `rasgo`, las variedades que ya existían quedaron como
-- 'melanico' y sin rasgo, y el seed (INSERT IGNORE) no las corrige. Aquí se les pone el tipo y el rasgo del nomenclador
-- (seeds/01_variedades.sql). En una base nueva la tabla está vacía al migrar y estas líneas no cambian nada.
UPDATE variedad SET tipo = 'lipocromico' WHERE codigo IN (
    'D.001', 'D.002', 'D.003', 'D.004', 'D.005', 'D.006', 'D.007', 'D.008', 'D.009', 'D.010',
    'D.011', 'D.012', 'D.013', 'D.014', 'D.015', 'D.016', 'D.017', 'D.018', 'D.019', 'D.020',
    'D.021', 'D.022', 'D.023', 'D.024', 'D.025', 'D.026', 'D.027', 'D.028', 'D.029', 'D.030',
    'D.031', 'D.032', 'D.033', 'D.034', 'D.035', 'D.036', 'D.037', 'D.038', 'D.039', 'D.040',
    'D.041', 'D.042', 'D.043', 'D.044', 'D.045', 'D.046', 'D.047', 'D.048', 'D.049', 'D.050',
    'D.051', 'D.052', 'D.053', 'D.054', 'D.055', 'D.056', 'D.057', 'D.058', 'D.059', 'D.060',
    'D.061', 'D.062', 'D.063', 'D.064', 'D.065', 'D.066', 'D.067', 'D.068', 'D.069', 'D.070',
    'D.071', 'D.072', 'D.073', 'D.074', 'D.075', 'D.076', 'D.077', 'D.078', 'D.079', 'D.080',
    'D.081', 'D.082', 'D.083', 'D.084', 'D.085', 'D.086', 'D.087', 'D.088', 'D.089', 'D.090',
    'D.091', 'D.092', 'D.093', 'D.094', 'D.095', 'D.096'
);

UPDATE variedad SET rasgo = 'ala_blanca' WHERE codigo IN (
    'D.021', 'D.022', 'D.023', 'D.024', 'D.025', 'D.026', 'D.027', 'D.028', 'D.029', 'D.030',
    'D.031', 'D.032', 'D.033', 'D.034', 'D.035', 'D.036', 'D.065', 'D.066', 'D.067', 'D.068',
    'D.069', 'D.070', 'D.071', 'D.072', 'D.073', 'D.074', 'D.075', 'D.076', 'D.077', 'D.078',
    'D.079', 'D.080'
);

UPDATE variedad SET rasgo = 'alas_grises' WHERE codigo IN (
    'D.377', 'D.378', 'D.379', 'D.380', 'D.381', 'D.382', 'D.383', 'D.384', 'D.385', 'D.386',
    'D.387', 'D.388', 'D.389', 'D.390', 'D.391', 'D.392', 'D.393', 'D.394'
);
