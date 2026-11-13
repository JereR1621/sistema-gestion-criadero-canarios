<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TuCriadero\Core\Conexion;

final class ConexionTest extends TestCase
{
    protected function tearDown(): void
    {
        Conexion::usar(null);
    }

    public function testLosModelosRecibenSiempreLaMismaConexion(): void
    {
        $pdo = new PDO('sqlite::memory:');
        Conexion::usar($pdo);

        self::assertSame($pdo, Conexion::obtener());
        self::assertSame(Conexion::obtener(), Conexion::obtener());
    }

    public function testSinConfiguracionDeLaBaseDaUnErrorClaro(): void
    {
        $anterior = getenv('DB_NAME');
        $anteriorEnv = $_ENV['DB_NAME'] ?? null;
        $anteriorServer = $_SERVER['DB_NAME'] ?? null;
        putenv('DB_NAME=');
        $_ENV['DB_NAME'] = '';
        unset($_SERVER['DB_NAME']);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Falta la variable de entorno');
            Conexion::obtener();
        } finally {
            $anterior === false ? putenv('DB_NAME') : putenv("DB_NAME={$anterior}");
            if ($anteriorEnv === null) {
                unset($_ENV['DB_NAME']);
            } else {
                $_ENV['DB_NAME'] = $anteriorEnv;
            }
            if ($anteriorServer !== null) {
                $_SERVER['DB_NAME'] = $anteriorServer;
            }
        }
    }
}
