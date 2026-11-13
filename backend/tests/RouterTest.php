<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Ruta;
use TuCriadero\Core\Router;

final class RouterTest extends TestCase
{
    public function testGeneraUnaUrlConParametrosEscapados(): void
    {
        $router = new Router([
            new Ruta('canarios.ver', 'GET', '/canarios/{id}', ControladorFalso::class, 'mostrar'),
        ]);

        self::assertSame('/canarios/12%2F3', $router->url('canarios.ver', ['id' => '12/3']));
    }

    public function testRechazaUnNombreDeRutaDesconocido(): void
    {
        $router = new Router([]);

        $this->expectException(InvalidArgumentException::class);
        $router->url('no.existe');
    }
}

final class ControladorFalso
{
    public function mostrar(): void
    {
    }
}
