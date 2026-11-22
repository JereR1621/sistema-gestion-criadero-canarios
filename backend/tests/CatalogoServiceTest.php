<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use DomainException;
use InvalidArgumentException;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use TuCriadero\Controllers\AdminCatalogoController;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\HttpException;
use TuCriadero\Core\Rutas;
use TuCriadero\Models\VariedadModel;
use TuCriadero\Services\CatalogoService;

/**
 * Pruebas de SRS-RF028: catálogo precargado de 880 variedades de color (D.001 a D.880),
 * su composición de factores, unicidad por sexo/tipo/rasgo y rechazo de códigos duplicados.
 *
 * Usa MySQL `tucriadero_pruebas` si está disponible; en su defecto, crea el esquema en SQLite en memoria
 * y carga los mismos seeds (`01_variedades.sql`, `02_factores.sql`, `03_composicion.sql`).
 */
final class CatalogoServiceTest extends TestCase
{
    private static ?PDO $pdoCompartido = null;

    private CatalogoService $servicio;
    private VariedadModel $variedades;

    public static function setUpBeforeClass(): void
    {
        self::$pdoCompartido = self::conectarBaseDePruebas();
    }

    public static function tearDownAfterClass(): void
    {
        Conexion::usar(null);
        self::$pdoCompartido = null;
    }

