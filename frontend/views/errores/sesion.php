<?php
/** 419: el token CSRF no es válido o la sesión expiró (contrato §7), con enlace para ingresar de nuevo. */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'Sesión expirada';
$contenido = bloque_error('', 'clock', 'warn', 'Tu sesión expiró',
    $sesion !== null ? 'La solicitud ya no es válida. Ingresa de nuevo para seguir. Si estabas guardando algo, revisa que haya quedado registrado.'
        : 'Por seguridad cerramos la sesión después de un tiempo sin uso. Ingresa de nuevo para seguir. Si estabas guardando algo, revisa que haya quedado registrado.',
    enlace('Ingresar de nuevo', ruta('ingreso'), 'primary big', 'chev'));
require __DIR__ . '/../_partes/acceso.php';
