<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\{Aplicacion, Rutas, Router, Ruta, Sesion, Vista};

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class VistaUbicacionTest extends TestCase
{
    protected function setUp(): void
    {
        Vista::definirRaiz(dirname(__DIR__, 2));
        Sesion::iniciarAutenticada(1001, 'criador', 1001, 'Ana Prueba');
        // Solo permite renderizar el action del formulario: no simula la implementación de #104.
        $propiedad = new \ReflectionProperty(Router::class, 'rutas');
        $rutas = $propiedad->getValue(Aplicacion::crearRouter());
        $rutas[] = new Ruta('canarios.ubicacion.guardar', 'POST', '/canarios/{id}/ubicacion', '', '');
        Rutas::definir(new Router($rutas));
    }

    private function datos(): array
    {
        return ['canario' => [
            'id' => 12, 'anillo_texto' => '7 · 2024 · 150 · CLUB', 'sexo' => 'macho',
            'variedad' => ['codigo' => 'D.049', 'nombre' => 'ROJO INTENSO'],
            'ubicacion_texto' => 'Jaula 25',
        ], 'jaulas' => [['id' => 1, 'numero' => 25, 'ocupantes' => 2]]];
    }

    public function testUsaLaVariedadYUbicacionDelContratoSinAvisos(): void
    {
        $html = (string) render('canarios/ubicacion', $this->datos(), true);
        self::assertStringContainsString('ROJO INTENSO', $html);
        self::assertStringContainsString('>Jaula 25</span>', $html);
        self::assertStringNotContainsString('>Array<', $html);
        self::assertStringContainsString('action="/canarios/12/ubicacion"', $html);
        self::assertStringContainsString('href="/css/canarios-ubicacion.css"', $html);
        self::assertStringContainsString('aria-label="Mi perfil">AP</a>', $html);
        self::assertStringNotContainsString('/frontend/css/', $html);
        self::assertStringNotContainsString('/assets/', $html);
    }

    public function testNoInventaUbicacionCuandoNoHayVariedadNiUbicacion(): void
    {
        $datos = $this->datos();
        $datos['canario']['variedad'] = null;
        $datos['canario']['ubicacion_texto'] = 'Sin ubicación';
        $html = (string) render('canarios/ubicacion', $datos, true);
        self::assertStringContainsString('>Sin ubicación</span>', $html);
        self::assertStringNotContainsString('>Array<', $html);
    }

    public function testEscapaDatosYConservaLosDosErroresYValores(): void
    {
        $datos = $this->datos();
        $datos['canario']['variedad']['nombre'] = '<script>alert(1)</script>';
        $datos['errores'] = ['jaula_id' => 'Jaula ajena', 'fecha' => 'Fecha inválida'];
        $datos['viejo'] = ['jaula_id' => '1', 'fecha' => '2026-11-21'];
        $html = (string) render('canarios/ubicacion', $datos, true);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('Jaula ajena', $html);
        self::assertStringContainsString('Fecha inválida', $html);
        self::assertStringContainsString('value="2026-11-21"', $html);
        self::assertMatchesRegularExpression('/value="1"\s+selected/', $html);
        self::assertStringContainsString('name="_csrf" value="' . Sesion::csrf() . '"', $html);
    }
}
