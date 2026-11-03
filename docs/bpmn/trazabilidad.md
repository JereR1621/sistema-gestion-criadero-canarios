# TuCriadero — Trazabilidad de actividades BPMN

Actividades de los 12 procesos BPMN y requisitos del SRS v1.0. Los identificadores coinciden con los archivos .bpmn y las vistas gráficas. El número de figura es el del SRS (las Figuras 1 y 2 son el modelo de datos).

## TEM — Temporada de cría (vista general) (Figura 3)

Vista general: cada caja de borde grueso es un proceso detallado en la figura indicada. Varios cruces pueden avanzar en paralelo; cada uno sigue esta secuencia. La espera de 10 a 12 días la decide el criador: el sistema no programa avisos ni temporizadores automáticos.

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| TEM_cru | Criador | Detalle en la Figura 4. | Planificar y registrar un cruce |
| TEM_pic | Criador | Detalle en la Figura 5. | Registrar los pichones del cruce |
| TEM_fin | Criador | Detalle en la Figura 6. | Terminar el cruce |
| TEM_ubi | Criador | Detalle en la Figura 8. | Trasladar pichones y adultos a la jaula grande |

**Notas y errores anotados en el diagrama:**

- *Pichones con 10 a 12 días de vida*: Entre el cruce y el anillado ocurren la postura y la incubación, que no se registran en el sistema (fuera del alcance). · El criador anilla los pichones entre los 10 y 12 días de vida (glosario, RF021).
- En cualquier momento, fuera de esta secuencia: · Registrar canarios (Figura 9). · Registrar jaulas (Figura 7). · Buscar y consultar la genealogía (Figura 10). · Dar de baja un canario (Figura 11). · Ingreso (Figura 12); cuentas y catálogo, solo el administrador (Figuras 13 y 14).

**Finales:** Fin de la temporada.

## CRU — Planificar y registrar un cruce (Figura 4)

Precondición: sesión de criador válida; solo se usan registros del propio criadero (RF002–RF003). Una hembra con varios machos, o un macho con varias hembras, se registran repitiendo el proceso para cada pareja (RF019–RF020). La vista previa no guarda nada. Los porcentajes siguen SUP-004 (factores independientes, una dosis de los dominantes) y se avisa cuando el resultado es aproximado.

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| CRU_propuesta_datos | Criador | SRS-RF018, SRS-RF019, SRS-RF020 | Elegir macho, hembra, jaula, temporada y fecha de inicio |
| CRU_propuesta_validar | Sistema | SRS-RF018, SRS-RF019, SRS-RF020 | Validar progenitores, sexo, jaula y criadero |
| CRU_catalogo | Sistema | SRS-RF031, SRS-RF033, SRS-RF034 | Obtener factores manifestados, portados y tipo de herencia |
| CRU_calcular | Sistema | SRS-RF035, SRS-RF036, SRS-RF037, SRS-RF038 | Calcular los porcentajes por sexo con las reglas del catálogo |
| CRU_aviso | Sistema | SRS-RF039 | Agregar el aviso de factores no calculables |
| CRU_mostrar | Sistema | SRS-RF035, SRS-RF039 | Mostrar el posible resultado por sexo y los avisos |
| CRU_revisar | Criador | SRS-RF035 | Revisar el posible resultado y decidir |
| CRU_revalidar | Sistema | SRS-RF018 | Revalidar los datos al confirmar |
| CRU_guardar | Sistema | SRS-RF018, SRS-RF019, SRS-RF020 | Registrar el cruce con su fecha de inicio y auditar |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Macho o hembra sin sexo registrado, o con un sexo que no corresponde (RF018). · Canario o jaula de otro criadero (RF003, DAT-004). · Falta un dato obligatorio (RF018).

**Finales:** Planificación cancelada; sin cruce guardado; Propuesta cancelada; sin cruce guardado; No guardado: un progenitor o la jaula cambió; Cruce registrado; ficha de cría disponible.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## PIC — Registrar pichones de un cruce (Figura 5)

