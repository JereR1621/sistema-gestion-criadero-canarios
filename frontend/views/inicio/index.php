<?php
$titulo = 'Inicio';
$seccion = 'inicio';
ob_start(); ?>
<section class="card">
    <h1>TuCriadero funciona</h1>
    <p>La estructura base está lista para recibir los módulos del proyecto.</p>
    <p><a class="link" href="<?= e(ruta('inicio.pdf')) ?>">Descargar PDF de prueba</a></p>
</section>
<?php $contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/marco.php';
