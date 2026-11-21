-- Jaspe codominante (backend.md §5): SD = una copia, DD = dos. La dosis va en la composición.
ALTER TABLE factor
    MODIFY tipo_herencia ENUM('ligado_sexo', 'recesivo', 'dominante', 'codominante') NOT NULL;

ALTER TABLE variedad_factor
    ADD COLUMN dosis TINYINT NOT NULL DEFAULT 1 AFTER factor_id,
    ADD CONSTRAINT ck_variedad_factor_dosis CHECK (dosis IN (1, 2));

-- Bases que ya cargaron el catálogo con «JASPE DOBLE DOSIS» como factor aparte.
UPDATE factor SET tipo_herencia = 'codominante' WHERE nombre = 'JASPE';
UPDATE variedad_factor vf
    JOIN factor dd ON dd.id = vf.factor_id AND dd.nombre = 'JASPE DOBLE DOSIS'
    JOIN factor j ON j.nombre = 'JASPE'
    SET vf.factor_id = j.id, vf.dosis = 2;
DELETE FROM factor WHERE nombre = 'JASPE DOBLE DOSIS';
