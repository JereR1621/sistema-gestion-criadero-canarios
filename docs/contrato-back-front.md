# Contrato backend–frontend

**Versión 1.0 · 22/11/2026, con los ajustes del cliente del 09/11/2026 ([`ajustes-cliente.md`](ajustes-cliente.md)) y las decisiones del PM indicadas en §8.** Acuerdo de interfaces de la épica E0: su entrada en vigor requiere integrar el PR #183 con aprobación de un integrante de backend y uno de frontend. Todo cambio posterior sigue ese mismo circuito. Este documento define lo que debe entregar cada módulo; no acredita que todas sus rutas ya estén implementadas (véase §9).

Fuente: SRS v1.1 (`docs/srs/`), los ajustes del cliente (`docs/ajustes-cliente.md`), `docs/backend.md` y `docs/frontend.md`.

## 1. Cómo se comunican las dos partes

El stack es PHP con HTML embebido, así que **no hay una API JSON**. El contrato es la frontera entre el controlador (backend) y la vista (frontend):

```text
Navegador ──GET/POST──▶ public/index.php ──▶ Controlador ──▶ Servicios / Modelos
                                                  │
                                                  └──▶ render('modulo/vista', $datos) ──▶ frontend/views/modulo/vista.php
```

| Responsabilidad | Backend | Frontend |
|---|---|---|
| Rutas, permisos y validaciones | ✔ | |
| Consultas y reglas de negocio | ✔ | |
| Datos que recibe cada vista (sección 5) | Los prepara | Los usa tal cual |
| HTML, estilos CSS y textos de la pantalla | | ✔ |
| Nombres de los campos de cada formulario (sección 6) | Los lee | Los usa tal cual |
| Textos de los mensajes de error | Los redacta | Los ubica en la pantalla |

Reglas fijas:
- **Estilos CSS:** los archivos `.css` van en `frontend/css/` y se publican en la URL `/css/`. Las vistas no incluyen bloques `<style>`. Se permite únicamente el atributo `style="width: …%"` para la barra de un porcentaje: la vista convierte el dato a número finito, lo limita a `0–100`, lo formatea con punto decimal y lo escapa con `e()`. Nunca se copia una cadena de la petición a ese atributo; el resto de los estilos va en las hojas CSS. La barra acompaña al porcentaje escrito, que sigue visible sin estilos ni JavaScript.
- **Las vistas no consultan la base de datos ni llaman a servicios.** Solo leen las variables que reciben y usan los helpers de la sección 3.
- **Las vistas nunca reciben datos de otro criadero.** El backend ya entrega todo filtrado.
- Después de un POST exitoso, el backend **redirige** a otra página (patrón POST → redirección → GET) con un mensaje *flash*. Así, recargar la página no repite el envío.
- Si hay errores de validación, el backend **vuelve a mostrar la misma vista** con `$errores` y `$viejo` (sección 4).

## 2. Variables disponibles en todas las vistas

| Variable | Contenido |
|---|---|
| `$sesion` | `null` si no hay sesión; si la hay, `['usuario_id' => int, 'nombre' => string, 'rol' => 'criador' \| 'admin']`. |
| `$csrf` | Token para el campo oculto `_csrf` de cada formulario. |
| `$flash` | `null` o `['tipo' => 'exito' \| 'error' \| 'aviso', 'mensaje' => string]`. |
| `$errores` | `[]` o `['campo' => 'mensaje', ...]`. La clave `_general` guarda un error que no es de un campo. |
| `$viejo` | Los valores que envió el usuario, para volver a llenar el formulario (nunca la contraseña). |
| `$anillo_duplicado_url` | `null` o `string` con la URL de la ficha del canario que ya posee el anillo cuando falla la validación de unicidad (RF008). |

## 3. Helpers que entrega el backend

