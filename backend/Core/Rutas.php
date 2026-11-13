<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use LogicException;

final class Rutas
{
    private static ?Router $router = null;

    public static function definir(Router $router): void
    {
        self::$router = $router;
    }

    /** @param array<string, scalar> $parametros */
    public static function url(string $nombre, array $parametros = []): string
    {
        if (self::$router === null) {
            throw new LogicException('El enrutador no está disponible.');
        }

        return self::$router->url($nombre, $parametros);
    }
}
