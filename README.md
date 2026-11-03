# TuCriadero

Aplicación web para criadores de **canarios de color**. Reemplaza el cuaderno de crías por un registro digital que se usa desde el teléfono: canarios identificados por su anillo, jaulas, cruces, pichones, genealogía, bajas y la previsualización de lo que puede salir de un cruce según la tabla de Mendel.

| | |
|---|---|
| **Estado** | SRS v1.0 listo para la aceptación del cliente (todavía sin aprobar). El código está por empezar: hay estructura, sin implementación. |
| **Stack** | PHP con HTML embebido · MySQL 8 · AWS · PHPUnit · Apache JMeter · Figma |
| **Requisitos** | [`docs/srs/`](docs/srs/), SRS v1.0 (ISO/IEC/IEEE 29148): **es la fuente oficial.** Si un documento o una issue no coincide con el SRS, manda el SRS. |

## Empieza aquí

Lee en este orden, según tu equipo:

| Si eres de… | 1. Lee | 2. Prepara tu computador | 3. Toma una issue |
|---|---|---|---|
| **Backend** | [`backend/README.md`](backend/README.md) y [`docs/backend.md`](docs/backend.md) | [`docs/base-de-datos.md`](docs/base-de-datos.md): MySQL, PHP y tu base local | [Issues con `area:backend`](../../issues?q=is%3Aopen+label%3Aarea%3Abackend) |
| **Frontend** | [`frontend/README.md`](frontend/README.md) y [`docs/frontend.md`](docs/frontend.md) | PHP (sección 3 de [`docs/base-de-datos.md`](docs/base-de-datos.md)) | [Issues con `area:frontend`](../../issues?q=is%3Aopen+label%3Aarea%3Afrontend) |
| **Los dos** | [`docs/contrato-back-front.md`](docs/contrato-back-front.md): qué datos recibe cada vista y cómo se llaman los campos | — | — |

Antes de tu primer cambio, lee [`CONTRIBUTING.md`](CONTRIBUTING.md): ramas, commits, pull requests y cuándo se cierra una issue.

## Estructura del repositorio

```
backend/                  ← equipo de backend
  Controllers/            reciben la petición, validan permisos y llaman a los servicios
  Services/               reglas de negocio (Services/Genetica: cálculo de cruces)
  Models/                 acceso a MySQL con PDO y consultas preparadas
  database/
    migrations/           cambios de tablas: 001_crear_usuario.sql, 002_…
    seeds/                catálogo de 762 variedades y datos de prueba inventados
    migrar.php            aplica migraciones y seeds
  tests/                  pruebas PHPUnit
frontend/                 ← equipo de frontend
  views/                  una carpeta por módulo: views/cruces/formulario.php, …
  css/                    estilos (si se acuerda un archivo aparte)
public/
  index.php               punto de entrada de la web: solo carga el backend
docs/                     ← todos
  srs/                    SRS v1.0 (PDF)
  backend.md              modelo de datos, reglas, genética y seguridad
  base-de-datos.md        MySQL: instalación, migraciones y qué se sube a GitHub
  frontend.md             pantallas, mensajes y uso en el teléfono
  contrato-back-front.md  rutas, datos de cada vista y campos (preliminar: se acuerda en la épica E0)
  bpmn/                   12 procesos BPMN 2.0 con su trazabilidad al SRS
  img/                    modelo de datos: MER (Figura 1) y modelo relacional con PK y FK (Figura 2)
```

**Frontera entre los dos equipos:** el backend prepara los datos y se los pasa a la vista; la vista solo los muestra. Una vista **nunca** consulta la base de datos. Si una pantalla necesita un dato que no está en el contrato, se pide un cambio al contrato; no se inventa en la vista.

## Cómo se trabaja

