<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use Throwable;
use TuCriadero\Controllers\ErrorController;
use TuCriadero\Controllers\InicioController;
use TuCriadero\Controllers\CuentaController;

final class Aplicacion
{
    private function __construct(
        private readonly string $raiz,
        private readonly Router $router,
    ) {
    }

    public static function ejecutar(string $raiz): void
    {
        // Hasta saber el entorno, ningún error se muestra en el navegador (contrato §7).
        ini_set('display_errors', '0');
        Vista::definirRaiz($raiz);

        try {
            Entorno::cargar($raiz . '/.env');
            if (Entorno::obtener('APP_ENV') === 'local') {
                ini_set('display_errors', '1');
            }

            Sesion::iniciar();

            $router = self::crearRouter();
            Rutas::definir($router);
            // La conexión a MySQL no se abre aquí: los modelos la piden con Conexion::obtener().
            (new self($raiz, $router))->manejar();
        } catch (HttpException $error) {
            self::mostrarError($error->codigo(), $error->getMessage());
        } catch (Throwable $error) {
            self::registrar($error);
            self::mostrarError(500);
        }
    }

    public static function crearRouter(): Router
    {
        return new Router([
            new Ruta('inicio', 'GET', '/inicio', InicioController::class, 'mostrar'),
            new Ruta('inicio.pdf', 'GET', '/inicio/pdf', InicioController::class, 'descargarPdf'),

            new Ruta('registro', 'GET', '/registro', CuentaController::class, 'registro'),
            new Ruta('registro.guardar', 'POST', '/registro', CuentaController::class, 'registroGuardar'),

            new Ruta('ingreso', 'GET', '/ingresar', CuentaController::class, 'ingreso'),
            new Ruta('ingreso.enviar', 'POST', '/ingresar', CuentaController::class, 'ingresoEnviar'),
            new Ruta('salida', 'POST', '/salir', CuentaController::class, 'salida'),
        ]);
    }

    /**
     * Todo formulario que envía datos lleva el campo oculto `_csrf` (contrato §4).
     * Si falta o no coincide con el de la sesión, se responde 419 (contrato §7)
     * antes de llegar al controlador.
     */
    public static function comprobarCsrf(string $metodo, array $post): void
    {
        if ($metodo === 'GET' || $metodo === 'HEAD') {
            return;
        }

        if (!Sesion::csrfValido($post['_csrf'] ?? null)) {
            throw HttpException::sesionExpirada();
        }
    }

    private function manejar(): void
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $ruta = parse_url($uri, PHP_URL_PATH) ?: '/';

        self::enviarCabecerasDeSeguridad();

        if (str_starts_with($ruta, '/css/')) {
            $this->servirCss($ruta);
            return;
        }

        self::comprobarCsrf($metodo, $_POST);
        $this->router->despachar($metodo, $ruta);
    }

    /**
     * Cabeceras que el navegador aplica a toda respuesta (SEG-005, OWASP).
     *
     * @return array<string, string>
     */
    public static function cabecerasDeSeguridad(): array
    {
        return [
            // Ningún otro sitio puede mostrar TuCriadero dentro de un marco (clickjacking).
            'X-Frame-Options' => 'DENY',
            'Content-Security-Policy' => "frame-ancestors 'none'",
            // El navegador no adivina el tipo de archivo: usa el Content-Type que enviamos.
            'X-Content-Type-Options' => 'nosniff',
            // Al seguir un enlace externo, no se envía la URL interna de la que viene.
            'Referrer-Policy' => 'same-origin',
        ];
    }

    private static function enviarCabecerasDeSeguridad(): void
    {
        if (headers_sent()) {
            return;
        }

        foreach (self::cabecerasDeSeguridad() as $nombre => $valor) {
            header("{$nombre}: {$valor}");
        }
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
        readfile($archivo);
    }

    private static function mostrarError(int $estado, string $mensaje = ''): void
    {
        try {
            ErrorController::mostrar($estado, $mensaje);
        } catch (Throwable $error) {
            // Si falla la propia página de error, se responde con un texto mínimo y sin detalles.
            self::registrar($error);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo 'Error inesperado. Inténtalo nuevamente más tarde.';
        }
    }

    /** El detalle va al registro del servidor (docker compose logs app), nunca al navegador. */
    private static function registrar(Throwable $error): void
    {
        error_log(sprintf(
            'TuCriadero: %s: %s en %s:%d',
            $error::class,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
        ));
    }
}
