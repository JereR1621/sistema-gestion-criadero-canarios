<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use InvalidArgumentException;

final class Sesion
{
    private const DURACION_MAXIMA = 604800;

    public static function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('tucriadero');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => self::esHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        $ahora = time();
        if (isset($_SESSION['ultima_actividad']) && $ahora - (int) $_SESSION['ultima_actividad'] > self::DURACION_MAXIMA) {
            self::cerrarAutenticada();
        }

        $_SESSION['ultima_actividad'] = $ahora;
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function iniciarAutenticada(int $usuarioId, string $rol, ?int $criaderoId, string $nombre = ''): void
    {
        if (!in_array($rol, ['criador', 'admin'], true)) {
            throw new InvalidArgumentException('El rol de la sesión no es válido.');
        }

        if ($rol === 'criador' && $criaderoId === null) {
            throw new InvalidArgumentException('Un criador debe tener un criadero asociado.');
        }

        self::iniciar();
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['rol'] = $rol;
        $_SESSION['criadero_id'] = $criaderoId;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['ultima_actividad'] = time();
    }

    public static function cerrarAutenticada(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::iniciar();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], (bool) $parametros['secure'], (bool) $parametros['httponly']);
        }

        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['ultima_actividad'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    /** @return array{usuario_id:int,nombre:string,rol:string}|null */
    public static function paraVista(): ?array
    {
        if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
            return null;
        }

        return [
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'nombre' => (string) ($_SESSION['nombre'] ?? ''),
            'rol' => (string) $_SESSION['rol'],
        ];
    }

    public static function csrf(): string
    {
        self::iniciar();
        return (string) $_SESSION['csrf'];
    }

    private static function esHttps(): bool
    {
        if (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
