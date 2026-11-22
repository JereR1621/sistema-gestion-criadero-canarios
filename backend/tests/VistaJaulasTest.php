<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\{Aplicacion, Rutas, Router, Sesion, Vista};

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class VistaJaulasTest extends TestCase
{
    protected function setUp(): void
    {
        Vista::definirRaiz(dirname(__DIR__, 2));
        // Reproduce módulos pendientes incluso cuando esas rutas se integren en el futuro.
        $propiedad = new \ReflectionProperty(Router::class, 'rutas');
        $rutas = array_values(array_filter(
            $propiedad->getValue(Aplicacion::crearRouter()),
            static fn ($ruta): bool => !in_array($ruta->nombre, ['cruces', 'buscar'], true)
        ));
        Rutas::definir(new Router($rutas));
        Sesion::iniciarAutenticada(1001, 'criador', 1001, 'Ana Prueba');
    }

    public function testRenderizaSinDependerDeRutasDeModulosPendientes(): void
    {
        $html = (string) render('jaulas/lista', ['jaulas' => []], true);
        self::assertStringContainsString('Todavía no hay jaulas', $html);
        self::assertStringContainsString('href="/css/jaulas.css"', $html);
        self::assertStringContainsString('aria-label="Mi perfil">AP</a>', $html);
        self::assertStringNotContainsString('/frontend/css/', $html);
        self::assertStringNotContainsString('/assets/', $html);
        self::assertStringNotContainsString('action="/buscar"', $html);
    }

    public function testConservaErrorValorYCsrfDelFormulario(): void
    {
        $html = (string) render('jaulas/lista', [
            'jaulas' => [], 'errores' => ['numero' => 'Ya existe la jaula N.º 25'],
            'viejo' => ['numero' => '25'],
        ], true);
        self::assertStringContainsString('Ya existe la jaula N.º 25', $html);
        self::assertStringContainsString('value="25"', $html);
        self::assertStringContainsString('aria-describedby="error-numero"', $html);
        self::assertStringContainsString('name="_csrf" value="' . Sesion::csrf() . '"', $html);
    }

    public function testEscapaDatosDelListadoYFlash(): void
    {
        Vista::guardarFlash(['tipo' => 'exito', 'mensaje' => '<script>alert(1)</script>']);
        $html = (string) render('jaulas/lista', ['jaulas' => [[
            'id' => 1, 'numero' => '<img src=x>', 'ocupantes' => 0, 'cruce_activo' => null, 'url' => '',
        ]]], true);
        self::assertStringContainsString('&lt;img src=x&gt;', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
    }
}
