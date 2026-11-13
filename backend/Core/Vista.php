<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use InvalidArgumentException;

final class Vista
{
    private static string $raiz = '';

    /** @var array<string, string> */
    private static array $errores = [];

    /** @var array<string, mixed> */
    private static array $viejo = [];

    public static function definirRaiz(string $raiz): void
    {
        self::$raiz = rtrim($raiz, '/\\');
    }

    /** @param array<string, mixed> $datos */
    public static function renderizar(string $vista, array $datos = [], bool $devolverHtml = false): ?string
    {
        if (self::$raiz === '' || !preg_match('#^[a-z0-9_-]+(?:/[a-z0-9_-]+)*$#i', $vista)) {
            throw new InvalidArgumentException('La vista solicitada no es válida.');
        }

        $archivo = self::$raiz . '/frontend/views/' . $vista . '.php';
        if (!is_file($archivo)) {
            throw HttpException::noEncontrado();
        }

        self::$errores = is_array($datos['errores'] ?? null) ? $datos['errores'] : [];
        self::$viejo = is_array($datos['viejo'] ?? null) ? $datos['viejo'] : [];

        $comunes = [
            'sesion' => Sesion::paraVista(),
            'csrf' => Sesion::csrf(),
            'flash' => self::obtenerFlash(),
            'errores' => self::$errores,
            'viejo' => self::$viejo,
        ];
        $datosVista = array_merge($datos, $comunes);

        ob_start();
        (static function (string $_archivo, array $_datos): void {
            extract($_datos, EXTR_SKIP);
            require $_archivo;
        })($archivo, $datosVista);
        $html = (string) ob_get_clean();

        if ($devolverHtml) {
            return $html;
        }

        echo $html;
        return null;
    }

    public static function error(string $campo): string
    {
        return (string) (self::$errores[$campo] ?? '');
    }

    public static function viejo(string $campo, mixed $porDefecto = ''): mixed
    {
        return self::$viejo[$campo] ?? $porDefecto;
    }

    /** @param array{tipo:string,mensaje:string} $flash */
    public static function guardarFlash(array $flash): void
    {
        Sesion::iniciar();
        $_SESSION['flash'] = $flash;
    }

    /** @return array{tipo:string,mensaje:string}|null */
    private static function obtenerFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return is_array($flash) ? $flash : null;
    }
}
