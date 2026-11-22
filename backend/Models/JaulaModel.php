<?php

declare(strict_types=1);

namespace TuCriadero\Models;

/**
 * Acceso a la tabla `jaula` filtrado siempre por `criadero_id` (SRS-RF003).
 */
final class JaulaModel extends ModeloCriadero
{
    /**
     * Busca una jaula por id dentro del criadero indicado.
     * Devuelve null tanto si no existe como si pertenece a otro criadero (RF003).
     *
     * @return array{id: int, criadero_id: int, numero: int}|null
     */
    public function buscarPorIdEnCriadero(int $id, int $criaderoId): ?array
    {
        $fila = $this->buscarUnoDelCriadero(
            'SELECT id, criadero_id, numero
             FROM jaula
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
            'numero' => (int) $fila['numero'],
        ];
    }

    /**
     * Devuelve la jaula si pertenece al criadero o responde 404 (RF003).
     *
     * @return array{id: int, criadero_id: int, numero: int}
     */
    public function obtenerPorIdO404(int $id, int $criaderoId): array
    {
        return $this->exigirDelCriadero($this->buscarPorIdEnCriadero($id, $criaderoId));
    }

    public function perteneceAlCriadero(int $id, int $criaderoId): bool
    {
        return $this->buscarPorIdEnCriadero($id, $criaderoId) !== null;
    }
}