| Helper | Uso |
|---|---|
| `e($texto)` | Escapa un texto para mostrarlo (XSS, SEG-005). **Todo dato se imprime con `e()`.** |
| `ruta('nombre', [params])` | Arma una URL a partir del nombre de la ruta (sección 6), sin escribir rutas a mano. |
| `error('campo')` | Mensaje de error del campo, o `''`. |
| `viejo('campo', $porDefecto)` | Valor anterior del campo, o el valor por defecto. |
| `fecha($aaaa_mm_dd)` | Convierte `2026-11-02` en `02/11/2026` para mostrarlo. |

## 4. Formularios

- Los formularios que modifican datos usan `method="post"` y llevan `<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">`. Las búsquedas y los filtros usan `GET`.
- Los nombres de los campos son los de la sección 6, en *snake_case*.
- **Fechas:** se envían como `AAAA-MM-DD` (`<input type="date">`) y se muestran como `DD/MM/AAAA`.
- **Campos opcionales vacíos** se envían como cadena vacía; el backend los guarda como "sin dato".
- **Listas:** `portadores[]` (ids de factores); pichones con índice, `pichones[0][anillo_correlativo]`. Los errores de una lista usan la clave con puntos: `pichones.0.anillo_correlativo`.
- **Anillo duplicado (RF008):** Los mensajes en `$errores` son siempre texto plano sin HTML. Si un anillo ya existe en el criadero, el backend envía el mensaje de texto en `$errores` y la ruta de destino en `$anillo_duplicado_url` para que la vista arme el enlace `&lt;a&gt;` de forma segura.
- **Selección de variedad sin JavaScript (RF029):** `<input list="variedades" name="variedad_codigo">` con un `<datalist>` de las 880 variedades (`código – nombre`). Vacío = "sin determinar".

## 5. Estructuras que reciben las vistas

Son arreglos asociativos de PHP. Los campos `*_texto` y `url` los arma el backend, para que la vista no tenga que calcular nada. El formato visible estándar del anillo (`anillo_texto`) es separado por puntos medios: `socio · año · correlativo · club`.

**`CanarioResumen`**
```php
[
  'id' => 12,
  'anillo' => ['socio' => '7', 'anio' => 2024, 'correlativo' => '150', 'club' => 'CLUB'],
  'anillo_texto' => '7 · 2024 · 150 · CLUB',
  'sexo' => 'macho',                       // 'macho' | 'hembra' | 'sin_determinar'
  'variedad' => ['codigo' => 'D.049', 'nombre' => 'ROJO INTENSO'],   // o null = sin determinar
  'ubicacion_texto' => 'Jaula 25',         // 'Jaula N' | 'Jaula grande' | 'Sin ubicación'
  'estado' => 'activo',                    // 'activo' | 'reservado' | 'baja' (reservado: AC-03)
  'url' => '/canarios/12',
]
```
Un canario reservado sigue siendo un canario activo del criadero (no está dado de baja): aparece en listados y búsquedas con la etiqueta «Reservado» (AC-03).

**`CanarioFicha`**: todo lo de `CanarioResumen`, más:
```php
[
  'fecha_anillado' => '2024-05-10', 'fecha_nacimiento' => null, 'procedencia' => null,
  'padre' => CanarioResumen|null, 'madre' => CanarioResumen|null,
  'hermanos' => [CanarioResumen, ...],
  'medios_hermanos' => [CanarioResumen + ['en_comun' => 'padre' | 'madre'], ...],
  'crias' => [CanarioResumen, ...],
  'cruces' => [CruceResumen, ...],
  'factores_manifestados' => [['nombre' => 'MARFIL', 'tipo_herencia' => 'ligado_sexo', 'dosis' => 1], ...],   // solo lectura (RF033); dosis = 1 | 2
  'portadores' => [['factor' => 'ÓPALO', 'origen' => 'deducido'], ...],   // 'deducido' | 'criador' (RF034)
  'baja' => null,                          // o ['motivo' => 'venta' | 'muerte' | 'otra', 'fecha' => '2026-10-01']
  'arbol' => ['padre' => NodoArbol|null, 'madre' => NodoArbol|null],   // AC-01: 3 generaciones
]
```

