-- RF032: el catálogo se audita con su código alfanumérico (la variedad es D.049, no un id numérico).
-- SEG-003: con VARCHAR(50) se guardan tanto los ids numéricos de siempre como los códigos.
ALTER TABLE auditoria MODIFY entidad_id VARCHAR(50) NOT NULL;
