<?php

declare(strict_types=1);

namespace TuCriadero\Services;

use DomainException;
use InvalidArgumentException;
use TuCriadero\Core\HttpException;
use TuCriadero\Models\FactorModel;
use TuCriadero\Models\VariedadModel;

/**
 * Reglas del catálogo global de variedades de color y factores genéticos (SRS-RF028, SRS-RF030,
 * SRS-RF031, SRS-RF032, DAT-004, DAT-005).
 *
 * Lee siempre a través de `VariedadModel` y `FactorModel` (sin consultar PDO directamente).
 * El catálogo es global: no se filtra por `criadero_id`.
 *
 * Los métodos que usa el administrador para mantener el catálogo (RF032) devuelven sus errores
 * como `['errores' => ['campo' => 'mensaje']]`, sin guardar nada cuando hay errores.
 */
final class CatalogoService
{
    public const TOTAL_VARIEDADES_NOMENCLADOR = 880;

    /** Tipos de herencia admitidos por la tabla de Mendel y docs/backend.md §3 y §5. */
    public const TIPOS_HERENCIA_VALIDOS = ['ligado_sexo', 'recesivo', 'dominante', 'codominante'];

    /** Solo factores recesivos o ligados al sexo pueden portarse (DAT-004, docs/backend.md §3). */
    public const TIPOS_HERENCIA_PORTABLES = ['ligado_sexo', 'recesivo'];

    private const SEXOS_VALIDOS = ['macho', 'hembra'];
    private const TIPOS_VALIDOS = ['melanico', 'lipocromico'];
    private const RASGOS_VALIDOS = ['ala_blanca', 'alas_grises'];

    public function __construct(
        private readonly VariedadModel $variedades = new VariedadModel(),
        private readonly FactorModel $factores = new FactorModel(),
    ) {
    }

    /**
     * @return list<array{codigo: string, nombre: string, sexo: ?string, tipo: string, rasgo: ?string}>
     */
    public function listarVariedades(): array
    {
        return $this->variedades->listarTodas();
    }

    /**
     * Devuelve todas las variedades del catálogo con su composición de factores.
     *
     * @return list<array{
     *     codigo: string,
     *     nombre: string,
     *     sexo: ?string,
     *     tipo: string,
     *     rasgo: ?string,
     *     factores: list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>
     * }>
     */
    public function listarVariedadesConComposicion(): array
    {
        $variedades = $this->variedades->listarTodas();
        $composiciones = $this->variedades->obtenerTodasLasComposiciones();

        $resultado = [];
        foreach ($variedades as $variedad) {
            $variedad['factores'] = $composiciones[$variedad['codigo']] ?? [];
            $resultado[] = $variedad;
        }

        return $resultado;
    }

    /**
     * Variedades del catálogo para la pantalla de administración (SRS-RF032, contrato §5 y §6):
     * `VariedadOpcion` más su composición de factores como `ComposicionFactor` (`factor_id`, `nombre`, `dosis`).
     *
     * `$q` filtra por código o por nombre (sin distinguir mayúsculas); vacío lista todas.
     * El catálogo es global: la respuesta no depende del `criadero_id` de la sesión.
     *
     * @return list<array{
     *     codigo: string,
     *     nombre: string,
     *     sexo: ?string,
     *     factores: list<array{factor_id: int, nombre: string, dosis: int}>
     * }>
     */
    public function listarVariedadesParaAdmin(?string $q = null): array
    {
        $busqueda = mb_strtoupper(trim((string) $q), 'UTF-8');
        $resultado = [];

        foreach ($this->listarVariedadesConComposicion() as $variedad) {
            $coincide = $busqueda === ''
                || str_contains(mb_strtoupper($variedad['codigo'], 'UTF-8'), $busqueda)
                || str_contains(mb_strtoupper($variedad['nombre'], 'UTF-8'), $busqueda);

            if (!$coincide) {
                continue;
            }

            $resultado[] = [
                'codigo' => $variedad['codigo'],
                'nombre' => $variedad['nombre'],
                'sexo' => $variedad['sexo'],
                'factores' => self::formatearComposicionContrato($variedad['factores']),
            ];
        }

        return $resultado;
    }