**`NodoArbol`** (AC-01): `CanarioResumen` + `['generacion' => 1 | 2 | 3, 'padre' => NodoArbol|null, 'madre' => NodoArbol|null]`. La generación 1 son los padres, la 2 los abuelos y la 3 los bisabuelos; en la generación 3, `padre` y `madre` son siempre `null`. `null` = antepasado sin registro: la vista muestra «Sin registro».

**`JaulaResumen`**: `['id', 'numero', 'ocupantes' => int, 'cruce_activo' => CruceResumen|null, 'url']`

**`CruceResumen`**
```php
[
  'id' => 5, 'macho' => CanarioResumen, 'hembra' => CanarioResumen,
  'jaula_numero' => 25, 'temporada' => 2026,
  'fecha_inicio' => '2026-09-01', 'fecha_fin' => null, 'activo' => true,
  'pichones' => 3, 'url' => '/cruces/5',
]
```

**`FichaCria`**: `CruceResumen` + `['lista_pichones' => [CanarioResumen, ...]]` (RF027).

**`ResultadoCruce`** (RF035 a RF039)
```php
[
  'pio' => bool,                              // true si un progenitor es melanico y el otro lipocromico
  'machos'  => [['variedad' => ['codigo' => 'D.051', 'nombre' => '…'] | null,
                 'factores' => ['JASPE'], 'dobles' => ['JASPE'],
                 'porcentaje' => 50.0, 'portador_de' => ['MARFIL']], ...],
  'hembras' => [ ... misma forma ... ],
  'no_calculables' => ['nombre de factor', ...],   // RF039: incluye «ala blanca» o «alas grises» si algún progenitor tiene ese rasgo
  'aproximado' => false,                            // true si combina 2 o más factores ligados al sexo (SUP-004)
]
```
Los nombres de los factores se entregan como están en el catálogo (por ejemplo, `MARFIL`, `ÓPALO` y `JASPE`); la vista puede adaptar su presentación sin cambiar su identidad. `factores` contiene los manifestados y `portador_de` los portados. Cada fila de machos y hembras incluye `dobles`, una lista de nombres de factores manifestados con dosis 2; es `[]` si no hay ninguno y siempre es un subconjunto de `factores`. Hoy distingue `JASPE` SD (ausente de `dobles`) de DD (presente), incluso si `variedad` es `null`. El ejemplo muestra la forma de una fila, no un cruce concreto.

Si `pio` es `true`, no se calculan ni muestran porcentajes genéticos: `machos` y `hembras` son `[]` y la vista muestra solo un aviso informativo. Si `variedad` es `null`, la combinación no corresponde a ninguna variedad del nomenclador y se muestra la lista de `factores`, indicando SD/DD según `dobles` para el jaspe (RF035).

**`VariedadOpcion`**: `['codigo', 'nombre', 'sexo' => 'macho' | 'hembra' | null]` · **`FactorOpcion`**: `['id', 'nombre', 'tipo_herencia' => 'ligado_sexo' | 'recesivo' | 'dominante' | 'codominante']` · **`ComposicionFactor`**: `['factor_id', 'nombre', 'dosis' => 1 | 2]`.

En el formulario de variedad, cada factor seleccionado lleva su dosis. La dosis debe ser `1` o `2`; hoy solo el factor jaspe usa una diferencia: SD usa `dosis = 1` y DD usa `dosis = 2`.

## 6. Rutas, vistas y formularios

Las rutas con `{id}` responden **404** si el registro no existe **o es de otro criadero** (RF003): para el usuario, ambos casos son iguales. La paginación en listados largos es de 50 elementos por página.

### Cuentas y acceso (RF001–RF006)

