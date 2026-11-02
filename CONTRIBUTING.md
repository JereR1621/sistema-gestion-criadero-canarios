# Cómo trabajamos en este repositorio

## Ramas

| Rama | Uso | Quién integra |
|---|---|---|
| `main` | Versión entregable (la que se despliega en producción). | Solo por pull request desde `develop`, aprobado por QA. |
| `develop` | Integración del trabajo terminado. Se despliega en el entorno de pruebas. | Por pull request desde una rama de trabajo, con una revisión. |
| `feature/RF0xx-descripcion` | Trabajo de un requisito (por ejemplo, `feature/RF023-busqueda-correlativo`). | — |
| `fix/descripcion` | Corrección de un defecto reportado por QA. | — |

Nadie sube cambios directo a `main` ni a `develop`.

## Commits

Mensaje corto en español, en presente, con el RF o el issue al que corresponde:

```
RF023: busca por correlativo y lista coincidencias (#23)
fix: rechaza anillo duplicado al anillar pichones (#61)
```

## Pull requests

1. Una rama y un pull request por sub-issue (nunca por épica). El PR dice `Cierra #NN` para enlazar el issue. Una épica se cierra cuando todos sus sub-issues están cerrados.
2. Completar la plantilla: qué RF cubre, cómo se probó y capturas si cambia una pantalla.
3. Un integrante del equipo revisa el código antes de unir a `develop`.
4. Para pasar de `develop` a `main`, QA aprueba después de probar en el entorno de pruebas.

## Cuándo se cierra un issue (definición de terminado)

- [ ] Cumple el texto del RF en el SRS v1.0.
- [ ] Respeta las reglas de integridad y de aislamiento entre criaderos (`docs/backend.md`, §2 y §3).
- [ ] Se ve bien en un teléfono de 360 px de ancho (si tiene pantalla).
- [ ] Tiene pruebas PHPUnit (si tiene lógica).
- [ ] QA lo probó y lo aprobó.

## Defectos

QA los registra con la plantilla **Defecto**, indicando el RF afectado, los pasos para reproducirlo y la severidad (alta, media o baja). Un defecto de severidad alta bloquea la entrega (SRS §7.1).

## Contrato backend–frontend

`docs/contrato-back-front.md` define qué datos recibe cada vista y cómo se llaman los campos de cada formulario. Si una pantalla necesita un dato que no está en el contrato, **no se inventa en la vista**: se modifica el contrato en un pull request que aprueban un integrante de backend y uno de frontend.

## Cambios de requisitos

Nadie cambia un requisito por su cuenta. Las solicitudes van al PM, que las evalúa y, si se aprueban, actualiza el SRS (registro de cambios) y los issues.

## Lo que nunca se sube

- Credenciales (AWS, MySQL): van en variables de entorno o en los *secrets* de GitHub.
- El Excel del cliente: trae datos personales de terceros. Solo se sube el seed generado a partir de él.
- Datos reales de criadores.
