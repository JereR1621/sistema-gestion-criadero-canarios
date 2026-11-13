<?php

declare(strict_types=1);

namespace TuCriadero\Core;

final class Ruta
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $metodo,
        public readonly string $patron,
        public readonly string $controlador,
        public readonly string $accion,
    ) {
    }
}
