<?php
/** Marco común de registro e ingreso. Recibe $titulo y $contenido. */
require_once __DIR__ . '/ayudas.php';
require __DIR__ . '/cabeza.php';
?>
<body class="is-auth">
<div id="app">
<div class="auth">
    <section class="auth-art"><a class="brand lg" href="<?= e(ruta('ingreso')) ?>"><span class="brand-mark"><?= icono('bird') ?></span><span>TuCri</span></a>
        <div class="auth-claim"><h2>Tu aviario,<br>generación tras generación.</h2><p>Canarios, jaulas y crías en un mismo lugar.</p></div></section>
    <section class="auth-panel"><main class="auth-card" id="main"><?= $contenido ?></main>
        <p class="auth-foot">© <?= e(date('Y')) ?> TuCri</p></section>
</div>
</div>
<?php require __DIR__ . '/aviso.php'; ?>
</body>
</html>
