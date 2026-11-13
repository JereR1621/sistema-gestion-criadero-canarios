<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use InvalidArgumentException;

final class Router
{
    /** @var array<string, Ruta> */
    private array $porNombre = [];

    /** @param list<Ruta> $rutas */
    public function __construct(private readonly array $rutas)
    {
        foreach ($rutas as $ruta) {
            if (isset($this->porNombre[$ruta->nombre])) {
                throw new InvalidArgumentException("La ruta {$ruta->nombre} está duplicada.");
            }

            $this->porNombre[$ruta->nombre] = $ruta;
        }
    }

    /** @param array<string, scalar> $parametros */
    public function url(string $nombre, array $parametros = []): string
    {
        $ruta = $this->porNombre[$nombre] ?? null;
        if ($ruta === null) {
            throw new InvalidArgumentException("No existe la ruta {$nombre}.");
        }

        return preg_replace_callback('/\{([a-z_][a-z0-9_]*)\}/', static function (array $coincidencia) use ($parametros): string {
            $parametro = $coincidencia[1];
            if (!array_key_exists($parametro, $parametros)) {
                throw new InvalidArgumentException("Falta el parámetro {$parametro} para la ruta.");
            }

            return rawurlencode((string) $parametros[$parametro]);
        }, $ruta->patron) ?? $ruta->patron;
    }

    public function despachar(string $metodo, string $url): void
    {
        foreach ($this->rutas as $ruta) {
            if ($ruta->metodo !== $metodo) {
                continue;
            }

            $parametros = $this->coincide($ruta->patron, $url);
            if ($parametros === null) {
                continue;
            }

            $controlador = new ($ruta->controlador)();
            $controlador->{$ruta->accion}(...$parametros);
            return;
        }

        throw HttpException::noEncontrado();
    }

    /** @return list<string>|null */
    private function coincide(string $patron, string $url): ?array
    {
        $expresion = preg_replace('/\{[a-z_][a-z0-9_]*\}/', '([^/]+)', $patron);
        if ($expresion === null || preg_match('#^' . $expresion . '$#D', $url, $coincidencias) !== 1) {
            return null;
        }

        array_shift($coincidencias);
        return array_map('rawurldecode', $coincidencias);
    }
}
