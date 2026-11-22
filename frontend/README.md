# frontend/

Aquí trabaja el equipo de frontend: las pantallas en HTML embebido en PHP y sus estilos. Todo funciona con formularios y enlaces; htmx se permite como mejora opcional por autorización del PM del 22/11/2026 (contrato §8), sin API JSON ni dependencia de JavaScript para completar los flujos.

## Qué va en cada carpeta

| Carpeta | Qué va | Ejemplo |
|---|---|---|
| `views/<modulo>/` | Una vista por pantalla, con el nombre que indica el contrato (§6). | `views/cruces/formulario.php` |
| `views/errores/` | Páginas de error y de estado (contrato §7). | `views/errores/404.php` |
| `css/` | Hojas de estilo (contrato §1). Se publican en la URL `/css/`. | `css/app.css` |

El backend llama a cada vista con `render('cruces/formulario', $datos)`, y esa llamada abre `frontend/views/cruces/formulario.php`.

## Antes de programar

1. [`docs/frontend.md`](../docs/frontend.md): pantallas, mensajes, experiencia de uso en el teléfono y términos del dominio.
2. [`docs/contrato-back-front.md`](../docs/contrato-back-front.md): qué variables recibe cada vista, cómo se llaman los campos de cada formulario y qué helpers puedes usar.
3. Los mockups de Figma de la pantalla (tarea de diseño de la épica E0).

## Reglas que no se negocian

- Una vista **nunca** consulta la base de datos ni llama a servicios: solo usa las variables que recibe.
- Todo dato se imprime con `e()`, para evitar XSS.
- Cada formulario que modifica datos lleva el campo oculto `_csrf`.
- Los nombres de los campos son los del contrato. Si necesitas un dato o un campo que no está, se cambia el contrato en un pull request; no se inventa en la vista.
- Diseño primero para el teléfono: sin desplazamiento horizontal desde 360 px de ancho.
