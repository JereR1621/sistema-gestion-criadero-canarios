<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Core\Aplicacion;
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

    public function testRegistroFallaConContrasenaDeMasDe72Bytes(): void
    {
        // bcrypt ignora lo que pasa de 72 bytes: se rechaza en vez de cortarla sin avisar.
        $larga = $this->servicio->registrar([
            'nombre' => 'Ana Criadora',
            'correo' => 'ana.larga@tucriadero.cl',
            'contrasena' => str_repeat('a', 73),
            'consentimiento' => '1',
        ]);
        self::assertArrayHasKey('contrasena', $larga['errores']);

        // 40 eñes son 40 caracteres, pero 80 bytes.
        $conEnies = $this->servicio->registrar([
            'nombre' => 'Ana Criadora',
            'correo' => 'ana.enie@tucriadero.cl',
            'contrasena' => str_repeat('ñ', 40),
            'consentimiento' => '1',
        ]);
        self::assertArrayHasKey('contrasena', $conEnies['errores']);
    }

    public function testRegistroAceptaContrasenaDeExactamente72Bytes(): void
    {
        $resultado = $this->servicio->registrar([
            'nombre' => 'Ana Criadora',
            'correo' => 'ana.limite@tucriadero.cl',
            'contrasena' => str_repeat('a', 72),
            'consentimiento' => '1',
        ]);

        self::assertSame([], $resultado['errores']);
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

    // -------------------------------------------------------------------------
    // RF004: Socio y club por defecto en los anillos
    // -------------------------------------------------------------------------

    public function testPerfilGuardaSocioYClubYEntregaValoresPorDefectoParaAnillos(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Carlos Criador',
            'correo' => 'carlos.perfil@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $usuarioId = (int) $registro['usuario_id'];
        $_SESSION['usuario_id'] = $usuarioId;

        // Antes de completar el perfil, los valores por defecto son null
        self::assertSame(
            ['anillo_socio' => null, 'anillo_club' => null],
            $this->servicio->obtenerValoresPorDefectoAnillo($usuarioId)
        );

        $resultado = $this->servicio->actualizarPerfil($usuarioId, [
            'numero_socio' => '  S-0452  ',
            'club' => '  Asociación de Canaricultura Chile  ',
        ]);

        self::assertSame([], $resultado['errores']);

        // La vista recibe exactamente las claves del contrato (§6): nombre, correo, numero_socio, club
        $perfil = $this->servicio->obtenerPerfil($usuarioId);
        self::assertSame([
            'nombre' => 'Carlos Criador',
            'correo' => 'carlos.perfil@tucriadero.cl',
            'numero_socio' => 'S-0452',
            'club' => 'Asociación de Canaricultura Chile',
        ], $perfil);

        // El servicio entrega esos valores como predeterminados para los formularios de anillado
        self::assertSame([
            'anillo_socio' => 'S-0452',
            'anillo_club' => 'Asociación de Canaricultura Chile',
        ], $this->servicio->obtenerValoresPorDefectoAnillo($usuarioId));

        // Auditoría registrada (SEG-003)
        $stmt = $this->pdo->prepare(
            "SELECT usuario_id, accion, entidad, entidad_id
             FROM auditoria
             WHERE accion = 'actualizar' AND entidad = 'usuario' AND entidad_id = ?"
        );
        $stmt->execute([$usuarioId]);
        $audit = $stmt->fetch();

        self::assertIsArray($audit);
        self::assertSame($usuarioId, (int) $audit['usuario_id']);
        self::assertSame('actualizar', $audit['accion']);
        self::assertSame('usuario', $audit['entidad']);
        self::assertSame($usuarioId, (int) $audit['entidad_id']);

        unset($_SESSION['usuario_id']);
    }

    public function testPerfilPermiteLimpiarSocioYClubOpcionalmente(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Elena Criadora',
            'correo' => 'elena.perfil@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $usuarioId = (int) $registro['usuario_id'];

        $this->servicio->actualizarPerfil($usuarioId, [
            'numero_socio' => 'S-0900',
            'club' => 'Club Sur',
        ]);

        $limpiar = $this->servicio->actualizarPerfil($usuarioId, [
            'numero_socio' => '   ',
            'club' => '',
        ]);

        self::assertSame([], $limpiar['errores']);
        self::assertSame(
            ['anillo_socio' => null, 'anillo_club' => null],
            $this->servicio->obtenerValoresPorDefectoAnillo($usuarioId)
        );
    }

    public function testPerfilSoloModificaLaCuentaIndicadaPorLaSesion(): void
    {
        $criador1 = $this->servicio->registrar([
            'nombre' => 'Criador Uno',
            'correo' => 'perfil.uno@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $criador2 = $this->servicio->registrar([
            'nombre' => 'Criador Dos',
            'correo' => 'perfil.dos@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);

        $id1 = (int) $criador1['usuario_id'];
        $id2 = (int) $criador2['usuario_id'];

        // Aunque en los datos del formulario se envíe otro usuario_id, se ignora y solo se edita $id1
        $resultado = $this->servicio->actualizarPerfil($id1, [
            'usuario_id' => $id2,
            'numero_socio' => 'S-1111',
            'club' => 'Club Uno',
        ]);

        self::assertSame([], $resultado['errores']);
        self::assertSame(
            ['anillo_socio' => 'S-1111', 'anillo_club' => 'Club Uno'],
            $this->servicio->obtenerValoresPorDefectoAnillo($id1)
        );
        self::assertSame(
            ['anillo_socio' => null, 'anillo_club' => null],
            $this->servicio->obtenerValoresPorDefectoAnillo($id2)
        );
    }

    public function testPerfilRechazaSocioOClubQueSuperanLongitudMaximaSinGuardar(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Mario Criador',
            'correo' => 'mario.largo@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $usuarioId = (int) $registro['usuario_id'];
        $auditoriaAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        $resultado = $this->servicio->actualizarPerfil($usuarioId, [
            'numero_socio' => str_repeat('9', 51),
            'club' => str_repeat('A', 51),
        ]);

        self::assertArrayHasKey('numero_socio', $resultado['errores']);
        self::assertSame('El club no puede superar los 50 caracteres.', $resultado['errores']['club'] ?? null);
        self::assertSame(
            ['anillo_socio' => null, 'anillo_club' => null],
            $this->servicio->obtenerValoresPorDefectoAnillo($usuarioId)
        );
        self::assertSame($auditoriaAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
    }

    public function testElClubPropuestoSiempreCabeEnElAnillo(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Mario Criador',
            'correo' => 'mario.limite@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $usuarioId = (int) $registro['usuario_id'];
        $limiteDelAnillo = (int) $this->pdo->query(
            "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'canario' AND COLUMN_NAME = 'anillo_club'"
        )->fetchColumn();

        self::assertSame([], $this->servicio->actualizarPerfil($usuarioId, ['numero_socio' => '1', 'club' => str_repeat('A', $limiteDelAnillo)])['errores']);
        self::assertArrayHasKey('club', $this->servicio->actualizarPerfil($usuarioId, ['numero_socio' => '1', 'club' => str_repeat('A', $limiteDelAnillo + 1)])['errores']);
    }

    public function testPerfilRechazaCuentaDeAdministrador(): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado)
             VALUES ('Admin Perfil', 'admin.perfil@tucriadero.cl', ?, 'admin', 'activa')"
        );
        $stmt->execute([password_hash('AdminClave2026', PASSWORD_DEFAULT)]);
        $adminId = (int) $this->pdo->lastInsertId();

        $resultado = $this->servicio->actualizarPerfil($adminId, [
            'numero_socio' => 'S-0001',
            'club' => 'Club Admin',
        ]);

        self::assertArrayHasKey('_general', $resultado['errores']);
        self::assertSame(
            ['anillo_socio' => null, 'anillo_club' => null],
            $this->servicio->obtenerValoresPorDefectoAnillo($adminId)
        );
    }

    public function testPerfilRechazaCuentaInexistenteODesactivada(): void
    {
        $inexistente = $this->servicio->actualizarPerfil(999999, [
            'numero_socio' => 'S-0001',
            'club' => 'Club Fantasma',
        ]);
        self::assertArrayHasKey('_general', $inexistente['errores']);
        self::assertNull($this->servicio->obtenerPerfil(999999));
        self::assertSame(
            ['anillo_socio' => null, 'anillo_club' => null],
            $this->servicio->obtenerValoresPorDefectoAnillo(999999)
        );

        $registro = $this->servicio->registrar([
            'nombre' => 'Criador Inactivo',
            'correo' => 'inactivo.perfil@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $usuarioId = (int) $registro['usuario_id'];
        $this->pdo->prepare("UPDATE usuario SET estado = 'desactivada' WHERE id = ?")->execute([$usuarioId]);

        $desactivada = $this->servicio->actualizarPerfil($usuarioId, [
            'numero_socio' => 'S-0002',
            'club' => 'Club Inactivo',
        ]);
        self::assertArrayHasKey('_general', $desactivada['errores']);
    }

    public function testRutasDePerfilEstanRegistradasSegunContrato(): void
    {
        $router = Aplicacion::crearRouter();

        self::assertSame('/perfil', $router->url('perfil'));
        self::assertSame('/perfil', $router->url('perfil.guardar'));
    }
}
