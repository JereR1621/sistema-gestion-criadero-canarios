<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\{Conexion, Entorno};

final class ZonaHorariaTest extends TestCase
{
    public function testEntradasWebYClIEjecutanConFechaDeSantiago(): void
    {
        self::assertSame('America/Santiago', date_default_timezone_get());
        $zona = new DateTimeZone(date_default_timezone_get());
        self::assertSame('2026-11-22', (new DateTimeImmutable('2026-11-23T00:30:00Z'))->setTimezone($zona)->format('Y-m-d'));
        self::assertSame('2026-06-30', (new DateTimeImmutable('2026-07-01T03:30:00Z'))->setTimezone($zona)->format('Y-m-d'));
        self::assertSame('2026-01-01', (new DateTimeImmutable('2026-01-01T03:30:00Z'))->setTimezone($zona)->format('Y-m-d'));
    }

    public function testConexionMysqlUsaElDesfaseVigenteYLaMismaFecha(): void
    {
        Entorno::cargar(dirname(__DIR__, 2) . '/.env');
        if (Entorno::obtener('DB_HOST') === null || Entorno::obtener('DB_NAME_PRUEBAS') === null) {
            self::markTestSkipped('Requiere MySQL y DB_NAME_PRUEBAS.');
        }
        $anterior = $_ENV['DB_NAME'] ?? null;
        $_ENV['DB_NAME'] = Entorno::obtener('DB_NAME_PRUEBAS');
        try {
            $pdo = Conexion::crearDesdeEntorno();
            $fila = $pdo->query("SELECT @@session.time_zone AS zona, UNIX_TIMESTAMP() AS instante, DATE_FORMAT(NOW(), '%Y-%m-%d') AS fecha")->fetch();
            $instante = (new DateTimeImmutable('@' . $fila['instante']))->setTimezone(new DateTimeZone('America/Santiago'));
            self::assertSame($instante->format('P'), $fila['zona']);
            self::assertSame($instante->format('Y-m-d'), $fila['fecha']);
            self::assertLessThanOrEqual(2, abs(time() - (int) $fila['instante']));
        } finally {
            if ($anterior === null) {
                unset($_ENV['DB_NAME']);
            } else {
                $_ENV['DB_NAME'] = $anterior;
            }
        }
    }
}
