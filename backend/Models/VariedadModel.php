<?php

declare(strict_types=1);

namespace TuCriadero\Models;

use Throwable;
use TuCriadero\Core\Conexion;

/**
 * Acceso a las tablas globales `variedad` y `variedad_factor` (catálogo de variedades, RF028).
 * No filtra por `criadero_id` porque el catálogo es compartido por todos los criadores.
 */
final class VariedadModel
{
    /**
     * @return list<array{codigo: string, nombre: string, sexo: ?string, tipo: string, rasgo: ?string}>
     */
    public function listarTodas(): array
    {
        $filas = Conexion::obtener()
            ->query('SELECT codigo, nombre, sexo, tipo, rasgo FROM variedad ORDER BY codigo')
            ->fetchAll();

        return array_map(static fn (array $fila): array => [
            'codigo' => (string) $fila['codigo'],
            'nombre' => (string) $fila['nombre'],
            'sexo' => $fila['sexo'] !== null ? (string) $fila['sexo'] : null,
            'tipo' => (string) $fila['tipo'],
            'rasgo' => $fila['rasgo'] !== null ? (string) $fila['rasgo'] : null,
        ], $filas);
    }

    /**
     * @return array{codigo: string, nombre: string, sexo: ?string, tipo: string, rasgo: ?string}|null
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT codigo, nombre, sexo, tipo, rasgo FROM variedad WHERE codigo = ?'
        );
        $consulta->execute([$codigo]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        return [
            'codigo' => (string) $fila['codigo'],
            'nombre' => (string) $fila['nombre'],
            'sexo' => $fila['sexo'] !== null ? (string) $fila['sexo'] : null,
            'tipo' => (string) $fila['tipo'],
            'rasgo' => $fila['rasgo'] !== null ? (string) $fila['rasgo'] : null,
        ];
    }

    public function contar(): int
    {
        return (int) Conexion::obtener()->query('SELECT COUNT(*) FROM variedad')->fetchColumn();
    }

    /**
     * Factores que componen una variedad (con su tipo de herencia y dosis).
     *
     * @return list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>
     */
    public function obtenerComposicion(string $codigo): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT f.id, f.nombre, f.tipo_herencia, vf.dosis
             FROM variedad_factor vf
             INNER JOIN factor f ON f.id = vf.factor_id
             WHERE vf.variedad_codigo = ?
             ORDER BY f.id'
        );
        $consulta->execute([$codigo]);

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'tipo_herencia' => (string) $fila['tipo_herencia'],
            'dosis' => (int) $fila['dosis'],
        ], $consulta->fetchAll());
    }

    /**
     * Composición de todas las variedades en una sola consulta, indexada por código.
     *
     * @return array<string, list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>>
     */
    public function obtenerTodasLasComposiciones(): array
    {
        $filas = Conexion::obtener()
            ->query(
                'SELECT vf.variedad_codigo, f.id, f.nombre, f.tipo_herencia, vf.dosis
                 FROM variedad_factor vf
                 INNER JOIN factor f ON f.id = vf.factor_id
                 ORDER BY vf.variedad_codigo, f.id'
            )
            ->fetchAll();

        $porCodigo = [];
        foreach ($filas as $fila) {
            $codigo = (string) $fila['variedad_codigo'];
            $porCodigo[$codigo][] = [
                'id' => (int) $fila['id'],
                'nombre' => (string) $fila['nombre'],
                'tipo_herencia' => (string) $fila['tipo_herencia'],
                'dosis' => (int) $fila['dosis'],
            ];
        }

        return $porCodigo;
    }

    /**
     * Inserta una variedad y su composición de forma atómica.
     * Usa INSERT estricto (sin IGNORE) para que un código duplicado falle por clave primaria `codigo`.
     *
     * @param array<int, int> $composicion mapa factor_id => dosis (1 o 2)
     */
    public function insertar(
        string $codigo,
        string $nombre,
        ?string $sexo,
        string $tipo,
        ?string $rasgo,
        array $composicion = [],
    ): void {
        $pdo = Conexion::obtener();
        $iniciaTransaccion = !$pdo->inTransaction();

        if ($iniciaTransaccion) {
            $pdo->beginTransaction();
        }

        try {
            $insertarVariedad = $pdo->prepare(
                'INSERT INTO variedad (codigo, nombre, sexo, tipo, rasgo) VALUES (?, ?, ?, ?, ?)'
            );
            $insertarVariedad->execute([$codigo, $nombre, $sexo, $tipo, $rasgo]);

            if ($composicion !== []) {
                $insertarFactor = $pdo->prepare(
                    'INSERT INTO variedad_factor (variedad_codigo, factor_id, dosis) VALUES (?, ?, ?)'
                );
                foreach ($composicion as $factorId => $dosis) {
                    $insertarFactor->execute([$codigo, (int) $factorId, (int) $dosis]);
                }
            }

            if ($iniciaTransaccion) {
                $pdo->commit();
            }
        } catch (Throwable $error) {
            if ($iniciaTransaccion && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}
