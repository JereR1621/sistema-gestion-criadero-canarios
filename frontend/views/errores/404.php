<?php
/** 404: el registro no existe o es de otro criadero (contrato §7). Con sesión, dentro del menú; sin sesión, en la pantalla de acceso. */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'Página no encontrada';
$acciones = enlace('Iniciar sesión', ruta('ingreso'), 'primary big', 'chev');
if ($sesion !== null) {
    $acciones = enlace('Ir al inicio', ruta('inicio'), 'primary big', 'home');
    if ($sesion['rol'] === 'criador') {
        try {
            $acciones .= enlace('Buscar canario', ruta('buscar'), 'soft big', 'search');
        } catch (InvalidArgumentException) {
            $acciones .= '<span class="btn btn-soft big" role="link" aria-disabled="true" title="Búsqueda aún no disponible">'
                . icono('search') . '<span>Buscar canario</span></span>';
        }
    }
}
$contenido = bloque_error('Error 404', 'search', 'muted', 'No encontramos lo que buscas',
    ($sesion['rol'] ?? '') === 'criador'
        ? 'El canario, la jaula o el cruce no existe o ya no está en tu criadero. Revisa el anillo o búscalo de nuevo.'
        : 'La página que buscas no existe o ya no está disponible.',
    $acciones);
$seccion = 'buscar';
$vista = '404';
require __DIR__ . '/../_partes/' . ($sesion !== null ? 'marco.php' : 'acceso.php');