    protected function setUp(): void
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);

        Conexion::usar($pdo);
        $pdo->beginTransaction();

        $this->variedades = new VariedadModel();
        $this->servicio = new CatalogoService($this->variedades);
    }

    protected function tearDown(): void
    {
        if (self::$pdoCompartido !== null && self::$pdoCompartido->inTransaction()) {
            self::$pdoCompartido->rollBack();
        }
        Conexion::usar(null);
        unset(
            $_SESSION['criadero_id'],
            $_SESSION['rol'],
            $_SESSION['usuario_id'],
            $_SESSION['nombre'],
            $_SESSION['flash'],
        );
    }

    /**
     * Criterio positivo (SRS-RF028): el catálogo precargado contiene las 880 variedades
     * (D.001 a D.880), con líneas agrupadas separadas por código, composición cargada
     * y sin códigos ni combinaciones repetidas.
     */
    public function testCatalogoPrecargadoTieneLas880VariedadesConComposicionSinDuplicados(): void
    {
        $resumen = $this->servicio->validarCatalogoPrecargado();

        self::assertSame(880, $resumen['total_variedades']);
        self::assertSame(21, $resumen['total_factores']);
        self::assertSame(880, $resumen['variedades_con_factores'] + $resumen['variedades_forma_normal']);

        // Líneas que el nomenclador agrupa en una misma fila (D.001-D.002) existen como variedades separadas
        $blanco = $this->servicio->obtenerVariedad('D.001');
        $blancoDominante = $this->servicio->obtenerVariedad('D.002');
        self::assertNotNull($blanco);
        self::assertNotNull($blancoDominante);
        self::assertSame('BLANCO', $blanco['nombre']);
        self::assertSame('BLANCO DOMINANTE', $blancoDominante['nombre']);
        self::assertSame(['BLANCO RECESIVO'], array_column($blanco['factores'], 'nombre'));
        self::assertSame(['BLANCO DOMINANTE'], array_column($blancoDominante['factores'], 'nombre'));

        // Variedad con múltiples factores y forma normal (0 mutaciones)
        $rojoIntenso = $this->servicio->obtenerVariedad('D.049');
        self::assertNotNull($rojoIntenso);
        self::assertSame('ROJO INTENSO', $rojoIntenso['nombre']);
        self::assertSame(['ROJO', 'INTENSO'], array_column($rojoIntenso['factores'], 'nombre'));

        $amarilloNevado = $this->servicio->obtenerVariedad('D.004');
        self::assertNotNull($amarilloNevado);
        self::assertSame([], $amarilloNevado['factores']);

        // Variedad codominante con dosis 2 (JASPE DD)
        $jaspeDd = $this->servicio->obtenerVariedad('D.737');
        self::assertNotNull($jaspeDd);
        self::assertSame('NEGRO JASPE DD BLANCO', $jaspeDd['nombre']);
        $dosisPorNombre = array_column($jaspeDd['factores'], 'dosis', 'nombre');
        self::assertSame(['BLANCO RECESIVO' => 1, 'JASPE' => 2], $dosisPorNombre);

        // Última variedad del nomenclador oficial 2026
        $ultima = $this->servicio->obtenerVariedad('D.880');
        self::assertNotNull($ultima);
        self::assertSame('NEGRO PERLA ROJO MARFIL MOSAICO HEMBRA', $ultima['nombre']);
        self::assertSame('hembra', $ultima['sexo']);
    }

    /**
     * Criterio negativo (SRS-RF028): cargar dos veces un mismo código falla por la clave primaria `codigo`
     * y el catálogo conserva exactamente las 880 variedades sin duplicados.
     */
    public function testCargarDosVecesUnMismoCodigoFallaPorClavePrimariaYConserva880Variedades(): void
    {
        try {
            $this->servicio->cargarVariedad(
                'D.049',
                'ROJO INTENSO',
                null,
                'lipocromico',
                null,
                [1 => 1, 4 => 1]
            );
            self::fail('Debió fallar al intentar cargar un código duplicado (D.049).');
        } catch (PDOException $error) {
            self::assertSame('23000', (string) $error->getCode());
        }

        self::assertSame(880, $this->variedades->contar());
    }

    /**
     * Regla de integridad (DAT-004, docs/backend.md §3): dos variedades del mismo sexo, tipo y rasgo
     * no pueden tener la misma combinación de factores y dosis.
     */
    public function testRechazaCombinacionDeFactoresYDosisRepetidaParaMismoSexoTipoYRasgo(): void
    {
        try {
            // Misma combinación que D.049 (ROJO INTENSO: lipocromico, sexo NULL, rasgo NULL, factores ROJO+INTENSO)
            $this->servicio->cargarVariedad(
                'D.881',
                'ROJO INTENSO REPETIDO',
                null,
                'lipocromico',
                null,
                [1 => 1, 4 => 1]
            );
            self::fail('Debió rechazar una combinación de factores repetida para el mismo sexo, tipo y rasgo.');
        } catch (DomainException $error) {
            self::assertStringContainsString('D.049', $error->getMessage());
        }

        self::assertSame(880, $this->variedades->contar());
    }

    /**
     * Alcance (SRS-RF028): solo se admiten códigos de la sección D (canarios de color).
     */
    public function testRechazaCodigoFueraDeLaSeccionDeColor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->servicio->cargarVariedad('E.001', 'CANARIO DE PORTE', null, 'lipocromico', null, []);
    }

    /**
     * Integridad de dosis (docs/backend.md §3): solo factores codominantes aceptan dosis 2.
     */
    public function testRechazaDosis2EnFactorNoCodominante(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->servicio->cargarVariedad('D.882', 'PRUEBA DOSIS INVALIDA', null, 'lipocromico', null, [1 => 2]);
    }

    /**
     * Validación negativa: un catálogo incompleto (menos de 880 variedades) falla la validación de RF028.
     */
    public function testValidarCatalogoIncompletoLanzaExcepcion(): void
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);
        $pdo->exec("DELETE FROM variedad WHERE codigo = 'D.880'");

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('880');
        $this->servicio->validarCatalogoPrecargado();
    }

    /**
     * Validación negativa: si dos variedades del mismo sexo, tipo y rasgo comparten
     * composición y dosis en la base, `validarCatalogoPrecargado()` falla (DAT-004).
     */
    public function testValidarCatalogoDetectaCombinacionRepetida(): void
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);
        // Hacer que D.050 (ROJO NEVADO) tenga la misma composición que D.049 (ROJO INTENSO)
        $pdo->exec("INSERT INTO variedad_factor (variedad_codigo, factor_id, dosis) VALUES ('D.050', 4, 1)");

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('repite la combinación');
        $this->servicio->validarCatalogoPrecargado();
    }

    /**
     * El catálogo es global: no se filtra por `criadero_id`.
     */
    public function testElCatalogoEsGlobalYNoDependeDelCriaderoDeLaSesion(): void
    {
        $_SESSION['criadero_id'] = 1001;
        $desdeCriadero1 = $this->servicio->listarVariedades();

        $_SESSION['criadero_id'] = 1002;
        $desdeCriadero2 = $this->servicio->listarVariedades();

        self::assertCount(880, $desdeCriadero1);
        self::assertSame($desdeCriadero1, $desdeCriadero2);
    }

    /**
     * Criterio positivo (SRS-RF032): cuando el administrador agrega o corrige una variedad,
     * su composición o un factor del catálogo, el cambio queda aplicado para todos los criadores
     * (el catálogo es global y no se filtra por `criadero_id`) y deja registro de auditoría (SEG-003).
     */
    public function testElAdministradorAplicaLosCambiosDelCatalogoParaTodosLosCriadores(): void
    {
        $adminId = $this->crearUsuarioDePrueba('admin');
        $_SESSION['usuario_id'] = $adminId;
        $_SESSION['rol'] = 'admin';
        $_SESSION['criadero_id'] = 1001;

        // 1) Agrega un factor: el nombre se guarda en mayúsculas, como el resto del catálogo
        $factor = $this->servicio->crearFactor('factor de prueba rf032', 'recesivo');
        self::assertSame([], $factor['errores']);
        $factorId = (int) $factor['factor_id'];

        // 2) Agrega una variedad nueva con su composición
        $nueva = $this->servicio->crearVariedad([
            'codigo' => 'D.900',
            'nombre' => 'VARIANTE DE PRUEBA',
            'sexo' => null,
            'tipo' => 'lipocromico',
            'rasgo' => null,
            'factores' => [$factorId],
        ]);
        self::assertSame([], $nueva['errores']);

        // 3) Corrige una variedad existente: nombre, sexo y composición
        $corregida = $this->servicio->actualizarVariedad('D.050', [
            'nombre' => 'ROJO NEVADO CORREGIDO',
            'sexo' => 'hembra',
            'tipo' => 'lipocromico',
            'rasgo' => null,
            'factores' => [1, $factorId],
        ]);
        self::assertSame([], $corregida['errores']);

        // Los cambios se ven desde cualquier criadero (el catálogo es uno solo y aplica a todos)
        $_SESSION['criadero_id'] = 1002;

        $lista = $this->servicio->listarVariedadesParaAdmin(null);
        self::assertCount(881, $lista);
        self::assertSame(['codigo', 'nombre', 'sexo', 'factores'], array_keys($lista[0]));

        $porCodigo = array_column($lista, null, 'codigo');
        self::assertSame('VARIANTE DE PRUEBA', $porCodigo['D.900']['nombre']);
        self::assertSame(['FACTOR DE PRUEBA RF032'], $porCodigo['D.900']['factores']);
        self::assertSame('ROJO NEVADO CORREGIDO', $porCodigo['D.050']['nombre']);
        self::assertSame('hembra', $porCodigo['D.050']['sexo']);
        self::assertSame(['ROJO', 'FACTOR DE PRUEBA RF032'], $porCodigo['D.050']['factores']);

        // El parámetro q= filtra por código o por nombre, sin distinguir mayúsculas
        self::assertCount(1, $this->servicio->listarVariedadesParaAdmin('D.900'));
        self::assertCount(1, $this->servicio->listarVariedadesParaAdmin('rojo nevado corregido'));
        self::assertSame([], $this->servicio->listarVariedadesParaAdmin('sin coincidencias xyz'));

        // Auditoría de las tres operaciones, con el usuario de la sesión (SEG-003)
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);
        $filas = $pdo->query(
            "SELECT usuario_id, accion, entidad, entidad_id FROM auditoria
             WHERE entidad IN ('variedad', 'factor') ORDER BY id"
        )->fetchAll();

        self::assertCount(3, $filas);
        self::assertSame(
            ["crear factor {$factorId}", 'crear variedad D.900', 'actualizar variedad D.050'],
            array_map(
                static fn (array $fila): string => "{$fila['accion']} {$fila['entidad']} {$fila['entidad_id']}",
                $filas,
            ),
        );
        foreach ($filas as $fila) {
            self::assertSame($adminId, (int) $fila['usuario_id']);
        }
    }

    /**
     * Contrato §5 y §6: la vista recibe exactamente las variables y las formas que acuerdan
     * backend y frontend para el mantenimiento del catálogo (SRS-RF032).
     */
    public function testLosDatosParaLasVistasTienenLaFormaDelContrato(): void
    {
        $variedades = $this->servicio->listarVariedadesParaAdmin('D.049');
        self::assertCount(1, $variedades);
        self::assertSame(['codigo', 'nombre', 'sexo', 'factores'], array_keys($variedades[0]));
        self::assertSame('D.049', $variedades[0]['codigo']);
        self::assertSame(['ROJO', 'INTENSO'], $variedades[0]['factores']);

        $variedad = $this->servicio->obtenerVariedad('D.049');
        self::assertIsArray($variedad);
        self::assertSame(['codigo', 'nombre', 'sexo', 'tipo', 'rasgo', 'factores'], array_keys($variedad));
        self::assertSame(['id', 'nombre', 'tipo_herencia', 'dosis'], array_keys($variedad['factores'][0]));

        $factores = $this->servicio->listarFactores();
        self::assertNotEmpty($factores);
        self::assertSame(['id', 'nombre', 'tipo_herencia'], array_keys($factores[0]));
    }

    /**
     * Las cinco rutas del RF032 llevan los nombres del contrato §6, que son los que usa
     * la vista de frontend (SRS-RF032 [Frontend] #87).
     */
    public function testLasRutasDelCatalogoEstanRegistradasConLosNombresDelContrato(): void
    {
        Rutas::definir(Aplicacion::crearRouter());

        self::assertSame('/admin/variedades', ruta('admin.variedades'));
        self::assertSame('/admin/variedades/D.049', ruta('admin.variedades.editar', ['codigo' => 'D.049']));
        self::assertSame('/admin/variedades/D.049', ruta('admin.variedades.guardar', ['codigo' => 'D.049']));
        self::assertSame('/admin/factores', ruta('admin.factores'));
        self::assertSame('/admin/factores', ruta('admin.factores.guardar'));
    }

    /**
     * Criterio negativo (SRS-RF032): código de variedad duplicado; se informa sin guardar nada.
     */
    public function testRechazaCodigoDeVariedadDuplicadoSinGuardarNada(): void
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);

        $variedadesAntes = $this->variedades->contar();
        $auditoriaAntes = (int) $pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        $resultado = $this->servicio->crearVariedad([
            'codigo' => 'D.049',
            'nombre' => 'OTRO ROJO INTENSO',
            'sexo' => null,
            'tipo' => 'lipocromico',
            'rasgo' => null,
            'factores' => [1],
        ]);

        self::assertArrayHasKey('codigo', $resultado['errores']);
        self::assertStringContainsString('D.049', $resultado['errores']['codigo']);
        self::assertSame($variedadesAntes, $this->variedades->contar());
        self::assertSame($auditoriaAntes, (int) $pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
        self::assertSame('ROJO INTENSO', $this->servicio->obtenerVariedad('D.049')['nombre']);
    }

    /**
     * Criterio negativo (SRS-RF032): eliminar una variedad asignada a algún canario;
     * se informa sin guardar nada (docs/backend.md §3).
     */
    public function testRechazaEliminarUnaVariedadAsignadaAAlgunCanarioSinGuardarNada(): void
    {
        $this->crearCanarioAsignado('D.049');

        $resultado = $this->servicio->eliminarVariedad('D.049');

        self::assertArrayHasKey('codigo', $resultado['errores']);
        self::assertStringContainsString('D.049', $resultado['errores']['codigo']);
        self::assertNotNull($this->servicio->obtenerVariedad('D.049'));
        self::assertSame(880, $this->variedades->contar());

        // Una variedad sin canarios sí se elimina y la baja queda registrada (SEG-003)
        $libre = $this->servicio->eliminarVariedad('D.880');
        self::assertSame([], $libre['errores']);
        self::assertNull($this->servicio->obtenerVariedad('D.880'));

        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);
        $consulta = $pdo->query("SELECT accion, entidad_id FROM auditoria WHERE accion = 'eliminar'");
        self::assertSame(['eliminar', 'D.880'], array_values($consulta->fetch()));
    }

    /**
     * Criterio negativo (SRS-RF032): composición repetida para el mismo sexo; se informa
     * sin guardar nada, tanto al corregir una variedad como al crearla (DAT-004).
     */
    public function testRechazaComposicionRepetidaParaElMismoSexoSinGuardarNada(): void
    {
        // Corregir D.050 con la misma combinación que D.049 (ROJO + INTENSO, sexo sin indicar, lipocrómica, sin rasgo)
        $resultado = $this->servicio->actualizarVariedad('D.050', [
            'nombre' => 'ROJO INTENSO COPIA',
            'sexo' => null,
            'tipo' => 'lipocromico',
            'rasgo' => null,
            'factores' => [1, 4],
        ]);

        self::assertArrayHasKey('factores', $resultado['errores']);
        self::assertStringContainsString('D.049', $resultado['errores']['factores']);

        $variedad = $this->servicio->obtenerVariedad('D.050');
        self::assertSame('ROJO NEVADO', $variedad['nombre']);
        self::assertSame(['ROJO'], array_column($variedad['factores'], 'nombre'));

        // Crear una variedad con esa misma combinación también se rechaza
        $nueva = $this->servicio->crearVariedad([
            'codigo' => 'D.900',
            'nombre' => 'ROJO INTENSO COPIA',
            'sexo' => null,
            'tipo' => 'lipocromico',
            'rasgo' => null,
            'factores' => [1, 4],
        ]);

        self::assertArrayHasKey('factores', $nueva['errores']);
        self::assertNull($this->variedades->buscarPorCodigo('D.900'));
        self::assertSame(880, $this->variedades->contar());
    }

    /**
     * Criterio negativo (SRS-RF032): un criador que entra a estas rutas recibe 403.
     */
    public function testUnCriadorQueEntraALasRutasDelCatalogoRecibe403(): void
    {
        $_SESSION['usuario_id'] = 100;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = 1;
        $_SESSION['nombre'] = 'Criador de prueba';

        $controlador = new AdminCatalogoController();
        $rutas = [
            'admin.variedades' => static fn (): mixed => $controlador->variedades(),
            'admin.variedades.editar' => static fn (): mixed => $controlador->variedadesEditar('D.049'),
            'admin.variedades.guardar' => static fn (): mixed => $controlador->variedadesGuardar('D.049'),
            'admin.factores' => static fn (): mixed => $controlador->factores(),
            'admin.factores.guardar' => static fn (): mixed => $controlador->factoresGuardar(),
        ];

        foreach ($rutas as $nombre => $ruta) {
            try {
                $ruta();
                self::fail("Un criador debe recibir 403 en {$nombre}.");
            } catch (HttpException $error) {
                self::assertSame(403, $error->codigo());
            }
        }
    }

    /**
     * Criterio negativo (SRS-RF032): ninguna vista de administración muestra datos de un criadero.
     * Las respuestas solo traen campos del catálogo y son iguales sin importar el criadero de la sesión.
     */
    public function testLasRespuestasDeAdministracionNoIncluyenDatosDeUnCriadero(): void
    {
        $_SESSION['usuario_id'] = 42;
        $_SESSION['rol'] = 'admin';
        $_SESSION['criadero_id'] = 1001;

        $variedades = $this->servicio->listarVariedadesParaAdmin(null);
        $factores = $this->servicio->listarFactores();
        $variedad = $this->servicio->obtenerVariedad('D.049');

        $_SESSION['criadero_id'] = 1002;

        self::assertSame($variedades, $this->servicio->listarVariedadesParaAdmin(null));
        self::assertSame($factores, $this->servicio->listarFactores());
        self::assertSame($variedad, $this->servicio->obtenerVariedad('D.049'));

        foreach ($variedades as $opcion) {
            self::assertSame(['codigo', 'nombre', 'sexo', 'factores'], array_keys($opcion));
        }

        foreach ([$variedad, $factores[0]] as $registro) {
            foreach (array_keys($registro) as $clave) {
                self::assertStringNotContainsString('criadero', $clave);
                self::assertStringNotContainsString('canario', $clave);
                self::assertStringNotContainsString('jaula', $clave);
                self::assertStringNotContainsString('cruce', $clave);
            }
        }
    }

    /**
     * Criterio negativo (SRS-RF032, admin.factores.guardar): un factor repetido, sin nombre o
     * con un tipo de herencia inválido se informa como `['campo' => 'mensaje']` sin guardar nada.
     */
    public function testCrearFactorInformaLosErroresPorCampoSinGuardarNada(): void
    {
        $factoresAntes = count($this->servicio->listarFactores());

        // «MARFIL» ya existe: el nombre se compara en mayúsculas, como se guarda
        $repetido = $this->servicio->crearFactor('marfil', 'recesivo');
        self::assertArrayHasKey('nombre', $repetido['errores']);

        $sinHerencia = $this->servicio->crearFactor('factor de prueba', '');
        self::assertArrayHasKey('tipo_herencia', $sinHerencia['errores']);

        $herenciaInvalida = $this->servicio->crearFactor('factor de prueba', 'parcial');
        self::assertArrayHasKey('tipo_herencia', $herenciaInvalida['errores']);

        $sinNombre = $this->servicio->crearFactor('   ', 'recesivo');
        self::assertArrayHasKey('nombre', $sinNombre['errores']);

        self::assertSame($factoresAntes, count($this->servicio->listarFactores()));
    }

    /** Crea una cuenta inventada y devuelve su id (en MySQL `auditoria` tiene clave foránea a `usuario`). */
    private function crearUsuarioDePrueba(string $rol): int
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);

        $correo = 'rf032.' . uniqid('', true) . '@tucriadero.cl';
        $pdo->prepare(
            'INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado) VALUES (?, ?, ?, ?, ?)'
        )->execute(['Cuenta RF032', $correo, password_hash('ClaveValida2026', PASSWORD_DEFAULT), $rol, 'activa']);

        return (int) $pdo->lastInsertId();
    }

    /** Crea un canario de un criadero inventado que tiene la variedad indicada asignada (RF032). */
    private function crearCanarioAsignado(string $codigo): void
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);

        $usuarioId = $this->crearUsuarioDePrueba('criador');
        $pdo->prepare('INSERT INTO criadero (usuario_id) VALUES (?)')->execute([$usuarioId]);
        $criaderoId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club,
                                  fecha_anillado, sexo, variedad_codigo, estado)
             VALUES (?, 'S-032', 2026, 1, 'Club RF032', '2026-11-22', 'macho', ?, 'activo')"
        )->execute([$criaderoId, $codigo]);
    }

    private static function conectarBaseDePruebas(): PDO
    {
        $archivoEnv = dirname(__DIR__, 2) . '/.env';
        if (is_file($archivoEnv)) {
            $env = self::leerEnvSinContaminar($archivoEnv);
            $host = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? null);
            $puerto = getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '3306');
            $base = getenv('DB_NAME_PRUEBAS') ?: ($env['DB_NAME_PRUEBAS'] ?? null);
            $usuario = getenv('DB_USER') ?: ($env['DB_USER'] ?? null);
            $contrasena = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : ($env['DB_PASS'] ?? '');

            if ($host && $base && $usuario) {
                try {
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $puerto, $base);
                    $pdo = new PDO($dsn, $usuario, $contrasena, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    if ((int) $pdo->query('SELECT COUNT(*) FROM variedad')->fetchColumn() === 880) {
                        return $pdo;
                    }
                } catch (PDOException) {
                    // Si MySQL de pruebas no está levantado, se usa SQLite en memoria con los mismos seeds.
                }
            }
        }

        return self::crearSqliteConSeeds();
    }

    /** @return array<string, string> */
    private static function leerEnvSinContaminar(string $ruta): array
    {
        $valores = [];
        foreach ((array) file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim((string) $linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            $valores[trim($clave)] = trim($valor, " \t\n\r\0\x0B\"'");
        }

        return $valores;
    }

    private static function crearSqliteConSeeds(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(
            'CREATE TABLE variedad (
                codigo CHAR(5) PRIMARY KEY,
                nombre VARCHAR(150) NOT NULL,
                sexo TEXT NULL CHECK (sexo IN (\'macho\', \'hembra\') OR sexo IS NULL),
                tipo TEXT NOT NULL CHECK (tipo IN (\'melanico\', \'lipocromico\')),
                rasgo TEXT NULL CHECK (rasgo IN (\'ala_blanca\', \'alas_grises\') OR rasgo IS NULL)
            )'
        );
        $pdo->exec(
            'CREATE TABLE factor (
                id INTEGER PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE,
                tipo_herencia TEXT NOT NULL CHECK (tipo_herencia IN (\'ligado_sexo\', \'recesivo\', \'dominante\', \'codominante\'))
            )'
        );
        $pdo->exec(
            'CREATE TABLE variedad_factor (
                variedad_codigo CHAR(5) NOT NULL,
                factor_id INTEGER NOT NULL,
                dosis INTEGER NOT NULL DEFAULT 1 CHECK (dosis IN (1, 2)),
                PRIMARY KEY (variedad_codigo, factor_id),
                FOREIGN KEY (variedad_codigo) REFERENCES variedad (codigo) ON DELETE CASCADE,
                FOREIGN KEY (factor_id) REFERENCES factor (id) ON DELETE CASCADE
            )'
        );
        $pdo->exec(
            'CREATE TABLE auditoria (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NULL,
                accion VARCHAR(50) NOT NULL,
                entidad VARCHAR(50) NOT NULL,
                entidad_id VARCHAR(50) NOT NULL,
                fecha_hora TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
        // Tablas de cuentas y de canarios que la prueba de RF032 necesita para comprobar
        // que no se elimina una variedad asignada (en MySQL ya existen, con sus claves foráneas).
        $pdo->exec(
            'CREATE TABLE usuario (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100) NOT NULL,
                correo VARCHAR(150) NOT NULL UNIQUE,
                contrasena_hash VARCHAR(255) NOT NULL,
                rol TEXT NOT NULL,
                estado TEXT NOT NULL
            )'
        );
        $pdo->exec(
            'CREATE TABLE criadero (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL UNIQUE
            )'
        );
        $pdo->exec(
            'CREATE TABLE canario (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                criadero_id INTEGER NOT NULL,
                anillo_socio VARCHAR(50) NOT NULL,
                anillo_anio INTEGER NOT NULL,
                anillo_correlativo INTEGER NOT NULL,
                anillo_club VARCHAR(50) NOT NULL,
                fecha_anillado TEXT NULL,
                sexo TEXT NOT NULL,
                variedad_codigo CHAR(5) NULL,
                estado TEXT NOT NULL DEFAULT \'activo\'
            )'
        );

        $seeds = dirname(__DIR__) . '/database/seeds/';
        foreach (['01_variedades.sql', '02_factores.sql', '03_composicion.sql'] as $archivo) {
            $sql = str_replace('INSERT IGNORE INTO', 'INSERT OR IGNORE INTO', (string) file_get_contents($seeds . $archivo));
            $pdo->exec($sql);
        }

        return $pdo;
    }
}
