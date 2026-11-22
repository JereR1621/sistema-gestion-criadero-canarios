<?php

declare(strict_types=1);

namespace TuCriadero\Models;

/**
 * Acceso a la tabla `cruce` filtrado siempre por `criadero_id` (SRS-RF003).
 */
final class CruceModel extends ModeloCriadero
{
    /**
     * Busca un cruce por id dentro del criadero indicado.
     * Devuelve null tanto si no existe como si pertenece a otro criadero (RF003).
     *
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     macho_id: int,
     *     hembra_id: int,
     *     jaula_id: int,
     *     temporada: int,
     *     fecha_inicio: string,
     *     fecha_fin: ?string
     * }|null
     */
    public function buscarPorIdEnCriadero(int $id, int $criaderoId): ?array
    {
        $fila = $this->buscarUnoDelCriadero(
            'SELECT id, criadero_id, macho_id, hembra_id, jaula_id, temporada, fecha_inicio, fecha_fin
             FROM cruce
             WHERE criadero_id = :criadero_id AND id = :id
             LIMIT 1',
            $criaderoId,
            [':id' => $id]
        );

        if ($fila === null) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'criadero_id' => (int) $fila['criadero_id'],
            'macho_id' => (int) $fila['macho_id'],
            'hembra_id' => (int) $fila['hembra_id'],
            'jaula_id' => (int) $fila['jaula_id'],
            'temporada' => (int) $fila['temporada'],
            'fecha_inicio' => (string) $fila['fecha_inicio'],
            'fecha_fin' => $fila['fecha_fin'] !== null ? (string) $fila['fecha_fin'] : null,
        ];
    }

    /**
     * Devuelve el cruce si pertenece al criadero o responde 404 (RF003).
     *
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     macho_id: int,
     *     hembra_id: int,
     *     jaula_id: int,
     *     temporada: int,
     *     fecha_inicio: string,
     *     fecha_fin: ?string
     * }
     */
    public function obtenerPorIdO404(int $id, int $criaderoId): array
    {
        return $this->exigirDelCriadero($this->buscarPorIdEnCriadero($id, $criaderoId));
    }

    /**
     * Cierra un cruce activo solo si pertenece al criadero indicado (devuelve filas afectadas).
     */
    public function cerrarEnCriadero(int $id, int $criaderoId, string $fechaFin): int
    {
        return $this->ejecutarEnCriadero(
            'UPDATE cruce
             SET fecha_fin = :fecha_fin
             WHERE criadero_id = :criadero_id AND id = :id AND fecha_fin IS NULL',
            $criaderoId,
            [':fecha_fin' => $fechaFin, ':id' => $id]
        );
    }
}
