# TuCriadero — Procesos BPMN 2.0

Modelos descriptivos del SRS v1.0. Separan las acciones del criador o administrador de las operaciones automáticas de la aplicación web. Incluyen validaciones, correcciones, decisiones y resultados, sin ampliar los requisitos.

- [Modelo completo editable](TuCriadero.bpmn): nueve procesos con sus diagramas BPMN DI.
- [Documento de los nueve procesos](TuCriadero_BPMN_v1.0.pdf): hojas A3 para lectura e impresión.
- [SRS completo](../srs/SRS_Gestion_Criadero_v1.0.pdf): mismos diagramas en el apéndice y matriz de trazabilidad.
- [Trazabilidad por actividad](trazabilidad.md): identificadores, responsables, requisitos y validaciones de cada tarea.

| Proceso | Vista vectorial | PDF vectorial | Archivo editable |
|---|---|---|---|
| Planificar y registrar un cruce | [SVG](bpmn-01-cru.svg) | [PDF](bpmn-01-cru.pdf) | [BPMN](bpmn-01-cru.bpmn) |
| Registrar pichones de un cruce | [SVG](bpmn-02-pic.svg) | [PDF](bpmn-02-pic.pdf) | [BPMN](bpmn-02-pic.bpmn) |
| Terminar un cruce | [SVG](bpmn-03-fin.svg) | [PDF](bpmn-03-fin.pdf) | [BPMN](bpmn-03-fin.bpmn) |
| Gestionar jaulas y ubicaciones | [SVG](bpmn-04-jau.svg) | [PDF](bpmn-04-jau.pdf) | [BPMN](bpmn-04-jau.bpmn) |
| Registrar y actualizar canarios | [SVG](bpmn-05-can.svg) | [PDF](bpmn-05-can.pdf) | [BPMN](bpmn-05-can.bpmn) |
| Buscar y consultar genealogía | [SVG](bpmn-06-bus.svg) | [PDF](bpmn-06-bus.pdf) | [BPMN](bpmn-06-bus.bpmn) |
| Dar de baja un canario | [SVG](bpmn-07-baj.svg) | [PDF](bpmn-07-baj.pdf) | [BPMN](bpmn-07-baj.bpmn) |
| Acceder al criadero y completar el perfil | [SVG](bpmn-08-acc.svg) | [PDF](bpmn-08-acc.pdf) | [BPMN](bpmn-08-acc.bpmn) |
| Administrar cuentas y catálogo | [SVG](bpmn-09-adm.svg) | [PDF](bpmn-09-adm.pdf) | [BPMN](bpmn-09-adm.bpmn) |

## Cómo leerlos

Los rectángulos con persona representan tareas de usuario; los de engranaje, tareas de servicio del sistema. Los rombos con X son decisiones exclusivas, con condiciones rotuladas y una rama por defecto. Los círculos de borde fino son inicios; los de borde grueso, finales. Las flechas son flujos de secuencia dentro del mismo proceso, también cuando cruzan un carril.

Cada proceso tiene un inicio independiente. Consultar una ficha o registrar una baja no inicia ni termina una temporada. Terminar un cruce registra su fecha de fin y no traslada automáticamente a los canarios. El movimiento a la jaula grande se registra aparte. Los datos sin determinar, portadores manuales, genealogía y avisos del cálculo genético siguen las reglas del SRS.

Los archivos tienen `isExecutable=false`: describen el negocio y los controles del sistema; no implementan un motor de ejecución. Se pueden importar individualmente en un editor BPMN. El modelo completo contiene nueve vistas; si un editor muestra solo la primera, abrir el archivo del proceso concreto.

La validación incluye el [esquema oficial BPMN 2.0.2 de OMG](https://www.omg.org/spec/BPMN/2.0.2), referencias de nodos, entradas/salidas, decisiones, alcance desde inicio hasta fin y cobertura de los 39 RF. Validar el esquema no sustituye probar la aplicación.

## Fuentes y regeneración

En la carpeta de documentación del proyecto: `04_SRS/scripts/bpmn_model.py` define los nodos, flujos y trazas; `render_bpmn.py` exporta PDF vectorial y PNG; `actualizar_bpmn.py` integra el apéndice; `validar_bpmn.py` comprueba el resultado. El modelo entidad-relación se mantiene separado.
