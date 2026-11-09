# Cómo trabajamos en este repositorio

Estas reglas están en definición por el PM. El SRS v1.1 está preparado para presentarlo al cliente para su aceptación; su aprobación se registra por separado.

## Ramas

| Rama | Uso | Quién integra |
|---|---|---|
| `main` | Versión entregable (la que se despliega en producción). | Solo por pull request desde `develop`, con revisión interna y autorización del PM basada en la revisión externa del hito. |
| `develop` | Integración del trabajo terminado. Se despliega en el entorno de pruebas. | Por pull request desde una rama de trabajo, con una revisión. |
| `feature/RF0xx-backend-descripcion` / `feature/RF0xx-frontend-descripcion` | Trabajo de una issue de implementación (por ejemplo, `feature/RF023-backend-busqueda-por-anillo-o-correlativo`). Cada issue trae el nombre exacto en «Cómo hacerlo». | — |
| `fix/descripcion` | Corrección de un defecto detectado internamente o comunicado por QA externo. | — |
| `docs/descripcion` | Cambios de documentación y reglas del repositorio. | — |

Las ramas permanentes son `main` y `develop`. Las ramas de trabajo se nombran por el cambio que realizan. No se crea ni se mantiene una rama QA: las revisiones externas se registran mediante informes y las correcciones se realizan en ramas de trabajo del equipo.

### Una rama por issue, y se borra al integrar

1. **Cada issue tiene su propia rama**, creada desde `develop` actualizado. Nunca se trabajan dos issues en la misma rama.
2. **La rama vive solo mientras dura la issue.** Al integrar el pull request, GitHub la borra automáticamente.
3. **En tu computador también se borra:**
   ```
   git checkout develop
   git pull
   git branch -d feature/RF023-backend-busqueda-por-anillo-o-correlativo
   git fetch --prune
   ```
4. **Para la siguiente issue, una rama nueva** desde `develop` actualizado. No se reutiliza una rama vieja.

Así, en GitHub solo quedan `main`, `develop` y las ramas de las issues en curso: cada rama abierta corresponde a una issue que alguien está trabajando.

Nadie del equipo sube cambios directo a `main` ni a `develop`, las borra ni fuerza un push: lo impiden las reglas del repositorio. La única excepción es el PM (ver «Pull requests», punto 5).

## Commits

Mensaje corto en español, en presente, con el RF o el issue al que corresponde:

```
RF023: busca por correlativo y lista coincidencias (#23)
fix: rechaza anillo duplicado al anillar pichones (#61)
```

## Pull requests

1. Una rama y un pull request por issue de implementación o tarea (nunca por épica ni por el RF padre). El PR dice `Closes #NN` para enlazar el issue: GitHub solo reconoce palabras en inglés (`Closes`, `Fixes`, `Resolves`) y cierra el issue al integrar el PR en `develop`, la rama por defecto. Una épica se cierra cuando todos sus sub-issues están cerrados.
2. Completar la plantilla: qué RF cubre, cómo se probó y capturas si cambia una pantalla.
3. Un integrante del equipo revisa el código antes de unir a `develop`.
4. Para pasar de `develop` a `main`, el PM comprueba la revisión externa del hito y autoriza la entrega; un integrante del equipo revisa el PR. QA externo no tiene que aprobar PR ni operar GitHub.
5. El PM (JereR1621, administrador del repositorio) no tiene restricciones: las reglas «Proteger develop» y «Proteger main» lo exceptúan siempre, porque GitHub no deja aprobar un PR propio y el PM prepara la base del proyecto. El resto del equipo tiene rol **Write** (nunca Admin) y trabaja siempre por PR con una aprobación.

## Cuándo se cierra un issue (definición de terminado)

- [ ] Cumple el texto del RF en el SRS v1.1.
- [ ] Respeta las reglas de integridad y de aislamiento entre criaderos (`docs/backend.md`, §2 y §3).
- [ ] Se ve bien en un teléfono de 360 px de ancho (si tiene pantalla).
- [ ] Tiene pruebas PHPUnit (si tiene lógica).
- [ ] Si cambia la base: migración nueva (nunca editar una ya integrada) y `docs/backend.md` §3 actualizado (`docs/base-de-datos.md`).
- [ ] Pruebas internas realizadas y evidencias del método de verificación del SRS disponibles.
- [ ] Código revisado e integrado en `develop`.
- [ ] Rama de la issue borrada en GitHub y en tu computador.

Cerrar una implementación significa que está lista para revisión externa; no equivale a aprobar la entrega. Las tareas documentales se cierran con el entregable revisado por el equipo y la conformidad del PM. Un RF con pantalla se cierra cuando sus issues de backend y frontend están cerradas y el RF completo se comprobó en el entorno de pruebas. Una issue se empieza y se integra cuando las issues que la bloquean («Blocked by») están cerradas; no hace falta esperar a que se cierre su épica ni la anterior. Las épicas se cierran cuando todos sus sub-issues están cerrados, en el orden que indica su «Depende de»; la entrega beta además exige las revisiones externas previstas.

## QA externo y aprobación de entregas

Boris Barrios y José Barraza forman el equipo QA externo. Revisan el proyecto y entregan sus observaciones por correo al PM. No son responsables de implementar funcionalidades, ejecutar las pruebas internas del equipo ni mantener el tablero.

Hay una issue de revisión externa por hito, `QA-M1` a `QA-M5`, con `tipo:revision` y `area:gestion`. La coordina el PM y registra la versión revisada, las evidencias, el informe de QA y las correcciones. Las tareas de pruebas internas usan `area:pruebas` y se asignan al equipo de desarrollo. No se asignan issues a QA externo por defecto.

La revisión de M5 se realiza en el entorno de pruebas después de seguridad, respaldos, carga y usabilidad, antes del despliegue beta. La aceptación final del cliente tras el periodo beta se registra después, sin darla por completada al desplegar.

## Defectos

El PM registra los hallazgos recibidos de QA externo con la plantilla **Defecto**; el equipo también registra los detectados internamente. Se indica el RF afectado, la referencia del informe (si corresponde), los pasos para reproducirlo y la severidad (alta, media o baja). La corrección se asigna a desarrollo. Un defecto alto bloquea la entrega; uno medio requiere mitigación acordada con el cliente (SRS §8.2 del documento completo; §7.1 de la versión cliente). El PM registra la verificación posterior y el resultado de QA en la revisión del hito.

## Contrato backend–frontend

`docs/contrato-back-front.md` define qué datos recibe cada vista y cómo se llaman los campos de cada formulario. Si una pantalla necesita un dato que no está en el contrato, **no se inventa en la vista**: se modifica el contrato en un pull request que aprueban un integrante de backend y uno de frontend.

## Cambios de requisitos

Nadie cambia un requisito por su cuenta. Las solicitudes van al PM, que las evalúa y, si se aprueban, actualiza el SRS (registro de cambios) y los issues.

## Lo que nunca se sube

- Credenciales (AWS, MySQL): van en `.env` (local) o en las variables de AWS y los *secrets* de GitHub. Al repositorio solo va `.env.example`.
- Volcados o respaldos de una base con datos (`mysqldump`).
- El Excel del cliente: trae datos personales de terceros. Solo se sube el seed generado a partir de él.
- Datos reales de criadores.
