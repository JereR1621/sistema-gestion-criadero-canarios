<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use DomainException;
use InvalidArgumentException;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Conexion;
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
        unset($_SESSION['criadero_id'], $_SESSION['rol'], $_SESSION['usuario_id']);
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

        $seeds = dirname(__DIR__) . '/database/seeds/';
        foreach (['01_variedades.sql', '02_factores.sql', '03_composicion.sql'] as $archivo) {
            $sql = str_replace('INSERT IGNORE INTO', 'INSERT OR IGNORE INTO', (string) file_get_contents($seeds . $archivo));
            $pdo->exec($sql);
        }

        return $pdo;
    }
}
