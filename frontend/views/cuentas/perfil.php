<?php
/**
 * Vista: frontend/views/cuentas/perfil.php
 * Issue: #77 (SRS-RF004 [Frontend]: Socio y club por defecto en los anillos)
 * Recibe: $perfil = nombre, correo, numero_socio, club.
 * Campos del formulario: numero_socio, club.
 */
require_once __DIR__ . '/../_partes/ayudas.php';

$perfilData = is_array($perfil ?? null) ? $perfil : [];
$nombre = (string) ($perfilData['nombre'] ?? '');
$correo = (string) ($perfilData['correo'] ?? '');

$errorSocio = error('numero_socio');
$errorClub = error('club');
$valorSocio = (string) viejo('numero_socio', (string) ($perfilData['numero_socio'] ?? ''));
$valorClub = (string) viejo('club', (string) ($perfilData['club'] ?? ''));

$titulo = 'Mi perfil';
$seccion = 'perfil';

ob_start(); ?>
<?= encabezado_pagina('Mi perfil', 'Configura los datos que se proponen al anillar un canario.', 'user') ?>
<?= error_general() ?>

<div class="card perfil-card">
    <dl class="perfil-datos">
        <div>
            <dt>Nombre</dt>
            <dd><?= e($nombre) ?></dd>
        </div>
        <div>
            <dt>Correo</dt>
            <dd><?= e($correo) ?></dd>
        </div>
    </dl>

    <form class="form perfil-form" method="post" action="<?= e(ruta('perfil.guardar')) ?>">
        <?= csrf_campo($csrf ?? '') ?>

        <div class="field<?= $errorSocio !== '' ? ' has-error' : '' ?>">
            <label class="lbl" for="numero_socio">Número de socio</label>
            <input
                id="numero_socio"
                name="numero_socio"
                type="text"
                value="<?= e($valorSocio) ?>"
                maxlength="50"
                autocomplete="off"
                placeholder="Ej.: S-0452"
                <?= $errorSocio !== '' ? 'aria-invalid="true" aria-describedby="error-numero-socio"' : '' ?>
            >
            <p class="hint">Se usará por defecto en el campo Socio al anillar canarios.</p>
            <?php if ($errorSocio !== ''): ?>
                <span id="error-numero-socio" class="field-error" role="alert"><?= e($errorSocio) ?></span>
            <?php endif; ?>
        </div>

        <div class="field<?= $errorClub !== '' ? ' has-error' : '' ?>">
            <label class="lbl" for="club">Club</label>
            <input
                id="club"
                name="club"
                type="text"
                value="<?= e($valorClub) ?>"
                maxlength="50"
                autocomplete="organization"
                placeholder="Ej.: Club Norte"
                <?= $errorClub !== '' ? 'aria-invalid="true" aria-describedby="error-club"' : '' ?>
            >
            <p class="hint">Se usará por defecto en el campo Club al anillar canarios.</p>
            <?php if ($errorClub !== ''): ?>
                <span id="error-club" class="field-error" role="alert"><?= e($errorClub) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-actions perfil-actions">
            <?= boton('Guardar perfil', 'type="submit"', 'primary', 'check') ?>
        </div>
    </form>
</div>
<?php $contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/marco.php';
