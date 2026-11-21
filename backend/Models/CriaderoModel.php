<?php

declare(strict_types=1);

namespace TuCriadero\Models;

use TuCriadero\Core\Conexion;

final class CriaderoModel
{
    /**
     * Crea un criadero vacío asociado a un usuario.
     *
     * Retorna el ID del criadero.
     */
    public function crear(int $usuarioId): int
    {
        $sql = '
            INSERT INTO criadero (usuario_id)
            VALUES (:usuario_id)
        ';

        $consulta = Conexion::obtener()->prepare($sql);

        $consulta->execute([
            'usuario_id' => $usuarioId,
        ]);

        return (int) Conexion::obtener()->lastInsertId();
    }
}