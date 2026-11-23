<?php
/** RF001: crear cuenta. Campos: nombre, correo, contrasena, consentimiento (casilla obligatoria, POL-001). */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'Crear cuenta';
$ojo = '<button type="button" class="eye" data-act="eye" aria-label="Mostrar contraseña">' . icono('eye') . '</button>';
ob_start(); ?>
<h1>Crear cuenta</h1><p class="auth-intro">Un lugar para llevar tu criadero al día.</p>
<?= error_general() ?>
<form class="form" method="post" action="<?= e(ruta('registro.guardar')) ?>">
    <?= csrf_campo($csrf) ?>
    <div class="field<?= error('nombre') !== '' ? ' has-error' : '' ?>">
        <label class="lbl" for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= e(viejo('nombre')) ?>" placeholder="Tu nombre" autocomplete="name" required<?= error('nombre') !== '' ? ' aria-invalid="true" aria-describedby="error-nombre"' : '' ?>>
        <?php if (error('nombre') !== ''): ?><span id="error-nombre" class="field-error" role="alert"><?= e(error('nombre')) ?></span><?php endif; ?>
    </div>
    <div class="field<?= error('correo') !== '' ? ' has-error' : '' ?>">
        <label class="lbl" for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" value="<?= e(viejo('correo')) ?>" placeholder="correo@ejemplo.cl" autocomplete="email" required<?= error('correo') !== '' ? ' aria-invalid="true" aria-describedby="error-correo"' : '' ?>>
        <?php if (error('correo') !== ''): ?><span id="error-correo" class="field-error" role="alert"><?= e(error('correo')) ?></span><?php endif; ?>
    </div>
    <div class="field<?= error('contrasena') !== '' ? ' has-error' : '' ?>">
        <label class="lbl" for="contrasena">Contraseña</label>
        <span class="pwd"><input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 8 caracteres" autocomplete="new-password" minlength="8" required<?= error('contrasena') !== '' ? ' aria-invalid="true" aria-describedby="error-contrasena ayuda-contrasena"' : ' aria-describedby="ayuda-contrasena"' ?>><?= $ojo ?></span>
        <span class="hint" id="ayuda-contrasena">Usa al menos 8 caracteres. Una frase corta es más segura y fácil de recordar.</span>
        <?php if (error('contrasena') !== ''): ?><span id="error-contrasena" class="field-error" role="alert"><?= e(error('contrasena')) ?></span><?php endif; ?>
    </div>
    <label class="check<?= error('consentimiento') !== '' ? ' has-error' : '' ?>"><input type="checkbox" name="consentimiento" id="consentimiento" value="si" aria-required="true"<?= error('consentimiento') !== '' ? ' aria-invalid="true" aria-describedby="error-consentimiento"' : '' ?><?= viejo('consentimiento') === 'si' ? ' checked' : '' ?>><span>Acepto el uso de mis datos para el servicio</span></label>
    <?php if (error('consentimiento') !== ''): ?><span id="error-consentimiento" class="field-error" role="alert"><?= e(error('consentimiento')) ?></span><?php endif; ?>
    <?= boton('Crear cuenta', 'type="submit"', 'primary big') ?>
</form>
<p class="center muted">¿Ya tienes cuenta? <a class="link" hx-boost="true" hx-target="#main" hx-select="#main" hx-select-oob="#toast" hx-swap="outerHTML show:window:top" href="<?= e(ruta('ingreso')) ?>">Iniciar sesión</a></p>
<?php $contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/acceso.php';
