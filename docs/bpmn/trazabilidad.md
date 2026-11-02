# TuCriadero — Trazabilidad de actividades BPMN

Actividades de los nueve procesos y requisitos del SRS v1.0. Los identificadores coinciden con los archivos .bpmn y las vistas gráficas.

## CRU — Planificar y registrar un cruce

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| CRU_propuesta_datos | Criador | SRS-RF018, SRS-RF019, SRS-RF020 | Elegir macho, hembra, jaula, temporada y fecha |
| CRU_propuesta_validar | Sistema | SRS-RF018, SRS-RF019, SRS-RF020 | Validar progenitores, sexo, jaula y criadero. Macho y hembra con sexo conocido y del criadero; jaula del mismo criadero; datos obligatorios completos. DAT-004. |
| CRU_propuesta_error | Sistema | SRS-RF018, SRS-RF019, SRS-RF020 | Mostrar los errores sin guardar cambios |
| CRU_propuesta_corregir | Criador | SRS-RF018, SRS-RF019, SRS-RF020 | Corregir los datos del formulario |
| CRU_catalogo | Sistema | SRS-RF028, SRS-RF029, SRS-RF030, SRS-RF031, SRS-RF033, SRS-RF034 | Obtener factores manifestados, portados y herencia. Armar genotipos con la variedad y los portadores de ambos progenitores. Un dato sin determinar no equivale a un factor normal conocido; informar lo que no puede calcularse. |
| CRU_calcular | Sistema | SRS-RF035, SRS-RF036, SRS-RF037, SRS-RF038, SRS-RF039 | Calcular proporciones con las reglas disponibles. Reglas ligadas al sexo, recesivas y dominantes. Combinar según SUP-004; resolver variedad o lista de factores. |
| CRU_aviso | Sistema | SRS-RF039 | Informar factores no calculables y limitaciones |
| CRU_mostrar | Sistema | SRS-RF035, SRS-RF039 | Mostrar porcentajes por sexo, factores y avisos |
| CRU_revisar | Criador | SRS-RF035 | Revisar el posible resultado y decidir |
| CRU_revalidar | Sistema | SRS-RF018 | Revalidar los datos al confirmar |
| CRU_cambio | Sistema | SRS-RF018 | Informar cambios o errores y no guardar |
| CRU_guardar | Sistema | SRS-RF018, SRS-RF019, SRS-RF020 | Registrar cruce con fecha de inicio y auditar |

## PIC — Registrar pichones de un cruce

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| PIC_elegir | Criador | SRS-RF027 | Elegir un cruce y abrir su ficha de cría |
| PIC_cruce | Sistema | SRS-RF004, SRS-RF027 | Obtener cruce, progenitores y datos por defecto |
| PIC_anillos_datos | Criador | SRS-RF007, SRS-RF008, SRS-RF012, SRS-RF021 | Ingresar anillos y fechas de los pichones |
| PIC_anillos_validar | Sistema | SRS-RF007, SRS-RF008, SRS-RF012, SRS-RF021 | Validar anillos, variedad, sexo y fechas. Anillo obligatorio y no duplicado en el criadero ni en el envío; fecha de anillado; nacimiento opcional; variedad compatible con sexo cuando se determine. DAT-004. |
| PIC_anillos_error | Sistema | SRS-RF007, SRS-RF008, SRS-RF012, SRS-RF021 | Mostrar los errores sin guardar cambios |
| PIC_anillos_corregir | Criador | SRS-RF007, SRS-RF008, SRS-RF012, SRS-RF021 | Corregir los datos del formulario |
| PIC_fichas | Sistema | SRS-RF021 | Crear fichas con padre, madre y cruce de origen |
| PIC_factores | Sistema | SRS-RF033, SRS-RF034 | Obtener manifestados y deducir portadores ciertos |
| PIC_mostrar | Sistema | SRS-RF021, SRS-RF027 | Actualizar ficha de cría y registrar auditoría |
| PIC_seguir | Criador | SRS-RF021, SRS-RF027 | Revisar fichas y elegir si registra más pichones |

## FIN — Terminar un cruce

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| FIN_cierre_datos | Criador | SRS-RF022 | Abrir el cruce e indicar la fecha de fin |
| FIN_cierre_validar | Sistema | SRS-RF022 | Validar criadero y fechas de inicio y fin |
| FIN_cierre_error | Sistema | SRS-RF022 | Mostrar los errores sin guardar cambios |
| FIN_cierre_corregir | Criador | SRS-RF022 | Corregir los datos del formulario |
| FIN_cerrar | Sistema | SRS-RF022 | Guardar fecha de fin y retirar el cruce de activos |
| FIN_historial | Sistema | SRS-RF017, SRS-RF022, SRS-RF027 | Conservar fichas e historial y registrar auditoría |

