-- Cierra la referencia circular canario.cruce_origen_id -> cruce (backend.md §3).
ALTER TABLE canario
    ADD COLUMN cruce_origen_id INT NULL AFTER madre_id,
    ADD CONSTRAINT fk_canario_cruce_origen FOREIGN KEY (cruce_origen_id) REFERENCES cruce (id) ON DELETE RESTRICT;
