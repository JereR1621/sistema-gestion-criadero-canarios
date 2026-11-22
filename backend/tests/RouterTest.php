<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Aplicacion;
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

    public function testRouterDeAplicacionIncluyeRaizYRutasCompletasDeCatalogoAdmin(): void
    {
        $router = Aplicacion::crearRouter();

        self::assertSame('/', $router->url('raiz'));
        self::assertSame('/inicio', $router->url('inicio'));
        self::assertSame('/admin/variedades/nueva', $router->url('admin.variedades.nueva'));
        self::assertSame('/admin/variedades', $router->url('admin.variedades.crear'));
        self::assertSame('/admin/variedades/D.049/eliminar', $router->url('admin.variedades.eliminar', ['codigo' => 'D.049']));
        self::assertSame('/admin/factores/5', $router->url('admin.factores.editar', ['id' => 5]));
        self::assertSame('/admin/factores/5', $router->url('admin.factores.actualizar', ['id' => 5]));
    }
}

final class ControladorFalso
{
    public function mostrar(): void
    {
    }
}
