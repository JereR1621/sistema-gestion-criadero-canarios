<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Conexion;
use TuCriadero\Services\CuentaService;

final class CuentaServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE usuario (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                correo TEXT NOT NULL UNIQUE,
                contrasena_hash TEXT NOT NULL,
                rol TEXT NOT NULL DEFAULT 'criador',
                estado TEXT NOT NULL DEFAULT 'activa',
                fecha_registro TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_consentimiento TEXT NULL,
                fecha_cierre TEXT NULL,
                numero_socio TEXT NULL,
                club TEXT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE criadero (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL UNIQUE
            )
        ");

        $this->pdo->exec("
            CREATE TABLE auditoria (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NULL,
                accion TEXT NOT NULL,
                entidad TEXT NOT NULL,
                entidad_id INTEGER NOT NULL,
                fecha_hora TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        Conexion::usar($this->pdo);
    }

    protected function tearDown(): void
    {
        Conexion::usar(null);
    }

    public function testCreaCuentaDeCriadorConCriaderoPropioYVacio(): void
    {
        $servicio = new CuentaService();

        $resultado = $servicio->registrar([
            'nombre' => 'Ana Pérez',
            'correo' => 'Ana@Ejemplo.com',
            'contrasena' => 'ClaveSegura2026',
            'consentimiento' => '1',
        ]);

        self::assertSame([], $resultado['errores']);
        self::assertGreaterThan(0, $resultado['usuario_id']);
        self::assertGreaterThan(0, $resultado['criadero_id']);

        $usuario = $this->pdo->query('SELECT * FROM usuario WHERE id = ' . (int) $resultado['usuario_id'])->fetch();
        self::assertSame('Ana Pérez', $usuario['nombre']);
        self::assertSame('ana@ejemplo.com', $usuario['correo']);
        self::assertSame('criador', $usuario['rol']);
        self::assertSame('activa', $usuario['estado']);
        self::assertNotEmpty($usuario['fecha_consentimiento']);
        self::assertTrue(password_verify('ClaveSegura2026', $usuario['contrasena_hash']));

        $criadero = $this->pdo->query('SELECT * FROM criadero WHERE id = ' . (int) $resultado['criadero_id'])->fetch();
        self::assertSame((int) $resultado['usuario_id'], (int) $criadero['usuario_id']);

        $auditorias = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();
        self::assertSame(2, $auditorias);
    }

    public function testRechazaContrasenaDeMenosDeOchoCaracteres(): void
    {
        $servicio = new CuentaService();

        $resultado = $servicio->registrar([
            'nombre' => 'Ana Pérez',
            'correo' => 'ana@ejemplo.com',
            'contrasena' => '1234567',
            'consentimiento' => '1',
        ]);

        self::assertArrayHasKey('contrasena', $resultado['errores']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM criadero')->fetchColumn());
    }

    public function testRechazaCorreoYaRegistrado(): void
    {
        $servicio = new CuentaService();

        $servicio->registrar([
            'nombre' => 'Ana Pérez',
            'correo' => 'ana@ejemplo.com',
            'contrasena' => 'ClaveSegura2026',
            'consentimiento' => '1',
        ]);

        $resultado = $servicio->registrar([
            'nombre' => 'Otra Persona',
            'correo' => 'ANA@ejemplo.com',
            'contrasena' => 'OtraClave2026',
            'consentimiento' => '1',
        ]);

        self::assertArrayHasKey('correo', $resultado['errores']);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
    }

    public function testRechazaCamposObligatoriosFaltantes(): void
    {
        $servicio = new CuentaService();

        $resultado = $servicio->registrar([
            'nombre' => '',
            'correo' => '',
            'contrasena' => '',
            'consentimiento' => '1',
        ]);

        self::assertArrayHasKey('nombre', $resultado['errores']);
        self::assertArrayHasKey('correo', $resultado['errores']);
        self::assertArrayHasKey('contrasena', $resultado['errores']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
    }

    public function testRechazaRegistroSinConsentimiento(): void
    {
        $servicio = new CuentaService();

        $resultado = $servicio->registrar([
            'nombre' => 'Ana Pérez',
            'correo' => 'ana@ejemplo.com',
            'contrasena' => 'ClaveSegura2026',
        ]);

        self::assertSame(
            'Para crear la cuenta debes aceptar el uso de tus datos',
            $resultado['errores']['consentimiento'] ?? null
        );
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM criadero')->fetchColumn());
    }
}