<?php
/**
 * Vista para exportar la ficha del canario en PDF (Hoja A4).
 * Issue: #146 (AC-04 [Frontend])
 * Recibe: $canario (CanarioFicha con 'arbol' de 3 generaciones)
 */

$sexos = [
    'macho' => 'Macho',
    'hembra' => 'Hembra',
    'sin_determinar' => 'Sin determinar',
];

$estados = [
    'activo' => 'Activo',
    'reservado' => 'Reservado',
    'baja' => 'Dado de baja',
];

$motivosBaja = [
    'venta' => 'Venta',
    'muerte' => 'Muerte',
    'otra' => 'Otra salida',
];

$variedadTexto = !empty($canario['variedad'])
    ? $canario['variedad']['codigo'] . ' — ' . $canario['variedad']['nombre']
    : 'Sin determinar';

$sexoTexto = $sexos[$canario['sexo'] ?? ''] ?? 'Sin determinar';
$estadoTexto = $estados[$canario['estado'] ?? ''] ?? 'Activo';

// Árbol genealógico de 3 generaciones (AC-01)
$arbol = $canario['arbol'] ?? ['padre' => null, 'madre' => null];

// 1.ª generación: Padres
$padre = $arbol['padre'] ?? null;
$madre = $arbol['madre'] ?? null;

// 2.ª generación: Abuelos
$abueloPat = $padre['padre'] ?? null;
$abuelaPat = $padre['madre'] ?? null;
$abueloMat = $madre['padre'] ?? null;
$abuelaMat = $madre['madre'] ?? null;

// 3.ª generación: Bisabuelos
$bisabueloPP = $abueloPat['padre'] ?? null;
$bisabuelaPP = $abueloPat['madre'] ?? null;
$bisabueloPM = $abuelaPat['padre'] ?? null;
$bisabuelaPM = $abuelaPat['madre'] ?? null;

$bisabueloMP = $abueloMat['padre'] ?? null;
$bisabuelaMP = $abueloMat['madre'] ?? null;
$bisabueloMM = $abuelaMat['padre'] ?? null;
$bisabuelaMM = $abuelaMat['madre'] ?? null;

