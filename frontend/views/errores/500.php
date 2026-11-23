<?php
/** Error inesperado. La información técnica se registra en el servidor. */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'Algo salió mal';
$acciones = enlace('Ir al inicio', ruta('inicio'), 'ghost big');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $acciones = enlace('Reintentar', '', 'primary big') . $acciones;
}
$contenido = bloque_error('', 'alert', 'danger', $titulo,
    'No pudimos completar la acción. Inténtalo de nuevo; si vuelve a pasar, avisa al administrador de TuCri.',
    $acciones);
require __DIR__ . '/../_partes/acceso.php';
