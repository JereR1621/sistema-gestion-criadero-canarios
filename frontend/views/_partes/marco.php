<?php
/**
 * Marco común de la aplicación con sesión (base visual de la maqueta aprobada + htmx 2.0.4).
 * Recibe: $titulo, $seccion (inicio | canarios | jaulas | crias | buscar | perfil | admin), $vista (clase view-…),
 * $volver (null o [url, texto]) y $contenido (HTML ya armado). Además, $sesion, $csrf y $flash del backend.
 */
require_once __DIR__ . '/ayudas.php';

$seccion ??= 'inicio';
$vista ??= $seccion;
$volver ??= null;
$acento = ['inicio' => 'green', 'canarios' => 'yellow', 'jaulas' => 'green', 'crias' => 'coral', 'buscar' => 'green', 'perfil' => 'green', 'admin' => 'blue'][$seccion] ?? 'green';
$enCrias = in_array($seccion, ['crias', 'canarios', 'jaulas'], true);
$nombre = (string) ($sesion['nombre'] ?? '');
$iniciales = implode('', array_map(
    static fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)),
    array_slice(preg_split('/\s+/', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2)
));
$esAdmin = ($sesion['rol'] ?? '') === 'admin';

$rutaOpcional = static function (string $nombreRuta): ?string {
    try {
        return ruta($nombreRuta);
    } catch (\InvalidArgumentException) {
        return null;
    }
};

$urlInicio = $rutaOpcional('inicio') ?? '/inicio';
$urlPerfil = $rutaOpcional('perfil');
$urlSalida = $rutaOpcional('salida');
$urlBuscar = $rutaOpcional('buscar');
$urlCruces = $rutaOpcional('cruces');
$urlCanarios = $rutaOpcional('canarios');
$urlJaulas = $rutaOpcional('jaulas');

$menuCrias = array_values(array_filter([
    $urlCruces !== null ? ['cruces', 'Libro de crías', 'book', 'crias', $urlCruces] : null,
    $urlCanarios !== null ? ['canarios', 'Canarios', 'bird', 'canarios', $urlCanarios] : null,
    $urlJaulas !== null ? ['jaulas', 'Jaulas', 'cage', 'jaulas', $urlJaulas] : null,
]));

$menuAdmin = ($esAdmin && is_file(__DIR__ . '/../admin/_menu.php'))
    ? require __DIR__ . '/../admin/_menu.php'
    : [];

$rutaActual = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$enAdmin = static fn (string $url): bool => str_starts_with($rutaActual, $url);
$enlaceLateral = static fn (string $url, string $texto, string $icono, bool $activo): string => '<a class="side-link' . ($activo ? ' on' : '') . '" href="' . e($url) . '"'
    . ($activo ? ' aria-current="page"' : '') . '>' . icono($icono) . '<span>' . e($texto) . '</span></a>';
$salir = $urlSalida !== null
    ? '<form method="post" action="' . e($urlSalida) . '" class="form-salir">' . csrf_campo($csrf ?? '') . '<button type="submit" role="menuitem" class="umenu-out">' . icono('logout') . 'Cerrar sesión</button></form>'
    : '';