| Nombre | Método y ruta | Vista | Datos para la vista | Campos del formulario | RF |
|---|---|---|---|---|---|
| registro | GET /registro | cuentas/registro | — | — | RF001 |
| registro.guardar | POST /registro | (redirige a /inicio) | — | nombre, correo, contrasena, consentimiento (casilla obligatoria que acepta el uso de los datos para el servicio, POL-001) | RF001 |
| ingreso | GET /ingresar | cuentas/ingresar | — | — | RF002 |
| ingreso.enviar | POST /ingresar | (redirige a /inicio) | — | correo, contrasena. Cuenta desactivada → `_general` | RF002, RF005 |
| salida | POST /salir | (redirige a /ingresar) | — | — | SEG-004 |
| recuperar | GET /recuperar | cuentas/recuperar | — | — | Decisión PM 22/11 (§8) |
| recuperar.enviar | POST /recuperar | (redirige a /ingresar con el mismo aviso, exista o no la cuenta) | — | correo | Decisión PM 22/11 (§8) |
| recuperar.nueva | GET /recuperar/nueva?token= | cuentas/restablecer | `$token` (cadena) | — | Decisión PM 22/11 (§8) |
| recuperar.guardar | POST /recuperar/nueva | (redirige a /ingresar) | — | token, contrasena | Decisión PM 22/11 (§8) |
| perfil | GET /perfil | cuentas/perfil | `$perfil` = nombre, correo, numero_socio, club | — | RF004 |
| perfil.guardar | POST /perfil | (redirige a /perfil) | — | numero_socio, club | RF004 |
| inicio | GET /inicio | inicio/index | — (el buscador va en la vista; si el rol es admin, va a /admin/cuentas) | — | RNF003 |

### Canarios (RF007–RF013, RF024)

| Nombre | Método y ruta | Vista | Datos para la vista | Campos del formulario | RF |
|---|---|---|---|---|---|
| canarios | GET /canarios?pagina=N | canarios/lista | `$canarios` [CanarioResumen] (solo activos, 50 por página), `$paginacion` = pagina, total_paginas | — | RF011 |
| canarios.nuevo | GET /canarios/nuevo | canarios/formulario | `$variedades` [VariedadOpcion], `$factores_portables` [FactorOpcion] (solo recesivos y ligados al sexo), `$por_defecto` = anillo_socio, anillo_club, `$posibles_padres` y `$posibles_madres` [CanarioResumen] (machos y hembras del criadero, sin el propio canario, para indicar padres a mano) | — | RF007, RF009, RF010, RF029, RF004 |
| canarios.guardar | POST /canarios | (redirige a la ficha) | — | anillo_socio, anillo_anio, anillo_correlativo, anillo_club, sexo, variedad_codigo, fecha_anillado, fecha_nacimiento, padre_id, madre_id (opcionales), procedencia, portadores[] | RF007–RF010, RF012, RF013 |
| canarios.ficha | GET /canarios/{id} | canarios/ficha | `$canario` CanarioFicha (con arbol, AC-01) | — | RF024, RF033, RF034 |
| canarios.editar | GET /canarios/{id}/editar | canarios/formulario | lo mismo que "nuevo" + `$canario` CanarioFicha | — | RF007, RF009, RF029 |
| canarios.actualizar | POST /canarios/{id} | (redirige a la ficha) | — | los mismos campos que "guardar" | RF007, RF009, RF012, RF013 |
| canarios.baja | GET /canarios/{id}/baja | canarios/baja | `$canario` CanarioResumen | — | RF011 |
| canarios.baja.guardar | POST /canarios/{id}/baja | (redirige a la ficha) | — | motivo (venta / muerte / otra), fecha | RF011 |
| canarios.ubicacion | GET /canarios/{id}/ubicacion | canarios/ubicacion | `$canario` CanarioResumen, `$jaulas` [JaulaResumen] | — | RF015, RF016 |
| canarios.ubicacion.guardar | POST /canarios/{id}/ubicacion | (redirige a la ficha) | — | jaula_id (vacío = jaula grande), fecha | RF015, RF016 |
| canarios.reserva | POST /canarios/{id}/reserva | (redirige a la ficha) | — | reservado (si / no) | AC-03 |
| canarios.pdf | GET /canarios/{id}/pdf | canarios/ficha_pdf | `$canario` CanarioFicha (con arbol). El backend convierte la vista en un PDF que se descarga. | — | AC-04 |

