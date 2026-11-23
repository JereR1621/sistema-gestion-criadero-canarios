<?php
require_once __DIR__ . '/../_partes/ayudas.php';

$urlInicio = '/inicio';
try {
    $urlInicio = ruta('inicio');
} catch (InvalidArgumentException) {
}

$acciones = '<a class="btn btn-primary" href="' . e($urlInicio) . '">' . icono('home') . '<span>Volver al inicio</span></a>';
ob_start();
echo bloque_error('403', 'lock', 'danger', $titulo, 'No tienes permiso para acceder a esta página.', $acciones);
$contenido = (string) ob_get_clean();

if (($sesion ?? null) !== null) {
    $seccion = 'inicio';
    $vista = 'error';
    $volver = null;
    $flash = $flash ?? null;
    $csrf = $csrf ?? '';
    require __DIR__ . '/../_partes/marco.php';
} else {
    require __DIR__ . '/../_partes/acceso.php';
}
