<?php
/** 503: modo mantenimiento (SRS §2.5). «Reintentar» vuelve a pedir la página. */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'En mantenimiento';
$contenido = bloque_error('', 'tool', 'warn', 'Estamos en mantenimiento',
    'TuCri no está disponible por un momento. Tus canarios, jaulas y crías están guardados. Vuelve a intentarlo en unos minutos.',
    (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ? enlace('Reintentar', '', 'primary big') : enlace('Ir al inicio', ruta('inicio'), 'primary big')));
require __DIR__ . '/../_partes/acceso.php';
