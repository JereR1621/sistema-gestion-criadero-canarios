<?php
/**
 * Vista: frontend/views/jaulas/lista.php
 * Issue: #103 (SRS-RF014 [Frontend]: Registro de jaula)
 * Usa el marco aprobado del Plan B (_partes/marco.php) con htmx 2.0.4 y animaciones progresivas.
 * Recibe: $jaulas [JaulaResumen].
 */
require_once __DIR__ . '/../_partes/ayudas.php';

$titulo ??= 'Jaulas';
$seccion = 'jaulas';
$seleccionada ??= null;

$jaulasLista = [];
if (isset($jaulas) && is_iterable($jaulas)) {
    $jaulasLista = is_array($jaulas) ? $jaulas : iterator_to_array($jaulas);
}

$errorNumero = error('numero');
$errorGeneral = error('_general');
$valorNumero = (string) viejo('numero', '');
$conError = $errorNumero !== '' || ($errorGeneral !== '' && $seleccionada === null);

ob_start(); ?>
<details class="act-more agregar-jaula" data-hoja-detalles<?= $conError ? ' open' : '' ?>>
    <summary class="btn btn-primary"><?= icono('plus') ?><span>Agregar jaula</span></summary>
    <div class="sheet-list" data-titulo="Agregar jaula">
        <form class="form" method="post" action="<?= e(ruta('jaulas.guardar')) ?>">
            <?= csrf_campo($csrf ?? '') ?>
            <?= $seleccionada === null ? error_general() : '' ?>
            <div class="field<?= $errorNumero !== '' ? ' has-error' : '' ?>">
                <label class="lbl" for="numero">Número de jaula</label>
                <input
                    id="numero"
                    name="numero"
                    type="text"
                    inputmode="numeric"
                    pattern="[1-9][0-9]*"
                    value="<?= e($valorNumero) ?>"
                    placeholder="Ej.: 12"
                    <?= $errorNumero !== '' ? 'aria-invalid="true" aria-describedby="error-numero"' : '' ?>
                    required
                >
                <p class="hint">Número único dentro de tu criadero.</p>
                <?php if ($errorNumero !== ''): ?>
                    <span id="error-numero" class="field-error" role="alert"><?= e($errorNumero) ?></span>
                <?php endif; ?>
            </div>
            <div class="sheet-actions"><?= boton('Guardar jaula', 'type="submit"', 'primary') ?></div>
        </form>
    </div>
</details>
<?php $accion = (string) ob_get_clean(); ob_start(); ?>
<?= encabezado_pagina('Jaulas', 'Toca una jaula para ver quién está y su historial. La ocupación se calcula con los traslados.', 'cage', $accion) ?>
<?php if ($jaulasLista === []): ?>
    <div class="empty empty-art">
        <?= jaula_ilustrada() ?>
        <p><strong>Todavía no hay jaulas registradas en el criadero.</strong><br>Agrega tu primera jaula con su número.</p>
    </div>
<?php else: ?>
    <div class="cage-grid" aria-label="Listado de jaulas">
    <?php foreach ($jaulasLista as $j):
        $x = is_array($j['cruce_activo'] ?? null) ? $j['cruce_activo'] : null;
        $n = (int) ($j['ocupantes'] ?? 0);
        $urlJaula = (string) ($j['url'] ?? '');
        $esSel = $seleccionada !== null && $seleccionada === ($j['id'] ?? null);
        $numCruce = $x !== null ? (string) ($x['numero'] ?? ($x['id'] ?? '')) : '';
        $macho = $x !== null && is_array($x['macho'] ?? null) ? $x['macho'] : null;
        $hembra = $x !== null && is_array($x['hembra'] ?? null) ? $x['hembra'] : null;
    ?>
        <?php if ($urlJaula !== ''): ?>
        <a class="cage<?= $esSel ? ' sel' : '' ?>" href="<?= e($urlJaula) ?>" hx-get="<?= e($urlJaula) ?>" hx-target="#cage-detail" hx-select="#cage-detail" hx-swap="outerHTML show:#cage-detail:top"<?= $esSel ? ' aria-current="true"' : '' ?>>
        <?php else: ?>
        <div class="cage<?= $esSel ? ' sel' : '' ?>">
        <?php endif; ?>
            <?= jaula_ilustrada() ?>
            <span class="cage-name">Jaula <?= e((string) ($j['numero'] ?? '')) ?></span>
            <span class="cage-occ"><?= e(ocupantes_texto($n)) ?></span>
            <?php if ($x !== null && !empty($x['activo'])): ?>
                <span class="cage-pair">
                    <span class="pill coral">Cruce<?= $numCruce !== '' ? ' ' . e(str_pad($numCruce, 2, '0', STR_PAD_LEFT)) : ' activo' ?></span>
                    <?php if ($macho !== null && $hembra !== null): ?>
                        <span class="pair-stack"><?= pareja_anillos($macho, $hembra) ?></span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        <?php if ($urlJaula !== ''): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $detalle ?? '<div id="cage-detail"></div>' ?>
<?php $contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/marco.php';
