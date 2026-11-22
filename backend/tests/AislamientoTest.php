<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use LogicException;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Controllers\AdminCuentaController;
use TuCriadero\Controllers\BaseController;
use TuCriadero\Controllers\CuentaController;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
use TuCriadero\Models\CanarioModel;
use TuCriadero\Models\CruceModel;
use TuCriadero\Models\JaulaModel;
use TuCriadero\Models\ModeloCriadero;
use TuCriadero\Services\AislamientoService;

/**
 * Pruebas de SRS-RF003: aislamiento entre criaderos y control común de roles.
 *
 * Usa dos criaderos inventados en `tucriadero_pruebas` (dentro de una transacción con rollback)
 * para comprobar que leer o modificar registros de otro criadero responde 404, que los IDs ajenos
 * enviados en formularios se rechazan sin guardar nada y que el administrador recibe 403 en rutas
 * de criador (y el criador recibe 403 en rutas de administración).
 */
final class AislamientoTest extends TestCase
{
    private PDO $pdo;
    private int $criaderoA;
    private int $criaderoB;
    private int $jaulaA;
    private int $jaulaB;
    private int $machoA;
    private int $hembraA;
    private int $machoB;
    private int $hembraB;
    private int $cruceActivoA;
    private int $cruceTerminadoA;
    private int $cruceActivoB;

    private CanarioModel $canarios;
    private JaulaModel $jaulas;
    private CruceModel $cruces;
    private AislamientoService $aislamiento;

