<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    private function __construct(private readonly int $estado, string $mensaje = '')
    {
        parent::__construct($mensaje);
    }

    public static function noEncontrado(): self
    {
        return new self(404);
    }

    public static function prohibido(): self
    {
        return new self(403);
    }

    public static function sesionExpirada(): self
    {
        return new self(419);
    }

    public static function mantenimiento(): self
    {
        return new self(503);
    }

    public function codigo(): int
    {
        return $this->estado;
    }
}
