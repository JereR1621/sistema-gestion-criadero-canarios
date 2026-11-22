-- Tipo y rasgo de la variedad (backend.md §3 y §6): no son factores con herencia,
-- pero distinguen variedades que tienen la misma composición (DAT-004).
ALTER TABLE variedad
    ADD COLUMN tipo ENUM('melanico', 'lipocromico') NOT NULL AFTER sexo,
    ADD COLUMN rasgo ENUM('ala_blanca', 'alas_grises') NULL AFTER tipo;
