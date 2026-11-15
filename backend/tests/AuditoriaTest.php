<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Conexion;

final class AuditoriaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('CREATE TABLE auditoria (id INTEGER PRIMARY KEY, usuario_id INTEGER NULL, accion TEXT NOT NULL,
            entidad TEXT NOT NULL, entidad_id INTEGER NOT NULL, fecha_hora TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
        Conexion::usar($this->pdo);
    }

    protected function tearDown(): void
    {
        Conexion::usar(null);
        unset($_SESSION['usuario_id']);
    }

    public function testDejaUnaFilaConElUsuarioDeLaSesionYLaFecha(): void
    {
        $_SESSION['usuario_id'] = 14;

        \auditar('crear', 'canario', 7);

        $fila = $this->pdo->query('SELECT usuario_id, accion, entidad, entidad_id, fecha_hora FROM auditoria')->fetch(PDO::FETCH_ASSOC);
        self::assertSame([14, 'crear', 'canario', 7], [(int) $fila['usuario_id'], $fila['accion'], $fila['entidad'], (int) $fila['entidad_id']]);
        self::assertNotEmpty($fila['fecha_hora']);
    }

    public function testSinSesionRegistraLaAccionSinUsuario(): void
    {
        \auditar('crear', 'usuario', 3);

        self::assertNull($this->pdo->query('SELECT usuario_id FROM auditoria')->fetchColumn());
    }
}