Precondición: sesión de criador válida; el cruce y sus progenitores son del propio criadero (RF003, DAT-004). Se registra cuando los pichones tienen entre 10 y 12 días (Figura 3). Sexo y variedad pueden quedar sin determinar y la fecha de nacimiento es opcional (RF007, RF021). Registrar pichones no termina el cruce (RF022).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| PIC_elegir | Criador | SRS-RF027 | Elegir un cruce activo y abrir su ficha de cría |
| PIC_cruce | Sistema | SRS-RF004, SRS-RF027 | Obtener el cruce, sus progenitores y los datos por defecto |
| PIC_anillos_datos | Criador | SRS-RF007, SRS-RF008, SRS-RF012, SRS-RF021 | Ingresar los anillos y las fechas de los pichones |
| PIC_anillos_validar | Sistema | SRS-RF007, SRS-RF008, SRS-RF012, SRS-RF021 | Validar anillos, sexo, variedad y fechas |
| PIC_fichas | Sistema | SRS-RF021 | Crear las fichas con padre, madre y cruce de origen |
| PIC_factores | Sistema | SRS-RF033, SRS-RF034 | Obtener los factores manifestados y deducir los portadores |
| PIC_mostrar | Sistema | SRS-RF021, SRS-RF027 | Actualizar la ficha de cría y registrar auditoría |
| PIC_revisar | Criador | SRS-RF021, SRS-RF027 | Revisar las fichas creadas |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Anillo ya usado en el criadero o repetido en el envío: se indica el canario que lo usa (RF008). · Variedad que no corresponde al sexo (RF012). · Falta la fecha de anillado (RF021).

**Finales:** Registro cancelado; sin pichones nuevos; Pichones registrados; el cruce sigue activo.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## FIN — Terminar un cruce (Figura 6)

Precondición: sesión de criador válida; el cruce es del propio criadero (RF003). Terminar el cruce no traslada a sus integrantes: los traslados se registran en la Figura 8. Se conservan padres, pichones e historial (RF022, DAT-004).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| FIN_cierre_datos | Criador | SRS-RF022 | Abrir el cruce activo e indicar la fecha de fin |
| FIN_cierre_validar | Sistema | SRS-RF022 | Validar el cruce y las fechas |
| FIN_cerrar | Sistema | SRS-RF022 | Guardar la fecha de fin y retirar el cruce de los activos |
| FIN_historial | Sistema | SRS-RF017, SRS-RF022, SRS-RF027 | Conservar la ficha de cría y el historial y auditar |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Fecha de fin anterior a la fecha de inicio (DAT-004). · Cruce ya terminado o de otro criadero (RF003, RF022).

**Finales:** Cierre cancelado; el cruce sigue activo; Cruce terminado; historial disponible.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## JAU — Registrar una jaula (Figura 7)

Precondición: sesión de criador válida. El número de jaula es único dentro del criadero (RF014, DAT-004). La jaula grande no se registra: es la ubicación sin número (RF016).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| JAU_nueva_datos | Criador | SRS-RF014 | Ingresar el número de la nueva jaula |
| JAU_nueva_validar | Sistema | SRS-RF014 | Validar que el número no exista en el criadero |
| JAU_alta | Sistema | SRS-RF014 | Registrar la jaula numerada |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Número de jaula ya registrado en el criadero (RF014).

**Finales:** Registro cancelado; sin jaula nueva; Jaula registrada.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## UBI — Asignar o trasladar un canario (Figura 8)

Precondición: sesión de criador válida; canario y jaula del propio criadero (RF003, DAT-004). La jaula grande es la ubicación sin número. Cada canario tiene como máximo una ubicación abierta: el traslado cierra la anterior (RF015, RF016, DAT-004).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| UBI_mover_datos | Criador | SRS-RF015, SRS-RF016 | Elegir el canario, el destino y la fecha del movimiento |
| UBI_mover_validar | Sistema | SRS-RF015, SRS-RF016 | Validar el canario, el destino y el criadero |
| UBI_mover | Sistema | SRS-RF015, SRS-RF016 | Cerrar la ubicación anterior y abrir la nueva |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Canario o jaula de otro criadero (RF003, DAT-004). · Falta el canario, el destino o la fecha (RF015).

**Finales:** Movimiento cancelado; ubicación sin cambios; Canario ubicado; historial de la jaula actualizado.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## CAN — Registrar o actualizar un canario (Figura 9)

