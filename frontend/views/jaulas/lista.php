<?php
/**
 * Vista: frontend/views/jaulas/lista.php
 * Issue: #103 (SRS-RF014 [Frontend]: Registro de jaula)
 */

// Iniciales del usuario, como en el marco del Plan B; sin datos de demostración.
$nombreSesion = (string) ($sesion['nombre'] ?? '');
$iniciales = implode('', array_map(
    static fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)),
    array_slice(preg_split('/\s+/', trim($nombreSesion), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2)
));
// Estos módulos tienen issues propias. No se muestran controles hasta que exista su ruta.
$urlCruces = null;
$urlBuscar = null;
try { $urlCruces = ruta('cruces'); } catch (\InvalidArgumentException) {}
try { $urlBuscar = ruta('buscar'); } catch (\InvalidArgumentException) {}

$jaulasLista = [];
if (isset($jaulas) && is_iterable($jaulas)) {
    $jaulasLista = is_array($jaulas) ? $jaulas : iterator_to_array($jaulas);
}

$tokenCsrf = is_string($csrf ?? null) ? $csrf : '';
$flashData = is_array($flash ?? null) ? $flash : null;
$errorNumero = error('numero');
$errorGeneral = is_array($errores ?? null) ? (string) ($errores['_general'] ?? '') : '';
$valorNumero = (string) viejo('numero', '');
$abrirFormulario = $errorNumero !== '' || $errorGeneral !== '' || $valorNumero !== '';