## JAU — Gestionar jaulas y ubicaciones

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| JAU_operacion | Criador | SRS-RF014, SRS-RF015, SRS-RF016, SRS-RF017 | Elegir alta, asignación, traslado o historial |
| JAU_nueva_datos | Criador | SRS-RF014 | Ingresar el número de la nueva jaula |
| JAU_nueva_validar | Sistema | SRS-RF014 | Validar número único en el criadero |
| JAU_nueva_error | Sistema | SRS-RF014 | Mostrar los errores sin guardar cambios |
| JAU_nueva_corregir | Criador | SRS-RF014 | Corregir los datos del formulario |
| JAU_alta | Sistema | SRS-RF014 | Registrar la jaula numerada |
| JAU_ubicacion_datos | Criador | SRS-RF015, SRS-RF016 | Elegir canario, destino y fecha del movimiento |
| JAU_ubicacion_validar | Sistema | SRS-RF015, SRS-RF016 | Validar canario, destino y pertenencia al criadero |
| JAU_ubicacion_error | Sistema | SRS-RF015, SRS-RF016 | Mostrar los errores sin guardar cambios |
| JAU_ubicacion_corregir | Criador | SRS-RF015, SRS-RF016 | Corregir los datos del formulario |
| JAU_mover | Sistema | SRS-RF015, SRS-RF016 | Cerrar ubicación anterior y abrir la nueva |
| JAU_seleccionar | Criador | SRS-RF017 | Elegir la jaula cuyo historial consulta |
| JAU_historial | Sistema | SRS-RF017 | Mostrar ocupantes y cruces ordenados por fecha |

## CAN — Registrar y actualizar canarios

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| CAN_ficha_datos | Criador | SRS-RF007, SRS-RF008, SRS-RF009, SRS-RF010, SRS-RF012, SRS-RF013 | Completar ficha, procedencia y portadores conocidos |
| CAN_ficha_validar | Sistema | SRS-RF007, SRS-RF008, SRS-RF009, SRS-RF010, SRS-RF012, SRS-RF013 | Validar anillo, sexo, variedad, padres y portadores. Si el anillo ya existe, indicar el canario que lo usa; al editar, excluir el registro actual de la comprobación de duplicados. |
| CAN_ficha_error | Sistema | SRS-RF007, SRS-RF008, SRS-RF009, SRS-RF010, SRS-RF012, SRS-RF013 | Mostrar los errores sin guardar cambios |
| CAN_ficha_corregir | Criador | SRS-RF007, SRS-RF008, SRS-RF009, SRS-RF010, SRS-RF012, SRS-RF013 | Corregir los datos del formulario |
| CAN_guardar | Sistema | SRS-RF007, SRS-RF009, SRS-RF010 | Guardar ficha y portadores indicados por el criador |
| CAN_manifestados | Sistema | SRS-RF033 | Obtener los factores de la composición de variedad |
| CAN_deducidos | Sistema | SRS-RF034 | Recalcular los portadores deducidos afectados |
| CAN_mostrar | Sistema | SRS-RF024 | Auditar el cambio y mostrar la ficha actualizada |

## BUS — Buscar y consultar genealogía

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| BUS_consulta | Criador | SRS-RF023, SRS-RF025, SRS-RF026 | Elegir anillo, variedad o jaula e ingresar la búsqueda |
| BUS_buscar | Sistema | SRS-RF023, SRS-RF025, SRS-RF026 | Buscar en el criadero según el criterio elegido |
| BUS_sin_resultados | Sistema | SRS-RF023, SRS-RF025, SRS-RF026 | Informar que no hay resultados disponibles |
| BUS_resultados | Sistema | SRS-RF023, SRS-RF025, SRS-RF026 | Mostrar ficha o coincidencias con identificación |
| BUS_seguir | Criador | SRS-RF023, SRS-RF024, SRS-RF025, SRS-RF026, SRS-RF027 | Revisar el resultado y elegir cómo continuar |
| BUS_seleccionar | Criador | SRS-RF024, SRS-RF027 | Elegir un resultado o un enlace de la ficha |
| BUS_ficha | Sistema | SRS-RF024, SRS-RF026, SRS-RF027 | Mostrar la ficha autorizada del registro elegido |

