<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use TuCriadero\Controllers\AdminCuentaController;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
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
        unset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['criadero_id'], $_SESSION['nombre']);
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
            // 73 bytes sin repetir un solo carácter (eso se rechaza aparte, por fácil de adivinar).
            'contrasena' => substr(str_repeat('jaula-patio-', 7), 0, 73),
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
            'contrasena' => substr(str_repeat('jaula-patio-', 7), 0, 72),
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

    // -------------------------------------------------------------------------
    // RF005: Desactivar y cerrar cuenta (administrador)
    // -------------------------------------------------------------------------

    public function testDesactivarCuentaImpideInicioDeSesionYConservaDatosYRegistraAuditoria(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Lucia Criadora',
            'correo' => 'lucia.rf005@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $criadorId = (int) $registro['usuario_id'];
        $criaderoId = (int) $registro['criadero_id'];

        // Crear datos reales del criadero (una jaula y un canario) para comprobar que se conservan al desactivar
        $this->pdo->prepare('INSERT INTO jaula (criadero_id, numero) VALUES (?, ?)')->execute([$criaderoId, 15]);
        $this->pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, fecha_anillado, sexo, estado)
             VALUES (?, 'S-50', 2026, 7, 'Club Norte', '2026-10-10', 'macho', 'activo')"
        )->execute([$criaderoId]);

        $adminId = $this->crearAdminDePrueba('admin.rf005@tucriadero.cl');
        $_SESSION['usuario_id'] = $adminId;
        $_SESSION['rol'] = 'admin';

        $resultado = $this->servicio->cambiarEstadoCuenta($criadorId, ['estado' => 'desactivada'], $adminId);

        self::assertSame([], $resultado['errores']);
        self::assertSame('desactivada', $resultado['cuenta']['estado']);
        self::assertNull($resultado['cuenta']['fecha_cierre']);

        // Impide iniciar sesión con error _general (contrato §6, ingreso.enviar)
        $login = $this->servicio->autenticar([
            'correo' => 'lucia.rf005@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
        ]);
        self::assertArrayHasKey('_general', $login['errores']);
        self::assertStringContainsString('desactivada', $login['errores']['_general']);
        self::assertArrayNotHasKey('usuario', $login);

        // Conserva todos los datos del criadero (usuario, criadero, jaula y canario)
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM criadero WHERE id = {$criaderoId}")->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM jaula WHERE criadero_id = {$criaderoId}")->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM canario WHERE criadero_id = {$criaderoId}")->fetchColumn());

        // Auditoría registrada (SEG-003)
        $stmt = $this->pdo->prepare(
            "SELECT usuario_id, accion, entidad, entidad_id
             FROM auditoria
             WHERE accion = 'desactivar' AND entidad = 'usuario' AND entidad_id = ?"
        );
        $stmt->execute([$criadorId]);
        $audit = $stmt->fetch();
        self::assertIsArray($audit);
        self::assertSame($adminId, (int) $audit['usuario_id']);

        // El administrador también puede reactivar la cuenta y el criador vuelve a entrar
        $reactivar = $this->servicio->cambiarEstadoCuenta($criadorId, ['estado' => 'activa'], $adminId);
        self::assertSame([], $reactivar['errores']);
        self::assertSame('activa', $reactivar['cuenta']['estado']);
        self::assertNull($reactivar['cuenta']['fecha_cierre']);

        $loginReactivado = $this->servicio->autenticar([
            'correo' => 'lucia.rf005@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
        ]);
        self::assertSame([], $loginReactivado['errores']);
    }

    public function testCerrarCuentaGuardaEstadoCerradaYFechaCierreEImpideInicioDeSesion(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Pedro Cierre',
            'correo' => 'pedro.cierre@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $criadorId = (int) $registro['usuario_id'];
        $criaderoId = (int) $registro['criadero_id'];

        $adminId = $this->crearAdminDePrueba('admin.cierre@tucriadero.cl');
        $_SESSION['usuario_id'] = $adminId;
        $_SESSION['rol'] = 'admin';

        $resultado = $this->servicio->cambiarEstadoCuenta($criadorId, ['estado' => 'cerrada'], $adminId);

        self::assertSame([], $resultado['errores']);
        self::assertSame('cerrada', $resultado['cuenta']['estado']);
        self::assertNotNull($resultado['cuenta']['fecha_cierre'], 'POL-001 / DAT-002: guarda fecha_cierre al cerrar.');

        $fila = $this->pdo->query("SELECT estado, fecha_cierre FROM usuario WHERE id = {$criadorId}")->fetch();
        self::assertSame('cerrada', $fila['estado']);
        self::assertNotNull($fila['fecha_cierre']);

        // No borra los datos del criadero (se conservan 90 días para la tarea de retención)
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM criadero WHERE id = {$criaderoId}")->fetchColumn());

        // Impide inicio de sesión con error _general
        $login = $this->servicio->autenticar([
            'correo' => 'pedro.cierre@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
        ]);
        self::assertArrayHasKey('_general', $login['errores']);
        self::assertStringContainsString('cerrada', $login['errores']['_general']);
        self::assertArrayNotHasKey('usuario', $login);

        // Auditoría registrada (SEG-003)
        $stmt = $this->pdo->prepare(
            "SELECT usuario_id, accion FROM auditoria WHERE accion = 'cerrar' AND entidad = 'usuario' AND entidad_id = ?"
        );
        $stmt->execute([$criadorId]);
        $audit = $stmt->fetch();
        self::assertIsArray($audit);
        self::assertSame($adminId, (int) $audit['usuario_id']);
    }

    public function testCriadorQueEntraARutaDeAdminCuentasRecibe403(): void
    {
        $_SESSION['usuario_id'] = 1001;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = 1001;
        $_SESSION['nombre'] = 'Criador Común';

        try {
            (new AdminCuentaController())->estado('1');
            self::fail('Un criador que intenta acceder a admin.cuentas.estado debe recibir 403.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }
    }

    public function testAdminNoRecibeDatosDeCriaderoEnListadoNiEnRespuestaDeEstado(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Raul Privado',
            'correo' => 'raul.privado@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $criadorId = (int) $registro['usuario_id'];

        $cuentas = $this->servicio->listarCuentasParaAdmin();
        self::assertNotEmpty($cuentas);

        $encontrada = null;
        foreach ($cuentas as $cuenta) {
            // Exactamente las claves del contrato (§6): id, nombre, correo, estado, fecha_registro
            self::assertSame(['id', 'nombre', 'correo', 'estado', 'fecha_registro'], array_keys($cuenta));
            self::assertArrayNotHasKey('criadero_id', $cuenta);
            self::assertArrayNotHasKey('canarios', $cuenta);
            self::assertArrayNotHasKey('jaulas', $cuenta);
            self::assertArrayNotHasKey('cruces', $cuenta);
            if ($cuenta['id'] === $criadorId) {
                $encontrada = $cuenta;
            }
        }
        self::assertNotNull($encontrada);

        $adminId = $this->crearAdminDePrueba('admin.privado@tucriadero.cl');
        $cambio = $this->servicio->cambiarEstadoCuenta($criadorId, ['estado' => 'desactivada'], $adminId);
        self::assertSame([], $cambio['errores']);
        self::assertArrayNotHasKey('criadero_id', $cambio['cuenta']);
        self::assertArrayNotHasKey('canarios', $cambio['cuenta']);
    }

    public function testCambiarEstadoRechazaEstadoInvalidoCuentaInexistenteOAdmin(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Sofia Criadora',
            'correo' => 'sofia.estado@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $criadorId = (int) $registro['usuario_id'];
        $adminId = $this->crearAdminDePrueba('admin.validaciones@tucriadero.cl');
        $auditoriaAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        $invalido = $this->servicio->cambiarEstadoCuenta($criadorId, ['estado' => 'eliminada'], $adminId);
        self::assertArrayHasKey('estado', $invalido['errores']);

        $inexistente = $this->servicio->cambiarEstadoCuenta(999999, ['estado' => 'desactivada'], $adminId);
        self::assertArrayHasKey('_general', $inexistente['errores']);
        self::assertTrue($inexistente['no_encontrado'] ?? false);

        $sobreAdmin = $this->servicio->cambiarEstadoCuenta($adminId, ['estado' => 'desactivada'], $adminId);
        self::assertArrayHasKey('_general', $sobreAdmin['errores']);

        // Nada cambió ni se registró auditoría en los casos rechazados
        self::assertSame('activa', $this->pdo->query("SELECT estado FROM usuario WHERE id = {$criadorId}")->fetchColumn());
        self::assertSame('activa', $this->pdo->query("SELECT estado FROM usuario WHERE id = {$adminId}")->fetchColumn());
        self::assertSame($auditoriaAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
    }

//    public function testRutaAdminCuentasEstadoEstaRegistradaSegunContrato(): void
//    {
//        $router = Aplicacion::crearRouter();
//
//        self::assertSame('/admin/cuentas/42/estado', $router->url('admin.cuentas.estado', ['id' => 42]));
//    }
    public function testRutaAdminCuentasEstadoEstaRegistradaSegunContrato(): void
    {
        $router = Aplicacion::crearRouter();

        self::assertSame('/admin/cuentas/42/estado', $router->url('admin.cuentas.estado', ['id' => 42]));
    }

    // -------------------------------------------------------------------------
    // RF006: Listado de cuentas (administrador)
    // -------------------------------------------------------------------------

    public function testRutaAdminCuentasEstaRegistradaSegunContrato(): void
    {
        $router = Aplicacion::crearRouter();

        self::assertSame('/admin/cuentas', $router->url('admin.cuentas'));
    }

    public function testCriadorQueEntraAAdminCuentasIndexRecibe403(): void
    {
        $_SESSION['usuario_id'] = 1001;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = 1001;
        $_SESSION['nombre'] = 'Criador Común';

        try {
            (new AdminCuentaController())->index();
            self::fail('Un criador que intenta acceder a admin.cuentas debe recibir 403.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->codigo());
        }
    }

    public function testListarCuentasParaAdminEntregaCamposDelContratoSinDatosDeCriadero(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Clara Criadora',
            'correo' => 'clara.rf006@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $criadorId = (int) $registro['usuario_id'];

        $cuentas = $this->servicio->listarCuentasParaAdmin();
        self::assertNotEmpty($cuentas);

        $encontrada = null;
        foreach ($cuentas as $cuenta) {
            self::assertSame(['id', 'nombre', 'correo', 'estado', 'fecha_registro'], array_keys($cuenta));
            self::assertArrayNotHasKey('criadero_id', $cuenta);
            self::assertArrayNotHasKey('canarios', $cuenta);
            self::assertArrayNotHasKey('jaulas', $cuenta);
            self::assertArrayNotHasKey('cruces', $cuenta);
            if ($cuenta['id'] === $criadorId) {
                $encontrada = $cuenta;
            }
        }

        self::assertNotNull($encontrada);
        self::assertSame('Clara Criadora', $encontrada['nombre']);
        self::assertSame('clara.rf006@tucriadero.cl', $encontrada['correo']);
        self::assertSame('activa', $encontrada['estado']);
    }

    private function crearAdminDePrueba(string $correo): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado)
             VALUES ('Admin Prueba', ?, ?, 'admin', 'activa')"
        );
        $stmt->execute([$correo, password_hash('AdminClave2026', PASSWORD_DEFAULT)]);

        return (int) $this->pdo->lastInsertId();
    }

    public function testDesactivarCortaLaSesionQueElCriadorYaTeniaAbierta(): void
    {
        $registro = $this->servicio->registrar([
            'nombre' => 'Criador Conectado',
            'correo' => 'conectado@tucriadero.cl',
            'contrasena' => 'ClaveValida2026',
            'consentimiento' => '1',
        ]);
        $_SESSION['usuario_id'] = $registro['usuario_id'];
        $_SESSION['rol'] = 'criador';

        // Mientras la cuenta está activa, la sesión sigue.
        Aplicacion::cerrarSesionSiLaCuentaNoEstaActiva();
        self::assertSame($registro['usuario_id'], $_SESSION['usuario_id'] ?? null);

        $this->servicio->cambiarEstadoCuenta((int) $registro['usuario_id'], ['estado' => 'desactivada']);
        Aplicacion::cerrarSesionSiLaCuentaNoEstaActiva();

        self::assertArrayNotHasKey('usuario_id', $_SESSION);
        self::assertArrayNotHasKey('rol', $_SESSION);
    }

    /** Un campo enviado como lista (`nombre[]=x`) cuenta como vacío: error del campo, sin avisos de PHP ni «Array» guardado. */
    public function testCamposEnviadosComoListaSeRechazanSinGuardar(): void
    {
        $resultado = $this->servicio->registrar(['nombre' => ['Ana'], 'correo' => ['ana@tucriadero.cl'], 'contrasena' => ['x'], 'consentimiento' => '1']);

        self::assertSame(['nombre', 'correo', 'contrasena'], array_keys($resultado['errores']));
        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM usuario WHERE nombre = 'Array'")->fetchColumn());
        self::assertArrayHasKey('_general', $this->servicio->autenticar(['correo' => ['a@b.cl'], 'contrasena' => 'ClaveValida2026'])['errores']);
    }

    /** Un byte nulo en la contraseña es un error del campo, no un 500 (bcrypt no lo admite en PHP 8.3). */
    public function testContrasenaConByteNuloSeRechaza(): void
    {
        $resultado = $this->servicio->registrar(['nombre' => 'Ana Criadora', 'correo' => 'ana.nulo@tucriadero.cl',
            'contrasena' => "jaula\0patio-norte", 'consentimiento' => '1']);

        self::assertArrayHasKey('contrasena', $resultado['errores']);
    }

    /** NIST SP 800-63B: sin solo espacios, contraseñas comunes, secuencias, repetidas ni el correo o el nombre. */
    public function testContrasenasFacilesDeAdivinarSeRechazanYUnaFraseSeAcepta(): void
    {
        // Pares [clave, motivo]: una clave numérica como índice de arreglo se volvería un entero.
        $casos = [
            ['        ', 'sin contar los espacios'],
            ['12345678', 'muy común'],
            ['87654321', 'muy común'],
            ['aaaaaaaa', 'muy común'],
            ['Password123', 'muy común'],
            ['contraseña', 'muy común'],
            ['ana.maria@tucriadero.cl', 'correo ni tu nombre'],
            ['clave anamaria 26', 'correo ni tu nombre'],
        ];
        foreach ($casos as [$clave, $motivo]) {
            $resultado = $this->servicio->registrar(['nombre' => 'Ana María', 'correo' => 'ana.maria@tucriadero.cl',
                'contrasena' => $clave, 'consentimiento' => '1']);
            self::assertStringContainsString($motivo, $resultado['errores']['contrasena'] ?? '', "Debió rechazar «{$clave}».");
        }

        $frase = $this->servicio->registrar(['nombre' => 'Ana María', 'correo' => 'ana.maria@tucriadero.cl',
            'contrasena' => 'mi jaula del patio', 'consentimiento' => '1']);
        self::assertSame([], $frase['errores']);
    }

    /** Compatibilidad con vistas de registro que envían `consentimiento=si` (RF001 / #71). */
    public function testRegistroAceptaConsentimientoSi(): void
    {
        $resultado = $this->servicio->registrar([
            'nombre' => 'Lucía Criadora',
            'correo' => 'lucia.si@tucriadero.cl',
            'contrasena' => 'mi jaula del sur',
            'consentimiento' => 'si',
        ]);

        self::assertSame([], $resultado['errores']);
        self::assertArrayHasKey('usuario_id', $resultado);
    }
}
