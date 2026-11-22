<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Controllers\CanarioController;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
use TuCriadero\Core\Rutas;
use TuCriadero\Services\CanarioService;

/**
 * Pruebas de SRS-RF007: Registro de canario (sexo y variedad pueden quedar sin determinar).
 */
final class CanarioServiceTest extends TestCase
{
    private PDO $pdo;
    private int $usuarioA;
    private int $criaderoA;
    private int $usuarioB;
    private int $criaderoB;
    private CanarioService $servicio;

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

        [$this->usuarioA, $this->criaderoA] = $this->crearCriadero('rf007.a@tucriadero.cl', 'S-77', 'Club Norte');
        [$this->usuarioB, $this->criaderoB] = $this->crearCriadero('rf007.b@tucriadero.cl', 'S-88', 'Club Sur');

        $_SESSION['usuario_id'] = $this->usuarioA;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = $this->criaderoA;
        $_SESSION['nombre'] = 'Criador A';

        $this->servicio = new CanarioService();
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
     * Criterio positivo (RF007): registra un canario con las 4 partes del anillo,
     * sexo y variedad sin determinar, ubicación inicial «Sin ubicación» y auditoría;
     * luego permite completarlos al editar.
     */
    public function testRegistrarSinDeterminarSexoNiVariedadYCompletarDespues(): void
    {
        $registro = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '101',
            'anillo_club' => 'Club Norte',
            'sexo' => '',
            'variedad_codigo' => '',
            'fecha_anillado' => '2026-09-15',
            'fecha_nacimiento' => '2026-09-01',
            'padre_id' => '',
            'madre_id' => '',
            'procedencia' => '',
        ]);

        self::assertSame([], $registro['errores']);
        $canarioId = (int) $registro['canario_id'];

        $ficha = $this->servicio->obtenerFicha($canarioId, $this->criaderoA);
        self::assertNotNull($ficha);
        self::assertSame('sin_determinar', $ficha['sexo']);
        self::assertNull($ficha['variedad']);
        self::assertSame('Sin ubicación', $ficha['ubicacion_texto']);
        self::assertSame('S-77 · 2026 · 101 · Club Norte', $ficha['anillo_texto']);
        self::assertNull($ficha['padre']);
        self::assertNull($ficha['madre']);

        // Auditoría SEG-003 registrada en la creación
        $auditCrear = $this->pdo->prepare("SELECT COUNT(*) FROM auditoria WHERE accion = 'crear' AND entidad = 'canario' AND entidad_id = ?");
        $auditCrear->execute([$canarioId]);
        self::assertSame(1, (int) $auditCrear->fetchColumn());

        // Completar sexo y variedad después
        $actualizacion = $this->servicio->actualizar($canarioId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '101',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'variedad_codigo' => 'D.049',
            'fecha_anillado' => '2026-09-15',
            'fecha_nacimiento' => '2026-09-01',
            'padre_id' => '',
            'madre_id' => '',
            'procedencia' => '',
        ]);

        self::assertSame([], $actualizacion['errores']);
        $fichaActualizada = $this->servicio->obtenerFicha($canarioId, $this->criaderoA);
        self::assertNotNull($fichaActualizada);
        self::assertSame('macho', $fichaActualizada['sexo']);
        self::assertSame('D.049', $fichaActualizada['variedad']['codigo'] ?? null);

        $auditActualizar = $this->pdo->prepare("SELECT COUNT(*) FROM auditoria WHERE accion = 'actualizar' AND entidad = 'canario' AND entidad_id = ?");
        $auditActualizar->execute([$canarioId]);
        self::assertSame(1, (int) $auditActualizar->fetchColumn());
    }

    /**
     * Criterio: $por_defecto trae socio y club del perfil (RF004) y listas vacías para variedades/portadores.
     */
    public function testPrepararDatosFormularioEntregaContratoYValoresPorDefecto(): void
    {
        $padreId = $this->insertarCanario($this->criaderoA, 1, 2024, 'macho');
        $madreId = $this->insertarCanario($this->criaderoA, 2, 2024, 'hembra');
        // Canario de otro criadero no debe aparecer en posibles padres/madres
        $this->insertarCanario($this->criaderoB, 1, 2024, 'macho');

        $datos = $this->servicio->prepararDatosFormulario($this->usuarioA, $this->criaderoA);

        self::assertSame([], $datos['variedades']);
        self::assertSame([], $datos['factores_portables']);
        self::assertSame(['anillo_socio' => 'S-77', 'anillo_club' => 'Club Norte'], $datos['por_defecto']);
        self::assertCount(1, $datos['posibles_padres']);
        self::assertSame($padreId, $datos['posibles_padres'][0]['id']);
        self::assertCount(1, $datos['posibles_madres']);
        self::assertSame($madreId, $datos['posibles_madres'][0]['id']);
    }

    /**
     * Criterio: un canario con padre y madre del criadero queda enlazado a ellos y arma su árbol.
     */
    public function testCanarioConPadreYMadreDelCriaderoQuedaEnlazado(): void
    {
        $padreId = $this->insertarCanario($this->criaderoA, 10, 2024, 'macho');
        $madreId = $this->insertarCanario($this->criaderoA, 11, 2025, 'hembra');

        $res = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '12',
            'anillo_club' => 'Club Norte',
            'sexo' => 'hembra',
            'variedad_codigo' => '',
            'padre_id' => (string) $padreId,
            'madre_id' => (string) $madreId,
        ]);

        self::assertSame([], $res['errores']);
        $ficha = $this->servicio->obtenerFicha((int) $res['canario_id'], $this->criaderoA);
        self::assertSame($padreId, $ficha['padre']['id'] ?? null);
        self::assertSame($madreId, $ficha['madre']['id'] ?? null);
        self::assertSame($padreId, $ficha['arbol']['padre']['id'] ?? null);
        self::assertSame($madreId, $ficha['arbol']['madre']['id'] ?? null);
    }

    /**
     * Criterio negativo: falta una parte del anillo (socio, año, correlativo o club); se informa sin guardar nada.
     */
    public function testNegativoFaltaUnaParteDelAnilloNoGuardaNada(): void
    {
        $antes = $this->contarCanarios();

        $base = [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '50',
            'anillo_club' => 'Club Norte',
            'sexo' => 'sin_determinar',
            'variedad_codigo' => '',
        ];

        foreach (['anillo_socio', 'anillo_anio', 'anillo_correlativo', 'anillo_club'] as $campo) {
            $incompleto = $base;
            $incompleto[$campo] = '';
            $res = $this->servicio->registrar($this->criaderoA, $incompleto);
            self::assertArrayHasKey($campo, $res['errores']);
        }

        self::assertSame($antes, $this->contarCanarios());
    }

    /**
     * Criterio negativo: un padre que no es macho o una madre que no es hembra se rechaza.
     */
    public function testNegativoPadreNoMachoOMadreNoHembraSeRechaza(): void
    {
        $hembraId = $this->insertarCanario($this->criaderoA, 20, 2024, 'hembra');
        $machoId = $this->insertarCanario($this->criaderoA, 21, 2024, 'macho');
        $sinDetId = $this->insertarCanario($this->criaderoA, 22, 2024, 'sin_determinar');

        $antes = $this->contarCanarios();

        $res1 = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '23',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $hembraId,
            'madre_id' => (string) $machoId,
        ]);
        self::assertArrayHasKey('padre_id', $res1['errores']);
        self::assertArrayHasKey('madre_id', $res1['errores']);

        $res2 = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '24',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $sinDetId,
            'madre_id' => (string) $sinDetId,
        ]);
        self::assertArrayHasKey('padre_id', $res2['errores']);
        self::assertArrayHasKey('madre_id', $res2['errores']);

        self::assertSame($antes, $this->contarCanarios());
    }

    /**
     * Criterio negativo: un canario con año de anillo anterior al de su padre o su madre se rechaza.
     */
    public function testNegativoAnioAnilloAnteriorAlDePadreOMadreSeRechaza(): void
    {
        $padre2025 = $this->insertarCanario($this->criaderoA, 30, 2025, 'macho');
        $madre2026 = $this->insertarCanario($this->criaderoA, 31, 2026, 'hembra');
        $antes = $this->contarCanarios();

        $resPadre = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2024',
            'anillo_correlativo' => '32',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $padre2025,
        ]);
        self::assertArrayHasKey('anillo_anio', $resPadre['errores']);

        $resMadre = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2025',
            'anillo_correlativo' => '33',
            'anillo_club' => 'Club Norte',
            'madre_id' => (string) $madre2026,
        ]);
        self::assertArrayHasKey('anillo_anio', $resMadre['errores']);

        self::assertSame($antes, $this->contarCanarios());
    }

    /**
     * Criterio negativo: indicar como padre o madre al propio canario o a uno de sus descendientes se rechaza.
     */
    public function testNegativoPadreOMadrePropioODescendienteSeRechaza(): void
    {
        $abueloId = $this->insertarCanario($this->criaderoA, 40, 2023, 'macho');
        $padreId = $this->insertarCanario($this->criaderoA, 41, 2024, 'macho', $abueloId);
        $hijoId = $this->insertarCanario($this->criaderoA, 42, 2025, 'macho', $padreId);

        // Sí mismo como padre
        $resPropio = $this->servicio->actualizar($abueloId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2023',
            'anillo_correlativo' => '40',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'padre_id' => (string) $abueloId,
        ]);
        self::assertArrayHasKey('padre_id', $resPropio['errores']);

        // Nieto (descendiente) como padre del abuelo
        $resDescendiente = $this->servicio->actualizar($abueloId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2023',
            'anillo_correlativo' => '40',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'padre_id' => (string) $hijoId,
        ]);
        self::assertArrayHasKey('padre_id', $resDescendiente['errores']);
    }

    /**
     * Criterios negativos de aislamiento (RF003):
     * - Padre/madre de otro criadero en formulario se rechaza sin guardar.
     * - Listado activo solo devuelve datos del criadero de la sesión.
     * - Pedir ficha/edición de un canario de otro criadero responde 404.
     */
    public function testNegativoAislamientoEntreCriaderosY404(): void
    {
        $machoAjeno = $this->insertarCanario($this->criaderoB, 1, 2024, 'macho');
        $antes = $this->contarCanarios();

        // Padre de otro criadero rechazado
        $resPadreAjeno = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '99',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $machoAjeno,
        ]);
        self::assertArrayHasKey('padre_id', $resPadreAjeno['errores']);
        self::assertSame($antes, $this->contarCanarios());

        // Listado solo trae canarios del criadero A
        $propio = $this->insertarCanario($this->criaderoA, 5, 2026, 'hembra');
        $listado = $this->servicio->listarActivos($this->criaderoA, 1);
        $idsListados = array_column($listado['canarios'], 'id');
        self::assertContains($propio, $idsListados);
        self::assertNotContains($machoAjeno, $idsListados);

        // Controlador responde 404 al pedir ficha de otro criadero
        Rutas::definir(Aplicacion::crearRouter());
        $controlador = new CanarioController();

        $this->expectException(HttpException::class);
        try {
            $controlador->ficha((string) $machoAjeno);
        } catch (HttpException $error) {
            self::assertSame(404, $error->codigo());
            throw $error;
        }
    }

    /** @return array{int, int} */
    private function crearCriadero(string $correo, string $socio, string $club): array
    {
        $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento, numero_socio, club)
             VALUES ('Criador RF007', ?, ?, 'criador', 'activa', CURRENT_TIMESTAMP, ?, ?)"
        )->execute([$correo, password_hash('ClaveValida2026', PASSWORD_DEFAULT), $socio, $club]);
        $usuarioId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare('INSERT INTO criadero (usuario_id) VALUES (?)')->execute([$usuarioId]);
        $criaderoId = (int) $this->pdo->lastInsertId();

        return [$usuarioId, $criaderoId];
    }

    private function insertarCanario(int $criaderoId, int $correlativo, int $anio, string $sexo, ?int $padreId = null): int
    {
        $this->pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, sexo, padre_id, estado)
             VALUES (?, 'S-77', ?, ?, 'Club Norte', ?, ?, 'activo')"
        )->execute([$criaderoId, $anio, $correlativo, $sexo, $padreId]);

        return (int) $this->pdo->lastInsertId();
    }

    private function contarCanarios(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM canario')->fetchColumn();
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