<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use DomainException;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
use TuCriadero\Services\Genetica\CalculoDominanteService;
use TuCriadero\Services\Genetica\HerenciaCodominante;
use TuCriadero\Services\Genetica\HerenciaDominante;

/**
 * Pruebas de SRS-RF038: cálculo mendeliano de factores dominantes (una dosis según SUP-004,
 * ej. intenso/nevado y blanco dominante) y codominantes por dosis (jaspe SD = 1, DD = 2;
 * decisión del PM 21/11/2026).
 */
final class HerenciaDominanteTest extends TestCase
{
    private ?PDO $pdo = null;

    protected function tearDown(): void
    {
        if ($this->pdo !== null && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        Conexion::usar(null);
        $this->pdo = null;
    }

    // -------------------------------------------------------------------------
    // Herencia dominante (SUP-004: una sola dosis cuando se manifiesta)
    // -------------------------------------------------------------------------

    public function testDominanteIntensoPorNevadoDa50PorcientoDeCadaUnoEnAmbosSexos(): void
    {
        $regla = new HerenciaDominante();
        self::assertSame('dominante', $regla->tipoHerencia());

        $esperado = [
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 50.0],
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 50.0],
        ];

        $intensoPorNevado = $regla->calcularDesdeProgenitores(true, false);
        self::assertSame($esperado, $intensoPorNevado['machos']);
        self::assertSame($esperado, $intensoPorNevado['hembras']);