require __DIR__ . '/cabeza.php';
?>
<body>
<div id="app">
<a class="skip-link" href="#main">Ir al contenido</a>
<div class="layout accent-<?= e($acento) ?> view-<?= e($vista) ?><?= $seccion === 'inicio' ? ' view-home' : '' ?>">
    <header class="topbar">
        <button class="top-btn only-m" type="button" data-act="mobile-menu" aria-label="Abrir menú" aria-haspopup="dialog" aria-controls="mobile-menu"><?= icono('menu') ?></button>
        <a class="brand" href="<?= e($urlInicio) ?>"><span class="brand-mark"><?= icono('bird') ?></span><span>TuCri</span></a>
        <nav class="desktop-nav" aria-label="Menú principal">
            <?php if ($esAdmin): ?>
            <?php foreach ($menuAdmin as [$r, $texto, $ic, $url]): ?><?= $enlaceLateral($url, $texto, $ic, $enAdmin($url)) ?><?php endforeach; ?>
            <?php else: ?>
            <?= $enlaceLateral($urlInicio, 'Inicio', 'home', $seccion === 'inicio') ?>
            <?php if ($menuCrias !== []): ?>
            <div class="umenu-wrap nav-drop">
                <button type="button" class="side-link<?= $enCrias ? ' on' : '' ?>" data-act="umenu" aria-haspopup="menu" aria-expanded="false"><?= icono('book') ?><span>Libro de crías</span><?= icono('chev', 'drop-chev') ?></button>
                <div class="umenu nav-menu" role="menu" hidden>
                    <?php foreach ($menuCrias as [$r, $texto, $ic, $sec, $url]): ?>
                        <a role="menuitem" href="<?= e($url) ?>"<?= $seccion === $sec ? ' aria-current="page"' : '' ?>><?= icono($ic) ?><?= e($texto) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </nav>
        <?php if (!$esAdmin && $urlBuscar !== null): ?>
        <form class="top-search" method="get" action="<?= e($urlBuscar) ?>" role="search">
            <input type="hidden" name="tipo" value="anillo">
            <?= icono('search') ?><input type="search" name="q" placeholder="Anillo o correlativo" aria-label="Anillo o correlativo">
        </form>
        <a class="top-btn only-m" href="<?= e($urlBuscar) ?>" aria-label="Buscar"><?= icono('search') ?></a>
        <?php endif; ?>
        <div class="umenu-wrap">
            <button class="top-btn profile-btn" type="button" data-act="umenu" aria-haspopup="menu" aria-expanded="false" aria-label="Menú de <?= e($nombre) ?>"><span class="profile-label">Mi perfil</span><span class="profile-initials"><?= e($iniciales) ?></span></button>
            <div class="umenu" role="menu" hidden>
                <p class="umenu-name"><?= e($nombre) ?><small><?= $esAdmin ? 'Administrador' : 'Criador' ?></small></p>
                <?php if ($esAdmin): ?>
                    <?php foreach ($menuAdmin as [$r, $texto, $ic, $url]): ?>
                    <a role="menuitem" href="<?= e($url) ?>"><?= icono($ic) ?><?= e($texto) ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                <?php if ($urlPerfil !== null): ?><a role="menuitem" href="<?= e($urlPerfil) ?>"><?= icono('user') ?>Mi perfil</a><?php endif; ?>
                <?php if ($urlBuscar !== null): ?><a role="menuitem" href="<?= e($urlBuscar) ?>"><?= icono('search') ?>Buscar, incluidas bajas</a><?php endif; ?>
                <?php endif; ?>
                <?= $salir ?>
            </div>
        </div>
    </header>
    <dialog class="mobile-drawer" id="mobile-menu" aria-labelledby="mobile-menu-title">
        <header class="drawer-head"><a class="brand" href="<?= e($urlInicio) ?>"><span id="mobile-menu-title">TuCri</span></a><button class="drawer-close" type="button" data-act="mobile-menu-close" aria-label="Cerrar menú"><?= icono('exit') ?></button></header>
        <p class="drawer-season"><?= $esAdmin ? 'Administración de TuCri' : 'Tu aviario · Temporada ' . e(date('Y')) ?></p>
        <nav class="drawer-nav" aria-label="Menú móvil">
            <?php if ($esAdmin): ?>
            <?php foreach ($menuAdmin as [$r, $texto, $ic, $url]): ?><?= $enlaceLateral($url, $texto, $ic, $enAdmin($url)) ?><?php endforeach; ?>
            <?php else: ?>
            <?= $enlaceLateral($urlInicio, 'Inicio', 'home', $seccion === 'inicio') ?>
            <?php foreach ($menuCrias as [$r, $texto, $ic, $sec, $url]): ?><?= $enlaceLateral($url, $texto, $ic, $seccion === $sec) ?><?php endforeach; ?>
            <?php if ($urlBuscar !== null): ?><?= $enlaceLateral($urlBuscar, 'Buscar, incluidas bajas', 'search', $seccion === 'buscar') ?><?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="drawer-foot">
            <?php if ($urlPerfil !== null): ?>
            <a class="drawer-profile" href="<?= e($urlPerfil) ?>"><span class="profile-initials"><?= e($iniciales) ?></span><span><strong><?= e($nombre) ?></strong><small>Mi perfil</small></span><?= icono('chev') ?></a>
            <?php endif; ?>
            <?php if ($urlSalida !== null): ?>
            <form method="post" action="<?= e($urlSalida) ?>"><?= csrf_campo($csrf ?? '') ?><button type="submit" class="side-link"><?= icono('logout') ?><span>Cerrar sesión</span></button></form>
            <?php endif; ?>
        </div>
    </dialog>
    <main class="main" id="main" tabindex="-1">
        <nav class="sin-js fallback-nav" aria-label="Navegación sin JavaScript">
            <a href="<?= e($urlInicio) ?>">Inicio</a>
            <?php if ($esAdmin): ?>
                <?php foreach ($menuAdmin as [$r, $texto, $ic, $url]): ?><a href="<?= e($url) ?>"><?= e($texto) ?></a><?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($menuCrias as [$r, $texto, $ic, $sec, $url]): ?><a href="<?= e($url) ?>"><?= e($texto) ?></a><?php endforeach; ?>
                <?php if ($urlPerfil !== null): ?><a href="<?= e($urlPerfil) ?>">Mi perfil</a><?php endif; ?>
                <?php if ($urlBuscar !== null): ?><a href="<?= e($urlBuscar) ?>">Buscar</a><?php endif; ?>
            <?php endif; ?>
            <?php if ($urlSalida !== null): ?>
                <form method="post" action="<?= e($urlSalida) ?>"><?= csrf_campo($csrf ?? '') ?><button type="submit">Cerrar sesión</button></form>
            <?php endif; ?>
        </nav>
        <?php if ($volver !== null): ?><div class="page-back"><a class="top-back" href="<?= e($volver[0]) ?>"><?= icono('back') ?><span><?= e($volver[1]) ?></span></a></div><?php endif; ?>
        <?= $contenido ?>
    </main>
    <nav class="tabbar" aria-label="Navegación principal">
        <?php if ($esAdmin): ?>
        <?php foreach ($menuAdmin as [$r, $texto, $ic, $url]): $activo = $enAdmin($url); ?>
        <a class="tab-link<?= $activo ? ' on' : '' ?>" href="<?= e($url) ?>"<?= $activo ? ' aria-current="page"' : '' ?>><?= icono($ic) ?><span><?= e($texto) ?></span></a>
        <?php endforeach; ?>
        <?php else: ?>
        <a class="tab-link<?= $seccion === 'inicio' ? ' on' : '' ?>" href="<?= e($urlInicio) ?>"<?= $seccion === 'inicio' ? ' aria-current="page"' : '' ?>><?= icono('home') ?><span>Inicio</span></a>
        <?php if ($menuCrias !== []): ?>
        <a class="tab-link<?= $enCrias ? ' on' : '' ?>" href="<?= e($menuCrias[0][4]) ?>" data-act="menu-crias" aria-haspopup="dialog"><?= icono('book') ?><span>Libro de crías</span></a>
        <?php endif; ?>
        <?php if ($urlPerfil !== null): ?>
        <a class="tab-link<?= $seccion === 'perfil' ? ' on' : '' ?>" href="<?= e($urlPerfil) ?>"<?= $seccion === 'perfil' ? ' aria-current="page"' : '' ?>><?= icono('user') ?><span>Perfil</span></a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>
</div>
</div>
<?php require __DIR__ . '/aviso.php'; ?>
<div id="sheet-root"></div>
<template id="hoja-crias">
    <h2>Libro de crías</h2>
    <div class="quick">
        <?php if ($urlCruces !== null): ?><a class="action t-coral" href="<?= e($urlCruces) ?>"><span class="a-ic"><?= icono('book') ?></span><strong>Libro de crías</strong><span>Cruces y pichones por temporada</span></a><?php endif; ?>
        <?php if ($urlCanarios !== null): ?><a class="action t-yellow" href="<?= e($urlCanarios) ?>"><span class="a-ic"><?= icono('bird') ?></span><strong>Canarios</strong><span>Todos los canarios del criadero</span></a><?php endif; ?>
        <?php if ($urlJaulas !== null): ?><a class="action t-green" href="<?= e($urlJaulas) ?>"><span class="a-ic"><?= icono('cage') ?></span><strong>Jaulas</strong><span>Quién está en cada jaula</span></a><?php endif; ?>
    </div>
    <div class="sheet-actions"><button class="btn btn-ghost" type="button" data-act="close"><span>Cerrar</span></button></div>
</template>
</body>
</html>
