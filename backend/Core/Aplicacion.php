<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use PDO;
use Throwable;
use TuCriadero\Controllers\ErrorController;
use TuCriadero\Controllers\InicioController;

final class Aplicacion
{
    private function __construct(
        private readonly string $raiz,
        private readonly PDO $conexion,
        private readonly Router $router,
    ) {
    }

    public static function ejecutar(string $raiz): void
    {
        Vista::definirRaiz($raiz);

        try {
            Entorno::cargar($raiz . '/.env');
            Sesion::iniciar();

            $router = self::crearRouter();
            Rutas::definir($router);
            $aplicacion = new self($raiz, Conexion::crearDesdeEntorno(), $router);
            $aplicacion->manejar();
        } catch (HttpException $error) {
            ErrorController::mostrar($error->codigo(), $error->getMessage());
        } catch (Throwable $error) {
            // Nunca se expone un detalle técnico ni una credencial al navegador.
            error_log('TuCriadero: error no controlado (' . $error::class . ')');
            ErrorController::mostrar(500);
        }
    }

    public static function crearRouter(): Router
    {
        return new Router([
            new Ruta('inicio', 'GET', '/inicio', InicioController::class, 'mostrar'),
            new Ruta('inicio.pdf', 'GET', '/inicio/pdf', InicioController::class, 'descargarPdf'),
        ]);
    }

    private function manejar(): void
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $ruta = parse_url($uri, PHP_URL_PATH) ?: '/';

        if (str_starts_with($ruta, '/css/')) {
            $this->servirCss($ruta);
            return;
        }

        $this->router->despachar($metodo, $ruta);
    }

    private function servirCss(string $ruta): void
    {
        $relativa = rawurldecode(substr($ruta, strlen('/css/')));

        if ($relativa === '' || str_contains($relativa, '..') || str_contains($relativa, '\\')) {
            throw HttpException::noEncontrado();
        }

        $archivo = $this->raiz . '/frontend/css/' . $relativa;
        if (!is_file($archivo)) {
            throw HttpException::noEncontrado();
        }

        header('Content-Type: text/css; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        readfile($archivo);
    }
}
