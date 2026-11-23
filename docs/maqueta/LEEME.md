# TuCri · Referencia navegable de frontend

La carpeta `public/` reproduce los archivos públicos del despliegue de Vercel `dpl_dGBFGhjLwmMmXGhJ3pobG5PNVfpE`, publicado el 22/11/2026. El alias [tucriadero-mockup.vercel.app](https://tucriadero-mockup.vercel.app/) puede cambiar. Para comparar pantallas con esta revisión, abre el visor local desde el mismo commit.

La base de diseño incorpora los ajustes del 19/11. El despliegue contiene además una **prueba de estilos del 22/11** en `tablero.css`. La decisión sobre trasladar esa variante al producto corresponde al PM. Los iconos de pestaña y el pie sin enlace visible de créditos sí forman parte de esta publicación; las atribuciones siguen en `public/creditos.html`.

El PDF del árbol de issues refleja la planificación del **16/11/2026**. Consulta GitHub para el estado y las asignaciones vigentes.

## Cómo abrirla

Requiere Node.js. Desde esta carpeta:

```
node preview.mjs
```

Después abrir <http://127.0.0.1:4173/#login>. Cualquier correo y contraseña sirve para entrar. Abrir `index.html` con doble clic no se recomienda, porque el navegador puede bloquear las fuentes.

## Qué es y qué no es

- Es HTML, CSS y JavaScript estático. Los datos son de ejemplo y viven en memoria: al recargar la página vuelven a su estado inicial.
- **No es código para copiar en PHP.** Las vistas se programan según `docs/frontend.md` y `docs/contrato-back-front.md` del repositorio. La maqueta muestra cómo se ven y cómo se usan.
- Si la maqueta y un documento vigente difieren, se consulta al PM antes de trasladar esa diferencia al producto.

## Diferencias con los documentos del repositorio

Esta comparación recoge las diferencias anotadas el 19/11. Antes de programar, consulta el contrato vigente y las decisiones posteriores del PM.

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

- `public/index.html`: entrada de la maqueta navegable. La subcarpeta estática `public/figma/` también está en el despliegue y se conserva para reproducir su contenido. Usa la entrada principal para revisar las pantallas.
- `public/tucriadero.js`: pantallas y comportamiento.
- `public/catalogo.js`: nomenclador 2026 (880 variedades).
- Hojas de estilo, en este orden de carga: `tucriadero.css`, `tema.css`, `mejoras.css` y `tablero.css`. Cada una ajusta a la anterior; los colores están en las variables de `:root`.
- `public/assets/`: `jaula.png` y `canario.png` (ilustraciones entregadas por el PM), las fotos del inicio y del acceso, fuentes IBM Plex Sans y Archivo (licencia OFL, incluida) y fondos.
- `public/creditos.html`: origen de las ilustraciones, de las fotos y de la tipografía.