Precondición: sesión de criador válida; el canario y sus padres, si existen, son del propio criadero (RF003, DAT-004). Un canario comprado puede no tener padres y registra su procedencia. Sexo y variedad admiten "sin determinar" (RF007–RF010). Al cambiar sexo, variedad o padres solo se recalculan los portadores deducidos (RF034).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| CAN_ficha_datos | Criador | SRS-RF007, SRS-RF008, SRS-RF009, SRS-RF010, SRS-RF012, SRS-RF013, SRS-RF029 | Completar la ficha del canario y los portadores conocidos |
| CAN_ficha_validar | Sistema | SRS-RF007, SRS-RF008, SRS-RF009, SRS-RF010, SRS-RF012, SRS-RF013, SRS-RF029 | Validar anillo, sexo, variedad, padres y portadores |
| CAN_guardar | Sistema | SRS-RF007, SRS-RF009, SRS-RF010 | Guardar la ficha y los portadores indicados |
| CAN_manifestados | Sistema | SRS-RF033 | Obtener los factores manifestados desde la variedad |
| CAN_deducidos | Sistema | SRS-RF034 | Recalcular los portadores deducidos del canario y sus crías |
| CAN_mostrar | Sistema | SRS-RF024 | Registrar auditoría y mostrar la ficha |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Anillo ya usado: se indica el canario que lo usa (RF008). · Variedad que no corresponde al sexo (RF012). · Hembra portadora de un factor ligado al sexo (RF013). · Portador de un factor dominante o de uno que ya manifiesta (DAT-004). · Padre o madre de otro criadero (DAT-004).

**Finales:** Edición cancelada; ficha sin cambios; Ficha registrada o actualizada.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## BUS — Buscar y consultar la genealogía (Figura 10)

Precondición: sesión de criador válida; todo se filtra por el criador de la sesión y un registro ajeno se trata como inexistente (RF003). Por anillo se incluyen los dados de baja; por variedad, solo los activos. La ficha de una jaula muestra ocupantes, cruce activo e historial por fecha (RF017, RF023, RF025, RF026).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| BUS_consulta | Criador | SRS-RF023, SRS-RF025, SRS-RF026 | Elegir anillo, correlativo, variedad o jaula y buscar |
| BUS_buscar | Sistema | SRS-RF023, SRS-RF025, SRS-RF026 | Buscar en el criadero según el criterio elegido |
| BUS_mostrar | Sistema | SRS-RF017, SRS-RF023, SRS-RF024, SRS-RF025, SRS-RF026, SRS-RF027 | Mostrar la ficha o la lista de coincidencias |
| BUS_revisar | Criador | SRS-RF024, SRS-RF027 | Revisar el resultado |

**Finales:** Consulta terminada sin resultados; Consulta terminada; sin cambios.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## BAJ — Dar de baja un canario (Figura 11)

Precondición: sesión de criador válida; el canario es del propio criadero (RF003). Motivos: venta, muerte u otra salida del criadero. La ficha no se borra: sigue en la genealogía y en la búsqueda por anillo (RF011, RF023, DAT-004).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| BAJ_baja_datos | Criador | SRS-RF011 | Abrir el canario e indicar el motivo y la fecha de la baja |
| BAJ_baja_validar | Sistema | SRS-RF011 | Validar el canario, el motivo y la fecha |
| BAJ_guardar | Sistema | SRS-RF011 | Registrar la baja y el estado en una sola operación |
| BAJ_conservar | Sistema | SRS-RF011, SRS-RF023, SRS-RF024 | Registrar auditoría y conservar la ficha y la genealogía |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Falta el motivo o la fecha (RF011). · Canario de otro criadero o ya dado de baja (RF003, DAT-001).

**Finales:** Baja cancelada; canario sin cambios; Baja registrada; historia conservada.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## ACC — Acceder a TuCriadero y completar el perfil (Figura 12)

El autorregistro crea solo cuentas de criador; la cuenta de administrador la crea el equipo (RF001, SEG-001). La sesión queda limitada al criadero del usuario (RF002–RF003), expira tras 7 días de inactividad y puede cerrarse en cualquier momento (SEG-004). Socio y club se proponen por defecto al anillar (RF004).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| ACC_registro_datos | Criador | SRS-RF001 | Ingresar nombre, correo y contraseña |
| ACC_registro_validar | Sistema | SRS-RF001 | Validar los datos del registro |
| ACC_crear | Sistema | SRS-RF001 | Crear la cuenta de criador y su criadero vacío |
| ACC_ingreso_datos | Criador | SRS-RF002, SRS-RF003 | Ingresar el correo y la contraseña |
| ACC_ingreso_validar | Sistema | SRS-RF002, SRS-RF003, SRS-RF005 | Validar las credenciales y el estado de la cuenta |
| ACC_sesion | Sistema | SRS-RF002, SRS-RF003 | Abrir la sesión con el rol y el criadero autorizados |
| ACC_perfil_datos | Criador | SRS-RF004 | Ingresar el número de socio y el club |
| ACC_perfil_validar | Sistema | SRS-RF004 | Validar los datos del perfil |
| ACC_guardar_perfil | Sistema | SRS-RF004 | Guardar socio y club como valores por defecto |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Contraseña de menos de 8 caracteres (SEG-001). · Correo ya registrado (RF002). · Falta el nombre, el correo o la contraseña (RF001).

