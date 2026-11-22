<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Aplicacion;

final class CabecerasSeguridadTest extends TestCase
{
    public function testTodaRespuestaLlevaLasCabecerasDeSeguridad(): void
    {
        $cabeceras = Aplicacion::cabecerasDeSeguridad();

        self::assertSame('DENY', $cabeceras['X-Frame-Options']);
        self::assertSame("frame-ancestors 'none'", $cabeceras['Content-Security-Policy']);
        self::assertSame('nosniff', $cabeceras['X-Content-Type-Options']);
        self::assertSame('same-origin', $cabeceras['Referrer-Policy']);
    }
}