    /**
     * Variedad para la pantalla de edición del administrador (contrato §5 y §6):
     * `VariedadOpcion` + `tipo`, `rasgo`, `factores` [`ComposicionFactor`].
     *
     * @return array{
     *     codigo: string,
     *     nombre: string,
     *     sexo: ?string,
     *     tipo: string,
     *     rasgo: ?string,
     *     factores: list<array{factor_id: int, nombre: string, dosis: int}>
     * }|null
     */
    public function obtenerVariedadParaAdmin(string $codigo): ?array
    {
        $variedad = $this->obtenerVariedad($codigo);
        if ($variedad === null) {
            return null;
        }

        return [
            'codigo' => $variedad['codigo'],
            'nombre' => $variedad['nombre'],
            'sexo' => $variedad['sexo'],
            'tipo' => $variedad['tipo'],
            'rasgo' => $variedad['rasgo'],
            'factores' => self::formatearComposicionContrato($variedad['factores']),
        ];
    }

    /**
     * @param list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}> $factores
     * @return list<array{factor_id: int, nombre: string, dosis: int}>
     */
    private static function formatearComposicionContrato(array $factores): array
    {
        return array_map(
            static fn (array $factor): array => [
                'factor_id' => $factor['id'],
                'nombre' => $factor['nombre'],
                'dosis' => $factor['dosis'],
            ],
            $factores,
        );
    }

    /**
     * @return array{
     *     codigo: string,
     *     nombre: string,
     *     sexo: ?string,
     *     tipo: string,
     *     rasgo: ?string,
     *     factores: list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>
     * }|null
     */
    public function obtenerVariedad(string $codigo): ?array
    {
        $variedad = $this->variedades->buscarPorCodigo($codigo);
        if ($variedad === null) {
            return null;
        }

        $variedad['factores'] = $this->obtenerComposicion($codigo);

        return $variedad;
    }

    /**
     * Factores genéticos que manifiesta una variedad, con su tipo de herencia tomado del catálogo (RF028 / RF031 / RF033).
     *
     * @return list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>
     */
    public function obtenerComposicion(string $codigo): array
    {
        $factores = $this->variedades->obtenerComposicion($codigo);
        foreach ($factores as $factor) {
            $this->asegurarTipoHerenciaEnCatalogo($factor);
        }

        return $factores;
    }

    /**
     * @return list<array{id: int, nombre: string, tipo_herencia: string}>
     */
    public function listarFactores(): array
    {
        return $this->factores->listarTodos();
    }

    /**
     * Devuelve el factor y su tipo de herencia directamente desde el catálogo precargado (SRS-RF031).
     * El criador nunca indica el tipo de herencia.
     *
     * @return array{id: int, nombre: string, tipo_herencia: string}
     */
    public function obtenerFactor(int $factorId): array
    {
        $factor = $this->factores->buscarPorId($factorId);
        if ($factor === null) {
            throw new DomainException("El factor genético con id {$factorId} no existe en el catálogo.");
        }

        $this->asegurarTipoHerenciaEnCatalogo($factor);

        return $factor;
    }

    /**
     * Toma el tipo de herencia de un factor desde el catálogo precargado (SRS-RF031).
     * Falla si el factor no existe o carece de un tipo de herencia válido en el catálogo.
     */
    public function obtenerTipoHerencia(int $factorId): string
    {
        return $this->obtenerFactor($factorId)['tipo_herencia'];
    }

    /**
     * Resuelve los factores genéticos para usarlos en la ficha de un canario o en un cruce (SRS-RF031),
     * tomando exclusivamente su tipo de herencia del catálogo precargado.
     *
     * @param list<int> $factorIds
     * @return array<int, array{id: int, nombre: string, tipo_herencia: string}> mapa id => factor
     */
    public function resolverFactoresParaUso(array $factorIds): array
    {
        $unicos = array_values(array_unique(array_map('intval', $factorIds)));
        if ($unicos === []) {
            return [];
        }

        $encontrados = $this->factores->buscarPorIds($unicos);
        $resueltos = [];

        foreach ($unicos as $id) {
            if (!isset($encontrados[$id])) {
                throw new DomainException("El factor genético con id {$id} no existe en el catálogo.");
            }
            $factor = $encontrados[$id];
            $this->asegurarTipoHerenciaEnCatalogo($factor);
            $resueltos[$id] = $factor;
        }

        return $resueltos;
    }

    /**
     * Factores que un canario puede portar según su tipo de herencia en el catálogo (recesivo o ligado al sexo).
     *
     * @return list<array{id: int, nombre: string, tipo_herencia: string}>
     */
    public function listarFactoresPortables(): array
    {
        return $this->factores->listarPorTiposHerencia(self::TIPOS_HERENCIA_PORTABLES);
    }

    /**
     * Indica si un factor puede portarse según el tipo de herencia definido en el catálogo (RF031 / DAT-004).
     */
    public function esFactorPortable(int $factorId): bool
    {
        return in_array($this->obtenerTipoHerencia($factorId), self::TIPOS_HERENCIA_PORTABLES, true);
    }

