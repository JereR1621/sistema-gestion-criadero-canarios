<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use Throwable;
use TuCriadero\Controllers\AdminCatalogoController;
use TuCriadero\Controllers\AdminCuentaController;
use TuCriadero\Controllers\CanarioController;
use TuCriadero\Controllers\CuentaController;
use TuCriadero\Controllers\ErrorController;
use TuCriadero\Controllers\InicioController;
use TuCriadero\Controllers\JaulaController;
use TuCriadero\Models\UsuarioModel;

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
            $router = self::crearRouter();
            Rutas::definir($router);
            Entorno::cargar($raiz . '/.env');
            if (Entorno::obtener('APP_ENV') === 'local') {
                ini_set('display_errors', '1');
            }

            Sesion::iniciar();
            self::cerrarSesionSiLaCuentaNoEstaActiva();

            // Sin sesión iniciada, la conexión a MySQL no se abre aquí: los modelos la piden con Conexion::obtener().
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
            new Ruta('raiz', 'GET', '/', InicioController::class, 'raiz'),
            new Ruta('inicio', 'GET', '/inicio', InicioController::class, 'mostrar'),
            new Ruta('inicio.pdf', 'GET', '/inicio/pdf', InicioController::class, 'descargarPdf'),

            new Ruta('registro', 'GET', '/registro', CuentaController::class, 'registro'),
            new Ruta('registro.guardar', 'POST', '/registro', CuentaController::class, 'registroGuardar'),

            new Ruta('ingreso', 'GET', '/ingresar', CuentaController::class, 'ingreso'),
            new Ruta('ingreso.enviar', 'POST', '/ingresar', CuentaController::class, 'ingresoEnviar'),
            new Ruta('salida', 'POST', '/salir', CuentaController::class, 'salida'),

            new Ruta('perfil', 'GET', '/perfil', CuentaController::class, 'perfil'),
            new Ruta('perfil.guardar', 'POST', '/perfil', CuentaController::class, 'perfilGuardar'),

            new Ruta('jaulas', 'GET', '/jaulas', JaulaController::class, 'index'),
            new Ruta('jaulas.guardar', 'POST', '/jaulas', JaulaController::class, 'guardar'),

            new Ruta('admin.cuentas', 'GET', '/admin/cuentas', AdminCuentaController::class, 'index'),
            new Ruta('admin.cuentas.estado', 'POST', '/admin/cuentas/{id}/estado', AdminCuentaController::class, 'estado'),

            new Ruta('canarios', 'GET', '/canarios', CanarioController::class, 'index'),
            new Ruta('canarios.nuevo', 'GET', '/canarios/nuevo', CanarioController::class, 'nuevo'),
            new Ruta('canarios.guardar', 'POST', '/canarios', CanarioController::class, 'guardar'),
            new Ruta('canarios.ficha', 'GET', '/canarios/{id}', CanarioController::class, 'ficha'),
            new Ruta('canarios.editar', 'GET', '/canarios/{id}/editar', CanarioController::class, 'editar'),
            new Ruta('canarios.actualizar', 'POST', '/canarios/{id}', CanarioController::class, 'actualizar'),

            new Ruta('admin.variedades', 'GET', '/admin/variedades', AdminCatalogoController::class, 'variedades'),
            new Ruta('admin.variedades.nueva', 'GET', '/admin/variedades/nueva', AdminCatalogoController::class, 'variedadesNueva'),
            new Ruta('admin.variedades.crear', 'POST', '/admin/variedades', AdminCatalogoController::class, 'variedadesCrear'),
            new Ruta('admin.variedades.editar', 'GET', '/admin/variedades/{codigo}', AdminCatalogoController::class, 'variedadesEditar'),
            new Ruta('admin.variedades.guardar', 'POST', '/admin/variedades/{codigo}', AdminCatalogoController::class, 'variedadesGuardar'),
            new Ruta('admin.variedades.eliminar', 'POST', '/admin/variedades/{codigo}/eliminar', AdminCatalogoController::class, 'variedadesEliminar'),
            new Ruta('admin.factores', 'GET', '/admin/factores', AdminCatalogoController::class, 'factores'),
            new Ruta('admin.factores.guardar', 'POST', '/admin/factores', AdminCatalogoController::class, 'factoresGuardar'),
            new Ruta('admin.factores.editar', 'GET', '/admin/factores/{id}', AdminCatalogoController::class, 'factoresEditar'),
            new Ruta('admin.factores.actualizar', 'POST', '/admin/factores/{id}', AdminCatalogoController::class, 'factoresActualizar'),
        ]);
    }

    /**
     * RF005: una cuenta desactivada o cerrada deja de usar el sistema en su siguiente petición, aunque ya
     * tuviera la sesión abierta. Sin esto, el criador desactivado seguiría entrando hasta que venza la sesión (7 días).
     */
    public static function cerrarSesionSiLaCuentaNoEstaActiva(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            return;
        }

        $cuenta = (new UsuarioModel())->buscarPorId((int) $_SESSION['usuario_id']);
        if ($cuenta === null || ($cuenta['estado'] ?? '') !== 'activa') {
            Sesion::cerrarAutenticada();
        }
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

        if (str_starts_with($ruta, '/js/')) {
            $this->servirJs($ruta);
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

        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        $tipos = [
            'css' => 'text/css; charset=UTF-8',
            'woff2' => 'font/woff2',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'html' => 'text/html; charset=UTF-8',
        ];
        if (!isset($tipos[$extension])) {
            throw HttpException::noEncontrado();
        }

        header('Content-Type: ' . $tipos[$extension]);
        readfile($archivo);
    }

    private function servirJs(string $ruta): void
    {
        $relativa = rawurldecode(substr($ruta, strlen('/js/')));

        if ($relativa === '' || str_contains($relativa, '..') || str_contains($relativa, '\\') || !str_ends_with(strtolower($relativa), '.js')) {
            throw HttpException::noEncontrado();
        }

        $archivo = $this->raiz . '/public/js/' . $relativa;
        if (!is_file($archivo)) {
            throw HttpException::noEncontrado();
        }

        header('Content-Type: application/javascript; charset=UTF-8');
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