$textoNodo = static function (?array $nodo): string {
    if ($nodo === null) {
        return 'Sin registro';
    }
    $anillo = e($nodo['anillo_texto'] ?? '');
    $var = !empty($nodo['variedad'])
        ? e($nodo['variedad']['codigo'] . ' ' . $nodo['variedad']['nombre'])
        : 'Sin determinar';
    return '<strong>' . $anillo . '</strong><br><span class="arbol-var">' . $var . '</span>';
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha Canario <?= e($canario['anillo_texto'] ?? '') ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.2cm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .encabezado {
            width: 100%;
            border-bottom: 2px solid #166534;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .encabezado h1 {
            font-size: 16px;
            color: #166534;
            margin: 0 0 2px 0;
        }
        .encabezado p {
            font-size: 10px;
            color: #4b5563;
            margin: 0;
        }
        .seccion-titulo {
            background-color: #166534;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 8px;
            margin: 10px 0 6px 0;
        }
        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.datos th,
        table.datos td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        table.datos th {
            background-color: #f3f4f6;
            font-weight: bold;
            width: 18%;
            color: #111827;
        }
        table.datos td {
            width: 32%;
        }
        table.columnas-dos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.columnas-dos td.col {
            width: 49%;
            vertical-align: top;
            padding: 0;
        }
        table.columnas-dos td.separador {
            width: 2%;
        }
        table.lista {
            width: 100%;
            border-collapse: collapse;
        }
        table.lista th,
        table.lista td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: left;
        }
        table.lista th {
            background-color: #f3f4f6;
        }
        table.arbol {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.arbol th {
            background-color: #f3f4f6;
            border: 1px solid #9ca3af;
            padding: 4px;
            font-size: 9px;
            text-align: center;
        }
        table.arbol td {
            border: 1px solid #9ca3af;
            padding: 4px 5px;
            font-size: 8.5px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .arbol-var {
            color: #4b5563;
            font-size: 8px;
        }
        .sin-registro {
            color: #6b7280;
            font-style: italic;
        }
        .badge {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <table class="encabezado">
        <tr>
            <td>
                <h1>Ficha del Canario — Anillo <?= e($canario['anillo_texto'] ?? '') ?></h1>
                <p>Sistema de Gestión de Criadero de Canarios</p>
            </td>
        </tr>
    </table>

    <div class="seccion-titulo">Datos generales</div>
    <table class="datos">
        <tr>
            <th>Anillo</th>
            <td><strong><?= e($canario['anillo_texto'] ?? '') ?></strong></td>
            <th>Estado</th>
            <td>
                <span class="badge"><?= e($estadoTexto) ?></span>
                <?php if (($canario['estado'] ?? '') === 'baja' && !empty($canario['baja'])): ?>
                    (<?= e($motivosBaja[$canario['baja']['motivo']] ?? $canario['baja']['motivo']) ?>
                    — <?= e(fecha($canario['baja']['fecha'] ?? null)) ?>)
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Sexo</th>
            <td><?= e($sexoTexto) ?></td>
            <th>Ubicación</th>
            <td><?= e($canario['ubicacion_texto'] ?? 'Sin ubicación') ?></td>
        </tr>
        <tr>
            <th>Variedad</th>
            <td colspan="3"><?= e($variedadTexto) ?></td>
        </tr>
        <tr>
            <th>Fecha de anillado</th>
            <td><?= !empty($canario['fecha_anillado']) ? e(fecha($canario['fecha_anillado'])) : 'Sin dato' ?></td>
            <th>Fecha de nacimiento</th>
            <td><?= !empty($canario['fecha_nacimiento']) ? e(fecha($canario['fecha_nacimiento'])) : 'Sin dato' ?></td>
        </tr>
        <tr>
            <th>Procedencia</th>
            <td colspan="3"><?= !empty($canario['procedencia']) ? e($canario['procedencia']) : 'Nacido en el criadero' ?></td>
        </tr>
    </table>

    <table class="columnas-dos">
        <tr>
            <td class="col">
                <div class="seccion-titulo">Factores que manifiesta</div>
                <table class="lista">
                    <thead>
                        <tr>
                            <th>Factor</th>
                            <th>Herencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($canario['factores_manifestados'])): ?>
                            <tr>
                                <td colspan="2" class="sin-registro">Sin factores registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($canario['factores_manifestados'] as $factor): ?>
                                <tr>
                                    <td><?= e($factor['nombre'] ?? '') ?></td>
                                    <td><?= e($factor['tipo_herencia'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </td>
            <td class="separador"></td>
            <td class="col">
                <div class="seccion-titulo">Portadores</div>
                <table class="lista">
                    <thead>
                        <tr>
                            <th>Factor portado</th>
                            <th>Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($canario['portadores'])): ?>
                            <tr>
                                <td colspan="2" class="sin-registro">No registra factores portados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($canario['portadores'] as $portador): ?>
                                <?php
                                $origenTexto = ($portador['origen'] ?? '') === 'deducido'
                                    ? 'Deducido por el sistema'
                                    : 'Indicado por el criador';
                                ?>
                                <tr>
                                    <td><?= e($portador['factor'] ?? '') ?></td>
                                    <td><?= e($origenTexto) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="seccion-titulo">Árbol genealógico (3 generaciones)</div>
    <table class="arbol">
        <thead>
            <tr>
                <th style="width: 30%;">Padres (1.ª gen.)</th>
                <th style="width: 35%;">Abuelos (2.ª gen.)</th>
                <th style="width: 35%;">Bisabuelos (3.ª gen.)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Rama Paterna -->
            <tr>
                <td rowspan="4" class="<?= $padre === null ? 'sin-registro' : '' ?>">
                    <small>Padre:</small><br>
                    <?= $textoNodo($padre) ?>
                </td>
                <td rowspan="2" class="<?= $abueloPat === null ? 'sin-registro' : '' ?>">
                    <small>Abuelo paterno:</small><br>
                    <?= $textoNodo($abueloPat) ?>
                </td>
                <td class="<?= $bisabueloPP === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabueloPP) ?>
                </td>
            </tr>
            <tr>
                <td class="<?= $bisabuelaPP === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabuelaPP) ?>
                </td>
            </tr>
            <tr>
                <td rowspan="2" class="<?= $abuelaPat === null ? 'sin-registro' : '' ?>">
                    <small>Abuela paterna:</small><br>
                    <?= $textoNodo($abuelaPat) ?>
                </td>
                <td class="<?= $bisabueloPM === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabueloPM) ?>
                </td>
            </tr>
            <tr>
                <td class="<?= $bisabuelaPM === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabuelaPM) ?>
                </td>
            </tr>

            <!-- Rama Materna -->
            <tr>
                <td rowspan="4" class="<?= $madre === null ? 'sin-registro' : '' ?>">
                    <small>Madre:</small><br>
                    <?= $textoNodo($madre) ?>
                </td>
                <td rowspan="2" class="<?= $abueloMat === null ? 'sin-registro' : '' ?>">
                    <small>Abuelo materno:</small><br>
                    <?= $textoNodo($abueloMat) ?>
                </td>
                <td class="<?= $bisabueloMP === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabueloMP) ?>
                </td>
            </tr>
            <tr>
                <td class="<?= $bisabuelaMP === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabuelaMP) ?>
                </td>
            </tr>
            <tr>
                <td rowspan="2" class="<?= $abuelaMat === null ? 'sin-registro' : '' ?>">
                    <small>Abuela materna:</small><br>
                    <?= $textoNodo($abuelaMat) ?>
                </td>
                <td class="<?= $bisabueloMM === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabueloMM) ?>
                </td>
            </tr>
            <tr>
                <td class="<?= $bisabuelaMM === null ? 'sin-registro' : '' ?>">
                    <?= $textoNodo($bisabuelaMM) ?>
                </td>
            </tr>
        </tbody>
    </table>

</body>
</html>