### Jaulas (RF014–RF017, RF026)

| Nombre | Método y ruta | Vista | Datos para la vista | Campos del formulario | RF |
|---|---|---|---|---|---|
| jaulas | GET /jaulas | jaulas/lista | `$jaulas` [JaulaResumen] | — | RF014 |
| jaulas.guardar | POST /jaulas | (redirige a /jaulas) | — | numero | RF014 |
| jaulas.ficha | GET /jaulas/{id} | jaulas/ficha | `$jaula` JaulaResumen, `$ocupantes` [CanarioResumen], `$historial` [fecha, tipo (ingreso / salida / cruce_inicio / cruce_fin), descripcion, url], ordenado por fecha | — | RF017, RF026 |

### Cruces y pichones (RF018–RF022, RF027, RF035–RF039)

| Nombre | Método y ruta | Vista | Datos para la vista | Campos del formulario | RF |
|---|---|---|---|---|---|
| cruces | GET /cruces?temporada=2026 | cruces/lista | `$temporada` (año elegido; por defecto, el año actual), `$temporadas` [años con cruces], `$cruces` [CruceResumen] de esa temporada, ordenados por fecha de inicio | — | RF018 |
| cruces.nuevo | GET /cruces/nuevo | cruces/formulario | `$machos` y `$hembras` [CanarioResumen] (solo disponibles: no reservados ni dados de baja, y con sexo conocido; AC-02), `$jaulas` [JaulaResumen] | — | RF018 |
| cruces.previsualizar | POST /cruces/previsualizar | cruces/resultado | `$propuesta` (los campos enviados), `$resultado` ResultadoCruce | macho_id, hembra_id, jaula_id, temporada, fecha_inicio | RF035–RF039 |
| cruces.guardar | POST /cruces | (redirige a la ficha de cría) | — | los mismos de "previsualizar" | RF018–RF020 |
| cruces.ficha | GET /cruces/{id} | cruces/ficha | `$cruce` FichaCria | — | RF027 |
| cruces.terminar | POST /cruces/{id}/terminar | (redirige a la ficha de cría) | — | fecha_fin | RF022 |
| pichones.nuevo | GET /cruces/{id}/pichones/nuevo | cruces/anillar | `$cruce` CruceResumen, `$variedades` [VariedadOpcion], `$por_defecto` = anillo_socio, anillo_anio, anillo_club | — | RF021 |
| pichones.guardar | POST /cruces/{id}/pichones | (redirige a la ficha de cría) | — | fecha_anillado y, por cada pichón, pichones[n][anillo_socio], [anillo_anio], [anillo_correlativo], [anillo_club], [sexo], [variedad_codigo], [fecha_nacimiento] | RF021, RF008 |

### Búsqueda (RF023, RF025, RF026)

El buscador principal de inicio y de la barra envía `GET /buscar` con `q` y un campo oculto `tipo=anillo` (AC-05). Su etiqueta indica «Anillo o correlativo»; no promete buscar variedades ni jaulas con ese mismo envío. Las búsquedas de variedad y jaula envían su `tipo` explícito desde su formulario o selector. Si falta `tipo`, el backend usa `anillo`; un valor distinto de `anillo`, `variedad` o `jaula` produce un error de campo, sin buscar en otros criaderos. No se exige un buscador libre nuevo al backend.

| Nombre | Método y ruta | Vista | Datos para la vista | RF |
|---|---|---|---|---|
| buscar | GET /buscar?tipo=anillo&q=150 | busqueda/resultados | `$tipo`, `$q`, `$resultados` [CanarioResumen], incluidos los dados de baja. Si hay una sola coincidencia, redirige directo a la ficha. | RF023 |
| buscar | GET /buscar?tipo=variedad&q=D.049 | busqueda/resultados | `$tipo`, `$q`, `$resultados` [CanarioResumen] (solo activos) | RF025 |
| buscar | GET /buscar?tipo=jaula&q=25 | (redirige a la ficha de la jaula) | — | RF026 |

