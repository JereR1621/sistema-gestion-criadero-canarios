<?php
/** 403: un criador entra a una ruta de administración (contrato §7). */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'Sin acceso';
$contenido = bloque_error('Error 403', 'lock', 'muted', 'Esta sección no está disponible para tu cuenta',
    ($sesion['rol'] ?? '') === 'criador'
        ? 'Solo el administrador de TuCri puede entrar aquí. Tus canarios y tus datos siguen a salvo.'
        : 'Tu cuenta no tiene permiso para entrar en esta sección.',
    $sesion !== null ? enlace('Volver al inicio', ruta('inicio'), 'primary big', 'home') : enlace('Iniciar sesión', ruta('ingreso'), 'primary big', 'chev'));
$seccion = 'buscar';
$vista = '403';
require __DIR__ . '/../_partes/' . ($sesion !== null ? 'marco.php' : 'acceso.php');
