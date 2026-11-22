<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use DomainException;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
use TuCriadero\Services\Genetica\FactoresManifestadosService;

/**
 * Pruebas de SRS-RF033: obtención de los factores manifestados por un canario
 * a partir de la composición de su variedad en el catálogo, sin pedírselos al criador
 * y con aislamiento por `criadero_id` de la sesión (RF003).
 */
final class FactoresManifestadosServiceTest extends TestCase
{
    private PDO $pdo;
    private FactoresManifestadosService $servicio;

    protected function setUp(): void
    {
        try {
            $this->pdo = self::baseDePruebas();
            $this->pdo->query('SELECT 1 FROM variedad LIMIT 1');
        } catch (Throwable) {
            self::markTestSkipped('Sin base de pruebas: levanta MySQL y corre php backend/database/migrar.php --seeds --pruebas.');
        }

        Conexion::usar($this->pdo);
        $this->pdo->beginTransaction();

        $this->servicio = new FactoresManifestadosService();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        Conexion::usar(null);
    }

    /**
     * Criterio positivo (SRS-RF033): al asignar una variedad a un canario, el sistema obtiene
     * sus factores manifestados desde la composición del catálogo (con tipo de herencia y dosis),
     * sin pedírselos al criador.
     */
    public function testObtieneFactoresManifestadosDesdeComposicionDeLaVariedad(): void
    {
        $criaderoId = $this->crearCriaderoDePrueba('rf033.criador@tucriadero.cl');

        // D.265: BRUNO OPALO AMARILLO MOSAICO MACHO → MOSAICO (recesivo), BRUNO (ligado_sexo), ÓPALO (recesivo)
        $canarioId = $this->insertarCanario($criaderoId, 1, 'macho', 'D.265');

        $completos = $this->servicio->obtenerPorCanarioId($canarioId, $criaderoId);
        self::assertSame([
            ['id' => 5, 'nombre' => 'MOSAICO', 'tipo_herencia' => 'recesivo', 'dosis' => 1],
            ['id' => 7, 'nombre' => 'BRUNO', 'tipo_herencia' => 'ligado_sexo', 'dosis' => 1],
            ['id' => 9, 'nombre' => 'ÓPALO', 'tipo_herencia' => 'recesivo', 'dosis' => 1],
        ], $completos);

        // Formato del contrato CanarioFicha.factores_manifestados (docs/contrato-back-front.md §5)
        $paraFicha = $this->servicio->paraFichaPorCanarioId($canarioId, $criaderoId);
        self::assertSame([
            ['nombre' => 'MOSAICO', 'tipo_herencia' => 'recesivo', 'dosis' => 1],
            ['nombre' => 'BRUNO', 'tipo_herencia' => 'ligado_sexo', 'dosis' => 1],
            ['nombre' => 'ÓPALO', 'tipo_herencia' => 'recesivo', 'dosis' => 1],
        ], $paraFicha);

        // Variedad codominante con dosis 2: D.737 NEGRO JASPE DD BLANCO
        $jaspeDdId = $this->insertarCanario($criaderoId, 2, 'macho', 'D.737');
        $factoresJaspe = $this->servicio->obtenerPorCanarioId($jaspeDdId, $criaderoId);
        self::assertSame([
            ['id' => 2, 'nombre' => 'BLANCO RECESIVO', 'tipo_herencia' => 'recesivo', 'dosis' => 1],
            ['id' => 18, 'nombre' => 'JASPE', 'tipo_herencia' => 'codominante', 'dosis' => 2],
        ], $factoresJaspe);
    }

    /**
     * RF033: nunca toma factores enviados por el criador; solo usa `variedad_codigo` contra el catálogo.
     */
    public function testIgnoraFactoresEnviadosPorElCriadorYUsaSoloElCatalogo(): void
    {
        $factores = $this->servicio->obtenerParaCanario([
            'variedad_codigo' => 'D.049', // ROJO INTENSO
            'factores' => [['id' => 9, 'nombre' => 'ÓPALO']],
            'factores_manifestados' => [['nombre' => 'MARFIL', 'tipo_herencia' => 'ligado_sexo']],
        ]);

        self::assertSame([
            ['id' => 1, 'nombre' => 'ROJO', 'tipo_herencia' => 'recesivo', 'dosis' => 1],
            ['id' => 4, 'nombre' => 'INTENSO', 'tipo_herencia' => 'dominante', 'dosis' => 1],
        ], $factores);
    }

    /**
     * Variedad sin determinar (NULL, RF007) o variedad de forma normal (0 mutaciones, ej. D.004 / D.100)
     * devuelven lista vacía sin error.
     */
    public function testVariedadSinDeterminarOFormaNormalDevuelvenListaVacia(): void
    {
        $criaderoId = $this->crearCriaderoDePrueba('rf033.normal@tucriadero.cl');

        $sinVariedadId = $this->insertarCanario($criaderoId, 10, 'sin_determinar', null);
        $formaNormalId = $this->insertarCanario($criaderoId, 11, 'hembra', 'D.004'); // AMARILLO NEVADO

        self::assertSame([], $this->servicio->obtenerPorCanarioId($sinVariedadId, $criaderoId));
        self::assertSame([], $this->servicio->paraFichaPorCanarioId($sinVariedadId, $criaderoId));
        self::assertSame([], $this->servicio->obtenerPorCanarioId($formaNormalId, $criaderoId));
        self::assertSame([], $this->servicio->paraFichaPorCanarioId($formaNormalId, $criaderoId));
        self::assertSame([], $this->servicio->obtenerDesdeVariedad(''));
    }

