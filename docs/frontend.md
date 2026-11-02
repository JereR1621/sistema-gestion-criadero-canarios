# Resumen para frontend

Guía de trabajo para el equipo de frontend del **Sistema de Gestión de Criadero de Canarios**. Resume lo que el SRS v1.0 (`docs/srs/`) exige a las pantallas. **Si este resumen y el SRS no coinciden, manda el SRS**; los cambios se piden al PM.

## 1. Qué es "frontend" en este proyecto

El stack es **PHP con HTML embebido**: cada pantalla es una vista HTML que el servidor genera con PHP. **No hay JavaScript en el stack**, así que todo tiene que funcionar con formularios y enlaces normales:

- Una "pestaña" (por ejemplo, "Posible resultado") es una sección o una página que el servidor arma al enviar el formulario.
- Los buscadores son formularios que envían la búsqueda y muestran los resultados en la página siguiente.
- Las validaciones las hace el servidor. En el navegador solo se usan los atributos de HTML (`required`, `maxlength`, `type`, `pattern`), como ayuda.

El trabajo de frontend vive en `app/Views/` (ver `README.md`). Backend entrega a cada vista los datos ya filtrados por el criador que inició sesión. **Qué variables recibe cada vista y cómo se llaman los campos de cada formulario está en `docs/contrato-back-front.md`.**

> **Diseño responsivo:** cumplir el ancho de 360 px (RNF001) requiere estilos CSS junto al HTML. Hay que confirmar con el PM si van dentro de las vistas o en un archivo `.css` propio.

## 2. Usuarios

| Rol | Qué ve | Qué no ve nunca |
|---|---|---|
| Criador | Su criadero: canarios, jaulas, cruces, búsquedas y fichas. El catálogo de variedades, en solo lectura. | Datos de otros criaderos. |
| Administrador de la plataforma | Las cuentas (con sus datos de registro y estado) y el mantenimiento del catálogo. | Los datos de cualquier criadero. |

## 3. Pantallas

| # | Pantalla | Contenido y acciones | RF |
|---|---|---|---|
| 1 | Registro de cuenta | Nombre, correo y contraseña (mínimo 8 caracteres). | RF001, SEG-001 |
| 2 | Inicio de sesión | Correo y contraseña. Mensaje claro si la cuenta está desactivada. | RF002, RF005 |
| 3 | Perfil | Número de socio y club, que se proponen por defecto al anillar. | RF004 |
| 4 | Inicio | **Buscador visible de inmediato.** La ficha de un canario tiene que quedar a 3 interacciones como máximo. | RNF003 |
| 5 | Lista de canarios | Solo los activos, con anillo, sexo, variedad y ubicación. | RF011 |
| 6 | Alta y edición de canario | Anillo en cuatro campos (socio, año, correlativo, club). Sexo: macho, hembra o "sin determinar". Variedad: buscable por código o por nombre, o "sin determinar". Procedencia, si fue comprado. Portadores que indica el criador. | RF007–RF010, RF012, RF029 |
| 7 | Ficha del canario | Datos del canario; padre, madre, hermanos, medios hermanos y crías, cada uno con enlace a su ficha; cruces en que participó, con enlace a su ficha de cría; ubicación actual. **Factores que manifiesta** (se muestran, no se editan) y **portadores**, distinguiendo "deducido por el sistema" de "indicado por el criador". | RF024, RF027, RF033, RF034 |
| 8 | Baja de canario | Motivo (venta, muerte u otra salida) y fecha, ambos obligatorios. | RF011 |
| 9 | Lista y alta de jaulas | Número de jaula, único por criadero. | RF014 |
| 10 | Ficha de jaula | Ocupantes actuales, cruce activo e historial de ocupantes y cruces por fecha. | RF017, RF026 |
| 11 | Asignar o trasladar canario | A una jaula numerada o a la **jaula grande**, que no tiene número. | RF015, RF016 |
| 12 | Nuevo cruce | Macho, hembra, jaula, temporada y fecha de inicio. Antes de confirmar, se muestra la pestaña **"Posible resultado"**. | RF018–RF020, RF035 |
| 13 | Posible resultado | Variedades esperadas con su porcentaje, **separadas para machos y para hembras**. Las combinaciones sin variedad se muestran como lista de factores. Aviso si algún factor no se puede calcular. Botones para confirmar o volver. | RF035, RF039 |
| 14 | Ficha de cría (cruce) | Macho, hembra, jaula, temporada y pichones con anillo, sexo y estado. Acciones: anillar pichones y terminar el cruce. | RF021, RF022, RF027 |
| 15 | Anillar pichones | Uno o varios anillos en el mismo envío, con fecha de anillado y fecha de nacimiento (opcional). Sexo y variedad pueden quedar sin determinar. | RF021, RF007 |
| 16 | Búsqueda | Por anillo completo o solo por el correlativo (por ejemplo, "150"), por variedad o por jaula. Si hay varias coincidencias, se muestra una lista con año, socio y club. También encuentra a los dados de baja. | RF023, RF025, RF026 |
| 17 | Administración: cuentas | Lista con datos de registro y estado; activar y desactivar. | RF005, RF006 |
| 18 | Administración: catálogo | Variedades, su composición y factores, con su tipo de herencia. | RF032 |