if (!function_exists('claseEstadoJaula')) {
    function claseEstadoJaula(int $ocupantes, bool $tieneCruceActivo): string
    {
        if ($ocupantes === 0) {
            return 'is-off';
        }
        if ($tieneCruceActivo) {
            return 'is-ok';
        }
        return 'is-warn';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jaulas — TuCri</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/jaulas.css">
</head>
<body class="tucri-body">
    <header class="topbar">
        <div class="topbar__inner">
            <div class="topbar__left">
                <a class="brand" href="<?= e(ruta('inicio')) ?>">
                    <svg class="brand__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.5 5.5a3.5 3.5 0 0 0-6.6 1.6L9 9c-3 .5-5.5 2.6-6 6 2.5 1.3 6 1.5 8.5.5L13 19h3l-1.6-3.6C17 14 18.5 11.5 18.5 9l2.5-1-2.6-1.5a3.5 3.5 0 0 0-2.9-1zM15.5 7.5h.01"/></svg>
                    <span class="brand__name">TuCri</span>
                </a>
                <nav class="nav" aria-label="Navegación principal">
                    <a class="nav__link" href="<?= e(ruta('inicio')) ?>">Inicio</a>
                    <?php if ($urlCruces !== null): ?>
                    <a class="nav__link" href="<?= e($urlCruces) ?>">Libro de crías</a>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="topbar__right">
                <?php if ($urlBuscar !== null): ?>
                <form class="search-inline" method="get" action="<?= e($urlBuscar) ?>">
                    <input type="hidden" name="tipo" value="anillo">
                    <label class="sr-only" for="buscar-top">Anillo o correlativo</label>
                    <input
                        id="buscar-top"
                        class="search-inline__input"
                        type="search"
                        name="q"
                        placeholder="Anillo o correlativo"
                    >
                </form>
                <?php endif; ?>
                <a class="avatar-chip" href="<?= e(ruta('perfil')) ?>" aria-label="Mi perfil"><?= e($iniciales) ?></a>
            </div>
        </div>
    </header>

    <main class="jaulas-page">
        <section class="jaulas-banner" aria-labelledby="titulo-jaulas">
            <div class="jaulas-banner__info">
                <div class="jaulas-banner__icono" aria-hidden="true">
                    <svg class="jaula-icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v15H4zM3 4h18M8 4v15M12 4v15M16 4v15M4 11.5h16M6 19v2M18 19v2"/></svg>
                </div>
                <div>
                    <h1 id="titulo-jaulas" class="jaulas-banner__titulo">Jaulas</h1>
                    <p class="jaulas-banner__texto">
                        Toca una jaula para ver quién está y su historial. La ocupación se calcula con los traslados.
                    </p>
                </div>
            </div>

            <a class="btn-agregar" href="#form-alta-jaula">+ Agregar jaula</a>
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

        <section id="form-alta-jaula" class="alta-panel<?= $abrirFormulario ? ' alta-panel--activa' : '' ?>" aria-labelledby="titulo-alta-jaula">
            <div class="alta-panel__head">
                <h2 id="titulo-alta-jaula" class="alta-panel__titulo">Agregar jaula</h2>
                <p class="alta-panel__ayuda">Ingresa un número de jaula único en tu criadero.</p>
            </div>

            <form class="alta-form" method="post" action="<?= e(ruta('jaulas.guardar')) ?>">
                <input type="hidden" name="_csrf" value="<?= e($tokenCsrf) ?>">

                <div class="alta-form__campo">
                    <label class="alta-form__label" for="numero">Número de jaula</label>
                    <input
                        id="numero"
                        name="numero"
                        class="alta-form__input"
                        type="text"
                        inputmode="numeric"
                        pattern="[1-9][0-9]*"
                        value="<?= e($valorNumero) ?>"
                        placeholder="Ej. 12"
                        <?php if ($errorNumero !== ''): ?>
                            aria-invalid="true"
                            aria-describedby="error-numero"
                        <?php endif; ?>
                        required
                    >
                    <?php if ($errorNumero !== ''): ?>
                        <span id="error-numero" class="alta-form__error" role="alert"><?= e($errorNumero) ?></span>
                    <?php endif; ?>
                </div>

                <button class="btn-guardar" type="submit">Guardar jaula</button>
            </form>
        </section>

        <?php if (empty($jaulasLista)): ?>
            <section class="jaulas-vacio" aria-live="polite">
                <p class="jaulas-vacio__texto">Todavía no hay jaulas registradas en el criadero.</p>
            </section>
        <?php else: ?>
            <section class="jaulas-grid" aria-label="Listado de jaulas">
                <?php foreach ($jaulasLista as $jaula): ?>
                    <?php
                    $numeroJaula = (string) ($jaula['numero'] ?? '');
                    $ocupantes = (int) ($jaula['ocupantes'] ?? 0);
                    $cruceActivo = $jaula['cruce_activo'] ?? null;
                    $urlJaula = (string) ($jaula['url'] ?? '');
                    $tieneCruceActivo = is_array($cruceActivo) && !empty($cruceActivo['activo']);
                    $codigoCruce = is_array($cruceActivo) ? (string) ($cruceActivo['codigo'] ?? ($cruceActivo['id'] ?? '')) : '';
                    $macho = is_array($cruceActivo) && is_array($cruceActivo['macho'] ?? null) ? $cruceActivo['macho'] : null;
                    $hembra = is_array($cruceActivo) && is_array($cruceActivo['hembra'] ?? null) ? $cruceActivo['hembra'] : null;
                    $estadoClase = claseEstadoJaula($ocupantes, $tieneCruceActivo);
                    ?>
                    <article class="jaula-card <?= e($estadoClase) ?>">
                        <?php if ($urlJaula !== ''): ?>
                            <a class="jaula-card__link" href="<?= e($urlJaula) ?>">
                        <?php else: ?>
                            <div class="jaula-card__link">
                        <?php endif; ?>

                            <div class="jaula-card__media">
                                <svg class="jaula-card__img" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v15H4zM3 4h18M8 4v15M12 4v15M16 4v15M4 11.5h16M6 19v2M18 19v2"/></svg>
                            </div>

                            <div class="jaula-card__cabecera">
                                <h2 class="jaula-card__titulo">Jaula <span class="num-tabular"><?= e($numeroJaula) ?></span></h2>
                                <span class="jaula-estado-badge <?= e($estadoClase) ?>">
                                    <?php if ($ocupantes === 0): ?>
                                        Vacía
                                    <?php elseif ($tieneCruceActivo): ?>
                                        Cruce
                                    <?php else: ?>
                                        Ocupada
                                    <?php endif; ?>
                                </span>
                            </div>

                            <?php if ($ocupantes === 0): ?>
                                <p class="jaula-card__estado">Sin aves</p>
                            <?php else: ?>
                                <p class="jaula-card__estado">
                                    <span class="num-tabular"><?= e((string) $ocupantes) ?></span>
                                    <?= e($ocupantes === 1 ? 'ocupante' : 'ocupantes') ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($tieneCruceActivo): ?>
                                <p class="jaula-card__cruce num-tabular">
                                    <span class="jaula-card__punto" aria-hidden="true">•</span>
                                    <span>Cruce<?= $codigoCruce !== '' ? ' ' . e($codigoCruce) : ' activo' ?></span>
                                </p>
                            <?php endif; ?>

                            <?php if ($macho !== null || $hembra !== null): ?>
                                <div class="jaula-card__aves">
                                    <?php if ($macho !== null): ?>
                                        <span class="jaula-card__ave">
                                            <span class="sexo-macho" aria-hidden="true">♂</span>
                                            <span class="num-tabular"><?= e((string) ($macho['anillo_texto'] ?? '')) ?></span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($hembra !== null): ?>
                                        <span class="jaula-card__ave">
                                            <span class="sexo-hembra" aria-hidden="true">♀</span>
                                            <span class="num-tabular"><?= e((string) ($hembra['anillo_texto'] ?? '')) ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                        <?php if ($urlJaula !== ''): ?>
                            </a>
                        <?php else: ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>