    /**
     * Indica si un factor es ligado al sexo según el catálogo (RF031 / RF013).
     */
    public function esFactorLigadoAlSexo(int $factorId): bool
    {
        return $this->obtenerTipoHerencia($factorId) === 'ligado_sexo';
    }

    /**
     * Valida que el tipo de herencia esté presente y sea uno de los admitidos (SRS-RF031).
     */
    public static function validarTipoHerencia(?string $tipoHerencia, ?string $nombreFactor = null): string
    {
        $valor = $tipoHerencia !== null ? trim($tipoHerencia) : '';
        if ($valor === '') {
            $etiqueta = $nombreFactor !== null && trim($nombreFactor) !== ''
                ? "«" . trim($nombreFactor) . "»"
                : 'indicado';
            throw new InvalidArgumentException("El factor {$etiqueta} debe tener un tipo de herencia.");
        }

        if (!in_array($valor, self::TIPOS_HERENCIA_VALIDOS, true)) {
            throw new InvalidArgumentException(sprintf(
                'El tipo de herencia «%s» no es válido. Debe ser uno de: %s.',
                $valor,
                implode(', ', self::TIPOS_HERENCIA_VALIDOS)
            ));
        }

        return $valor;
    }

    /**
     * Registra un factor en el catálogo validando que tenga nombre y tipo de herencia (SRS-RF031).
     * Si falta el tipo de herencia o no es válido, informa el error sin guardar nada.
     * El nombre se guarda en mayúsculas, como el resto del catálogo («MARFIL»).
     */
    public function cargarFactor(string $nombre, ?string $tipoHerencia): int
    {
        $this->impedirModificacionPorCriador();

        $nombreNormalizado = mb_strtoupper(trim($nombre), 'UTF-8');
        if ($nombreNormalizado === '') {
            throw new InvalidArgumentException('El nombre del factor es obligatorio.');
        }

        $herenciaNormalizada = self::validarTipoHerencia($tipoHerencia, $nombreNormalizado);

        if ($this->factores->buscarPorNombre($nombreNormalizado) !== null) {
            throw new DomainException("Ya existe un factor con el nombre «{$nombreNormalizado}» en el catálogo.");
        }

        return $this->factores->enTransaccion(function () use ($nombreNormalizado, $herenciaNormalizada): int {
            $id = $this->factores->insertar($nombreNormalizado, $herenciaNormalizada);
            \auditar('crear', 'factor', $id);

            return $id;
        });
    }

    /**
     * Crea un factor del catálogo para el administrador (SRS-RF032, admin.factores.guardar).
     * Devuelve los errores como `['campo' => 'mensaje']` en lugar de lanzar excepciones:
     * `nombre` para el factor vacío o repetido y `tipo_herencia` cuando falta o no es válido.
     * El nombre se guarda en mayúsculas, como el resto del catálogo («MARFIL»).
     *
     * @return array{errores: array<string, string>, factor_id?: int}
     */
    public function crearFactor(string $nombre, ?string $tipoHerencia): array
    {
        $this->impedirModificacionPorCriador();

        $nombreNormalizado = mb_strtoupper(trim($nombre), 'UTF-8');

        $errores = [];
        if ($nombreNormalizado === '') {
            $errores['nombre'] = 'El nombre del factor es obligatorio.';
        }

        try {
            $herenciaNormalizada = self::validarTipoHerencia($tipoHerencia, $nombreNormalizado);
        } catch (InvalidArgumentException $error) {
            $herenciaNormalizada = '';
            $errores['tipo_herencia'] = $error->getMessage();
        }

        if ($errores !== []) {
            return ['errores' => $errores];
        }

        try {
            $factorId = $this->cargarFactor($nombreNormalizado, $herenciaNormalizada);
        } catch (DomainException $error) {
            return ['errores' => ['nombre' => $error->getMessage()]];
        }

        return ['errores' => [], 'factor_id' => $factorId];
    }

    /**
     * Busca un factor por id devolviendo null si no existe (útil en controladores para 404).
     *
     * @return array{id: int, nombre: string, tipo_herencia: string}|null
     */
    public function buscarFactor(int $factorId): ?array
    {
        $factor = $this->factores->buscarPorId($factorId);
        if ($factor === null) {
            return null;
        }

        $this->asegurarTipoHerenciaEnCatalogo($factor);

        return $factor;
    }

