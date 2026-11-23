(() => {
    'use strict';
    const screens = window.MOCKUP_SCREENS;
    // Recuperar acceso: se arma sobre el diseño de acceso (no existe en el ZIP de Figma).
    {
        const login = screens.find(s => s.key === 'login');
        const at = screens.findIndex(s => s.key === 'login-desactivado') + 1;
        screens.splice(at, 0,
            { key: 'recuperar', label: 'Recuperar acceso', desktop: login.desktop, mobile: login.mobile, custom: 'recuperar' },
            { key: 'recuperar-enviado', label: 'Recuperar acceso: solicitud enviada', desktop: login.desktop, mobile: login.mobile, custom: 'recuperar-enviado' });
    }
    const sceneSelect = document.querySelector('#scene');
    const deviceSelect = document.querySelector('#device');
    const stage = document.querySelector('#stage');
    const wrap = document.querySelector('#stage-wrap');
    const notes = document.querySelector('#notes');
    const values = new Map();
    document.body.classList.toggle('presentation', new URLSearchParams(location.search).has('presentar'));
    let current, currentMode, toastTimer;
    const route = () => location.hash.slice(1).split('?')[0] || 'login';
    const MOBILE_MAX = 860;
    const mode = () => deviceSelect.value === 'auto' ? (innerWidth <= MOBILE_MAX ? 'mobile' : 'desktop') : deviceSelect.value;
    const clean = t => t.replace(/[＋+›⌕]/g, '').replace(/\s+/g, ' ').trim();
    function go(key, message) {
        if (route() === key) render();
        else location.hash = key;
        if (message) toast(message);
    }
    function toast(text) {
        const el = document.querySelector('#toast');
        el.textContent = text;
        el.classList.add('visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('visible'), 4500);
    }
    function saved(key) { go(key, '¡Listo! Así se vería guardado. (En esta maqueta los datos no se guardan de verdad.)'); }
    const linkRoutes = {
        'Inicio': 'inicio', 'Buscar': 'buscar', 'Canarios': 'canarios', 'Aves': 'canarios',
        'Mis canarios': 'canarios', 'Adquirido': 'adquirido', 'Nacido en criadero': 'canario-nuevo', 'Nacido en el criadero': 'canario-nuevo', 'Jaulas': 'jaulas', 'Libro de crías': 'cruces', 'Cría': 'cruces',
        'Mi perfil': 'perfil', 'Cerrar sesión': 'login', 'Crear cuenta': 'registro',
        'Iniciar sesión': 'inicio', 'Ingresar': 'inicio', 'Registrar canario': 'canario-nuevo',
        'Nuevo cruce': 'cruce-nuevo', 'Registrar cruce': 'cruce-nuevo', 'Registro de cría 2026': 'cruce-nuevo',
        'Editar ficha': 'canario-nuevo', 'Dar de baja': 'dar-baja',
        'Asignar a jaula': 'traslado', 'Asignar o trasladar': 'traslado',
        'Mover canario': 'traslado', 'Registrar traslado': 'traslado',
        'Reservar': 'reservado', 'Quitar reserva': 'ficha',
        'Exportar ficha a PDF': 'pdf', 'Exportar PDF': 'pdf',
        'Ver posible resultado': 'resultado', 'Posible resultado': 'resultado',
        'Anillar pichones': 'anillar', 'Cuentas': 'cuentas', 'Catálogo de variedades': 'catalogo',
        'Factores': 'factores', 'Administrar factores': 'factores',
        'Volver al inicio': 'inicio', 'Volver a buscar': 'buscar',
        'Abrir ficha del canario': 'ficha', 'Abrir ficha existente': 'ficha',
        'Volver a la ficha': 'ficha', 'Abrir cruce 01': 'cruce',
        'Revisar campos': 'canario-nuevo', 'Corregir datos': 'canario-nuevo',
        'Revisar pichones': 'anillar', 'Corregir pichones': 'anillar',
        'Volver a elegir': 'cruce-nuevo', 'Corregir selección': 'cruce-nuevo',
    };
    function cancelRoute() {
        if (/cuenta|desactivar/.test(current.key)) return 'cuentas';
        if (/resultado|sin-regla|sin-variedad|aproximado/.test(current.key)) return 'cruce-nuevo';
        if (/cruce/.test(current.key)) return 'cruces';
        if (/anillar/.test(current.key)) return 'cruce';
        if (/variedad|catalogo|factores/.test(current.key)) return 'catalogo';
        return 'ficha';
    }
    function action(text, node, parent) {
        const t = clean(text);
        if (/Ya tienes cuenta/.test(t)) return () => go('login');
        if (/Crear cuenta/.test(t) && current.key.startsWith('registro')) return () => {
            const check = stage.querySelector('input[type=checkbox]');
            if (check && !check.checked) go('registro-error', 'Para crear la cuenta debes aceptar el uso de tus datos.');
            else saved('inicio');
        };
        if (t === 'Guardar canario') return () => saved('ficha');
        if (/Confirmar baja/.test(t)) return () => saved('baja');
        if (/Guardar traslado/.test(t)) return () => saved('ficha');
        if (t === 'Guardar cambios') return () => saved(current.key === 'perfil' ? 'perfil' : /variedad|catalogo/.test(current.key) ? 'catalogo' : 'ficha');
        if (t === 'Guardar factor') return () => saved('factores');
        if (/Guardar pichones/.test(t)) return () => saved('cruce');
        if (/Guardar fecha y terminar|^Terminar cruce$/.test(t)) return () => saved('cruces');
        if (/^Confirmar(?: y guardar cruce)?$/.test(t)) return () => saved('cruce');
        if (t === 'Registrar jaula') return () => {
            const input = [...stage.querySelectorAll('input')].find(e => /jaula/i.test(e.getAttribute('aria-label')));
            if (input && ['2', '5', '8', '10'].includes(input.value.trim())) go('jaula-error');
            else saved('jaulas');
        };
        if (t === 'Cerrar cuenta') return () => current.key === 'cerrar-cuenta' ? saved('cuentas') : go('cerrar-cuenta');
        if (t === 'Desactivar') return () => current.key === 'desactivar' ? saved('cuentas') : go('desactivar');
        if (t === 'Activar') return () => saved('cuentas');
        if (/Agregar otro pichón/.test(t)) return () => toast('Ejemplo de envío múltiple: la maqueta ya muestra dos pichones editables.');
        if (/Agregar variedad/.test(t)) return () => toast('El alta de variedades está pendiente de definición con el PM. La edición se puede revisar desde el catálogo.');
        if (t === 'Cancelar' || t === 'Volver' || t === 'Volver y corregir datos') return () => go(cancelRoute());
        if (t === 'Editar') return () => go(/catalogo|variedad/.test(current.key) ? 'variedad' : 'canario-nuevo');
        if (/Intentar de nuevo|Reintentar|Volver a intentar/.test(t)) return () => go('inicio');
        if (t === '☰') return () => {
            const menu = renderNode(window.MOCKUP_MENU);
            menu.dataset.menu = 'true'; menu.classList.add('menu-overlay'); stage.append(menu);
        };
        if (t === '×') return () => stage.querySelector('[data-menu]')?.remove();
        if (linkRoutes[t]) return () => go(linkRoutes[t]);
        if (/Abrir|[›]/.test(text) && /Cruce|cruce/.test(text)) return () => go('cruce');
        if (/Abrir ficha/.test(text)) return () => go(/Baja/.test(text) ? 'baja' : 'ficha');
        if (/^Cruce \d/.test(t)) return () => go('cruce');
        if (/^Jaula (?:\d|grande)/.test(t) && text.includes('›')) return () => go('jaula');
        if (/^D\.\d+/.test(t) && text.includes('›')) return () => go('variedad');
        if (/^18 ·|^41 ·/.test(t) && /canarios|buscar|ficha|reservado|jaula|cruce/.test(current.key)) {
            const row = parent?.children?.map(c => c.text).join(' ') ?? '';
            return () => go(/41 ·/.test(t) || /Baja/.test(row) ? 'baja' : /083/.test(t) ? 'reservado' : 'ficha');
        }
        return null;
    }
    function isControl(n, parent) {
        if (/^(Nacido en (el )?criadero|Adquirido)$/.test(n.name)) return false;
        if (n.type !== 'FRAME' || n.style.height < 30 || n.style.height > 65) return false;
        if (n.name.startsWith('Campo ') && !n.children.length) return true;
        const texts = n.children.filter(c => c.type === 'TEXT');
        if (texts.length !== 1 || n.children.some(c => c.type === 'FRAME')) return false;
        if (/Botón|Guardar|Confirmar|Cancelar|Volver|Registrar|Crear cuenta|sesión|consentimiento/i.test(n.name)) return false;
        if (/^Campo |^Entrada|^correo@|^ana@|^••|^dd \/|^Sin completar/.test(n.name)) return true;
        return !!n.style.borderColor && /Socio|Año|Correlativo|Club|Correo|Contraseña|Fecha|Nacimiento|Sexo|Variedad|Buscar por|Texto a buscar|Número de jaula|Destino|Jaula de destino|disponible|herencia|Portadores|Procedencia|Origen|Nombre|Temporada/i.test(parent?.name ?? '');
    }
    function control(n, parent, el) {
        const label = (n.name.startsWith('Campo ') ? n.name : parent?.name ?? n.name).replace(/^Campo /, '').replace(/\s*\*$/, '');
        const text = n.children.find(c => c.type === 'TEXT')?.text ?? '';
        let options = null;
        if (/^Buscar por\s*\*?$/.test(label)) options = ['Anillo', 'Variedad', 'Jaula'];
        else if (/Sexo|sexo restringido|Sexo restringido/.test(label)) options = ['Sin determinar', 'Macho', 'Hembra'];
        else if (/Origen/.test(label)) options = ['Nacido en el criadero', 'Adquirido'];
        else if (/herencia/.test(label)) options = ['Ligado al sexo', 'Recesivo', 'Dominante'];
        else if (/Jaula|Destino/.test(label) && !/Número/.test(label)) options = ['Jaula 2', 'Jaula 5', 'Jaula 8', 'Jaula 10', 'Jaula grande'];
        else if (/Motivo/.test(label)) options = ['Venta', 'Muerte', 'Otra salida'];
        else if (/Macho disponible|Hembra disponible/.test(label)) options = [text.replace(/\s*[⌄▾]$/, '')];
        const input = document.createElement(options ? 'select' : 'input');
        input.className = 'design-control';
        const original = n.children.find(c => c.type === 'TEXT');
        input.style.fontSize = `${Math.max(16, original?.style.fontSize ?? 16)}px`;
        input.style.fontWeight = original?.style.fontWeight ?? 400;
        input.style.paddingLeft = `${original?.style.left ?? 13}px`;
        input.setAttribute('aria-label', label);
        const key = `${current.key}:${n.id}`;
        if (options) {
            for (const value of options) { const opt = document.createElement('option'); opt.textContent = value; input.append(opt); }
            const selected = text.replace(/\s*[⌄▾]$/, '');
            if (options.includes(selected)) input.value = selected;
        } else {
            input.type = /Contraseña/.test(label) ? 'password' : /Correo/.test(label) ? 'email' : 'text';
            input.autocomplete = 'off';
            input.placeholder = text.replace(/\s*[⌕⌄▾]$/, '');
            input.value = /correo@|ana@|dd\s*\/|Sin completar|Sin registrar|••/.test(text) ? '' : text;
            if (/Contraseña/.test(label)) input.placeholder = 'Contraseña de ejemplo';
            if (/Correo/.test(label)) input.placeholder = 'correo@ejemplo.cl';
        }
        if (values.has(key)) input.value = values.get(key);
        if (input.type === 'password') {
            const eye = document.createElement('button');
            eye.type = 'button'; eye.className = 'pwd-toggle'; eye.innerHTML = EYE;
            eye.setAttribute('aria-label', 'Mostrar contraseña');
            eye.addEventListener('click', e => {
                e.stopPropagation();
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eye.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
            el.append(eye);
        }
        input.addEventListener('change', () => {
            values.set(key, input.value);
            if (/Origen/.test(label)) go(input.value === 'Adquirido' ? 'adquirido' : 'canario-nuevo');
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); go(current.key === 'inicio' ? 'buscar' : current.key === 'login' ? 'inicio' : current.key); }
        });
        el.append(input);
    }
    // Iconos de trazo para el menú (no forman parte del ZIP; solo presentación).
    const ICONS = {
        home: 'M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
        search: 'M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zM20.5 20.5 16 16',
        bird: 'M15.5 5.5a3.5 3.5 0 0 0-6.6 1.6L9 9c-3 .5-5.5 2.6-6 6 2.5 1.3 6 1.5 8.5.5L13 19h3l-1.6-3.6C17 14 18.5 11.5 18.5 9l2.5-1-2.6-1.5a3.5 3.5 0 0 0-2.9-1zM15.5 7.5h.01',
        cage: 'M12 2v2M5 10a7 6 0 0 1 14 0v10H5zM9 10v10M12 10v10M15 10v10M3.5 20h17',
        book: 'M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5zM4 20.5V5.5M20 18v3H6.5M8.5 7.5h7',
        user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4.5 21a7.5 7.5 0 0 1 15 0',
        logout: 'M14 4h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-5M10 16.5 5.5 12 10 7.5M5.5 12H15',
        users: 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2.5 21a6.5 6.5 0 0 1 13 0M16 3.5a4 4 0 0 1 0 7.5M21.5 21a6.5 6.5 0 0 0-3.5-5.8',
        list: 'M9 6h12M9 12h12M9 18h12M4 6h.01M4 12h.01M4 18h.01',
        dna: 'M7 3c0 6 10 6 10 9s-10 3-10 9M17 3c0 6-10 6-10 9s10 3 10 9M8.5 7h7M8.5 17h7',
        bookmark: 'M6 3h12v18l-6-4-6 4z',
        file: 'M14 3H6v18h12V7zM14 3v4h4M9 13h6M9 17h6',
        exit: 'M18 6 6 18M6 6l12 12',
        edit: 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
    };
    const NAV_ICON = {
        'Inicio': 'home', 'Buscar': 'search', 'Canarios': 'bird', 'Mis canarios': 'bird', 'Aves': 'bird',
        'Jaulas': 'cage', 'Libro de crías': 'book', 'Cría': 'book', 'Crías': 'book', 'Mi perfil': 'user', 'Cerrar sesión': 'logout',
        'Cuentas': 'users', 'Catálogo de variedades': 'list', 'Catálogo': 'list', 'Factores': 'dna',
    };
    function icon(name) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('aria-hidden', 'true'); svg.classList.add('ui-icon');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', ICONS[name]); svg.append(path);
        return svg;
    }
    function logo(size) {
        const img = document.createElement('img');
        img.src = 'assets/logo.png'; img.alt = ''; img.className = 'brand-logo';
        img.style.width = img.style.height = `${size}px`;
        return img;
    }
    // Fondo verde menta del diseño, suavizado para la presentación al cliente.
    const SOFTEN = {
        'rgba(177,220,188,1)': 'var(--page)', 'rgba(151,187,172,1)': 'var(--page)',
        'rgba(228,241,235,1)': 'var(--soft)', 'rgba(236,245,239,1)': 'var(--soft)',
        'rgba(255,243,198,1)': 'var(--accent-soft)', 'rgba(255,242,197,1)': 'var(--accent-soft)',
        'rgba(21,93,80,1)': 'var(--brand)', 'rgba(43,99,64,1)': 'var(--brand)',
    };
    const INK = { 'rgba(25,59,53,1)': 'var(--ink)', 'rgba(64,94,85,1)': 'var(--muted)' };
    const VISUAL = ['color', 'borderRadius', 'borderColor', 'borderWidth', 'borderStyle', 'opacity', 'fontSize',
        'fontWeight', 'fontStyle', 'textAlign', 'textDecoration', 'letterSpacing', 'lineHeight'];
    const px = v => `${v}px`;
    // Escala de lectura: nada por debajo de 14 px y el texto de 14–15 px sube un punto.
    const readable = fs => fs < 14 ? 14 : fs < 16 ? fs + 1 : fs;
    function applyStyles(el, style) {
        if (style.fontSize) style = { ...style, fontSize: readable(style.fontSize) };
        if (style.background) el.style.background = SOFTEN[style.background] ?? style.background;
        if (style.background === 'rgba(228,241,235,1)') el.dataset.panel = '1';
        if (style.color && INK[style.color]) style = { ...style, color: INK[style.color] };
        for (const key of VISUAL) {
            if (style[key] === undefined || style[key] === 0 && key === 'borderRadius') continue;
            const value = style[key];
            el.style[key] = typeof value === 'number' && !['fontWeight', 'opacity'].includes(key) ? px(value) : value;
        }
        if (style.borderColor && !style.borderStyle) el.style.borderStyle = 'solid';
    }
    // Estados del canario, del cruce y de la cuenta, mostrados como insignias.
    const STATUS = /(^|[\s·(:])(Activo|Activa|Reservado|Dado de baja|Baja|Terminado|Desactivada|Cerrada)(?=$|[\s·,.):])/g;
    const STATUS_CLASS = { Activo: 'ok', Activa: 'ok', Reservado: 'warn', Terminado: 'muted', 'Dado de baja': 'off', Baja: 'off', Desactivada: 'warn', Cerrada: 'off' };
    // Énfasis por tipo de dato: anillo, código de variedad, fecha y estado se distinguen del texto corriente.
    const TOKEN = /(\b\d{1,3} · \d{4} · \d{2,3}\b)|(\bD\.\d{3}\b)|(\b\d{2}\/\d{2}\/\d{4}\b)|((?<=^|[\s·(:])(?:Activo|Activa|Reservado|Dado de baja|Baja|Terminado|Desactivada|Cerrada)(?=$|[\s·,.):]))/g;
    function decorate(target, text) {
        let last = 0;
        for (const m of text.matchAll(TOKEN)) {
            target.append(text.slice(last, m.index));
            const span = document.createElement('span');
            if (m[1]) span.className = 'ring';
            else if (m[2]) span.className = 'code';
            else if (m[3]) span.className = 'date';
            else span.className = `status status-${STATUS_CLASS[m[4]]}`;
            span.textContent = m[0];
            target.append(span);
            last = m.index + m[0].length;
        }
        target.append(text.slice(last));
    }
    // Varias líneas «Etiqueta: valor» se muestran como ficha de datos: etiqueta pequeña y valor destacado.
    const KV_LINE = /^([A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÑáéíóúñü ]{1,24}): (.+)$/;
    function fillText(el, n) {
        const text = n.text;
        // Cuando el anillo es el título del canario, se rotula «Anillo» para que se sepa qué número es.
        if (!n.note && n.style.fontSize >= 15 && /^\d{1,3} · \d{4} · \d{2,3}\b/.test(text)) {
            const tag = document.createElement('span'); tag.className = 'ring-tag'; tag.textContent = 'Anillo';
            el.append(tag);
        }
        if (n.style.fontSize > 20 || n.note) { el.append(text); return; }
        const lines = text.split('\n');
        // Bloque «Título» + varias líneas (ocupantes, historial): título pequeño y una fila por registro.
        if (lines.length >= 3 && /^[A-ZÁÉÍÓÚÑ][^:·\d\n]{2,30}$/.test(lines[0].trim())) {
            el.classList.add('list-block');
            const h = document.createElement('span'); h.className = 'list-title'; h.textContent = lines[0];
            el.append(h);
            for (const line of lines.slice(1)) {
                if (!line.trim()) continue;
                const r = document.createElement('span'); r.className = 'list-row'; decorate(r, line); el.append(r);
            }
            return;
        }
        const labelled = lines.filter(l => KV_LINE.test(l)).length;
        if (lines.length >= 2 && labelled >= 2 && labelled >= lines.filter(l => l.trim()).length - 1) {
            el.classList.add('kv');
            for (const line of lines) {
                if (!line.trim()) continue;
                const m = KV_LINE.exec(line);
                if (m) {
                    const k = document.createElement('span'); k.className = 'kv-label'; k.textContent = m[1];
                    const v = document.createElement('span'); v.className = 'kv-value'; decorate(v, m[2]);
                    el.append(k, v);
                } else {
                    const v = document.createElement('span'); v.className = 'kv-value kv-full'; decorate(v, line);
                    el.append(v);
                }
            }
            return;
        }
        decorate(el, text);
    }
    function navKey() {
        const k = current.key;
        if (k === 'inicio') return 'Inicio';
        if (/jaula/.test(k)) return 'Jaulas';
        if (/cruce|resultado|sin-|aproximado|anillar/.test(k)) return 'Crías';
        if (/perfil/.test(k)) return '';
        return 'Canarios';
    }
    function bottomNav() {
        const nav = document.createElement('nav');
        nav.className = 'bottom-nav'; nav.setAttribute('aria-label', 'Navegación principal');
        const active = navKey();
        const items = [['Inicio', 'inicio'], ['Canarios', 'canarios'], null, ['Jaulas', 'jaulas'], ['Crías', 'cruces']];
        for (const item of items) {
            if (!item) { nav.append(fabButton()); continue; }
            const [title, key] = item;
            const a = document.createElement('a');
            a.href = `#${key}`; a.append(icon(NAV_ICON[title]), title);
            if (title === active) { a.classList.add('active'); a.setAttribute('aria-current', 'page'); }
            nav.append(a);
        }
        return nav;
    }
    const isBottomBar = n => /^(Barra inferior|Navegación inferior)$/.test(n.name)
        && JSON.stringify(n).includes('Aves');
    // Agrupa en filas los hijos de un marco sin Auto Layout, según su posición en el ZIP.
    function rowsOf(children) {
        const sorted = [...children].sort((a, b) => a.style.top - b.style.top);
        const rows = [];
        for (const c of sorted) {
            const row = rows.find(r => c.style.top < r.bottom - 4 && c.style.top + c.style.height > r.top + 4);
            if (row) { row.items.push(c); row.bottom = Math.max(row.bottom, c.style.top + c.style.height); row.top = Math.min(row.top, c.style.top); }
            else rows.push({ items: [c], top: c.style.top, bottom: c.style.top + c.style.height });
        }
        rows.forEach(r => r.items.sort((a, b) => a.style.left - b.style.left));
        return rows;
    }
    // Ubica el elemento según el contenedor: flujo flexible (Auto Layout) o posición absoluta.
    function place(el, n, ctx) {
        const s = n.style;
        if (ctx.flow === 'root') { el.classList.add('screen-root'); return; }
        if (!ctx.flow) {
            Object.assign(el.style, { position: 'absolute', left: px(s.left), top: px(s.top), width: px(s.width), height: px(s.height) });
            return;
        }
        el.style.position = 'relative';
        if (ctx.flow === 'column') {
            el.style.flexShrink = '0';
            if (n.stretch || s.width >= ctx.innerW * 0.9) el.style.alignSelf = 'stretch';
            else {
                el.style.width = px(s.width); el.style.maxWidth = '100%';
                if (ctx.center) el.style.alignSelf = 'center';
            }
            if (ctx.grow) el.style.flexGrow = '1';
        } else if (ctx.flow === 'row') {
            const grow = s.width >= ctx.innerW * 0.5 && n.type === 'FRAME' ? 1 : 0;
            el.style.flex = `${grow} 1 ${px(s.width)}`;
            el.style.minWidth = n.type === 'TEXT' || !(s.background || s.borderColor) ? '0' : px(Math.min(s.width, 96));
            if (n.stretch || (ctx.innerH > 300 && s.height >= ctx.innerH * 0.95)) el.style.alignSelf = 'stretch';
        } else if (ctx.flow === 'leaf') {
            el.style.flex = '1 1 auto'; el.style.minWidth = '0';
        }
    }
    const EYE = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    const LOCK = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';
    const ARROW = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    // Jerarquía de acciones: un principal (Editar), las demás secundarias y las delicadas en rojo, al final.
    const DANGER = /^(Dar de baja|Desactivar|Cerrar cuenta)$/;
    function setLook(b, primary) {
        b.classList.toggle('btn-primary', primary); b.classList.toggle('btn-secondary', !primary);
        b.style.background = primary ? 'var(--brand)' : '#fff';
        b.style.borderColor = primary ? 'var(--brand)' : '#b9d3c3';
        for (const t of b.querySelectorAll('.design-text')) t.style.color = primary ? '#fff' : 'var(--brand)';
    }
    function tidyActions() {
        for (const group of stage.querySelectorAll('.design-node, .action-grid')) {
            const btns = [...group.children].filter(c => c.classList.contains('btn-primary') || c.classList.contains('btn-secondary'));
            if (btns.length < 3) continue;
            const edit = btns.find(b => /^Editar( ficha)?$/.test(b.getAttribute('aria-label')));
            if (edit) for (const b of btns) setLook(b, b === edit);
            for (const b of btns) if (DANGER.test(b.getAttribute('aria-label'))) {
                setLook(b, false); b.classList.add('btn-danger'); group.append(b);
            }
            if (group.classList.contains('action-grid')) {
                for (const b of group.children) b.classList.remove('wide');
                if (group.children.length % 2) group.lastElementChild.classList.add('wide');
            }
        }
    }
    // Filas de tabla y tarjetas con enlace: se puede tocar toda la fila o la tarjeta.
    function wholeRowLinks() {
        const rows = [...stage.querySelectorAll('[data-name="Tabla"] > .design-node')];
        const cards = [...stage.querySelectorAll('.card')].filter(c => c.querySelectorAll('.hotspot').length === 1 && /›/.test(c.querySelector('.hotspot').textContent) && !c.querySelector('.btn-primary, .btn-secondary, input, select'));
        for (const row of [...rows, ...cards]) {
            const link = row.querySelector('.hotspot');
            if (!link || row.classList.contains('hotspot')) continue;
            row.classList.add('row-link');
            row.addEventListener('click', e => { if (!e.target.closest('.hotspot, button, a, input, select')) link.click(); });
        }
    }
    // Textos de especificación que no ayudan al criador en esta pantalla.
    const SPEC_TEXT = [/^Solo se muestran parentescos acreditados/, /^Padres → abuelos → bisabuelos\. Los anillos subrayados/];
    function hideSpecText() {
        for (const t of stage.querySelectorAll('.design-text')) {
            const txt = t.textContent.trim();
            if (SPEC_TEXT.some(re => re.test(txt)) || (current.key === 'ficha' && /^La ficha y los vínculos familiares se conservan/.test(txt))) {
                const box = t.parentElement?.children.length === 1 && t.parentElement.dataset.name !== 'Área principal' ? t.parentElement : t;
                box.hidden = true;
            }
        }
    }
    // Ficha del canario: cabecera y acciones siempre visibles; el resto en pestañas (revelación progresiva).
    const TABS = ['Familia', 'Genética', 'Cruces'];
    function tabOf(el) {
        const t = el.textContent.trim();
        if (el.classList.contains('pedigree-wrap') || /^(Árbol|Familia|Hermanos|Medios hermanos|Crías)/.test(t)) return 'Familia';
        if (/^(Factores que manifiesta|Portadores|Indicado por el criador|Deducido por el sistema)/.test(t)) return 'Genética';
        if (/^Cruces en que participó/.test(t)) return 'Cruces';
        return null;
    }
    function fichaTabs() {
        if (!/^(ficha|reservado|baja)$/.test(current.key)) return;
        const box = stage.querySelector('[data-name="Área principal"], [data-name^="Contenido de "]');
        if (!box) return;
        const kids = [...box.children].filter(c => !c.hidden);
        const first = kids.findIndex(tabOf);
        if (first < 0) return;
        const panels = new Map(TABS.map(t => [t, []]));
        // Cada elemento va a la pestaña de su título; las tarjetas sin título siguen al título anterior.
        let currentTab = null;
        for (const el of kids.slice(first)) { currentTab = tabOf(el) ?? currentTab; panels.get(currentTab).push(el); }
        const used = TABS.filter(t => panels.get(t).length);
        if (used.length < 2) return;
        const bar = document.createElement('div'); bar.className = 'tabs'; bar.setAttribute('role', 'tablist');
        const holder = document.createElement('div'); holder.className = 'tab-panels';
        kids[first].before(bar);
        bar.after(holder);
        const views = used.map((name, i) => {
            const tab = document.createElement('button');
            tab.type = 'button'; tab.className = 'tab'; tab.setAttribute('role', 'tab'); tab.textContent = name;
            tab.id = `tab-${i}`; tab.setAttribute('aria-controls', `panel-${i}`);
            const panel = document.createElement('div');
            panel.className = 'tab-panel'; panel.id = `panel-${i}`; panel.setAttribute('role', 'tabpanel'); panel.setAttribute('aria-labelledby', tab.id);
            for (const el of panels.get(name)) { el.style.marginTop = ''; panel.append(el); }
            bar.append(tab); holder.append(panel);
            return [tab, panel];
        });
        const show = k => views.forEach(([tab, panel], i) => {
            tab.setAttribute('aria-selected', String(i === k)); tab.tabIndex = i === k ? 0 : -1; panel.hidden = i !== k;
        });
        views.forEach(([tab], i) => {
            tab.addEventListener('click', () => show(i));
            tab.addEventListener('keydown', e => {
                const d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
                if (d) { const k = (i + d + views.length) % views.length; show(k); views[k][0].focus(); }
            });
        });
        show(0);
        requestAnimationFrame(resize);
    }
    // Formularios: etiqueta arriba y campo debajo, sin una caja por cada campo (menos ruido visual).
    function flattenFields() {
        for (const box of stage.querySelectorAll('.design-node')) {
            if (box.dataset.panel !== '1' || box.children.length > 3 || box.classList.contains('card')) continue;
            const control = [...box.children].some(c => c.querySelector(':scope > .design-control'));
            const label = box.firstElementChild?.classList.contains('design-text');
            if (control && label) box.classList.add('field-group');
        }
    }
    // Formulario de canario: campos agrupados en tarjetas por tema, con los campos cortos lado a lado.
    const BIRD_SECTIONS = [
        ['Identificación del anillo', '', /^(Identificación del anillo|Socio|Año|Correlativo|Club)/],
        ['Datos del canario', '', /^(Sexo|Variedad|Origen|Procedencia|Opciones de sexo)/],
        ['Padres', 'opcional', /^(Padre|Madre|Progenitores)/],
        ['Fechas', 'opcional', /^(Fecha de anillado|Fecha de nacimiento|Fechas)/],
        ['Genética', 'opcional', /^(Portadores|La herencia|Los factores manifestados)/],
    ];
    const HALF = /^(Socio|Año|Correlativo|Club|Fecha de anillado|Fecha de nacimiento)/;
    function birdFormSections() {
        if (!/^(canario-nuevo|adquirido|canario-error)$/.test(current.key)) return;
        const box = stage.querySelector('[data-name="Área principal"], [data-name^="Contenido de "]');
        if (!box) return;
        const groups = BIRD_SECTIONS.map(() => []);
        let cur = -1;
        for (const el of [...box.children]) {
            if (el.hidden || el.classList.contains('req-legend')) continue;
            // Se detiene en las acciones finales del formulario (Guardar / Cancelar).
            const isFinal = b => /^(Guardar|Cancelar|Confirmar|Volver)/.test(b.getAttribute('aria-label') ?? '');
            if ((el.matches('.btn-primary, .btn-secondary') && isFinal(el)) || [...el.children].some(c => c.matches('.btn-primary, .btn-secondary') && isFinal(c))) break;
            const text = el.textContent.trim();
            const k = BIRD_SECTIONS.findIndex(([, , re]) => re.test(text));
            if (k >= 0) cur = k;
            if (cur < 0) continue;
            if (el.classList.contains('card') && k >= 0) { groups[cur].push(el); continue; }
            groups[cur].push(el);
        }
        groups.forEach((els, k) => {
            if (!els.length) return;
            // Si la sección ya es una tarjeta del diseño (escritorio), se deja como está.
            if (els.length === 1 && els[0].classList.contains('card')) return;
            const card = document.createElement('section'); card.className = 'form-card';
            const h = document.createElement('h3'); h.className = 'form-card-title'; h.textContent = BIRD_SECTIONS[k][0];
            if (BIRD_SECTIONS[k][1]) { const o = document.createElement('span'); o.textContent = BIRD_SECTIONS[k][1]; h.append(o); }
            const grid = document.createElement('div'); grid.className = 'form-grid';
            els[0].before(card);
            card.append(h, grid);
            for (const el of els) {
                if (el.textContent.trim() === 'Identificación del anillo' && el.classList.contains('design-text')) { el.hidden = true; continue; }
                el.style.marginTop = ''; el.style.width = '';
                if (el.dataset.name === 'Origen' && !el.classList.contains('field-group')) {
                    const label = document.createElement('div'); label.className = 'form-label'; label.textContent = 'Origen';
                    grid.append(label); el.classList.add('choice-row');
                }
                if (el.classList.contains('field-group') && HALF.test(el.textContent.trim())) el.classList.add('half');
                grid.append(el);
            }
        });
        const legend = box.querySelector(':scope > .req-legend');
        if (legend) box.querySelector(':scope > .form-card, :scope > .card')?.before(legend);
    }
    // Jaulas: en móvil, fichas en cuadrícula con el número destacado; en escritorio, tabla y detalle lado a lado.
    function cageLayout() {
        if (!/^(jaulas|jaula|jaula-error)$/.test(current.key)) return;
        const cards = [...stage.querySelectorAll('.card')].filter(c => /^Jaula (\d+|grande)/.test(c.textContent.trim()) && c.textContent.length < 90);
        if (currentMode === 'mobile' && cards.length > 1) {
            const grid = document.createElement('div'); grid.className = 'cage-grid';
            cards[0].before(grid);
            for (const c of cards) {
                const m = /^Jaula (\d+|grande)/.exec(c.textContent.trim());
                const badge = document.createElement('span'); badge.className = 'cage-num'; badge.textContent = m[1] === 'grande' ? 'G' : m[1];
                c.prepend(badge);
                c.classList.add('cage-tile');
                if (/Cruce activo/.test(c.textContent)) c.classList.add('has-cross');
                c.style.marginTop = '';
                grid.append(c);
            }
        }
        if (currentMode === 'desktop') {
            const table = stage.querySelector('[data-name="Tabla"]');
            const detail = stage.querySelector('[data-name^="Ficha de Jaula"]');
            if (table && detail) {
                const split = document.createElement('div'); split.className = 'master-detail';
                table.before(split); split.append(table, detail);
                for (const row of table.children) if (/^Jaula 2 ›/.test(row.textContent.trim())) row.classList.add('row-selected');
            }
        }
    }
    // Listas de tarjetas en móvil: una columna en teléfono y dos cuando hay espacio.
    function cardLists() {
        if (currentMode !== 'mobile') return;
        for (const box of stage.querySelectorAll('[data-name^="Contenido de "], .tab-panel')) {
            let run = [];
            const flush = () => {
                if (run.length >= 3) { const g = document.createElement('div'); g.className = 'card-list'; run[0].before(g); for (const c of run) { c.style.marginTop = ''; g.append(c); } }
                run = [];
            };
            for (const el of [...box.children]) { if (el.classList.contains('card') && el.classList.contains('row-link')) run.push(el); else flush(); }
            flush();
        }
    }
    // Enlace «¿Olvidaste tu contraseña?» bajo el campo de contraseña del acceso.
    function forgotLink() {
        if (!/^login/.test(current.key)) return;
        const pwd = stage.querySelector('.auth-card input[type=password], .auth-sheet input[type=password]');
        const box = pwd?.closest('.field-group') ?? pwd?.closest('.auth-card > *, .auth-sheet > *');
        if (!box) return;
        const a = document.createElement('a');
        a.href = '#recuperar'; a.className = 'forgot-link'; a.textContent = '¿Olvidaste tu contraseña?';
        box.after(a);
    }
    function el(tag, cls, text) { const e = document.createElement(tag); if (cls) e.className = cls; if (text) e.textContent = text; return e; }
    function recoveryScreen() {
        if (!current.custom) return;
        const sent = current.custom === 'recuperar-enviado';
        const box = stage.querySelector('.auth-card, .auth-sheet');
        if (!box) return;
        // Cabecera móvil: el título pasa a «Recuperar acceso».
        const heroTitle = stage.querySelector('.auth-hero > .design-text:last-of-type');
        if (heroTitle) heroTitle.replaceWith(Object.assign(el('div', heroTitle.className.replace('hotspot', ''), 'Recuperar acceso'), { style: heroTitle.style.cssText }));
        const parts = [];
        if (box.classList.contains('auth-card')) parts.push(el('p', 'auth-eyebrow', 'Recuperar acceso'));
        if (!sent) {
            parts.push(el('h1', 'rc-title', '¿Olvidaste tu contraseña?'));
            parts.push(el('p', 'rc-text', 'Escribe el correo con el que creaste tu cuenta. El administrador de TuCriadero te ayudará a recuperar el acceso.'));
            const label = el('label', 'rc-label', 'Correo electrónico');
            const input = el('input', 'rc-input');
            input.type = 'email'; input.placeholder = 'correo@ejemplo.cl'; input.autocomplete = 'email'; input.id = 'rc-correo';
            label.htmlFor = 'rc-correo';
            const send = el('button', 'rc-btn', 'Enviar solicitud');
            send.type = 'button';
            send.addEventListener('click', () => {
                if (!input.value.trim()) { input.focus(); toast('Escribe tu correo para enviar la solicitud.'); return; }
                go('recuperar-enviado');
            });
            input.addEventListener('keydown', e => { if (e.key === 'Enter') send.click(); });
            parts.push(label, input, send);
        } else {
            const ok = el('div', 'rc-ok');
            ok.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
            parts.push(ok, el('h1', 'rc-title', 'Solicitud enviada'));
            parts.push(el('p', 'rc-text', 'Si el correo está registrado, el administrador de TuCriadero te contactará para ayudarte a recuperar el acceso. Revisa tu bandeja de entrada.'));
            const back = el('button', 'rc-btn', 'Volver a iniciar sesión');
            back.type = 'button'; back.addEventListener('click', () => go('login'));
            parts.push(back);
        }
        if (!sent) {
            const back = el('a', 'rc-back', '‹ Volver a iniciar sesión'); back.href = '#login';
            parts.push(back);
        }
        box.replaceChildren(...parts);
        box.classList.add('rc-box');
    }
    // ===== Propuesta visual (12/11/2026): más cálida, amable e intuitiva; no sigue el Figma. =====
    // Avatar de canario: silueta pintada con el color de su variedad (sale del nombre; no es un dato nuevo).
    function varietyColors(text) {
        const t = (text || '').toLowerCase();
        let body = '#CBD3CD', wing = '#9EAAA2', bg = '#EEF1EE', unknown = true;
        if (/rojo/.test(t)) { body = /marfil/.test(t) ? '#F49A72' : '#EE6A3F'; wing = '#C64B2C'; bg = '#FDE8DF'; unknown = false; }
        else if (/amarillo/.test(t)) { body = /marfil/.test(t) ? '#F6DA7C' : '#F6C324'; wing = '#D7A10E'; bg = '#FFF4CF'; unknown = false; }
        else if (/blanco/.test(t)) { body = '#F7F5EE'; wing = '#D9D4C7'; bg = '#EFEDE6'; unknown = false; }
        if (/negro|ágata|agata|bruno|isabela|ónix|onix/.test(t)) wing = '#4A3F35';
        return { body, wing, bg, unknown };
    }
    function birdSvg({ body, wing }) {
        return `<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M9 46 23 37.5 25 47Z" fill="${wing}"/><ellipse cx="31" cy="39" rx="17" ry="12.5" fill="${body}"/><circle cx="43" cy="25" r="10" fill="${body}"/><path d="M52.3 23.3 60 25.6 52.3 28.2Z" fill="#F08A24"/><circle cx="46.2" cy="23" r="1.9" fill="#1d2b26"/><path d="M18.5 35.5Q31 26 43.5 37.5 31 46.5 18.5 35.5Z" fill="${wing}"/><path d="M27.5 51 26 57M35 51 36 57" stroke="#B7793A" stroke-width="2.2" stroke-linecap="round"/></svg>`;
    }
    function birdAvatar(contextText, size) {
        const variety = /D\.\d{3}[\s·]+([A-Za-zÁÉÍÓÚÑáéíóúñü ]+)/.exec(contextText)?.[1] ?? '';
        const colors = varietyColors(variety);
        const a = document.createElement('span');
        a.className = `bird-avatar ${size}`;
        a.style.background = colors.bg;
        a.innerHTML = birdSvg(colors);
        const sex = /\bMacho\b/.test(contextText) ? 'm' : /\bHembra\b/.test(contextText) ? 'f' : '';
        if (sex) { const b = document.createElement('span'); b.className = `sex-badge sex-${sex}`; b.textContent = sex === 'm' ? '♂' : '♀'; b.title = sex === 'm' ? 'Macho' : 'Hembra'; a.append(b); }
        a.setAttribute('aria-hidden', 'true');
        return a;
    }
    function addAvatars() {
        // Títulos con anillo (fichas, tarjetas, búsqueda): avatar + «Anillo» + número.
        for (const tag of stage.querySelectorAll('.ring-tag')) {
            const title = tag.parentElement;
            if (!title || title.classList.contains('with-avatar') || title.closest('.pdf-sheet')) continue;
            let box = title.closest('.card') ?? title.parentElement;
            if (!/Macho|Hembra/.test(box.textContent) && box.parentElement) box = box.parentElement;
            const big = parseFloat(getComputedStyle(title).fontSize) > 20;
            const num = document.createElement('span'); num.className = 'id-num';
            num.append(...[...title.childNodes].filter(n => n !== tag));
            title.append(num);
            title.prepend(birdAvatar(box.textContent, big ? 'lg' : 'md'));
            title.classList.add('with-avatar');
        }
        // Filas de tabla cuyo primer dato es un anillo.
        for (const row of stage.querySelectorAll('[data-name="Tabla"] > .design-node')) {
            const cell = row.querySelector('.design-text');
            if (!cell || !/^\d{1,3} · \d{4} · \d{2,3}/.test(cell.textContent.trim())) continue;
            cell.prepend(birdAvatar(row.textContent, 'sm'));
            cell.classList.add('cell-with-avatar');
        }
    }
    // Avisos con icono.
    function notices() {
        for (const box of stage.querySelectorAll('[data-name="Aviso"], [data-name$=" Aviso"]')) {
            if (box.classList.contains('design-note') || box.querySelector(':scope > .notice-icon')) continue;
            box.classList.add('notice');
            const i = document.createElement('span'); i.className = 'notice-icon'; i.setAttribute('aria-hidden', 'true');
            i.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/></svg>';
            box.prepend(i);
        }
    }
    // Inicio: bienvenida con foto y números de la temporada (conteos de los datos de ejemplo, sin datos nuevos).
    const HOME_STATS = [['24', 'Canarios activos', 'bird', 'canarios'], ['5', 'Jaulas', 'cage', 'jaulas'], ['2', 'Cruces activos', 'book', 'cruces']];
    function homeHero() {
        if (current.key !== 'inicio') return;
        const box = stage.querySelector('[data-name="Área principal"]') ?? stage.querySelector('.screen-root');
        const hero = document.createElement('section'); hero.className = 'hero';
        hero.innerHTML = `<div class="hero-text"><span class="hero-eyebrow">Temporada 2026</span><h1>¡Hola! Qué bueno verte</h1><p>Tu criadero de un vistazo: tus canarios, sus jaulas y los cruces de esta temporada.</p></div><div class="hero-photo" role="img" aria-label="Dos canarios junto a una jaula"></div>`;
        const stats = document.createElement('div'); stats.className = 'stats';
        for (const [n, label, ic, key] of HOME_STATS) {
            const a = document.createElement('a'); a.className = 'stat'; a.href = `#${key}`;
            const i = document.createElement('span'); i.className = 'stat-icon'; i.append(icon(ic));
            const num = document.createElement('strong'); num.textContent = n;
            const l = document.createElement('span'); l.textContent = label;
            a.append(i, num, l); stats.append(a);
        }
        const title = [...box.children].find(c => c.classList.contains('design-text') && c.textContent.trim() === 'Inicio');
        const sub = [...box.children].find(c => /^Encuentra un canario/.test(c.textContent.trim()));
        if (title) { title.before(hero); title.hidden = true; } else box.prepend(hero);
        if (sub) sub.hidden = true;
        hero.after(stats);
        stage.querySelector('.home-section')?.remove();
    }
    // ===== Registro de cría (pedido del cliente, 12/11/2026) =====
    // Ejemplo del cliente: en la jaula 1, macho ágata rojo intenso portador de marfil × hembra ágata roja marfil nevada.
    const PAREJA = {
        jaula: 'Jaula 1',
        m: { socio: '277', anio: '2020', corr: '150', club: 'SOCH', codigo: 'D.143', variedad: 'Ágata rojo intenso', porta: ['Marfil'] },
        f: { socio: 'RH955', anio: '2025', corr: '1245', club: 'ACAM', codigo: 'D.146', variedad: 'Ágata rojo marfil nevado', porta: [] },
    };
    const PORTABLES = ['Marfil', 'Pastel', 'Ópalo', 'Satiné', 'Ino', 'Topacio', 'Ónix'];
    const MIS_MACHOS = ['18 · 2025 · 150 · CC — D.049 Rojo intenso', '18 · 2025 · 162 · CC — D.052 Rojo marfil nevado'];
    const MIS_HEMBRAS = ['18 · 2025 · 164 · CC — D.050 Rojo nevado', '18 · 2025 · 151 · CC — D.051 Rojo marfil intenso'];
    const h = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
    function field(label, input) { const w = h('label', 'cf-field'); w.append(h('span', 'cf-label', label), input); return w; }
    function textInput(value, extra = {}) { const i = h('input', 'cf-input'); i.type = 'text'; i.value = value; Object.assign(i, extra); return i; }
    function varietyPicker(data, onPick) {
        const wrap = h('div', 'vp');
        const input = textInput(`${data.codigo} · ${data.variedad}`, { placeholder: 'Escribe el código o el nombre (D.001–D.880)' });
        input.setAttribute('aria-label', 'Variedad del nomenclador');
        input.setAttribute('role', 'combobox'); input.setAttribute('aria-expanded', 'false');
        const list = h('ul', 'vp-list'); list.setAttribute('role', 'listbox'); list.hidden = true;
        const show = () => {
            const q = input.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
            const words = q.split(/[\s·]+/).filter(Boolean);
            const hits = (window.CATALOGO ?? []).filter(([c, n]) => {
                const t = `${c} ${n}`.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
                return words.every(w => t.includes(w));
            }).slice(0, 8);
            list.replaceChildren(...hits.map(([c, n]) => {
                const li = h('li', 'vp-item'); li.setAttribute('role', 'option');
                li.append(h('span', 'code', c), ' ', n);
                li.addEventListener('mousedown', e => { e.preventDefault(); input.value = `${c} · ${n}`; list.hidden = true; input.setAttribute('aria-expanded', 'false'); onPick(c, n); });
                return li;
            }));
            if (!hits.length) list.append(h('li', 'vp-empty', 'Sin coincidencias en el nomenclador.'));
            list.hidden = false; input.setAttribute('aria-expanded', 'true');
        };
        input.addEventListener('focus', () => { input.select(); show(); });
        input.addEventListener('input', show);
        input.addEventListener('blur', () => setTimeout(() => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); }, 120));
        wrap.append(input, list);
        return wrap;
    }
    function birdCard(sex, data) {
        const male = sex === 'm';
        const card = h('section', `cf-bird ${male ? 'is-m' : 'is-f'}`);
        const head = h('div', 'cf-bird-head');
        const avatar = birdAvatar(`${data.codigo} ${data.variedad} ${male ? 'Macho' : 'Hembra'}`, 'md');
        head.append(avatar, h('h3', '', male ? 'Macho ♂' : 'Hembra ♀'));
        const seg = h('div', 'cf-seg'); seg.setAttribute('role', 'tablist');
        const own = h('button', '', 'De mis canarios'), fresh = h('button', '', 'Ave nueva');
        [own, fresh].forEach(b => { b.type = 'button'; b.setAttribute('role', 'tab'); seg.append(b); });
        const panelOwn = h('div', 'cf-panel');
        const sel = h('select', 'cf-input'); for (const o of male ? MIS_MACHOS : MIS_HEMBRAS) sel.append(new Option(o));
        sel.setAttribute('aria-label', male ? 'Macho de mis canarios' : 'Hembra de mis canarios');
        panelOwn.append(field(male ? 'Macho disponible' : 'Hembra disponible', sel), h('p', 'cf-hint', 'Solo aparecen canarios disponibles: sin reserva y sin baja.'));
        const panelNew = h('div', 'cf-panel');
        const ring = h('div', 'cf-ring');
        ring.append(field('Socio', textInput(data.socio)), field('Año', textInput(data.anio, { inputMode: 'numeric' })), field('Correlativo', textInput(data.corr, { inputMode: 'numeric' })), field('Club', textInput(data.club)));
        const picker = varietyPicker(data, (c, n) => avatar.replaceWith(Object.assign(birdAvatar(`${c} ${n} ${male ? 'Macho' : 'Hembra'}`, 'md'))));
        const chips = h('div', 'cf-chips');
        for (const f of PORTABLES) {
            const c = h('button', 'cf-chip', f); c.type = 'button';
            c.setAttribute('aria-pressed', String(data.porta.includes(f)));
            c.addEventListener('click', () => c.setAttribute('aria-pressed', String(c.getAttribute('aria-pressed') !== 'true')));
            chips.append(c);
        }
        panelNew.append(h('p', 'cf-sub', 'Anillo'), ring, field('Variedad (nomenclador 2026)', picker), h('p', 'cf-sub', 'Portador de (opcional)'), chips,
            h('p', 'cf-hint', 'Al guardar, el ave queda registrada en Mis canarios con estos datos.'));
        const show = i => { [own, fresh].forEach((b, k) => b.setAttribute('aria-selected', String(k === i))); panelOwn.hidden = i !== 0; panelNew.hidden = i !== 1; };
        own.addEventListener('click', () => show(0)); fresh.addEventListener('click', () => show(1));
        show(1);
        card.append(head, seg, panelNew, panelOwn);
        return card;
    }
    function crossContainer() {
        const box = stage.querySelector('[data-name="Área principal"], [data-name^="Contenido de "]');
        if (!box) return null;
        // En escritorio se conservan la ruta y el título; el resto se reemplaza.
        const keep = currentMode === 'desktop' ? [...box.children].slice(0, 2) : [];
        box.replaceChildren(...keep);
        box.classList.add('cf-box');
        return box;
    }
    function crossForm() {
        if (current.key !== 'cruce-nuevo') return;
        const box = crossContainer(); if (!box) return;
        const intro = h('p', 'cf-intro', 'Anota la pareja tal como en tu ficha de cría: primero la jaula, luego el macho y la hembra. Si un ave aún no está en TuCriadero, regístrala aquí mismo.');
        const jaula = h('section', 'cf-step');
        const js = h('select', 'cf-input'); for (const j of ['Jaula 1', 'Jaula 2', 'Jaula 5', 'Jaula 8', 'Jaula 10', 'Jaula grande']) js.append(new Option(j));
        js.value = PAREJA.jaula;
        const fecha = textInput('12/11/2026', { inputMode: 'numeric' });
        const row = h('div', 'cf-row');
        row.append(field('Jaula', js), field('Temporada', Object.assign(textInput('2026'), { readOnly: true })), field('Fecha de inicio', fecha));
        jaula.append(h('h3', 'cf-step-title', '1 · ¿En qué jaula?'), row);
        const pair = h('section', 'cf-step');
        const birds = h('div', 'cf-pair');
        birds.append(birdCard('m', PAREJA.m), h('div', 'cf-x', '×'), birdCard('f', PAREJA.f));
        pair.append(h('h3', 'cf-step-title', '2 · La pareja'), birds);
        const actions = h('div', 'cf-actions');
        const go1 = h('button', 'rc-btn', 'Ver posible resultado'); go1.type = 'button'; go1.addEventListener('click', () => go('resultado'));
        const cancel = h('button', 'cf-ghost', 'Cancelar'); cancel.type = 'button'; cancel.addEventListener('click', () => go('cruces'));
        actions.append(go1, cancel);
        box.append(intro, jaula, pair, actions);
    }
    // Resultado del ejemplo del cliente. Coincide con la calculadora que el cliente indicó (animalesbiologia.com).
    const RESULT = {
        m: [['D.143', 'Ágata rojo intenso', 'Portador de marfil'], ['D.144', 'Ágata rojo nevado', 'Portador de marfil'], ['D.145', 'Ágata rojo marfil intenso', ''], ['D.146', 'Ágata rojo marfil nevado', '']],
        f: [['D.143', 'Ágata rojo intenso', ''], ['D.144', 'Ágata rojo nevado', ''], ['D.145', 'Ágata rojo marfil intenso', ''], ['D.146', 'Ágata rojo marfil nevado', '']],
    };
    const STEPS = [
        ['Ágata', 'Ligado al sexo', 'El padre y la madre son ágata.', 'Todas las crías salen ágata.'],
        ['Color de fondo rojo', 'Semidominante', 'Los dos tienen fondo rojo.', 'Todas las crías salen rojas.'],
        ['Intenso / nevado', 'Dominante', 'El intenso domina y el padre lo lleva en una sola copia; la madre es nevada.', 'La mitad sale intensa y la otra mitad nevada.'],
        ['Marfil', 'Ligado al sexo', 'La madre es marfil y el padre lo lleva oculto (portador).', 'Hembras: la mitad marfil, porque heredan del padre su único cromosoma de sexo. Machos: la mitad marfil y la otra mitad portadores, porque siempre reciben el marfil de la madre.'],
    ];
    function miniBird(sex, d) {
        const c = h('div', `cf-mini ${sex === 'm' ? 'is-m' : 'is-f'}`);
        c.append(birdAvatar(`${d.codigo} ${d.variedad} ${sex === 'm' ? 'Macho' : 'Hembra'}`, 'md'));
        const t = h('div', '');
        t.append(h('strong', 'ring', `${d.socio} · ${d.anio} · ${d.corr} · ${d.club}`));
        const v = h('span', 'cf-var'); v.append(h('span', 'code', d.codigo), ` ${d.variedad}`);
        t.append(v);
        if (d.porta.length) t.append(h('span', 'cf-tag', `Portador de ${d.porta.join(', ').toLowerCase()}`));
        c.append(t);
        return c;
    }
    function crossResult() {
        if (current.key !== 'resultado') return;
        const box = crossContainer(); if (!box) return;
        box.append(h('p', 'cf-eyebrow', `Registro de cría 2026 · ${PAREJA.jaula}`));
        const pair = h('div', 'cf-pair compact');
        pair.append(miniBird('m', PAREJA.m), h('div', 'cf-x', '×'), miniBird('f', PAREJA.f));
        box.append(pair);
        const res = h('div', 'cf-results');
        for (const sex of ['m', 'f']) {
            const col = h('section', `cf-res ${sex === 'm' ? 'is-m' : 'is-f'}`);
            col.append(h('h3', '', sex === 'm' ? 'Machos ♂' : 'Hembras ♀'));
            for (const [c, n, extra] of RESULT[sex]) {
                const r = h('div', 'cf-res-row');
                r.append(birdAvatar(`${c} ${n} ${sex === 'm' ? 'Macho' : 'Hembra'}`, 'sm'));
                const t = h('div', 'cf-res-text');
                const top = h('div', 'cf-res-top'); top.append(h('span', 'code', c), ` ${n}`);
                t.append(top);
                if (extra) t.append(h('span', 'cf-tag', extra));
                const bar = h('span', 'pct-bar'); const fill = h('span', ''); fill.style.width = '25%'; bar.append(fill); t.append(bar);
                r.append(t, h('strong', 'cf-pct', '25 %'));
                col.append(r);
            }
            res.append(col);
        }
        box.append(res);
        const how = h('details', 'cf-how'); how.open = true;
        how.append(h('summary', '', '¿Cómo se llegó a este resultado?'));
        how.append(h('p', 'cf-how-intro', 'TuCriadero mira cada factor por separado, según la tabla de Mendel, y después los combina:'));
        const list = h('ol', 'cf-steps');
        for (const [name, kind, cause, effect] of STEPS) {
            const li = h('li', '');
            const head = h('div', 'cf-step-head'); head.append(h('strong', '', name), h('span', 'cf-kind', kind));
            li.append(head, h('p', '', cause), h('p', 'cf-effect', `→ ${effect}`));
            list.append(li);
        }
        how.append(list, h('p', 'cf-how-intro', 'Al combinar «intenso o nevado» con «marfil o no», cada sexo queda repartido en cuatro partes iguales: 25 % cada una. Son probabilidades; con pocas crías puede haber diferencias.'));
        box.append(how);
        const actions = h('div', 'cf-actions');
        const ok = h('button', 'rc-btn', 'Confirmar y guardar en el libro de crías'); ok.type = 'button'; ok.addEventListener('click', () => saved('cruce'));
        const back = h('button', 'cf-ghost', 'Volver y corregir'); back.type = 'button'; back.addEventListener('click', () => go('cruce-nuevo'));
        actions.append(ok, back);
        box.append(actions);
    }
    // Botón «+» del celular con las acciones más usadas.
    function fabButton() {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'fab'; b.setAttribute('aria-label', 'Acciones rápidas'); b.setAttribute('aria-haspopup', 'true');
        b.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>';
        b.addEventListener('click', openQuickSheet);
        return b;
    }
    function openQuickSheet() {
        const back = document.createElement('div'); back.className = 'sheet-backdrop';
        const sheet = document.createElement('div'); sheet.className = 'quick-sheet'; sheet.setAttribute('role', 'dialog'); sheet.setAttribute('aria-label', 'Acciones rápidas');
        const h = document.createElement('p'); h.className = 'sheet-title'; h.textContent = '¿Qué quieres hacer?';
        sheet.append(h);
        for (const [label, ic, key, desc] of [['Registrar canario', 'bird', 'canario-nuevo', 'Anillo, sexo, variedad y padres'], ['Registro de cría 2026', 'book', 'cruce-nuevo', 'Jaula, macho y hembra de la pareja'], ['Anillar pichones', 'edit', 'anillar', 'Registra las crías de un cruce']]) {
            const a = document.createElement('a'); a.className = 'sheet-action'; a.href = `#${key}`;
            const i = document.createElement('span'); i.className = 'stat-icon'; i.append(icon(ic));
            const t = document.createElement('span'); t.innerHTML = `<strong>${label}</strong><small>${desc}</small>`;
            a.append(i, t); sheet.append(a);
        }
        const close = () => { back.remove(); sheet.remove(); };
        back.addEventListener('click', close);
        sheet.addEventListener('click', e => { if (e.target.closest('a')) close(); });
        document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); } });
        stage.append(back, sheet);
        sheet.querySelector('a')?.focus();
    }
    function addRequiredLegend() {
        const first = [...stage.querySelectorAll('.design-text')].find(t => /\*\s*$/.test(t.textContent) && !t.closest('.design-note'));
        if (!first || stage.querySelector('.req-legend')) return;
        const p = document.createElement('p'); p.className = 'req-legend';
        p.textContent = 'Los campos con * son obligatorios.';
        (first.closest('.card') ?? first.closest('.field-group') ?? first).before(p);
    }
    const trail = [];
    const ROOT_KEYS = /^(inicio|canarios|jaulas|cruces|login.*|recuperar.*|registro.*|cuentas|catalogo|404|403|sesion|mantenimiento|error|sin-conexion|pdf)$/;
    function backButton() {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'back-btn'; b.setAttribute('aria-label', 'Volver');
        b.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>';
        b.addEventListener('click', () => {
            // Vuelve a la pantalla anterior dentro de la maqueta; si se entró directo, a la sección.
            const section = { Inicio: 'inicio', Canarios: 'canarios', Jaulas: 'jaulas', 'Crías': 'cruces' }[navKey()] ?? 'inicio';
            go(trail.length > 1 ? trail.at(-2) : section);
        });
        return b;
    }
    function searchForm() {
        const form = document.createElement('form'); form.className = 'list-search'; form.setAttribute('role', 'search');
        form.innerHTML = `<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="${ICONS.search}"/></svg>`;
        const input = document.createElement('input');
        input.type = 'search'; input.placeholder = 'Buscar por anillo, variedad o jaula'; input.setAttribute('aria-label', 'Buscar canario');
        const submit = document.createElement('button'); submit.type = 'submit'; submit.textContent = 'Buscar';
        form.append(input, submit);
        form.addEventListener('submit', e => {
            e.preventDefault();
            go('buscar', input.value.trim() ? `Resultados de ejemplo: la maqueta muestra la búsqueda del anillo 150, no «${input.value.trim()}».` : undefined);
        });
        return form;
    }
    // Accesos del inicio: solo navegación a funciones existentes, sin cifras ni datos nuevos.
    const HOME_TILES = [
        ['Mis canarios', 'bird', 'canarios', 'Fichas, árbol genealógico y estado de cada ejemplar.'],
        ['Jaulas', 'cage', 'jaulas', 'Quién está en cada jaula y su historial.'],
        ['Libro de crías', 'book', 'cruces', 'Cruces y pichones de cada temporada.'],
        ['Buscar', 'search', 'buscar', 'Por anillo, variedad o jaula, incluidas las bajas.'],
    ];
    function homeTiles() {
        const box = document.createElement('section'); box.className = 'home-section';
        const h = document.createElement('h2'); h.className = 'home-title'; h.textContent = 'Tu criadero';
        const grid = document.createElement('div'); grid.className = 'home-tiles';
        for (const [title, ic, key, text] of HOME_TILES) {
            const a = document.createElement('a'); a.className = 'home-tile'; a.href = `#${key}`;
            const badge = document.createElement('span'); badge.className = 'home-icon'; badge.append(icon(ic));
            const t = document.createElement('strong'); t.textContent = title;
            const d = document.createElement('span'); d.textContent = text;
            a.append(badge, t, d); grid.append(a);
        }
        box.append(h, grid);
        return box;
    }
    const ACTION_ICON = { 'Asignar a jaula': 'cage', 'Reservar': 'bookmark', 'Quitar reserva': 'bookmark', 'Exportar PDF': 'file', 'Exportar ficha a PDF': 'file', 'Dar de baja': 'exit', 'Editar ficha': 'edit', 'Editar': 'edit' };
    function groupActions(el) {
        // Varias acciones secundarias seguidas (móvil): cuadrícula de dos columnas con iconos, para que no abrumen.
        const kids = [...el.children];
        for (let i = 0; i < kids.length;) {
            let j = i;
            while (j < kids.length && kids[j].classList.contains('btn-secondary')) j++;
            if (j - i >= 3) {
                const grid = document.createElement('div'); grid.className = 'action-grid';
                kids[i].before(grid);
                for (const b of kids.slice(i, j)) {
                    const ic = ACTION_ICON[b.getAttribute('aria-label')];
                    if (ic) b.prepend(icon(ic));
                    b.style.marginTop = ''; grid.append(b);
                }
                if ((j - i) % 2) grid.lastElementChild.classList.add('wide');
            }
            i = Math.max(j, i + 1);
        }
    }
    // Árbol genealógico interactivo (RF024 + AC-01): mismos textos del ZIP, dibujados como pedigrí.
    // Índices de montículo: 0 = este canario; los padres de i son 2i+1 (padre) y 2i+2 (madre).
    const GEN2 = ['abuelo paterno', 'abuela paterna', 'abuelo materno', 'abuela materna'];
    function kinship(i) {
        if (i >= 100) return 'Cría';
        if (i === 0) return 'Este canario';
        if (i <= 2) return i === 1 ? 'Padre' : 'Madre';
        if (i <= 6) { const t = GEN2[i - 3]; return t[0].toUpperCase() + t.slice(1); }
        const g2 = GEN2[Math.floor((i - 7) / 2)];
        const male = /^abuelo/.test(g2);
        return `${(i - 7) % 2 === 0 ? 'Padre' : 'Madre'} ${male ? 'del' : 'de la'} ${g2}`;
    }
    const pathOf = i => { if (i >= 100) return [0, i]; const p = []; while (i > 0) { p.push(i); i = Math.floor((i - 1) / 2); } return [0, ...p.reverse()]; };
    function pedigreeModel(n, parent) {
        if (n.name === 'Árbol genealógico conectado') {
            return n.children.filter(c => c.type === 'FRAME').map(c => {
                const t = c.children.filter(x => x.type === 'TEXT');
                return { role: t[0]?.text ?? '', value: (t[1]?.text ?? '').replace(/\s*›\s*$/, '') };
            });
        }
        const lines = name => (parent.children.find(c => c.name === name)?.children ?? []).filter(c => c.type === 'TEXT').slice(1).map(c => c.text);
        const split = t => { const i = t.indexOf(' · '); return { role: t.slice(0, i), value: t.slice(i + 3).replace(/\s*›\s*$/, '').replace(' · Club', '\nClub') }; };
        const ring = parent.children.find(c => c.type === 'TEXT')?.text ?? '';
        return [{ role: 'Este canario', value: ring }, ...['Padres', 'Abuelos', 'Bisabuelos'].flatMap(k => lines(k).map(split))];
    }
    // Crías del canario (RF024), tomadas de la tarjeta «Crías» de la misma ficha.
    function criasOf() {
        const found = [];
        let done = false;
        const walk = n => {
            if (done) return;
            if (n.type === 'FRAME' && n.name === 'Crías') {
                const txt = n.children.filter(c => c.type === 'TEXT').slice(1).map(c => c.text).join('\n');
                for (const m of txt.matchAll(/(\d+ · \d{4} · \d+)(?: · ([A-ZÁÉÍÓÚÑ]{2,8}|Club[^\n›]*?))?\s*›/g)) found.push({ role: 'Cría', value: m[1] + (m[2] ? ' · ' + m[2].trim() : '') });
                done = true; return;
            }
            n.children.forEach(walk);
        };
        walk(current[currentMode]);
        return found;
    }
    function pedigree(model, wide, crias = []) {
        const known = p => p && !/^Sin registro/.test(p.value);
        const wrap = document.createElement('div');
        wrap.className = `pedigree-wrap${wide ? ' wide' : ''}`;
        const bar = document.createElement('div');
        bar.className = 'ped-bar';
        const label = document.createElement('span'); label.textContent = 'Ver:';
        bar.append(label);
        const scroller = document.createElement('div'); scroller.className = 'ped-scroll';
        const detail = document.createElement('div'); detail.className = 'ped-detail'; detail.setAttribute('aria-live', 'polite');
        let depth = 3, selected = null;
        const levels = [['Padres', 1], ['Abuelos', 2], ['Bisabuelos', 3]];
        const chips = levels.map(([title, d]) => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'ped-chip'; b.textContent = title;
            b.addEventListener('click', () => { depth = d; if (selected !== null && selected < 100 && selected >= 2 ** (d + 1) - 1) selected = null; draw(); });
            bar.append(b); return [b, d];
        });
        function showDetail() {
            detail.replaceChildren();
            const i = selected ?? 0, p = i >= 100 ? crias[i - 100] : model[i];
            const title = document.createElement('strong'); title.textContent = kinship(i);
            const ring = document.createElement('span'); ring.textContent = p.value.replace('\n', ' · ');
            detail.append(title, ring);
            if (selected === null) {
                const tip = document.createElement('span'); tip.className = 'ped-tip';
                tip.textContent = 'Toca un ancestro para ver su línea hasta este canario.';
                detail.append(tip);
            } else if (known(p)) {
                const open = document.createElement('button');
                open.type = 'button'; open.className = 'ped-open'; open.textContent = 'Abrir ficha ›';
                open.addEventListener('click', () => go('ficha', 'En la aplicación se abrirá la ficha de este ejemplar.'));
                detail.append(open);
            } else {
                const tip = document.createElement('span'); tip.className = 'ped-tip';
                tip.textContent = 'Sin registro en el criadero: no hay datos de este ancestro.';
                detail.append(tip);
            }
        }
        function draw() {
            chips.forEach(([b, d]) => { b.classList.toggle('active', d === depth); b.setAttribute('aria-pressed', String(d === depth)); });
            let rows = 2 ** depth;
            while (rows < crias.length && rows < 8) rows *= 2;
            const line = new Set(selected === null ? [] : pathOf(selected)), offset = crias.length ? 2 : 0;
            const grid = document.createElement('div');
            grid.className = 'pedigree';
            grid.style.gridTemplateRows = `repeat(${rows}, minmax(${wide ? 58 : 48}px, auto))`;
            grid.style.gridTemplateColumns = (offset ? 'var(--ped-col-c) var(--ped-gap) ' : '') + Array.from({ length: depth + 1 }, (_, g) => `var(--ped-col-${g})`).join(' var(--ped-gap) ');
            for (let g = 0; g <= depth; g++) {
                const count = 2 ** g, span = rows / count;
                for (let k = 0; k < count; k++) {
                    const i = count - 1 + k, p = model[i] ?? { role: '', value: 'Sin registro' };
                    const card = document.createElement('div');
                    card.className = `ped-card${i === 0 ? ' ped-self' : ''}${known(p) ? '' : ' ped-empty'}${line.has(i) ? ' on-path' : ''}${selected === i ? ' selected' : ''}`;
                    card.style.gridColumn = String(g * 2 + 1 + offset); card.style.gridRow = `${k * span + 1} / span ${span}`;
                    card.tabIndex = 0; card.setAttribute('role', 'button');
                    card.setAttribute('aria-label', `${kinship(i)}: ${p.value.replace('\n', ' ')}`);
                    const role = document.createElement('span'); role.className = 'ped-role'; role.textContent = p.role;
                    const value = document.createElement('span'); value.className = 'ped-value'; value.textContent = p.value;
                    card.append(role, value);
                    const pick = () => { selected = i === 0 || selected === i ? null : i; draw(); };
                    card.addEventListener('click', pick);
                    card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(); } });
                    grid.append(card);
                    if (g < depth) {
                        const conn = document.createElement('div');
                        conn.className = 'ped-conn'; conn.style.gridColumn = String(g * 2 + 2 + offset); conn.style.gridRow = `${k * span + 1} / span ${span}`;
                        const up = line.has(2 * i + 1) ? ' on' : '', down = line.has(2 * i + 2) ? ' on' : '';
                        conn.innerHTML = `<svg viewBox="0 0 20 100" preserveAspectRatio="none" aria-hidden="true"><path class="${up || down ? 'on' : ''}" d="M0 50H10"/><path class="${up}" d="M10 50V25H20"/><path class="${down}" d="M10 50V75H20"/></svg>`;
                        grid.append(conn);
                    }
                }
            }
            if (crias.length) {
                // Crías a la izquierda del canario, unidas a él.
                const k = Math.min(crias.length, rows), span = Math.max(1, Math.floor(rows / k)), start = Math.floor((rows - span * k) / 2);
                let paths = `<path class="${selected >= 100 ? 'on' : ''}" d="M20 50H10"/>`;
                crias.slice(0, k).forEach((c, j) => {
                    const i = 100 + j;
                    const card = document.createElement('div');
                    card.className = `ped-card ped-child${line.has(i) ? ' on-path' : ''}${selected === i ? ' selected' : ''}`;
                    card.style.gridColumn = '1'; card.style.gridRow = `${start + j * span + 1} / span ${span}`;
                    card.tabIndex = 0; card.setAttribute('role', 'button');
                    card.setAttribute('aria-label', `Cría: ${c.value.replace('\n', ' ')}`);
                    const role = document.createElement('span'); role.className = 'ped-role'; role.textContent = 'Cría';
                    const value = document.createElement('span'); value.className = 'ped-value'; value.textContent = c.value;
                    card.append(role, value);
                    const pick = () => { selected = selected === i ? null : i; draw(); };
                    card.addEventListener('click', pick);
                    card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(); } });
                    grid.append(card);
                    const y = ((start + j * span + span / 2) / rows) * 100;
                    paths += `<path class="${selected === i ? 'on' : ''}" d="M10 50V${y}H0"/>`;
                });
                const conn = document.createElement('div');
                conn.className = 'ped-conn'; conn.style.gridColumn = '2'; conn.style.gridRow = `1 / span ${rows}`;
                conn.innerHTML = `<svg viewBox="0 0 20 100" preserveAspectRatio="none" aria-hidden="true">${paths}</svg>`;
                grid.append(conn);
            }
            scroller.replaceChildren(grid);
            showDetail();
            requestAnimationFrame(() => { if (scroller.clientWidth) wrap.classList.toggle('scrolls', scroller.scrollWidth > scroller.clientWidth + 2); });
        }
        const hint = document.createElement('p'); hint.className = 'ped-hint'; hint.textContent = 'Desliza para ver más generaciones →';
        const legend = document.createElement('p'); legend.className = 'ped-legend';
        legend.textContent = `${crias.length ? 'A la izquierda, sus crías; a la derecha, sus antepasados. ' : ''}P = padre · M = madre, leído desde este canario. Ejemplo: P–P–M es la madre del abuelo paterno.`;
        wrap.append(bar, hint, scroller, detail, legend);
        draw();
        return wrap;
    }
    const isPedigreePart = (n, parent) => n.name === 'Árbol genealógico conectado'
        || (/^(Padres|Abuelos|Bisabuelos)$/.test(n.name) && ['Padres', 'Abuelos', 'Bisabuelos'].every(k => parent?.children?.some(c => c.name === k)));
    function renderNode(n, parent = null, ancestorButton = false, ctx = { flow: 'root' }) {
        if (isBottomBar(n)) return bottomNav();
        if (n.name === 'Búsqueda inmediata' && !n.layout && current.key === 'inicio') {
            const form = searchForm(); form.classList.add('home-search'); return form;
        }
        if (isPedigreePart(n, parent)) {
            if (n.name === 'Árbol genealógico conectado' || n.name === 'Padres') return pedigree(pedigreeModel(n, parent), currentMode === 'desktop', criasOf());
            const skip = document.createElement('div'); skip.hidden = true; return skip;
        }
        const el = document.createElement('div');
        el.dataset.node = n.id;
        el.dataset.name = n.name;
        el.className = 'design-node';
        if (n.note) el.classList.add('design-note');
        applyStyles(el, n.style);
        place(el, n, ctx);
        if (n.image) {
            el.classList.add('brand-mark'); el.append(logo(Math.min(n.style.width, n.style.height)));
            el.style.width = el.style.height = px(n.style.width); el.style.flex = '0 0 auto';
            return el;
        }
        if (n.svgPaths?.length) {
            el.style.border = 'none'; el.style.background = 'transparent';
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('width', n.style.width); svg.setAttribute('height', n.style.height);
            svg.style.overflow = 'visible';
            for (const d of n.svgPaths) {
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', d); path.setAttribute('fill', n.style.borderColor ?? '#155d50'); svg.append(path);
            }
            el.append(svg);
            return el;
        }
        if (isControl(n, parent)) { el.style.minHeight = px(n.style.height); control(n, parent, el); return el; }
        if (n.type === 'TEXT') {
            el.classList.add('design-text');
            if (/^Inicio\s+·\s+Aves/.test(n.text)) return bottomNav();
            const brand = n.text === 'TuCriadero' && (n.style.fontSize >= 20 || parent?.name === 'Cabecera') && !parent?.children?.some(c => c.image);
            if (brand) { el.classList.add('brand-text'); el.append(logo(Math.max(16, Math.round(n.style.fontSize * 1.25)))); el.append(n.text); }
            else fillText(el, n);
            const pct = /^(\d{1,3})\s?%/.exec(n.text);
            if (pct && /resultado|sin-regla|sin-variedad|aproximado/.test(current.key) && n.style.fontSize >= 14) {
                const bar = document.createElement('span');
                bar.className = 'pct-bar'; bar.innerHTML = '<span></span>';
                bar.firstChild.style.width = `${Math.min(100, +pct[1])}%`;
                el.append(bar);
            }
        }
        const oneText = n.children.length === 1 && n.children[0].type === 'TEXT' ? n.children[0].text : '';
        const isButton = n.type === 'FRAME' && n.style.height <= 65 && !!oneText;
        let handler = !ancestorButton && !n.note && (n.type === 'TEXT' || isButton) ? action(isButton ? oneText : n.text, n, parent) : null;
        // Los títulos grandes no son botones de navegación.
        if (n.type === 'TEXT' && (n.style.fontSize >= 22 || /^TuCriadero\s*\//.test(n.text))) handler = null;
        if (handler) {
            el.classList.add('hotspot');
            if (isButton && /rgba\((1[0-9]|2[0-9]|[0-9]|4[0-9]),/.test(n.style.background ?? '')) el.classList.add('btn-primary');
            else if (isButton && (n.style.background || n.style.borderColor)) el.classList.add('btn-secondary');
            el.tabIndex = 0;
            el.setAttribute('role', 'button');
            el.setAttribute('aria-label', clean(isButton ? oneText : n.text));
            el.addEventListener('click', e => { e.stopPropagation(); handler(); });
            el.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); e.stopPropagation(); handler(); }
            });
        }
        if (n.type === 'FRAME' && n.style.background === 'rgba(255,255,255,1)' && n.style.borderRadius >= 8 && !isButton) el.classList.add('card');
        if (n.name === 'Navegación' && n.layout) el.classList.add('sidebar');
        if (n.name === 'Cabecera' && n.layout) el.classList.add('app-header');
        const authMobile = /^(login|recuperar)/.test(current.key) && currentMode === 'mobile';
        if (authMobile && n.name === 'Cabecera') el.classList.add('auth-hero');
        if (authMobile && ctx.flow === 'root') el.classList.add('auth-root');
        if (n.name === 'Cabecera' && n.layout && currentMode === 'mobile' && !ROOT_KEYS.test(current.key)) { el.classList.add('has-back'); el.prepend(backButton()); }
        if (authMobile && /^Contenido de /.test(n.name)) el.classList.add('auth-sheet');
        if (authMobile && /^Contenido de /.test(parent?.name ?? '') && n.style.background === 'rgba(228,241,235,1)') el.classList.add('field-group');
        if (n.name === 'Marca y bienvenida') el.classList.add('auth-brand');
        if (parent?.name === 'Navegación' && NAV_ICON[clean(oneText)]) { el.classList.add('nav-item'); el.prepend(icon(NAV_ICON[clean(oneText)])); }
        if (/consentimiento/i.test(n.name) && n.type === 'FRAME' && n.style.height < 60 && n.style.width < 40) {
            el.style.width = px(n.style.width); el.style.height = px(n.style.height); el.style.flex = '0 0 auto';
            const check = document.createElement('input');
            check.type = 'checkbox'; check.checked = current.key !== 'registro-error';
            check.setAttribute('aria-label', 'Acepto el uso de mis datos para el servicio');
            check.style.cssText = 'width:100%;height:100%;margin:0;accent-color:#155d50';
            el.append(check);
            return el;
        }
        layoutChildren(el, n, ancestorButton || !!handler, ctx);
        if (n.name === 'Acceso a tu cuenta') {
            // Acceso de escritorio, con la imagen de referencia del PM como guía de estilo (solo TuCriadero, sin funciones nuevas).
            const card = document.createElement('div');
            card.className = 'auth-card';
            const eyebrow = document.createElement('p'); eyebrow.className = 'auth-eyebrow'; eyebrow.textContent = 'Bienvenido a TuCriadero';
            card.append(eyebrow, ...el.childNodes);
            for (const t of card.querySelectorAll('.design-text')) if (t.textContent.trim() === 'Crear cuenta') t.hidden = true;
            const button = card.querySelector('.btn-primary');
            if (button) {
                button.classList.add('auth-submit');
                button.insertAdjacentHTML('beforeend', ARROW);
                const note = document.createElement('p'); note.className = 'auth-note';
                note.innerHTML = LOCK; note.append('Tus datos son privados: solo tu criadero los ve.');
                button.after(note);
            }
            const top = document.createElement('div'); top.className = 'auth-top';
            const ask = document.createElement('span'); ask.textContent = '¿Aún no tienes cuenta?';
            const link = document.createElement('a'); link.href = '#registro'; link.textContent = 'Crear cuenta';
            top.append(ask, link);
            const foot = document.createElement('div'); foot.className = 'auth-foot'; foot.textContent = '© 2026 TuCriadero';
            el.append(top, card, foot); el.classList.add('auth-panel');
        }
        if (current.key === 'inicio' && (ctx.flow === 'root' && currentMode === 'mobile' || n.name === 'Área principal')) {
            const anchor = currentMode === 'mobile'
                ? [...el.children].find(c => c.getAttribute('aria-label') === 'Nuevo cruce')
                : [...el.children].find(c => c.dataset.node && /Acciones/.test(c.dataset.name ?? '')) ?? el.children[4];
            if (anchor) anchor.after(homeTiles());
        }
        if (currentMode === 'mobile' && /^Contenido de /.test(n.name)) groupActions(el);
        if (current.key === 'canarios' && /^(Área principal|Contenido de Mis canarios)$/.test(n.name)) {
            // Acceso directo a la búsqueda (RF de búsqueda ya existente) desde el listado; lleva a la pantalla de resultados.
            const add = [...el.children].find(c => c.getAttribute('aria-label') === 'Registrar canario');
            if (add) {
                const bar = document.createElement('div'); bar.className = 'list-toolbar';
                const form = searchForm();
                add.replaceWith(bar);
                bar.append(form, add);
                add.style.marginTop = ''; add.style.alignSelf = '';
            }
        }
        if (ctx.flow === 'root' && /^registro/.test(current.key) && currentMode === 'desktop') {
            // Registro de escritorio con el mismo panel de marca del acceso (textos del propio diseño de acceso).
            const card = el.lastElementChild;
            card.classList.add('reg-card');
            card.querySelector('.btn-primary')?.classList.add('auth-submit');
            const brand = document.createElement('div');
            brand.className = 'design-node auth-brand reg-brand';
            const name = document.createElement('div'); name.className = 'design-node design-text brand-text';
            name.append(logo(50), 'TuCriadero');
            const head = document.createElement('div'); head.className = 'design-node design-text'; head.textContent = 'Tu aviario,\ngeneración tras generación.';
            const sub = document.createElement('div'); sub.className = 'design-node design-text'; sub.textContent = 'Canarios, jaulas y cría en un mismo lugar.';
            brand.append(name, head, sub);
            const side = document.createElement('div'); side.className = 'auth-panel reg-panel';
            const eyebrow = document.createElement('p'); eyebrow.className = 'auth-eyebrow'; eyebrow.textContent = 'Bienvenido a TuCriadero';
            const foot = document.createElement('div'); foot.className = 'auth-foot'; foot.textContent = '© 2026 TuCriadero';
            const box = document.createElement('div'); box.className = 'reg-box';
            box.append(eyebrow, card);
            side.append(box, foot);
            el.replaceChildren(brand, side);
            el.classList.add('auth-split');
        }
        return el.__wrapper ?? el;
    }
    function layoutChildren(el, n, inButton, ctx) {
        const s = n.style, kids = n.children;
        if (!kids.length) {
            if (n.type === 'FRAME') { el.style.minHeight = px(s.height); if (ctx.flow !== 'column' || !(n.stretch || s.width >= ctx.innerW * 0.9)) el.style.width = el.style.width || px(s.width); }
            return;
        }
        if (n.layout) {
            const { dir, gap, pad } = n.layout;
            Object.assign(el.style, { display: 'flex', flexDirection: dir, gap: px(gap), padding: pad.map(px).join(' '), alignItems: 'flex-start' });
            const boxed = c => c.type === 'FRAME' && (c.style.background || c.style.borderColor) && c.style.width < (s.width - pad[1] - pad[3]) * 0.5;
            const wraps = dir === 'row' && kids.length > 1 && kids.every(boxed);
            if (wraps) el.style.flexWrap = 'wrap';
            const inner = { innerW: s.width - pad[1] - pad[3], innerH: s.height - pad[0] - pad[2] };
            const biggest = ctx.flow === 'root' && dir === 'column' ? kids.reduce((a, b) => (b.style.height > a.style.height ? b : a)) : null;
            for (const child of kids) {
                const node = renderNode(child, n, inButton, { flow: dir, ...inner, grow: child === biggest });
                // Tarjetas lado a lado: se reparten el ancho y bajan de línea solo si no caben.
                if (wraps && child.style.height > 65) Object.assign(node.style, { flex: '1 1 0', minWidth: px(Math.min(child.style.width, 260)) });
                el.append(node);
            }
            return;
        }
        // Marco contenedor del escritorio: un único marco con Auto Layout del mismo tamaño.
        if (kids.length === 1 && kids[0].type === 'FRAME' && kids[0].layout && Math.abs(kids[0].style.width - s.width) < 2) {
            Object.assign(el.style, { display: 'flex', flexDirection: 'column' });
            el.append(renderNode(kids[0], n, inButton, { flow: 'column', innerW: s.width, innerH: s.height, grow: true }));
            return;
        }
        // Diagramas con líneas (árbol genealógico): se conservan las posiciones, con desplazamiento horizontal.
        if (kids.some(c => c.type === 'VECTOR')) {
            Object.assign(el.style, { width: px(s.width), height: px(s.height), maxWidth: 'none', flex: '0 0 auto' });
            for (const child of kids) el.append(renderNode(child, n, inButton, { flow: null }));
            const scroller = document.createElement('div');
            scroller.className = 'diagram-scroll';
            el.style.position = 'relative'; el.style.marginTop = '';
            scroller.append(el);
            el.__wrapper = scroller;
            return;
        }
        // Un solo texto (botón o campo): se centra verticalmente con los márgenes del diseño.
        if (kids.length === 1 && kids[0].type === 'TEXT') {
            const t = kids[0].style;
            Object.assign(el.style, {
                display: 'flex', alignItems: 'center', minHeight: px(s.height),
                paddingLeft: px(Math.max(0, t.left)), paddingRight: px(Math.max(0, s.width - t.left - t.width)),
            });
            if (ctx.flow !== 'column' || !(n.stretch || s.width >= ctx.innerW * 0.9)) el.style.width = el.style.width || px(s.width);
            el.append(renderNode(kids[0], n, inButton, { flow: 'leaf' }));
            return;
        }
        // Resto: filas deducidas de la posición de cada capa; cada fila conserva sus márgenes laterales.
        const rows = rowsOf(kids);
        Object.assign(el.style, { display: 'flex', flexDirection: 'column', alignItems: 'flex-start', paddingTop: px(rows[0].top), paddingBottom: px(Math.min(32, Math.max(0, s.height - rows.at(-1).bottom))) });
        let prev = rows[0].top;
        for (const row of rows) {
            const gap = Math.max(0, row.top - prev); prev = row.bottom;
            const left = Math.max(0, row.items[0].style.left);
            const rightEdge = Math.max(...row.items.map(c => c.style.left + c.style.width));
            const right = Math.max(0, s.width - rightEdge);
            if (row.items.length === 1) {
                const c = row.items[0];
                const wide = c.style.width >= (s.width - left - right) * 0.98 && c.style.width >= s.width * 0.7;
                const centered = !wide && Math.abs(left - right) < 10 && left > 8;
                const child = renderNode(c, n, inButton, { flow: 'column', innerW: wide ? c.style.width : s.width, innerH: s.height, center: centered });
                child.style.marginTop = px(gap);
                if (wide) Object.assign(child.style, { alignSelf: 'stretch', marginLeft: px(left), marginRight: px(right), width: '' });
                else if (!centered) child.style.marginLeft = px(left);
                el.append(child);
                continue;
            }
            const line = document.createElement('div');
            line.className = 'design-row';
            Object.assign(line.style, { display: 'flex', alignItems: 'center', alignSelf: 'stretch', marginTop: px(gap), marginLeft: px(left), marginRight: px(right) });
            let edge = left;
            row.items.forEach((c, i) => {
                const child = renderNode(c, n, inButton, { flow: 'row', innerW: rightEdge - left, innerH: row.bottom - row.top });
                const space = c.style.left - edge;
                const atRight = i === row.items.length - 1 && i > 0 && space > 32;
                child.style.marginLeft = atRight ? 'auto' : px(Math.max(0, i ? space : 0));
                if (c.type === 'TEXT') child.style.flex = '1 1 auto';
                edge = c.style.left + c.style.width;
                line.append(child);
            });
            el.append(line);
        }
    }
    function resize() {
        if (!current) return;
        if (mode() !== currentMode) { render(); return; }
        const vw = document.documentElement.clientWidth;
        let width = null, scale = 1;
        if (current.key === 'pdf') { width = 794; scale = Math.min(1, (vw - 16) / 794); }
        else if (currentMode === 'mobile' && vw > MOBILE_MAX) width = 390;
        else if (currentMode === 'desktop' && vw < 700) { width = 1024; scale = vw / 1024; }
        document.body.classList.toggle('phone-preview', currentMode === 'mobile' && vw > MOBILE_MAX && current.key !== 'pdf');
        stage.style.width = width ? px(width) : '100%';
        stage.style.transform = scale < 1 ? `scale(${scale})` : '';
        const top = wrap.getBoundingClientRect().top + scrollY;
        const screenH = document.body.classList.contains('phone-preview') ? 780 : Math.max(480, innerHeight - top);
        stage.style.setProperty('--screen-h', px(screenH / scale));
        for (const box of stage.querySelectorAll('.diagram-scroll')) {
            const inner = box.firstElementChild;
            inner.style.zoom = '';
            const fit = box.clientWidth / inner.offsetWidth;
            if (fit < 1) inner.style.zoom = String(Math.max(0.6, fit));
        }
        for (const box of stage.querySelectorAll('.pedigree-wrap')) {
            const sc = box.querySelector('.ped-scroll');
            box.classList.toggle('scrolls', sc.scrollWidth > sc.clientWidth + 2);
        }
        wrap.style.width = width ? px(width * scale) : '100%';
        wrap.style.height = scale < 1 ? px(stage.offsetHeight * scale) : '';
    }
    function render() {
        current = screens.find(s => s.key === route()) ?? screens.find(s => s.key === '404');
        if (trail.at(-2) === current.key) trail.pop(); else if (trail.at(-1) !== current.key) trail.push(current.key);
        currentMode = mode();
        sceneSelect.value = current.key;
        document.title = `${current.label} · TuCriadero · Maqueta`;
        stage.setAttribute('aria-label', current.label);
        stage.replaceChildren(renderNode(current[currentMode]));
        document.querySelector('.print-button')?.remove();
        if (current.key === 'pdf') {
            const button = document.createElement('button');
            button.className = 'print-button'; button.textContent = 'Guardar / imprimir PDF';
            button.addEventListener('click', () => window.print());
            document.querySelector('#viewer').prepend(button);
        }
        flattenFields();
        addRequiredLegend();
        tidyActions();
        hideSpecText();
        recoveryScreen();
        forgotLink();
        fichaTabs();
        wholeRowLinks();
        homeHero();
        crossForm();
        crossResult();
        addAvatars();
        notices();
        birdFormSections();
        cageLayout();
        cardLists();
        let order = 0;
        for (const item of stage.querySelectorAll('.card, .btn-primary, .btn-secondary, .home-tile, .pedigree-wrap, .list-toolbar, .auth-card, .reg-box, .auth-sheet, .action-grid'))
            item.style.setProperty('--i', String(Math.min(order++, 14)));
        resize();
        window.scrollTo(0, 0);
    }
    for (const scene of screens) {
        const option = document.createElement('option'); option.value = scene.key; option.textContent = scene.label; sceneSelect.append(option);
    }
    sceneSelect.addEventListener('change', () => go(sceneSelect.value));
    deviceSelect.addEventListener('change', render);
    notes.addEventListener('change', () => stage.classList.toggle('show-notes', notes.checked));
    document.querySelector('#help').addEventListener('click', () => document.querySelector('#help-dialog').showModal());
    document.querySelector('#present').addEventListener('click', () => {
        document.body.classList.add('presentation');
        const url = new URL(location.href); url.searchParams.set('presentar', '1'); history.replaceState(null, '', url);
        resize();
        toast('Modo presentación. Pulsa Escape para volver a los controles de revisión.');
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.body.classList.contains('presentation')) {
            document.body.classList.remove('presentation');
            const url = new URL(location.href); url.searchParams.delete('presentar'); history.replaceState(null, '', url);
            resize();
        }
    });
    document.querySelector('.dialog-close').addEventListener('click', () => document.querySelector('#help-dialog').close());
    window.addEventListener('hashchange', render);
    window.addEventListener('resize', resize);
    render();
})();
