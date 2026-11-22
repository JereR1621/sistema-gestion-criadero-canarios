<?php

declare(strict_types=1);

namespace TuCriadero\Models;

use InvalidArgumentException;

/**
 * Acceso a la tabla `jaula` filtrado siempre por `criadero_id` (SRS-RF003, SRS-RF014).
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

    public function existeNumeroEnCriadero(int $numero, int $criaderoId): bool
    {
        return $this->buscarUnoDelCriadero(
            'SELECT id
             FROM jaula
             WHERE criadero_id = :criadero_id AND numero = :numero
             LIMIT 1',
            $criaderoId,
            [':numero' => $numero]
        ) !== null;
    }

    public function crear(int $criaderoId, int $numero): int
    {
        if ($criaderoId <= 0) {
            throw new InvalidArgumentException('El identificador del criadero debe ser un entero positivo.');
        }

        $consulta = $this->pdo()->prepare(
            'INSERT INTO jaula (criadero_id, numero) VALUES (:criadero_id, :numero)'
        );
        $consulta->execute([
            ':criadero_id' => $criaderoId,
            ':numero' => $numero,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Lista las jaulas del criadero ordenadas por número junto con la cantidad de ocupantes actuales.
     *
     * @return list<array{id: int, numero: int, ocupantes: int}>
     */
    public function listarConOcupantesEnCriadero(int $criaderoId): array
    {
        $filas = $this->buscarTodosDelCriadero(
            'SELECT j.id, j.numero, COUNT(c.id) AS ocupantes
             FROM jaula j
             LEFT JOIN ubicacion u ON u.jaula_id = j.id AND u.fecha_hasta IS NULL
             LEFT JOIN canario c ON c.id = u.canario_id AND c.criadero_id = j.criadero_id
             WHERE j.criadero_id = :criadero_id
             GROUP BY j.id, j.numero
             ORDER BY j.numero ASC',
            $criaderoId
        );

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'numero' => (int) $fila['numero'],
            'ocupantes' => (int) $fila['ocupantes'],
        ], $filas);
    }

    /**
     * Cruces activos (`fecha_fin` NULL) del criadero indexados por `jaula_id`,
     * con los datos de macho, hembra y cantidad de pichones para armar `CruceResumen` (contrato §5).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarCrucesActivosPorJaulaEnCriadero(int $criaderoId): array
    {
        $filas = $this->buscarTodosDelCriadero(
            'SELECT cr.id,
                    cr.jaula_id,
                    j.numero AS jaula_numero,
                    cr.temporada,
                    cr.fecha_inicio,
                    m.id AS macho_id,
                    m.anillo_socio AS macho_socio,
                    m.anillo_anio AS macho_anio,
                    m.anillo_correlativo AS macho_correlativo,
                    m.anillo_club AS macho_club,
                    m.sexo AS macho_sexo,
                    m.variedad_codigo AS macho_variedad_codigo,
                    vm.nombre AS macho_variedad_nombre,
                    m.estado AS macho_estado,
                    h.id AS hembra_id,
                    h.anillo_socio AS hembra_socio,
                    h.anillo_anio AS hembra_anio,
                    h.anillo_correlativo AS hembra_correlativo,
                    h.anillo_club AS hembra_club,
                    h.sexo AS hembra_sexo,
                    h.variedad_codigo AS hembra_variedad_codigo,
                    vh.nombre AS hembra_variedad_nombre,
                    h.estado AS hembra_estado,
                    (SELECT COUNT(*)
                     FROM canario p
                     WHERE p.cruce_origen_id = cr.id AND p.criadero_id = cr.criadero_id) AS pichones
             FROM cruce cr
             INNER JOIN jaula j ON j.id = cr.jaula_id AND j.criadero_id = cr.criadero_id
             INNER JOIN canario m ON m.id = cr.macho_id AND m.criadero_id = cr.criadero_id
             LEFT JOIN variedad vm ON vm.codigo = m.variedad_codigo
             INNER JOIN canario h ON h.id = cr.hembra_id AND h.criadero_id = cr.criadero_id
             LEFT JOIN variedad vh ON vh.codigo = h.variedad_codigo
             WHERE cr.criadero_id = :criadero_id AND cr.fecha_fin IS NULL
             ORDER BY cr.fecha_inicio DESC, cr.id DESC',
            $criaderoId
        );

        $porJaula = [];
        foreach ($filas as $fila) {
            $jaulaId = (int) $fila['jaula_id'];
            // Si hubiese más de un cruce activo en la jaula, conserva el más reciente.
            if (!isset($porJaula[$jaulaId])) {
                $porJaula[$jaulaId] = $fila;
            }
        }

        return $porJaula;
    }
}
