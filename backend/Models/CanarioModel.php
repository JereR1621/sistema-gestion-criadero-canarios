<?php

declare(strict_types=1);

namespace TuCriadero\Models;

use TuCriadero\Core\Conexion;

/**
 * Acceso a la tabla `canario` con consultas preparadas.
 * Toda consulta filtra siempre por `criadero_id` (aislamiento multicuenta, RF003).
 */
final class CanarioModel
{
    /**
     * Busca un canario por id dentro del criadero indicado.
     * Devuelve null tanto si no existe como si pertenece a otro criadero (RF003).
     *
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     estado: string
     * }|null
     */
    public function buscarPorIdEnCriadero(int $id, int $criaderoId): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, criadero_id, sexo, variedad_codigo, padre_id, madre_id, estado
             FROM canario
             WHERE id = ? AND criadero_id = ?
             LIMIT 1'
        );
        $consulta->execute([$id, $criaderoId]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'criadero_id' => (int) $fila['criadero_id'],
            'sexo' => (string) $fila['sexo'],
            'variedad_codigo' => $fila['variedad_codigo'] !== null ? (string) $fila['variedad_codigo'] : null,
            'padre_id' => $fila['padre_id'] !== null ? (int) $fila['padre_id'] : null,
            'madre_id' => $fila['madre_id'] !== null ? (int) $fila['madre_id'] : null,
            'estado' => (string) $fila['estado'],
        ];
    }
}