    /**
     * DAT-001 / RF033: los factores manifestados no se guardan en el canario; al cambiar la variedad
     * del canario o la composición en el catálogo, se reflejan de inmediato.
     */
    public function testLosFactoresSeObtienenSiempreDesdeElCatalogoSinGuardarseEnElCanario(): void
    {
        $criaderoId = $this->crearCriaderoDePrueba('rf033.dinamico@tucriadero.cl');

        $canarioId = $this->insertarCanario($criaderoId, 20, 'macho', 'D.049'); // ROJO INTENSO
        self::assertSame(['ROJO', 'INTENSO'], array_column($this->servicio->paraFichaPorCanarioId($canarioId, $criaderoId), 'nombre'));

        // Al cambiar la variedad del canario a D.051 (ROJO MARFIL INTENSO), manifiesta MARFIL automáticamente
        $this->pdo->prepare('UPDATE canario SET variedad_codigo = ? WHERE id = ?')->execute(['D.051', $canarioId]);
        self::assertSame(
            ['ROJO', 'INTENSO', 'MARFIL'],
            array_column($this->servicio->paraFichaPorCanarioId($canarioId, $criaderoId), 'nombre')
        );
    }

    /**
     * Negativo: una variedad que no existe en el catálogo lanza DomainException.
     */
    public function testVariedadInexistenteLanzaExcepcion(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('D.999');
        $this->servicio->obtenerDesdeVariedad('D.999');
    }

    /**
     * Negativo (RF003 — aislamiento entre criaderos): pedir los factores de un canario de otro criadero
     * responde 404, igual que si el canario no existiera.
     */
    public function testCanarioDeOtroCriaderoOInexistenteResponde404(): void
    {
        $criadero1 = $this->crearCriaderoDePrueba('rf033.c1@tucriadero.cl');
        $criadero2 = $this->crearCriaderoDePrueba('rf033.c2@tucriadero.cl');
        $canarioAjeno = $this->insertarCanario($criadero2, 1, 'macho', 'D.049');


        try {
            $this->servicio->obtenerPorCanarioId($canarioAjeno, $criadero1);
            self::fail('Un canario de otro criadero debe responder 404.');
        } catch (HttpException $error) {
            self::assertSame(404, $error->codigo());
        }

        try {
            $this->servicio->obtenerPorCanarioId(999999, $criadero1);
            self::fail('Un canario inexistente debe responder 404.');
        } catch (HttpException $error) {
            self::assertSame(404, $error->codigo());
        }
    }

    /**
     * La ficha distingue jaspe SD de DD por la dosis (backend.md §3 y §5).
     */
    public function testLaFichaDistingueJaspeSimpleDeDoble(): void
    {
        $jaspe = static fn (array $ficha): array => array_values(array_filter($ficha, static fn (array $f): bool => $f['nombre'] === 'JASPE'));

        self::assertSame([['nombre' => 'JASPE', 'tipo_herencia' => 'codominante', 'dosis' => 1]], $jaspe($this->servicio->paraFichaDesdeVariedad('D.665')));
        self::assertSame([['nombre' => 'JASPE', 'tipo_herencia' => 'codominante', 'dosis' => 2]], $jaspe($this->servicio->paraFichaDesdeVariedad('D.737')));
    }

    private function crearCriaderoDePrueba(string $correo): int
    {
        $stmtUsuario = $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento)
             VALUES ('Criador RF033', ?, ?, 'criador', 'activa', CURRENT_TIMESTAMP)"
        );
        $stmtUsuario->execute([$correo, password_hash('ClaveValida2026', PASSWORD_DEFAULT)]);
        $usuarioId = (int) $this->pdo->lastInsertId();

        $stmtCriadero = $this->pdo->prepare('INSERT INTO criadero (usuario_id) VALUES (?)');
        $stmtCriadero->execute([$usuarioId]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertarCanario(int $criaderoId, int $correlativo, string $sexo, ?string $variedadCodigo): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, fecha_anillado, sexo, variedad_codigo, estado)
             VALUES (?, 'S-033', 2026, ?, 'Club RF033', '2026-10-10', ?, ?, 'activo')"
        );
        $stmt->execute([$criaderoId, $correlativo, $sexo, $variedadCodigo]);

        return (int) $this->pdo->lastInsertId();
    }

    private static function baseDePruebas(): PDO
    {
        Entorno::cargar(dirname(__DIR__, 2) . '/.env');
        $anterior = $_ENV['DB_NAME'] ?? null;
        $_ENV['DB_NAME'] = Entorno::obtener('DB_NAME_PRUEBAS') ?? '';

        try {
            return Conexion::crearDesdeEntorno();
        } finally {
            if ($anterior === null) {
                unset($_ENV['DB_NAME']);
            } else {
                $_ENV['DB_NAME'] = $anterior;
            }
        }
    }
}
