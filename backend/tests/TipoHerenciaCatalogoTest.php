<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use DomainException;
use InvalidArgumentException;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Conexion;
use TuCriadero\Models\FactorModel;
use TuCriadero\Services\CatalogoService;

/**
 * Pruebas de SRS-RF031: el tipo de herencia de cada factor genético se obtiene
 * del catálogo precargado de factores (ligado al sexo, recesivo, dominante o codominante);
 * el criador no lo indica y un factor sin tipo de herencia se rechaza sin guardar nada.
 */
final class TipoHerenciaCatalogoTest extends TestCase
{
    private static ?PDO $pdoCompartido = null;

    private CatalogoService $servicio;
    private FactorModel $factores;

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

        $this->factores = new FactorModel();
        $this->servicio = new CatalogoService(factores: $this->factores);
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
     * Criterio positivo (SRS-RF031): al usar factores en la ficha de un canario o en un cruce,
     * el tipo de herencia se obtiene del catálogo precargado sin que el criador lo indique.
     */
    public function testTomaElTipoDeHerenciaDelCatalogoPrecargado(): void
    {
        // Ejemplos representativos de cada tipo de herencia del catálogo precargado
        self::assertSame('ligado_sexo', $this->servicio->obtenerTipoHerencia(6));   // MARFIL
        self::assertSame('recesivo', $this->servicio->obtenerTipoHerencia(9));      // ÓPALO
        self::assertSame('dominante', $this->servicio->obtenerTipoHerencia(4));     // INTENSO
        self::assertSame('codominante', $this->servicio->obtenerTipoHerencia(18));  // JASPE

        // Resolución conjunta para ficha de canario o cruce: solo se entregan IDs, nunca el tipo de herencia
        $resueltos = $this->servicio->resolverFactoresParaUso([6, 9, 4, 18]);
        self::assertSame([
            6 => ['id' => 6, 'nombre' => 'MARFIL', 'tipo_herencia' => 'ligado_sexo'],
            9 => ['id' => 9, 'nombre' => 'ÓPALO', 'tipo_herencia' => 'recesivo'],
            4 => ['id' => 4, 'nombre' => 'INTENSO', 'tipo_herencia' => 'dominante'],
            18 => ['id' => 18, 'nombre' => 'JASPE', 'tipo_herencia' => 'codominante'],
        ], $resueltos);

        // La composición de una variedad entrega los factores con su tipo de herencia desde el catálogo
        $composicion = $this->servicio->obtenerComposicion('D.265'); // BRUNO OPALO AMARILLO MOSAICO MACHO
        $herenciaPorNombre = array_column($composicion, 'tipo_herencia', 'nombre');
        self::assertSame([
            'MOSAICO' => 'recesivo',
            'BRUNO' => 'ligado_sexo',
            'ÓPALO' => 'recesivo',
        ], $herenciaPorNombre);

        // Reglas derivadas del catálogo: portabilidad y ligado al sexo
        self::assertTrue($this->servicio->esFactorPortable(6));       // MARFIL (ligado_sexo)
        self::assertTrue($this->servicio->esFactorPortable(9));       // ÓPALO (recesivo)
        self::assertFalse($this->servicio->esFactorPortable(4));      // INTENSO (dominante)
        self::assertFalse($this->servicio->esFactorPortable(18));     // JASPE (codominante)
        self::assertTrue($this->servicio->esFactorLigadoAlSexo(6));
        self::assertFalse($this->servicio->esFactorLigadoAlSexo(9));

        $portables = $this->servicio->listarFactoresPortables();
        self::assertNotEmpty($portables);
        foreach ($portables as $factor) {
            self::assertContains($factor['tipo_herencia'], CatalogoService::TIPOS_HERENCIA_PORTABLES);
        }
    }

