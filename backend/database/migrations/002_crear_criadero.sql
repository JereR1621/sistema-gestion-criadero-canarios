-- Un criadero por criador; el administrador no tiene.
CREATE TABLE criadero (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    CONSTRAINT uq_criadero_usuario UNIQUE (usuario_id),
    CONSTRAINT fk_criadero_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
