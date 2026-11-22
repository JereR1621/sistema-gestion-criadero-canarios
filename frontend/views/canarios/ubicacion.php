<?php
/**
 * Vista: frontend/views/canarios/ubicacion.php
 * Issue: #105 (SRS-RF015 [Frontend]: Asignar canario a una jaula)
 */

if (!function_exists('e')) {
    function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('ruta')) {
    function ruta(string $nombre, array $params = []): string
    {
        return '#';
    }
}

if (!function_exists('viejo')) {
    function viejo(string $campo, mixed $defecto = ''): mixed
    {
        return $defecto;
    }
}

if (!function_exists('error')) {
    function error(string $campo): string
    {
        return '';
    }
}

$canarioData = is_array($canario ?? null) ? $canario : [];
$idCanario = (string) ($canarioData['id'] ?? '');
$anilloCanario = (string) ($canarioData['anillo_texto'] ?? ($canarioData['anillo'] ?? ''));
$sexoCanario = strtolower((string) ($canarioData['sexo'] ?? ''));
$variedadCanario = (string) ($canarioData['variedad'] ?? ($canarioData['variedad_nombre'] ?? ''));
$ubicacionActual = $canarioData['ubicacion_actual'] ?? ($canarioData['jaula_actual'] ?? null);

$textoUbicacionActual = 'Jaula grande (sin número)';
if (is_array($ubicacionActual)) {
    $numActual = (string) ($ubicacionActual['numero'] ?? '');
    if ($numActual !== '') {
        $textoUbicacionActual = 'Jaula ' . $numActual;
    } elseif (!empty($ubicacionActual['es_grande']) || empty($ubicacionActual['id'])) {
        $textoUbicacionActual = 'Jaula grande (sin número)';
    }
} elseif (is_string($ubicacionActual) && trim($ubicacionActual) !== '') {
    $textoUbicacionActual = trim($ubicacionActual);
}

$jaulasLista = [];
if (isset($jaulas) && is_iterable($jaulas)) {
    $jaulasLista = is_array($jaulas) ? $jaulas : iterator_to_array($jaulas);
}

$tokenCsrf = is_string($csrf ?? null) ? $csrf : '';
$flashData = is_array($flash ?? null) ? $flash : null;
$errorJaula = error('jaula_id');
$errorFecha = error('fecha');
$errorGeneral = is_array($errores ?? null) ? (string) ($errores['_general'] ?? '') : '';

