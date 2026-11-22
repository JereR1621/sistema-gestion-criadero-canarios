# TuCriadero

Aplicación web para criadores de **canarios de color**. Reemplaza el cuaderno de crías por un registro digital que se usa desde el teléfono: canarios identificados por su anillo, jaulas, cruces, pichones, genealogía, bajas y la previsualización de lo que puede salir de un cruce según la tabla de Mendel.

| | |
|---|---|
| **Estado** | SRS v1.1 listo para la aceptación del cliente (todavía sin aprobar); corrige la v1.0 con el nomenclador oficial 2026 que entregó el cliente. El desarrollo está en curso: ya están integrados en `develop` el backend de cuentas, aislamiento entre criaderos, catálogo, jaulas y parte de la genética. El avance de cada parte se sigue en las issues. |
| **Stack** | PHP con HTML embebido · MySQL 8 · AWS · PHPUnit · Apache JMeter · Figma |
| **Requisitos** | [`docs/srs/`](docs/srs/), SRS v1.1 (ISO/IEC/IEEE 29148): **es la fuente oficial.** Si un documento o una issue no coincide con el SRS, manda el SRS. Los pedidos que el cliente acordó después del SRS están en [`docs/ajustes-cliente.md`](docs/ajustes-cliente.md) (etiqueta `ajuste:cliente`) y lo amplían. |

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
    seeds/                catálogo de 880 variedades y datos de prueba inventados
    migrar.php            aplica migraciones y seeds
  tests/                  pruebas PHPUnit
frontend/                 ← equipo de frontend
  views/                  una carpeta por módulo: views/cruces/formulario.php, …
  css/                    estilos (si se acuerda un archivo aparte)
public/
  index.php               punto de entrada de la web: solo carga el backend
docs/                     ← todos
  srs/                    SRS v1.1 (PDF)
  ajustes-cliente.md      pedidos del cliente acordados después del SRS (AC-01 a AC-05)
  guia-issues.md          nomenclatura de las issues y árbol de qué desbloquea qué (backend y frontend)
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
5. Abre un pull request hacia `develop` con `Closes #NN`. Claude lo revisa automáticamente y otro integrante del equipo lo revisa y lo aprueba. **Solo el PM (`JereR1621`) integra en `develop` y en `main`**: comprueba la issue, sus dependencias y los acuerdos del proyecto antes de integrar.
6. Al integrarse, GitHub borra la rama; bórrala también en tu computador (`git branch -d …`). La siguiente issue empieza con una rama nueva.
7. Si algo no está en la issue, en el SRS ni en `docs/ajustes-cliente.md`, **no lo inventes**: pregunta al PM en un comentario de la issue.

## Cómo están organizadas las issues

> **Guía completa:** [`docs/guia-issues.md`](docs/guia-issues.md), con la nomenclatura, por dónde empezar y el árbol de qué desbloquea qué para backend y para frontend.

- **9 épicas** (`tipo:epica`), una por etapa. Cada una agrupa sus RF y sus tareas.

  ```
  E0 Base técnica → E1 Cuentas y E2 Catálogo → E3 Canarios → E4 Jaulas → E5 Libro de crías
  E5 → E6 Búsqueda y genealogía y E7 Genética → E8 Beta
  ```

  Las flechas son el **orden de cierre** de las épicas. Para empezar o integrar una issue **mandan sus propias dependencias** («Blocked by» en GitHub), no la épica: no hace falta esperar a que se cierre la épica anterior.

- **Cada RF con pantalla se divide en dos** (divide y vencerás):

  ```
  E5: Libro de crías
  └─ SRS-RF018: Registro de cruce              tipo:requisito   → no se programa: se valida completo
     ├─ SRS-RF018 [Backend]: Registro de cruce   area:backend   → lo programa backend
     └─ SRS-RF018 [Frontend]: Registro de cruce  area:frontend  → lo programa frontend
  ```

  Los 11 RF sin pantalla (reglas como el aislamiento entre criaderos, cálculo genético y catálogo) son una sola issue de backend.