## BAJ — Dar de baja un canario

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| BAJ_baja_datos | Criador | SRS-RF011 | Abrir el canario e indicar motivo y fecha de baja |
| BAJ_baja_validar | Sistema | SRS-RF011 | Validar canario, criadero, motivo y fecha |
| BAJ_baja_error | Sistema | SRS-RF011 | Mostrar los errores sin guardar cambios |
| BAJ_baja_corregir | Criador | SRS-RF011 | Corregir los datos del formulario |
| BAJ_guardar | Sistema | SRS-RF011 | Registrar baja y actualizar estado en una operación |
| BAJ_conservar | Sistema | SRS-RF011, SRS-RF023, SRS-RF024 | Auditar; conservar ficha y genealogía para consultas |

## ACC — Acceder al criadero y completar el perfil

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| ACC_registro_datos | Criador | SRS-RF001 | Ingresar nombre, correo y contraseña |
| ACC_registro_validar | Sistema | SRS-RF001 | Validar el registro de una cuenta de criador |
| ACC_registro_error | Sistema | SRS-RF001 | Mostrar los errores sin guardar cambios |
| ACC_registro_corregir | Criador | SRS-RF001 | Corregir los datos del formulario |
| ACC_crear | Sistema | SRS-RF001 | Crear cuenta de criador y criadero propio vacío |
| ACC_ingreso_datos | Criador | SRS-RF002, SRS-RF003 | Ingresar correo y contraseña |
| ACC_ingreso_validar | Sistema | SRS-RF002, SRS-RF003 | Validar credenciales y estado de la cuenta |
| ACC_ingreso_error | Sistema | SRS-RF002, SRS-RF003 | Mostrar los errores sin guardar cambios |
| ACC_ingreso_corregir | Criador | SRS-RF002, SRS-RF003 | Corregir los datos del formulario |
| ACC_sesion | Sistema | SRS-RF002, SRS-RF003 | Abrir sesión con rol y criadero autorizados |
| ACC_perfil_opcion | Criador | SRS-RF004 | Elegir si completa o actualiza socio y club |
| ACC_editar_datos | Criador | SRS-RF004 | Ingresar número de socio y club |
| ACC_editar_validar | Sistema | SRS-RF004 | Validar los datos del perfil propio |
| ACC_editar_error | Sistema | SRS-RF004 | Mostrar los errores sin guardar cambios |
| ACC_editar_corregir | Criador | SRS-RF004 | Corregir los datos del formulario |
| ACC_guardar_perfil | Sistema | SRS-RF004 | Guardar socio y club como valores por defecto |

## ADM — Administrar cuentas y catálogo

| Actividad | Responsable | Requisitos | Descripción / validaciones |
|---|---|---|---|
| ADM_operacion | Administrador | SRS-RF005, SRS-RF006, SRS-RF032 | Elegir administración de cuentas o catálogo |
| ADM_cuentas | Sistema | SRS-RF006 | Listar registro y estado de las cuentas |
| ADM_estado_datos | Administrador | SRS-RF005, SRS-RF006 | Seleccionar cuenta y cambio de estado |
| ADM_estado_validar | Sistema | SRS-RF005, SRS-RF006 | Validar cuenta y operación administrativa |
| ADM_estado_error | Sistema | SRS-RF005, SRS-RF006 | Mostrar los errores sin guardar cambios |
| ADM_estado_corregir | Administrador | SRS-RF005, SRS-RF006 | Corregir los datos del formulario |
| ADM_estado_guardar | Sistema | SRS-RF005 | Guardar estado sin borrar datos y auditar |
| ADM_catalogo_datos | Administrador | SRS-RF028, SRS-RF029, SRS-RF030, SRS-RF031, SRS-RF032 | Agregar, corregir o solicitar eliminar datos del catálogo |
| ADM_catalogo_validar | Sistema | SRS-RF028, SRS-RF029, SRS-RF030, SRS-RF031, SRS-RF032 | Validar códigos, composición, herencia y uso. Solo variedades de color; códigos únicos; variedad asignada no se elimina; composición única por sexo; tipo de herencia del catálogo. |
| ADM_catalogo_error | Sistema | SRS-RF028, SRS-RF029, SRS-RF030, SRS-RF031, SRS-RF032 | Mostrar los errores sin guardar cambios |
| ADM_catalogo_corregir | Administrador | SRS-RF028, SRS-RF029, SRS-RF030, SRS-RF031, SRS-RF032 | Corregir los datos del formulario |
| ADM_catalogo_guardar | Sistema | SRS-RF032 | Aplicar catálogo para todos los criadores y auditar |
