# TuCriadero — Sistema de Gestión de Criadero de Canarios

Aplicación web para criadores de **canarios de color**. Reemplaza el cuaderno de crías por un registro digital, usable desde el teléfono: canarios identificados por su anillo, jaulas, cruces, pichones, genealogía, bajas y previsualización de los resultados de un cruce según la tabla de Mendel.

Nombre oficial del producto: **TuCriadero**.

## Documentación

| Documento | Para quién |
|---|---|
| [`docs/srs/`](docs/srs/) | Todos. Especificación de requisitos v1.0 (ISO/IEC/IEEE 29148). **Es la fuente oficial.** |
| [`docs/backend.md`](docs/backend.md) | Backend: modelo de datos, reglas, cálculo genético y seguridad. |
| [`docs/frontend.md`](docs/frontend.md) | Frontend: pantallas, mensajes, experiencia de uso en el teléfono. |
| [`docs/contrato-back-front.md`](docs/contrato-back-front.md) | Backend y frontend: rutas, datos que recibe cada vista, campos de cada formulario y errores. **Preliminar:** se acuerda en la épica E0. |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Todos: ramas, commits, pull requests y cómo cerrar un issue. |

## Stack

PHP con HTML embebido · MySQL 8.0.16 o superior · Amazon Web Services · PHPUnit · Apache JMeter · Figma

## Estructura

```
app/
  Controllers/        backend
  Services/           backend (Services/Genetica: cálculo de cruces)
  Models/             backend
  Views/              frontend
public/               punto de entrada (index.php)
database/
  migrations/         tablas
  seeds/              catálogo de variedades, factores y composición
tests/                PHPUnit
docs/                 SRS y resúmenes por equipo
```

## Cómo se organiza el trabajo

El trabajo está en los [issues](../../issues), en dos niveles:

- **9 épicas** (`tipo:epica`), una por etapa, con dependencias técnicas explícitas.

  ```
  E0 → E1 Cuentas y E2 Catálogo → E3 Canarios → E4 Jaulas → E5 Libro de crías
  E5 → E6 Búsqueda y genealogía y E7 Genética → E8 Beta
  ```

- Dentro de cada épica, sus **sub-issues** (un RF del SRS o una tarea) pueden diseñarse en paralelo; la integración y verificación respetan las dependencias indicadas en cada issue.

- **5 revisiones externas**, una por hito, coordinadas por el PM. QA externo entrega su informe y no recibe tareas de implementación ni de pruebas internas.

Plan actual: **63 issues** (9 épicas, 39 RF, 10 tareas y 5 revisiones externas). La tarea de entrega beta incluye despliegue, documentación y capacitación del SRS. Las revisiones no son sub-issues de implementación y su cierre no se confunde con la aceptación final del cliente.

Las etiquetas de área son `area:backend`, `area:frontend`, `area:pruebas` (pruebas internas del equipo) y `area:gestion` (coordinación del PM). Las issues pertenecen a un hito (M1 a M5); los RF además indican módulo y prioridad.

## Equipo

| Rol | Integrantes |
|---|---|
| Jefe de Proyecto (PM) | Jeremy Edgardo Rojas Herrera |
| QA externo | Boris Barrios, José Barraza |
| Desarrollo | Equipo A |

## Puesta en marcha local

Pendiente: la completa el equipo de backend cuando exista la primera versión ejecutable (requisitos de PHP y MySQL, variables de entorno, migraciones y seeds).
