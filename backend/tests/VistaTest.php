<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Rutas;
use TuCriadero\Core\Sesion;
use TuCriadero\Core\Vista;

final class VistaTest extends TestCase
{
    protected function setUp(): void
    {
        Vista::definirRaiz(dirname(__DIR__, 2));
        Rutas::definir(Aplicacion::crearRouter());
        Sesion::iniciar();
    }

    public function testPuedeDevolverElHtmlDeUnaVista(): void
    {
        $html = \render('inicio/pdf', [], true);

        self::assertIsString($html);
        self::assertStringContainsString('TuCriadero funciona', $html);
    }
}