### Administración (RF005, RF006, RF032). Solo rol admin; un criador recibe 403.

| Nombre | Método y ruta | Vista | Datos para la vista | Campos del formulario | RF |
|---|---|---|---|---|---|
| admin.cuentas | GET /admin/cuentas | admin/cuentas | `$cuentas` [id, nombre, correo, estado, fecha_registro]. Nunca datos del criadero. | — | RF006 |
| admin.cuentas.estado | POST /admin/cuentas/{id}/estado | (redirige a /admin/cuentas) | — | estado (activa / desactivada / cerrada; cerrar se hace a pedido del criador y guarda fecha_cierre, POL-001 y DAT-002; reactivar una cerrada anula el cierre y deja fecha_cierre en NULL; Decisión PM 22/11) | RF005 |
| admin.variedades | GET /admin/variedades?q= | admin/variedades | `$variedades` [VariedadOpcion + factores [ComposicionFactor]] | — | RF032 |
| admin.variedades.nueva | GET /admin/variedades/nueva | admin/variedad | `$variedad` (null), `$factores` [FactorOpcion] | — | RF032 |
| admin.variedades.crear | POST /admin/variedades | (redirige a /admin/variedades) | — | codigo, nombre, sexo, tipo (`melanico` / `lipocromico`), rasgo (`ala_blanca` / `alas_grises` / vacío: `null` o `''`), factores[n][factor_id], factores[n][dosis] | RF032 |
| admin.variedades.editar | GET /admin/variedades/{codigo} | admin/variedad | `$variedad` = VariedadOpcion + `tipo`, `rasgo`, `factores` [ComposicionFactor]; `$factores` [FactorOpcion] (opciones del formulario) | — | RF032 |
| admin.variedades.guardar | POST /admin/variedades/{codigo} | (redirige a /admin/variedades) | — | nombre, sexo, tipo (`melanico` / `lipocromico`), rasgo (`ala_blanca` / `alas_grises` / vacío: `null` o `''`), factores[n][factor_id], factores[n][dosis] | RF032 |
| admin.variedades.eliminar | POST /admin/variedades/{codigo}/eliminar | (redirige a /admin/variedades) | — | — (rechaza con mensaje flash de error si la variedad está asignada a algún canario) | RF032 |
| admin.factores | GET /admin/factores | admin/factores | `$factores` [FactorOpcion] | — | RF032 |
| admin.factores.guardar | POST /admin/factores | (redirige a /admin/factores) | — | nombre, tipo_herencia (`ligado_sexo` / `recesivo` / `dominante` / `codominante`) | RF032 |
| admin.factores.editar | GET /admin/factores/{id} | admin/factor | `$factor` FactorOpcion | — | RF032 |
| admin.factores.actualizar | POST /admin/factores/{id} | (redirige a /admin/factores) | — | nombre, tipo_herencia (`ligado_sexo` / `recesivo` / `dominante` / `codominante`) | RF032 |

## 7. Páginas de error y de estado

| Situación | Código | Vista |
|---|---|---|
| El registro no existe o es de otro criadero | 404 | errores/404 |
| Un criador entra a una ruta de administración | 403 | errores/403 |
| El administrador entra a una ruta de criador | 403 | errores/403 |
| El token CSRF no es válido o la sesión expiró | 419 | errores/sesion (con enlace para ingresar de nuevo) |
| Modo mantenimiento (SRS §2.5) | 503 | errores/mantenimiento |
| Error inesperado | 500 | errores/500 (sin detalles técnicos) |

## 8. Acuerdos y decisiones registradas

