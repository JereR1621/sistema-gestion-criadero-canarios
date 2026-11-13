<?php

declare(strict_types=1);

use TuCriadero\Core\Rutas;
use TuCriadero\Core\Vista;

/** Escapa un valor para insertarlo de forma segura en HTML. */
function e(mixed $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @param array<string, scalar> $parametros */
function ruta(string $nombre, array $parametros = []): string
{
    return Rutas::url($nombre, $parametros);
}

function error(string $campo): string
{
    return Vista::error($campo);
}

function viejo(string $campo, mixed $porDefecto = ''): mixed
{
    return Vista::viejo($campo, $porDefecto);
}

function fecha(?string $aaaaMmDd): string
{
    if ($aaaaMmDd === null || $aaaaMmDd === '') {
        return '';
    }

    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $aaaaMmDd);
    $errores = DateTimeImmutable::getLastErrors();
    if ($fecha === false || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
        return '';
    }

    return $fecha->format('d/m/Y');
}

/** @param array<string, mixed> $datos */
function render(string $vista, array $datos = [], bool $devolverHtml = false): ?string
{
    return Vista::renderizar($vista, $datos, $devolverHtml);
}