    /**
     * Actualiza un factor existente del catálogo para el administrador (SRS-RF032, admin.factores.actualizar).
     *
     * @return array{errores: array<string, string>}
     */
    public function actualizarFactor(int $factorId, string $nombre, ?string $tipoHerencia): array
    {
        $this->impedirModificacionPorCriador();

        if ($this->factores->buscarPorId($factorId) === null) {
            return ['errores' => ['nombre' => "El factor con id {$factorId} no existe en el catálogo."]];
        }

        $nombreNormalizado = mb_strtoupper(trim($nombre), 'UTF-8');

        $errores = [];
        if ($nombreNormalizado === '') {
            $errores['nombre'] = 'El nombre del factor es obligatorio.';
        } elseif (mb_strlen($nombreNormalizado) > 80) {
            $errores['nombre'] = 'El nombre del factor no puede superar los 80 caracteres.';
        } else {
            $existente = $this->factores->buscarPorNombre($nombreNormalizado);
            if ($existente !== null && $existente['id'] !== $factorId) {
                $errores['nombre'] = "Ya existe un factor con el nombre «{$nombreNormalizado}» en el catálogo.";
            }
        }

        try {
            $herenciaNormalizada = self::validarTipoHerencia($tipoHerencia, $nombreNormalizado);
        } catch (InvalidArgumentException $error) {
            $herenciaNormalizada = '';
            $errores['tipo_herencia'] = $error->getMessage();
        }

        if ($herenciaNormalizada !== '') {
            if ($herenciaNormalizada !== 'codominante' && $this->factores->contarComposicionesConDosisDoble($factorId) > 0) {
                $errores['tipo_herencia'] = 'No se puede cambiar este factor a un tipo no codominante porque hay variedades con dosis 2 que lo usan.';
            } elseif (!in_array($herenciaNormalizada, self::TIPOS_HERENCIA_PORTABLES, true) && $this->factores->contarCanariosPortadores($factorId) > 0) {
                $errores['tipo_herencia'] = 'No se puede cambiar este factor a un tipo no portable porque hay canarios que lo portan.';
            }
        }

        if ($errores !== []) {
            return ['errores' => $errores];
        }

        $this->factores->enTransaccion(function () use ($factorId, $nombreNormalizado, $herenciaNormalizada): void {
            $this->factores->actualizar($factorId, $nombreNormalizado, $herenciaNormalizada);
            \auditar('actualizar', 'factor', $factorId);
        });

        return ['errores' => []];
    }

    /**
     * Valida la integridad del catálogo precargado según SRS-RF028 y DAT-004:
     * - 880 variedades con códigos D.001 a D.880 sin huecos ni duplicados (solo sección D).
     * - Todas con nombre no vacío.
     * - Todos los factores de la composición existen y tienen dosis válida (1 o 2; dosis 2 solo codominante).
     * - Ninguna combinación de factores y dosis se repite para el mismo sexo, tipo y rasgo.
     *
     * @return array{
     *     total_variedades: int,
     *     total_factores: int,
     *     variedades_con_factores: int,
     *     variedades_forma_normal: int
     * }
     */
    public function validarCatalogoPrecargado(): array
    {
        $variedades = $this->listarVariedadesConComposicion();
        $factoresPorId = [];
        foreach ($this->factores->listarTodos() as $factor) {
            $this->asegurarTipoHerenciaEnCatalogo($factor);
            $factoresPorId[$factor['id']] = $factor;
        }

        $totalVariedades = count($variedades);
        if ($totalVariedades !== self::TOTAL_VARIEDADES_NOMENCLADOR) {
            throw new DomainException(sprintf(
                'El catálogo debe contener %d variedades del nomenclador oficial 2026; hay %d.',
                self::TOTAL_VARIEDADES_NOMENCLADOR,
                $totalVariedades
            ));
        }

        $codigosEsperados = [];
        for ($i = 1; $i <= self::TOTAL_VARIEDADES_NOMENCLADOR; $i++) {
            $codigosEsperados[] = sprintf('D.%03d', $i);
        }

        $codigosCargados = array_column($variedades, 'codigo');
        if ($codigosCargados !== $codigosEsperados) {
            throw new DomainException(
                'Los códigos del catálogo deben cubrir exactamente el rango D.001 a D.880 sin huecos ni duplicados.'
            );
        }

        $combinacionesVistas = [];
        $conFactores = 0;

        foreach ($variedades as $variedad) {
            if (trim($variedad['nombre']) === '') {
                throw new DomainException("La variedad {$variedad['codigo']} no tiene nombre.");
            }

            $composicion = [];
            foreach ($variedad['factores'] as $factor) {
                $id = $factor['id'];
                $dosis = $factor['dosis'];

                if (!isset($factoresPorId[$id])) {
                    throw new DomainException("La variedad {$variedad['codigo']} referencia un factor inexistente ({$id}).");
                }
                if ($dosis !== 1 && $dosis !== 2) {
                    throw new DomainException("La variedad {$variedad['codigo']} tiene una dosis inválida ({$dosis}) para el factor {$id}.");
                }
                if ($dosis === 2 && $factoresPorId[$id]['tipo_herencia'] !== 'codominante') {
                    throw new DomainException("La variedad {$variedad['codigo']} asigna dosis 2 al factor no codominante {$factoresPorId[$id]['nombre']}.");
                }

                $composicion[$id] = $dosis;
            }

            if ($composicion !== []) {
                $conFactores++;
            }

            $clave = self::claveCombinacion($variedad['sexo'], $variedad['tipo'], $variedad['rasgo'], $composicion);
            if (isset($combinacionesVistas[$clave])) {
                throw new DomainException(sprintf(
                    'La variedad %s repite la combinación de factores y dosis de %s para el mismo sexo, tipo y rasgo.',
                    $variedad['codigo'],
                    $combinacionesVistas[$clave]
                ));
            }
            $combinacionesVistas[$clave] = $variedad['codigo'];
        }

        return [
            'total_variedades' => $totalVariedades,
            'total_factores' => count($factoresPorId),
            'variedades_con_factores' => $conFactores,
            'variedades_forma_normal' => $totalVariedades - $conFactores,
        ];
    }

