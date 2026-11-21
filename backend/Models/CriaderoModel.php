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
    /**
    * Devuelve el ID del criadero perteneciente al usuario, o null si no tiene.
     */
    public function obtenerIdPorUsuario(int $usuarioId): ?int
    {
        $stmt = Conexion::obtener()->prepare(
            'SELECT id FROM criadero WHERE usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute([':usuario_id' => $usuarioId]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }
}