/* TuCriadero · propuesta navegable (12/11/2026). Escrita a mano, no derivada del Figma.
   Mismas funciones del SRS v1.1 y de los ajustes del cliente; datos de ejemplo en memoria (se reinician al recargar). */
(() => {
    'use strict';
    const app = document.querySelector('#app');
    const sheetRoot = document.querySelector('#sheet-root');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const norm = s => String(s).toUpperCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    // ================= Catálogo (nomenclador de color 2026) =================
    const CATALOGO = window.CATALOGO ?? [];
    const BY_CODE = new Map(CATALOGO.map(([c, n]) => [c, n]));
    const tokensOf = name => norm(name).split(/\s+/).filter(Boolean);
    const keyOf = toks => [...toks].sort().join(' ');
    const BY_KEY = new Map(CATALOGO.map(([c, n]) => [keyOf(tokensOf(n)), c]));
    const varName = code => BY_CODE.get(code) ?? 'Sin variedad';

    // ================= Datos de ejemplo =================
    const perfil = { nombre: 'Juan Carlos', correo: 'juancarlos@ejemplo.cl', socio: '18', club: 'CC' };
    const C = (o) => ({ porta: [], estado: 'activo', origen: 'criadero', padre: null, madre: null, nac: '', anillado: '', ...o });
    const state = {
        canarios: [
            C({ id: 'c150', socio: '18', anio: '2025', corr: '150', club: 'CC', sexo: 'M', codigo: 'D.049', jaula: '2', padre: 'c016', madre: 'c021', nac: '02/04/2025', anillado: '08/04/2025' }),
            C({ id: 'c151', socio: '18', anio: '2025', corr: '151', club: 'CC', sexo: 'H', codigo: 'D.051', jaula: '2', padre: 'c016', madre: 'c021', nac: '02/04/2025', anillado: '08/04/2025' }),
            C({ id: 'c162', socio: '18', anio: '2025', corr: '162', club: 'CC', sexo: 'M', codigo: 'D.052', jaula: '10', padre: 'c070', madre: 'c071', nac: '20/05/2025', anillado: '26/05/2025' }),
            C({ id: 'c165', socio: '18', anio: '2025', corr: '165', club: 'CC', sexo: 'H', codigo: 'D.050', jaula: '10', padre: 'c070', madre: 'c071', nac: '20/05/2025', anillado: '26/05/2025' }),
            C({ id: 'c164', socio: '18', anio: '2025', corr: '164', club: 'CC', sexo: 'H', codigo: 'D.050', jaula: '1', padre: 'c070', madre: 'c071', nac: '20/05/2025', anillado: '26/05/2025' }),
            C({ id: 'c083', socio: '18', anio: '2025', corr: '083', club: 'CC', sexo: 'H', codigo: 'D.104', jaula: '7', estado: 'reservado', nac: '15/03/2025', anillado: '21/03/2025' }),
            C({ id: 'c016', socio: '18', anio: '2023', corr: '016', club: 'CC', sexo: 'M', codigo: 'D.049', jaula: 'grande', padre: 'c004', madre: 'c009', nac: '10/04/2023', anillado: '16/04/2023' }),
            C({ id: 'c021', socio: '18', anio: '2023', corr: '021', club: 'CC', sexo: 'H', codigo: 'D.052', jaula: 'grande', madre: 'c011', nac: '12/04/2023', anillado: '18/04/2023', porta: [] }),
            C({ id: 'c070', socio: '18', anio: '2024', corr: '070', club: 'CC', sexo: 'M', codigo: 'D.051', jaula: '8', nac: '05/04/2024', anillado: '11/04/2024' }),
            C({ id: 'c071', socio: '18', anio: '2024', corr: '071', club: 'CC', sexo: 'H', codigo: 'D.050', jaula: '8', nac: '06/04/2024', anillado: '12/04/2024' }),
            C({ id: 'c004', socio: '18', anio: '2021', corr: '004', club: 'CC', sexo: 'M', codigo: 'D.049', jaula: null, estado: 'baja', baja: '03/02/2025' }),
            C({ id: 'c009', socio: '18', anio: '2021', corr: '009', club: 'CC', sexo: 'H', codigo: 'D.050', jaula: null, estado: 'baja', baja: '20/09/2024' }),
            C({ id: 'c011', socio: '7', anio: '2021', corr: '011', club: 'SOCH', sexo: 'H', codigo: 'D.051', jaula: null, estado: 'baja', origen: 'adquirido', procedencia: 'Criadero amigo, Santiago', baja: '15/01/2025' }),
            C({ id: 'n011', socio: '18', anio: '2026', corr: '011', club: 'CC', sexo: '?', codigo: null, jaula: null, padre: 'c150', madre: 'c151', cruce: 'x01', nac: '10/04/2026', anillado: '16/04/2026' }),
            C({ id: 'n012', socio: '18', anio: '2026', corr: '012', club: 'CC', sexo: 'M', codigo: 'D.049', jaula: '7', estado: 'reservado', padre: 'c150', madre: 'c151', cruce: 'x01', nac: '10/04/2026', anillado: '16/04/2026' }),
            C({ id: 'n013', socio: '18', anio: '2026', corr: '013', club: 'CC', sexo: 'H', codigo: 'D.051', jaula: null, estado: 'baja', padre: 'c150', madre: 'c151', cruce: 'x01', nac: '11/04/2026', anillado: '16/04/2026', baja: '30/09/2026' }),
            C({ id: 'b150', socio: '41', anio: '2024', corr: '150', club: 'CR', sexo: 'H', codigo: 'D.104', jaula: null, estado: 'baja', origen: 'adquirido', procedencia: 'Exposición regional', baja: '12/08/2026' }),
        ],
        jaulas: ['1', '2', '5', '7', '8', '10', 'grande'],
        cruces: [
            { id: 'x01', num: '01', jaula: '2', macho: 'c150', hembra: 'c151', temporada: '2026', inicio: '02/04/2026', fin: null },
            { id: 'x02', num: '02', jaula: '10', macho: 'c162', hembra: 'c165', temporada: '2026', inicio: '18/05/2026', fin: null },
            { id: 'x03', num: '03', jaula: '1', macho: 'c150', hembra: 'c164', temporada: '2026', inicio: '10/06/2026', fin: null },
            { id: 'x00', num: '01', jaula: '8', macho: 'c070', hembra: 'c071', temporada: '2025', inicio: '15/04/2025', fin: '30/06/2025' },
        ],
        historial: {
            '2': ['02/04/2026 · Entran 150 y 151 · inicio del cruce 01', '16/04/2026 · Se anillan 3 pichones', '30/09/2026 · Baja de 013', '01/10/2026 · Sale 012 hacia Jaula 7; 011 queda sin jaula asignada'],
            '10': ['18/05/2026 · Entran 162 y 165 · inicio del cruce 02'],
            'grande': ['01/06/2024 · Entran 016 y 021'],
            '5': ['21/03/2025 · Entra 083', '01/10/2026 · Sale 083 hacia Jaula 7'],
            '7': ['01/10/2026 · Entran 083 y 012'],
            '8': ['05/04/2024 · Entran 070 y 071', '30/06/2025 · Termina el cruce 01 de 2025 (sin traslado)'],
            '1': ['10/06/2026 · Entra 164 · inicio del cruce 03 con el macho 150'],
        },
    };
    const byId = id => state.canarios.find(c => c.id === id);
    const ring = c => `${c.socio} · ${c.anio} · ${c.corr} · ${c.club}`;
    // Ajuste del cliente (19/11/2026): en parejas se muestra el anillo completo, macho arriba y hembra abajo.
    const pairRings = (m, f) => `<span class="pair-ring">${ic('male')}${esc(ring(m))}</span><span class="pair-ring">${ic('female')}${esc(ring(f))}</span>`;
    const cageName = j => (!j ? 'Sin jaula' : j === 'grande' ? 'Jaula grande' : `Jaula ${j}`);
    const sexoTxt = s => (s === 'M' ? 'Macho' : s === 'H' ? 'Hembra' : 'Sin determinar');
    const vivos = () => state.canarios.filter(c => c.estado !== 'baja');
    const enJaula = j => vivos().filter(c => c.jaula === j);
    // Regla de la maqueta acordada por el PM: un macho y una hembra por jaula.
    function conflictoJaula(j, nuevos) {
        if (!j) return '';
        if (!state.jaulas.includes(j)) return 'Selecciona una jaula existente.';
        const aves = [...enJaula(j).filter(c => !nuevos.some(n => n.id && n.id === c.id)), ...nuevos];
        if (aves.length > 2) return `La ${cageName(j)} admite como máximo un macho y una hembra.`;
        if (aves.some(c => c.sexo !== 'M' && c.sexo !== 'H')) return 'Indica el sexo del canario antes de asignarle una jaula.';
        if (aves.filter(c => c.sexo === 'M').length > 1) return `La ${cageName(j)} ya tiene un macho. Solo puedes agregar una hembra.`;
        if (aves.filter(c => c.sexo === 'H').length > 1) return `La ${cageName(j)} ya tiene una hembra. Solo puedes agregar un macho.`;
        return '';
    }
    function ubicarCanario(c, destino, fecha = hoy) {
        if (c.jaula === destino) return;
        if (c.jaula) (state.historial[c.jaula] ??= []).unshift(`${fecha} · Sale ${c.corr} hacia ${destino ? cageName(destino) : 'sin jaula asignada'}`);
        c.jaula = destino || null;
        if (destino) (state.historial[destino] ??= []).unshift(`${fecha} · Entra ${c.corr}`);
    }
    // Jaulas en orden numérico (2 antes que 10) y la jaula grande al final.
    const porJaula = (a, b) => (a === 'grande') - (b === 'grande') || String(a).localeCompare(String(b), 'es', { numeric: true });
    const cruceActivo = j => state.cruces.find(x => x.jaula === j && !x.fin);
    const pichonesDe = x => state.canarios.filter(c => c.cruce === x.id);
    const crucesActivosDe = c => state.cruces.filter(x => !x.fin && (x.macho === c.id || x.hembra === c.id));
    const hoy = '12/11/2026';

    // ================= Avatar de canario (pintado según la variedad) =================
    function palette(code) {
        const t = norm(code ? varName(code) : '');
        let body = '#D3D8D4', wing = '#A9B2AC', bg = '#EEF1EE', mask = null;
        const red = /ROJO|RUBINO/.test(t), yellow = /AMARILLO|LUTINO/.test(t), white = /BLANCO|ALBINO/.test(t) && !/ALA BLANCA/.test(t);
        if (white) { body = '#F7F5EE'; wing = '#DCD6C8'; bg = '#EFEDE6'; }
        else if (red) { body = /MARFIL/.test(t) ? '#F39B76' : '#EB6A3D'; wing = '#C24A2B'; bg = '#FDE7DD'; }
        else if (yellow) { body = /MARFIL/.test(t) ? '#F6DC86' : '#F5C325'; wing = '#D29E0F'; bg = '#FFF3CC'; }
        if (/MOSAICO/.test(t) && (red || yellow)) { mask = body; body = red ? '#F6DED3' : '#FBF0CE'; }
        if (/NEGRO/.test(t)) wing = '#2F2A26';
        else if (/BRUNO/.test(t)) wing = '#7A4E2D';
        else if (/AGATA/.test(t)) wing = '#6E7378';
        else if (/ISABEL/.test(t)) wing = '#B49A7C';
        return { body, wing, bg, mask };
    }
    // Ajuste del cliente (19/11/2026): un mismo canario ilustrado, de color fijo, para todos; la variedad va en el texto.
    function avatar(code, sexo, size = 'md') {
        const s = sexo === 'M' ? `<span class="sx sx-m" title="Macho">${ic('male')}</span>` : sexo === 'H' ? `<span class="sx sx-f" title="Hembra">${ic('female')}</span>` : '';
        return `<span class="bird-thumb" aria-hidden="true"><span class="av av-${size}"><img src="assets/canario.png" alt="" loading="lazy" decoding="async"></span>${s}</span>`;
    }
    const varChip = code => code ? `<span class="code">${esc(code)}</span> ${esc(varName(code))}` : '<span class="muted">Variedad sin determinar</span>';
    const pill = estado => ({ activo: '<span class="pill ok">Activo</span>', reservado: '<span class="pill warn">Reservado</span>', baja: '<span class="pill off">Dado de baja</span>' }[estado] ?? '');

    // ================= Iconos =================
    const ICON = {
        eye: 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
        male: 'M14 10l6-6M15 4h5v5M15 14a5 5 0 1 1-10 0 5 5 0 0 1 10 0',
        female: 'M12 15v7M8 19h8M17 9a5 5 0 1 1-10 0 5 5 0 0 1 10 0',
        home: 'M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
        bird: 'M15.5 5.5a3.5 3.5 0 0 0-6.6 1.6L9 9c-3 .5-5.5 2.6-6 6 2.5 1.3 6 1.5 8.5.5L13 19h3l-1.6-3.6C17 14 18.5 11.5 18.5 9l2.5-1-2.6-1.5a3.5 3.5 0 0 0-2.9-1zM15.5 7.5h.01',
        cage: 'M4 4h16v15H4zM3 4h18M8 4v15M12 4v15M16 4v15M4 11.5h16M6 19v2M18 19v2',
        book: 'M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5zM4 20.5V5.5M20 18v3H6.5M8.5 7.5h7',
        dna: 'M7 3c0 6 10 6 10 9s-10 3-10 9M17 3c0 6-10 6-10 9s10 3 10 9M8.5 7h7M8.5 17h7',
        search: 'M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zM20.5 20.5 16 16',
        user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4.5 21a7.5 7.5 0 0 1 15 0',
        logout: 'M14 4h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-5M10 16.5 5.5 12 10 7.5M5.5 12H15',
        plus: 'M12 5v14M5 12h14',
        back: 'M15 5l-7 7 7 7',
        edit: 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
        move: 'M5 12h14M13 6l6 6-6 6',
        bookmark: 'M6 3h12v18l-6-4-6 4z',
        file: 'M14 3H6v18h12V7zM14 3v4h4M9 13h6M9 17h6',
        exit: 'M18 6 6 18M6 6l12 12',
        ring: 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z',
        egg: 'M12 3c3.5 0 6 5.5 6 10a6 6 0 0 1-12 0c0-4.5 2.5-10 6-10z',
        check: 'M5 12.5l4.5 4.5L19 7.5',
        chev: 'M9 6l6 6-6 6',
        menu: 'M4 7h16M4 12h16M4 17h16',
        info: 'M12 8h.01M11 12h1v5h1M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18z',
        spark: 'M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6',
        lock: 'M6 11h12v10H6zM8 11V7a4 4 0 0 1 8 0v4M12 15v2',
        clock: 'M12 7v5l3 2M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18z',
        tool: 'M14.5 6.5a4 4 0 0 0 5 5L21 13l-3 3-1.5-1.5a4 4 0 0 1-5-5zM11.5 12.5 4 20a1.4 1.4 0 0 1-2-2l7.5-7.5',
        alert: 'M12 3 22 20H2zM12 10v4M12 17h.01',
        offline: 'M3 3l18 18M8.5 16.5a5 5 0 0 1 7 0M5 13a10 10 0 0 1 4.5-2.6M19 13a10 10 0 0 0-2.4-1.7M2 9.5a15 15 0 0 1 4.6-2.9M22 9.5a15 15 0 0 0-11.6-3.8M12 20h.01',
    };
    const ic = (n, cls = '') => `<svg class="ic ${cls}" viewBox="0 0 24 24" aria-hidden="true"><path d="${ICON[n]}"/></svg>`;

    // ================= Motor genético (RF035–RF039) =================
    // Cálculo exacto por factor (tabla de Mendel), con loci independientes (SUP-004).
    const LOCI = {
        bruno: { name: 'Bruno (oxidación)', type: 'Ligado al sexo', z: true, rec: true, carrierLabel: 'bruno' },
        dil: { name: 'Ágata / dilución', type: 'Ligado al sexo', z: true, rec: true, carrierLabel: 'ágata' },
        pastel: { name: 'Pastel', type: 'Ligado al sexo', z: true, rec: true, carrierLabel: 'pastel', tok: 'PASTEL' },
        satine: { name: 'Satiné', type: 'Ligado al sexo', z: true, rec: true, carrierLabel: 'satiné', tok: 'SATINE' },
        marfil: { name: 'Marfil', type: 'Ligado al sexo', z: true, rec: true, carrierLabel: 'marfil', tok: 'MARFIL' },
        opalo: { name: 'Ópalo', type: 'Recesivo', rec: true, carrierLabel: 'ópalo', tok: 'OPALO' },
        topacio: { name: 'Topacio', type: 'Recesivo', rec: true, carrierLabel: 'topacio', tok: 'TOPACIO' },
        onix: { name: 'Ónix', type: 'Recesivo', rec: true, carrierLabel: 'ónix', tok: 'ONIX' },
        mogno: { name: 'Mogno', type: 'Recesivo', rec: true, carrierLabel: 'mogno', tok: 'MOGNO' },
        cobalto: { name: 'Cobalto', type: 'Recesivo', rec: true, carrierLabel: 'cobalto', tok: 'COBALTO' },
        eumo: { name: 'Eumo', type: 'Recesivo', rec: true, carrierLabel: 'eumo', tok: 'EUMO' },
        ino: { name: 'Ino', type: 'Recesivo', rec: true, carrierLabel: 'ino' },
        wrec: { name: 'Blanco recesivo', type: 'Recesivo', rec: true, carrierLabel: 'blanco recesivo' },
        mo: { name: 'Mosaico', type: 'Recesivo', rec: true, carrierLabel: 'mosaico' },
        // AC-06 (19/11/2026): úrucum y pico amarillo, recesivos independientes, como en el catálogo de factores.
        urucum: { name: 'Úrucum', type: 'Recesivo', rec: true, carrierLabel: 'úrucum', tok: 'URUCUM' },
        pico: { name: 'Pico amarillo', type: 'Recesivo', rec: true, carrierLabel: 'pico amarillo', tok: 'PICO' },
        I: { name: 'Intenso / nevado', type: 'Dominante' },
        W: { name: 'Blanco dominante', type: 'Dominante' },
        jaspe: { name: 'Jaspe', type: 'Semidominante' },
        R: { name: 'Color de fondo rojo', type: 'Semidominante' },
    };
    const MELANIN = ['NEGRO', 'BRUNO', 'AGATA', 'ISABEL'];
    const KNOWN = new Set([...MELANIN, 'AMARILLO', 'ROJO', 'BLANCO', 'DOMINANTE', 'LUTINO', 'RUBINO', 'ALBINO', 'MARFIL', 'PASTEL', 'SATINE', 'OPALO', 'TOPACIO', 'ONIX', 'MOGNO', 'COBALTO', 'EUMO', 'JASPE', 'SD', 'DD', 'INTENSO', 'NEVADO', 'MOSAICO', 'MACHO', 'HEMBRA', 'URUCUM', 'PICO']);
    const UNKNOWN_LABEL = { ALA: 'ala blanca', BLANCA: 'ala blanca', PERLA: 'perla', ALAS: 'alas grises', GRISES: 'alas grises', FEO: 'feo' };
    function parse(code) {
        const t = tokensOf(varName(code));
        const has = x => t.includes(x);
        const melanin = MELANIN.find(has) ?? null;
        const p = {
            melanic: !!melanin, melanin,
            bruno: has('BRUNO') || has('ISABEL'), dil: has('AGATA') || has('ISABEL'),
            red: has('ROJO') || has('RUBINO'), ino: has('LUTINO') || has('RUBINO') || has('ALBINO'),
            W: has('BLANCO') && has('DOMINANTE') || has('ALBINO') && has('DOMINANTE'),
            wrec: (has('BLANCO') || has('ALBINO')) && !has('DOMINANTE') && !has('BLANCA'),
            I: has('INTENSO'), mo: has('MOSAICO'),
            jaspe: has('DD') ? 2 : has('SD') || has('JASPE') ? 1 : 0,
            unknown: [...new Set(t.filter(x => !KNOWN.has(x)).map(x => UNKNOWN_LABEL[x] ?? x.toLowerCase()))],
        };
        for (const k of ['pastel', 'satine', 'marfil', 'opalo', 'topacio', 'onix', 'mogno', 'cobalto', 'eumo', 'urucum', 'pico']) p[k] = has(LOCI[k].tok);
        return p;
    }
    // Genotipo: cantidad de alelos mutantes por locus (machos 0–2 en Z; hembras 0–1 en Z).
    function genotype(code, sexo, porta = []) {
        const p = parse(code), male = sexo === 'M', g = {};
        for (const [k, L] of Object.entries(LOCI)) {
            if (!L.rec) continue;
            const expressed = k === 'mo' ? p.mo : p[k];
            const carrier = porta.includes(k);
            g[k] = L.z ? (expressed ? (male ? 2 : 1) : carrier && male ? 1 : 0) : expressed ? 2 : carrier ? 1 : 0;
        }
        g.I = p.I ? 1 : 0; g.W = p.W ? 1 : 0; g.jaspe = p.jaspe; g.R = p.red ? 2 : 0;
        // Bruno y ágata van en el mismo cromosoma Z: se siguen por cromosoma. Portador de isabel = los dos juntos
        // en un mismo cromosoma (supuesto AC-06: ligados, sin recombinación).
        const h = (male ? [0, 1] : [0]).map(() => ({ b: p.bruno ? 1 : 0, d: p.dil ? 1 : 0 }));
        if (male && porta.includes('isabel')) { h[0].b = 1; h[0].d = 1; }
        else if (male) { if (porta.includes('bruno')) h[0].b = 1; if (porta.includes('dil')) h[1].d = 1; }
        g.bruno = h.reduce((s, x) => s + x.b, 0); g.dil = h.reduce((s, x) => s + x.d, 0);
        return { g, p, h };
    }
    function locusDist(k, gm, gf, sex) {
        const L = LOCI[k];
        if (L.z) {
            const pf = gm / 2;
            return sex === 'M' ? { [gf]: 1 - pf, [gf + 1]: pf } : { 0: 1 - pf, 1: pf };
        }
        const a = gm / 2, b = gf / 2;
        return { 0: (1 - a) * (1 - b), 1: a * (1 - b) + b * (1 - a), 2: a * b };
    }
    function phenotype(g, sex, melanic) {
        const zx = k => (sex === 'M' ? g[k] === 2 : g[k] === 1);
        const toks = [], carriers = [], notes = [];
        if (melanic) toks.push(zx('bruno') && zx('dil') ? 'ISABEL' : zx('bruno') ? 'BRUNO' : zx('dil') ? 'AGATA' : 'NEGRO');
        for (const k of ['pastel', 'opalo', 'topacio', 'onix', 'mogno', 'cobalto', 'eumo', 'satine']) {
            const on = LOCI[k].z ? zx(k) : g[k] === 2;
            if (on) toks.push(LOCI[k].tok);
        }
        if (g.jaspe) toks.push('JASPE', g.jaspe === 2 ? 'DD' : 'SD');
        const white = g.W >= 1 || g.wrec === 2;
        const ino = g.ino === 2;
        if (g.W >= 1) toks.push(melanic ? 'BLANCO' : ino ? 'ALBINO' : 'BLANCO', 'DOMINANTE');
        else if (g.wrec === 2) toks.push(!melanic && ino ? 'ALBINO' : 'BLANCO');
        else {
            const red = g.R >= 1;
            toks.push(!melanic && ino ? (red ? 'RUBINO' : 'LUTINO') : red ? 'ROJO' : 'AMARILLO');
            if (g.R === 1) notes.push('fondo intermedio (naranja)');
            if (zx('marfil')) toks.push('MARFIL');
            if (g.urucum === 2) toks.push('URUCUM');
            if (g.pico === 2) toks.push('PICO', 'AMARILLO');
            if (g.I >= 1) toks.push('INTENSO');
            else if (g.mo === 2) toks.push('MOSAICO', sex === 'M' ? 'MACHO' : 'HEMBRA');
            else toks.push('NEVADO');
            if (g.I === 2) notes.push('doble intenso');
        }
        if (melanic && ino) notes.push('ino sobre melánico');
        for (const [k, L] of Object.entries(LOCI)) {
            if (!L.rec) continue;
            if (white && (k === 'marfil' || k === 'mo' || k === 'urucum' || k === 'pico')) continue;
            if (g.isa && (k === 'bruno' || k === 'dil')) { if (k === 'bruno') carriers.push('isabel'); continue; }
            const carrier = L.z ? sex === 'M' && g[k] === 1 : g[k] === 1;
            if (carrier) carriers.push(L.carrierLabel);
        }
        return { toks, carriers, notes };
    }
    const pct = p => { const v = Math.round(p * 1000) / 10; return `${Number.isInteger(v) ? v : v.toFixed(1).replace('.', ',')} %`; };
    function calcular(m, f) {
        // m, f: { codigo, porta } — el macho y la hembra.
        if (!m?.codigo || !f?.codigo) return { error: 'Elige la variedad del macho y de la hembra para ver el resultado.' };
        const A = genotype(m.codigo, 'M', m.porta), B = genotype(f.codigo, 'H', f.porta);
        if (A.p.melanic !== B.p.melanic) return { error: 'Un padre es melánico y el otro lipocromo: el catálogo no define cómo se combinan, así que no se calcula (factor sin regla).' };
        const sinRegla = [...new Set([...A.p.unknown, ...B.p.unknown])];
        const out = { M: [], H: [], explain: [], sinRegla, lethal: 0 };
        for (const sex of ['M', 'H']) {
            // Bruno y ágata juntos: el hijo recibe un Z del padre y otro de la madre; la hija, solo el del padre.
            let states = A.h.flatMap(fh => (sex === 'M' ? B.h : [null]).map(mh => {
                const b = fh.b + (mh?.b ?? 0), d = fh.d + (mh?.d ?? 0);
                const isa = !!mh && b === 1 && d === 1 && (fh.b === fh.d);
                return { p: 1 / A.h.length, g: { bruno: b, dil: d, isa } };
            }));
            for (const k of Object.keys(LOCI)) {
                if (k === 'bruno' || k === 'dil') continue;
                const d = locusDist(k, A.g[k], B.g[k], sex), next = [];
                for (const s of states) for (const [v, q] of Object.entries(d)) if (q > 0) next.push({ p: s.p * q, g: { ...s.g, [k]: +v } });
                states = next;
            }
            const agg = new Map();
            let lethal = 0;
            for (const s of states) {
                if (s.g.W === 2) { lethal += s.p; continue; }
                const ph = phenotype(s.g, sex, A.p.melanic);
                const code = BY_KEY.get(keyOf(ph.toks)) ?? null;
                const key = (code ?? ph.toks.join(' ')) + '|' + ph.carriers.sort().join(',') + '|' + ph.notes.join(',');
                const e = agg.get(key) ?? { p: 0, code, toks: ph.toks, carriers: ph.carriers, notes: ph.notes };
                e.p += s.p; agg.set(key, e);
            }
            const rest = 1 - lethal;
            out[sex] = [...agg.values()].map(e => ({ ...e, p: e.p / rest })).sort((a, b) => b.p - a.p || String(a.code ?? 'Z').localeCompare(String(b.code ?? 'Z')) || a.carriers.join().localeCompare(b.carriers.join()));
            out.lethal = Math.max(out.lethal, lethal);
        }
        // Explicación por factor, en lenguaje simple.
        const desc = (g, sex, k) => {
            const L = LOCI[k];
            if (k === 'I') return g ? 'intenso' : 'nevado';
            if (k === 'R') return g === 2 ? 'fondo rojo' : g === 1 ? 'fondo intermedio' : 'fondo amarillo';
            if (k === 'jaspe') return ['sin jaspe', 'jaspe simple (SD)', 'jaspe doble (DD)'][g];
            if (k === 'W') return g ? 'blanco dominante' : 'sin blanco dominante';
            const full = L.z ? (sex === 'M' ? 2 : 1) : 2;
            return g === full ? `${L.name.toLowerCase()}` : g ? `portador de ${L.carrierLabel}` : `sin ${L.carrierLabel}`;
        };
        const share = (k, sex) => {
            const d = locusDist(k, A.g[k], B.g[k], sex), L = LOCI[k];
            const parts = [];
            if (L.rec) {
                const full = L.z ? (sex === 'M' ? 2 : 1) : 2;
                const exp = d[full] ?? 0, car = L.z && sex === 'H' ? 0 : d[1] ?? 0;
                if (exp) parts.push(`${pct(exp)} ${L.carrierLabel}`);
                if (car && full === 2) parts.push(`${pct(car)} portadores`);
                const none = 1 - exp - (full === 2 ? car : 0);
                if (none > 1e-9) parts.push(`${pct(none)} sin ${L.carrierLabel}`);
            } else if (k === 'I') { const on = (d[1] ?? 0) + (d[2] ?? 0); parts.push(`${pct(on)} intensos`, `${pct(1 - on)} nevados`); }
            else if (k === 'R') { if (d[2]) parts.push(`${pct(d[2])} fondo rojo`); if (d[1]) parts.push(`${pct(d[1])} fondo intermedio`); if (d[0]) parts.push(`${pct(d[0])} fondo amarillo`); }
            else if (k === 'jaspe') { if (d[2]) parts.push(`${pct(d[2])} jaspe doble`); if (d[1]) parts.push(`${pct(d[1])} jaspe simple`); if (d[0]) parts.push(`${pct(d[0])} sin jaspe`); }
            else if (k === 'W') { if (d[1]) parts.push(`${pct(d[1] / (1 - (d[2] ?? 0)))} blancos dominantes`); if (d[2]) parts.push('las crías con doble blanco dominante no son viables'); }
            return parts.filter(Boolean).join(' · ').replace(/(^|· )100 % /g, '$1todas ');
        };
        if (A.p.melanic) out.explain.push({ name: 'Melanina', type: 'Serie de color', m: A.p.melanin.toLowerCase().replace('agata', 'ágata'), f: B.p.melanin.toLowerCase().replace('agata', 'ágata'), M: 'melánicos', H: 'melánicas', simple: 'Los dos tienen melanina (dibujo), así que todas las crías son melánicas.' });
        for (const k of Object.keys(LOCI)) {
            if (!A.g[k] && !B.g[k] && !(k === 'I' || k === 'R')) continue;
            if (k === 'mo' && (A.g.I || B.g.I) && !A.g.mo && !B.g.mo) continue;
            out.explain.push({ name: LOCI[k].name, type: LOCI[k].type, m: desc(A.g[k], 'M', k), f: desc(B.g[k], 'H', k), M: share(k, 'M'), H: share(k, 'H') });
        }
        return out;
    }
    const resultName = e => e.code ? varName(e.code) : e.toks.map(t => t.toLowerCase().replace('agata', 'ágata').replace('onix', 'ónix').replace('satine', 'satiné').replace('opalo', 'ópalo')).join(' ').replace(/^./, s => s.toUpperCase());

    // ================= Navegación =================
    const route = () => { const [name, arg] = location.hash.slice(1).split('/'); return { name: name || 'login', arg }; };
    const go = h => { if (location.hash === h) render(); else location.hash = h; };
    let toastTimer;
    function toast(text) {
        const el = document.querySelector('#toast');
        el.textContent = text; el.classList.add('show');
        clearTimeout(toastTimer); toastTimer = setTimeout(() => el.classList.remove('show'), 3800);
    }
    const NAV = [['inicio', 'Inicio', 'home'], ['canarios', 'Canarios', 'bird'], ['jaulas', 'Jaulas', 'cage'], ['crias', 'Libro de crías', 'book']];
    const CRIAS_MENU = [['crias', 'Libro de crías', 'book'], ['canarios', 'Canarios', 'bird'], ['jaulas', 'Jaulas', 'cage']];
    const SECTION = { 404: 'buscar', 403: 'buscar', inicio: 'inicio', canarios: 'canarios', canario: 'canarios', 'canario-nuevo': 'canarios', jaulas: 'jaulas', crias: 'crias', cruce: 'crias', 'registro-cria': 'crias', anillar: 'crias', calculadora: 'crias', buscar: 'buscar', perfil: 'perfil' };
    const ACCENT = { inicio: 'green', canarios: 'yellow', jaulas: 'green', crias: 'coral', buscar: 'green', perfil: 'green' };

    function shell(name, html, opts = {}) {
        const sec = SECTION[name] ?? 'inicio';
        const side = NAV.map(([k, label, i]) => `<a class="side-link ${sec === k ? 'on' : ''}" href="#${k}" ${sec === k ? 'aria-current="page"' : ''}>${ic(i)}<span>${label}</span></a>`).join('');
        // Ajuste del cliente (19/11/2026): el menú muestra solo «Libro de crías», y Canarios y Jaulas se abren desde ahí.
        const enCrias = ['crias', 'canarios', 'jaulas'].includes(sec);
        const tab = (k, label, i) => `<a class="tab-link ${sec === k ? 'on' : ''}" href="#${k}" ${sec === k ? 'aria-current="page"' : ''}>${ic(i)}<span>${label}</span></a>`;
        const tabs = `${tab('inicio', 'Inicio', 'home')}<button type="button" class="tab-link ${enCrias ? 'on' : ''}" data-act="menu-crias" aria-haspopup="dialog">${ic('book')}<span>Libro de crías</span></button>${tab('perfil', 'Perfil', 'user')}`;
        const topNav = `<a class="side-link ${sec === 'inicio' ? 'on' : ''}" href="#inicio" ${sec === 'inicio' ? 'aria-current="page"' : ''}>${ic('home')}<span>Inicio</span></a><div class="umenu-wrap nav-drop"><button type="button" class="side-link ${enCrias ? 'on' : ''}" data-act="umenu" aria-haspopup="menu" aria-expanded="false">${ic('book')}<span>Libro de crías</span>${ic('chev', 'drop-chev')}</button><div class="umenu nav-menu" role="menu" hidden>${CRIAS_MENU.map(([k, label, i]) => `<a role="menuitem" href="#${k}" ${sec === k ? 'aria-current="page"' : ''}>${ic(i)}${label}</a>`).join('')}</div></div>`;
        const back = opts.back ? `<div class="page-back"><a class="top-back" href="${opts.back}">${ic('back')}<span>${esc(opts.title ?? 'Volver')}</span></a></div>` : '';
        return `<a class="skip-link" href="#main" data-skip>Ir al contenido</a><div class="layout accent-${ACCENT[sec]} view-${name}${sec === 'inicio' ? ' view-home' : ''}">
            <header class="topbar"><button class="top-btn only-m" data-act="mobile-menu" aria-label="Abrir menú" aria-haspopup="dialog" aria-controls="mobile-menu">${ic('menu')}</button><a class="brand" href="#inicio"><span class="brand-mark">${ic('bird')}</span><span>TuCri</span></a>
                <nav class="desktop-nav" aria-label="Menú principal">${topNav}</nav>
                <form class="top-search" data-form="buscar" role="search">${ic('search')}<input type="search" placeholder="Buscar anillo, variedad o jaula" aria-label="Buscar canario"></form>
                <button class="top-btn only-m" data-act="top-search" aria-label="Buscar">${ic('search')}</button>
                <div class="umenu-wrap"><button class="top-btn profile-btn" data-act="umenu" aria-haspopup="menu" aria-expanded="false" aria-label="Menú de ${esc(perfil.nombre)}"><span class="profile-label">Mi perfil</span><span class="profile-initials">${esc(perfil.nombre.split(' ').map(n => n[0]).slice(0, 2).join(''))}</span></button>
                    <div class="umenu" role="menu" hidden><p class="umenu-name">${esc(perfil.nombre)}<small>${esc(perfil.correo)}</small></p>
                        <a role="menuitem" href="#perfil">${ic('user')}Mi perfil</a><a role="menuitem" href="#calculadora">${ic('dna')}Calculadora genética</a><a role="menuitem" href="#buscar">${ic('search')}Buscar, incluidas bajas</a>
                        <a role="menuitem" class="umenu-out" href="#login">${ic('logout')}Cerrar sesión</a></div></div>
            </header>
            <dialog class="mobile-drawer" id="mobile-menu" aria-labelledby="mobile-menu-title">
                <header class="drawer-head"><a class="brand" href="#inicio"><span id="mobile-menu-title">TuCri</span></a><button class="drawer-close" data-act="mobile-menu-close" aria-label="Cerrar menú">${ic('exit')}</button></header>
                <p class="drawer-season">Tu aviario · Temporada 2026</p>
                <nav class="drawer-nav" aria-label="Menú móvil">${side}
                    <a class="side-link" href="#calculadora">${ic('dna')}<span>Calculadora genética</span></a>
                    <a class="side-link" href="#buscar">${ic('search')}<span>Buscar, incluidas bajas</span></a>
                </nav>
                <div class="drawer-foot"><a class="drawer-profile" href="#perfil"><span class="profile-initials">${esc(perfil.nombre.split(' ').map(n => n[0]).slice(0, 2).join(''))}</span><span><strong>${esc(perfil.nombre)}</strong><small>Mi perfil</small></span>${ic('chev')}</a><a class="side-link" href="#login">${ic('logout')}<span>Cerrar sesión</span></a></div>
            </dialog>
            <main class="main" id="main" tabindex="-1">${back}${html}</main>
            <nav class="tabbar" aria-label="Navegación principal">${tabs}</nav>
        </div>`;
    }
    // Ajuste del cliente (19/11/2026): jaula moderna ilustrada, la misma en tarjetas y estados vacíos.
    const cageArt = '<img class="cage-art" src="assets/jaula.png" alt="" aria-hidden="true">';
    const pageHead = (title, sub, icon, action = '') => `<header class="page-head"><span class="head-ic">${ic(icon)}</span><div class="head-txt"><h1>${title}</h1>${sub ? `<p>${sub}</p>` : ''}</div>${action}</header>`;
    const btn = (label, attrs, kind = 'primary', icon = '') => `<button class="btn btn-${kind}" ${attrs}>${icon ? ic(icon) : ''}<span>${label}</span></button>`;
    const link = (label, href, kind = 'primary', icon = '') => `<a class="btn btn-${kind}" href="${href}">${icon ? ic(icon) : ''}<span>${label}</span></a>`;

    // ================= Pantallas =================
    const V = {};

    // ---- Acceso ----
    function authShell(inner) {
        return `<div class="auth"><section class="auth-art"><a class="brand lg" href="#login"><span class="brand-mark">${ic('bird')}</span><span>TuCri</span></a>
            <div class="auth-claim"><h2>Tu aviario,<br>generación tras generación.</h2><p>Canarios, jaulas y crías en un mismo lugar.</p></div></section>
            <section class="auth-panel"><div class="auth-card">${inner}</div><p class="auth-foot">© 2026 TuCri</p></section></div>`;
    }
    const field = (label, input, hint = '') => `<label class="field"><span class="lbl">${label}</span>${input}${hint ? `<span class="hint">${hint}</span>` : ''}</label>`;
    V.login = () => authShell(`<h1>Bienvenido a tu criadero</h1><p class="auth-intro">Inicia sesión para seguir con tus canarios.</p>
        <form class="form" data-form="login">
            ${field('Correo electrónico', '<input type="email" placeholder="correo@ejemplo.cl" autocomplete="email">')}
            ${field('Contraseña', `<span class="pwd"><input type="password" placeholder="Tu contraseña" autocomplete="current-password"><button type="button" class="eye" data-act="eye" aria-label="Mostrar contraseña">${ic('eye')}</button></span>`)}
            <a class="link right" href="#recuperar">¿Olvidaste tu contraseña?</a>
            ${btn('Ingresar', 'type="submit"', 'primary big', 'chev')}
        </form><p class="center muted">¿Aún no tienes cuenta? <a class="link" href="#registro">Crear cuenta</a></p>`);
    V.registro = () => authShell(`<h1>Crear cuenta</h1><p class="auth-intro">Un lugar para llevar tu criadero al día.</p>
        <form class="form" data-form="registro">
            ${field('Nombre', '<input type="text" placeholder="Tu nombre" autocomplete="name">')}
            ${field('Correo electrónico', '<input type="email" placeholder="correo@ejemplo.cl" autocomplete="email">')}
            ${field('Contraseña', `<span class="pwd"><input type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password"><button type="button" class="eye" data-act="eye" aria-label="Mostrar contraseña">${ic('eye')}</button></span>`)}
            <label class="check"><input type="checkbox" id="consent"><span>Acepto el uso de mis datos para el servicio</span></label>
            ${btn('Crear cuenta', 'type="submit"', 'primary big')}
        </form><p class="center muted">¿Ya tienes cuenta? <a class="link" href="#login">Iniciar sesión</a></p>`);
    V.recuperar = (arg) => authShell(arg === 'enviado'
        ? `<span class="ok-badge">${ic('check')}</span><h1>Solicitud enviada</h1><p class="lead">Si el correo está registrado, el administrador de TuCri te contactará para ayudarte a recuperar el acceso.</p>${link('Volver a iniciar sesión', '#login', 'primary big')}`
        : `<h1>¿Olvidaste tu contraseña?</h1><p class="lead">Escribe el correo con el que creaste tu cuenta. El administrador de TuCri te ayudará a recuperar el acceso.</p>
          <form class="form" data-form="recuperar">${field('Correo electrónico', '<input type="email" placeholder="correo@ejemplo.cl" required>')}${btn('Enviar solicitud', 'type="submit"', 'primary big')}</form>
          <p class="center"><a class="link" href="#login">‹ Volver a iniciar sesión</a></p>`);

    // ---- Errores del sistema (contrato §errores: 404, 403, 419, 503, 500; sin conexión: SRS §2.5) ----
    const errState = (code, icon, tone, title, text, actions) => `<section class="err-state tone-${tone}"><span class="err-badge">${ic(icon)}</span>
        ${code ? `<p class="err-code">${code}</p>` : ''}<h1>${title}</h1><p class="lead">${text}</p><div class="err-actions">${actions}</div></section>`;
    V['404'] = () => shell('404', errState('Error 404', 'search', 'muted', 'No encontramos lo que buscas',
        'El canario, la jaula o el cruce no existe o ya no está en tu criadero. Revisa el anillo o búscalo de nuevo.',
        `${link('Ir al inicio', '#inicio', 'primary big', 'home')}${link('Buscar canario', '#buscar', 'soft big', 'search')}`));
    V['403'] = () => shell('403', errState('Error 403', 'lock', 'muted', 'Esta sección no está disponible para tu cuenta',
        'Solo el administrador de TuCri puede entrar aquí. Tus canarios y tus datos siguen a salvo.',
        link('Volver al inicio', '#inicio', 'primary big', 'home')));
    V.sesion = () => authShell(errState('', 'clock', 'warn', 'Tu sesión expiró',
        'Por seguridad cerramos la sesión después de un tiempo sin uso. Ingresa de nuevo para seguir. Si estabas guardando algo, revisa que haya quedado registrado.',
        link('Ingresar de nuevo', '#login', 'primary big', 'chev')));
    V.mantenimiento = () => authShell(errState('', 'tool', 'warn', 'Estamos en mantenimiento',
        'TuCri no está disponible por un momento. Tus canarios, jaulas y crías están guardados. Vuelve a intentarlo en unos minutos.',
        btn('Reintentar', 'data-act="reintentar"', 'primary big')));
    V['500'] = () => authShell(errState('', 'alert', 'danger', 'Algo salió mal',
        'No pudimos completar la acción. Inténtalo de nuevo; si vuelve a pasar, avisa al administrador de TuCri.',
        `${btn('Reintentar', 'data-act="reintentar"', 'primary big')}${link('Ir al inicio', '#inicio', 'ghost big')}`));
    V['sin-conexion'] = () => authShell(errState('', 'offline', 'danger', 'Sin conexión a internet',
        'Sin conexión no podemos consultar ni guardar datos. Lo que no alcanzaste a guardar no quedó registrado. Revisa tu señal o el wifi e inténtalo otra vez.',
        btn('Reintentar', 'data-act="reintentar"', 'primary big')));

    // ---- Inicio ----
    V.inicio = () => {
        const act = state.cruces.filter(x => !x.fin && x.temporada === '2026').sort((a, b) => porJaula(a.jaula, b.jaula));
        const pich = state.canarios.filter(c => c.anio === '2026').slice(-4).reverse();
        const crossCard = x => { const m = byId(x.macho), f = byId(x.hembra), n = pichonesDe(x).length;
            return `<a class="cross-card ${n ? 'has-chicks' : 'awaiting-chicks'}" href="#cruce/${x.id}"><div class="cross-card-head"><span class="cage-badge">${ic('cage')} ${cageName(x.jaula)} · Cruce ${esc(x.num)}</span>${ic('chev', 'chev')}</div>
                <div class="pair">${avatar(m.codigo, 'M', 'xl')}<span class="x">×</span>${avatar(f.codigo, 'H', 'xl')}</div>
                <p class="pair-rings">${pairRings(m, f)}</p>
                <p class="muted sm cross-vars" title="${esc(varName(m.codigo))} × ${esc(varName(f.codigo))}">${esc(varName(m.codigo))} × ${esc(varName(f.codigo))}</p>
                <p class="cross-meta"><span>Desde el ${esc(x.inicio)}</span><span class="pill ${n ? 'ok' : 'muted-pill'}">${n ? `${n} ${n === 1 ? 'pichón' : 'pichones'}` : 'Sin pichones aún'}</span></p></a>`; };
        const stats = [['Canarios', vivos().length, 'bird', '#canarios', 'yellow'], ['Jaulas', state.jaulas.length, 'cage', '#jaulas', 'green'], ['Cruces activos', act.length, 'book', '#crias', 'green'], ['Pichones 2026', state.canarios.filter(c => c.anio === '2026').length, 'egg', '#crias', 'yellow']];
        const cageMini = j => { const x = cruceActivo(j), occ = enJaula(j).length;
            return `<a class="cage-mini ${x ? 'busy' : ''}" href="#jaulas/${j}">${ic('cage')}<strong>${esc(cageName(j))}</strong><span>${x ? pairRings(byId(x.macho), byId(x.hembra)) : occ ? `${occ} ${occ === 1 ? 'ocupante' : 'ocupantes'}`: 'vacía'}</span></a>`; };
        return shell('inicio', `<div class="home-board"><div class="home-intro">
            <section class="hero"><div class="hero-txt"><h1>¡Hola, ${esc(perfil.nombre)}!</h1>
                <p>Así va tu criadero hoy. ${act.length ? `Tienes <strong>${act.length} cruces en marcha</strong>` : 'Aún no hay cruces en marcha'}.</p><span class="hero-season">Temporada 2026</span></div>
                <div class="hero-photo" role="img" aria-label="Aviario con jaulas de cría y canarios"></div></section>
            <!-- Pedido del cliente (19/11/2026): un solo botón, «Libro de crías», en lugar de «Registrar pareja» y «Registrar canario». -->
            <section class="home-actions home-libro" aria-label="Libro de crías">
                <a class="big-action t-coral libro-main" href="#crias"><span class="a-ic">${ic('book')}</span><span><strong>Libro de crías</strong><span>Cruces, pichones, canarios y jaulas</span></span>${ic('chev', 'chev')}</a>
            </section>
            </div>
            <section class="block home-crosses"><div class="block-head"><h2>Cruces en marcha <span class="section-count">${act.length}</span></h2><a class="link" href="#crias">Ver cruces ${ic('chev')}</a></div>
                <div class="cross-grid">${act.map(crossCard).join('') || `<div class="empty empty-art">${cageArt}<p><strong>Aún no hay cruces en marcha.</strong><br>Registra una pareja en su jaula para empezar la temporada.</p>${link('Registrar pareja', '#registro-cria', 'primary', 'book')}</div>`}</div></section>
            <div class="home-summary"><section class="card home-stats"><h2>Tu criadero</h2><div class="stats">${stats.map(([l, n, i, h, a]) => `<a class="stat t-${a}" href="${h}"><span class="stat-ic">${ic(i)}</span><strong>${n}</strong><span>${l}</span></a>`).join('')}</div></section>
            <section class="card home-cages"><div class="block-head"><h2>Tus jaulas</h2><a class="link" href="#jaulas" aria-label="Ver todas las jaulas">Ver todas ${ic('chev')}</a></div><p class="muted">Una pareja por jaula.</p><div class="cage-minis">${state.jaulas.map(cageMini).join('')}</div></section>
            </div>
            <section class="card home-chicks"><div class="block-head"><h2>Últimos pichones</h2><a class="link" href="#canarios">Ver canarios ${ic('chev')}</a></div><div class="list compact">${pich.map(birdRow).join('') || '<p class="muted">Aún no hay pichones anillados.</p>'}</div></section>
            <section class="card promo home-promo"><span class="a-ic">${ic('dna')}</span><h2>Calculadora de cruces</h2><p class="muted">Simula una pareja antes de juntarla para ver su descendencia probable.</p>${link('Probar una pareja', '#calculadora', 'soft', 'chev')}</section>
            </div>`);
    };
    const birdRow = c => `<a class="row" href="#canario/${c.id}">${avatar(c.codigo, c.sexo, 'md')}<span class="row-main"><strong class="ring">${esc(ring(c))}</strong><span class="row-sub">${esc(sexoTxt(c.sexo))} · ${varChip(c.codigo)}</span></span><span class="row-end">${pill(c.estado)}${c.jaula ? `<span class="muted sm">${esc(cageName(c.jaula))}</span>` : ''}</span>${ic('chev', 'chev')}</a>`;

    // ---- Canarios ----
    let filtro = 'todos', texto = '';
    V.canarios = () => {
        const chips = [['todos', 'Todos'], ['M', 'Machos'], ['H', 'Hembras'], ['?', 'Sin determinar'], ['reservado', 'Reservados']];
        return shell('canarios', `${pageHead('Mis canarios', `${vivos().length} canarios en el criadero. Los dados de baja se encuentran en Buscar.`, 'bird', link('Registrar canario', '#canario-nuevo', 'primary', 'plus'))}
            <div class="toolbar"><label class="search-box">${ic('search')}<input type="search" id="flt" placeholder="Filtra por anillo, variedad o jaula" value="${esc(texto)}" aria-label="Filtrar canarios"></label>
            <div class="chips" role="group" aria-label="Filtrar">${chips.map(([k, l]) => `<button class="chip ${filtro === k ? 'on' : ''}" data-act="filtro" data-v="${k}" aria-pressed="${filtro === k}">${l}</button>`).join('')}</div></div>
            <div class="bird-grid" id="bird-grid">${birdCards()}</div>`);
    };
    function birdCards() {
        const q = norm(texto);
        const list = vivos().filter(c => (filtro === 'todos' || c.sexo === filtro || c.estado === filtro) && (!q || norm(`${ring(c)} ${c.codigo ?? ''} ${varName(c.codigo)} ${cageName(c.jaula)}`).includes(q)));
        if (!list.length) return `<div class="empty empty-search">${ic('search')}<h2>No encontramos canarios</h2><p>Prueba con otro anillo o variedad, o quita los filtros.</p>${btn('Quitar filtros', 'data-act="limpiar-filtros"', 'soft')}</div>`;
        return list.map(c => `<a class="bird-card" href="#canario/${c.id}"><div class="bc-top">${avatar(c.codigo, c.sexo, 'xl')}</div>
            <div class="bc-body"><span class="ring-lbl">Anillo</span><strong class="ring">${esc(ring(c))}</strong><span class="bc-var">${varChip(c.codigo)}</span>
            <span class="bc-meta"><span>${esc(sexoTxt(c.sexo))}</span><span>${ic('cage')} ${esc(cageName(c.jaula))}</span></span>${c.estado !== 'activo' ? pill(c.estado) : ''}</div>${ic('chev', 'bird-card-chevron')}</a>`).join('');
    }

    // ---- Ficha del canario ----
    let fichaTab = 'familia';
    V.canario = (id) => {
        const c = byId(id); if (!c) return V.canarios();
        const pal = palette(c.codigo), p = c.codigo ? parse(c.codigo) : null;
        const padre = byId(c.padre), madre = byId(c.madre);
        const hijos = state.canarios.filter(x => x.padre === c.id || x.madre === c.id);
        const herm = state.canarios.filter(x => x.id !== c.id && x.padre && x.madre && x.padre === c.padre && x.madre === c.madre);
        const medios = state.canarios.filter(x => x.id !== c.id && !herm.includes(x) && ((c.padre && x.padre === c.padre) || (c.madre && x.madre === c.madre)));
        const cruces = state.cruces.filter(x => x.macho === c.id || x.hembra === c.id);
        const manifiesta = p ? Object.entries(LOCI).filter(([k, L]) => L.rec && (k === 'mo' ? p.mo : p[k])).map(([, L]) => L.name).concat(p.melanic ? [p.melanin.toLowerCase().replace('agata', 'ágata')] : [], p.red ? ['fondo rojo'] : [], p.I ? ['intenso'] : []) : [];
        const deducidos = c.sexo === 'M' && madre?.codigo ? Object.entries(LOCI).filter(([k, L]) => L.z && genotype(madre.codigo, 'H').g[k] === 1 && !(p && p[k])).map(([, L]) => L.carrierLabel).join(',').replace('bruno,ágata', 'isabel').split(',').filter(Boolean) : [];
        const mini = x => x ? `<a class="mini" href="#canario/${x.id}">${avatar(x.codigo, x.sexo, 'sm')}<span><strong class="ring">${esc(ring(x))}</strong><span class="muted sm">${varChip(x.codigo)}</span></span></a>` : '<span class="mini empty">Sin registro</span>';
        const acts = c.estado === 'baja' ? '' : `<div class="act-bar">
            ${btn('Editar ficha', 'data-act="soon" data-msg="En la maqueta la edición usa el mismo formulario del registro."', 'primary', 'edit')}
            ${btn('Mover de jaula', `data-act="mover" data-id="${c.id}"`, 'soft', 'move')}
            ${btn('Más acciones', `data-act="mas" data-id="${c.id}"`, 'soft', 'menu')}</div>`;
        const tabs = [['familia', 'Familia'], ['genetica', 'Genética'], ['historia', 'Historial']];
        const panels = {
            familia: `<section class="card"><h3>Árbol genealógico</h3><p class="muted">Padres, abuelos y bisabuelos (3 generaciones). A la izquierda, sus crías. Toca un ejemplar para ver el parentesco.</p>${pedigree(c)}</section>
                <div class="grid-2"><section class="card"><h3>Padres</h3><div class="minis">${mini(padre)}${mini(madre)}</div></section>
                <section class="card"><h3>Hermanos</h3><div class="minis">${herm.map(mini).join('') || '<span class="muted">Sin hermanos registrados</span>'}</div></section>
                <section class="card"><h3>Medios hermanos</h3><div class="minis">${medios.map(mini).join('') || '<span class="muted">Sin medios hermanos registrados</span>'}</div></section>
                <section class="card"><h3>Crías</h3><div class="minis">${hijos.map(mini).join('') || '<span class="muted">Sin crías registradas</span>'}</div></section></div>`,
            genetica: `<div class="grid-2"><section class="card"><h3>Factores visibles</h3><div class="tags">${manifiesta.map(t => `<span class="tag">${esc(t)}</span>`).join('') || '<span class="muted">Variedad sin determinar</span>'}</div></section>
                <section class="card"><h3>Portador de</h3>
                <div class="tags">${deducidos.map(t => `<span class="tag t-auto" title="Deducido por el sistema">${esc(t)} · deducido</span>`).join('')}${c.porta.map(k => `<span class="tag t-hand" title="Indicado por el criador">${esc(portaInfo(k)?.carrierLabel ?? k)} · indicado</span>`).join('') || (deducidos.length ? '' : '<span class="muted">Sin portadores conocidos</span>')}</div>
                ${deducidos.length ? '<p class="muted sm">«Deducido»: calculado automáticamente según su genealogía.</p>' : ''}</section></div>
                <a class="btn btn-violet" href="#calculadora/${c.sexo === 'H' ? '' : c.id}">${ic('dna')}<span>Probar cruces con este canario</span></a>`,
            historia: `<div class="grid-2"><section class="card"><h3>Cruces en que participó</h3><div class="minis">${cruces.map(x => `<a class="mini" href="#cruce/${x.id}">${ic('book')}<span><strong>Cruce ${x.num} · ${esc(cageName(x.jaula))}</strong><span class="muted sm">Temporada ${x.temporada} · ${x.fin ? 'terminado' : 'activo'}</span></span></a>`).join('') || '<span class="muted">No ha participado en cruces</span>'}</div></section>
                <section class="card"><h3>Datos del registro</h3><dl class="kv"><dt>Origen</dt><dd>${c.origen === 'adquirido' ? `Adquirido${c.procedencia ? ` · ${esc(c.procedencia)}` : ''}` : 'Nacido en el criadero'}</dd><dt>Nacimiento</dt><dd>${esc(c.nac || 'Sin registrar')}</dd><dt>Anillado</dt><dd>${esc(c.anillado || 'Sin registrar')}</dd>${c.baja ? `<dt>Fecha de baja</dt><dd>${esc(c.baja)}</dd>` : ''}</dl></section></div>`,
        };
        return shell('canario', `<section class="profile" style="--tint:${pal.bg};--deep:${pal.body}">
                <div class="profile-av">${avatar(c.codigo, c.sexo, 'xxl')}</div>
                <div class="profile-txt"><span class="ring-lbl">Anillo</span><h1 class="ring">${ring(c).split(' · ').map((p, i, a) => `<span class="rp">${esc(p)}${i < a.length - 1 ? ' ·' : ''}</span>`).join(' ')}</h1>
                    <div class="ring-parts"><span><small>Socio</small>${esc(c.socio)}</span><span><small>Año</small>${esc(c.anio)}</span><span><small>Correlativo</small>${esc(c.corr)}</span><span><small>Club</small>${esc(c.club)}</span></div>
                    <p class="profile-var">${varChip(c.codigo)}</p>
                    <div class="profile-meta">${pill(c.estado)}<span class="meta">${esc(sexoTxt(c.sexo))}</span><span class="meta">${ic('cage')} ${esc(c.jaula ? cageName(c.jaula) : 'Sin jaula')}</span></div>
                    ${acts}</div></section>
            <div class="seg-tabs" role="tablist">${tabs.map(([k, l]) => `<button role="tab" class="seg ${fichaTab === k ? 'on' : ''}" aria-selected="${fichaTab === k}" data-act="tab" data-v="${k}">${l}</button>`).join('')}</div>
            <div class="tab-body">${panels[fichaTab]}</div>`, { back: '#canarios', title: 'Mis canarios' });
    };
    function pedigree(c) {
        const heap = [c];
        for (let i = 0; i < 7; i++) { const x = heap[i]; heap[2 * i + 1] = x ? byId(x.padre) : null; heap[2 * i + 2] = x ? byId(x.madre) : null; }
        const roles = ['Este canario', 'Padre', 'Madre', 'Abuelo paterno', 'Abuela paterna', 'Abuelo materno', 'Abuela materna', 'P–P–P', 'P–P–M', 'P–M–P', 'P–M–M', 'M–P–P', 'M–P–M', 'M–M–P', 'M–M–M'];
        const crias = state.canarios.filter(x => x.padre === c.id || x.madre === c.id);
        let cells = '';
        const cell = (x, i, col, row, span) => `<button class="pcard ${i === 0 ? 'self' : ''} ${x ? '' : 'none'} ${i >= 100 ? 'child' : ''}" style="grid-column:${col};grid-row:${row} / span ${span}" data-act="ped" data-i="${i}" data-id="${x?.id ?? ''}" data-role="${esc(i >= 100 ? 'Cría' : roles[i])}">
            <span class="prole">${esc(i >= 100 ? 'Cría' : roles[i])}</span>${x ? `<span class="pring">${esc(ring(x))}</span>` : '<span class="pring">Sin registro</span>'}</button>`;
        const off = crias.length ? 2 : 0;
        for (let g = 0; g <= 3; g++) {
            const n = 2 ** g, span = 8 / n;
            for (let k = 0; k < n; k++) {
                const i = n - 1 + k;
                cells += cell(heap[i], i, g * 2 + 1 + off, k * span + 1, span);
                if (g < 3) cells += `<span class="pconn" style="grid-column:${g * 2 + 2 + off};grid-row:${k * span + 1} / span ${span}"><svg viewBox="0 0 20 100" preserveAspectRatio="none"><path d="M0 50H10M10 25V75M10 25H20M10 75H20"/></svg></span>`;
            }
        }
        if (crias.length) {
            const k = Math.min(crias.length, 8), span = Math.max(1, Math.floor(8 / k)), start = Math.floor((8 - span * k) / 2);
            let paths = '<path d="M20 50H10"/>';
            crias.slice(0, 8).forEach((x, j) => { cells += cell(x, 100 + j, 1, start + j * span + 1, span); paths += `<path d="M10 50V${((start + j * span + span / 2) / 8) * 100}H0"/>`; });
            cells += `<span class="pconn" style="grid-column:2;grid-row:1 / span 8"><svg viewBox="0 0 20 100" preserveAspectRatio="none">${paths}</svg></span>`;
        }
        const cols = (off ? 'var(--pc) var(--pg) ' : '') + 'var(--p0) var(--pg) var(--p1) var(--pg) var(--p2) var(--pg) var(--p3)';
        return `<div class="ped-wrap"><p class="ped-hint">Desliza para ver todas las generaciones →</p><div class="ped-scroll"><div class="ped" style="grid-template-columns:${cols}">${cells}</div></div>
            <div class="ped-detail" id="ped-detail"><strong>Este canario</strong><span>${esc(ring(c))}</span></div></div>`;
    }

    // ---- Registrar canario ----
    V['canario-nuevo'] = () => {
        const machos = vivos().filter(c => c.sexo === 'M'), hembras = vivos().filter(c => c.sexo === 'H');
        return shell('canario-nuevo', `${pageHead('Registrar canario', 'Solo el anillo es obligatorio. Lo demás puedes completarlo después.', 'bird')}
            <form class="form wide" data-form="canario">
            <section class="card step"><h2><span class="num">1</span>Anillo</h2><div class="ring-grid">
                ${field('Socio *', `<input name="socio" value="${esc(perfil.socio)}" required>`)}${field('Año *', '<input name="anio" value="2026" inputmode="numeric" required>')}
                ${field('Correlativo *', '<input name="corr" placeholder="Ej.: 014" inputmode="numeric" required>')}${field('Club *', `<input name="club" value="${esc(perfil.club)}" required>`)}</div>
                <p class="hint">Socio y club vienen de Mi perfil; puedes cambiarlos.</p></section>
            <section class="card step"><h2><span class="num">2</span>Características</h2>
                <div class="field"><span class="lbl">Sexo</span>${segRadio('sexo', [['M', '♂ Macho'], ['H', '♀ Hembra'], ['?', 'Sin determinar']], '?')}</div>
                ${field('Variedad (nomenclador 2026)', picker('variedad', ''), 'Escribe parte del nombre o el código. Puede quedar sin determinar.')}
                <div class="field"><span class="lbl">Portador de (opcional)</span>${carrierChips('porta', [])}</div></section>
            <section class="card step"><h2><span class="num">3</span>Origen y padres <small>opcional</small></h2>
                <div class="field"><span class="lbl">Origen</span>${segRadio('origen', [['criadero', 'Nacido en mi criadero'], ['adquirido', 'Adquirido']], 'criadero')}</div>
                <div class="grid-2">${field('Padre', `<select name="padre"><option value="">Sin registro</option>${machos.map(c => `<option value="${c.id}">${esc(ring(c))}</option>`).join('')}</select>`)}
                ${field('Madre', `<select name="madre"><option value="">Sin registro</option>${hembras.map(c => `<option value="${c.id}">${esc(ring(c))}</option>`).join('')}</select>`)}</div>
                <div class="grid-2">${field('Fecha de nacimiento', '<input name="nac" placeholder="dd/mm/aaaa">')}${field('Fecha de anillado', '<input name="anillado" placeholder="dd/mm/aaaa">')}</div>
                ${field('Jaula', `<select name="jaula"><option value="">Sin jaula asignada</option>${state.jaulas.map(j => `<option value="${j}">${cageName(j)}</option>`).join('')}</select>`, 'Puedes asignarla después. Cada jaula admite un macho y una hembra.')}</section>
            <div class="form-actions">${btn('Guardar canario', 'type="submit"', 'primary big', 'check')}${link('Cancelar', '#canarios', 'ghost big')}</div></form>`, { back: '#canarios', title: 'Mis canarios' });
    };
    const segRadio = (name, opts, val) => `<div class="segr" role="radiogroup">${opts.map(([v, l]) => `<label><input type="radio" name="${name}" value="${v}" ${v === val ? 'checked' : ''}><span>${l}</span></label>`).join('')}</div>`;
    // AC-06 (19/11/2026): sin mosaico; isabel en lugar de ágata; se agregan úrucum y pico amarillo.
    const PORTABLES = ['marfil', 'pastel', 'opalo', 'satine', 'ino', 'topacio', 'onix', 'mogno', 'cobalto', 'eumo', 'bruno', 'isabel', 'urucum', 'pico'];
    const portaInfo = k => k === 'isabel' ? { z: true, carrierLabel: 'isabel' } : LOCI[k];
    // Segundo nivel: los portadores son opcionales y se abren solo si hacen falta (menos carga para quien no sabe genética).
    const carrierBlock = (name, sel, femaleZ = false) => `<details class="porta" ${sel.length ? 'open' : ''}>
        <summary><span>Portador de <small>Opcional</small></span>${sel.length ? `<b class="porta-n">${sel.length} ${sel.length === 1 ? 'marcado' : 'marcados'}</b>` : ''}</summary>
        ${femaleZ ? '<p class="hint">Los factores ligados al sexo aparecen desactivados en hembras.</p>' : ''}
        ${carrierChips(name, sel, femaleZ)}</details>`;
    // Mantiene al día el conteo «N marcados» del resumen sin volver a dibujar la pantalla.
    const portaCount = el => {
        const d = el.closest('details.porta'); if (!d) return;
        const n = d.querySelectorAll('input:checked').length, sum = d.querySelector('summary');
        let b = sum.querySelector('.porta-n');
        if (!n) { b?.remove(); return; }
        if (!b) { b = document.createElement('b'); b.className = 'porta-n'; sum.append(b); }
        b.textContent = `${n} ${n === 1 ? 'marcado' : 'marcados'}`;
    };
    const carrierChips = (name, sel, femaleZ = false) => `<div class="chips wrap">${PORTABLES.map(k => { const z = portaInfo(k).z && femaleZ; return `<label class="chip-cb ${z ? 'dis' : ''}" ${z ? 'title="Una hembra no puede ser portadora de un factor ligado al sexo"' : ''}><input type="checkbox" name="${name}" value="${k}" ${sel.includes(k) ? 'checked' : ''} ${z ? 'disabled' : ''}><span>${esc(portaInfo(k).carrierLabel)}</span></label>`; }).join('')}</div>`;
    const picker = (name, code) => `<div class="picker" data-picker><input type="text" class="pk-in" placeholder="Ej.: D.049 o «rojo intenso»" value="${code ? esc(`${code} · ${varName(code)}`) : ''}" autocomplete="off" role="combobox" aria-expanded="false"><input type="hidden" name="${name}" value="${code ?? ''}"><ul class="pk-list" role="listbox" hidden></ul></div>`;

    // ---- Jaulas ----
    V.jaulas = (arg) => shell('jaulas', `${pageHead('Jaulas', 'Toca una jaula para ver quién está y su historial. La ocupación se calcula con los traslados.', 'cage', btn('Agregar jaula', 'data-act="nueva-jaula"', 'primary', 'plus'))}
        <div class="cage-grid">${state.jaulas.map(j => { const occ = enJaula(j), x = cruceActivo(j);
            return `<button class="cage ${arg === j ? 'sel' : ''}" data-act="jaula" data-v="${j}">${cageArt}
                <span class="cage-birds">${occ.slice(0, 4).map(c => avatar(c.codigo, c.sexo, 'sm')).join('')}${occ.length > 4 ? `<span class="more">+${occ.length - 4}</span>` : ''}</span>
                <span class="cage-name">${esc(cageName(j))}</span><span class="cage-occ">${occ.length ? `${occ.length} ${occ.length === 1 ? 'ocupante' : 'ocupantes'}` : 'Vacía'}</span>
                ${x ? `<span class="cage-pair"><span class="pill coral">Cruce ${x.num}</span><span class="pair-stack">${pairRings(byId(x.macho), byId(x.hembra))}</span></span>` : ''}</button>`; }).join('')}</div>
        ${arg ? cageDetail(arg) : ''}`);
    function cageDetail(j) {
        const occ = enJaula(j), x = cruceActivo(j), hist = state.historial[j] ?? [];
        const pareja = x ? (() => { const m = byId(x.macho), f = byId(x.hembra); const away = [m, f].filter(c => c.jaula !== j);
            return `<div class="pair-box"><h3>Pareja del cruce ${x.num}</h3><div class="pair-row">${parentCard(m, 'M')}<span class="x big">×</span>${parentCard(f, 'H')}</div>
            ${away.map(c => `<p class="notice sm">${ic('info')}<span>${esc(ring(c))} está ahora en ${esc(c.jaula ? cageName(c.jaula) : 'ninguna jaula')}. ${c.sexo === 'M' ? 'Un macho puede criar con varias hembras en jaulas distintas' : 'Una hembra puede criar con varios machos'}: cada pareja es un cruce aparte.</span></p>`).join('')}</div>`; })() : '';
        return `<section class="card detail" id="cage-detail"><div class="block-head"><h2>${esc(cageName(j))}</h2><a class="link" href="#jaulas">Cerrar ✕</a></div>${pareja}
            <div class="grid-2"><div><div class="block-head"><h3>Ocupantes ahora</h3>${occ.length < 2 ? btn('Agregar canario', `data-act="jaula-agregar" data-j="${esc(j)}"`, 'soft sm', 'plus') : '<span class="muted sm">Pareja completa</span>'}</div><div class="list">${occ.map(birdRow).join('') || `<div class="empty empty-art">${cageArt}<p><strong>Jaula vacía.</strong><br>Agrega un canario o registra aquí una pareja.</p></div>`}</div></div>
            <div><h3>Historial</h3><ol class="timeline">${hist.map(h => `<li>${esc(h)}</li>`).join('') || '<li class="muted">Sin movimientos registrados.</li>'}</ol>
            <div class="act-bar">${x ? link(`Ver cruce ${x.num}`, `#cruce/${x.id}`, 'soft', 'book') : link('Registrar pareja aquí', `#registro-cria/${j}`, 'coral', 'book')}</div></div></div></section>`;
    }

    // ---- Libro de crías ----
    let temporada = '2026';
    V.crias = () => {
        const list = state.cruces.filter(x => x.temporada === temporada).sort((a, b) => porJaula(a.jaula, b.jaula));
        const seasons = [...new Set(state.cruces.map(x => x.temporada).concat('2026'))].sort().reverse();
        return shell('crias', `${pageHead('Libro de crías', 'Cada pareja con su jaula, sus fechas y sus pichones, como en tu ficha de cría.', 'book', link('Registrar pareja', '#registro-cria', 'coral', 'plus') + link('Calculadora', '#calculadora', 'soft', 'dna'))}
            <div class="chips" role="group" aria-label="Temporada">${seasons.map(t => `<button class="chip ${t === temporada ? 'on' : ''}" data-act="temporada" data-v="${t}" aria-pressed="${t === temporada}">Temporada ${t}</button>`).join('')}</div>
            <div class="cross-list">${list.map(x => { const m = byId(x.macho), f = byId(x.hembra), pch = pichonesDe(x);
                return `<a class="cross-row" href="#cruce/${x.id}"><span class="cage-badge big"><small class="cr-num">Cruce ${esc(x.num)}</small><strong>${esc(cageName(x.jaula))}</strong></span>
                    <span class="cr-pair"><span class="cr-bird">${avatar(m.codigo, 'M', 'md')}<span><strong class="ring">${esc(ring(m))}</strong><span class="muted sm">${varChip(m.codigo)}</span></span></span>
                    <span class="x">×</span><span class="cr-bird">${avatar(f.codigo, 'H', 'md')}<span><strong class="ring">${esc(ring(f))}</strong><span class="muted sm">${varChip(f.codigo)}</span></span></span></span>
                    <span class="cr-end"><span class="pill ${x.fin ? 'off-soft' : 'ok'}">${x.fin ? 'Terminado' : 'Activo'}</span><span class="muted sm">${esc(x.inicio)}${x.fin ? ` – ${esc(x.fin)}` : ''}</span>
                    <span class="chick-row">${pch.map(c => avatar(c.codigo, c.sexo, 'xs')).join('')}<span class="muted sm">${pch.length} ${pch.length === 1 ? 'pichón' : 'pichones'}</span></span></span></a>`; }).join('') || `<div class="empty empty-art">${cageArt}<p><strong>No hay cruces en esta temporada.</strong><br>Cuando registres una pareja, su cruce aparecerá aquí con su jaula y sus pichones.</p></div>`}</div>`);
    };

    // ---- Ficha de cruce ----
    V.cruce = (id) => {
        const x = state.cruces.find(c => c.id === id); if (!x) return V.crias();
        const m = byId(x.macho), f = byId(x.hembra), pch = pichonesDe(x), r = calcular(m, f);
        return shell('cruce', `<section class="cross-hero"><span class="chip-gold">Temporada ${x.temporada}</span><h1>Cruce ${x.num} · ${esc(cageName(x.jaula))}</h1>
                <p>${x.fin ? `Terminado el ${esc(x.fin)}` : `Activo desde el ${esc(x.inicio)}`}</p>
                <div class="pair-cards">${parentCard(m, 'M')}<span class="x big">×</span>${parentCard(f, 'H')}</div></section>
            ${[m, f].flatMap(c => crucesActivosDe(c).filter(o => o.id !== x.id).map(o => `<p class="notice sm">${ic('info')}<span>${esc(ring(c))} también cría en ${esc(cageName(o.jaula))} (cruce ${o.num}).</span></p>`)).join('')}
            <div class="act-bar">${x.fin ? '' : link('Anillar pichones', `#anillar/${x.id}`, 'coral', 'ring')}${link('Ver posible resultado', `#calculadora/${m.id}/${f.id}`, 'violet', 'dna')}${x.fin ? '' : btn('Terminar cruce', `data-act="terminar" data-id="${x.id}"`, 'soft', 'check')}</div>
            <section class="block"><div class="block-head"><h2>Pichones (${pch.length})</h2></div><div class="list">${pch.map(birdRow).join('') || '<p class="empty">Aún no hay pichones anillados en este cruce.</p>'}</div></section>
            <section class="block"><div class="block-head"><h2>Lo que se espera de esta pareja</h2></div>${resultsHtml(r, true)}</section>`, { back: '#crias', title: 'Libro de crías' });
    };
    const parentCard = (c, s) => `<a class="parent ${s === 'M' ? 'is-m' : 'is-f'}" href="#canario/${c.id}">${avatar(c.codigo, s, 'lg')}<span><span class="ring-lbl">${s === 'M' ? 'Macho' : 'Hembra'}</span><strong class="ring">${esc(ring(c))}</strong><span class="sm">${varChip(c.codigo)}</span>${c.porta.length ? `<span class="tag t-hand">portador de ${esc(c.porta.map(k => LOCI[k].carrierLabel).join(', '))}</span>` : ''}</span></a>`;

    // ---- Resultados (compartido por calculadora, registro de cría y ficha de cruce) ----
    function resultsHtml(r, compact = false) {
        if (r.error) return `<div class="notice">${ic('info')}<p>${esc(r.error)}</p></div>`;
        const col = (sex) => `<section class="res-col ${sex === 'M' ? 'is-m' : 'is-f'}"><h3>${sex === 'M' ? 'Machos ♂' : 'Hembras ♀'}</h3>${r[sex].map(e => `<div class="res-row">${avatar(e.code, sex, 'sm')}
            <div class="res-txt"><span class="res-name">${e.code ? `<span class="code">${esc(e.code)}</span> ` : ''}${esc(resultName(e))}</span>
            ${e.code ? '' : '<span class="tag t-warn">Sin variedad en el catálogo</span>'}${e.carriers.map(cr => `<span class="tag t-hand">portador de ${esc(cr)}</span>`).join('')}${e.notes.map(n => `<span class="tag t-warn">${esc(n)}</span>`).join('')}
            <span class="bar"><span style="width:${(e.p * 100).toFixed(1)}%"></span></span></div><strong class="res-pct">${pct(e.p)}</strong></div>`).join('')}</section>`;
        const how = `<details class="how" ${compact || innerWidth < 900 ? '' : 'open'}><summary>${ic('spark')}Desglose por factor</summary><p>Herencia calculada por factor independiente.</p>
            <ol class="how-steps">${r.explain.map(e => `<li><div class="hs-head"><strong>${esc(e.name)}</strong><span class="kind">${esc(e.type)}</span></div>
                ${e.simple ? `<p>${esc(e.simple)}</p>` : `<p class="hs-par"><span>♂ Padre: <strong>${esc(e.m)}</strong></span><span>♀ Madre: <strong>${esc(e.f)}</strong></span></p><p class="hs-res"><span>→ Machos: ${esc(e.M)}</span><span>→ Hembras: ${esc(e.H)}</span></p>`}</li>`).join('')}</ol>
            <p class="muted sm">Son probabilidades: con pocas crías puede haber diferencias. Se supone que cada factor se hereda por separado.</p></details>`;
        const extra = (r.sinRegla.length ? `<div class="notice warn">${ic('info')}<p>Sin regla en el catálogo para: <strong>${esc(r.sinRegla.join(', '))}</strong>. Ese factor no se calcula; el resto sí.</p></div>` : '') + (r.lethal ? `<div class="notice warn">${ic('info')}<p>${pct(r.lethal)} de las combinaciones (doble blanco dominante) no es viable y se excluyó.</p></div>` : '');
        return `${extra}<div class="results">${col('M')}${col('H')}</div>${how}`;
    }

    // ---- Calculadora genética ----
    const calc = { M: { src: 'nom', id: 'c150', codigo: 'D.143', porta: ['marfil'] }, H: { src: 'nom', id: 'c151', codigo: 'D.146', porta: [] } };
    V.calculadora = (a, b) => {
        if (a && byId(a)) { const c = byId(a); calc[c.sexo === 'H' ? 'H' : 'M'] = { src: 'mis', id: c.id, codigo: c.codigo, porta: [...c.porta] }; }
        if (b && byId(b)) { const c = byId(b); calc.H = { src: 'mis', id: c.id, codigo: c.codigo, porta: [...c.porta] }; }
        return shell('calculadora', `${pageHead('Calculadora de cruces', 'Elige un macho y una hembra para calcular la descendencia probable sin modificar tu criadero.', 'dna')}
            <div class="calc"><div class="calc-parents">${calcParent('M')}<span class="x big">×</span>${calcParent('H')}</div>
            <div class="calc-out" id="calc-out">${resultsHtml(calcular(calc.M, calc.H))}</div></div>`, { back: '#crias', title: 'Libro de crías' });
    };
    function calcParent(s) {
        const p = calc[s], own = vivos().filter(c => c.sexo === s);
        return `<section class="cparent ${s === 'M' ? 'is-m' : 'is-f'}" data-sex="${s}"><div class="cp-head">${avatar(p.codigo, s, 'lg')}<h2>${s === 'M' ? 'Macho' : 'Hembra'}</h2></div>
            <div class="seg-mini" role="tablist"><button class="${p.src === 'mis' ? 'on' : ''}" data-act="csrc" data-s="${s}" data-v="mis">De mis canarios</button><button class="${p.src === 'nom' ? 'on' : ''}" data-act="csrc" data-s="${s}" data-v="nom">Cualquier variedad</button></div>
            ${p.src === 'mis' ? field(s === 'M' ? 'Macho del criadero' : 'Hembra del criadero', `<select data-act="cown" data-s="${s}">${own.map(c => `<option value="${c.id}" ${c.id === p.id ? 'selected' : ''}>${esc(ring(c))} — ${esc(c.codigo ?? '?')}</option>`).join('')}</select>`)
                : field('Variedad (nomenclador 2026)', picker(`calc-${s}`, p.codigo))}
            ${carrierBlock(`cp-${s}`, p.porta, s === 'H')}</section>`;
    }
    function refreshCalc() {
        const out = document.querySelector('#calc-out'); if (out) out.innerHTML = resultsHtml(calcular(calc.M, calc.H));
        for (const s of ['M', 'H']) { const av = document.querySelector(`.cparent[data-sex="${s}"] .cp-head .bird-thumb`); if (av) av.outerHTML = avatar(calc[s].codigo, s, 'lg'); }
    }

    // ---- Registro de cría 2026 (pedido del cliente) ----
    const rc = { jaula: '5', inicio: hoy, M: { src: 'nueva', id: 'c150', socio: '277', anio: '2020', corr: '150', club: 'SOCH', codigo: 'D.143', porta: ['marfil'] }, H: { src: 'nueva', id: 'c151', socio: 'RH955', anio: '2025', corr: '1245', club: 'ACAM', codigo: 'D.146', porta: [] } };
    const rcBird = s => (rc[s].src === 'mis' ? byId(rc[s].id) : rc[s]);
    let rcStep = 1;
    V['registro-cria'] = (j) => {
        if (j && state.jaulas.includes(j)) rc.jaula = j;
        const pane = s => { const p = rc[s], own = vivos().filter(c => c.sexo === s && c.estado === 'activo');
            return `<section class="cparent ${s === 'M' ? 'is-m' : 'is-f'}" data-rc="${s}"><div class="cp-head">${avatar(rcBird(s)?.codigo, s, 'lg')}<h2>${s === 'M' ? 'Macho' : 'Hembra'}</h2></div>
                <div class="seg-mini"><button class="${p.src === 'mis' ? 'on' : ''}" data-act="rsrc" data-s="${s}" data-v="mis">De mis canarios</button><button class="${p.src === 'nueva' ? 'on' : ''}" data-act="rsrc" data-s="${s}" data-v="nueva">Ave nueva</button></div>
                ${p.src === 'mis' ? `${field('Elige', `<select data-act="rown" data-s="${s}">${own.map(c => { const o = crucesActivosDe(c); return `<option value="${c.id}" ${c.id === p.id ? 'selected' : ''}>${esc(ring(c))} — ${esc(varName(c.codigo))}${o.length ? ` · ya cría en ${o.map(x => cageName(x.jaula)).join(', ')}` : ''}</option>`; }).join('')}</select>`)}<p class="hint">Solo canarios disponibles: sin reserva y sin baja. ${s === 'M' ? 'Un macho puede criar con varias hembras en jaulas distintas' : 'Una hembra puede criar con varios machos'}: cada pareja es un cruce aparte.</p>`
                : `<div class="ring-grid sm">${['socio', 'anio', 'corr', 'club'].map(k => field({ socio: 'Socio', anio: 'Año', corr: 'Correlativo', club: 'Club' }[k], `<input data-rc-f="${s}" data-k="${k}" value="${esc(p[k])}">`)).join('')}</div>
                   ${field('Variedad (nomenclador 2026)', picker(`rc-${s}`, p.codigo))}${carrierBlock(`rcp-${s}`, p.porta, s === 'H')}
                   <p class="hint">Al guardar, el ave queda registrada en Mis canarios.</p>`}</section>`; };
        const steps = [['Jaula', 'cage'], ['Pareja', 'bird'], ['Resultado', 'dna']];
        const stepper = `<ol class="stepper wiz">${steps.map(([l, i], k) => `<li class="${k + 1 < rcStep ? 'done' : ''} ${k + 1 === rcStep ? 'cur' : ''}"><button type="button" data-act="rc-step" data-v="${k + 1}" ${k + 1 > rcStep ? 'disabled' : ''}><span>${k + 1 < rcStep ? ic('check') : k + 1}</span>${l}</button></li>`).join('')}</ol>`;
        const summary = (title, body, step) => `<div class="done-step"><span class="done-ic">${ic('check')}</span><div><strong>${title}</strong><span class="muted sm">${body}</span></div><button class="btn btn-ghost sm" data-act="rc-step" data-v="${step}">Cambiar</button></div>`;
        const birdSum = s => { const b = rcBird(s); return b ? `${s === 'M' ? '♂' : '♀'} ${esc(`${b.socio} · ${b.anio} · ${b.corr} · ${b.club}`)} · ${esc(varName(b.codigo))}` : `${s === 'M' ? '♂' : '♀'} sin datos`; };
        let body = '';
        // Una jaula tiene una sola pareja a la vez: las que ya tienen un cruce activo no se pueden elegir y dicen por qué,
        // en vez de rechazarse recién al guardar (Conclusiones conjuntas 14/11).
        if (cruceActivo(rc.jaula)) rc.jaula = state.jaulas.find(jj => !cruceActivo(jj)) ?? rc.jaula;
        if (rcStep === 1) body = `<section class="card step"><h2><span class="num">1</span>Jaula y fecha</h2><p class="hint">Cada jaula admite una pareja activa. Las jaulas ocupadas aparecen desactivadas.</p><div class="cage-pick">${state.jaulas.map(jj => { const aves = enJaula(jj), busy = cruceActivo(jj);
                const nota = busy ? `Cruce ${busy.num} en marcha` : aves.length ? `Ocupada por ${aves.map(c => esc(ring(c))).join(' y ')}` : 'Vacía';
                return `<label class="cp-cage ${busy ? 'busy' : ''}"><input type="radio" name="rc-jaula" value="${jj}" ${rc.jaula === jj ? 'checked' : ''} ${busy ? 'disabled' : ''}><span>${ic('cage')}<strong>${esc(cageName(jj))}</strong><small>${nota}</small></span></label>`; }).join('')}</div>
                <div class="grid-2">${field('Temporada', '<input value="2026" readonly>')}${field('Fecha de inicio', `<input id="rc-inicio" value="${esc(rc.inicio)}">`)}</div></section>
            <div class="form-actions">${btn('Siguiente: la pareja', 'data-act="rc-step" data-v="2"', 'coral big', 'chev')}${link('Cancelar', '#crias', 'ghost big')}</div>`;
        else if (rcStep === 2) body = `${summary(cageName(rc.jaula), `Temporada 2026 · desde el ${esc(rc.inicio)}`, 1)}
            <section class="card step"><h2><span class="num">2</span>Macho y hembra</h2><p class="hint">Selecciona aves del criadero o registra un ave nueva.</p><div class="calc-parents">${pane('M')}<span class="x big">×</span>${pane('H')}</div></section>
            <div class="form-actions">${btn('Ver posible resultado', 'data-act="rc-step" data-v="3"', 'coral big', 'dna')}${btn('Atrás', 'data-act="rc-step" data-v="1"', 'ghost big')}</div>`;
        else body = `${summary(cageName(rc.jaula), `Temporada 2026 · desde el ${esc(rc.inicio)}`, 1)}${summary('La pareja', `${birdSum('M')}<br>${birdSum('H')}`, 2)}
            <section class="card step"><h2><span class="num">3</span>Resultado esperado</h2><div id="rc-out">${resultsHtml(calcular(rcBird('M'), rcBird('H')))}</div></section>
            <div class="form-actions">${btn('Guardar en el libro de crías', 'data-act="rc-save"', 'coral big', 'check')}${btn('Atrás', 'data-act="rc-step" data-v="2"', 'ghost big')}</div>`;
        return shell('registro-cria', `${pageHead('Registrar pareja', 'Elige la jaula, el macho y la hembra.', 'book')}${stepper}${body}`, { back: '#crias', title: 'Libro de crías' });
    };
    function refreshRc() {
        const out = document.querySelector('#rc-out'); if (out) out.innerHTML = resultsHtml(calcular(rcBird('M'), rcBird('H')));
        for (const s of ['M', 'H']) { const av = document.querySelector(`[data-rc="${s}"] .cp-head .bird-thumb`); if (av) av.outerHTML = avatar(rcBird(s)?.codigo, s, 'lg'); }
    }

    // ---- Anillar pichones ----
    let anillarN = 2;
    V.anillar = (id) => {
        const x = state.cruces.find(c => c.id === id); if (!x) return V.crias();
        const m = byId(x.macho), f = byId(x.hembra), next = 14 + pichonesDe(x).length;
        const rows = Array.from({ length: anillarN }, (_, i) => `<section class="card chick"><h3>${avatar(null, '?', 'sm')} Pichón ${i + 1}</h3><div class="ring-grid sm">
            ${field('Socio', `<input value="${esc(perfil.socio)}">`)}${field('Año', '<input value="2026">')}${field('Correlativo *', `<input name="c${i}" value="${String(next + i).padStart(3, '0')}">`)}${field('Club', `<input value="${esc(perfil.club)}">`)}</div>
            <div class="field"><span class="lbl">Sexo</span>${segRadio(`s${i}`, [['M', '♂'], ['H', '♀'], ['?', 'Sin determinar']], '?')}</div></section>`).join('');
        return shell('anillar', `${pageHead('Anillar pichones', `Cruce ${x.num} · ${esc(cageName(x.jaula))} · padres heredados: ${esc(ring(m))} × ${esc(ring(f))}`, 'ring')}
            <form class="form wide" data-form="anillar" data-id="${x.id}">${field('Fecha de anillado (común)', `<input value="${hoy}">`)}<p class="hint">La jaula del cruce conserva su pareja. Los pichones quedan sin jaula asignada; puedes ubicarlos después desde su ficha cuando conozcas su sexo.</p><div class="chicks">${rows}</div>
            ${btn('Agregar otro pichón', 'type="button" data-act="mas-pichon"', 'soft', 'plus')}
            <div class="form-actions">${btn(`Guardar ${anillarN} pichones`, 'type="submit"', 'coral big', 'check')}${link('Cancelar', `#cruce/${x.id}`, 'ghost big')}</div></form>`, { back: `#cruce/${x.id}`, title: `Cruce ${x.num} · ${cageName(x.jaula)}` });
    };

    // ---- Buscar ----
    let busq = '150';
    V.buscar = (q) => {
        if (q !== undefined) busq = decodeURIComponent(q);
        const n = norm(busq.trim());
        const res = n ? state.canarios.filter(c => norm(`${ring(c)} ${c.codigo ?? ''} ${varName(c.codigo)} ${c.jaula ? cageName(c.jaula) : ''}`).includes(n)) : [];
        return shell('buscar', `${pageHead('Buscar', 'Por anillo completo, correlativo, variedad o jaula. Incluye los dados de baja.', 'search')}
            <form class="search-big" data-form="buscar2" role="search">${ic('search')}<input type="search" id="q" value="${esc(busq)}" placeholder="Ej.: 150 · D.049 · rojo intenso · Jaula 2" aria-label="Buscar"><button class="btn btn-blue">Buscar</button></form>
            ${n ? `<p class="muted">${res.length} ${res.length === 1 ? 'resultado' : 'resultados'} para «${esc(busq)}»${res.length > 1 && /^\d+$/.test(busq.trim()) ? ' · compara socio, año y club para saber cuál es' : ''}</p>` : ''}
            <div class="list">${res.map(birdRow).join('') || (n ? '<p class="empty">No encontramos canarios con esa búsqueda.</p>' : '')}</div>`, { back: '#inicio', title: 'Inicio' });
    };

    // ---- Perfil ----
    V.perfil = () => shell('perfil', `${pageHead('Mi perfil', 'Tus datos y los valores que se proponen al registrar anillos.', 'user')}
        <form class="form wide" data-form="perfil"><section class="card step"><div class="grid-2">${field('Nombre', `<input value="${esc(perfil.nombre)}">`)}${field('Correo', `<input type="email" value="${esc(perfil.correo)}">`)}
        ${field('Número de socio', `<input value="${esc(perfil.socio)}">`)}${field('Club (sigla del anillo)', `<input value="${esc(perfil.club)}">`)}</div></section>
        <div class="form-actions">${btn('Guardar cambios', 'type="submit"', 'primary big', 'check')}</div></form>`, { back: '#inicio', title: 'Inicio' });

    // ================= Movimiento: menú lateral, hojas y pasos =================
    // Menú y hojas entran y salen por el mismo lado; en el teléfono siguen al dedo y se cierran arrastrando.
    const isPhone = () => matchMedia('(max-width: 899px)').matches;
    // Espera el fin de la transición, con un tope por si no llega (movimiento reducido o pestaña oculta).
    function afterTransition(el, ms, done) {
        let ok = false;
        const fin = () => { if (ok) return; ok = true; el.removeEventListener('transitionend', onEnd); done(); };
        const onEnd = e => { if (e.target === el) fin(); };
        el.addEventListener('transitionend', onEnd);
        setTimeout(fin, ms);
    }
    // Arrastre para cerrar: el panel sigue al dedo; se cierra si pasó un tercio de su tamaño o si el gesto fue rápido,
    // y si no, vuelve a su lugar. axis 'x' cierra hacia la izquierda (menú); 'y', hacia abajo (hoja).
    function dragToClose(handle, panel, axis, onClose) {
        if (!handle || !panel) return;
        let suppress = false;
        handle.addEventListener('dragstart', e => e.preventDefault());
        handle.addEventListener('click', e => { if (suppress) { e.preventDefault(); e.stopPropagation(); suppress = false; } }, true);
        handle.addEventListener('pointerdown', e => {
            if (!isPhone() || e.button !== 0 || e.target.closest('input, select, textarea')) return;
            const pos = ev => axis === 'x' ? [-(ev.clientX - e.clientX), ev.clientY - e.clientY] : [ev.clientY - e.clientY, ev.clientX - e.clientX];
            const size = axis === 'x' ? panel.offsetWidth : panel.offsetHeight, t0 = performance.now();
            let drag = false, d = 0;
            const put = v => { panel.style.transform = axis === 'x' ? `translateX(${-v}px)` : `translateY(${v}px)`; };
            const stop = () => {
                document.removeEventListener('pointermove', move);
                document.removeEventListener('pointerup', up);
                document.removeEventListener('pointercancel', up);
            };
            const move = ev => {
                if (!panel.isConnected || panel.classList.contains('is-closing') || panel.closest('.sheet-ghost')) return stop(); // se cerró por otra vía
                const [along, across] = pos(ev);
                if (!drag) {
                    if (Math.abs(along) < 10 && Math.abs(across) < 10) return;
                    if (along < 10 || Math.abs(across) > along) return stop();
                    drag = true; suppress = true;
                    try { handle.setPointerCapture(ev.pointerId); } catch { /* el puntero ya terminó */ }
                    panel.style.transition = 'none';
                }
                d = along;
                put(d > 0 ? d : -Math.sqrt(-d) * 2);
            };
            const up = () => {
                stop();
                if (!drag) return;
                setTimeout(() => { suppress = false; });
                if (d > size / 3 || (d > 20 && d / (performance.now() - t0) > 0.11)) return onClose();
                panel.style.transition = 'transform 200ms var(--ease-ui)';
                panel.style.transform = '';
                afterTransition(panel, 260, () => { panel.style.transition = ''; });
            };
            document.addEventListener('pointermove', move);
            document.addEventListener('pointerup', up);
            document.addEventListener('pointercancel', up);
        });
    }
    // ¿El toque cayó dentro del menú o en el fondo oscurecido? Los dos llegan con el mismo destino (el diálogo).
    const drawerHit = (e, d) => { const r = d.getBoundingClientRect(); return e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom; };
    function closeDrawer(d) {
        if (!d?.open || d.classList.contains('is-closing')) return;
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) { d.close(); return; }
        d.classList.add('is-closing');
        d.style.transform = ''; d.style.transition = '';
        afterTransition(d, 360, () => { if (d.open) d.close(); });
    }
    // Escape cierra el menú con el mismo movimiento; cualquier cierre deja el menú listo para la próxima vez.
    document.addEventListener('cancel', e => { if (e.target.matches?.('.mobile-drawer') && e.cancelable) { e.preventDefault(); closeDrawer(e.target); } }, true);
    document.addEventListener('close', e => { if (e.target.matches?.('.mobile-drawer')) { e.target.classList.remove('is-closing'); e.target.style.transform = ''; e.target.style.transition = ''; } }, true);
    // Pasos: lo nuevo entra desde el lado hacia el que se avanza (adelante, desde la derecha; atrás, desde la izquierda).
    function slideStep(dir) {
        if (!dir) return;
        document.querySelectorAll('.main > .stepper.wiz ~ *').forEach(el => { el.style.setProperty('--dir', dir); el.classList.add('step-in'); });
    }

    // ================= Hojas (modales) =================
    let sheetReturnFocus = null;
    function sheet(title, body, actions) {
        if (!sheetRoot.innerHTML) sheetReturnFocus = document.activeElement;
        sheetRoot.innerHTML = `<div class="sheet-bg" data-act="close"></div><div class="sheet" role="dialog" aria-modal="true" aria-label="${esc(title)}"><span class="sheet-grip" aria-hidden="true"></span><h2>${esc(title)}</h2>${body}<div class="sheet-actions">${actions}</div></div>`;
        app.inert = true;
        document.body.style.overflow = 'hidden';
        sheetRoot.querySelector('input, select, button:not([data-act=close])')?.focus();
        const s = sheetRoot.querySelector('.sheet');
        sheetRoot.querySelectorAll('.sheet-grip, .sheet > h2').forEach(h => dragToClose(h, s, 'y', closeSheet));
    }
    // La hoja sale por el mismo camino por el que entró. Se cierra en el acto (estado, foco y página quedan libres)
    // y una copia inerte termina el movimiento encima, para no demorar lo que venga después.
    function sheetExit() {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const bg = sheetRoot.querySelector('.sheet-bg'), s = sheetRoot.querySelector('.sheet');
        if (!s) return;
        const ghost = document.createElement('div');
        ghost.className = 'sheet-ghost'; ghost.inert = true; ghost.setAttribute('aria-hidden', 'true');
        ghost.append(...[bg, s].filter(Boolean));
        ghost.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
        document.body.append(ghost);
        s.getBoundingClientRect();
        ghost.classList.add('out');
        s.style.transform = ''; s.style.transition = '';
        afterTransition(s, 360, () => ghost.remove());
    }
    const closeSheet = () => { const wasOpen = !!sheetRoot.innerHTML; if (wasOpen) sheetExit(); sheetRoot.innerHTML = ''; app.inert = false; document.body.style.overflow = ''; if (wasOpen && sheetReturnFocus?.isConnected) sheetReturnFocus.focus(); sheetReturnFocus = null; };
    function criasSheet() {
        sheet('Libro de crías', `<div class="quick">${[['#crias', 'book', 'Libro de crías', 'Cruces y pichones por temporada', 'coral'], ['#canarios', 'bird', 'Canarios', 'Todos los canarios del criadero', 'yellow'], ['#jaulas', 'cage', 'Jaulas', 'Quién está en cada jaula', 'green']]
            .map(([h, i, t, d, a]) => `<a class="action t-${a}" href="${h}" data-act="close-nav"><span class="a-ic">${ic(i)}</span><strong>${t}</strong><span>${d}</span></a>`).join('')}</div>`, btn('Cerrar', 'data-act="close"', 'ghost'));
    }
    function quickSheet() {
        sheet('¿Qué quieres hacer?', `<div class="quick">${[['#canario-nuevo', 'bird', 'Registrar canario', 'Anillo, variedad y padres', 'yellow'], ['#registro-cria', 'book', 'Registrar pareja', 'Elige la jaula, el macho y la hembra', 'coral'], ['#calculadora', 'dna', 'Calculadora genética', '¿Qué saldrá de una pareja?', 'violet'], ['#buscar', 'search', 'Buscar', 'Anillo, variedad o jaula', 'blue']]
            .map(([h, i, t, d, a]) => `<a class="action t-${a}" href="${h}" data-act="close-nav"><span class="a-ic">${ic(i)}</span><strong>${t}</strong><span>${d}</span></a>`).join('')}</div>`, btn('Cerrar', 'data-act="close"', 'ghost'));
    }

    // ================= Selector de variedad =================
    function wirePickers(root = document) {
        for (const pk of root.querySelectorAll('[data-picker]')) {
            if (pk.dataset.wired) continue; pk.dataset.wired = '1';
            const input = pk.querySelector('.pk-in'), hidden = pk.querySelector('input[type=hidden]'), list = pk.querySelector('.pk-list');
            const show = () => {
                const words = norm(input.value).split(/[\s·]+/).filter(Boolean);
                const hits = CATALOGO.filter(([c, n]) => { const t = norm(`${c} ${n}`); return words.every(w => t.includes(w)); }).slice(0, 8);
                list.innerHTML = hits.map(([c, n]) => `<li role="option" data-code="${c}"><span class="code">${c}</span> ${esc(n)}</li>`).join('') || '<li class="pk-empty">Sin coincidencias en el nomenclador.</li>';
                list.hidden = false; input.setAttribute('aria-expanded', 'true');
            };
            input.addEventListener('focus', () => { input.select(); show(); });
            input.addEventListener('input', show);
            input.addEventListener('blur', () => setTimeout(() => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); }, 150));
            list.addEventListener('mousedown', e => {
                const li = e.target.closest('li[data-code]'); if (!li) return; e.preventDefault();
                input.value = `${li.dataset.code} · ${varName(li.dataset.code)}`; hidden.value = li.dataset.code; list.hidden = true;
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    }

    // ================= Acciones =================
    document.addEventListener('click', e => {
        if (e.target.closest('[data-skip]')) { e.preventDefault(); document.querySelector('#main')?.focus(); return; }
        const drawer = document.querySelector('#mobile-menu');
        if (drawer?.open && e.target.closest('.mobile-drawer a')) drawer.close();
        else if (drawer?.open && e.target === drawer && !drawerHit(e, drawer)) closeDrawer(drawer);
        if (!e.target.closest('.umenu-wrap')) document.querySelectorAll('.umenu').forEach(x => { x.hidden = true; x.previousElementSibling?.setAttribute('aria-expanded', 'false'); });
        const t = e.target.closest('[data-act]'); if (!t) return;
        const a = t.dataset.act;
        if (a === 'mobile-menu' && matchMedia('(max-width: 899px)').matches) { t.focus(); return drawer.showModal(); }
        if (a === 'mobile-menu-close') return closeDrawer(drawer);
        if (a === 'close') return closeSheet();
        if (a === 'close-nav') return closeSheet();
        if (a === 'jaula-agregar') {
            const j = t.dataset.j, occ = enJaula(j);
            if (occ.length >= 2) return toast('La jaula ya tiene su pareja completa.');
            const disponibles = vivos().filter(c => c.jaula !== j && (c.sexo === 'M' || c.sexo === 'H'));
            const validos = disponibles.filter(c => !conflictoJaula(j, [c]));
            const plaza = occ.length ? `Puedes agregar ${occ[0].sexo === 'M' ? 'una hembra' : 'un macho'}.` : 'Puedes agregar un macho y una hembra.';
            return sheet(`Agregar canario a ${cageName(j)}`, `<p class="muted">${plaza} Si está en otra jaula, se registrará su traslado.</p>
                ${validos.length ? field('Canario', `<select id="jaula-canario">${disponibles.map(c => { const motivo = conflictoJaula(j, [c]); return `<option value="${c.id}" ${motivo ? 'disabled' : ''} ${c.id === validos[0].id ? 'selected' : ''}>${esc(ring(c))} · ${sexoTxt(c.sexo)} · ${esc(c.jaula ? cageName(c.jaula) : 'Sin jaula')}${motivo ? ' · sexo ya presente' : ''}</option>`; }).join('')}</select>`) : '<p>No hay canarios registrados que puedan ocupar esta plaza. Puedes registrar uno nuevo desde Mis canarios.</p>'}
                ${validos.length ? field('Fecha del traslado', `<input id="jaula-fecha" value="${hoy}">`) : ''}`,
                `${validos.length ? btn('Agregar a la jaula', `data-act="jaula-agregar-ok" data-j="${esc(j)}"`, 'primary') : ''}${btn('Cancelar', 'data-act="close"', 'ghost')}`);
        }
        if (a === 'jaula-agregar-ok') {
            const j = t.dataset.j, c = byId(sheetRoot.querySelector('#jaula-canario')?.value);
            if (!c || c.estado === 'baja') return toast('Selecciona un canario del criadero que no esté dado de baja.');
            const error = conflictoJaula(j, [c]); if (error) return toast(error);
            ubicarCanario(c, j, sheetRoot.querySelector('#jaula-fecha')?.value || hoy);
            closeSheet(); render(); return toast(`Canario ${c.corr} agregado a ${cageName(j)}.`);
        }
        // Maqueta: reintentar vuelve al inicio; en la app real se repite la petición.
        if (a === 'reintentar') return navigator.onLine ? go('#inicio') : toast('Sigues sin conexión. Revisa tu señal e inténtalo otra vez.');
        if (a === 'menu-crias') return criasSheet();
        if (a === 'quick') return quickSheet();
        if (a === 'eye') { const i = t.previousElementSibling; i.type = i.type === 'password' ? 'text' : 'password'; t.setAttribute('aria-label', i.type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña'); return; }
        if (a === 'limpiar-filtros') { filtro = 'todos'; texto = ''; render(); document.querySelector('#flt')?.focus(); return; }
        if (a === 'filtro') { filtro = t.dataset.v; return render(); }
        if (a === 'temporada') { temporada = t.dataset.v; return render(); }
        if (a === 'tab') { fichaTab = t.dataset.v; return render(); }
        if (a === 'jaula') return go(`#jaulas/${t.dataset.v}`);
        if (a === 'soon') return toast(t.dataset.msg);
        if (a === 'pdf') return toast('En la aplicación, el servidor genera la ficha en PDF lista para imprimir (AC-04).');
        if (a === 'mas') {
            const c = byId(t.dataset.id);
            return sheet('Más acciones', `<div class="sheet-list">
                ${btn(c.estado === 'reservado' ? 'Quitar reserva' : 'Reservar', `data-act="reserva" data-id="${c.id}"`, 'soft big', 'bookmark')}
                ${btn('Ficha en PDF', 'data-act="pdf"', 'soft big', 'file')}
                ${btn('Dar de baja', `data-act="baja" data-id="${c.id}"`, 'danger big', 'exit')}</div>`, btn('Cerrar', 'data-act="close"', 'ghost'));
        }
        if (a === 'umenu') { const m = t.nextElementSibling; const open = m.hidden; document.querySelectorAll('.umenu').forEach(x => { x.hidden = true; }); m.hidden = !open; t.setAttribute('aria-expanded', String(open)); return; }
        if (a === 'top-search') { const f = document.querySelector('.top-search'); f.classList.toggle('open'); if (f.classList.contains('open')) f.querySelector('input').focus(); return; }
        if (a === 'ped') {
            for (const p of document.querySelectorAll('.pcard')) p.classList.toggle('on', p === t);
            const d = document.querySelector('#ped-detail'), c = byId(t.dataset.id);
            d.innerHTML = `<strong>${esc(t.dataset.role.replace('P–', 'Línea P–').replace(/^M–/, 'Línea M–'))}</strong><span>${c ? esc(ring(c)) : 'Sin registro en el criadero'}</span>${c && t.dataset.i !== '0' ? `<a class="btn btn-soft sm" href="#canario/${c.id}">Abrir ficha ›</a>` : ''}`;
            return;
        }
        if (a === 'reserva') { const c = byId(t.dataset.id); c.estado = c.estado === 'reservado' ? 'activo' : 'reservado'; render(); return toast(c.estado === 'reservado' ? 'Canario reservado: sigue en tu lista, pero no puede formar un cruce nuevo.' : 'Reserva quitada.'); }
        if (a === 'baja') {
            const c = byId(t.dataset.id);
            return sheet(`Dar de baja ${ring(c)}`, `<p class="muted">Su ficha y su genealogía se conservan; deja de ocupar su jaula.</p>${field('Fecha de baja', `<input id="fbaja" value="${hoy}">`)}`,
                `${btn('Confirmar baja', `data-act="baja-ok" data-id="${c.id}"`, 'danger')}${btn('Cancelar', 'data-act="close"', 'ghost')}`);
        }
        if (a === 'baja-ok') { const c = byId(t.dataset.id); c.estado = 'baja'; c.baja = sheetRoot.querySelector('#fbaja').value; (state.historial[c.jaula] ??= []).unshift(`${hoy} · Baja de ${c.corr}`); c.jaula = null; closeSheet(); render(); return toast('Baja registrada.'); }
        if (a === 'mover') {
            const c = byId(t.dataset.id);
            const destinos = state.jaulas.filter(j => j !== c.jaula), primero = destinos.find(j => !conflictoJaula(j, [c]));
            return sheet(`Mover ${ring(c)}`, `<p class="muted">Ahora está en ${esc(c.jaula ? cageName(c.jaula) : 'ninguna jaula')}. Elige el destino:</p><div class="cage-pick">${destinos.map(j => { const error = conflictoJaula(j, [c]); return `<label class="cp-cage"><input type="radio" name="dest" value="${j}" ${j === primero ? 'checked' : ''} ${error ? 'disabled' : ''}><span>${ic('cage')}<strong>${esc(cageName(j))}</strong><small>${error ? esc(error) : `${enJaula(j).length} ocupantes`}</small></span></label>`; }).join('')}</div>${field('Fecha del traslado', `<input value="${hoy}">`)}`,
                `${btn('Guardar traslado', `data-act="mover-ok" data-id="${c.id}"`, 'primary')}${btn('Cancelar', 'data-act="close"', 'ghost')}`);
        }
        if (a === 'mover-ok') { const c = byId(t.dataset.id), d = sheetRoot.querySelector('input[name=dest]:checked')?.value; if (!d) return toast('Selecciona una jaula compatible.'); const error = conflictoJaula(d, [c]); if (error) return toast(error); ubicarCanario(c, d); closeSheet(); render(); return toast(`Movido a ${cageName(d)}.`); }
        if (a === 'terminar') {
            return sheet('Terminar cruce', `<p class="muted">Terminar el cruce no mueve los canarios: siguen en su jaula hasta que registres un traslado.</p>${field('Fecha de fin', `<input id="ffin" value="${hoy}">`)}`, `${btn('Guardar fecha y terminar', `data-act="terminar-ok" data-id="${t.dataset.id}"`, 'primary')}${btn('Cancelar', 'data-act="close"', 'ghost')}`);
        }
        if (a === 'terminar-ok') { const x = state.cruces.find(c => c.id === t.dataset.id); x.fin = sheetRoot.querySelector('#ffin').value; closeSheet(); render(); return toast('Cruce terminado.'); }
        if (a === 'nueva-jaula') {
            return sheet('Agregar jaula', field('Número de jaula', '<input id="njaula" inputmode="numeric" placeholder="Ej.: 12">', 'Número único dentro de tu criadero.'), `${btn('Agregar', 'data-act="nueva-jaula-ok"', 'primary')}${btn('Cancelar', 'data-act="close"', 'ghost')}`);
        }
        if (a === 'nueva-jaula-ok') { const v = sheetRoot.querySelector('#njaula').value.trim(); if (!v) return toast('Escribe el número de la jaula.'); if (state.jaulas.includes(v)) return toast(`Ya existe la ${cageName(v)}.`); state.jaulas.push(v); state.jaulas.sort(porJaula); state.historial[v] = []; closeSheet(); render(); return toast(`${cageName(v)} agregada.`); }
        if (a === 'mas-pichon') { anillarN++; return render(); }
        if (a === 'rc-step') { const v = +t.dataset.v; if (v === 2 || v === 3) { const ini = document.querySelector('#rc-inicio'); if (ini) rc.inicio = ini.value; } if (v === 3 && (!rcBird('M')?.codigo || !rcBird('H')?.codigo)) return toast('Elige la variedad del macho y de la hembra para ver el resultado.'); const dir = Math.sign(v - rcStep); rcStep = v; render(); return slideStep(dir); }
        if (a === 'csrc' || a === 'rsrc') {
            const obj = a === 'csrc' ? calc : rc, s = t.dataset.s;
            obj[s].src = t.dataset.v;
            if (t.dataset.v === 'mis') { const c = byId(obj[s].id) ?? vivos().find(x => x.sexo === s); obj[s].id = c.id; if (a === 'csrc') { obj[s].codigo = c.codigo; obj[s].porta = [...c.porta]; } }
            return render();
        }
        if (a === 'rc-save') {
            const pareja = ['M', 'H'].map(s => ({ ...rcBird(s), id: rc[s].src === 'mis' ? rcBird(s)?.id : undefined, sexo: rc[s].src === 'mis' ? rcBird(s)?.sexo : s }));
            if (['M', 'H'].some((s, i) => !pareja[i].corr || pareja[i].sexo !== s || (rc[s].src === 'mis' && pareja[i].estado !== 'activo'))) return toast('Completa la pareja con un macho y una hembra disponibles.');
            const error = conflictoJaula(rc.jaula, pareja); if (error) return toast(error);
            const mk = s => { const p = rc[s]; if (p.src === 'mis') return byId(p.id); if (!p.corr) return null;
                const n = C({ id: `r${Date.now()}${s}`, socio: p.socio, anio: p.anio, corr: p.corr, club: p.club, sexo: s, codigo: p.codigo, porta: [...p.porta], jaula: rc.jaula, origen: 'adquirido', anillado: '' }); state.canarios.push(n); return n; };
            if (cruceActivo(rc.jaula)) return toast(`La ${cageName(rc.jaula)} ya tiene un cruce activo. Elige otra jaula o termina ese cruce.`);
            const m = mk('M'), f = mk('H'); if (!m || !f) return toast('Completa el anillo del macho y de la hembra.');
            ubicarCanario(m, rc.jaula, rc.inicio || hoy); ubicarCanario(f, rc.jaula, rc.inicio || hoy);
            const num = String(state.cruces.filter(x => x.temporada === '2026').length + 1).padStart(2, '0');
            const x = { id: `x${Date.now()}`, num, jaula: rc.jaula, macho: m.id, hembra: f.id, temporada: '2026', inicio: rc.inicio || hoy, fin: null };
            state.cruces.push(x); (state.historial[rc.jaula] ??= []).unshift(`${x.inicio} · Entran ${m.corr} y ${f.corr} · inicio del cruce ${num}`);
            rcStep = 1; go(`#cruce/${x.id}`); return toast(`Cruce ${num} guardado en el libro de crías 2026.`);
        }
    });
    document.addEventListener('change', e => {
        const t = e.target;
        if (t.matches('[data-act=cown]')) { const c = byId(t.value), s = t.dataset.s; Object.assign(calc[s], { id: c.id, codigo: c.codigo, porta: [...c.porta] }); render(); return; }
        if (t.matches('[data-act=rown]')) { rc[t.dataset.s].id = t.value; return refreshRc(); }
        if (t.name === 'rc-jaula') { rc.jaula = t.value; return; }
        if (t.name?.startsWith('calc-')) { calc[t.name.slice(5)].codigo = t.value; return refreshCalc(); }
        if (t.name?.startsWith('rc-')) { rc[t.name.slice(3)].codigo = t.value; return refreshRc(); }
        if (t.name?.startsWith('cp-')) { const s = t.name.slice(3); calc[s].porta = [...document.querySelectorAll(`input[name="cp-${s}"]:checked`)].map(i => i.value); portaCount(t); return refreshCalc(); }
        if (t.name?.startsWith('rcp-')) { const s = t.name.slice(4); rc[s].porta = [...document.querySelectorAll(`input[name="rcp-${s}"]:checked`)].map(i => i.value); portaCount(t); return refreshRc(); }
        if (t.matches('[data-rc-f]')) { rc[t.dataset.rcF][t.dataset.k] = t.value; return; }
    });
    document.addEventListener('input', e => {
        if (e.target.id === 'flt') { texto = e.target.value; const g = document.querySelector('#bird-grid'); if (g) g.innerHTML = birdCards(); }
    });
    document.addEventListener('submit', e => {
        const f = e.target.closest('[data-form]'); if (!f) return; e.preventDefault();
        const k = f.dataset.form;
        if (k === 'login') return go('#inicio');
        if (k === 'registro') { if (!document.querySelector('#consent').checked) return toast('Para crear la cuenta debes aceptar el uso de tus datos.'); go('#inicio'); return toast('¡Cuenta creada! Bienvenido a TuCri.'); }
        if (k === 'recuperar') return go('#recuperar/enviado');
        if (k === 'buscar' || k === 'buscar2') return go(`#buscar/${encodeURIComponent(f.querySelector('input').value)}`);
        if (k === 'perfil') return toast('Cambios guardados.');
        if (k === 'canario') {
            const d = Object.fromEntries(new FormData(f));
            if (!d.corr) return toast('Falta el correlativo del anillo.');
            if (state.canarios.some(c => c.socio === d.socio && c.anio === d.anio && c.corr === d.corr && c.club === d.club)) return toast('Ya existe un canario con ese anillo.');
            const error = conflictoJaula(d.jaula, [{ sexo: d.sexo }]); if (error) return toast(error);
            const c = C({ id: `n${Date.now()}`, socio: d.socio, anio: d.anio, corr: d.corr, club: d.club, sexo: d.sexo, codigo: d.variedad || null, porta: new FormData(f).getAll('porta'), jaula: null, padre: d.padre || null, madre: d.madre || null, origen: d.origen, nac: d.nac, anillado: d.anillado });
            state.canarios.push(c); ubicarCanario(c, d.jaula || null); go(`#canario/${c.id}`); return toast('Canario registrado.');
        }
        if (k === 'anillar') {
            const x = state.cruces.find(c => c.id === f.dataset.id);
            for (let i = 0; i < anillarN; i++) { const corr = f.querySelector(`[name=c${i}]`).value, s = f.querySelector(`[name=s${i}]:checked`).value;
                state.canarios.push(C({ id: `p${Date.now()}${i}`, socio: perfil.socio, anio: '2026', corr, club: perfil.club, sexo: s, codigo: null, jaula: null, padre: x.macho, madre: x.hembra, cruce: x.id, anillado: hoy })); }
            const n = anillarN; anillarN = 2; go(`#cruce/${x.id}`); return toast(`${n} pichones anillados con padres y cruce de origen.`);
        }
    });
    document.addEventListener('keydown', e => {
        const activeSheet = sheetRoot.querySelector('.sheet');
        if (activeSheet && e.key === 'Tab') {
            const items = [...activeSheet.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled)')].filter(x => x.getClientRects().length);
            const first = items[0], last = items.at(-1);
            if (e.shiftKey && (document.activeElement === first || !activeSheet.contains(document.activeElement))) { e.preventDefault(); last?.focus(); }
            else if (!e.shiftKey && (document.activeElement === last || !activeSheet.contains(document.activeElement))) { e.preventDefault(); first?.focus(); }
        }
        const tab = e.target.closest?.('[role="tab"]');
        if (tab && ['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) {
            e.preventDefault();
            const tabs = [...tab.parentElement.querySelectorAll('[role="tab"]')];
            const index = e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : (tabs.indexOf(tab) + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            const value = tabs[index].dataset.v;
            tabs[index].click(); document.querySelector(`[role="tab"][data-v="${value}"]`)?.focus();
        }
        const drawer = document.querySelector('#mobile-menu[open]');
        if (drawer && e.key === 'Tab') {
            const items = [...drawer.querySelectorAll('a[href], button:not(:disabled)')].filter(x => x.getClientRects().length);
            const first = items[0], last = items.at(-1);
            if (e.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) { e.preventDefault(); last?.focus(); }
            else if (!e.shiftKey && (document.activeElement === last || !drawer.contains(document.activeElement))) { e.preventDefault(); first?.focus(); }
        }
        if (e.key === 'Escape' && sheetRoot.innerHTML) closeSheet();
        if (e.key === 'Escape') document.querySelectorAll('.umenu:not([hidden])').forEach(m => { m.hidden = true; const b = m.previousElementSibling; b?.setAttribute('aria-expanded', 'false'); b?.focus(); });
    });

    // ================= Render =================
    const AUTH = new Set(['login', 'registro', 'recuperar', 'sesion', 'mantenimiento', '500', 'sin-conexion']);
    let prevName = '';
    let authPointerAction = false;
    document.addEventListener('pointerdown', e => {
        authPointerAction = !!e.target.closest('.auth');
    }, { capture: true, passive: true });
    document.addEventListener('keydown', () => { authPointerAction = false; }, true);
    function render() {
        document.querySelector('#mobile-menu[open]')?.close();
        closeSheet();
        const { name, arg } = route();
        const animateAccess = AUTH.has(name) && AUTH.has(prevName) && authPointerAction;
        authPointerAction = false;
        if (name === 'registro-cria' && prevName !== 'registro-cria') rcStep = 1;
        prevName = name;
        const args = location.hash.slice(1).split('/').slice(1);
        const view = V[name] ?? V['404'];
        app.innerHTML = view(...args.length ? args : [arg]);
        document.title = `${{ inicio: 'Inicio', canarios: 'Mis canarios', canario: 'Ficha del canario', jaulas: 'Jaulas', crias: 'Libro de crías', cruce: 'Ficha de cruce', calculadora: 'Calculadora genética', 'registro-cria': 'Registrar pareja', buscar: 'Buscar', perfil: 'Mi perfil', 'canario-nuevo': 'Registrar canario', anillar: 'Anillar pichones', login: 'Iniciar sesión', registro: 'Crear cuenta', recuperar: 'Recuperar acceso', 404: 'Página no encontrada', 403: 'Sin acceso', sesion: 'Sesión expirada', mantenimiento: 'En mantenimiento', 500: 'Algo salió mal', 'sin-conexion': 'Sin conexión' }[name] ?? 'TuCri'} · TuCri`;
        document.body.classList.toggle('is-auth', AUTH.has(name));
        if (animateAccess && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
            app.querySelector('.auth-card')?.animate([
                { opacity: 0, transform: 'translateY(8px)' },
                { opacity: 1, transform: 'translateY(0)' },
            ], { duration: 200, easing: getComputedStyle(document.documentElement).getPropertyValue('--ease-ui').trim() || 'ease-out' });
        }
        wirePickers();
        const dr = document.querySelector('#mobile-menu');
        dragToClose(dr, dr, 'x', () => closeDrawer(dr));
        const detail = document.querySelector('#cage-detail');
        if (detail) detail.scrollIntoView({ behavior: 'smooth', block: 'start' }); else window.scrollTo(0, 0);
        const ped = document.querySelector('.ped-scroll');
        if (ped) requestAnimationFrame(() => {
            const scrolls = ped.scrollWidth > ped.clientWidth + 2;
            ped.parentElement.classList.toggle('scrolls', scrolls);
            const self = ped.querySelector('.pcard.self');
            if (scrolls && self) ped.scrollLeft = Math.max(0, self.offsetLeft - 12);
        });
    }
    window.TuCriadero = { calcular, varName };
    window.addEventListener('hashchange', render);
    window.addEventListener('offline', () => go('#sin-conexion'));
    matchMedia('(max-width: 899px)').addEventListener('change', e => { if (!e.matches) document.querySelector('#mobile-menu[open]')?.close(); });
    render();
})();