    protected function setUp(): void
    {
        try {
            $this->pdo = self::baseDePruebas();
            $this->pdo->query('SELECT 1 FROM criadero LIMIT 1');
        } catch (Throwable) {
            self::markTestSkipped('Sin base de pruebas: levanta MySQL y corre php backend/database/migrar.php --seeds --pruebas.');
        }

        Conexion::usar($this->pdo);
        $this->pdo->beginTransaction();

        $this->criaderoA = $this->crearCriadero('aislamiento.a@tucriadero.cl');
        $this->criaderoB = $this->crearCriadero('aislamiento.b@tucriadero.cl');

        $this->jaulaA = $this->crearJaula($this->criaderoA, 10);
        $this->jaulaB = $this->crearJaula($this->criaderoB, 10);

        $this->machoA = $this->crearCanario($this->criaderoA, 1, 'macho', 'D.049');
        $this->hembraA = $this->crearCanario($this->criaderoA, 2, 'hembra', 'D.050');
        $this->machoB = $this->crearCanario($this->criaderoB, 1, 'macho', 'D.049');
        $this->hembraB = $this->crearCanario($this->criaderoB, 2, 'hembra', 'D.050');

        $this->cruceActivoA = $this->crearCruce($this->criaderoA, $this->machoA, $this->hembraA, $this->jaulaA, null);
        $this->cruceTerminadoA = $this->crearCruce($this->criaderoA, $this->machoA, $this->hembraA, $this->jaulaA, '2026-10-30');
        $this->cruceActivoB = $this->crearCruce($this->criaderoB, $this->machoB, $this->hembraB, $this->jaulaB, null);

        $this->canarios = new CanarioModel();
        $this->jaulas = new JaulaModel();
        $this->cruces = new CruceModel();
        $this->aislamiento = new AislamientoService($this->canarios, $this->jaulas, $this->cruces);
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
     * Criterio positivo (SRS-RF003): acceder a un canario, una jaula o un cruce de otro criadero
     * responde 404 sin mostrar ningún dato de ese registro; los propios sí se leen normalmente.
     */
    public function testAccederACanarioJaulaOCruceDeOtroCriaderoResponde404SinMostrarDatos(): void
    {
        // Registros propios de A son accesibles
        self::assertSame($this->machoA, $this->aislamiento->exigirCanarioPropio($this->machoA, $this->criaderoA)['id']);
        self::assertSame($this->jaulaA, $this->aislamiento->exigirJaulaPropia($this->jaulaA, $this->criaderoA)['id']);
        self::assertSame($this->cruceActivoA, $this->aislamiento->exigirCrucePropio($this->cruceActivoA, $this->criaderoA)['id']);

        // Canario de B pedido desde A → 404 sin datos
        $this->assertResponde404SinMensaje(fn () => $this->aislamiento->exigirCanarioPropio($this->machoB, $this->criaderoA));
        // Jaula de B pedida desde A → 404 sin datos
        $this->assertResponde404SinMensaje(fn () => $this->aislamiento->exigirJaulaPropia($this->jaulaB, $this->criaderoA));
        // Cruce de B pedido desde A → 404 sin datos
        $this->assertResponde404SinMensaje(fn () => $this->aislamiento->exigirCrucePropio($this->cruceActivoB, $this->criaderoA));

        // Tablas dependientes del canario (ubicacion, baja, factor_portado) tampoco devuelven datos ajenos
        $this->pdo->prepare("INSERT INTO ubicacion (canario_id, jaula_id, fecha_desde) VALUES (?, ?, '2026-09-01')")
            ->execute([$this->machoB, $this->jaulaB]);
        $this->pdo->prepare("INSERT INTO baja (canario_id, motivo, fecha) VALUES (?, 'venta', '2026-11-01')")
            ->execute([$this->hembraB]);
        $this->pdo->prepare("INSERT INTO factor_portado (canario_id, factor_id, origen) VALUES (?, 9, 'criador')")
            ->execute([$this->machoB]);

        self::assertSame([], $this->canarios->listarUbicacionesEnCriadero($this->machoB, $this->criaderoA));
        self::assertNull($this->canarios->obtenerBajaEnCriadero($this->hembraB, $this->criaderoA));
        self::assertSame([], $this->canarios->listarFactoresPortadosEnCriadero($this->machoB, $this->criaderoA));

        // El criadero B sí ve sus propios registros dependientes
        self::assertCount(1, $this->canarios->listarUbicacionesEnCriadero($this->machoB, $this->criaderoB));
        self::assertNotNull($this->canarios->obtenerBajaEnCriadero($this->hembraB, $this->criaderoB));
        self::assertCount(1, $this->canarios->listarFactoresPortadosEnCriadero($this->machoB, $this->criaderoB));
    }

    /**
     * Intentar modificar un registro de otro criadero no altera nada (0 filas) y el controlador responde 404.
     */
    public function testModificarRegistroDeOtroCriaderoNoAlteraNadaYResponde404(): void
    {
        $filasCanario = $this->canarios->actualizarVariedadEnCriadero($this->machoB, $this->criaderoA, 'D.001');
        $filasCruce = $this->cruces->cerrarEnCriadero($this->cruceActivoB, $this->criaderoA, '2026-11-15');

        self::assertSame(0, $filasCanario);
        self::assertSame(0, $filasCruce);

        // Los registros de B permanecen intactos
        self::assertSame('D.049', $this->canarios->buscarPorIdEnCriadero($this->machoB, $this->criaderoB)['variedad_codigo']);
        self::assertNull($this->cruces->buscarPorIdEnCriadero($this->cruceActivoB, $this->criaderoB)['fecha_fin']);

        // En BaseController::exigirRegistroPropio, un registro ajeno (null) lanza 404
        $controlador = $this->crearControladorDePrueba();
        $this->assertResponde404SinMensaje(
            fn () => $controlador->probarExigirRegistroPropio(
                $this->canarios->buscarPorIdEnCriadero($this->machoB, $this->criaderoA)
            )
        );
    }

    /**
     * Criterio negativo: canario o jaula de otro criadero; se informa sin guardar nada.
     */
    public function testCanarioOJaulaDeOtroCriaderoSeRechazaSinGuardar(): void
    {
        $ubicacionesAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM ubicacion')->fetchColumn();

        $errorCanarioAjeno = $this->aislamiento->validarCanarioYJaulaDelCriadero($this->machoB, $this->jaulaA, $this->criaderoA);
        self::assertArrayHasKey('canario_id', $errorCanarioAjeno);

        $errorJaulaAjena = $this->aislamiento->validarCanarioYJaulaDelCriadero($this->machoA, $this->jaulaB, $this->criaderoA);
        self::assertArrayHasKey('jaula_id', $errorJaulaAjena);

        // Propios (y jaula grande = null) pasan sin errores
        self::assertSame([], $this->aislamiento->validarCanarioYJaulaDelCriadero($this->machoA, $this->jaulaA, $this->criaderoA));
        self::assertSame([], $this->aislamiento->validarCanarioYJaulaDelCriadero($this->machoA, null, $this->criaderoA));
        self::assertSame($ubicacionesAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM ubicacion')->fetchColumn());
    }

    /**
     * Criterio negativo: cruce ya terminado o de otro criadero; se informa sin guardar nada.
     */
    public function testCruceTerminadoODeOtroCriaderoSeRechazaSinGuardar(): void
    {
        $errorAjeno = $this->aislamiento->validarCruceActivoDelCriadero($this->cruceActivoB, $this->criaderoA);
        self::assertArrayHasKey('_general', $errorAjeno);

        $errorTerminado = $this->aislamiento->validarCruceActivoDelCriadero($this->cruceTerminadoA, $this->criaderoA);
        self::assertArrayHasKey('_general', $errorTerminado);

        // El cruce activo del propio criadero es válido
        self::assertSame([], $this->aislamiento->validarCruceActivoDelCriadero($this->cruceActivoA, $this->criaderoA));
    }

    /**
     * Criterio negativo: padre o madre de otro criadero enviados en un formulario; se informa sin guardar nada.
     */
    public function testPadreOMadreDeOtroCriaderoSeRechazaSinGuardar(): void
    {
        $canariosAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM canario')->fetchColumn();

        $errorPadreAjeno = $this->aislamiento->validarPadresDelCriadero($this->machoB, $this->hembraA, $this->criaderoA);
        self::assertArrayHasKey('padre_id', $errorPadreAjeno);

        $errorMadreAjena = $this->aislamiento->validarPadresDelCriadero($this->machoA, $this->hembraB, $this->criaderoA);
        self::assertArrayHasKey('madre_id', $errorMadreAjena);

        $errorAmbosAjenos = $this->aislamiento->validarPadresDelCriadero($this->machoB, $this->hembraB, $this->criaderoA);
        self::assertSame(['padre_id', 'madre_id'], array_keys($errorAmbosAjenos));

        // Padres propios o sin padres (fundador/comprado) pasan
        self::assertSame([], $this->aislamiento->validarPadresDelCriadero($this->machoA, $this->hembraA, $this->criaderoA));
        self::assertSame([], $this->aislamiento->validarPadresDelCriadero(null, null, $this->criaderoA));
        self::assertSame($canariosAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM canario')->fetchColumn());
    }

    /**
     * Criterio negativo: canario de otro criadero o ya dado de baja; se informa sin guardar nada.
     */
    public function testCanarioDeOtroCriaderoOYaDadoDeBajaSeRechazaSinGuardar(): void
    {
        $bajasAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM baja')->fetchColumn();

        $errorAjeno = $this->aislamiento->validarCanarioParaBajaEnCriadero($this->machoB, $this->criaderoA);
        self::assertArrayHasKey('_general', $errorAjeno);

        $this->pdo->prepare("UPDATE canario SET estado = 'baja' WHERE id = ?")->execute([$this->hembraA]);
        $errorYaBaja = $this->aislamiento->validarCanarioParaBajaEnCriadero($this->hembraA, $this->criaderoA);
        self::assertArrayHasKey('_general', $errorYaBaja);

        // Canario activo propio sí puede darse de baja
        self::assertSame([], $this->aislamiento->validarCanarioParaBajaEnCriadero($this->machoA, $this->criaderoA));
        self::assertSame($bajasAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM baja')->fetchColumn());
    }

    /**
     * Criterio negativo: el administrador que entra a una ruta de criador recibe 403 y no ve datos
     * de ningún criadero; y el criador que entra a una ruta de administración recibe 403.
     * Además, el `criadero_id` sale siempre de la sesión (nunca de $_POST ni $_GET).
     */
    public function testControlComunDeRolYOrigenDeCriaderoIdEnBaseController(): void
    {
        $controlador = $this->crearControladorDePrueba();

        // 1. Administrador en ruta de criador (BaseController y CuentaController::perfil) → 403
        $_SESSION['usuario_id'] = 999;
        $_SESSION['rol'] = 'admin';
        $_SESSION['criadero_id'] = null;
        $_SESSION['nombre'] = 'Admin';

        try {
            $controlador->probarCriaderoIdDeSesion();
            self::fail('Un administrador no puede obtener criadero_id ni entrar a rutas de criador.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }

        try {
            (new CuentaController())->perfil();
            self::fail('Un administrador que entra a GET /perfil debe recibir 403.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }

        // 2. Criador en ruta de administración (BaseController y AdminCuentaController::estado) → 403
        $_SESSION['usuario_id'] = 100;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = $this->criaderoA;
        $_SESSION['nombre'] = 'Criador A';
        $_POST['criadero_id'] = (string) $this->criaderoB;
        $_GET['criadero_id'] = (string) $this->criaderoB;

        try {
            $controlador->probarExigirAdmin();
            self::fail('Un criador que entra a una ruta de administración debe recibir 403.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }

        try {
            (new AdminCuentaController())->estado('1');
            self::fail('Un criador que entra a POST /admin/cuentas/{id}/estado debe recibir 403.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }

        // 3. El criadero_id sale siempre de la sesión, ignorando $_POST['criadero_id'] y $_GET['criadero_id']
        self::assertSame($this->criaderoA, $controlador->probarCriaderoIdDeSesion());

        unset($_POST['criadero_id'], $_GET['criadero_id']);
    }

    /**
     * ModeloCriadero rechaza por seguridad cualquier consulta que olvide filtrar por `criadero_id = :criadero_id`.
     */
    public function testModeloCriaderoRechazaConsultaSinFiltroDeCriadero(): void
    {
        $modeloInseguro = new class extends ModeloCriadero {
            public function consultaSinFiltro(int $criaderoId): ?array
            {
                return $this->buscarUnoDelCriadero('SELECT * FROM canario LIMIT 1', $criaderoId);
            }
        };

        $this->expectException(LogicException::class);
        $modeloInseguro->consultaSinFiltro($this->criaderoA);
    }

    private function assertResponde404SinMensaje(callable $operacion): void
    {
        try {
            $operacion();
            self::fail('Debió responder 404 para un registro de otro criadero.');
        } catch (HttpException $error) {
            self::assertSame(404, $error->codigo());
            self::assertSame('', $error->getMessage());
        }
    }

    private function crearControladorDePrueba(): object
    {
        return new class extends BaseController {
            public function probarCriaderoIdDeSesion(): int
            {
                return $this->criaderoIdDeSesion();
            }

            /** @return array{usuario_id: int, nombre: string, rol: 'admin'} */
            public function probarExigirAdmin(): array
            {
                return $this->exigirAdmin();
            }

            /**
             * @param array<string, mixed>|null $registro
             * @return array<string, mixed>
             */
            public function probarExigirRegistroPropio(?array $registro): array
            {
                return $this->exigirRegistroPropio($registro);
            }
        };
    }

    private function crearCriadero(string $correo): int
    {
        $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento)
             VALUES ('Criador RF003', ?, ?, 'criador', 'activa', CURRENT_TIMESTAMP)"
        )->execute([$correo, password_hash('ClaveValida2026', PASSWORD_DEFAULT)]);
        $usuarioId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare('INSERT INTO criadero (usuario_id) VALUES (?)')->execute([$usuarioId]);

        return (int) $this->pdo->lastInsertId();
    }

    private function crearJaula(int $criaderoId, int $numero): int
    {
        $this->pdo->prepare('INSERT INTO jaula (criadero_id, numero) VALUES (?, ?)')->execute([$criaderoId, $numero]);

        return (int) $this->pdo->lastInsertId();
    }

    private function crearCanario(int $criaderoId, int $correlativo, string $sexo, ?string $variedadCodigo): int
    {
        $this->pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, fecha_anillado, sexo, variedad_codigo, estado)
             VALUES (?, 'S-003', 2026, ?, 'Club RF003', '2026-10-10', ?, ?, 'activo')"
        )->execute([$criaderoId, $correlativo, $sexo, $variedadCodigo]);

        return (int) $this->pdo->lastInsertId();
    }

    private function crearCruce(int $criaderoId, int $machoId, int $hembraId, int $jaulaId, ?string $fechaFin): int
    {
        $this->pdo->prepare(
            "INSERT INTO cruce (criadero_id, macho_id, hembra_id, jaula_id, temporada, fecha_inicio, fecha_fin)
             VALUES (?, ?, ?, ?, 2026, '2026-09-01', ?)"
        )->execute([$criaderoId, $machoId, $hembraId, $jaulaId, $fechaFin]);

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
