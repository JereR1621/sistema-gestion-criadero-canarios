<?php
/**
 * Vista: registro de cuenta de criador.
 * Issue: #71 (SRS-RF001 [Frontend]: Registro de cuenta de criador)
 * Recibe: solo las variables comunes del contrato: $csrf, $flash, $errores, $viejo y $sesion.
 */
require_once __DIR__ . '/../_partes/ayudas.php';

$titulo = 'Registro de cuenta';
$rutaInicio = '/';
$rutaIngreso = '/ingresar';
try { $rutaInicio = ruta('inicio'); } catch (InvalidArgumentException) {}
try { $rutaIngreso = ruta('ingreso'); } catch (InvalidArgumentException) {}
$nombre = (string) viejo('nombre', '');
$correo = (string) viejo('correo', '');
$consentimiento = viejo('consentimiento', '') === 'si';
$errorNombre = error('nombre');
$errorCorreo = error('correo');
$errorContrasena = error('contrasena');
$errorConsentimiento = error('consentimiento');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#193f46">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo . ' · TuCri') ?></title>
    <link rel="stylesheet" href="/css/assets/fonts/plex.css">
    <link rel="stylesheet" href="/css/tucriadero.css">
    <link rel="stylesheet" href="/css/tema.css">
    <link rel="stylesheet" href="/css/mejoras.css">
    <link rel="stylesheet" href="/css/tablero.css">
    <link rel="stylesheet" href="/css/local.css">
</head>
<body>
<div class="auth">
    <aside class="auth-art" aria-label="TuCriadero">
        <a class="brand brand-light" href="<?= e($rutaInicio) ?>" aria-label="Inicio de TuCri"><span class="brand-mark"><?= icono('bird') ?></span><span>TuCri</span></a>
        <div class="auth-claim">
            <h2>Tu criadero, organizado</h2>
            <p>Canarios, jaulas y cruces en un solo lugar.</p>
        </div>
    </aside>

    <main class="auth-panel">
        <section class="auth-card" aria-labelledby="titulo-registro">
            <p class="eyebrow">Cuenta de criador</p>
            <h1 id="titulo-registro">Registro de cuenta</h1>

            <?php if (($flash ?? null) !== null): ?>
                <div class="notice <?= e($flash['tipo'] === 'error' ? 'warn' : 'ok') ?>" role="<?= e($flash['tipo'] === 'error' ? 'alert' : 'status') ?>">
                    <?= e($flash['mensaje']) ?>
                </div>
            <?php endif; ?>
            <?= error_general() ?>

            <form class="form" method="post" action="<?= e(ruta('registro.guardar')) ?>">
                <?= csrf_campo($csrf ?? '') ?>

                <div class="field<?= $errorNombre !== '' ? ' has-error' : '' ?>">
                    <label class="lbl" for="nombre">Nombre</label>
                    <input
                        id="nombre"
                        name="nombre"
                        type="text"
                        autocomplete="name"
                        value="<?= e($nombre) ?>"
                        <?= $errorNombre !== '' ? 'aria-invalid="true" aria-describedby="error-nombre"' : '' ?>
                        required
                    >
                    <?php if ($errorNombre !== ''): ?>
                        <span id="error-nombre" class="field-error" role="alert"><?= e($errorNombre) ?></span>
                    <?php endif; ?>
                </div>

                <div class="field<?= $errorCorreo !== '' ? ' has-error' : '' ?>">
                    <label class="lbl" for="correo">Correo electrónico</label>
                    <input
                        id="correo"
                        name="correo"
                        type="email"
                        autocomplete="email"
                        value="<?= e($correo) ?>"
                        <?= $errorCorreo !== '' ? 'aria-invalid="true" aria-describedby="error-correo"' : '' ?>
                        required
                    >
                    <?php if ($errorCorreo !== ''): ?>
                        <span id="error-correo" class="field-error" role="alert"><?= e($errorCorreo) ?></span>
                    <?php endif; ?>
                </div>

                <div class="field<?= $errorContrasena !== '' ? ' has-error' : '' ?>">
                    <label class="lbl" for="contrasena">Contraseña</label>
                    <input
                        id="contrasena"
                        name="contrasena"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        <?= $errorContrasena !== '' ? 'aria-invalid="true" aria-describedby="error-contrasena ayuda-contrasena"' : 'aria-describedby="ayuda-contrasena"' ?>
                        required
                    >
                    <span id="ayuda-contrasena" class="hint">Mínimo 8 caracteres.</span>
                    <?php if ($errorContrasena !== ''): ?>
                        <span id="error-contrasena" class="field-error" role="alert"><?= e($errorContrasena) ?></span>
                    <?php endif; ?>
                </div>

                <div class="check<?= $errorConsentimiento !== '' ? ' has-error' : '' ?>">
                    <label for="consentimiento">
                        <input
                            id="consentimiento"
                            name="consentimiento"
                            type="checkbox"
                            value="si"
                            <?= $consentimiento ? 'checked' : '' ?>
                            <?= $errorConsentimiento !== '' ? 'aria-invalid="true" aria-describedby="error-consentimiento"' : '' ?>
                            required
                        >
                        <span>Acepto el uso de mis datos para el servicio.</span>
                    </label>
                    <?php if ($errorConsentimiento !== ''): ?>
                        <span id="error-consentimiento" class="field-error" role="alert"><?= e($errorConsentimiento) ?></span>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary" type="submit">Crear cuenta</button>
            </form>

            <p class="center">¿Ya tienes una cuenta? <a class="link" href="<?= e($rutaIngreso) ?>">Iniciar sesión</a></p>
        </section>
    </main>
</div>
</body>
</html>
