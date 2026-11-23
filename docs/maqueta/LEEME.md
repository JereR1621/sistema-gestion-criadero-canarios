# TuCri: maqueta aprobada para el equipo de frontend

19/11/2026. Es la referencia visual y de experiencia de uso para las vistas PHP de `frontend/views/`. Incluye los ajustes que pidió el cliente el 19/11/2026. Es la misma versión publicada en <https://tucriadero-mockup.vercel.app/>.

## Cómo abrirla

Requiere Node.js. Desde esta carpeta:

```
node preview.mjs
```

Después abrir <http://127.0.0.1:4173/#login>. Cualquier correo y contraseña sirve para entrar. Abrir `index.html` con doble clic no se recomienda, porque el navegador puede bloquear las fuentes.

## Qué es y qué no es

- Es HTML, CSS y JavaScript estático. Los datos son de ejemplo y viven en memoria: al recargar la página vuelven a su estado inicial.
- **No es código para copiar en PHP.** Las vistas se programan según `docs/frontend.md` y `docs/contrato-back-front.md` del repositorio. La maqueta muestra cómo se ven y cómo se usan.
- Es un diseño local: los ajustes del cliente están en la maqueta, pero no en los documentos del repositorio. Si la maqueta y un documento no coinciden, se avisa al PM antes de programar.

## Diferencias con los documentos del repositorio

Estos puntos de la maqueta todavía no figuran en el repositorio. Consultar al PM antes de programarlos:

| Tema | En la maqueta | En el repositorio |
|---|---|---|
| Nombre | **TuCri** | TuCriadero |
| Baja de un canario | Solo la fecha; en todas partes dice «Dado de baja», sin ningún motivo | `motivo` (`venta` / `muerte` / `otra`) y fecha |
| Menú | «Inicio» y «Libro de crías ▾», que abre Libro de crías, Canarios y Jaulas; en el teléfono, Inicio, Libro de crías y Perfil | Sin indicación |
| Factores ocultos | marfil, pastel, ópalo, satiné, ino, topacio, ónix, mogno, cobalto, eumo, bruno, isabel, úrucum y pico amarillo (sin mosaico ni ágata) | Sale del catálogo de factores (T12, #148) |
| Jaulas | En orden numérico; la jaula grande, al final | Sin orden definido |
| Parejas | Anillo completo de los dos, macho arriba y hembra abajo | Sin indicación |
| Imágenes | Una sola ilustración de canario para todos y una de jaula; foto del aviario en el inicio y en el acceso | Sin indicación |
| Inicio | Un solo botón, «Libro de crías», en lugar de «Registrar pareja» y «Registrar canario» | Sin indicación |

## Pantallas

| Ruta en la maqueta | Pantalla |
|---|---|
| `#login`, `#registro`, `#recuperar` | Acceso, creación de cuenta y recuperación |
| `#inicio` | Inicio del criadero |
| `#canarios`, `#canario/c150`, `#canario-nuevo` | Lista, ficha y registro de canarios |
| `#jaulas`, `#jaulas/2` | Jaulas y detalle de una jaula |
| `#crias`, `#cruce/x01`, `#registro-cria`, `#anillar/x01` | Libro de crías, ficha de cruce, registro de pareja y anillado |
| `#calculadora` | Calculadora genética |
| `#buscar` | Búsqueda, incluidos los dados de baja |
| `#perfil` | Perfil del criador |

### Pantallas de error

Corresponden a la tabla de errores del contrato y a SRS §2.5.

| Ruta en la maqueta | Vista del contrato | Código | Diseño |
|---|---|---|---|
| `#404` (también cualquier ruta inexistente) | `errores/404` | 404 | Dentro del menú de la aplicación |
| `#403` | `errores/403` | 403 | Dentro del menú de la aplicación |
| `#sesion` | `errores/sesion` | 419 | Pantalla de acceso, con enlace para ingresar de nuevo |
| `#mantenimiento` | `errores/mantenimiento` | 503 | Pantalla de acceso |
| `#500` | `errores/500` | 500 | Pantalla de acceso, sin detalles técnicos |
| `#sin-conexion` | — | — | Pantalla de acceso. Aparece sola si el navegador pierde la conexión |

En la maqueta, el botón «Reintentar» vuelve al inicio. En la aplicación real debe repetir la petición o recargar la página.

## Lo que la maqueta no muestra

La maqueta incluye solo 2 de los 10 mensajes de validación de `docs/frontend.md` §4: el de la hembra portadora y el de aceptar el uso de los datos. Los otros 8 se programan con el texto exacto de esa tabla, junto al campo afectado:

- anillo repetido;
- correo o contraseña incorrectos;
- número de jaula repetido;
- sexo que no corresponde en un cruce;
- variedad incompatible con el sexo;
- factor sin regla de herencia;
- canario reservado o dado de baja en un cruce (en la maqueta la baja no lleva motivo; ver la tabla de diferencias);
- reservar un canario dado de baja.

## Archivos

- `public/index.html`: página única.
- `public/tucriadero.js`: pantallas y comportamiento.
- `public/catalogo.js`: nomenclador 2026 (880 variedades).
- Hojas de estilo, en este orden de carga: `tucriadero.css`, `tema.css`, `mejoras.css` y `tablero.css`. Cada una ajusta a la anterior; los colores están en las variables de `:root`.
- `public/assets/`: `jaula.png` y `canario.png` (ilustraciones entregadas por el PM), las fotos del inicio y del acceso, fuentes IBM Plex Sans y Archivo (licencia OFL, incluida) y fondos.
- `public/creditos.html`: origen de las ilustraciones, de las fotos y de la tipografía.
