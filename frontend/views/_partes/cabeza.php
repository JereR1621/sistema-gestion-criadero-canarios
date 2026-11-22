<?php
/** <head> común. Recibe $titulo. Estilos y scripts del propio sitio (la política de seguridad no admite otros). */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#E5EEF0">
    <meta name="color-scheme" content="light">
    <?php /* htmx sin historial en el navegador: las páginas tienen datos del criadero (Cache-Control: no-store). */ ?>
    <meta name="htmx-config" content='{"historyCacheSize":0,"refreshOnHistoryMiss":true,"allowEval":false,"allowScriptTags":false}'>
    <title><?= e(($titulo ?? '') !== '' ? $titulo . ' · TuCri' : 'TuCri') ?></title>
    <link rel="preload" href="/css/assets/fonts/plex-sans-1.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/css/assets/fonts/plex.css">
    <link rel="icon" href="/css/assets/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/css/assets/icono-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="/css/assets/icono-180.png">
    <link rel="stylesheet" href="/css/tucriadero.css">
    <link rel="stylesheet" href="/css/tema.css">
    <link rel="stylesheet" href="/css/mejoras.css">
    <link rel="stylesheet" href="/css/tablero.css">
    <link rel="stylesheet" href="/css/local.css">
    <script src="/js/htmx.min.js" defer></script>
    <script src="/js/tucri.js" defer></script>
</head>
