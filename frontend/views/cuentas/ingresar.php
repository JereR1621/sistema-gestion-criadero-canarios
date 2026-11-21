<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión — TuCriadero</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <h1>Iniciar sesión (Prueba RF002)</h1>

    <?php if ($flash): ?>
        <p style="padding:8px; border:1px solid #888;"><strong><?= e($flash['mensaje']) ?></strong></p>
    <?php endif; ?>

    <?php if (error('_general')): ?>
        <p style="color:red; font-weight:bold;"><?= e(error('_general')) ?></p>
    <?php endif; ?>

    <form action="<?= e(ruta('ingreso.enviar')) ?>" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div style="margin-bottom:10px;">
            <label>Correo electrónico</label><br>
            <input type="email" name="correo" value="<?= e(viejo('correo')) ?>">
            <?php if (error('correo')): ?><br><small style="color:red"><?= e(error('correo')) ?></small><?php endif; ?>
        </div>

        <div style="margin-bottom:10px;">
            <label>Contraseña</label><br>
            <input type="password" name="contrasena">
            <?php if (error('contrasena')): ?><br><small style="color:red"><?= e(error('contrasena')) ?></small><?php endif; ?>
        </div>

        <button type="submit">Ingresar</button>
    </form>

    <hr>
    <p><em>Prueba rápida de cierre de sesión (POST /salir):</em></p>
    <form action="<?= e(ruta('salida')) ?>" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button type="submit">Cerrar sesión</button>
    </form>
</body>
</html>