- **Cada ruta y cada vista las crea una sola issue.** Si un RF necesita algo en una pantalla que crea otro RF, su issue dice «**No crees** … : la crea #NN. Edítala y agrega …» y depende de esa issue. Por ejemplo, el formulario de canarios lo crea RF007, y RF008, RF009, RF010, RF012, RF013 y RF029 le agregan su parte. Así dos personas nunca crean el mismo archivo.

- **5 hitos** (M1 a M5) y **5 revisiones externas**, una por hito, que coordina el PM con QA externo. QA externo entrega su informe y no recibe tareas de implementación.
- **127 issues**: 9 épicas (`E0`–`E8`), 39 RF (`SRS-RFxxx`), 2 ajustes del cliente (`AC-03` y `AC-04`), 60 implementaciones (`[Backend]` y `[Frontend]`), 12 tareas (`T01`–`T12`) y 5 revisiones (`QA-M1`–`QA-M5`). Toda issue empieza por su ID.

| Etiqueta | Para qué sirve |
|---|---|
| `tipo:epica` | Agrupa una etapa. No se programa. |
| `tipo:requisito` | Un RF del SRS. Si tiene pantalla, agrupa su backend y su frontend y se cierra al validarlo completo. |
| `tipo:implementacion` | La parte de backend o de frontend de un RF. **Es lo que se programa.** |
| `tipo:tarea` | Trabajo técnico que no es un RF (estructura, esquema, seguridad, despliegue…). |
| `tipo:revision` / `tipo:defecto` | Revisión externa de un hito / defecto a corregir. |
| `area:backend`, `area:frontend`, `area:pruebas`, `area:gestion` | **Quién la hace.** Cada issue que se programa tiene una sola área. |
| `modulo:…` y `prioridad:…` | Módulo del SRS y prioridad del RF. |
| `ajuste:cliente` | Pedido del cliente acordado después del SRS ([`docs/ajustes-cliente.md`](docs/ajustes-cliente.md)). |

**Para encontrar tu trabajo**, filtra por tu área: [backend](../../issues?q=is%3Aopen+label%3Aarea%3Abackend), [frontend](../../issues?q=is%3Aopen+label%3Aarea%3Afrontend).

Cerrar una issue significa que está implementada y revisada por el equipo; no equivale a la aprobación de la entrega.

## Lo que nunca se sube

- `.env` y cualquier credencial de MySQL o AWS: al repositorio solo va `.env.example`.
- El Excel del cliente: trae datos personales de un tercero. Solo se sube el seed generado.
- Volcados de bases con datos y datos reales de criadores.

## Puesta en marcha local

Todo el detalle está en [`docs/base-de-datos.md`](docs/base-de-datos.md) §3 y §4. Hay dos opciones:

- **Con Docker (recomendada):** copia `.env.example` como `.env`, completa las contraseñas y ejecuta:
  ```
  docker compose up -d
  docker compose exec app composer install
  docker compose exec app composer test
  ```
  Abre <http://localhost:8080/inicio>. El enlace **Descargar PDF de prueba** debe entregar un PDF generado desde una vista PHP.
- **Instalación manual:** instala PHP 8.3 con `pdo_mysql`, `mbstring`, `gd` y `zip`, más MySQL 8.4. Copia `.env.example` como `.env`, completa `DB_PASS` y ejecuta:
  ```
  composer install
  composer test
  php -S localhost:8000 -t public public/router.php
  ```
  Abre <http://localhost:8000/inicio>.

Esta base abre la conexión PDO, pero no incluye migraciones, seeds ni `backend/database/migrar.php`: son el alcance de la tarea de esquema (T02). No son necesarios para ver `/inicio`; sí lo serán antes de usar cualquier módulo que guarde datos. Docker publica `frontend/css/` en `/css/`; el router de `public/` hace lo mismo al usar el servidor integrado de PHP.

En AWS, el entorno de pruebas debe terminar TLS y exponer solo HTTPS. Las credenciales se configuran como variables de entorno del servicio, nunca en `.env` ni en el repositorio.

## Equipo

| Rol | Integrantes |
|---|---|
| Jefe de Proyecto (PM) | Jeremy Edgardo Rojas Herrera |
| QA externo | Boris Barrios, José Barraza |
| Desarrollo | Equipo B |
