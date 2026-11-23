<?php

declare(strict_types=1);

namespace TuCriadero\Services;

use PDOException;
use Throwable;
use TuCriadero\Core\Conexion;
use TuCriadero\Models\CanarioModel;
use TuCriadero\Models\VariedadModel;
use TuCriadero\Services\Genetica\FactoresManifestadosService;

/**
 * Reglas de negocio para el registro, consulta y edición de canarios (SRS-RF007, RF033, DAT-004).
 */
final class CanarioService
{
    public const SEXOS_VALIDOS = ['macho', 'hembra', 'sin_determinar'];
    public const POR_PAGINA = 50;

    /** Límite superior de `INT UNSIGNED` en MySQL (`007_crear_canario.sql`). */
    private const CORRELATIVO_MAXIMO = 4294967295;

    private CanarioModel $canarios;
    private AislamientoService $aislamiento;
    private CuentaService $cuentas;
    private FactoresManifestadosService $factoresManifestados;
    private VariedadModel $variedades;

    public function __construct(
        ?CanarioModel $canarios = null,
        ?AislamientoService $aislamiento = null,
        ?CuentaService $cuentas = null,
        ?FactoresManifestadosService $factoresManifestados = null,
        ?VariedadModel $variedades = null,
    ) {
        $this->canarios = $canarios ?? new CanarioModel();
        $this->aislamiento = $aislamiento ?? new AislamientoService($this->canarios);
        $this->cuentas = $cuentas ?? new CuentaService();
        $this->factoresManifestados = $factoresManifestados ?? new FactoresManifestadosService();
        $this->variedades = $variedades ?? new VariedadModel();
    }

    /**
     * Lista paginada de canarios activos (incluye reservados) del criadero (contrato §6: canarios).
     *
     * @return array{
     *     canarios: list<array<string, mixed>>,
     *     paginacion: array{pagina: int, total_paginas: int}
     * }
     */
    public function listarActivos(int $criaderoId, int $pagina = 1): array
    {
        $total = $this->canarios->contarActivosEnCriadero($criaderoId);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $paginaActual = min(max(1, $pagina), $totalPaginas);
        $desplazamiento = ($paginaActual - 1) * self::POR_PAGINA;

        $filas = $this->canarios->listarActivosPaginadosEnCriadero($criaderoId, self::POR_PAGINA, $desplazamiento);

        return [
            'canarios' => array_map(fn (array $fila): array => $this->formatearResumen($fila), $filas),
            'paginacion' => [
                'pagina' => $paginaActual,
                'total_paginas' => $totalPaginas,
            ],
        ];
    }

    /**
     * Prepara los datos requeridos por `canarios/formulario` tanto para alta como para edición (contrato §6).
     *
     * @return array{
     *     variedades: list<array<string, mixed>>,
     *     factores_portables: list<array<string, mixed>>,
     *     por_defecto: array{anillo_socio: ?string, anillo_club: ?string},
     *     posibles_padres: list<array<string, mixed>>,
     *     posibles_madres: list<array<string, mixed>>
     * }
     */
    public function prepararDatosFormulario(int $usuarioId, int $criaderoId, ?int $canarioId = null): array
    {
        $padres = $this->canarios->listarPorSexoEnCriadero($criaderoId, 'macho', $canarioId);
        $madres = $this->canarios->listarPorSexoEnCriadero($criaderoId, 'hembra', $canarioId);

        return [
            // Se entregan vacías hasta integrar #82 (RF029) y #92 (RF009).
            'variedades' => [],
            'factores_portables' => [],
            'por_defecto' => $this->cuentas->obtenerValoresPorDefectoAnillo($usuarioId),
            'posibles_padres' => array_map(fn (array $fila): array => $this->formatearResumen($fila), $padres),
            'posibles_madres' => array_map(fn (array $fila): array => $this->formatearResumen($fila), $madres),
        ];
    }

