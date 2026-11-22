<?php

declare(strict_types=1);

namespace TuCriadero\Models;

/**
 * Acceso a la tabla `canario` y a sus tablas dependientes (`ubicacion`, `baja`, `factor_portado`)
 * con consultas preparadas filtradas siempre por `criadero_id` (aislamiento multicuenta, RF003).
 */
final class CanarioModel extends ModeloCriadero
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
        $fila = $this->buscarUnoDelCriadero(
            'SELECT id, criadero_id, sexo, variedad_codigo, padre_id, madre_id, estado
             FROM canario
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
            'sexo' => (string) $fila['sexo'],
            'variedad_codigo' => $fila['variedad_codigo'] !== null ? (string) $fila['variedad_codigo'] : null,
            'padre_id' => $fila['padre_id'] !== null ? (int) $fila['padre_id'] : null,
            'madre_id' => $fila['madre_id'] !== null ? (int) $fila['madre_id'] : null,
            'estado' => (string) $fila['estado'],
        ];
    }

    /**
     * Devuelve el canario si pertenece al criadero o responde 404 (RF003).
     *
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     estado: string
     * }
     */
    public function obtenerPorIdO404(int $id, int $criaderoId): array
    {
        return $this->exigirDelCriadero($this->buscarPorIdEnCriadero($id, $criaderoId));
    }

    public function perteneceAlCriadero(int $id, int $criaderoId): bool
    {
        return $this->buscarPorIdEnCriadero($id, $criaderoId) !== null;
    }

    /**
     * Actualiza la variedad de un canario solo si pertenece al criadero indicado (devuelve filas afectadas).
     */
    public function actualizarVariedadEnCriadero(int $id, int $criaderoId, ?string $variedadCodigo): int
    {
        return $this->ejecutarEnCriadero(
            'UPDATE canario
             SET variedad_codigo = :variedad_codigo
             WHERE criadero_id = :criadero_id AND id = :id',
            $criaderoId,
            [':variedad_codigo' => $variedadCodigo, ':id' => $id]
        );
    }

    /**
     * Lista ubicaciones de un canario asegurando pertenencia al criadero (`ubicacion` → `canario`).
     *
     * @return list<array{id: int, canario_id: int, jaula_id: ?int, fecha_desde: string, fecha_hasta: ?string}>
     */
    public function listarUbicacionesEnCriadero(int $canarioId, int $criaderoId): array
    {
        $filas = $this->buscarTodosDelCriadero(
            'SELECT u.id, u.canario_id, u.jaula_id, u.fecha_desde, u.fecha_hasta
             FROM ubicacion u
             INNER JOIN canario c ON c.id = u.canario_id
             WHERE c.criadero_id = :criadero_id AND u.canario_id = :canario_id
             ORDER BY u.fecha_desde, u.id',
            $criaderoId,
            [':canario_id' => $canarioId]
        );

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'canario_id' => (int) $fila['canario_id'],
            'jaula_id' => $fila['jaula_id'] !== null ? (int) $fila['jaula_id'] : null,
            'fecha_desde' => (string) $fila['fecha_desde'],
            'fecha_hasta' => $fila['fecha_hasta'] !== null ? (string) $fila['fecha_hasta'] : null,
        ], $filas);
    }

    /**
     * Obtiene el registro de baja de un canario asegurando pertenencia al criadero (`baja` → `canario`).
     *
     * @return array{id: int, canario_id: int, motivo: string, fecha: string}|null
     */
    public function obtenerBajaEnCriadero(int $canarioId, int $criaderoId): ?array
    {
        $fila = $this->buscarUnoDelCriadero(
            'SELECT b.id, b.canario_id, b.motivo, b.fecha
             FROM baja b
             INNER JOIN canario c ON c.id = b.canario_id
             WHERE c.criadero_id = :criadero_id AND b.canario_id = :canario_id
             LIMIT 1',
            $criaderoId,
            [':canario_id' => $canarioId]
        );

        if ($fila === null) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'canario_id' => (int) $fila['canario_id'],
            'motivo' => (string) $fila['motivo'],
            'fecha' => (string) $fila['fecha'],
        ];
    }

    /**
     * Lista factores portados de un canario asegurando pertenencia al criadero (`factor_portado` → `canario`).
     *
     * @return list<array{factor_id: int, origen: string}>
     */
    public function listarFactoresPortadosEnCriadero(int $canarioId, int $criaderoId): array
    {
        $filas = $this->buscarTodosDelCriadero(
            'SELECT fp.factor_id, fp.origen
             FROM factor_portado fp
             INNER JOIN canario c ON c.id = fp.canario_id
             WHERE c.criadero_id = :criadero_id AND fp.canario_id = :canario_id
             ORDER BY fp.factor_id',
            $criaderoId,
            [':canario_id' => $canarioId]
        );

        return array_map(static fn (array $fila): array => [
            'factor_id' => (int) $fila['factor_id'],
            'origen' => (string) $fila['origen'],
        ], $filas);
    }
}
