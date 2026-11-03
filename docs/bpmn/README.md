# TuCriadero — Procesos BPMN 2.0

Modelos descriptivos del SRS v1.0, corregidos según la revisión de QA externo. Separan las acciones del criador o administrador de las operaciones automáticas de la aplicación web. Incluyen validaciones, errores del SRS, correcciones, cancelaciones y resultados, sin ampliar los requisitos.

- [Modelo completo editable](TuCriadero.bpmn): 12 procesos con sus diagramas BPMN DI.
- [Documento de los 12 procesos](TuCriadero_BPMN_v1.0.pdf): hojas A3 para lectura e impresión.
- [SRS completo](../srs/SRS_Gestion_Criadero_v1.0.pdf): mismos diagramas (Figuras 3 a 14) y matriz de trazabilidad. Las Figuras 1 y 2 son el modelo de datos.
- [Trazabilidad por actividad](trazabilidad.md): identificadores, responsables, requisitos, errores anotados y finales de cada proceso.

| Figura | Proceso | Vista vectorial | PDF vectorial | Archivo editable |
|---|---|---|---|---|
| 3 | Temporada de cría (vista general) | [SVG](bpmn-01-tem.svg) | [PDF](bpmn-01-tem.pdf) | [BPMN](bpmn-01-tem.bpmn) |
| 4 | Planificar y registrar un cruce | [SVG](bpmn-02-cru.svg) | [PDF](bpmn-02-cru.pdf) | [BPMN](bpmn-02-cru.bpmn) |
| 5 | Registrar pichones de un cruce | [SVG](bpmn-03-pic.svg) | [PDF](bpmn-03-pic.pdf) | [BPMN](bpmn-03-pic.bpmn) |
| 6 | Terminar un cruce | [SVG](bpmn-04-fin.svg) | [PDF](bpmn-04-fin.pdf) | [BPMN](bpmn-04-fin.bpmn) |
| 7 | Registrar una jaula | [SVG](bpmn-05-jau.svg) | [PDF](bpmn-05-jau.pdf) | [BPMN](bpmn-05-jau.bpmn) |
| 8 | Asignar o trasladar un canario | [SVG](bpmn-06-ubi.svg) | [PDF](bpmn-06-ubi.pdf) | [BPMN](bpmn-06-ubi.bpmn) |
| 9 | Registrar o actualizar un canario | [SVG](bpmn-07-can.svg) | [PDF](bpmn-07-can.pdf) | [BPMN](bpmn-07-can.bpmn) |
| 10 | Buscar y consultar la genealogía | [SVG](bpmn-08-bus.svg) | [PDF](bpmn-08-bus.pdf) | [BPMN](bpmn-08-bus.bpmn) |
| 11 | Dar de baja un canario | [SVG](bpmn-09-baj.svg) | [PDF](bpmn-09-baj.pdf) | [BPMN](bpmn-09-baj.bpmn) |
| 12 | Acceder a TuCriadero y completar el perfil | [SVG](bpmn-10-acc.svg) | [PDF](bpmn-10-acc.pdf) | [BPMN](bpmn-10-acc.bpmn) |
| 13 | Administrar las cuentas | [SVG](bpmn-11-cta.svg) | [PDF](bpmn-11-cta.pdf) | [BPMN](bpmn-11-cta.bpmn) |
| 14 | Mantener el catálogo de variedades y factores | [SVG](bpmn-12-cat.svg) | [PDF](bpmn-12-cat.pdf) | [BPMN](bpmn-12-cat.bpmn) |

## Cómo leerlos

La Figura 3 es la **vista general de la temporada**: cada caja de borde grueso es un proceso detallado en otra figura. Incluye la espera de 10 a 12 días entre el cruce y el anillado de los pichones; esa espera la decide el criador y el sistema no programa avisos.

Los rectángulos con persona representan tareas de usuario; los de engranaje, tareas del sistema. Los rombos con X son decisiones exclusivas, con condiciones rotuladas. Los círculos de borde fino son inicios; los de borde grueso, finales (registro exitoso, cancelación o rechazo). Las notas al costado listan los errores que el SRS exige informar, con su RF.

Cada validación sigue el mismo patrón: el sistema valida, muestra los errores sin guardar y el usuario decide si corrige o cancela. El subproceso de evento punteado cubre la falta de conexión, el mantenimiento y la sesión expirada. Una cuenta desactivada termina en «Acceso rechazado»; no vuelve al inicio.

Cada proceso tiene un inicio independiente. Consultar una ficha o registrar una baja no inicia ni termina una temporada. Terminar un cruce registra su fecha de fin y no traslada automáticamente a los canarios: el traslado a la jaula grande es el proceso de la Figura 8.

Los archivos tienen `isExecutable=false`: describen el negocio y los controles del sistema; no implementan un motor de ejecución. Se pueden importar individualmente en un editor BPMN. Si un editor muestra solo la primera vista del modelo completo, abrir el archivo del proceso concreto.

La validación incluye el [esquema oficial BPMN 2.0.2 de OMG](https://www.omg.org/spec/BPMN/2.0.2), referencias de nodos, decisiones, alcance desde inicio hasta fin, cobertura de los 39 RF y legibilidad (sin flechas cruzadas ni superpuestas y sin textos tapados). Validar el esquema no sustituye probar la aplicación.

## Fuentes y regeneración

En la carpeta de documentación del proyecto: `04_SRS/scripts/bpmn_model.py` define los nodos, flujos y trazas; `validar_bpmn.py` comprueba el resultado; `render_bpmn.py` exporta PDF vectorial; `actualizar_bpmn.py` integra las figuras en el SRS; `trazabilidad_bpmn.py` genera `trazabilidad.md`. El modelo de datos está en [`docs/backend.md`](../backend.md) (§3).