$valorJaulaId = (string) viejo('jaula_id', '');
$valorFecha = (string) viejo('fecha', '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asignar canario a jaula — TuCri</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/frontend/css/app.css">
    <link rel="stylesheet" href="/frontend/css/jaulas.css">
    <link rel="stylesheet" href="/frontend/css/canarios-ubicacion.css">
</head>
<body class="tucri-body">
    <header class="topbar">
        <div class="topbar__inner">
            <div class="topbar__left">
                <a class="brand" href="<?= e(ruta('inicio')) ?>">
                    <img class="brand__icon" src="/assets/logo.png" alt="">
                    <span class="brand__name">TuCri</span>
                </a>
                <nav class="nav" aria-label="Navegación principal">
                    <a class="nav__link" href="<?= e(ruta('inicio')) ?>">Inicio</a>
                    <a class="nav__link" href="<?= e(ruta('cruces')) ?>">Libro de crías</a>
                </nav>
            </div>

            <div class="topbar__right">
                <form class="search-inline" method="get" action="<?= e(ruta('buscar')) ?>">
                    <input type="hidden" name="tipo" value="anillo">
                    <label class="sr-only" for="buscar-top">Anillo o correlativo</label>
                    <input
                        id="buscar-top"
                        class="search-inline__input"
                        type="search"
                        name="q"
                        placeholder="Buscar anillo, variedad o jaula"
                    >
                </form>
                <a class="avatar-chip" href="<?= e(ruta('perfil')) ?>" aria-label="Mi perfil">JC</a>
            </div>
        </div>
    </header>

    <main class="jaulas-page">
        <section class="jaulas-banner" aria-labelledby="titulo-ubicacion-canario">
            <div class="jaulas-banner__info">
                <div class="jaulas-banner__icono" aria-hidden="true">
                    <img src="/assets/jaula.jpg" alt="">
                </div>
                <div>
                    <h1 id="titulo-ubicacion-canario" class="jaulas-banner__titulo">Asignar o trasladar canario</h1>
                    <p class="jaulas-banner__texto">
                        Asigna el canario a una jaula numerada o a la jaula grande, que no tiene número, indicando la fecha de ingreso.
                    </p>
                </div>
            </div>

            <a class="btn-agregar" href="<?= e(ruta('jaulas')) ?>">Ver jaulas</a>
        </section>

        <?php if ($flashData !== null && !empty($flashData['mensaje'])): ?>
            <?php
            $tipoFlash = (string) ($flashData['tipo'] ?? '');
            $claseFlash = 'alert-box';
            if ($tipoFlash === 'exito') {
                $claseFlash .= ' alert-box--exito';
            } elseif ($tipoFlash === 'error') {
                $claseFlash .= ' alert-box--error';
            } elseif ($tipoFlash === 'aviso') {
                $claseFlash .= ' alert-box--aviso';
            }
            ?>
            <div class="<?= e($claseFlash) ?>" role="status">
                <?= e((string) $flashData['mensaje']) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorGeneral !== ''): ?>
            <div class="alert-box alert-box--error" role="alert">
                <?= e($errorGeneral) ?>
            </div>
        <?php endif; ?>

        <section class="ubicacion-canario-ficha" aria-label="Resumen del canario">
            <div class="ubicacion-canario-ficha__item">
                <span class="ubicacion-canario-ficha__etiqueta">Anillo</span>
                <strong class="ubicacion-canario-ficha__valor num-tabular">
                    <?= e($anilloCanario !== '' ? $anilloCanario : ('#' . $idCanario)) ?>
                </strong>
            </div>

            <?php if ($sexoCanario === 'm' || $sexoCanario === 'macho' || $sexoCanario === 'h' || $sexoCanario === 'hembra'): ?>
                <div class="ubicacion-canario-ficha__item">
                    <span class="ubicacion-canario-ficha__etiqueta">Sexo</span>
                    <span class="ubicacion-canario-ficha__valor <?= ($sexoCanario === 'm' || $sexoCanario === 'macho') ? 'sexo-macho' : 'sexo-hembra' ?>">
                        <?php if ($sexoCanario === 'm' || $sexoCanario === 'macho'): ?>
                            <span aria-hidden="true">♂</span> Macho
                        <?php else: ?>
                            <span aria-hidden="true">♀</span> Hembra
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($variedadCanario !== ''): ?>
                <div class="ubicacion-canario-ficha__item">
                    <span class="ubicacion-canario-ficha__etiqueta">Variedad</span>
                    <span class="ubicacion-canario-ficha__valor"><?= e($variedadCanario) ?></span>
                </div>
            <?php endif; ?>

            <div class="ubicacion-canario-ficha__item">
                <span class="ubicacion-canario-ficha__etiqueta">Ubicación actual</span>
                <span class="ubicacion-canario-ficha__valor num-tabular"><?= e($textoUbicacionActual) ?></span>
            </div>
        </section>

        <section class="alta-panel alta-panel--activa" aria-labelledby="titulo-form-ubicacion">
            <div class="alta-panel__head">
                <h2 id="titulo-form-ubicacion" class="alta-panel__titulo">Destino y fecha de ingreso</h2>
                <p class="alta-panel__ayuda">
                    Elige una jaula numerada o deja la opción «Jaula grande (sin número)».
                </p>
            </div>

            <form class="ubicacion-form" method="post" action="<?= e(ruta('canarios.ubicacion.guardar', ['id' => $idCanario])) ?>">
                <input type="hidden" name="_csrf" value="<?= e($tokenCsrf) ?>">

                <div class="alta-form__campo">
                    <label class="alta-form__label" for="jaula_id">Jaula de destino</label>
                    <select
                        id="jaula_id"
                        name="jaula_id"
                        class="alta-form__input ubicacion-form__select"
                        <?php if ($errorJaula !== ''): ?>
                            aria-invalid="true"
                            aria-describedby="error-jaula-id"
                        <?php endif; ?>
                    >
                        <option value=""<?= $valorJaulaId === '' ? ' selected' : '' ?>>
                            Jaula grande (sin número)
                        </option>
                        <?php foreach ($jaulasLista as $jaula): ?>
                            <?php
                            $jaulaIdItem = (string) ($jaula['id'] ?? '');
                            $jaulaNumeroItem = (string) ($jaula['numero'] ?? '');
                            $ocupantesItem = (int) ($jaula['ocupantes'] ?? 0);
                            $etiquetaJaula = $jaulaNumeroItem !== ''
                                ? 'Jaula ' . $jaulaNumeroItem . ' (' . $ocupantesItem . ' ' . ($ocupantesItem === 1 ? 'ocupante' : 'ocupantes') . ')'
                                : 'Jaula #' . $jaulaIdItem;
                            ?>
                            <option
                                value="<?= e($jaulaIdItem) ?>"
                                <?= $valorJaulaId !== '' && $valorJaulaId === $jaulaIdItem ? ' selected' : '' ?>
                            >
                                <?= e($etiquetaJaula) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($errorJaula !== ''): ?>
                        <span id="error-jaula-id" class="alta-form__error" role="alert"><?= e($errorJaula) ?></span>
                    <?php endif; ?>
                </div>

                <div class="alta-form__campo">
                    <label class="alta-form__label" for="fecha">Fecha de ingreso</label>
                    <input
                        id="fecha"
                        name="fecha"
                        class="alta-form__input"
                        type="date"
                        value="<?= e($valorFecha) ?>"
                        <?php if ($errorFecha !== ''): ?>
                            aria-invalid="true"
                            aria-describedby="error-fecha"
                        <?php endif; ?>
                        required
                    >
                    <?php if ($errorFecha !== ''): ?>
                        <span id="error-fecha" class="alta-form__error" role="alert"><?= e($errorFecha) ?></span>
                    <?php endif; ?>
                </div>

                <button class="btn-guardar" type="submit">Guardar</button>
            </form>
        </section>
    </main>
</body>
</html>