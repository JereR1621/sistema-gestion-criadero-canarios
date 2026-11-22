<?php

declare(strict_types=1);

namespace TuCriadero\Models;

use InvalidArgumentException;
use LogicException;
use PDO;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\HttpException;

/**
 * Base común para los modelos cuyos datos pertenecen a un criadero (SRS-RF003, SEG-001):
 * `canario`, `jaula`, `cruce`, `ubicacion`, `baja` y `factor_portado`.
 *
 * Exige que toda consulta preparada incluya `criadero_id = :criadero_id` y vincula siempre
 * el `$criaderoId` recibido desde la sesión (nunca del formulario ni de la URL).
 * Ofrece `exigirDelCriadero()` para responder 404 si un registro no existe o es de otro criadero.
 */
abstract class ModeloCriadero
{
    protected function pdo(): PDO
    {
        return Conexion::obtener();
    }

    /**
     * Ejecuta una consulta preparada que devuelve una fila filtrada por `criadero_id = :criadero_id`.
     *
     * @param array<string, scalar|null> $parametros parámetros nombrados adicionales (ej. [':id' => 5])
     * @return array<string, mixed>|null
     */
    protected function buscarUnoDelCriadero(string $sql, int $criaderoId, array $parametros = []): ?array
    {
        $this->asegurarFiltroCriadero($sql, $criaderoId);
        $parametros[':criadero_id'] = $criaderoId;

        $consulta = $this->pdo()->prepare($sql);
        $consulta->execute($parametros);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Ejecuta una consulta preparada que devuelve todas las filas filtradas por `criadero_id = :criadero_id`.
     *
     * @param array<string, scalar|null> $parametros parámetros nombrados adicionales
     * @return list<array<string, mixed>>
     */
    protected function buscarTodosDelCriadero(string $sql, int $criaderoId, array $parametros = []): array
    {
        $this->asegurarFiltroCriadero($sql, $criaderoId);
        $parametros[':criadero_id'] = $criaderoId;

        $consulta = $this->pdo()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Ejecuta un UPDATE o DELETE filtrado por `criadero_id = :criadero_id` y devuelve las filas afectadas.
     * Si el registro pertenece a otro criadero o no existe, afecta 0 filas sin modificar nada.
     *
     * @param array<string, scalar|null> $parametros parámetros nombrados adicionales
     */
    protected function ejecutarEnCriadero(string $sql, int $criaderoId, array $parametros = []): int
    {
        $this->asegurarFiltroCriadero($sql, $criaderoId);
        $parametros[':criadero_id'] = $criaderoId;

        $consulta = $this->pdo()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->rowCount();
    }

    /**
     * Responde 404 si el registro buscado no existe o pertenece a otro criadero (RF003).
     *
     * @template T of array<string, mixed>
     * @param T|null $registro
     * @return T
     */
    protected function exigirDelCriadero(?array $registro): array
    {
        if ($registro === null) {
            throw HttpException::noEncontrado();
        }

        return $registro;
    }

    private function asegurarFiltroCriadero(string $sql, int $criaderoId): void
    {
        if ($criaderoId <= 0) {
            throw new InvalidArgumentException('El identificador del criadero debe ser un entero positivo.');
        }

        if (!preg_match('/\bcriadero_id\s*=\s*:criadero_id\b/i', $sql)) {
            throw new LogicException(
                'Toda consulta en un modelo de criadero debe filtrar explícitamente por `criadero_id = :criadero_id`.'
            );
        }
    }
}
