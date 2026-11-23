<?php
/** RF002: ingreso. Campos: correo, contrasena (contrato §6). La contraseña nunca se rellena. */
require_once __DIR__ . '/../_partes/ayudas.php';
$titulo = 'Iniciar sesión';
$ojo = '<button type="button" class="eye" data-act="eye" aria-label="Mostrar contraseña">' . icono('eye') . '</button>';
try {
    $urlRecuperar = ruta('recuperar');
} catch (InvalidArgumentException) {
    $urlRecuperar = null;
}
ob_start(); ?>
<h1>Bienvenido a tu criadero</h1><p class="auth-intro">Inicia sesión para seguir con tus canarios.</p>
<?= error_general() ?>
<form class="form" method="post" action="<?= e(ruta('ingreso.enviar')) ?>">
    <?= csrf_campo($csrf) ?>
    <div class="field<?= error('correo') !== '' ? ' has-error' : '' ?>">
        <label class="lbl" for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" value="<?= e(viejo('correo')) ?>" placeholder="correo@ejemplo.cl" autocomplete="email" required<?= error('correo') !== '' ? ' aria-invalid="true" aria-describedby="error-correo"' : '' ?>>
        <?php if (error('correo') !== ''): ?><span id="error-correo" class="field-error" role="alert"><?= e(error('correo')) ?></span><?php endif; ?>
    </div>
    <div class="field<?= error('contrasena') !== '' ? ' has-error' : '' ?>">
        <label class="lbl" for="contrasena">Contraseña</label>
        <span class="pwd"><input type="password" id="contrasena" name="contrasena" placeholder="Tu contraseña" autocomplete="current-password" required<?= error('contrasena') !== '' ? ' aria-invalid="true" aria-describedby="error-contrasena"' : '' ?>><?= $ojo ?></span>
        <?php if (error('contrasena') !== ''): ?><span id="error-contrasena" class="field-error" role="alert"><?= e(error('contrasena')) ?></span><?php endif; ?>
    </div>
    <?php if ($urlRecuperar !== null): ?>
    <a class="link right" href="<?= e($urlRecuperar) ?>">¿Olvidaste tu contraseña?</a>
    <?php else: ?>
    <span class="link right" role="link" aria-disabled="true" title="Recuperación de contraseña aún no disponible">¿Olvidaste tu contraseña?</span>
    <?php endif; ?>
    <?= boton('Ingresar', 'type="submit"', 'primary big', 'chev') ?>
</form>
<p class="center muted">¿Aún no tienes cuenta? <a class="link" hx-boost="true" hx-target="#main" hx-select="#main" hx-select-oob="#toast" hx-swap="outerHTML show:window:top" href="<?= e(ruta('registro')) ?>">Crear cuenta</a></p>
<?php $contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/acceso.php';
