<?php
/** RF004: perfil del criador y valores por defecto al anillar. */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'Mi perfil';
$seccion = 'perfil';
$volver = [ruta('inicio'), 'Inicio'];
ob_start(); ?>
<?= encabezado_pagina('Mi perfil', 'Tus datos y los valores que se proponen al registrar anillos.', 'user') ?>
<?= error_general() ?>
<form class="form wide" method="post" action="<?= e(ruta('perfil.guardar')) ?>">
    <?= csrf_campo($csrf) ?>
    <section class="card step">
        <div class="grid-2">
            <?= campo('Nombre', '<input value="' . e($perfil['nombre']) . '" readonly>') ?>
            <?= campo('Correo', '<input type="email" value="' . e($perfil['correo']) . '" readonly>') ?>
            <?php foreach (['numero_socio' => 'Número de socio', 'club' => 'Club (sigla del anillo)'] as $campo => $etiqueta): ?>
                <?php $mensaje = error($campo); ?>
                <label for="<?= e($campo) ?>" class="field<?= $mensaje !== '' ? ' has-error' : '' ?>">
                    <span class="lbl"><?= e($etiqueta) ?></span>
                    <input id="<?= e($campo) ?>" name="<?= e($campo) ?>" value="<?= e(viejo($campo, $perfil[$campo])) ?>"
                        <?php if ($mensaje !== ''): ?>aria-invalid="true" aria-describedby="error-<?= e($campo) ?>"<?php endif; ?>>
                    <?php if ($mensaje !== ''): ?>
                        <span id="error-<?= e($campo) ?>" class="field-error" role="alert"><?= e($mensaje) ?></span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
    </section>
    <div class="form-actions"><?= boton('Guardar cambios', 'type="submit"', 'primary big', 'check') ?></div>
</form>
<?php $contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/marco.php';