    /**
     * Criterio negativo (SRS-RF031): factor sin tipo de herencia (null o vacío);
     * se informa el error sin guardar nada en `factor` ni en `auditoria`.
     */
    public function testRechazaFactorSinTipoDeHerenciaSinGuardarNada(): void
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);

        $factoresAntes = $this->factores->contar();
        $auditoriaAntes = (int) $pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        foreach ([null, '', '   '] as $herenciaVacia) {
            try {
                $this->servicio->cargarFactor('FACTOR SIN HERENCIA', $herenciaVacia);
                self::fail('Debió rechazar un factor sin tipo de herencia.');
            } catch (InvalidArgumentException $error) {
                self::assertStringContainsString('tipo de herencia', $error->getMessage());
            }
        }

        self::assertSame($factoresAntes, $this->factores->contar());
        self::assertSame($auditoriaAntes, (int) $pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
    }

    /**
     * Criterio negativo (SRS-RF031): un tipo de herencia desconocido también se rechaza sin guardar nada.
     */
    public function testRechazaTipoDeHerenciaDesconocidoSinGuardarNada(): void
    {
        $factoresAntes = $this->factores->contar();

        try {
            $this->servicio->cargarFactor('FACTOR HERENCIA INVALIDA', 'parcial');
            self::fail('Debió rechazar un tipo de herencia que no pertenece al catálogo.');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('no es válido', $error->getMessage());
        }

        self::assertSame($factoresAntes, $this->factores->contar());
    }

    /**
     * Criterio negativo (SRS-RF031): al usar un factor cuyo registro en el catálogo no tiene
     * tipo de herencia, el servicio informa el problema en lugar de devolver un valor vacío.
     */
    public function testUsarUnFactorSinTipoDeHerenciaEnCatalogoInformaError(): void
    {
        // SQLite en memoria sin CHECK para simular un registro corrupto sin tipo_herencia
        $sqlite = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $sqlite->exec('CREATE TABLE factor (id INTEGER PRIMARY KEY, nombre TEXT NOT NULL, tipo_herencia TEXT NULL)');
        $sqlite->exec("INSERT INTO factor (id, nombre, tipo_herencia) VALUES (99, 'SIN REGLA', '')");
        Conexion::usar($sqlite);

        $servicioAislado = new CatalogoService();

        try {
            $servicioAislado->obtenerTipoHerencia(99);
            self::fail('Debió fallar al intentar leer un factor sin tipo de herencia.');
        } catch (DomainException $error) {
            self::assertStringContainsString('SIN REGLA', $error->getMessage());
            self::assertStringContainsString('tipo de herencia', $error->getMessage());
        }

        $this->expectException(DomainException::class);
        $servicioAislado->resolverFactoresParaUso([99]);
    }

    /**
     * Criterio negativo: solicitar el tipo de herencia de un factor inexistente falla claramente.
     */
    public function testFactorInexistenteLanzaExcepcion(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('no existe en el catálogo');
        $this->servicio->obtenerTipoHerencia(9999);
    }

    /**
     * Cuando se crea un factor válido, se guarda y deja registro de auditoría (SEG-003).
     */
    public function testCargarFactorValidoGuardaYRegistraAuditoria(): void
    {
        $pdo = self::$pdoCompartido;
        self::assertInstanceOf(PDO::class, $pdo);

        $id = $this->servicio->cargarFactor('FACTOR DE PRUEBA RF031', 'recesivo');

        self::assertSame('recesivo', $this->servicio->obtenerTipoHerencia($id));
        $consulta = $pdo->prepare("SELECT accion, entidad, entidad_id FROM auditoria WHERE entidad = 'factor' AND entidad_id = ?");
        $consulta->execute([$id]);
        $audit = $consulta->fetch();

        self::assertIsArray($audit);
        self::assertSame('crear', $audit['accion']);
        self::assertSame('factor', $audit['entidad']);
        self::assertSame($id, (int) $audit['entidad_id']);
    }

    /**
     * El catálogo de factores es global: no se filtra por `criadero_id`.
     */
    public function testElCatalogoDeFactoresEsGlobal(): void
    {
        $_SESSION['criadero_id'] = 1001;
        $factores1 = $this->servicio->listarFactores();

        $_SESSION['criadero_id'] = 1002;
        $factores2 = $this->servicio->listarFactores();

        self::assertCount(21, $factores1);
        self::assertSame($factores1, $factores2);
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
                    if ((int) $pdo->query('SELECT COUNT(*) FROM factor')->fetchColumn() === 21) {
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
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
                entidad_id INTEGER NOT NULL,
                fecha_hora TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
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