## 4. Mensajes que el usuario debe ver

Backend valida y devuelve el error; la vista lo muestra junto al campo afectado, en lenguaje simple.

| Situación | Mensaje (orientativo) | RF |
|---|---|---|
| Anillo repetido | "Este anillo ya lo usa el canario …", con enlace a su ficha. | RF008 |
| Número de jaula repetido | "Ya existe la jaula N.º …". | RF014 |
| Sexo que no corresponde en un cruce | "El macho debe estar registrado como macho" (o lo mismo para la hembra). | RF018 |
| Variedad incompatible con el sexo | "Esta variedad es solo para hembras" (o para machos). | RF012 |
| Hembra portadora de un factor ligado al sexo | "Una hembra no puede ser portadora de este factor: lo manifiesta o no lo tiene". | RF013 |
| Factor sin regla de herencia | "No se puede calcular el resultado para el factor …". | RF039 |
| Sin conexión o servidor caído | Aviso de que no se pueden consultar ni guardar datos. | SRS §2.5 |
| Mantenimiento | Aviso de mantenimiento para los criadores. | SRS §2.5 |

## 5. Requisitos de experiencia de uso

- **Diseño primero para el teléfono** (*mobile-first*), sin desplazamiento horizontal desde 360 px de ancho (RNF001, IF-001). Se revisa en un teléfono Android y en uno iOS.
- **Registrar un pichón en 2 minutos o menos**, en el primer intento y sin capacitación (RNF002): formularios cortos y con valores por defecto (socio y club del perfil; padres tomados del cruce).
- **Ficha de un canario a 3 interacciones o menos** desde el inicio (RNF003).
- **Accesibilidad WCAG 2.1 nivel AA** (propuesta): etiquetas en todos los campos, contraste suficiente y uso con teclado.
- **Navegadores:** versiones actuales de Chrome, Safari, Edge y Firefox, en teléfono y en computador.
- Los usuarios son criadores, y muchos están acostumbrados al cuaderno: los botones y textos deben ser grandes, claros y con los términos de la sección 7.

## 6. Seguridad en las vistas

- **Escapar todo dato** que se muestre con `htmlspecialchars` (XSS, SEG-005).
- **Token CSRF** oculto en cada formulario que modifique datos.
- **No poner datos de otros criaderos** en ninguna vista, ni en campos ocultos ni en enlaces.
- Las contraseñas nunca se muestran ni se vuelven a cargar en el formulario.

## 7. Términos del dominio

Usar siempre estos términos en la interfaz, igual que en el SRS (§1.4):

| Término | Significado |
|---|---|
| Anillo | Identificador del canario: socio, año, correlativo y club. |
| Correlativo | El número con el que el criador nombra al canario en el día a día ("el 150"). |
| Jaula grande | Donde van los pichones y adultos después de la cría. No tiene número. |
| Cruce | Unión de un macho y una hembra en una jaula y temporada. |
| Ficha de cría | La vista de un cruce con sus pichones. |
| Pichón | Cría nacida de un cruce. |
| Variedad | Color reconocido del nomenclador, con su código (por ejemplo, 033 Rojo intenso). |
| Portador | Canario que lleva un factor sin manifestarlo. |
| Dar de baja | Sacar del criadero por venta, muerte u otra salida, sin borrar la historia. |

## 8. Flujo de trabajo

1. Diseño de pantallas en Figma, partiendo por el teléfono. El cliente revisa los mockups (SRS §7.2, Sesión 2).
2. Cada pantalla se trabaja en el issue del RF correspondiente.
3. QA prueba en teléfono antes de cerrar el issue.
