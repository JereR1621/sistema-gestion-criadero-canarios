<?php
require_once __DIR__ . '/../_partes/ayudas.php';

$urlIngreso = '/ingresar';
try {
    $urlIngreso = ruta('ingreso');
} catch (InvalidArgumentException) {
}

$acciones = '<a class="btn btn-primary" href="' . e($urlIngreso) . '">' . icono('login') . '<span>Volver a ingresar</span></a>';
ob_start();
echo bloque_error('419', 'lock', 'warn', $titulo, 'Tu sesión expiró o la solicitud ya no es válida. Vuelve a ingresar para continuar.', $acciones);
$contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/acceso.php';
