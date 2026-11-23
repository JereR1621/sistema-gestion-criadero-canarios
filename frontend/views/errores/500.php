<?php
require_once __DIR__ . '/../_partes/ayudas.php';

$urlInicio = '/inicio';
try {
    $urlInicio = ruta('inicio');
} catch (InvalidArgumentException) {
}

$acciones = '<a class="btn btn-primary" href="' . e($urlInicio) . '">' . icono('refresh') . '<span>Volver a intentar</span></a>';
ob_start();
echo bloque_error('500', 'warning', 'danger', $titulo, 'No fue posible completar la solicitud. Inténtalo nuevamente más tarde.', $acciones);
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
