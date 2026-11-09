# Ajustes del cliente

Pedidos que el cliente acordó con el PM en reunión **después del SRS v1.1**. El SRS no se modifica: estos ajustes lo **amplían**, y en lo que tocan **mandan sobre el SRS**. Cada ajuste dice qué RF amplía y qué issues lo programan; esas issues llevan la etiqueta `ajuste:cliente`.

Si un ajuste no responde una duda, **no se inventa**: se pregunta al PM en un comentario de la issue.

| ID | Fecha | Ajuste | Amplía | Dónde se programa |
|---|---|---|---|---|
| AC-01 | 09/11/2026 | Árbol genealógico de 3 generaciones en la ficha del canario | RF024 | Issues de SRS-RF024 |
| AC-02 | 09/11/2026 | Al formar un cruce, solo se ofrecen los canarios disponibles | RF018 | Issues de SRS-RF018 |
| AC-03 | 09/11/2026 | Estado «reservado» de un canario | RF007, RF011, RF018 | Issues de AC-03 (nuevas) |
| AC-04 | 09/11/2026 | Exportar la ficha del canario en PDF | RF024 | Issues de AC-04 (nuevas) |
| AC-05 | 09/11/2026 | El anillo es el motor de búsqueda principal | RF023 | Issues de SRS-RF023, adelantadas al hito M2 |

## AC-01 Árbol genealógico de 3 generaciones

- La ficha del canario muestra su árbol genealógico: **padres, abuelos y bisabuelos** (hasta 14 antepasados), cada uno con enlace a su ficha.
- Se llega a él buscando el canario por su anillo (RF023 y AC-05).
- Un antepasado que no está registrado (por ejemplo, los padres de un canario adquirido, RF010) se muestra como **«Sin registro»** y su rama termina ahí.
- Solo se muestran canarios del mismo criadero (RF003). Los dados de baja aparecen igual, porque la baja conserva la genealogía (RF011).
- Contrato: `CanarioFicha.arbol` (§5).

## AC-02 Solo canarios disponibles para un cruce

- Al formar un cruce, las listas de machos y hembras muestran solo canarios **disponibles**: no reservados (AC-03), no dados de baja y con el sexo conocido (RF018).
- Si alguien envía igual un canario no disponible, el backend lo rechaza con el mensaje «Este canario está reservado: no puede formar un cruce».
- **Un canario que ya está en un cruce activo sigue disponible:** el SRS permite una hembra con varios machos y un macho con varias hembras (RF019 y RF020).

## AC-03 Estado «reservado»

- El criador puede marcar un canario como **reservado** desde su ficha, y quitarle la reserva.
- Un reservado **sigue en el criadero**: aparece en los listados y en las búsquedas con la etiqueta «Reservado», y se puede ubicar en jaulas.
- Un reservado **no puede formar cruces nuevos** (AC-02). Los cruces que ya tenía no cambian.
- Un reservado se puede dar de baja (RF011); un canario dado de baja no se puede reservar.
- Datos: `canario.estado` pasa a ser `activo` / `reservado` / `baja`. En el SRS, «canario activo» significa «no dado de baja»: incluye a los reservados.
- Contrato: ruta `canarios.reserva` (§6) y `estado` en `CanarioResumen` (§5).

## AC-04 Exportar la ficha en PDF

- La ficha del canario tiene un botón **«Exportar PDF»**. El servidor genera un PDF con los datos de la ficha: anillo, sexo, variedad, estado, ubicación, procedencia, fechas, factores que manifiesta, portadores y el árbol de 3 generaciones (AC-01).
- El PDF se arma con una vista HTML propia y una librería de PHP. La propuesta es **Dompdf**, instalada con Composer; la confirma la tarea de estructura base.
- Solo se exporta un canario del propio criadero (404 si es de otro, RF003). Un dado de baja también se exporta, con su motivo y fecha de baja.
- Contrato: ruta `canarios.pdf` (§6).

## AC-05 El anillo como motor de búsqueda

- El buscador por anillo o correlativo (RF023) es la **entrada principal** del sistema: está visible de inmediato en el inicio y lleva a la ficha.
- Por eso las issues de RF023 pasan al hito **M2** (épica E3, Canarios): solo necesitan que existan los canarios. Las búsquedas por variedad y por jaula siguen en M3.

## Información del cliente para el equipo (audio del 09/11/2026)

No son ajustes: el cliente explicó conceptos que sirven para armar la composición de las variedades (tarea de composición del catálogo) y para probar el cálculo genético.

- **Lipocromo:** el color de la grasa en la pluma. Hay tres de base: rojo, amarillo y blanco.
- **Categoría:** cómo se distribuye el lipocromo. Intenso (parejo), nevado (con una decoloración en la punta de las plumas) y mosaico (concentrado en máscara, hombros, alas y rabadilla). En el mosaico hay dimorfismo: macho y hembra se ven distintos, por eso hay variedades «mosaico macho» y «mosaico hembra».
- **Melánicos:** los que tienen melanina. Cuatro tipos clásicos: negro, bruno, ágata (negro diluido) e isabela (bruno diluido). El cliente usa **canela, marrón y bruno como sinónimos**; el nomenclador 2026 usa «bruno».
- **Marfil:** factor que hace que el color llegue con menos intensidad a la pluma.
- **Ópalo:** factor recesivo que da una refracción azul a la eumelanina.
- **Caso de prueba del cliente (RF037):** negro ópalo amarillo mosaico macho × negro amarillo marfil mosaico hembra → como el ópalo es recesivo, **ninguna cría manifiesta ópalo y todas lo portan**.
- **Alcance:** la beta es solo de canarios de color. La forma y postura se evaluaría después, cuando el color esté funcionando por completo. El canto no se incluye.

Fuente: transcripción revisada del audio y de las notas del 09/11/2026, a cargo del PM.