        $nevadoPorIntenso = $regla->calcularDesdeProgenitores(false, true);
        self::assertSame($esperado, $nevadoPorIntenso['machos']);
        self::assertSame($esperado, $nevadoPorIntenso['hembras']);
    }

    public function testDominanteIntensoPorIntensoDa75PorcientoIntensoY25PorcientoNevado(): void
    {
        $regla = new HerenciaDominante();

        $resultado = $regla->calcularDesdeProgenitores(true, true);
        $esperado = [
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 25.0],
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 75.0],
        ];

        self::assertSame($esperado, $resultado['machos']);
        self::assertSame($esperado, $resultado['hembras']);
    }

    public function testDominanteNevadoPorNevadoDa100PorcientoNevado(): void
    {
        $regla = new HerenciaDominante();

        $resultado = $regla->calcularDesdeProgenitores(false, false);
        $esperado = [
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 100.0],
        ];

        self::assertSame($esperado, $resultado['machos']);
        self::assertSame($esperado, $resultado['hembras']);
    }

    public function testDominanteRechazaPortadoresODosisMayorAUno(): void
    {
        $regla = new HerenciaDominante();

        try {
            $regla->calcular(0, true, 0, false);
            self::fail('Un factor dominante no admite portadores.');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('portadores', $error->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $regla->calcular(2, false, 0, false);
    }

    // -------------------------------------------------------------------------
    // Herencia codominante (jaspe: 0 = normal, 1 = SD, 2 = DD)
    // -------------------------------------------------------------------------

    public function testCodominanteDdPorSinJaspeDa100PorcientoSd(): void
    {
        $regla = new HerenciaCodominante();
        self::assertSame('codominante', $regla->tipoHerencia());

        $esperado = [
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 100.0],
        ];

        $ddPorNormal = $regla->calcularPorDosis(2, 0);
        self::assertSame($esperado, $ddPorNormal['machos']);
        self::assertSame($esperado, $ddPorNormal['hembras']);

        $normalPorDd = $regla->calcularPorDosis(0, 2);
        self::assertSame($esperado, $normalPorDd['machos']);
        self::assertSame($esperado, $normalPorDd['hembras']);
    }

    public function testCodominanteSdPorSdDa25SinJaspe50SdY25Dd(): void
    {
        $regla = new HerenciaCodominante();

        $resultado = $regla->calcularPorDosis(1, 1);
        $esperado = [
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 25.0],
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 50.0],
            ['manifiesta' => true, 'dosis' => 2, 'portador' => false, 'porcentaje' => 25.0],
        ];

        self::assertSame($esperado, $resultado['machos']);
        self::assertSame($esperado, $resultado['hembras']);
    }

    public function testCodominanteOtrasCombinacionesDeDosis(): void
    {
        $regla = new HerenciaCodominante();

        // SD × normal = 50% normal, 50% SD
        self::assertSame([
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 50.0],
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 50.0],
        ], $regla->calcularPorDosis(1, 0)['machos']);

        // DD × SD = 50% SD, 50% DD
        self::assertSame([
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 50.0],
            ['manifiesta' => true, 'dosis' => 2, 'portador' => false, 'porcentaje' => 50.0],
        ], $regla->calcularPorDosis(2, 1)['machos']);

        // DD × DD = 100% DD
        self::assertSame([
            ['manifiesta' => true, 'dosis' => 2, 'portador' => false, 'porcentaje' => 100.0],
        ], $regla->calcularPorDosis(2, 2)['machos']);

        // normal × normal = 100% normal
        self::assertSame([
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 100.0],
        ], $regla->calcularPorDosis(0, 0)['machos']);
    }

    public function testCodominanteRechazaPortadoresODosisFueraDeRango(): void
    {
        $regla = new HerenciaCodominante();

        try {
            $regla->calcular(0, false, 0, true);
            self::fail('Un factor codominante no admite portadores.');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('portadores', $error->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $regla->calcularPorDosis(3, 0);
    }

    // -------------------------------------------------------------------------
    // Integración con el catálogo (RF031 + RF033 + RF003) en MySQL de pruebas
    // -------------------------------------------------------------------------

    public function testCalculoConCatalogoParaIntensoNevadoYJaspePorVariedades(): void
    {
        $this->conectarBaseDePruebas();
        $servicio = new CalculoDominanteService();

        // Factor 4 = INTENSO (dominante): D.049 ROJO INTENSO × D.050 ROJO NEVADO → 50% nevado, 50% intenso
        $intenso = $servicio->calcularPorVariedades(4, 'D.049', 'D.050');
        self::assertSame('INTENSO', $intenso['factor']['nombre']);
        self::assertSame('dominante', $intenso['factor']['tipo_herencia']);
        self::assertSame([
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 50.0],
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 50.0],
        ], $intenso['machos']);

        // Factor 18 = JASPE (codominante): D.737 NEGRO JASPE DD BLANCO × D.097 NEGRO BLANCO → 100% SD
        $ddPorNormal = $servicio->calcularPorVariedades(18, 'D.737', 'D.097');
        self::assertSame('JASPE', $ddPorNormal['factor']['nombre']);
        self::assertSame('codominante', $ddPorNormal['factor']['tipo_herencia']);
        self::assertSame([
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 100.0],
        ], $ddPorNormal['machos']);
        self::assertSame($ddPorNormal['machos'], $ddPorNormal['hembras']);

        // Factor 18 = JASPE (codominante): D.665 NEGRO JASPE SD BLANCO × D.665 → 25% sin jaspe, 50% SD, 25% DD
        $sdPorSd = $servicio->calcularPorVariedades(18, 'D.665', 'D.665');
        self::assertSame([
            ['manifiesta' => false, 'dosis' => 0, 'portador' => false, 'porcentaje' => 25.0],
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 50.0],
            ['manifiesta' => true, 'dosis' => 2, 'portador' => false, 'porcentaje' => 25.0],
        ], $sdPorSd['machos']);
    }

    public function testCalculoPorCanariosRespetaAislamientoDeCriaderoYRechazaFactorNoDominante(): void
    {
        $pdo = $this->conectarBaseDePruebas();
        $servicio = new CalculoDominanteService();

        $criadero1 = $this->crearCriadero($pdo, 'rf038.c1@tucriadero.cl');
        $criadero2 = $this->crearCriadero($pdo, 'rf038.c2@tucriadero.cl');

        $machoDd = $this->insertarCanario($pdo, $criadero1, 1, 'macho', 'D.737'); // NEGRO JASPE DD BLANCO
        $hembraNormal = $this->insertarCanario($pdo, $criadero1, 2, 'hembra', 'D.097'); // NEGRO BLANCO
        $hembraAjena = $this->insertarCanario($pdo, $criadero2, 1, 'hembra', 'D.097');

        // Cruce dentro del propio criadero funciona sin guardar nada
        $resultado = $servicio->calcularPorCanarios(18, $machoDd, $hembraNormal, $criadero1);
        self::assertSame([
            ['manifiesta' => true, 'dosis' => 1, 'portador' => false, 'porcentaje' => 100.0],
        ], $resultado['hembras']);

        // Factor recesivo (9 = ÓPALO) rechazado por esta regla
        try {
            $servicio->calcularPorCanarios(9, $machoDd, $hembraNormal, $criadero1);
            self::fail('Un factor recesivo no debe calcularse con la regla dominante/codominante.');
        } catch (DomainException $error) {
            self::assertStringContainsString('recesivo', $error->getMessage());
        }

        // Progenitor de otro criadero responde 404 (RF003)
        try {
            $servicio->calcularPorCanarios(18, $machoDd, $hembraAjena, $criadero1);
            self::fail('Un canario de otro criadero debe responder 404.');
        } catch (HttpException $error) {
            self::assertSame(404, $error->codigo());
        }
    }

    private function conectarBaseDePruebas(): PDO
    {
        try {
            Entorno::cargar(dirname(__DIR__, 2) . '/.env');
            $anterior = $_ENV['DB_NAME'] ?? null;
            $_ENV['DB_NAME'] = Entorno::obtener('DB_NAME_PRUEBAS') ?? '';

            try {
                $pdo = Conexion::crearDesdeEntorno();
            } finally {
                if ($anterior === null) {
                    unset($_ENV['DB_NAME']);
                } else {
                    $_ENV['DB_NAME'] = $anterior;
                }
            }

            $pdo->query('SELECT 1 FROM factor LIMIT 1');
            $this->pdo = $pdo;
            Conexion::usar($pdo);
            $pdo->beginTransaction();

            return $pdo;
        } catch (Throwable) {
            self::markTestSkipped('Sin base de pruebas: levanta MySQL y corre php backend/database/migrar.php --seeds --pruebas.');
        }
    }

    private function crearCriadero(PDO $pdo, string $correo): int
    {
        $pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento)
             VALUES ('Criador RF038', ?, ?, 'criador', 'activa', CURRENT_TIMESTAMP)"
        )->execute([$correo, password_hash('ClaveValida2026', PASSWORD_DEFAULT)]);
        $usuarioId = (int) $pdo->lastInsertId();

        $pdo->prepare('INSERT INTO criadero (usuario_id) VALUES (?)')->execute([$usuarioId]);

        return (int) $pdo->lastInsertId();
    }

    private function insertarCanario(PDO $pdo, int $criaderoId, int $correlativo, string $sexo, ?string $variedadCodigo): int
    {
        $pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, fecha_anillado, sexo, variedad_codigo, estado)
             VALUES (?, 'S-038', 2026, ?, 'Club RF038', '2026-10-10', ?, ?, 'activo')"
        )->execute([$criaderoId, $correlativo, $sexo, $variedadCodigo]);

        return (int) $pdo->lastInsertId();
    }
}