1. Elige una issue de tu área en el hito actual y asígnatela.
2. Lee la issue completa. Todas tienen las mismas secciones: **qué hacer, por qué, qué incluye y qué no, el detalle, cómo hacerlo paso a paso, criterios de aceptación y definición de terminado**. Al final hay un glosario con los términos técnicos.
3. Crea **una rama solo para esa issue** desde `develop` actualizado, con el nombre que indica la issue en «Cómo hacerlo» (por ejemplo `feature/RF023-backend-busqueda-por-anillo-o-correlativo`).
4. Programa, prueba (PHPUnit y teléfono de 360 px) y sube tus cambios.
5. Abre un pull request hacia `develop` con `Closes #NN`. Claude lo revisa automáticamente y un integrante del equipo lo aprueba.
6. Al integrarse, GitHub borra la rama; bórrala también en tu computador (`git branch -d …`). La siguiente issue empieza con una rama nueva.
7. Si algo no está en la issue ni en el SRS, **no lo inventes**: pregunta al PM en un comentario de la issue.

## Cómo están organizadas las issues

- **9 épicas** (`tipo:epica`), una por etapa. Cada una agrupa sus RF y sus tareas.

  ```
  E0 Base técnica → E1 Cuentas y E2 Catálogo → E3 Canarios → E4 Jaulas → E5 Libro de crías
  E5 → E6 Búsqueda y genealogía y E7 Genética → E8 Beta
  ```

- **Cada RF con pantalla se divide en dos** (divide y vencerás):

  ```
  Épica E5: Libro de crías
  └─ SRS-RF018: Registro de cruce              tipo:requisito   → no se programa: se valida completo
     ├─ SRS-RF018 [Backend]: Registro de cruce   area:backend   → lo programa backend
     └─ SRS-RF018 [Frontend]: Registro de cruce  area:frontend  → lo programa frontend
  ```

  Los 7 RF sin pantalla (cálculo genético y catálogo) son una sola issue de backend.

- **5 hitos** (M1 a M5) y **5 revisiones externas**, una por hito, que coordina el PM con QA externo. QA externo entrega su informe y no recibe tareas de implementación.
- **127 issues** en total: 9 épicas, 39 RF, 64 implementaciones (32 de backend y 32 de frontend), 10 tareas y 5 revisiones.

| Etiqueta | Para qué sirve |
|---|---|
| `tipo:epica` | Agrupa una etapa. No se programa. |
| `tipo:requisito` | Un RF del SRS. Si tiene pantalla, agrupa su backend y su frontend y se cierra al validarlo completo. |
| `tipo:implementacion` | La parte de backend o de frontend de un RF. **Es lo que se programa.** |
| `tipo:tarea` | Trabajo técnico que no es un RF (estructura, esquema, seguridad, despliegue…). |
| `tipo:revision` / `tipo:defecto` | Revisión externa de un hito / defecto a corregir. |
| `area:backend`, `area:frontend`, `area:pruebas`, `area:gestion` | **Quién la hace.** Cada issue que se programa tiene una sola área. |
| `modulo:…` y `prioridad:…` | Módulo del SRS y prioridad del RF. |

**Para encontrar tu trabajo**, filtra por tu área: [backend](../../issues?q=is%3Aopen+label%3Aarea%3Abackend), [frontend](../../issues?q=is%3Aopen+label%3Aarea%3Afrontend).

Cerrar una issue significa que está implementada y revisada por el equipo; no equivale a la aprobación de la entrega.

## Lo que nunca se sube

- `.env` y cualquier credencial de MySQL o AWS: al repositorio solo va `.env.example`.
- El Excel del cliente: trae datos personales de un tercero. Solo se sube el seed generado.
- Volcados de bases con datos y datos reales de criadores.

## Puesta en marcha local

La base de datos se prepara con [`docs/base-de-datos.md`](docs/base-de-datos.md). El comando para levantar la aplicación lo agrega la tarea de estructura base cuando exista la primera versión ejecutable.

## Equipo

| Rol | Integrantes |
|---|---|
| Jefe de Proyecto (PM) | Jeremy Edgardo Rojas Herrera |
| QA externo | Boris Barrios, José Barraza |
| Desarrollo | Equipo A |