    /**
     * Obtiene la ficha completa (`CanarioFicha`) de un canario del criadero, o null si no existe
     * o pertenece a otro criadero (RF003, RF007, RF033, contrato §5).
     *
     * @return array<string, mixed>|null
     */
    public function obtenerFicha(int $canarioId, int $criaderoId): ?array
    {
        $fila = $this->canarios->buscarDetallePorIdEnCriadero($canarioId, $criaderoId);
        if ($fila === null) {
            return null;
        }

        $padre = $fila['padre_id'] !== null
            ? $this->canarios->buscarDetallePorIdEnCriadero($fila['padre_id'], $criaderoId)
            : null;
        $madre = $fila['madre_id'] !== null
            ? $this->canarios->buscarDetallePorIdEnCriadero($fila['madre_id'], $criaderoId)
            : null;

        $baja = $this->canarios->obtenerBajaEnCriadero($canarioId, $criaderoId);

        return array_merge($this->formatearResumen($fila), [
            'fecha_anillado' => $fila['fecha_anillado'],
            'fecha_nacimiento' => $fila['fecha_nacimiento'],
            'procedencia' => $fila['procedencia'],
            'padre' => $padre !== null ? $this->formatearResumen($padre) : null,
            'madre' => $madre !== null ? $this->formatearResumen($madre) : null,
            'hermanos' => [],
            'medios_hermanos' => [],
            'crias' => [],
            'cruces' => [],
            'factores_manifestados' => $this->factoresManifestados->paraFichaDesdeVariedad($fila['variedad_codigo']),
            'portadores' => [],
            'baja' => $baja !== null ? ['motivo' => $baja['motivo'], 'fecha' => $baja['fecha']] : null,
            'arbol' => [
                'padre' => $this->construirNodoArbol($fila['padre_id'], $criaderoId, 1, [$canarioId]),
                'madre' => $this->construirNodoArbol($fila['madre_id'], $criaderoId, 1, [$canarioId]),
            ],
        ]);
    }

    /**
     * Valida y registra un nuevo canario en el criadero de la sesión (SRS-RF007, SEG-003).
     *
     * @param array<string, mixed> $datos
     * @return array{errores: array<string, string>, canario_id?: int}
     */
    public function registrar(int $criaderoId, array $datos): array
    {
        return $this->modificarEnCriadero($criaderoId, function () use ($criaderoId, $datos): array {
            $validacion = $this->validarYNormalizar($criaderoId, $datos, null);
            if ($validacion['errores'] !== []) {
                return ['errores' => $validacion['errores']];
            }
            $canarioId = $this->canarios->crearEnCriadero($criaderoId, $validacion['limpios']);
            \auditar('crear', 'canario', $canarioId);
            return [
                'errores' => [],
                'canario_id' => $canarioId,
            ];
        });
    }

    /**
     * Valida y actualiza un canario existente del criadero de la sesión (SRS-RF007, SEG-003).
     *
     * @param array<string, mixed> $datos
     * @return array{errores: array<string, string>, no_encontrado?: bool}
     */
    public function actualizar(int $canarioId, int $criaderoId, array $datos): array
    {
        return $this->modificarEnCriadero($criaderoId, function () use ($canarioId, $criaderoId, $datos): array {
            $existente = $this->canarios->buscarDetallePorIdEnCriadero($canarioId, $criaderoId);
            if ($existente === null) {
                return ['errores' => ['_general' => 'El canario indicado no existe.'], 'no_encontrado' => true];
            }
            $validacion = $this->validarYNormalizar($criaderoId, $datos, $existente);
            if ($validacion['errores'] !== []) {
                return ['errores' => $validacion['errores']];
            }
            $this->canarios->actualizarEnCriadero($canarioId, $criaderoId, $validacion['limpios']);
            \auditar('actualizar', 'canario', $canarioId);
            return ['errores' => []];
        });
    }

