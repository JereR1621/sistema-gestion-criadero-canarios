<?php

declare(strict_types=1);

namespace TuCriadero\Models;

use Throwable;
use TuCriadero\Core\Conexion;

/**
 * Acceso a la tabla global `factor` (catálogo de factores genéticos, RF028 / RF031).
 * No filtra por `criadero_id` porque el catálogo es compartido por todos los criadores.
 */
final class FactorModel
{
    /**
     * @return list<array{id: int, nombre: string, tipo_herencia: string}>
     */
    public function listarTodos(): array
    {
        $filas = Conexion::obtener()
            ->query('SELECT id, nombre, tipo_herencia FROM factor ORDER BY id')
            ->fetchAll();

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'tipo_herencia' => $fila['tipo_herencia'] !== null ? (string) $fila['tipo_herencia'] : '',
        ], $filas);
    }

    /**
     * @return array{id: int, nombre: string, tipo_herencia: string}|null
     */
    public function buscarPorId(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, tipo_herencia FROM factor WHERE id = ?'
        );
        $consulta->execute([$id]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'tipo_herencia' => $fila['tipo_herencia'] !== null ? (string) $fila['tipo_herencia'] : '',
        ];
    }

    /**
     * Busca varios factores en una sola consulta preparada, indexados por `id`.
     *
     * @param list<int> $ids
     * @return array<int, array{id: int, nombre: string, tipo_herencia: string}>
     */
    public function buscarPorIds(array $ids): array
    {
        $unicos = array_values(array_unique(array_map('intval', $ids)));
        if ($unicos === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($unicos), '?'));
        $consulta = Conexion::obtener()->prepare(
            "SELECT id, nombre, tipo_herencia FROM factor WHERE id IN ({$marcadores}) ORDER BY id"
        );
        $consulta->execute($unicos);

        $porId = [];
        foreach ($consulta->fetchAll() as $fila) {
            $id = (int) $fila['id'];
            $porId[$id] = [
                'id' => $id,
                'nombre' => (string) $fila['nombre'],
                'tipo_herencia' => $fila['tipo_herencia'] !== null ? (string) $fila['tipo_herencia'] : '',
            ];
        }

        return $porId;
    }

    /**
     * Lista factores cuyo tipo de herencia coincide con alguno de los indicados.
     *
     * @param list<string> $tiposHerencia
     * @return list<array{id: int, nombre: string, tipo_herencia: string}>
     */
    public function listarPorTiposHerencia(array $tiposHerencia): array
    {
        $unicos = array_values(array_unique($tiposHerencia));
        if ($unicos === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($unicos), '?'));
        $consulta = Conexion::obtener()->prepare(
            "SELECT id, nombre, tipo_herencia FROM factor WHERE tipo_herencia IN ({$marcadores}) ORDER BY id"
        );
        $consulta->execute($unicos);

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'tipo_herencia' => $fila['tipo_herencia'] !== null ? (string) $fila['tipo_herencia'] : '',
        ], $consulta->fetchAll());
    }

    /**
     * @return array{id: int, nombre: string, tipo_herencia: string}|null
     */
    public function buscarPorNombre(string $nombre): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, tipo_herencia FROM factor WHERE nombre = ?'
        );
        $consulta->execute([$nombre]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'tipo_herencia' => $fila['tipo_herencia'] !== null ? (string) $fila['tipo_herencia'] : '',
        ];
    }

    public function insertar(string $nombre, string $tipoHerencia): int
    {
        $pdo = Conexion::obtener();
        $consulta = $pdo->prepare('INSERT INTO factor (nombre, tipo_herencia) VALUES (?, ?)');
        $consulta->execute([$nombre, $tipoHerencia]);

        return (int) $pdo->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, string $tipoHerencia): void
    {
        $consulta = Conexion::obtener()->prepare(
            'UPDATE factor SET nombre = ?, tipo_herencia = ? WHERE id = ?'
        );
        $consulta->execute([$nombre, $tipoHerencia, $id]);
    }

    /**
     * Ejecuta una operación dentro de una transacción (si no hay una ya abierta).
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public function enTransaccion(callable $operacion): mixed
    {
        $pdo = Conexion::obtener();
        $iniciaTransaccion = !$pdo->inTransaction();

        if ($iniciaTransaccion) {
            $pdo->beginTransaction();
        }

        try {
            $resultado = $operacion();
            if ($iniciaTransaccion) {
                $pdo->commit();
            }

            return $resultado;
        } catch (Throwable $error) {
            if ($iniciaTransaccion && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    public function contar(): int
    {
        return (int) Conexion::obtener()->query('SELECT COUNT(*) FROM factor')->fetchColumn();
    }
}
