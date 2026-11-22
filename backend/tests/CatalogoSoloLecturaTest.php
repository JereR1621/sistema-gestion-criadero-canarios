<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionProperty;
use SplFileInfo;
use Throwable;
use TuCriadero\Controllers\AdminCatalogoController;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
use TuCriadero\Core\Ruta;
use TuCriadero\Services\CatalogoService;

/**
 * Pruebas de SRS-RF030: el catálogo de variedades y factores es de solo lectura para los criadores.
 *
 * Comprueba:
 * - Un criador que pide cualquier ruta `/admin/variedades*` o `/admin/factores*` recibe 403
 *   sin que se modifiquen `variedad`, `factor`, `variedad_factor` ni `auditoria`.
 * - Defensa en profundidad: `CatalogoService` impide cualquier escritura con sesión de criador (403)
 *   y permite la consulta de solo lectura.
 * - Ninguna ruta/controlador de criador ni vista de criador expone modificación del catálogo.
 */
final class CatalogoSoloLecturaTest extends TestCase
{
    private PDO $pdo;
    private CatalogoService $servicio;

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

        $this->servicio = new CatalogoService();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        Conexion::usar(null);
        unset(
            $_SESSION['usuario_id'],
            $_SESSION['rol'],
            $_SESSION['criadero_id'],
            $_SESSION['nombre'],
        );
        $_POST = [];
        $_GET = [];
    }

    /**
     * Criterio positivo (SRS-RF030): un criador autenticado que solicita cualquiera de las rutas
     * `/admin/variedades*` o `/admin/factores*` a través del enrutador real recibe 403 y no altera el catálogo.
     */
    public function testCriadorRecibe403EnTodasLasRutasAdminDelCatalogoSinAlterarDatos(): void
    {
        $_SESSION['usuario_id'] = 1001;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = 1001;
        $_SESSION['nombre'] = 'Criador Solo Lectura';

        $variedadesAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM variedad')->fetchColumn();
        $factoresAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM factor')->fetchColumn();
        $composicionAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM variedad_factor')->fetchColumn();
        $auditoriaAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        $router = Aplicacion::crearRouter();

        $peticiones = [
            ['GET', '/admin/variedades', []],
            ['GET', '/admin/variedades/D.049', []],
            ['POST', '/admin/variedades/D.049', [
                'nombre' => 'ROJO INTENSO ALTERADO',
                'sexo' => '',
                'tipo' => 'lipocromico',
                'rasgo' => '',
                'factores' => [1],
            ]],
            ['GET', '/admin/factores', []],
            ['POST', '/admin/factores', [
                'nombre' => 'FACTOR ILEGAL',
                'tipo_herencia' => 'recesivo',
            ]],
        ];

        foreach ($peticiones as [$metodo, $url, $post]) {
            $_POST = $post;
            try {
                $router->despachar($metodo, $url);
                self::fail("Un criador debe recibir 403 al solicitar {$metodo} {$url}.");
            } catch (HttpException $error) {
                self::assertSame(403, $error->codigo(), "{$metodo} {$url} debió responder 403.");
            }
        }

        self::assertSame($variedadesAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM variedad')->fetchColumn());
        self::assertSame($factoresAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM factor')->fetchColumn());
        self::assertSame($composicionAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM variedad_factor')->fetchColumn());
        self::assertSame($auditoriaAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
        self::assertSame('ROJO INTENSO', $this->servicio->obtenerVariedad('D.049')['nombre']);
    }

    /**
     * Defensa en profundidad (SRS-RF030): incluso llamando directamente a `CatalogoService`,
     * un criador no puede crear, modificar ni eliminar variedades ni factores del catálogo.
     */
    public function testCriadorNoPuedeModificarNiEliminarCatalogoDesdeElServicio(): void
    {
        $_SESSION['usuario_id'] = 1001;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = 1001;

        $operacionesProhibidas = [
            'crearVariedad' => fn () => $this->servicio->crearVariedad([
                'codigo' => 'D.950',
                'nombre' => 'NO PERMITIDA',
                'sexo' => null,
                'tipo' => 'lipocromico',
                'rasgo' => null,
                'factores' => [1],
            ]),
            'actualizarVariedad' => fn () => $this->servicio->actualizarVariedad('D.049', [
                'nombre' => 'EDITADA POR CRIADOR',
                'sexo' => null,
                'tipo' => 'lipocromico',
                'rasgo' => null,
                'factores' => [1, 4],
            ]),
            'eliminarVariedad' => fn () => $this->servicio->eliminarVariedad('D.880'),
            'cargarVariedad' => fn () => $this->servicio->cargarVariedad('D.951', 'NO PERMITIDA', null, 'lipocromico', null, []),
            'crearFactor' => fn () => $this->servicio->crearFactor('FACTOR PROHIBIDO', 'recesivo'),
            'cargarFactor' => fn () => $this->servicio->cargarFactor('FACTOR PROHIBIDO 2', 'recesivo'),
        ];

        foreach ($operacionesProhibidas as $nombre => $operacion) {
            try {
                $operacion();
                self::fail("Un criador no debe poder ejecutar CatalogoService::{$nombre}().");
            } catch (HttpException $error) {
                self::assertSame(403, $error->codigo(), "CatalogoService::{$nombre}() debió responder 403.");
            }
        }

        // Ningún dato del catálogo fue modificado
        self::assertSame('ROJO INTENSO', $this->servicio->obtenerVariedad('D.049')['nombre']);
        self::assertNotNull($this->servicio->obtenerVariedad('D.880'));
        self::assertCount(21, $this->servicio->listarFactores());
    }

    /**
     * Un criador sí puede consultar libremente el catálogo en modo solo lectura (RF028, RF031, RF033).
     */
    public function testCriadorPuedeConsultarElCatalogoEnSoloLectura(): void
    {
        $_SESSION['usuario_id'] = 1001;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = 1001;

        self::assertCount(880, $this->servicio->listarVariedades());
        self::assertSame('ROJO INTENSO', $this->servicio->obtenerVariedad('D.049')['nombre']);
        self::assertSame(['ROJO', 'INTENSO'], array_column($this->servicio->obtenerComposicion('D.049'), 'nombre'));
        self::assertCount(21, $this->servicio->listarFactores());
        self::assertSame('ligado_sexo', $this->servicio->obtenerTipoHerencia(6));
        self::assertNotEmpty($this->servicio->listarFactoresPortables());
    }

    /**
     * Revisión estructural (paso 3): todas las rutas del catálogo están bajo `/admin/`,
     * y ningún controlador de criador escribe en `variedad`, `factor` ni `variedad_factor`.
     */
    public function testNingunaRutaDeCriadorModificaElCatalogo(): void
    {
        $router = Aplicacion::crearRouter();
        $propiedad = new ReflectionProperty($router, 'rutas');
        /** @var list<Ruta> $rutas */
        $rutas = $propiedad->getValue($router);

        $rutasAdminCatalogo = 0;
        foreach ($rutas as $ruta) {
            if ($ruta->controlador === AdminCatalogoController::class) {
                $rutasAdminCatalogo++;
                self::assertStringStartsWith('/admin/', $ruta->patron);
            } else {
                self::assertStringNotContainsString('/variedades', $ruta->patron);
                self::assertStringNotContainsString('/factores', $ruta->patron);
            }
        }
        self::assertGreaterThanOrEqual(5, $rutasAdminCatalogo);

        // Ningún controlador fuera de AdminCatalogoController invoca escritura sobre el catálogo
        $directorioControladores = dirname(__DIR__) . '/Controllers';
        foreach (glob($directorioControladores . '/*.php') ?: [] as $archivo) {
            if (basename($archivo) === 'AdminCatalogoController.php') {
                continue;
            }
            $contenido = (string) file_get_contents($archivo);
            self::assertDoesNotMatchRegularExpression(
                '/\b(crearVariedad|actualizarVariedad|eliminarVariedad|cargarVariedad|crearFactor|cargarFactor)\s*\(/',
                $contenido,
                basename($archivo) . ' no debe modificar el catálogo.'
            );
        }
    }

    /**
     * Alcance (SRS-RF030): las vistas que usa un criador no muestran controles ni enlaces
     * para editar el catálogo (`/admin/variedades`, `/admin/factores`).
     */
    public function testVistasDeCriadorNoMuestranControlesParaEditarElCatalogo(): void
    {
        $raizVistas = dirname(__DIR__, 2) . '/frontend/views';
        $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raizVistas));

        /** @var SplFileInfo $archivo */
        foreach ($iterador as $archivo) {
            if (!$archivo->isFile() || $archivo->getExtension() !== 'php') {
                continue;
            }

            $rutaRelativa = str_replace('\\', '/', substr($archivo->getPathname(), strlen($raizVistas) + 1));
            if (str_starts_with($rutaRelativa, 'admin/')) {
                continue;
            }

            $contenido = (string) file_get_contents($archivo->getPathname());
            self::assertStringNotContainsString('/admin/variedades', $contenido, "{$rutaRelativa} no debe enlazar a /admin/variedades.");
            self::assertStringNotContainsString('/admin/factores', $contenido, "{$rutaRelativa} no debe enlazar a /admin/factores.");
            self::assertStringNotContainsString('admin.variedades', $contenido, "{$rutaRelativa} no debe usar la ruta admin.variedades.");
            self::assertStringNotContainsString('admin.factores', $contenido, "{$rutaRelativa} no debe usar la ruta admin.factores.");
        }
    }

    /**
     * Contraste: con rol `admin`, el mantenimiento del catálogo sigue funcionando con auditoría (SEG-003).
     */
    public function testAdministradorSiPuedeModificarElCatalogoConAuditoria(): void
    {
        $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado)
             VALUES ('Admin RF030', 'admin.rf030@tucriadero.cl', ?, 'admin', 'activa')"
        )->execute([password_hash('AdminClave2026', PASSWORD_DEFAULT)]);
        $adminId = (int) $this->pdo->lastInsertId();

        $_SESSION['usuario_id'] = $adminId;
        $_SESSION['rol'] = 'admin';
        $_SESSION['criadero_id'] = null;

        $creado = $this->servicio->crearFactor('FACTOR ADMIN RF030', 'recesivo');
        self::assertSame([], $creado['errores']);
        self::assertArrayHasKey('factor_id', $creado);

        $stmt = $this->pdo->prepare(
            "SELECT usuario_id, accion FROM auditoria WHERE entidad = 'factor' AND entidad_id = ?"
        );
        $stmt->execute([(string) $creado['factor_id']]);
        $audit = $stmt->fetch();

        self::assertIsArray($audit);
        self::assertSame($adminId, (int) $audit['usuario_id']);
        self::assertSame('crear', $audit['accion']);
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