- **PDF (AC-04):** Dompdf mediante Composer, ya declarado en `composer.json` e instalado en la estructura base. Se usa una vista HTML propia para la ficha. La presencia de la librería no acredita la implementación de `canarios.pdf`.
- **CSS, anillo y paginación:** CSS en `frontend/css/` publicado en `/css/` con la excepción limitada de §1; anillo `socio · año · correlativo · club`; listados largos de 50 elementos por página.
- **htmx (autorización del PM, 22/11/2026):** puede usarse dentro de las vistas PHP como mejora de los formularios y enlaces. Las respuestas siguen siendo HTML generado en el servidor, sin API JSON, y el flujo completo debe seguir funcionando sin JavaScript.
- **Recuperación por correo (decisión del PM, 22/11/2026):** se incorpora al producto aunque no es un RF del SRS v1.1. Las cuatro rutas están en §6. El enlace usa un token aleatorio, de un solo uso y con vencimiento de 1 hora; al restablecer se invalidan las sesiones anteriores y la contraseña nueva cumple las reglas de registro de `backend.md` §7. No reactiva cuentas desactivadas ni cerradas. Para un correo válido se muestra el mismo aviso exista o no la cuenta; los errores de formato o de disponibilidad del correo se muestran con `$errores`, sin revelar cuentas registradas. Un token inválido, usado o vencido muestra un error y no cambia la contraseña. El token se conserva al volver a mostrar `cuentas/restablecer`; la contraseña nunca se repuebla. Requiere transporte real de correo verificado antes de habilitarla públicamente.
- **Reactivación (decisión del PM, 22/11/2026):** el administrador puede reactivar una cuenta cerrada antes de la eliminación de sus datos; se anula el cierre y `fecha_cierre` vuelve a `NULL`. Un cierre posterior inicia un nuevo plazo de 90 días. No recupera datos ya eliminados.

Decidido por el PM (10/11/2026), Libro de crías: la ruta cruces lista los cruces de una temporada (macho, hembra, jaula, fecha de inicio y estado), con el botón para registrar un cruce. Es la entrada al libro de crías desde el menú; no tiene filtros por jaula ni por estado. La crea RF018.

Decidido por el PM (10/11/2026), padres a mano: el formulario de canarios tiene padre y madre opcionales (`padre_id`, `madre_id`), elegidos entre los machos y hembras del criadero. Sirven para reconstruir la genealogía de canarios que nacieron antes de usar el sistema. Un canario comprado (con procedencia) o uno anillado desde un cruce no los lleva a mano: el comprado queda sin padres y el pichón los toma del cruce. Reglas en backend.md §3.

Decidido por el PM (09/11/2026), POL-001: el consentimiento es una casilla obligatoria al crear la cuenta (`consentimiento`; el backend guarda `fecha_consentimiento`). Los derechos de acceso, rectificación y eliminación se piden por correo de soporte (SOS-004). Para eliminar, el administrador cierra la cuenta (`estado = cerrada`, RF005) y la tarea de retención borra los datos a los 90 días (DAT-002).

## 9. Aplicación del contrato al desarrollo

El contrato fija interfaces acordadas; cada issue conserva su responsable y sus criterios de aceptación. Integrarlo no cierra automáticamente la implementación de los RF ni entrega por sí solo las pantallas del frontend.

Al contrastar con `develop` `d0df249` (22/11/2026), quedan estas adaptaciones específicas, además de los módulos aún en desarrollo:

- **RF032 (#86/#87):** recibir `factores[n][factor_id]` y `factores[n][dosis]`; entregar `factores` [ComposicionFactor] en listado y edición; completar las rutas de crear/eliminar variedad y editar/actualizar factor. Las rutas fijas `nueva` deben resolverse antes de `{codigo}`. Se coordina en sus issues; este PR no modifica su código.
- **Búsqueda (RF023/RF025/RF026):** las issues de backend y frontend implementan los criterios explícitos de §6; la búsqueda libre de la maqueta no es parte de este acuerdo.
- **Recuperación por correo:** requiere entrega de backend, vistas, transporte y pruebas del flujo completo; estas cuatro rutas todavía no están en `develop`. El enlace de acceso se habilita cuando el flujo esté disponible.
- **Sincronización de issues:** después de integrar, actualizar sus extractos del contrato sin cambiar asignaciones ni cerrar criterios no comprobados. La vista de inicio es `frontend/views/inicio/index.php`.
