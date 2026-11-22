<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Sesion;

final class SesionTest extends TestCase
{
    public function testIniciaUnaSesionAutenticadaConLosDatosDelContrato(): void
    {
        Sesion::iniciarAutenticada(14, 'criador', 8, 'Ana');

        self::assertSame(14, $_SESSION['usuario_id']);
        self::assertSame('criador', $_SESSION['rol']);
        self::assertSame(8, $_SESSION['criadero_id']);
        self::assertSame('Ana', $_SESSION['nombre']);
    }

    public function testCerrarUnaSesionEliminaLosDatosAutenticados(): void
    {
        Sesion::iniciarAutenticada(14, 'admin', null);
        Sesion::cerrarAutenticada();

        self::assertArrayNotHasKey('usuario_id', $_SESSION);
        self::assertArrayNotHasKey('rol', $_SESSION);
        self::assertArrayNotHasKey('criadero_id', $_SESSION);
        self::assertArrayHasKey('csrf', $_SESSION);
    }

    public function testLaSesionDuraSieteDiasDeInactividad(): void
    {
        Sesion::iniciar();

        self::assertSame(604800, Sesion::DURACION_MAXIMA);
        self::assertSame('604800', ini_get('session.gc_maxlifetime'));
        self::assertSame(604800, session_get_cookie_params()['lifetime']);
    }

    public function testLaSesionSoloAceptaIdsCreadosPorElServidor(): void
    {
        Sesion::iniciar();

        self::assertSame('1', ini_get('session.use_strict_mode'));
        self::assertSame('1', ini_get('session.use_only_cookies'));
    }
}
