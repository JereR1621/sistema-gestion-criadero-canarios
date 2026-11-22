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
     * Devuelve el canario completo (incluyendo anillo, fechas, procedencia y nombre de variedad)
     * dentro del criadero indicado, o null si no existe / pertenece a otro criadero (RF003, RF007).
     *
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     anillo_socio: string,
     *     anillo_anio: int,
     *     anillo_correlativo: int,
     *     anillo_club: string,
     *     fecha_anillado: ?string,
     *     fecha_nacimiento: ?string,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     variedad_nombre: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     cruce_origen_id: ?int,
     *     procedencia: ?string,
     *     estado: string
     * }|null
     */
    public function buscarDetallePorIdEnCriadero(int $id, int $criaderoId): ?array
    {
        $fila = $this->buscarUnoDelCriadero(
            'SELECT c.id, c.criadero_id, c.anillo_socio, c.anillo_anio, c.anillo_correlativo, c.anillo_club,
                    c.fecha_anillado, c.fecha_nacimiento, c.sexo, c.variedad_codigo,
                    v.nombre AS variedad_nombre,
                    c.padre_id, c.madre_id, c.cruce_origen_id, c.procedencia, c.estado
             FROM canario c
             LEFT JOIN variedad v ON v.codigo = c.variedad_codigo
             WHERE c.criadero_id = :criadero_id AND c.id = :id
             LIMIT 1',
            $criaderoId,
            [':id' => $id]
        );

        if ($fila === null) {
            return null;
        }

        return $this->mapearFilaDetalle($fila);
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
     * Cuenta los canarios activos (estados `activo` y `reservado`) del criadero (RF011, AC-03).
     */
    public function contarActivosEnCriadero(int $criaderoId): int
    {
        $fila = $this->buscarUnoDelCriadero(
            "SELECT COUNT(*) AS total
             FROM canario
             WHERE criadero_id = :criadero_id AND estado IN ('activo', 'reservado')",
            $criaderoId
        );

        return $fila !== null ? (int) $fila['total'] : 0;
    }

    /**
     * Lista una página de canarios activos (incluidos los reservados) del criadero (RF011, AC-03).
     *
     * @return list<array{
     *     id: int,
     *     criadero_id: int,
     *     anillo_socio: string,
     *     anillo_anio: int,
     *     anillo_correlativo: int,
     *     anillo_club: string,
     *     fecha_anillado: ?string,
     *     fecha_nacimiento: ?string,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     variedad_nombre: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     cruce_origen_id: ?int,
     *     procedencia: ?string,
     *     estado: string
     * }>
     */
    public function listarActivosPaginadosEnCriadero(int $criaderoId, int $limite, int $desplazamiento): array
    {
        $limiteSeguro = max(1, $limite);
        $offsetSeguro = max(0, $desplazamiento);

        $filas = $this->buscarTodosDelCriadero(
            "SELECT c.id, c.criadero_id, c.anillo_socio, c.anillo_anio, c.anillo_correlativo, c.anillo_club,
                    c.fecha_anillado, c.fecha_nacimiento, c.sexo, c.variedad_codigo,
                    v.nombre AS variedad_nombre,
                    c.padre_id, c.madre_id, c.cruce_origen_id, c.procedencia, c.estado
             FROM canario c
             LEFT JOIN variedad v ON v.codigo = c.variedad_codigo
             WHERE c.criadero_id = :criadero_id AND c.estado IN ('activo', 'reservado')
             ORDER BY c.anillo_anio DESC, c.anillo_correlativo DESC, c.id DESC
             LIMIT {$limiteSeguro} OFFSET {$offsetSeguro}",
            $criaderoId
        );

        return array_map(fn (array $fila): array => $this->mapearFilaDetalle($fila), $filas);
    }

    /**
     * Lista machos o hembras del criadero (excluyendo opcionalmente un id) para elegir padres a mano (RF007).
     *
     * @return list<array{
     *     id: int,
     *     criadero_id: int,
     *     anillo_socio: string,
     *     anillo_anio: int,
     *     anillo_correlativo: int,
     *     anillo_club: string,
     *     fecha_anillado: ?string,
     *     fecha_nacimiento: ?string,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     variedad_nombre: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     cruce_origen_id: ?int,
     *     procedencia: ?string,
     *     estado: string
     * }>
     */
    public function listarPorSexoEnCriadero(int $criaderoId, string $sexo, ?int $excluirId = null): array
    {
        $sql = 'SELECT c.id, c.criadero_id, c.anillo_socio, c.anillo_anio, c.anillo_correlativo, c.anillo_club,
                       c.fecha_anillado, c.fecha_nacimiento, c.sexo, c.variedad_codigo,
                       v.nombre AS variedad_nombre,
                       c.padre_id, c.madre_id, c.cruce_origen_id, c.procedencia, c.estado
                FROM canario c
                LEFT JOIN variedad v ON v.codigo = c.variedad_codigo
                WHERE c.criadero_id = :criadero_id AND c.sexo = :sexo';
        $parametros = [':sexo' => $sexo];

        if ($excluirId !== null) {
            $sql .= ' AND c.id <> :excluir_id';
            $parametros[':excluir_id'] = $excluirId;
        }

        $sql .= ' ORDER BY c.anillo_anio DESC, c.anillo_correlativo ASC, c.id ASC';

        $filas = $this->buscarTodosDelCriadero($sql, $criaderoId, $parametros);

        return array_map(fn (array $fila): array => $this->mapearFilaDetalle($fila), $filas);
    }

    /**
     * Mapa `id => ['padre_id' => ?int, 'madre_id' => ?int]` de todos los canarios del criadero
     * para verificar ciclos genealógicos sin salir del criadero (RF007, RF003).
     *
     * @return array<int, array{padre_id: ?int, madre_id: ?int}>
     */
    public function mapaGenealogiaEnCriadero(int $criaderoId): array
    {
        $filas = $this->buscarTodosDelCriadero(
            'SELECT id, padre_id, madre_id
             FROM canario
             WHERE criadero_id = :criadero_id',
            $criaderoId
        );

        $mapa = [];
        foreach ($filas as $fila) {
            $mapa[(int) $fila['id']] = [
                'padre_id' => $fila['padre_id'] !== null ? (int) $fila['padre_id'] : null,
                'madre_id' => $fila['madre_id'] !== null ? (int) $fila['madre_id'] : null,
            ];
        }

        return $mapa;
    }

    /**
     * Inserta un nuevo canario en el criadero indicado (RF007).
     *
     * @param array{
     *     anillo_socio: string,
     *     anillo_anio: int,
     *     anillo_correlativo: int,
     *     anillo_club: string,
     *     fecha_anillado: ?string,
     *     fecha_nacimiento: ?string,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     procedencia: ?string
     * } $datos
     */
    public function crearEnCriadero(int $criaderoId, array $datos): int
    {
        if ($criaderoId <= 0) {
            throw new \InvalidArgumentException('El identificador del criadero debe ser un entero positivo.');
        }

        $consulta = $this->pdo()->prepare(
            "INSERT INTO canario
                (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club,
                 fecha_anillado, fecha_nacimiento, sexo, variedad_codigo, padre_id, madre_id, procedencia, estado)
             VALUES
                (:criadero_id, :anillo_socio, :anillo_anio, :anillo_correlativo, :anillo_club,
                 :fecha_anillado, :fecha_nacimiento, :sexo, :variedad_codigo, :padre_id, :madre_id, :procedencia, 'activo')"
        );

        $consulta->execute([
            ':criadero_id' => $criaderoId,
            ':anillo_socio' => $datos['anillo_socio'],
            ':anillo_anio' => $datos['anillo_anio'],
            ':anillo_correlativo' => $datos['anillo_correlativo'],
            ':anillo_club' => $datos['anillo_club'],
            ':fecha_anillado' => $datos['fecha_anillado'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':sexo' => $datos['sexo'],
            ':variedad_codigo' => $datos['variedad_codigo'],
            ':padre_id' => $datos['padre_id'],
            ':madre_id' => $datos['madre_id'],
            ':procedencia' => $datos['procedencia'],
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Actualiza los datos editables de un canario dentro del criadero indicado (RF007).
     *
     * @param array{
     *     anillo_socio: string,
     *     anillo_anio: int,
     *     anillo_correlativo: int,
     *     anillo_club: string,
     *     fecha_anillado: ?string,
     *     fecha_nacimiento: ?string,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     procedencia: ?string
     * } $datos
     */
    public function actualizarEnCriadero(int $id, int $criaderoId, array $datos): int
    {
        return $this->ejecutarEnCriadero(
            'UPDATE canario
             SET anillo_socio = :anillo_socio,
                 anillo_anio = :anillo_anio,
                 anillo_correlativo = :anillo_correlativo,
                 anillo_club = :anillo_club,
                 fecha_anillado = :fecha_anillado,
                 fecha_nacimiento = :fecha_nacimiento,
                 sexo = :sexo,
                 variedad_codigo = :variedad_codigo,
                 padre_id = :padre_id,
                 madre_id = :madre_id,
                 procedencia = :procedencia
             WHERE criadero_id = :criadero_id AND id = :id',
            $criaderoId,
            [
                ':id' => $id,
                ':anillo_socio' => $datos['anillo_socio'],
                ':anillo_anio' => $datos['anillo_anio'],
                ':anillo_correlativo' => $datos['anillo_correlativo'],
                ':anillo_club' => $datos['anillo_club'],
                ':fecha_anillado' => $datos['fecha_anillado'],
                ':fecha_nacimiento' => $datos['fecha_nacimiento'],
                ':sexo' => $datos['sexo'],
                ':variedad_codigo' => $datos['variedad_codigo'],
                ':padre_id' => $datos['padre_id'],
                ':madre_id' => $datos['madre_id'],
                ':procedencia' => $datos['procedencia'],
            ]
        );
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

    /**
     * @param array<string, mixed> $fila
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     anillo_socio: string,
     *     anillo_anio: int,
     *     anillo_correlativo: int,
     *     anillo_club: string,
     *     fecha_anillado: ?string,
     *     fecha_nacimiento: ?string,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     variedad_nombre: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     cruce_origen_id: ?int,
     *     procedencia: ?string,
     *     estado: string
     * }
     */
    private function mapearFilaDetalle(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'criadero_id' => (int) $fila['criadero_id'],
            'anillo_socio' => (string) $fila['anillo_socio'],
            'anillo_anio' => (int) $fila['anillo_anio'],
            'anillo_correlativo' => (int) $fila['anillo_correlativo'],
            'anillo_club' => (string) $fila['anillo_club'],
            'fecha_anillado' => $fila['fecha_anillado'] !== null ? (string) $fila['fecha_anillado'] : null,
            'fecha_nacimiento' => $fila['fecha_nacimiento'] !== null ? (string) $fila['fecha_nacimiento'] : null,
            'sexo' => (string) $fila['sexo'],
            'variedad_codigo' => $fila['variedad_codigo'] !== null ? (string) $fila['variedad_codigo'] : null,
            'variedad_nombre' => $fila['variedad_nombre'] !== null ? (string) $fila['variedad_nombre'] : null,
            'padre_id' => $fila['padre_id'] !== null ? (int) $fila['padre_id'] : null,
            'madre_id' => $fila['madre_id'] !== null ? (int) $fila['madre_id'] : null,
            'cruce_origen_id' => $fila['cruce_origen_id'] !== null ? (int) $fila['cruce_origen_id'] : null,
            'procedencia' => $fila['procedencia'] !== null ? (string) $fila['procedencia'] : null,
            'estado' => (string) $fila['estado'],
        ];
    }
}
