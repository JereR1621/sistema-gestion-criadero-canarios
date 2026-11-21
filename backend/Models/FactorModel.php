<?php

declare(strict_types=1);

namespace TuCriadero\Models;

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
            'tipo_herencia' => (string) $fila['tipo_herencia'],
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
            'tipo_herencia' => (string) $fila['tipo_herencia'],
        ];
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
            'tipo_herencia' => (string) $fila['tipo_herencia'],
        ];
    }

    public function contar(): int
    {
        return (int) Conexion::obtener()->query('SELECT COUNT(*) FROM factor')->fetchColumn();
    }
}
