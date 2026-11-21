<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Models\CriaderoModel;
use TuCriadero\Models\UsuarioModel;
use TuCriadero\Services\CuentaService;

final class CuentaServiceTest extends TestCase
{
    private PDO $pdo;
    private CuentaService $servicio;

    protected function setUp(): void
    {
        try {
            $this->pdo = self::baseDePruebas();
            $this->pdo->query('SELECT 1 FROM usuario LIMIT 1');
        } catch (Throwable) {
            self::markTestSkipped('Sin base de pruebas: levanta MySQL y corre php backend/database/migrar.php --pruebas.');
        }

        Conexion::usar($this->pdo);
        $this->pdo->beginTransaction();

        $this->servicio = new CuentaService(
            new UsuarioModel(),
            new CriaderoModel()
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        Conexion::usar(null);
    }

    /** Conexión a DB_NAME_PRUEBAS (docs/base-de-datos.md): las pruebas nunca tocan la base de trabajo. */
    private static function baseDePruebas(): PDO
    {
        Entorno::cargar(dirname(__DIR__, 2) . '/.env');
        $anterior = $_ENV['DB_NAME'] ?? null;
        $_ENV['DB_NAME'] = Entorno::obtener('DB_NAME_PRUEBAS') ?? '';   // mismo mecanismo que migrar.php --pruebas

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

    // -------------------------------------------------------------------------
    // RF001: Registro de cuenta de criador
    // -------------------------------------------------------------------------

    public function testRegistroExitosoCreaCuentaYCriaderoVacio(): void
    {
        $resultado = $this->servicio->registrar([
            'nombre' => 'Ana Criadora',
            'correo' => 'ana.prueba@tucriadero.cl',
            'contrasena' => 'ClaveSegura123',
            'consentimiento' => '1',
        ]);

        self::assertSame([], $resultado['errores']);
        self::assertArrayHasKey('usuario_id', $resultado);
        self::assertArrayHasKey('criadero_id', $resultado);
        self::assertGreaterThan(0, $resultado['usuario_id']);
        self::assertGreaterThan(0, $resultado['criadero_id']);

        $usuario = $this->pdo->query("SELECT rol, estado, fecha_consentimiento FROM usuario WHERE id = {$resultado['usuario_id']}")->fetch();
        self::assertSame(['criador', 'activa'], [$usuario['rol'], $usuario['estado']]);
        self::assertNotNull($usuario['fecha_consentimiento'], 'POL-001: guarda la fecha del consentimiento.');

        $criadero = (int) $resultado['criadero_id'];
        foreach (['canario', 'jaula'] as $tabla) {
            self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM {$tabla} WHERE criadero_id = {$criadero}")->fetchColumn(), "Criadero vacío: sin {$tabla}.");
        }
    }

    public function testRegistroFallaSinConsentimientoPol001(): void
    {
        $resultado = $this->servicio->registrar([
            'nombre' => 'Ana Criadora',
            'correo' => 'ana.sinconsentimiento@tucriadero.cl',
            'contrasena' => 'ClaveSegura123',
        ]);

        self::assertSame(
            'Para crear la cuenta debes aceptar el uso de tus datos',
            $resultado['errores']['consentimiento'] ?? ''
        );
    }

    public function testRegistroFallaConContrasenaCorta(): void
    {
        $resultado = $this->servicio->registrar([
            'nombre' => 'Ana Criadora',
            'correo' => 'ana.corta@tucriadero.cl',
            'contrasena' => '1234567',
            'consentimiento' => '1',
        ]);

        self::assertArrayHasKey('contrasena', $resultado['errores']);
    }

    public function testRegistroFallaSiFaltaNombreCorreoOContrasena(): void
    {
        $antes = (int) $this->pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn();

        $resultado = $this->servicio->registrar(['consentimiento' => '1']);

        self::assertSame(['nombre', 'correo', 'contrasena'], array_keys($resultado['errores']));
        self::assertSame($antes, (int) $this->pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn(), 'No guarda nada.');
    }

    public function testRegistroFallaConCorreoDuplicado(): void
    {
        $datos = [
            'nombre' => 'Ana Criadora',
            'correo' => 'ana.duplicada@tucriadero.cl',
            'contrasena' => 'ClaveSegura123',
            'consentimiento' => '1',
        ];

        $primero = $this->servicio->registrar($datos);
        self::assertSame([], $primero['errores']);

        $segundo = $this->servicio->registrar($datos);
        self::assertArrayHasKey('correo', $segundo['errores']);
    }

    // -------------------------------------------------------------------------
    // RF002: Inicio de sesión
    // -------------------------------------------------------------------------

    public function testInicioSesionExitosoCriadorDevuelveSuPropioCriadero(): void
    {
        $registro1 = $this->servicio->registrar([
            'nombre' => 'Criador Uno',
            'correo' => 'criador1@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);

        $registro2 = $this->servicio->registrar([
            'nombre' => 'Criador Dos',
            'correo' => 'criador2@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);

        $login = $this->servicio->autenticar([
            'correo' => 'CRIADOR1@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
        ]);

        self::assertSame([], $login['errores']);
        self::assertArrayHasKey('usuario', $login);
        self::assertSame($registro1['usuario_id'], $login['usuario']['id']);
        self::assertSame('Criador Uno', $login['usuario']['nombre']);
        self::assertSame('criador', $login['usuario']['rol']);
        self::assertSame($registro1['criadero_id'], $login['usuario']['criadero_id']);
        self::assertNotSame($registro2['criadero_id'], $login['usuario']['criadero_id']);
    }

    public function testInicioSesionExitosoAdminNoTieneCriadero(): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento)
             VALUES (:nombre, :correo, :hash, 'admin', 'activa', NOW())"
        );
        $stmt->execute([
            ':nombre' => 'Administrador',
            ':correo' => 'admin.prueba@tucriadero.cl',
            ':hash' => password_hash('AdminClave2026', PASSWORD_DEFAULT),
        ]);
        $adminId = (int) $this->pdo->lastInsertId();

        $login = $this->servicio->autenticar([
            'correo' => 'admin.prueba@tucriadero.cl',
            'contrasena' => 'AdminClave2026',
        ]);

        self::assertSame([], $login['errores']);
        self::assertSame($adminId, $login['usuario']['id']);
        self::assertSame('admin', $login['usuario']['rol']);
        self::assertNull($login['usuario']['criadero_id']);
    }

    public function testInicioSesionFallaConCorreoInexistente(): void
    {
        $login = $this->servicio->autenticar([
            'correo' => 'noexiste@tucriadero.cl',
            'contrasena' => 'CualquierClave123',
        ]);

        self::assertSame(
            'Correo o contraseña incorrectos',
            $login['errores']['_general'] ?? ''
        );
        self::assertArrayNotHasKey('usuario', $login);
    }

    public function testInicioSesionFallaConContrasenaIncorrecta(): void
    {
        $this->servicio->registrar([
            'nombre' => 'Criador Prueba',
            'correo' => 'criador.clave@tucriadero.cl',
            'contrasena' => 'ClaveCorrecta2026',
            'consentimiento' => '1',
        ]);

        $login = $this->servicio->autenticar([
            'correo' => 'criador.clave@tucriadero.cl',
            'contrasena' => 'ClaveEquivocada999',
        ]);

        self::assertSame(
            'Correo o contraseña incorrectos',
            $login['errores']['_general'] ?? ''
        );
        self::assertArrayNotHasKey('usuario', $login);
    }

    public function testInicioSesionFallaConCamposVacios(): void
    {
        $login = $this->servicio->autenticar([
            'correo' => '',
            'contrasena' => '',
        ]);

        self::assertSame(
            'Correo o contraseña incorrectos',
            $login['errores']['_general'] ?? ''
        );
        self::assertArrayNotHasKey('usuario', $login);
    }

    public function testInicioSesionFallaConCuentaDesactivada(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Criador Suspendido',
            'correo' => 'suspendido@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);

        $stmt = $this->pdo->prepare("UPDATE usuario SET estado = 'desactivada' WHERE id = :id");
        $stmt->execute([':id' => $registro['usuario_id']]);

        $login = $this->servicio->autenticar([
            'correo' => 'suspendido@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
        ]);

        self::assertArrayHasKey('_general', $login['errores']);
        self::assertArrayNotHasKey('usuario', $login);
    }
}
