<?php

declare(strict_types=1);

namespace TuCriadero\Services;

use PDOException;
use Throwable;
use TuCriadero\Core\Conexion;
use TuCriadero\Models\JaulaModel;

/**
 * Reglas de negocio de jaulas numeradas (SRS-RF014, DAT-004, RF003).
 *
 * El número de jaula es único dentro de cada criadero. La jaula grande no se registra:
 * es la ubicación sin jaula (`jaula_id` NULL, RF016).
 */
final class JaulaService
{
    /** Límite superior de `INT UNSIGNED` en MySQL (`003_crear_jaula.sql`). */
    private const NUMERO_MAXIMO = 4294967295;

    public function __construct(
        private readonly JaulaModel $jaulas = new JaulaModel(),
    ) {
    }

    /**
     * Lista las jaulas del criadero en el formato `JaulaResumen` del contrato (§5 y §6).
     *
     * @return list<array{
     *     id: int,
     *     numero: int,
     *     ocupantes: int,
     *     cruce_activo: array<string, mixed>|null,
     *     url: string
     * }>
     */
    public function listarResumenes(int $criaderoId): array
    {
        $jaulas = $this->jaulas->listarConOcupantesEnCriadero($criaderoId);
        $crucesActivos = $this->jaulas->listarCrucesActivosPorJaulaEnCriadero($criaderoId);

        $resumenes = [];
        foreach ($jaulas as $jaula) {
            $id = $jaula['id'];
            $cruceFila = $crucesActivos[$id] ?? null;

            $resumenes[] = [
                'id' => $id,
                'numero' => $jaula['numero'],
                'ocupantes' => $jaula['ocupantes'],
                'cruce_activo' => $cruceFila !== null ? self::armarCruceResumen($cruceFila) : null,
                'url' => self::urlOAlternativa('jaulas.ficha', ['id' => $id], "/jaulas/{$id}"),
            ];
        }

        return $resumenes;
    }

    /**
     * Valida y registra una nueva jaula numerada en el criadero del usuario autenticado (SRS-RF014).
     *
     * @param array<string, mixed> $datos
     * @return array{errores: array<string, string>, jaula_id?: int, numero?: int}
     */
    public function registrar(int $criaderoId, array $datos): array
    {
        $numeroCrudo = trim((string) ($datos['numero'] ?? ''));

        if ($numeroCrudo === '') {
            return [
                'errores' => [
                    'numero' => 'El número de jaula es obligatorio.',
                ],
            ];
        }

        if (!preg_match('/^\d+$/', $numeroCrudo)) {
            return [
                'errores' => [
                    'numero' => 'El número de jaula debe ser un número entero positivo.',
                ],
            ];
        }

        $numero = (int) $numeroCrudo;
        if ($numero <= 0 || $numero > self::NUMERO_MAXIMO || (string) $numero !== ltrim($numeroCrudo, '0')) {
            return [
                'errores' => [
                    'numero' => 'El número de jaula debe ser un número entero positivo.',
                ],
            ];
        }

        if ($this->jaulas->existeNumeroEnCriadero($numero, $criaderoId)) {
            return [
                'errores' => [
                    'numero' => 'Número de jaula ya registrado en el criadero.',
                ],
            ];
        }

        $pdo = Conexion::obtener();
        $transaccionPropia = !$pdo->inTransaction();

        try {
            if ($transaccionPropia) {
                $pdo->beginTransaction();
            }

            if ($this->jaulas->existeNumeroEnCriadero($numero, $criaderoId)) {
                if ($transaccionPropia && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                return [
                    'errores' => [
                        'numero' => 'Número de jaula ya registrado en el criadero.',
                    ],
                ];
            }

            $jaulaId = $this->jaulas->crear($criaderoId, $numero);
            \auditar('crear', 'jaula', $jaulaId);

            if ($transaccionPropia) {
                $pdo->commit();
            }

            return [
                'errores' => [],
                'jaula_id' => $jaulaId,
                'numero' => $numero,
            ];
        } catch (Throwable $error) {
            if ($transaccionPropia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Dos envíos simultáneos con el mismo número de jaula: uq_jaula_criadero_numero rechaza el segundo.
            if ($error instanceof PDOException && $error->getCode() === '23000') {
                return [
                    'errores' => [
                        'numero' => 'Número de jaula ya registrado en el criadero.',
                    ],
                ];
            }

            throw $error;
        }
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private static function armarCruceResumen(array $fila): array
    {
        $cruceId = (int) $fila['id'];
        $jaulaNumero = (int) $fila['jaula_numero'];
        $ubicacionTexto = "Jaula {$jaulaNumero}";

        return [
            'id' => $cruceId,
            'macho' => self::armarCanarioResumen($fila, 'macho', $ubicacionTexto),
            'hembra' => self::armarCanarioResumen($fila, 'hembra', $ubicacionTexto),
            'jaula_numero' => $jaulaNumero,
            'temporada' => (int) $fila['temporada'],
            'fecha_inicio' => (string) $fila['fecha_inicio'],
            'fecha_fin' => null,
            'activo' => true,
            'pichones' => (int) $fila['pichones'],
            'url' => self::urlOAlternativa('cruces.ficha', ['id' => $cruceId], "/cruces/{$cruceId}"),
        ];
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private static function armarCanarioResumen(array $fila, string $prefijo, string $ubicacionTexto): array
    {
        $id = (int) $fila["{$prefijo}_id"];
        $socio = (string) $fila["{$prefijo}_socio"];
        $anio = (int) $fila["{$prefijo}_anio"];
        $correlativo = (string) $fila["{$prefijo}_correlativo"];
        $club = (string) $fila["{$prefijo}_club"];
        $variedadCodigo = $fila["{$prefijo}_variedad_codigo"];

        return [
            'id' => $id,
            'anillo' => [
                'socio' => $socio,
                'anio' => $anio,
                'correlativo' => $correlativo,
                'club' => $club,
            ],
            'anillo_texto' => "{$socio} · {$anio} · {$correlativo} · {$club}",
            'sexo' => (string) $fila["{$prefijo}_sexo"],
            'variedad' => $variedadCodigo !== null
                ? [
                    'codigo' => (string) $variedadCodigo,
                    'nombre' => (string) ($fila["{$prefijo}_variedad_nombre"] ?? ''),
                ]
                : null,
            'ubicacion_texto' => $ubicacionTexto,
            'estado' => (string) $fila["{$prefijo}_estado"],
            'url' => self::urlOAlternativa('canarios.ficha', ['id' => $id], "/canarios/{$id}"),
        ];
    }

    /** @param array<string, scalar> $parametros */
    private static function urlOAlternativa(string $nombreRuta, array $parametros, string $alternativa): string
    {
        try {
            return \ruta($nombreRuta, $parametros);
        } catch (Throwable) {
            return $alternativa;
        }
    }
}
