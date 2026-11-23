<?php

declare(strict_types=1);

/*
 * Ayudas de presentación del front local (maqueta aprobada por el cliente, pasada a PHP).
 * Solo arman HTML con los datos que ya entrega el backend: no consultan la base ni llaman a servicios
 * (contrato §1). Todo dato se imprime con e().
 */

if (!function_exists('icono')) {
    /** Íconos de la maqueta (mismo trazo de 24 px). */
    function icono(string $nombre, string $clase = ''): string
    {
        static $trazos = [
            'eye' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
            'male' => 'M14 10l6-6M15 4h5v5M15 14a5 5 0 1 1-10 0 5 5 0 0 1 10 0',
            'female' => 'M12 15v7M8 19h8M17 9a5 5 0 1 1-10 0 5 5 0 0 1 10 0',
            'home' => 'M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
            'bird' => 'M15.5 5.5a3.5 3.5 0 0 0-6.6 1.6L9 9c-3 .5-5.5 2.6-6 6 2.5 1.3 6 1.5 8.5.5L13 19h3l-1.6-3.6C17 14 18.5 11.5 18.5 9l2.5-1-2.6-1.5a3.5 3.5 0 0 0-2.9-1zM15.5 7.5h.01',
            'cage' => 'M4 4h16v15H4zM3 4h18M8 4v15M12 4v15M16 4v15M4 11.5h16M6 19v2M18 19v2',
            'book' => 'M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5zM4 20.5V5.5M20 18v3H6.5M8.5 7.5h7',
            'dna' => 'M7 3c0 6 10 6 10 9s-10 3-10 9M17 3c0 6-10 6-10 9s10 3 10 9M8.5 7h7M8.5 17h7',
            'search' => 'M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zM20.5 20.5 16 16',
            'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4.5 21a7.5 7.5 0 0 1 15 0',
            'logout' => 'M14 4h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-5M10 16.5 5.5 12 10 7.5M5.5 12H15',
            'plus' => 'M12 5v14M5 12h14',
            'back' => 'M15 5l-7 7 7 7',
            'edit' => 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
            'move' => 'M5 12h14M13 6l6 6-6 6',
            'bookmark' => 'M6 3h12v18l-6-4-6 4z',
            'file' => 'M14 3H6v18h12V7zM14 3v4h4M9 13h6M9 17h6',
            'exit' => 'M18 6 6 18M6 6l12 12',
            'ring' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z',
            'egg' => 'M12 3c3.5 0 6 5.5 6 10a6 6 0 0 1-12 0c0-4.5 2.5-10 6-10z',
            'check' => 'M5 12.5l4.5 4.5L19 7.5',
            'chev' => 'M9 6l6 6-6 6',
            'menu' => 'M4 7h16M4 12h16M4 17h16',
            'info' => 'M12 8h.01M11 12h1v5h1M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18z',
            'spark' => 'M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6',
            'lock' => 'M6 11h12v10H6zM8 11V7a4 4 0 0 1 8 0v4M12 15v2',
            'clock' => 'M12 7v5l3 2M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18z',
            'tool' => 'M14.5 6.5a4 4 0 0 0 5 5L21 13l-3 3-1.5-1.5a4 4 0 0 1-5-5zM11.5 12.5 4 20a1.4 1.4 0 0 1-2-2l7.5-7.5',
            'alert' => 'M12 3 22 20H2zM12 10v4M12 17h.01',
            'offline' => 'M3 3l18 18M8.5 16.5a5 5 0 0 1 7 0M5 13a10 10 0 0 1 4.5-2.6M19 13a10 10 0 0 0-2.4-1.7M2 9.5a15 15 0 0 1 4.6-2.9M22 9.5a15 15 0 0 0-11.6-3.8M12 20h.01',
        ];

        return '<svg class="ic ' . e($clase) . '" viewBox="0 0 24 24" aria-hidden="true"><path d="' . ($trazos[$nombre] ?? '') . '"/></svg>';
    }

    /** Canario estándar de la maqueta, el mismo para todos (ajuste del cliente); el sexo va como marca. */
    function ave(string $sexo, string $tamano = 'md'): string
    {
        $marca = match ($sexo) {
            'macho' => '<span class="sx sx-m" title="Macho">' . icono('male') . '</span>',
            'hembra' => '<span class="sx sx-f" title="Hembra">' . icono('female') . '</span>',
            default => '',
        };

        return '<span class="bird-thumb" aria-hidden="true"><span class="av av-' . e($tamano) . '"><img src="/css/assets/canario.png" alt="" loading="lazy" decoding="async"></span>' . $marca . '</span>';
    }

    function sexo_texto(string $sexo): string
    {
        return match ($sexo) { 'macho' => 'Macho', 'hembra' => 'Hembra', default => 'Sin determinar' };
    }

    /** «ROJO MARFIL INTENSO» del catálogo se muestra «Rojo marfil intenso», como en la maqueta. */
    function nombre_variedad(string $nombre): string
    {
        $minusculas = mb_strtolower($nombre);
        $texto = mb_strtoupper(mb_substr($minusculas, 0, 1)) . mb_substr($minusculas, 1);

        // Las dosis del jaspe se escriben en mayúsculas, como en el nomenclador: «Negro jaspe DD blanco».
        return (string) preg_replace_callback('/\b(sd|dd)\b/u', static fn (array $m): string => mb_strtoupper($m[1]), $texto);
    }

    /** @param array{codigo:string, nombre:string}|null $variedad */
    function variedad_chip(?array $variedad): string
    {
        return $variedad === null ? '<span class="muted">Variedad sin determinar</span>'
            : '<span class="code">' . e($variedad['codigo']) . '</span> ' . e(nombre_variedad($variedad['nombre']));
    }

    function pastilla(string $estado): string
    {
        return match ($estado) {
            'activo' => '<span class="pill ok">Activo</span>',
            'reservado' => '<span class="pill warn">Reservado</span>',
            'baja' => '<span class="pill off">Dado de baja</span>',
            default => '',
        };
    }

    /** Botón o enlace con las clases de la maqueta (btn-primary, btn-soft, …). */
    function boton(string $texto, string $atributos = '', string $tipo = 'primary', string $icono = ''): string
    {
        return '<button class="btn btn-' . e($tipo) . '" ' . $atributos . '>' . ($icono !== '' ? icono($icono) : '') . '<span>' . e($texto) . '</span></button>';
    }

    function enlace(string $texto, string $url, string $tipo = 'primary', string $icono = '', string $atributos = ''): string
    {
        return '<a class="btn btn-' . e($tipo) . '" href="' . e($url) . '" ' . $atributos . '>' . ($icono !== '' ? icono($icono) : '') . '<span>' . e($texto) . '</span></a>';
    }

    /** Campo con su etiqueta, ayuda y el error que devolvió el backend (contrato §4). */
    function campo(string $etiqueta, string $control, string $ayuda = '', string $nombre = ''): string
    {
        $error = $nombre !== '' ? error($nombre) : '';

        return '<label class="field' . ($error !== '' ? ' has-error' : '') . '"><span class="lbl">' . e($etiqueta) . '</span>' . $control
            . ($ayuda !== '' ? '<span class="hint">' . e($ayuda) . '</span>' : '')
            . ($error !== '' ? '<span class="field-error" role="alert">' . e($error) . '</span>' : '') . '</label>';
    }

    function csrf_campo(string $csrf): string
    {
        return '<input type="hidden" name="_csrf" value="' . e($csrf) . '">';
    }

    /** Error general (`_general`) de un formulario. */
    function error_general(): string
    {
        $mensaje = error('_general');

        return $mensaje === '' ? '' : '<div class="notice warn" role="alert">' . icono('info') . '<p>' . e($mensaje) . '</p></div>';
    }

    /** @param array<string, mixed> $c CanarioResumen */
    function fila_canario(array $c, bool $conJaula = true): string
    {
        $jaula = $conJaula && $c['ubicacion_texto'] !== 'Sin ubicación' ? '<span class="muted sm">' . e($c['ubicacion_texto']) . '</span>' : '';

        return '<a class="row" href="' . e($c['url']) . '">' . ave($c['sexo']) . '<span class="row-main"><strong class="ring">' . e($c['anillo_texto']) . '</strong>'
            . '<span class="row-sub">' . e(sexo_texto($c['sexo'])) . ' · ' . variedad_chip($c['variedad']) . '</span></span>'
            . '<span class="row-end">' . pastilla($c['estado']) . $jaula . '</span>' . icono('chev', 'chev') . '</a>';
    }

    /** Ficha chica de un pariente; null = «Sin registro». @param array<string, mixed>|null $c */
    function mini_canario(?array $c): string
    {
        if ($c === null) {
            return '<span class="mini empty">Sin registro</span>';
        }

        return '<a class="mini" href="' . e($c['url']) . '">' . ave($c['sexo'], 'sm') . '<span><strong class="ring">' . e($c['anillo_texto']) . '</strong>'
            . '<span class="muted sm">' . variedad_chip($c['variedad']) . '</span></span></a>';
    }

    /** Ajuste del cliente: en las parejas, el anillo completo, macho arriba y hembra abajo. */
    function pareja_anillos(array $macho, array $hembra): string
    {
        return '<span class="pair-ring">' . icono('male') . e($macho['anillo_texto']) . '</span><span class="pair-ring">' . icono('female') . e($hembra['anillo_texto']) . '</span>';
    }

    /** Tarjeta del padre o la madre de un cruce. @param array<string, mixed> $c */
    function tarjeta_progenitor(array $c, string $sexo): string
    {
        return '<a class="parent ' . ($sexo === 'macho' ? 'is-m' : 'is-f') . '" href="' . e($c['url']) . '">' . ave($sexo, 'lg')
            . '<span><span class="ring-lbl">' . ($sexo === 'macho' ? 'Macho' : 'Hembra') . '</span><strong class="ring">' . e($c['anillo_texto']) . '</strong>'
            . '<span class="sm">' . variedad_chip($c['variedad']) . '</span></span></a>';
    }

    function encabezado_pagina(string $titulo, string $subtitulo, string $icono, string $accion = ''): string
    {
        return '<header class="page-head"><span class="head-ic">' . icono($icono) . '</span><div class="head-txt"><h1>' . e($titulo) . '</h1>'
            . ($subtitulo !== '' ? '<p>' . e($subtitulo) . '</p>' : '') . '</div>' . $accion . '</header>';
    }

    function jaula_ilustrada(): string
    {
        return '<img class="cage-art" src="/css/assets/jaula.png" alt="" aria-hidden="true">';
    }

    function ocupantes_texto(int $n): string
    {
        return $n === 0 ? 'Vacía' : $n . ' ' . ($n === 1 ? 'ocupante' : 'ocupantes');
    }

    function pichones_texto(int $n): string
    {
        return $n . ' ' . ($n === 1 ? 'pichón' : 'pichones');
    }

    /**
     * El <datalist> de las 880 variedades (contrato §4, RF029): se elige escribiendo parte del código o del nombre.
     *
     * @param list<array{codigo:string, nombre:string, sexo:?string}> $variedades
     */
    function lista_variedades(array $variedades, string $id = 'variedades'): string
    {
        $opciones = '';
        foreach ($variedades as $v) {
            $opciones .= '<option value="' . e($v['codigo'] . ' · ' . nombre_variedad($v['nombre'])) . '"></option>';
        }

        return '<datalist id="' . e($id) . '">' . $opciones . '</datalist>';
    }

    /** Valor visible del campo de variedad: «D.049 · Rojo intenso». @param array{codigo:string, nombre:string}|null $variedad */
    function variedad_valor(?array $variedad): string
    {
        return $variedad === null ? '' : $variedad['codigo'] . ' · ' . nombre_variedad($variedad['nombre']);
    }

    /**
     * Casillas de factores ocultos con la lista que aprobó el cliente (19/11/2026): sin mosaico; «isabel» en lugar de
     * ágata. Isabel es bruno con ágata: la casilla envía los dos factores («id,id»). Perla se suma al final: es un factor
     * recesivo que entró al catálogo después de esa aprobación (20–21/11) y el contrato pide los recesivos (decisión del PM, 22/11/2026).
     *
     * @param list<array{id:int, nombre:string, tipo_herencia:string}> $portables
     * @param list<int> $marcados ids ya marcados
     */
    function casillas_portadores(string $nombre, array $portables, array $marcados, bool $hembra = false): string
    {
        $sinTilde = static fn (string $t): string => strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        $porNombre = [];
        foreach ($portables as $f) {
            $porNombre[$sinTilde($f['nombre'])] = $f;
        }
        $lista = ['marfil', 'pastel', 'ópalo', 'satiné', 'ino', 'topacio', 'ónix', 'mogno', 'cobalto', 'eumo', 'bruno', 'isabel', 'úrucum', 'pico amarillo', 'perla'];
        $html = '';
        foreach ($lista as $etiqueta) {
            $clave = $sinTilde($etiqueta);
            if ($clave === 'isabel') {
                $bruno = $porNombre['bruno'] ?? null;
                $agata = $porNombre['agata'] ?? null;
                if ($bruno === null || $agata === null) {
                    continue;
                }
                $valor = $bruno['id'] . ',' . $agata['id'];
                $marcado = in_array($agata['id'], $marcados, true);
                $ligado = true;
            } else {
                $f = $porNombre[$clave === 'urucum' ? 'urucum' : $clave] ?? null;
                if ($f === null) {
                    continue;
                }
                $valor = (string) $f['id'];
                // Con isabel marcada, bruno ya va incluido.
                $marcado = in_array($f['id'], $marcados, true) && !($clave === 'bruno' && isset($porNombre['agata']) && in_array($porNombre['agata']['id'], $marcados, true));
                $ligado = $f['tipo_herencia'] === 'ligado_sexo';
            }
            $desactivada = $hembra && $ligado;
            $html .= '<label class="chip-cb' . ($desactivada ? ' dis' : '') . '"' . ($desactivada ? ' title="Una hembra no puede ser portadora de un factor ligado al sexo"' : '') . '>'
                . '<input type="checkbox" name="' . e($nombre) . '[]" value="' . e($valor) . '"' . ($marcado ? ' checked' : '') . ($desactivada ? ' disabled' : '') . '>'
                . '<span>' . e($etiqueta) . '</span></label>';
        }

        return '<div class="chips wrap">' . $html . '</div>';
    }

    /** Bloque plegable «Portador de» de la maqueta. $claveError: campo de $errores (por ejemplo, `portadores`). */
    function bloque_portadores(string $nombre, array $portables, array $marcados, bool $hembra = false, string $claveError = ''): string
    {
        $n = count($marcados);
        $error = error($claveError !== '' ? $claveError : $nombre);
        $extra = $hembra ? '<p class="hint">Los factores ligados al sexo aparecen desactivados en hembras.</p>' : '';

        return '<details class="porta"' . ($n > 0 || $error !== '' ? ' open' : '') . '><summary><span>Portador de <small>Opcional</small></span>'
            . ($n > 0 ? '<b class="porta-n">' . $n . ' ' . ($n === 1 ? 'marcado' : 'marcados') . '</b>' : '') . '</summary>'
            . $extra
            . casillas_portadores($nombre, $portables, $marcados, $hembra)
            . ($error !== '' ? '<span class="field-error" role="alert">' . e($error) . '</span>' : '') . '</details>';
    }

    /** Ids marcados en el formulario anterior (viejo), aceptando «id,id» de isabel. @return list<int> */
    function ids_marcados(mixed $valor): array
    {
        $ids = [];
        foreach (is_array($valor) ? $valor : [] as $item) {
            foreach (explode(',', (string) $item) as $id) {
                if (ctype_digit($id)) {
                    $ids[] = (int) $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /** Porcentaje como en la maqueta: «25 %», «12,5 %». */
    function porcentaje(float $valor): string
    {
        $redondo = round($valor, 1);

        return (fmod($redondo, 1.0) === 0.0 ? (string) (int) $redondo : str_replace('.', ',', (string) $redondo)) . ' %';
    }

    /**
     * Posible resultado de una pareja (ResultadoCruce, RF035–RF039): machos y hembras por separado, con su porcentaje.
     * Lo usan la calculadora, la vista previa del cruce y la ficha de cría.
     *
     * @param array{machos: list<array<string, mixed>>, hembras: list<array<string, mixed>>, no_calculables: list<string>, aproximado: bool} $r
     */
    function resultado_cruce(array $r, bool $compacto = false): string
    {
        $columna = static function (string $sexo, array $filas): string {
            $html = '<section class="res-col ' . ($sexo === 'macho' ? 'is-m' : 'is-f') . '"><h3>' . ($sexo === 'macho' ? 'Machos ♂' : 'Hembras ♀') . '</h3>';
            // Orden de la maqueta: más probable primero; a igual porcentaje, por código de variedad y después por portador.
            usort($filas, static fn (array $a, array $b): int => [-(float) $a['porcentaje'], $a['variedad']['codigo'] ?? 'Z', implode(',', $a['portador_de'])]
                <=> [-(float) $b['porcentaje'], $b['variedad']['codigo'] ?? 'Z', implode(',', $b['portador_de'])]);
            foreach ($filas as $f) {
                $nombre = $f['variedad'] !== null ? '<span class="code">' . e($f['variedad']['codigo']) . '</span> ' . e(nombre_variedad($f['variedad']['nombre']))
                    : e($f['factores'] === [] ? 'Sin factores del catálogo' : nombre_variedad(implode(' ', $f['factores'])));
                $etiquetas = ($f['variedad'] === null ? '<span class="tag t-warn">Sin variedad en el catálogo</span>' : '')
                    . implode('', array_map(static fn (string $p): string => '<span class="tag t-hand">portador de ' . e(mb_strtolower($p)) . '</span>', $f['portador_de']));
                $ancho = max(0.0, min(100.0, (float) $f['porcentaje']));
                $html .= '<div class="res-row">' . ave($sexo, 'sm') . '<div class="res-txt"><span class="res-name">' . $nombre . '</span>' . $etiquetas
                    . '<span class="bar"><span style="width:' . number_format($ancho, 1, '.', '') . '%"></span></span></div><strong class="res-pct">' . e(porcentaje((float) $f['porcentaje'])) . '</strong></div>';
            }

            return $html . '</section>';
        };
        // backend.md §5, paso 7: melánico × lipocrómico no se calcula; las crías salen pías.
        if ($r['pio'] ?? false) {
            return '<div class="notice warn">' . icono('info') . '<p>Un padre es melánico (con dibujo) y el otro lipocrómico (sin dibujo): las crías salen <strong>pías</strong>, con manchas. Ese reparto no sigue la tabla de Mendel, así que TuCri no lo calcula.</p></div>';
        }
        $avisos = '';
        if ($r['no_calculables'] !== []) {
            $avisos .= '<div class="notice warn">' . icono('info') . '<p>Sin regla en el catálogo para: <strong>' . e(implode(', ', $r['no_calculables'])) . '</strong>. Ese factor no se calcula; el resto sí.</p></div>';
        }
        if ($r['aproximado']) {
            $avisos .= '<div class="notice">' . icono('info') . '<p>La pareja combina dos o más factores ligados al sexo: el resultado es aproximado.</p></div>';
        }
        // Como la maqueta: un paso por factor, con lo que tiene cada padre y cómo sale en las crías de cada sexo.
        $tipos = ['dominante' => 'Dominante', 'recesivo' => 'Recesivo', 'ligado_sexo' => 'Ligado al sexo'];
        $padre = static fn (string $estado, string $f): string => match ($estado) { 'manifiesta' => $f, 'portador' => "portador de {$f}", default => "sin {$f}" };
        $crias = static function (array $p, string $f): string {
            $partes = [];
            foreach (['manifiesta' => $f, 'portador' => 'portadores', 'no' => "sin {$f}"] as $estado => $texto) {
                if (($p[$estado] ?? 0.0) > 1e-9) {
                    $partes[] = porcentaje($p[$estado] * 100) . ' ' . $texto;
                }
            }

            return (string) preg_replace('/(^|· )100 % /u', '$1todas ', implode(' · ', $partes));
        };
        $pasos = '';
        foreach ($r['explicacion'] ?? [] as $x) {
            // Como la maqueta: el factor con mayúscula inicial en el título y en minúsculas dentro de las frases («portador de marfil»).
            $f = mb_strtolower((string) $x['factor']);
            $pasos .= '<li><div class="hs-head"><strong>' . e(mb_strtoupper(mb_substr($f, 0, 1)) . mb_substr($f, 1)) . '</strong><span class="kind">' . e($tipos[$x['tipo']] ?? $x['tipo']) . '</span></div>'
                . '<p class="hs-par"><span>♂ Padre: <strong>' . e($padre($x['macho'], $f)) . '</strong></span><span>♀ Madre: <strong>' . e($padre($x['hembra'], $f)) . '</strong></span></p>'
                . '<p class="hs-res"><span>→ Machos: ' . e($crias($x['machos'], $f)) . '</span><span>→ Hembras: ' . e($crias($x['hembras'], $f)) . '</span></p></li>';
        }
        // data-cerrar-movil: en el teléfono la maqueta la muestra cerrada (tucri.js); sin JavaScript queda abierta.
        $como = '<details class="how"' . ($compacto ? '' : ' open data-cerrar-movil') . '><summary>' . icono('spark') . 'Desglose por factor</summary>'
            . '<p>Herencia calculada por factor independiente.</p>'
            . ($pasos !== '' ? '<ol class="how-steps">' . $pasos . '</ol>' : '')
            . '<p class="muted sm">Son probabilidades: con pocas crías puede haber diferencias. Se supone que cada factor se hereda por separado.</p></details>';

        return $avisos . '<div class="results">' . $columna('macho', $r['machos']) . $columna('hembra', $r['hembras']) . '</div>' . $como;
    }

    /** Pantalla de error de la maqueta (404, 403, sesión, mantenimiento, 500, sin conexión). $acciones es HTML ya armado. */
    function bloque_error(string $codigo, string $icono, string $tono, string $titulo, string $texto, string $acciones): string
    {
        return '<section class="err-state tone-' . e($tono) . '"><span class="err-badge">' . icono($icono) . '</span>'
            . ($codigo !== '' ? '<p class="err-code">' . e($codigo) . '</p>' : '') . '<h1>' . e($titulo) . '</h1><p class="lead">' . e($texto) . '</p>'
            . '<div class="err-actions">' . $acciones . '</div></section>';
    }

    /**
     * Pasos del registro de pareja, con el marcado de la maqueta: cada paso es un botón; los anteriores vuelven a ese paso
     * (un formulario GET con los campos ya elegidos, sin JavaScript) y los siguientes están desactivados.
     *
     * @param array<int, string> $urls dirección de cada paso anterior
     */
    function pasos_pareja(int $actual, array $urls = []): string
    {
        $html = '<ol class="stepper wiz">';
        foreach ([1 => 'Jaula', 2 => 'Pareja', 3 => 'Resultado'] as $n => $nombre) {
            $boton = '<span>' . ($n < $actual ? icono('check') : $n) . '</span>' . $nombre;
            if ($n < $actual && isset($urls[$n])) {
                $partes = parse_url($urls[$n]);
                parse_str($partes['query'] ?? '', $campos);
                $ocultos = '';
                foreach ($campos as $campo => $v) {
                    foreach (is_array($v) ? $v : [$v] as $item) {
                        $ocultos .= '<input type="hidden" name="' . e($campo) . (is_array($v) ? '[]' : '') . '" value="' . e((string) $item) . '">';
                    }
                }
                $html .= '<li class="done"><form method="get" action="' . e($partes['path'] ?? '') . '">' . $ocultos . '<button type="submit">' . $boton . '</button></form></li>';
            } else {
                $html .= '<li class="' . ($n === $actual ? 'cur' : ($n < $actual ? 'done' : '')) . '"><button type="button"' . ($n === $actual ? ' aria-current="step"' : ' disabled') . '>' . $boton . '</button></li>';
            }
        }

        return $html . '</ol>';
    }

    /** Fecha AAAA-MM-DD de hoy, para los campos de fecha. */
    function hoy(): string
    {
        return date('Y-m-d');
    }
}
