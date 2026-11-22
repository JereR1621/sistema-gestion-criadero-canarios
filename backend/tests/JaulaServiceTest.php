<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Controllers\JaulaController;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
use TuCriadero\Services\JaulaService;

/**
 * Pruebas de SRS-RF014: registro y listado de jaulas numeradas del criadero,
 * unicidad del número dentro del criadero, contrato `JaulaResumen`, auditoría (SEG-003)
 * y aislamiento entre criaderos (RF003).
 */
final class JaulaServiceTest extends TestCase
{
    private PDO $pdo;
    private JaulaService $servicio;
    private int $usuarioA;
    private int $criaderoA;
    private int $criaderoB;

    protected function setUp(): void
    {
        try {
            $this->pdo = self::baseDePruebas();
            $this->pdo->query('SELECT 1 FROM jaula LIMIT 1');
        } catch (Throwable) {
            self::markTestSkipped('Sin base de pruebas: levanta MySQL y corre php backend/database/migrar.php --seeds --pruebas.');
        }

        Conexion::usar($this->pdo);
        $this->pdo->beginTransaction();

        [$this->usuarioA, $this->criaderoA] = $this->crearCriadero('jaulas.a@tucriadero.cl');
        [, $this->criaderoB] = $this->crearCriadero('jaulas.b@tucriadero.cl');

        $this->servicio = new JaulaService();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        Conexion::usar(null);
        unset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['criadero_id'], $_SESSION['nombre']);
    }

    /**
     * Criterio positivo (SRS-RF014) + contrato `JaulaResumen` + auditoría (SEG-003).
     */
    public function testRegistraJaulaNuevaEntregaJaulaResumenYRegistraAuditoria(): void
    {
        $_SESSION['usuario_id'] = $this->usuarioA;

        $resultado = $this->servicio->registrar($this->criaderoA, ['numero' => '25']);

        self::assertSame([], $resultado['errores']);
        self::assertArrayHasKey('jaula_id', $resultado);
        self::assertSame(25, $resultado['numero']);

        $jaulaId = (int) $resultado['jaula_id'];
        $resumenes = $this->servicio->listarResumenes($this->criaderoA);

        self::assertCount(1, $resumenes);
        // Exactamente las claves del contrato JaulaResumen (§5): id, numero, ocupantes, cruce_activo, url
        self::assertSame(['id', 'numero', 'ocupantes', 'cruce_activo', 'url'], array_keys($resumenes[0]));
        self::assertSame([
            'id' => $jaulaId,
            'numero' => 25,
            'ocupantes' => 0,
            'cruce_activo' => null,
            'url' => "/jaulas/{$jaulaId}",
        ], $resumenes[0]);

        // Auditoría registrada (SEG-003)
        $stmt = $this->pdo->prepare(
            "SELECT usuario_id, accion, entidad, entidad_id
             FROM auditoria
             WHERE accion = 'crear' AND entidad = 'jaula' AND entidad_id = ?"
        );
        $stmt->execute([$jaulaId]);
        $audit = $stmt->fetch();

        self::assertIsArray($audit);
        self::assertSame($this->usuarioA, (int) $audit['usuario_id']);
        self::assertSame('crear', $audit['accion']);
        self::assertSame('jaula', $audit['entidad']);
        self::assertSame($jaulaId, (int) $audit['entidad_id']);
    }

    /**
     * Contrato `JaulaResumen`: incluye conteo de ocupantes actuales y `CruceResumen` si hay cruce activo.
     */
    public function testJaulaResumenIncluyeOcupantesActualesYCruceActivoDelCriadero(): void
    {
        $registro = $this->servicio->registrar($this->criaderoA, ['numero' => '12']);
        $jaulaId = (int) $registro['jaula_id'];

        $machoId = $this->crearCanario($this->criaderoA, 1, 'macho', 'D.049');
        $hembraId = $this->crearCanario($this->criaderoA, 2, 'hembra', 'D.050');
        $salidoId = $this->crearCanario($this->criaderoA, 3, 'macho', 'D.049');

        // 2 ocupantes actuales (fecha_hasta NULL) y 1 histórico cerrado (no debe contarse)
        $this->pdo->prepare("INSERT INTO ubicacion (canario_id, jaula_id, fecha_desde, fecha_hasta) VALUES (?, ?, '2026-09-01', NULL)")
            ->execute([$machoId, $jaulaId]);
        $this->pdo->prepare("INSERT INTO ubicacion (canario_id, jaula_id, fecha_desde, fecha_hasta) VALUES (?, ?, '2026-09-01', NULL)")
            ->execute([$hembraId, $jaulaId]);
        $this->pdo->prepare("INSERT INTO ubicacion (canario_id, jaula_id, fecha_desde, fecha_hasta) VALUES (?, ?, '2026-08-01', '2026-08-15')")
            ->execute([$salidoId, $jaulaId]);

        $this->pdo->prepare(
            "INSERT INTO cruce (criadero_id, macho_id, hembra_id, jaula_id, temporada, fecha_inicio, fecha_fin)
             VALUES (?, ?, ?, ?, 2026, '2026-09-01', NULL)"
        )->execute([$this->criaderoA, $machoId, $hembraId, $jaulaId]);
        $cruceId = (int) $this->pdo->lastInsertId();

        $resumenes = $this->servicio->listarResumenes($this->criaderoA);
        self::assertCount(1, $resumenes);
        self::assertSame(2, $resumenes[0]['ocupantes']);
        self::assertIsArray($resumenes[0]['cruce_activo']);
        self::assertSame($cruceId, $resumenes[0]['cruce_activo']['id']);
        self::assertSame(12, $resumenes[0]['cruce_activo']['jaula_numero']);
        self::assertTrue($resumenes[0]['cruce_activo']['activo']);
        self::assertSame($machoId, $resumenes[0]['cruce_activo']['macho']['id']);
        self::assertSame($hembraId, $resumenes[0]['cruce_activo']['hembra']['id']);
        self::assertSame(0, $resumenes[0]['cruce_activo']['pichones']);
    }

    /**
     * Criterio negativo (SRS-RF014): número de jaula ya registrado en el criadero; se informa sin guardar nada.
     */
    public function testRechazaNumeroDeJaulaDuplicadoEnElMismoCriaderoSinGuardar(): void
    {
        $primera = $this->servicio->registrar($this->criaderoA, ['numero' => '7']);
        self::assertSame([], $primera['errores']);

        $jaulasAntes = (int) $this->pdo->query("SELECT COUNT(*) FROM jaula WHERE criadero_id = {$this->criaderoA}")->fetchColumn();
        $auditoriaAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        $duplicada = $this->servicio->registrar($this->criaderoA, ['numero' => '7']);

        self::assertSame(
            'Número de jaula ya registrado en el criadero.',
            $duplicada['errores']['numero'] ?? ''
        );
        self::assertArrayNotHasKey('jaula_id', $duplicada);
        self::assertSame($jaulasAntes, (int) $this->pdo->query("SELECT COUNT(*) FROM jaula WHERE criadero_id = {$this->criaderoA}")->fetchColumn());
        self::assertSame($auditoriaAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
    }

    /**
     * Criterio negativo (RF003 — aislamiento entre criaderos): dos criaderos pueden usar el mismo número
     * de jaula, pero el listado de un criadero nunca incluye jaulas de otro criadero.
     */
    public function testAislamientoEntreCriaderosMismoNumeroYListadoExclusivo(): void
    {
        $enA = $this->servicio->registrar($this->criaderoA, [
            'numero' => '1',
            'criadero_id' => $this->criaderoB, // debe ignorarse; prevalece el criadero de la sesión
        ]);
        $enB1 = $this->servicio->registrar($this->criaderoB, ['numero' => '1']);
        $enB2 = $this->servicio->registrar($this->criaderoB, ['numero' => '99']);

        self::assertSame([], $enA['errores']);
        self::assertSame([], $enB1['errores']);
        self::assertSame([], $enB2['errores']);

        $listaA = $this->servicio->listarResumenes($this->criaderoA);
        $listaB = $this->servicio->listarResumenes($this->criaderoB);

        self::assertCount(1, $listaA);
        self::assertSame([(int) $enA['jaula_id']], array_column($listaA, 'id'));
        self::assertSame([1], array_column($listaA, 'numero'));

        self::assertCount(2, $listaB);
        self::assertSame([1, 99], array_column($listaB, 'numero'));
    }

    /**
     * Criterio negativo: número vacío, cero, negativo, decimal o texto no se guardan.
     */
    public function testRechazaNumerosDeJaulaInvalidosSinGuardar(): void
    {
        $auditoriaAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        foreach (['', '   ', '0', '-3', '1.5', 'jaula1', '9999999999999'] as $invalido) {
            $resultado = $this->servicio->registrar($this->criaderoA, ['numero' => $invalido]);
            self::assertArrayHasKey('numero', $resultado['errores'], "Debió rechazar «{$invalido}».");
            self::assertArrayNotHasKey('jaula_id', $resultado);
        }

        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM jaula WHERE criadero_id = {$this->criaderoA}")->fetchColumn());
        self::assertSame($auditoriaAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
    }

    /**
     * Control común de rol (RF003): un administrador recibe 403 al entrar a las rutas de jaulas.
     */
    public function testAdministradorRecibe403EnRutasDeJaulas(): void
    {
        $_SESSION['usuario_id'] = 999;
        $_SESSION['rol'] = 'admin';
        $_SESSION['criadero_id'] = null;
        $_SESSION['nombre'] = 'Admin';

        $controlador = new JaulaController();

        try {
            $controlador->index();
            self::fail('Un administrador debe recibir 403 en GET /jaulas.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }

        try {
            $controlador->guardar();
            self::fail('Un administrador debe recibir 403 en POST /jaulas.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }
    }

    /**
     * Contrato (§6): rutas con nombre `jaulas` y `jaulas.guardar` registradas en el enrutador.
     */
    public function testRutasDeJaulasEstanRegistradasSegunContrato(): void
    {
        $router = Aplicacion::crearRouter();

        self::assertSame('/jaulas', $router->url('jaulas'));
        self::assertSame('/jaulas', $router->url('jaulas.guardar'));
    }

    /** @return array{0: int, 1: int} [usuarioId, criaderoId] */
    private function crearCriadero(string $correo): array
    {
        $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento)
             VALUES ('Criador RF014', ?, ?, 'criador', 'activa', CURRENT_TIMESTAMP)"
        )->execute([$correo, password_hash('ClaveValida2026', PASSWORD_DEFAULT)]);
        $usuarioId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare('INSERT INTO criadero (usuario_id) VALUES (?)')->execute([$usuarioId]);
        $criaderoId = (int) $this->pdo->lastInsertId();

        return [$usuarioId, $criaderoId];
    }

    private function crearCanario(int $criaderoId, int $correlativo, string $sexo, ?string $variedadCodigo): int
    {
        $this->pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, fecha_anillado, sexo, variedad_codigo, estado)
             VALUES (?, 'S-014', 2026, ?, 'Club RF014', '2026-10-10', ?, ?, 'activo')"
        )->execute([$criaderoId, $correlativo, $sexo, $variedadCodigo]);

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
