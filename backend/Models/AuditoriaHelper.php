<?php

declare(strict_types=1);

use TuCriadero\Core\Conexion;

/**
 * Registra en `auditoria` quién hizo qué y cuándo (SEG-003).
 *
 * Usa la misma conexión que el servicio: dentro de una transacción, si la operación
 * se deshace, su registro de auditoría también. Los errores no se ocultan.
 * Sin sesión iniciada (por ejemplo, durante el registro), usuario_id queda NULL.
 * El `entidadId` puede ser un id numérico o un código (migración 016, p. ej. la variedad `D.049`).
 */
function auditar(string $accion, string $entidad, int|string $entidadId): void
{
    $usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;

    Conexion::obtener()
        ->prepare('INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id) VALUES (?, ?, ?, ?)')
        ->execute([$usuarioId, $accion, $entidad, $entidadId]);
}
