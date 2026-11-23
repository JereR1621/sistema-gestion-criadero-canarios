<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use TuCriadero\Core\{Aplicacion, Router, Rutas, Sesion, Vista};

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class FrontendBaseTest extends TestCase
{
    protected function setUp(): void
    {
        Vista::definirRaiz(dirname(__DIR__, 2));
        Rutas::definir(Aplicacion::crearRouter());
        Sesion::iniciarAutenticada(1001, 'criador', 1001, 'Ana Prueba');
    }

    public function testMarcoCargaEstilosAprobadosHtmxYScriptsPropios(): void
    {
        $raiz = dirname(__DIR__, 2);
        self::assertFileExists($raiz . '/public/js/htmx.min.js');
        self::assertFileExists($raiz . '/public/js/tucri.js');
        foreach (['tucriadero.css', 'tema.css', 'mejoras.css', 'tablero.css', 'local.css'] as $css) {
            self::assertFileExists($raiz . '/frontend/css/' . $css);
        }

        $titulo = 'Prueba';
        $seccion = 'jaulas';
        $contenido = '<p>Contenido seguro</p>';
        $sesion = Sesion::paraVista();
        $csrf = Sesion::csrf();
        $flash = ['tipo' => 'exito', 'mensaje' => '<script>alert(1)</script>'];

        ob_start();
        require $raiz . '/frontend/views/_partes/marco.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('src="/js/htmx.min.js"', $html);
        self::assertStringContainsString('src="/js/tucri.js"', $html);
        self::assertStringContainsString('href="/css/tucriadero.css"', $html);
        self::assertStringContainsString('href="/css/tablero.css"', $html);
        self::assertStringContainsString('>AP</span>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('aria-label="Navegación sin JavaScript"', $html);
        self::assertStringContainsString('class="sin-js fallback-nav"', $html);
        self::assertStringContainsString('action="/salir"', $html);
        self::assertStringNotContainsString('Fotografías y créditos', $html);
    }

    public function testVistaIngresoIncluyeFormularioSeguroErroresYCorreoConservado(): void
    {
        $sesionAntes = $_SESSION ?? null;
        $_SESSION = [];

        Vista::guardarFlash(['tipo' => 'exito', 'mensaje' => 'Has cerrado sesión.']);
        $html = Vista::renderizar('cuentas/ingresar', [
            'errores' => [
                '_general' => 'Correo o contraseña incorrectos',
                'correo' => 'Revisa el correo',
            ],
            'viejo' => ['correo' => 'ana<script>@ejemplo.com'],
        ], true);

        if ($sesionAntes === null) {
            $_SESSION = [];
        } else {
            $_SESSION = $sesionAntes;
        }

        self::assertNotNull($html);
        self::assertStringContainsString('Has cerrado sesión.', $html);
        self::assertStringNotContainsString('/css/creditos.html', $html);
        self::assertStringContainsString('method="post" action="/ingresar"', $html);
        self::assertStringContainsString('name="correo"', $html);
        self::assertStringContainsString('name="contrasena"', $html);
        self::assertStringContainsString('name="_csrf"', $html);
        self::assertStringContainsString('Correo o contraseña incorrectos', $html);
        self::assertStringContainsString('Revisa el correo', $html);
        self::assertStringContainsString('ana&lt;script&gt;@ejemplo.com', $html);
        self::assertStringNotContainsString('ana<script>@ejemplo.com', $html);
        self::assertStringContainsString('href="/registro"', $html);
    }

    public function testPaginasDeErrorUsanLayoutComunYEscapanTitulo(): void
    {
        $sesionAntes = $_SESSION ?? null;

        foreach ([
            'errores/403' => ['codigo' => '403', 'texto' => 'No tienes permiso'],
            'errores/404' => ['codigo' => '404', 'texto' => 'La página que buscas'],
            'errores/500' => ['codigo' => '500', 'texto' => 'Inténtalo nuevamente'],
            'errores/sesion' => ['codigo' => '419', 'texto' => 'Volver a ingresar'],
            'errores/mantenimiento' => ['codigo' => '503', 'texto' => 'mantenimiento'],
        ] as $vista => $esperado) {
            $_SESSION = [];
            $html = Vista::renderizar($vista, [
                'titulo' => 'Título <script>alert(1)</script>',
                'mensaje' => 'Mensaje <b>de prueba</b>',
            ], true);

            self::assertNotNull($html);
            self::assertStringContainsString('TuCri', $html);
            self::assertStringContainsString('href="/css/tucriadero.css"', $html);
            self::assertStringContainsString($esperado['codigo'], $html);
            self::assertStringContainsString($esperado['texto'], $html);
            self::assertStringContainsString('Título &lt;script&gt;alert(1)&lt;/script&gt;', $html);
            self::assertStringNotContainsString('<script>alert(1)</script>', $html);
            self::assertStringNotContainsString('/css/app.css', $html);
        }

        if ($sesionAntes === null) {
            $_SESSION = [];
        } else {
            $_SESSION = $sesionAntes;
        }
    }

    public function testError500NoDebeRevelarMensajeTecnico(): void
    {
        $html = Vista::renderizar('errores/500', [
            'titulo' => 'Error inesperado',
            'mensaje' => 'PDOException: password=secreto',
        ], true);

        self::assertNotNull($html);
        self::assertStringContainsString('No fue posible completar la solicitud', $html);
        self::assertStringNotContainsString('PDOException', $html);
        self::assertStringNotContainsString('secreto', $html);
    }

    public function testMarcoNoFallaCuandoFaltanRutasDeModulosPendientes(): void
    {
        $propiedad = new \ReflectionProperty(Router::class, 'rutas');
        $rutas = array_values(array_filter(
            $propiedad->getValue(Aplicacion::crearRouter()),
            static fn ($ruta): bool => !in_array($ruta->nombre, ['cruces', 'buscar', 'calculadora'], true)
        ));
        Rutas::definir(new Router($rutas));

        $titulo = 'Jaulas';
        $seccion = 'jaulas';
        $contenido = '<p>OK</p>';
        $sesion = Sesion::paraVista();
        $csrf = Sesion::csrf();
        $flash = null;

        ob_start();
        require dirname(__DIR__, 2) . '/frontend/views/_partes/marco.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('<p>OK</p>', $html);
        self::assertStringNotContainsString('action="/buscar"', $html);
    }
}
