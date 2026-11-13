<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

final class ErrorController
{
    public static function mostrar(int $estado, string $mensaje = ''): void
    {
        $vistas = [
            403 => 'errores/403',
            404 => 'errores/404',
            419 => 'errores/sesion',
            503 => 'errores/mantenimiento',
        ];
        $vista = $vistas[$estado] ?? 'errores/500';
        $titulo = [
            403 => 'Acceso no autorizado',
            404 => 'Página no encontrada',
            419 => 'Sesión expirada',
            503 => 'Sitio en mantenimiento',
            500 => 'Error inesperado',
        ][$estado] ?? 'Error inesperado';

        if ($estado === 419) {
            // Apache no conoce el código 419 y, sin la frase, lo convierte en 500.
            header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 419 Page Expired', true, 419);
        } else {
            http_response_code($estado);
        }

        render($vista, ['titulo' => $titulo, 'mensaje' => $mensaje]);
    }
}