    /**
     * Carga una variedad del nomenclador de color (sección D) validando formato, factores y unicidad
     * de composición por sexo, tipo y rasgo (DAT-004). Si el código ya existe, falla en la clave primaria `codigo`.
     *
     * @param array<int, int> $composicion mapa factor_id => dosis (1 o 2)
     */
    public function cargarVariedad(
        string $codigo,
        string $nombre,
        ?string $sexo,
        string $tipo,
        ?string $rasgo,
        array $composicion = [],
    ): void {
        $this->impedirModificacionPorCriador();

        $codigo = trim($codigo);
        $nombre = trim($nombre);

        if (!preg_match('/^D\.\d{3}$/', $codigo)) {
            throw new InvalidArgumentException("El código «{$codigo}» no corresponde a la sección D de canarios de color (D.001–D.880).");
        }
        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre de la variedad es obligatorio.');
        }
        if ($sexo !== null && !in_array($sexo, self::SEXOS_VALIDOS, true)) {
            throw new InvalidArgumentException("El sexo «{$sexo}» no es válido.");
        }
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            throw new InvalidArgumentException("El tipo «{$tipo}» no es válido.");
        }
        if ($rasgo !== null && !in_array($rasgo, self::RASGOS_VALIDOS, true)) {
            throw new InvalidArgumentException("El rasgo «{$rasgo}» no es válido.");
        }

        $factoresPorId = [];
        foreach ($this->factores->listarTodos() as $factor) {
            $factoresPorId[$factor['id']] = $factor;
        }

        $composicionNormalizada = [];
        foreach ($composicion as $factorId => $dosis) {
            $id = (int) $factorId;
            $copias = (int) $dosis;

            if (!isset($factoresPorId[$id])) {
                throw new InvalidArgumentException("El factor con id {$id} no existe en el catálogo.");
            }
            if ($copias !== 1 && $copias !== 2) {
                throw new InvalidArgumentException("La dosis debe ser 1 o 2 (recibido: {$copias}).");
            }
            if ($copias === 2 && $factoresPorId[$id]['tipo_herencia'] !== 'codominante') {
                throw new InvalidArgumentException("Solo un factor codominante admite dosis 2 ({$factoresPorId[$id]['nombre']}).");
            }

            $composicionNormalizada[$id] = $copias;
        }

        // Si el código aún no existe, se verifica DAT-004 (combinación única por sexo, tipo y rasgo).
        // Si el código ya existe, se delega a VariedadModel::insertar() para que falle por clave primaria `codigo`.
        if ($this->variedades->buscarPorCodigo($codigo) === null) {
            $repetida = $this->buscarCombinacionRepetida($sexo, $tipo, $rasgo, $composicionNormalizada);
            if ($repetida !== null) {
                throw new DomainException(sprintf(
                    'Ya existe una variedad (%s) con la misma combinación de factores y dosis para el mismo sexo, tipo y rasgo.',
                    $repetida
                ));
            }
        }

        $this->variedades->insertar($codigo, $nombre, $sexo, $tipo, $rasgo, $composicionNormalizada);
    }

    /**
     * Crea una variedad del catálogo para el administrador (SRS-RF032).
     * Valida cada caso negativo antes de tocar la base: código duplicado, datos inválidos y
     * combinación de factores repetida para el mismo sexo, tipo y rasgo (DAT-004).
     * Si hay errores, los devuelve como `['campo' => 'mensaje']` sin guardar nada.
     *
     * @param array<string, mixed> $datos codigo, nombre, sexo, tipo, rasgo y factores[]
     * @return array{errores: array<string, string>}
     */
    public function crearVariedad(array $datos): array
    {
        $this->impedirModificacionPorCriador();

        $codigo = trim((string) ($datos['codigo'] ?? ''));
        if ($codigo === '') {
            return ['errores' => ['codigo' => 'El código de la variedad es obligatorio.']];
        }

        if (!preg_match('/^D\.\d{3}$/', $codigo)) {
            return ['errores' => ['codigo' => "El código «{$codigo}» no corresponde a la sección D de canarios de color (D.001–D.880)."]];
        }

        if ($this->variedades->buscarPorCodigo($codigo) !== null) {
            return ['errores' => ['codigo' => "Ya existe una variedad con el código «{$codigo}» en el catálogo."]];
        }

        $validado = $this->validarDatosDeVariedad($datos);
        if ($validado['errores'] !== []) {
            return ['errores' => $validado['errores']];
        }

        $composicion = [];
        foreach ($validado['composicion'] as $factorId => $dosisExplicita) {
            $composicion[$factorId] = $dosisExplicita ?? 1;
        }

        $repetida = $this->buscarCombinacionRepetida($validado['sexo'], $validado['tipo'], $validado['rasgo'], $composicion);
        if ($repetida !== null) {
            return ['errores' => ['factores' => sprintf(
                'Ya existe una variedad (%s) con la misma combinación de factores y dosis para el mismo sexo, tipo y rasgo.',
                $repetida
            )]];
        }

        $this->variedades->enTransaccion(function () use ($codigo, $validado, $composicion): void {
            $this->cargarVariedad(
                $codigo,
                $validado['nombre'],
                $validado['sexo'],
                $validado['tipo'],
                $validado['rasgo'],
                $composicion,
            );
            \auditar('crear', 'variedad', $codigo);
        });

        return ['errores' => []];
    }

    /**
     * Corrige una variedad del catálogo y su composición para el administrador (SRS-RF032).
     * Si la variedad no existe, si los datos no son válidos, si el sexo no coincide con el de los
     * canarios que ya la tienen (RF012) o si la composición repetiría la de otra variedad del
     * mismo sexo, tipo y rasgo (DAT-004), devuelve los errores como
     * `['campo' => 'mensaje']` sin guardar nada.
     *
     * @param array<string, mixed> $datos nombre, sexo, tipo, rasgo y factores[]
     * @return array{errores: array<string, string>}
     */
    public function actualizarVariedad(string $codigo, array $datos): array
    {
        $this->impedirModificacionPorCriador();

        $codigo = trim($codigo);
        if ($this->variedades->buscarPorCodigo($codigo) === null) {
            return ['errores' => ['codigo' => "La variedad «{$codigo}» no existe en el catálogo."]];
        }

        $validado = $this->validarDatosDeVariedad($datos);
        if ($validado['errores'] !== []) {
            return ['errores' => $validado['errores']];
        }

        // Una variedad con sexo solo se asigna a un canario de ese sexo (RF012, docs/backend.md §3).
        $sexo = $validado['sexo'];
        if ($sexo !== null && $this->variedades->contarCanariosAsignados($codigo, $sexo) > 0) {
            return ['errores' => ['sexo' => 'Hay canarios registrados con esta variedad que no son '
                . ($sexo === 'macho' ? 'machos' : 'hembras') . '.']];
        }

        // Si el formulario envía dosis explícita (`factores[n][dosis]`), se aplica;
        // si envía solo ids planos, cada factor conserva su dosis actual y los nuevos entran con 1.
        $dosisActuales = [];
        foreach ($this->variedades->obtenerComposicion($codigo) as $factor) {
            $dosisActuales[$factor['id']] = $factor['dosis'];
        }

        $composicion = [];
        foreach ($validado['composicion'] as $factorId => $dosisExplicita) {
            $composicion[$factorId] = $dosisExplicita ?? ($dosisActuales[$factorId] ?? 1);
        }

        $repetida = $this->buscarCombinacionRepetida(
            $validado['sexo'],
            $validado['tipo'],
            $validado['rasgo'],
            $composicion,
            $codigo,
        );
        if ($repetida !== null) {
            return ['errores' => ['factores' => sprintf(
                'Ya existe una variedad (%s) con la misma combinación de factores y dosis para el mismo sexo, tipo y rasgo.',
                $repetida
            )]];
        }

        $this->variedades->enTransaccion(function () use ($codigo, $validado, $composicion): void {
            $this->variedades->actualizar(
                $codigo,
                $validado['nombre'],
                $validado['sexo'],
                $validado['tipo'],
                $validado['rasgo'],
                $composicion,
            );
            \auditar('actualizar', 'variedad', $codigo);
        });

        return ['errores' => []];
    }

    /**
     * Elimina una variedad del catálogo (SRS-RF032).
     * Si algún canario la tiene asignada, se informa y no se guarda nada (docs/backend.md §3).
     *
     * @return array{errores: array<string, string>}
     */
    public function eliminarVariedad(string $codigo): array
    {
        $this->impedirModificacionPorCriador();

        $codigo = trim($codigo);
        if ($this->variedades->buscarPorCodigo($codigo) === null) {
            return ['errores' => ['codigo' => "La variedad «{$codigo}» no existe en el catálogo."]];
        }

        if ($this->variedades->contarCanariosAsignados($codigo) > 0) {
            return ['errores' => ['codigo' => "La variedad «{$codigo}» está asignada a algún canario y no se puede eliminar."]];
        }

        $this->variedades->enTransaccion(function () use ($codigo): void {
            $this->variedades->eliminar($codigo);
            \auditar('eliminar', 'variedad', $codigo);
        });

        return ['errores' => []];
    }

    /**
     * Valida los datos del formulario de mantenimiento de una variedad (SRS-RF032) y devuelve
     * los errores como `['campo' => 'mensaje']`, junto con los valores ya normalizados.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     errores: array<string, string>,
     *     nombre: string,
     *     sexo: ?string,
     *     tipo: string,
     *     rasgo: ?string,
     *     factores: list<int>
     * }
     */
    private function validarDatosDeVariedad(array $datos): array
    {
        $errores = [];

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre de la variedad es obligatorio.';
        } elseif (mb_strlen($nombre) > 150) {
            $errores['nombre'] = 'El nombre de la variedad no puede superar los 150 caracteres.';
        }

        $sexo = trim((string) ($datos['sexo'] ?? ''));
        $sexo = $sexo === '' ? null : $sexo;
        if ($sexo !== null && !in_array($sexo, self::SEXOS_VALIDOS, true)) {
            $errores['sexo'] = "El sexo «{$sexo}» no es válido. Debe ser macho o hembra.";
        }

        $tipo = trim((string) ($datos['tipo'] ?? ''));
        if ($tipo === '') {
            $errores['tipo'] = 'El tipo de la variedad es obligatorio.';
        } elseif (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            $errores['tipo'] = "El tipo «{$tipo}» no es válido. Debe ser melanico o lipocromico.";
        }

        $rasgo = trim((string) ($datos['rasgo'] ?? ''));
        $rasgo = $rasgo === '' ? null : $rasgo;
        if ($rasgo !== null && !in_array($rasgo, self::RASGOS_VALIDOS, true)) {
            $errores['rasgo'] = "El rasgo «{$rasgo}» no es válido. Debe ser ala_blanca o alas_grises.";
        }

        $composicion = [];
        if (array_key_exists('factores', $datos) && !is_array($datos['factores'])) {
            $errores['factores'] = 'La lista de factores enviada no es válida.';
        } else {
            foreach ((array) ($datos['factores'] ?? []) as $entrada) {
                if ($entrada === null || $entrada === '') {
                    continue;
                }

                if (is_array($entrada)) {
                    $idCrudo = $entrada['factor_id'] ?? null;
                    if ($idCrudo === null || $idCrudo === '') {
                        continue;
                    }
                    if (!is_numeric($idCrudo) || (int) $idCrudo != $idCrudo || (int) $idCrudo <= 0) {
                        $errores['factores'] = 'La lista de factores enviada no es válida.';
                        break;
                    }

                    $dosisCruda = $entrada['dosis'] ?? 1;
                    if ($dosisCruda === '' || $dosisCruda === null) {
                        $dosisCruda = 1;
                    }
                    if (!is_numeric($dosisCruda) || (int) $dosisCruda != $dosisCruda || !in_array((int) $dosisCruda, [1, 2], true)) {
                        $errores['factores'] = 'La dosis de cada factor debe ser 1 o 2.';
                        break;
                    }

                    $factorId = (int) $idCrudo;
                    $dosis = (int) $dosisCruda;
                    if (array_key_exists($factorId, $composicion) && $composicion[$factorId] !== $dosis) {
                        $errores['factores'] = 'No se puede repetir el mismo factor con dosis distintas.';
                        break;
                    }
                    $composicion[$factorId] = $dosis;
                    continue;
                }

                if (!is_numeric($entrada) || (int) $entrada != $entrada || (int) $entrada <= 0) {
                    $errores['factores'] = 'La lista de factores enviada no es válida.';
                    break;
                }

                $factorId = (int) $entrada;
                if (!array_key_exists($factorId, $composicion)) {
                    $composicion[$factorId] = null;
                }
            }
        }

        if ($composicion !== [] && !isset($errores['factores'])) {
            $existentes = $this->factores->buscarPorIds(array_keys($composicion));
            foreach ($composicion as $factorId => $dosisExplicita) {
                if (!isset($existentes[$factorId])) {
                    $errores['factores'] = "El factor con id {$factorId} no existe en el catálogo.";
                    break;
                }
                if ($dosisExplicita === 2 && $existentes[$factorId]['tipo_herencia'] !== 'codominante') {
                    $errores['factores'] = "Solo los factores codominantes admiten dosis 2 (factor «{$existentes[$factorId]['nombre']}»).";
                    break;
                }
            }
        }

        return [
            'errores' => $errores,
            'nombre' => $nombre,
            'sexo' => $sexo,
            'tipo' => $tipo,
            'rasgo' => $rasgo,
            'composicion' => $composicion,
        ];
    }

    /**
     * Busca otra variedad con la misma combinación de factores y dosis para el mismo sexo,
     * tipo y rasgo (DAT-004, docs/backend.md §3). Devuelve su código o `null` si no la hay.
     *
     * @param array<int, int> $composicion mapa factor_id => dosis
     */
    private function buscarCombinacionRepetida(
        ?string $sexo,
        string $tipo,
        ?string $rasgo,
        array $composicion,
        ?string $excluirCodigo = null,
    ): ?string {
        $clave = self::claveCombinacion($sexo, $tipo, $rasgo, $composicion);

        foreach ($this->listarVariedadesConComposicion() as $existente) {
            if ($excluirCodigo !== null && $existente['codigo'] === $excluirCodigo) {
                continue;
            }

            $composicionExistente = [];
            foreach ($existente['factores'] as $factor) {
                $composicionExistente[$factor['id']] = $factor['dosis'];
            }

            $claveExistente = self::claveCombinacion(
                $existente['sexo'],
                $existente['tipo'],
                $existente['rasgo'],
                $composicionExistente,
            );
            if ($claveExistente === $clave) {
                return $existente['codigo'];
            }
        }

        return null;
    }

    /**
     * Genera la clave canónica de una composición para validar unicidad (DAT-004).
     *
     * @param array<int, int> $composicion mapa factor_id => dosis
     */
    public static function claveCombinacion(
        ?string $sexo,
        string $tipo,
        ?string $rasgo,
        array $composicion,
    ): string {
        ksort($composicion);
        $pares = [];
        foreach ($composicion as $factorId => $dosis) {
            $pares[] = "{$factorId}:{$dosis}";
        }

        return implode('|', [
            $sexo ?? 'NULL',
            $tipo,
            $rasgo ?? 'NULL',
            implode(',', $pares),
        ]);
    }

    /**
     * Comprueba que un factor leído del catálogo tenga un tipo de herencia válido (SRS-RF031).
     *
     * @param array{id: int, nombre: string, tipo_herencia: string} $factor
     */
    private function asegurarTipoHerenciaEnCatalogo(array $factor): void
    {
        $tipo = trim($factor['tipo_herencia']);
        if ($tipo === '' || !in_array($tipo, self::TIPOS_HERENCIA_VALIDOS, true)) {
            throw new DomainException(
                "El factor «{$factor['nombre']}» no tiene un tipo de herencia válido en el catálogo."
            );
        }
    }

    /**
     * Defensa en profundidad (SRS-RF030): el catálogo es de solo lectura para los criadores.
     * Si la sesión activa pertenece a un criador, se bloquea cualquier escritura con 403.
     */
    private function impedirModificacionPorCriador(): void
    {
        if (($_SESSION['rol'] ?? null) === 'criador') {
            throw HttpException::prohibido();
        }
    }
}
