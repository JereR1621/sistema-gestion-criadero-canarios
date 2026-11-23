<?php
/**
 * Vista: frontend/views/canarios/ubicacion.php
 * Issues: #105 (SRS-RF015) y #107 (SRS-RF016 [Frontend]: Traslado a la jaula grande)
 * Usa el marco común (_partes/marco.php) con htmx 2.0.4 y hoja arrastrable (#hoja).
 * Recibe: $canario CanarioResumen, $jaulas [JaulaResumen].
 * Campos del formulario: jaula_id (vacío = jaula grande), fecha.
 */
require_once __DIR__ . '/../_partes/ayudas.php';

$canarioData = is_array($canario ?? null) ? $canario : [];
$idCanario = (string) ($canarioData['id'] ?? '');
$anilloCanario = (string) ($canarioData['anillo_texto'] ?? '');
$sexoCanario = strtolower((string) ($canarioData['sexo'] ?? ''));
$variedadCanario = is_array($canarioData['variedad'] ?? null)
    ? (string) ($canarioData['variedad']['nombre'] ?? '')
    : '';
$textoUbicacionActual = (string) ($canarioData['ubicacion_texto'] ?? 'Sin ubicación');
$urlCanario = (string) ($canarioData['url'] ?? ($idCanario !== '' ? '/canarios/' . $idCanario : '#'));

$jaulasLista = [];
if (isset($jaulas) && is_iterable($jaulas)) {
    $jaulasLista = is_array($jaulas) ? $jaulas : iterator_to_array($jaulas);
}

$titulo = 'Mover de jaula';
$seccion = 'canarios';
$volver = [$urlCanario, 'Ficha del canario'];

$errorJaula = error('jaula_id');
$errorFecha = error('fecha');
$valorJaulaId = (string) viejo('jaula_id', '');
$valorFecha = (string) viejo('fecha', hoy());

ob_start(); ?>
<h1 class="sr-only">Mover <?= e($anilloCanario !== '' ? $anilloCanario : ('#' . $idCanario)) ?> de jaula</h1>
<div id="hoja" class="hoja-form" data-titulo="Mover <?= e($anilloCanario !== '' ? $anilloCanario : ('#' . $idCanario)) ?>">
    <h2>Mover <?= e($anilloCanario !== '' ? $anilloCanario : ('#' . $idCanario)) ?></h2>
    <p class="muted">
        <?php if ($variedadCanario !== ''): ?><?= e($variedadCanario) ?> · <?php endif; ?>
        Ubicación actual: <strong><?= e($textoUbicacionActual) ?></strong>. Elige el destino:
    </p>
    <?= error_general() ?>
    <form class="form" method="post" action="<?= e(ruta('canarios.ubicacion.guardar', ['id' => $idCanario])) ?>">
        <?= csrf_campo($csrf ?? '') ?>
        <div class="field<?= $errorJaula !== '' ? ' has-error' : '' ?>">
            <label class="lbl" for="jaula_id">Jaula de destino</label>
            <select
                id="jaula_id"
                name="jaula_id"
                <?= $errorJaula !== '' ? 'aria-invalid="true" aria-describedby="error-jaula-id"' : '' ?>
            >
                <option value=""<?= $valorJaulaId === '' ? ' selected' : '' ?>>
                    Jaula grande (sin número)
                </option>
                <?php foreach ($jaulasLista as $j):
                    $jaulaIdItem = (string) ($j['id'] ?? '');
                    $jaulaNumeroItem = (string) ($j['numero'] ?? '');
                    $ocupantesItem = (int) ($j['ocupantes'] ?? 0);
                    $etiquetaJaula = $jaulaNumeroItem !== ''
                        ? 'Jaula ' . $jaulaNumeroItem . ' (' . ocupantes_texto($ocupantesItem) . ')'
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
            <p class="hint">Selecciona «Jaula grande» si el canario no va a una jaula numerada.</p>
            <?php if ($errorJaula !== ''): ?>
                <span id="error-jaula-id" class="field-error" role="alert"><?= e($errorJaula) ?></span>
            <?php endif; ?>
        </div>

        <div class="field<?= $errorFecha !== '' ? ' has-error' : '' ?>">
            <label class="lbl" for="fecha">Fecha de ingreso</label>
            <input
                id="fecha"
                name="fecha"
                type="date"
                value="<?= e($valorFecha) ?>"
                max="<?= e(hoy()) ?>"
                <?= $errorFecha !== '' ? 'aria-invalid="true" aria-describedby="error-fecha"' : '' ?>
                required
            >
            <?php if ($errorFecha !== ''): ?>
                <span id="error-fecha" class="field-error" role="alert"><?= e($errorFecha) ?></span>
            <?php endif; ?>
        </div>

        <div class="sheet-actions">
            <?= boton('Guardar traslado', 'type="submit"', 'primary') ?>
            <?= enlace('Cancelar', $urlCanario, 'ghost', '', 'data-act="close"') ?>
        </div>
    </form>
</div>
<?php $contenido = (string) ob_get_clean();
require __DIR__ . '/../_partes/marco.php';