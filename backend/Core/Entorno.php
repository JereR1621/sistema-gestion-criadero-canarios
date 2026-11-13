<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use RuntimeException;

final class Entorno
{
    public static function cargar(string $archivo): void
    {
        if (!is_file($archivo)) {
            return;
        }

        $lineas = file($archivo, FILE_IGNORE_NEW_LINES);
        if ($lineas === false) {
            throw new RuntimeException('No se pudo leer la configuración local.');
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $posicion = strpos($linea, '=');
            if ($posicion === false) {
                continue;
            }

            $nombre = trim(substr($linea, 0, $posicion));
            $valor = trim(substr($linea, $posicion + 1));

            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $nombre) || self::yaExiste($nombre)) {
                continue;
            }

            if (strlen($valor) >= 2 && (($valor[0] === '"' && str_ends_with($valor, '"')) || ($valor[0] === "'" && str_ends_with($valor, "'")))) {
                $valor = substr($valor, 1, -1);
            }

            putenv("{$nombre}={$valor}");
            $_ENV[$nombre] = $valor;
        }
    }

    public static function obtener(string $nombre, ?string $porDefecto = null): ?string
    {
        if (array_key_exists($nombre, $_ENV)) {
            return (string) $_ENV[$nombre];
        }

        if (array_key_exists($nombre, $_SERVER)) {
            return (string) $_SERVER[$nombre];
        }

        $valor = getenv($nombre);
        return $valor === false ? $porDefecto : $valor;
    }

    private static function yaExiste(string $nombre): bool
    {
        return array_key_exists($nombre, $_ENV)
            || array_key_exists($nombre, $_SERVER)
            || getenv($nombre) !== false;
    }
}
