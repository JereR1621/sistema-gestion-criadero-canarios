<?php
require_once __DIR__ . '/../_partes/ayudas.php';

$urlIngreso = '/ingresar';
try {
    $urlIngreso = ruta('ingreso');
} catch (InvalidArgumentException) {
}

$acciones = '<a class="btn btn-primary" href="' . e($urlIngreso) . '">' . icono('refresh') . '<span>Volver a intentar</span></a>';
ob_start();
echo bloque_error('503', 'settings', 'warn', $titulo, 'Estamos realizando mantenimiento. Inténtalo nuevamente más tarde.', $acciones);
$contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/acceso.php';
