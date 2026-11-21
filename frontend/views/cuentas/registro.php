<?php

use TuCriadero\Core\Vista;

?>

<h1>Crear cuenta</h1>

<?php if (isset($errores['general'])): ?>
    <p class="error">
        <?= e($errores['general']) ?>
    </p>
<?php endif; ?>

<form action="/registro" method="POST">

    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <label for="nombre">Nombre</label>
    <input
        type="text"
        id="nombre"
        name="nombre"
        maxlength="100"
        required
        value="<?= e(viejo('nombre')) ?>"
    >

    <label for="correo">Correo electrónico</label>
    <input
        type="email"
        id="correo"
        name="correo"
        maxlength="150"
        required
        value="<?= e(viejo('correo')) ?>"
    >

    <label for="contrasena">Contraseña</label>
    <input
        type="password"
        id="contrasena"
        name="contrasena"
        minlength="10"
        required
    >

    <label for="confirmar_contrasena">Confirmar contraseña</label>
    <input
        type="password"
        id="confirmar_contrasena"
        name="confirmar_contrasena"
        minlength="10"
        required
    >

    <label>
        <input
            type="checkbox"
            name="consentimiento"
            value="1"
            required
        >
        Acepto el tratamiento de mis datos personales.
    </label>

    <button type="submit">Registrarme</button>

</form>