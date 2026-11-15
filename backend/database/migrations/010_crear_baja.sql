CREATE TABLE baja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    canario_id INT NOT NULL,
    motivo ENUM('venta', 'muerte', 'otra') NOT NULL,
    fecha DATE NOT NULL,
    CONSTRAINT uq_baja_canario UNIQUE (canario_id),
    CONSTRAINT fk_baja_canario FOREIGN KEY (canario_id) REFERENCES canario (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
