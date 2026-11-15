CREATE TABLE IF NOT EXISTS ubicacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    canario_id INT NOT NULL,
    jaula_id INT DEFAULT NULL,
    fecha_desde DATE NOT NULL,
    fecha_hasta DATE DEFAULT NULL,
    FOREIGN KEY (canario_id) REFERENCES canario(id) ON DELETE CASCADE,
    FOREIGN KEY (jaula_id) REFERENCES jaula(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;