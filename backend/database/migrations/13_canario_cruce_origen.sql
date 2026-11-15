ALTER TABLE canario 
    ADD COLUMN cruce_origen_id INT DEFAULT NULL AFTER madre_id,
    ADD CONSTRAINT fk_canario_cruce_origen FOREIGN KEY (cruce_origen_id) REFERENCES cruce(id) ON DELETE SET NULL;