**Finales:** Registro cancelado; sin cuenta nueva; Ingreso cancelado; Acceso rechazado: cuenta desactivada; Perfil sin cambios; acceso disponible; Acceso disponible con sesión autorizada.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## CTA — Administrar las cuentas (Figura 13)

Precondición: sesión de administrador; un criador no accede a esta función. El administrador ve los datos de registro y el estado de las cuentas, nunca los datos de los criaderos (RF006, SEG-001). Desactivar una cuenta impide su ingreso y conserva sus datos (RF005). Toda acción administrativa se audita (SEG-003).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| CTA_consultar | Administrador | SRS-RF006 | Consultar las cuentas |
| CTA_listar | Sistema | SRS-RF006 | Listar el registro y el estado de cada cuenta |
| CTA_estado_datos | Administrador | SRS-RF005 | Elegir una cuenta y su nuevo estado |
| CTA_estado_validar | Sistema | SRS-RF005 | Validar la cuenta y el cambio de estado |
| CTA_guardar | Sistema | SRS-RF005 | Guardar el estado sin borrar datos y auditar |

**Finales:** Cuentas sin cambios; Estado de la cuenta actualizado.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## CAT — Mantener el catálogo de variedades y factores (Figura 14)

Precondición: sesión de administrador; para los criadores el catálogo es de solo lectura (RF030, SEG-001). Las 762 variedades, sus factores y su composición vienen precargados (RF028, DAT-005). Los cambios aplican para todos los criadores y se auditan (RF032, SEG-003).

| Actividad | Responsable | Requisitos | Tarea |
|---|---|---|---|
| CAT_inicio | Administrador | SRS-RF028, SRS-RF030 | Mantener el catálogo |
| CAT_catalogo_datos | Administrador | SRS-RF031, SRS-RF032 | Agregar o corregir una variedad, su composición o un factor |
| CAT_catalogo_validar | Sistema | SRS-RF031, SRS-RF032 | Validar códigos, composición, herencia y uso |
| CAT_guardar | Sistema | SRS-RF032 | Aplicar el cambio para todos los criadores y auditar |

**Notas y errores anotados en el diagrama:**

- *¿Datos válidos?*: Errores que se informan sin guardar: · Código de variedad duplicado (RF032). · Eliminar una variedad asignada a algún canario (RF032). · Composición repetida para el mismo sexo (DAT-004). · Factor sin tipo de herencia (RF031).

**Finales:** Catálogo sin cambios; Catálogo actualizado.

**Indisponibilidad:** subproceso de evento por falta de conexión, mantenimiento o sesión expirada (SRS §2.5 y SEG-004).

## Cobertura de los requisitos funcionales

| Requisito | Procesos |
|---|---|
| SRS-RF001 | ACC |
| SRS-RF002 | ACC |
| SRS-RF003 | ACC |
| SRS-RF004 | PIC, ACC |
| SRS-RF005 | ACC, CTA |
| SRS-RF006 | CTA |
| SRS-RF007 | PIC, CAN |
| SRS-RF008 | PIC, CAN |
| SRS-RF009 | CAN |
| SRS-RF010 | CAN |
| SRS-RF011 | BAJ |
| SRS-RF012 | PIC, CAN |
| SRS-RF013 | CAN |
| SRS-RF014 | JAU |
| SRS-RF015 | UBI |
| SRS-RF016 | UBI |
| SRS-RF017 | FIN, BUS |
| SRS-RF018 | CRU |
| SRS-RF019 | CRU |
| SRS-RF020 | CRU |
| SRS-RF021 | PIC |
| SRS-RF022 | FIN |
| SRS-RF023 | BUS, BAJ |
| SRS-RF024 | CAN, BUS, BAJ |
| SRS-RF025 | BUS |
| SRS-RF026 | BUS |
| SRS-RF027 | PIC, FIN, BUS |
| SRS-RF028 | CAT |
| SRS-RF029 | CAN |
| SRS-RF030 | CAT |
| SRS-RF031 | CRU, CAT |
| SRS-RF032 | CAT |
| SRS-RF033 | CRU, PIC, CAN |
| SRS-RF034 | CRU, PIC, CAN |
| SRS-RF035 | CRU |
| SRS-RF036 | CRU |
| SRS-RF037 | CRU |
| SRS-RF038 | CRU |
| SRS-RF039 | CRU |