    /** Valida sobre el estado protegido, no sobre una lectura anterior a la transacción. @return array<string,mixed> */
    private function modificarEnCriadero(int $criaderoId, callable $operacion): array
    {
        $pdo = Conexion::obtener();
        $transaccionPropia = !$pdo->inTransaction();
        try {
            if ($transaccionPropia) {
                $pdo->beginTransaction();
            }
            $this->canarios->bloquearGenealogiaEnCriadero($criaderoId);
            $resultado = $operacion();
            if ($transaccionPropia) {
                $pdo->commit();
            }
            return $resultado;
        } catch (Throwable $error) {
            if ($transaccionPropia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (self::esErrorAnilloDuplicado($error)) {
                return [
                    'errores' => [
                        'anillo_correlativo' => 'Ya existe un canario registrado con ese anillo en tu criadero.',
                    ],
                ];
            }

            throw $error;
        }
    }

    /**
     * Valida todas las reglas de RF007 y DAT-004 antes de persistir.
     *
     * @param array<string, mixed> $datos
     * @param array<string, mixed>|null $existente
     * @return array{
     *     errores: array<string, string>,
     *     limpios: array{
     *         anillo_socio: string,
     *         anillo_anio: int,
     *         anillo_correlativo: int,
     *         anillo_club: string,
     *         fecha_anillado: ?string,
     *         fecha_nacimiento: ?string,
     *         sexo: string,
     *         variedad_codigo: ?string,
     *         padre_id: ?int,
     *         madre_id: ?int,
     *         procedencia: ?string
     *     }
     * }
     */
    private function validarYNormalizar(int $criaderoId, array $datos, ?array $existente): array
    {
        $errores = [];

        $anilloSocio = trim(self::textoDe($datos['anillo_socio'] ?? ''));
        $anilloAnioCrudo = trim(self::textoDe($datos['anillo_anio'] ?? ''));
        $anilloCorrelativoCrudo = trim(self::textoDe($datos['anillo_correlativo'] ?? ''));
        $anilloClub = trim(self::textoDe($datos['anillo_club'] ?? ''));

        if ($anilloSocio === '') {
            $errores['anillo_socio'] = 'El número de socio del anillo es obligatorio.';
        } elseif (mb_strlen($anilloSocio) > 50) {
            $errores['anillo_socio'] = 'El número de socio del anillo no puede superar los 50 caracteres.';
        }

        $anilloAnio = null;
        if ($anilloAnioCrudo === '') {
            $errores['anillo_anio'] = 'El año del anillo es obligatorio.';
        } elseif (strlen($anilloAnioCrudo) !== 4 || !ctype_digit($anilloAnioCrudo) || (int) $anilloAnioCrudo < 1900) {
            $errores['anillo_anio'] = 'El año del anillo debe ser un número válido de cuatro dígitos.';
        } else {
            $anilloAnio = (int) $anilloAnioCrudo;
        }

        $anilloCorrelativo = null;
        if ($anilloCorrelativoCrudo === '') {
            $errores['anillo_correlativo'] = 'El número correlativo del anillo es obligatorio.';
        } elseif (!ctype_digit($anilloCorrelativoCrudo)) {
            $errores['anillo_correlativo'] = 'El número correlativo del anillo debe ser un entero positivo.';
        } else {
            $correlativoEntero = (int) $anilloCorrelativoCrudo;
            if ($correlativoEntero <= 0
                || $correlativoEntero > self::CORRELATIVO_MAXIMO
                || (string) $correlativoEntero !== ltrim($anilloCorrelativoCrudo, '0')
            ) {
                $errores['anillo_correlativo'] = 'El número correlativo del anillo debe ser un entero positivo.';
            } else {
                $anilloCorrelativo = $correlativoEntero;
            }
        }

        if ($anilloClub === '') {
            $errores['anillo_club'] = 'El club del anillo es obligatorio.';
        } elseif (mb_strlen($anilloClub) > 50) {
            $errores['anillo_club'] = 'El club del anillo no puede superar los 50 caracteres.';
        }

        $sexoCrudo = trim(self::textoDe($datos['sexo'] ?? ''));
        $sexo = $sexoCrudo === '' ? 'sin_determinar' : $sexoCrudo;
        if (!in_array($sexo, self::SEXOS_VALIDOS, true)) {
            $errores['sexo'] = 'El sexo indicado no es válido.';
        }

        $variedadCruda = trim(self::textoDe($datos['variedad_codigo'] ?? ''));
        $variedadCodigo = null;
        if ($variedadCruda !== '') {
            // Soporta tanto el código directo ("D.049") como el formato del datalist ("D.049 – ROJO INTENSO", contrato §4).
            $codigoExtraido = trim(explode('–', $variedadCruda, 2)[0]);
            $variedad = ($codigoExtraido !== '' && mb_strlen($codigoExtraido) <= 5)
                ? $this->variedades->buscarPorCodigo($codigoExtraido)
                : null;

            if ($variedad === null) {
                $errores['variedad_codigo'] = 'La variedad indicada no existe en el catálogo.';
            } else {
                $sexoVariedad = $variedad['sexo'] ?? null;
                if (in_array($sexoVariedad, ['macho', 'hembra'], true) && $sexo !== $sexoVariedad) {
                    $errores['variedad_codigo'] = $sexoVariedad === 'macho'
                        ? 'Esta variedad es solo para machos.'
                        : 'Esta variedad es solo para hembras.';
                } else {
                    $variedadCodigo = $codigoExtraido;
                }
            }
        }

        $fechaAnillado = $this->parsearFechaOpcional($datos['fecha_anillado'] ?? null, 'fecha_anillado', 'fecha de anillado', $errores);
        $fechaNacimiento = $this->parsearFechaOpcional($datos['fecha_nacimiento'] ?? null, 'fecha_nacimiento', 'fecha de nacimiento', $errores);

        $procedenciaCruda = trim(self::textoDe($datos['procedencia'] ?? ''));
        if (mb_strlen($procedenciaCruda) > 150) {
            $errores['procedencia'] = 'La procedencia no puede superar los 150 caracteres.';
        }
        $procedencia = $procedenciaCruda !== '' ? $procedenciaCruda : null;

        $padreId = $this->parsearIdOpcional($datos['padre_id'] ?? null, 'padre_id', 'padre', $errores);
        $madreId = $this->parsearIdOpcional($datos['madre_id'] ?? null, 'madre_id', 'madre', $errores);

        // Regla de integridad: no se indican padres a mano en un canario comprado (con procedencia)
        // ni en uno nacido de un cruce registrado en el sistema (backend.md §3).
        if ($procedencia !== null && ($padreId !== null || $madreId !== null)) {
            if ($padreId !== null) {
                $errores['padre_id'] = 'Un canario comprado (con procedencia) se registra sin padres.';
            }
            if ($madreId !== null) {
                $errores['madre_id'] = 'Un canario comprado (con procedencia) se registra sin padres.';
            }
        }

        if ($existente !== null && ($existente['cruce_origen_id'] ?? null) !== null) {
            $padreOriginal = $existente['padre_id'] !== null ? (int) $existente['padre_id'] : null;
            $madreOriginal = $existente['madre_id'] !== null ? (int) $existente['madre_id'] : null;
            if ($padreId !== $padreOriginal) {
                $errores['padre_id'] = 'No se puede cambiar el padre de un canario nacido de un cruce.';
            }
            if ($madreId !== $madreOriginal) {
                $errores['madre_id'] = 'No se puede cambiar la madre de un canario nacido de un cruce.';
            }
        }

        // Aislamiento entre criaderos (RF003, DAT-004): rechazar padres de otro criadero como si no existieran.
        $erroresAislamiento = $this->aislamiento->validarPadresDelCriadero(
            isset($errores['padre_id']) ? null : $padreId,
            isset($errores['madre_id']) ? null : $madreId,
            $criaderoId
        );
        foreach ($erroresAislamiento as $campo => $mensaje) {
            $errores[$campo] ??= $mensaje;
        }

        // Validaciones de sexo, año de anillo y ciclos genealógicos sobre padre y madre propios (backend.md §3).
        $canarioIdActual = $existente !== null ? (int) $existente['id'] : null;

        if ($padreId !== null && !isset($errores['padre_id'])) {
            $padre = $this->canarios->buscarDetallePorIdEnCriadero($padreId, $criaderoId);
            if ($padre === null) {
                $errores['padre_id'] = 'El padre indicado no pertenece a tu criadero.';
            } else {
                if ($padre['sexo'] !== 'macho') {
                    $errores['padre_id'] = 'El padre debe ser un canario macho.';
                }
                if ($anilloAnio !== null && $anilloAnio < $padre['anillo_anio']) {
                    $errores['anillo_anio'] = 'El año del anillo no puede ser anterior al del padre.';
                }
                if ($canarioIdActual !== null && ($padreId === $canarioIdActual || $this->esDescendienteDe($padreId, $canarioIdActual, $criaderoId))) {
                    $errores['padre_id'] = 'Un canario no puede ser padre de sí mismo ni de ninguno de sus antepasados.';
                }
            }
        }

        if ($madreId !== null && !isset($errores['madre_id'])) {
            $madre = $this->canarios->buscarDetallePorIdEnCriadero($madreId, $criaderoId);
            if ($madre === null) {
                $errores['madre_id'] = 'La madre indicada no pertenece a tu criadero.';
            } else {
                if ($madre['sexo'] !== 'hembra') {
                    $errores['madre_id'] = 'La madre debe ser una canaria hembra.';
                }
                if ($anilloAnio !== null && $anilloAnio < $madre['anillo_anio']) {
                    $errores['anillo_anio'] ??= 'El año del anillo no puede ser anterior al de la madre.';
                }
                if ($canarioIdActual !== null && ($madreId === $canarioIdActual || $this->esDescendienteDe($madreId, $canarioIdActual, $criaderoId))) {
                    $errores['madre_id'] = 'Una canaria no puede ser madre de sí misma ni de ninguno de sus antepasados.';
                }
            }
        }

        // Editar un progenitor también debe preservar a los hijos que ya lo referencian.
        if ($canarioIdActual !== null) {
            foreach ($this->canarios->listarHijosEnCriadero($canarioIdActual, $criaderoId) as $hijo) {
                if ($hijo['padre_id'] === $canarioIdActual && $sexo !== 'macho') {
                    $errores['sexo'] ??= 'Este canario ya es padre de otros canarios y debe conservar el sexo macho.';
                }
                if ($hijo['madre_id'] === $canarioIdActual && $sexo !== 'hembra') {
                    $errores['sexo'] ??= 'Esta canaria ya es madre de otros canarios y debe conservar el sexo hembra.';
                }
                if ($anilloAnio !== null && $anilloAnio > $hijo['anillo_anio']) {
                    $errores['anillo_anio'] ??= 'El año del anillo no puede ser posterior al de sus hijos registrados.';
                }
            }
        }

        return [
            'errores' => $errores,
            'limpios' => [
                'anillo_socio' => $anilloSocio,
                'anillo_anio' => $anilloAnio ?? 0,
                'anillo_correlativo' => $anilloCorrelativo ?? 0,
                'anillo_club' => $anilloClub,
                'fecha_anillado' => $fechaAnillado,
                'fecha_nacimiento' => $fechaNacimiento,
                'sexo' => $sexo,
                'variedad_codigo' => $variedadCodigo,
                'padre_id' => $padreId,
                'madre_id' => $madreId,
                'procedencia' => $procedencia,
            ],
        ];
    }

    /**
     * Comprueba si `$candidatoId` es descendiente de `$ancestroId` dentro del criadero (RF007).
     */
    private function esDescendienteDe(int $candidatoId, int $ancestroId, int $criaderoId): bool
    {
        $mapa = $this->canarios->mapaGenealogiaEnCriadero($criaderoId);
        $cola = [$candidatoId];
        $visitados = [];

        while ($cola !== []) {
            $actual = array_shift($cola);
            if ($actual === $ancestroId) {
                return true;
            }
            if (isset($visitados[$actual]) || !isset($mapa[$actual])) {
                continue;
            }
            $visitados[$actual] = true;

            if ($mapa[$actual]['padre_id'] !== null) {
                $cola[] = $mapa[$actual]['padre_id'];
            }
            if ($mapa[$actual]['madre_id'] !== null) {
                $cola[] = $mapa[$actual]['madre_id'];
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $errores
     */
    private function parsearIdOpcional(mixed $valor, string $campo, string $etiqueta, array &$errores): ?int
    {
        $texto = trim(self::textoDe($valor));
        if ($texto === '') {
            return null;
        }

        if (!ctype_digit($texto)) {
            $errores[$campo] = "El identificador de {$etiqueta} no es válido.";
            return null;
        }

        $entero = (int) $texto;
        if ($entero <= 0 || (string) $entero !== ltrim($texto, '0')) {
            $errores[$campo] = "El identificador de {$etiqueta} no es válido.";
            return null;
        }

        return $entero;
    }

    /**
     * @param array<string, string> $errores
     */
    private function parsearFechaOpcional(mixed $valor, string $campo, string $etiqueta, array &$errores): ?string
    {
        $texto = trim(self::textoDe($valor));
        if ($texto === '') {
            return null;
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $partes)
            || !checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1])
        ) {
            $errores[$campo] = "La {$etiqueta} debe tener formato AAAA-MM-DD válido.";
            return null;
        }

        return $texto;
    }

    /**
     * Construye `CanarioResumen` según `docs/contrato-back-front.md` §5.
     *
     * @param array<string, mixed> $fila
     * @return array{
     *     id: int,
     *     anillo: array{socio: string, anio: int, correlativo: string, club: string},
     *     anillo_texto: string,
     *     sexo: string,
     *     variedad: ?array{codigo: string, nombre: string},
     *     ubicacion_texto: string,
     *     estado: string,
     *     url: string
     * }
     */
    public function formatearResumen(array $fila): array
    {
        $id = (int) $fila['id'];
        $socio = (string) $fila['anillo_socio'];
        $anio = (int) $fila['anillo_anio'];
        $correlativo = (string) $fila['anillo_correlativo'];
        $club = (string) $fila['anillo_club'];

        $variedad = null;
        if (($fila['variedad_codigo'] ?? null) !== null) {
            $codigo = (string) $fila['variedad_codigo'];
            $variedad = [
                'codigo' => $codigo,
                'nombre' => (string) ($fila['variedad_nombre'] ?? $codigo),
            ];
        }

        return [
            'id' => $id,
            'anillo' => [
                'socio' => $socio,
                'anio' => $anio,
                'correlativo' => $correlativo,
                'club' => $club,
            ],
            'anillo_texto' => "{$socio} · {$anio} · {$correlativo} · {$club}",
            'sexo' => (string) $fila['sexo'],
            'variedad' => $variedad,
            // RF007: se muestra como «Sin ubicación» hasta que se integre RF015 (#104).
            'ubicacion_texto' => 'Sin ubicación',
            'estado' => (string) $fila['estado'],
            'url' => self::urlOAlternativa('canarios.ficha', ['id' => $id], "/canarios/{$id}"),
        ];
    }

    /**
     * Construye recursivamente un `NodoArbol` de hasta 3 generaciones (AC-01, contrato §5).
     *
     * @param list<int> $visitados
     * @return array<string, mixed>|null
     */
    private function construirNodoArbol(?int $canarioId, int $criaderoId, int $generacion, array $visitados): ?array
    {
        if ($canarioId === null || $generacion > 3 || in_array($canarioId, $visitados, true)) {
            return null;
        }

        $fila = $this->canarios->buscarDetallePorIdEnCriadero($canarioId, $criaderoId);
        if ($fila === null) {
            return null;
        }

        $nuevosVisitados = [...$visitados, $canarioId];

        return array_merge($this->formatearResumen($fila), [
            'generacion' => $generacion,
            'padre' => $generacion < 3
                ? $this->construirNodoArbol($fila['padre_id'], $criaderoId, $generacion + 1, $nuevosVisitados)
                : null,
            'madre' => $generacion < 3
                ? $this->construirNodoArbol($fila['madre_id'], $criaderoId, $generacion + 1, $nuevosVisitados)
                : null,
        ]);
    }

    private static function textoDe(mixed $valor): string
    {
        if (is_string($valor)) {
            return $valor;
        }

        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        return '';
    }

    private static function esErrorAnilloDuplicado(Throwable $error): bool
    {
        if (!$error instanceof PDOException || $error->getCode() !== '23000') {
            return false;
        }

        return ($error->errorInfo[1] ?? null) === 1062;
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
