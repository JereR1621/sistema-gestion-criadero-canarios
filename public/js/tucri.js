/* TuCri · front local. JavaScript mínimo de interfaz: lo de negocio va por PHP y htmx, y todo funciona sin este archivo.
   Menú lateral y hojas (con arrastrar para cerrar, como la maqueta), desplegables, pestañas, contraseña,
   filtro de Mis canarios, agregar pichón, aviso flash y pantalla sin conexión. */
(() => {
    'use strict';
    document.documentElement.classList.add('js');
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];
    const isPhone = () => matchMedia('(max-width: 899px)').matches;
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const app = $('#app');
    const sheetRoot = $('#sheet-root');

    // Espera el fin de la transición, con un tope por si no llega (movimiento reducido o pestaña oculta).
    function afterTransition(el, ms, done) {
        let ok = false;
        const fin = () => { if (ok) return; ok = true; el.removeEventListener('transitionend', onEnd); done(); };
        const onEnd = e => { if (e.target === el) fin(); };
        el.addEventListener('transitionend', onEnd);
        setTimeout(fin, ms);
    }

    // Arrastre para cerrar (maqueta): el panel sigue al dedo; se cierra si pasó un tercio o el gesto fue rápido.
    function dragToClose(handle, panel, axis, onClose) {
        if (!handle || !panel) return;
        let suppress = false;
        handle.addEventListener('dragstart', e => e.preventDefault());
        handle.addEventListener('click', e => { if (suppress) { e.preventDefault(); e.stopPropagation(); suppress = false; } }, true);
        handle.addEventListener('pointerdown', e => {
            if (!isPhone() || e.button !== 0 || e.target.closest('input, select, textarea, a, button')) return;
            const pos = ev => axis === 'x' ? [-(ev.clientX - e.clientX), ev.clientY - e.clientY] : [ev.clientY - e.clientY, ev.clientX - e.clientX];
            const size = axis === 'x' ? panel.offsetWidth : panel.offsetHeight, t0 = performance.now();
            let drag = false, d = 0;
            const put = v => { panel.style.transform = axis === 'x' ? `translateX(${-v}px)` : `translateY(${v}px)`; };
            const stop = () => { document.removeEventListener('pointermove', move); document.removeEventListener('pointerup', up); document.removeEventListener('pointercancel', up); };
            const move = ev => {
                if (!panel.isConnected || panel.classList.contains('is-closing')) return stop();
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

    // ---- Hojas (modales) ----
    let volverFoco = null;
    function abrirHoja(titulo, contenido) {
        if (!sheetRoot) return;
        if (!sheetRoot.innerHTML) volverFoco = document.activeElement;
        sheetRoot.innerHTML = '';
        const bg = document.createElement('div'); bg.className = 'sheet-bg'; bg.dataset.act = 'close';
        const s = document.createElement('div'); s.className = 'sheet'; s.setAttribute('role', 'dialog'); s.setAttribute('aria-modal', 'true'); s.setAttribute('aria-label', titulo);
        s.innerHTML = '<span class="sheet-grip" aria-hidden="true"></span>';
        if (titulo) { const h = document.createElement('h2'); h.textContent = titulo; s.append(h); }
        s.append(...contenido);
        sheetRoot.append(bg, s);
        if (app) app.inert = true;
        document.body.style.overflow = 'hidden';
        $('input:not([type=hidden]), select, textarea, button:not([data-act=close])', s)?.focus();
        $$('.sheet-grip, .sheet > h2', s).forEach(h => dragToClose(h, s, 'y', cerrarHoja));
        if (window.htmx) window.htmx.process(s);
    }
    function cerrarHoja() {
        if (!sheetRoot?.innerHTML) return;
        sheetRoot.innerHTML = '';
        if (app) app.inert = false;
        document.body.style.overflow = '';
        if (volverFoco?.isConnected) volverFoco.focus();
        volverFoco = null;
    }
    // Lo que htmx trae del servidor para una hoja (#hoja: mover, dar de baja) se muestra como hoja.
    document.addEventListener('htmx:afterSwap', e => {
        if (e.detail.target !== sheetRoot) return;
        const hoja = $('#hoja', sheetRoot);
        if (!hoja) return;
        hoja.removeAttribute('id');
        abrirHoja(hoja.dataset.titulo || '', [hoja]);
    });
    // <details data-hoja-detalles>: sin JavaScript se despliega en el lugar; con JavaScript, su contenido abre una hoja.
    document.addEventListener('click', e => {
        const resumen = e.target.closest('details[data-hoja-detalles] > summary');
        if (!resumen) return;
        e.preventDefault();
        const lista = resumen.parentElement.querySelector('.sheet-list');
        const copia = lista.cloneNode(true);
        abrirHoja(lista.dataset.titulo || resumen.textContent.trim(), [copia]);
    });
    // Un formulario que volvió con errores abre su hoja de inmediato.
    $$('details[data-hoja-detalles][open]').forEach(d => { d.open = false; const l = $('.sheet-list', d); abrirHoja(l.dataset.titulo || '', [l.cloneNode(true)]); });

    // ---- Menú lateral del teléfono ----
    const drawer = $('#mobile-menu');
    const drawerHit = (e, d) => { const r = d.getBoundingClientRect(); return e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom; };
    function closeDrawer(d) {
        if (!d?.open || d.classList.contains('is-closing')) return;
        if (reduce()) { d.close(); return; }
        d.classList.add('is-closing');
        d.style.transform = ''; d.style.transition = '';
        afterTransition(d, 360, () => { if (d.open) d.close(); });
    }
    document.addEventListener('cancel', e => { if (e.target.matches?.('.mobile-drawer') && e.cancelable) { e.preventDefault(); closeDrawer(e.target); } }, true);
    document.addEventListener('close', e => { if (e.target.matches?.('.mobile-drawer')) { e.target.classList.remove('is-closing'); e.target.style.transform = ''; e.target.style.transition = ''; } }, true);
    if (drawer) dragToClose(drawer, drawer, 'x', () => closeDrawer(drawer));

    // ---- Clics ----
    document.addEventListener('click', e => {
        if (drawer?.open && e.target === drawer && !drawerHit(e, drawer)) closeDrawer(drawer);
        if (!e.target.closest('.umenu-wrap')) $$('.umenu').forEach(m => { m.hidden = true; m.previousElementSibling?.setAttribute('aria-expanded', 'false'); });
        const t = e.target.closest('[data-act]');
        if (!t) return;
        const a = t.dataset.act;
        if (a === 'mobile-menu' && drawer && isPhone()) { t.focus(); drawer.showModal(); return; }
        if (a === 'mobile-menu-close') { closeDrawer(drawer); return; }
        if (a === 'close') { e.preventDefault(); cerrarHoja(); return; }
        if (a === 'menu-crias' && isPhone()) {
            const tpl = $('#hoja-crias');
            if (tpl) { e.preventDefault(); abrirHoja('', [tpl.content.cloneNode(true)]); }
            return;
        }
        if (a === 'umenu') {
            const m = t.nextElementSibling, abrir = m.hidden;
            $$('.umenu').forEach(x => { x.hidden = true; x.previousElementSibling?.setAttribute('aria-expanded', 'false'); });
            m.hidden = !abrir; t.setAttribute('aria-expanded', String(abrir));
            return;
        }
        if (a === 'eye') {
            const i = t.previousElementSibling;
            i.type = i.type === 'password' ? 'text' : 'password';
            t.setAttribute('aria-label', i.type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
        }
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (sheetRoot?.innerHTML) cerrarHoja();
        $$('.umenu:not([hidden])').forEach(m => { m.hidden = true; const b = m.previousElementSibling; b?.setAttribute('aria-expanded', 'false'); b?.focus(); });
    });

    // ---- Pestañas de la ficha ----
    $$('[data-pestanas]').forEach(barra => {
        const pestanas = $$('[role=tab]', barra);
        const mostrar = (p, foco) => {
            pestanas.forEach(x => { const on = x === p; x.classList.toggle('on', on); x.setAttribute('aria-selected', String(on)); x.tabIndex = on ? 0 : -1; $(x.getAttribute('href'))?.classList.toggle('on', on); });
            if (foco) p.focus();
            requestAnimationFrame(() => ajustarArbol(true));
        };
        pestanas.forEach(p => p.addEventListener('click', e => { e.preventDefault(); mostrar(p); history.replaceState(null, '', p.getAttribute('href')); }));
        barra.addEventListener('keydown', e => {
            const i = pestanas.indexOf(document.activeElement);
            if (i < 0 || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
            e.preventDefault();
            const j = e.key === 'Home' ? 0 : e.key === 'End' ? pestanas.length - 1 : (i + (e.key === 'ArrowRight' ? 1 : -1) + pestanas.length) % pestanas.length;
            mostrar(pestanas[j], true);
        });
        const inicial = pestanas.find(p => p.getAttribute('href') === location.hash) ?? pestanas[0];
        if (inicial) mostrar(inicial);
    });

    // ---- Filtro de Mis canarios (chips y texto), sobre las tarjetas ya dibujadas ----
    const filtro = $('[data-filtro-canarios]');
    if (filtro) {
        let elegido = 'todos';
        const texto = $('[data-filtro-texto]', filtro), vacio = $('[data-filtro-vacio]');
        const sinTildes = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        const aplicar = () => {
            const q = sinTildes(texto.value.trim());
            let visibles = 0;
            $$('#bird-grid .bird-card').forEach(c => {
                const ok = (elegido === 'todos' || c.dataset.sexo === elegido || c.dataset.estado === elegido) && (!q || sinTildes(c.dataset.texto).includes(q));
                c.hidden = !ok; if (ok) visibles++;
            });
            if (vacio && $$('#bird-grid .bird-card').length) vacio.hidden = visibles > 0;
        };
        $$('[data-filtro]', filtro).forEach(b => b.addEventListener('click', () => {
            elegido = b.dataset.filtro;
            $$('[data-filtro]', filtro).forEach(x => { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', String(x === b)); });
            aplicar();
        }));
        texto.addEventListener('input', aplicar);
        document.addEventListener('click', e => { if (e.target.closest('[data-filtro-limpiar]')) { texto.value = ''; $('[data-filtro="todos"]', filtro)?.click(); texto.focus(); } });
    }

    // El árbol arranca en el ejemplar actual y avisa cuando hay generaciones fuera de pantalla.
    function ajustarArbol(centrar = false) {
        const ped = $('.ped-scroll');
        if (!ped || !ped.clientWidth) return;
        const scrolls = ped.scrollWidth > ped.clientWidth + 2;
        ped.parentElement.classList.toggle('scrolls', scrolls);
        const self = $('.pcard.self', ped);
        if (centrar && scrolls && self) {
            ped.scrollLeft += self.getBoundingClientRect().left - ped.getBoundingClientRect().left - 12;
        }
    }
    requestAnimationFrame(() => ajustarArbol(true));
    window.addEventListener('resize', () => ajustarArbol());

    // ---- Jaulas: la tarjeta que se abre debajo queda marcada, como la maqueta ----
    document.addEventListener('click', e => {
        const t = e.target.closest('.cage-grid a.cage');
        if (!t) return;
        $$('.cage-grid a.cage').forEach(c => {
            c.classList.toggle('sel', c === t);
            if (c === t) c.setAttribute('aria-current', 'true'); else c.removeAttribute('aria-current');
        });
    });

    // ---- Árbol genealógico: tocar una tarjeta muestra el parentesco debajo, como la maqueta ----
    document.addEventListener('click', e => {
        const t = e.target.closest('[data-ped]');
        const d = $('#ped-detail');
        if (!t || !d) return;
        e.preventDefault();
        $$('[data-ped]').forEach(p => p.classList.toggle('on', p === t));
        const rol = t.dataset.rol.replace('P–', 'Línea P–').replace(/^M–/, 'Línea M–');
        d.replaceChildren(Object.assign(document.createElement('strong'), { textContent: rol }),
            Object.assign(document.createElement('span'), { textContent: t.dataset.anillo || 'Sin registro en el criadero' }));
        if (t.matches('a[href]')) d.append(Object.assign(document.createElement('a'), { className: 'btn btn-soft sm', href: t.href, textContent: 'Abrir ficha ›' }));
    });

    // ---- «¿Cómo se llegó a este resultado?» se ve cerrado en el teléfono (maqueta), también tras cada cálculo con htmx ----
    const cerrarEnMovil = (r = document) => { if (isPhone()) $$('details[data-cerrar-movil]', r).forEach(d => { d.open = false; }); };
    cerrarEnMovil();
    document.addEventListener('htmx:afterSwap', e => cerrarEnMovil(e.detail.target));

    // ---- Agregar otro pichón: copia la plantilla con el índice siguiente ----
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-agregar-pichon]');
        if (!b) return;
        const form = b.closest('form'), lista = $('[data-pichones]', form), tpl = $('template[data-plantilla-pichon]', form);
        if (!lista || !tpl) return;
        const n = lista.children.length;
        const html = tpl.innerHTML.replaceAll('__N__', String(n));
        lista.insertAdjacentHTML('beforeend', html);
        const nueva = lista.lastElementChild;
        const num = $('[data-numero-pichon]', nueva); if (num) num.textContent = String(n + 1);
        // Como la maqueta: el correlativo sigue al de la tarjeta anterior y el botón cuenta los pichones.
        const previo = $$('input[name$="[anillo_correlativo]"]', lista).at(-2)?.value ?? '';
        const campo = $('input[name$="[anillo_correlativo]"]', nueva);
        if (campo && /^\d+$/.test(previo)) campo.value = String(+previo + 1).padStart(Math.max(3, previo.length), '0');
        const total = $('[data-total-pichones]', form);
        if (total) { total.textContent = String(n + 1); total.nextSibling.textContent = ' pichones'; }
        campo?.focus();
    });

    // ---- Pantalla sin conexión (la maqueta la mostraba al perder la red) ----
    let pantalla = null;
    addEventListener('offline', () => {
        if (pantalla) return;
        pantalla = document.createElement('div');
        pantalla.className = 'sin-conexion'; pantalla.setAttribute('role', 'alertdialog'); pantalla.setAttribute('aria-modal', 'true');
        pantalla.innerHTML = '<section class="err-state tone-danger"><h1>Sin conexión a internet</h1><p class="lead">Sin conexión no podemos consultar ni guardar datos. Lo que no alcanzaste a guardar no quedó registrado. Revisa tu señal o el wifi.</p><div class="err-actions"><button class="btn btn-primary big" type="button">Reintentar</button></div></section>';
        $('button', pantalla).addEventListener('click', () => { if (navigator.onLine) location.reload(); });
        document.body.append(pantalla);
        $('button', pantalla).focus();
    });
    addEventListener('online', () => { pantalla?.remove(); pantalla = null; });
})();
