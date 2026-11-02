# Sistema de Gestión de Criadero de Canarios

Aplicación web para criadores de **canarios de color**. Reemplaza el cuaderno de crías por un registro digital, usable desde el teléfono: canarios identificados por su anillo, jaulas, cruces, pichones, genealogía, bajas y previsualización de los resultados de un cruce según la tabla de Mendel.

Nombre comercial tentativo: TuCriadero (por confirmar con el cliente).

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

- **9 épicas** (`tipo:epica`), una por etapa. Forman una cadena: cada una depende solo de la anterior.

  ```
  E0 Base técnica ─┬─ E1 Cuentas ── E3 Canarios ── E4 Jaulas ── E5 Libro de crías ─┬─ E6 Búsqueda y genealogía ─┬─ E8 Beta
                   └─ E2 Catálogo                                                   └─ E7 Genética y cruces ───────┘
  ```

- Dentro de cada épica, sus **sub-issues** (un RF del SRS o una tarea) son independientes entre sí: se pueden repartir y trabajar en paralelo en cuanto la épica anterior está cerrada.

Cada issue tiene etiquetas de área (`area:backend`, `area:frontend`, `area:qa`), de módulo y de prioridad, y pertenece a un hito (M1 a M5).

## Equipo

| Rol | Integrantes |
|---|---|
| Jefe de Proyecto (PM) | Jeremy Edgardo Rojas Herrera |
| QA | Boris Barrios, José Barraza |
| Desarrollo | Equipo A |

## Puesta en marcha local

Pendiente: la completa el equipo de backend cuando exista la primera versión ejecutable (requisitos de PHP y MySQL, variables de entorno, migraciones y seeds).
