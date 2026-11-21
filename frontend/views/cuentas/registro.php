<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de cuenta — TuCriadero</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <h1>Registro de cuenta de criador</h1>
    <?php if ($flash): ?><p><strong><?= e($flash['mensaje']) ?></strong></p><?php endif; ?>
    <form action="<?= e(ruta('registro.guardar')) ?>" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div>
            <label>Nombre</label><br>
            <input type="text" name="nombre" value="<?= e(viejo('nombre')) ?>">
            <?php if (error('nombre')): ?><small style="color:red"><?= e(error('nombre')) ?></small><?php endif; ?>
        </div>
        <div>
            <label>Correo</label><br>
            <input type="email" name="correo" value="<?= e(viejo('correo')) ?>">
            <?php if (error('correo')): ?><small style="color:red"><?= e(error('correo')) ?></small><?php endif; ?>
        </div>
        <div>
            <label>Contraseña</label><br>
            <input type="password" name="contrasena">
            <?php if (error('contrasena')): ?><small style="color:red"><?= e(error('contrasena')) ?></small><?php endif; ?>
        </div>
        <div>
            <label><input type="checkbox" name="consentimiento" value="1"> Acepto el uso de mis datos (POL-001)</label>
            <?php if (error('consentimiento')): ?><br><small style="color:red"><?= e(error('consentimiento')) ?></small><?php endif; ?>
        </div>
        <button type="submit">Crear cuenta</button>
    </form>
</body>
</html>
