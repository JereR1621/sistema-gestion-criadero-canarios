<?php

declare(strict_types=1);

require_once __DIR__ . '/../_partes/ayudas.php';
require __DIR__ . '/../_partes/cabeza.php';

$correo = viejo('correo');
$errorGeneral = error('_general');
$errorCorreo = error('correo');
$errorContrasena = error('contrasena');
?>
<body>
<main class="auth">
    <section class="auth-art" aria-label="TuCri">
        <div class="auth-brand brand"><span class="brand-mark"><?= icono('bird') ?></span><span>TuCri</span></div>
        <div class="auth-claim">
            <h2>Tu aviario, organizado.</h2>
            <p>Canarios, jaulas y crías, todo en un solo lugar.</p>
        </div>
    </section>

    <section class="auth-panel">
        <form class="auth-card" method="post" action="<?= e(ruta('ingreso.enviar')) ?>">
            <p class="eyebrow">Bienvenido</p>
            <h1>Iniciar sesión</h1>

            <?php if ($errorGeneral !== ''): ?>
            <div class="aviso aviso-error" role="alert"><?= e($errorGeneral) ?></div>
            <?php endif; ?>

            <?= csrf_campo($csrf ?? '') ?>

            <div class="field">
                <label for="correo">Correo electrónico</label>
                <input id="correo" name="correo" type="email" autocomplete="email" inputmode="email" required
                    value="<?= e((string) $correo) ?>"<?= $errorCorreo !== '' ? ' aria-invalid="true" aria-describedby="correo-error"' : '' ?>>
                <?php if ($errorCorreo !== ''): ?><small id="correo-error" class="field-error"><?= e($errorCorreo) ?></small><?php endif; ?>
            </div>

            <div class="field">
                <label for="contrasena">Contraseña</label>
                <span class="pwd">
                    <input id="contrasena" name="contrasena" type="password" autocomplete="current-password" required
                        <?= $errorContrasena !== '' ? ' aria-invalid="true" aria-describedby="contrasena-error"' : '' ?>>
                    <button class="eye" type="button" data-act="eye" aria-label="Mostrar contraseña">Ver</button>
                </span>
                <?php if ($errorContrasena !== ''): ?><small id="contrasena-error" class="field-error"><?= e($errorContrasena) ?></small><?php endif; ?>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Iniciar sesión</button>
            </div>

            <p class="lead">¿No tienes cuenta? <a href="<?= e(ruta('registro')) ?>">Crear una cuenta</a></p>
        </form>

        <p class="auth-foot"><a href="/css/creditos.html">Fotografías y créditos</a></p>
    </section>
</main>
</body>
</html>
