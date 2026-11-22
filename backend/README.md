# backend/

Aquí trabaja el equipo de backend: rutas, permisos, validaciones, reglas de negocio, cálculo genético y base de datos.

## Qué va en cada carpeta

| Carpeta | Qué va | Ejemplo |
|---|---|---|
| `Controllers/` | Recibe la petición, comprueba la sesión y el permiso, llama al servicio y elige la vista o la redirección. **Sin reglas de negocio ni SQL.** | `CruceController.php`: `nuevo()`, `guardar()` |
| `Services/` | Las reglas del SRS: validaciones, integridad (`docs/backend.md` §3) y transacciones. | `CruceService.php` |
| `Services/Genetica/` | El cálculo de cruces y la deducción de portadores (`docs/backend.md` §5). Sin acceso directo a la base. | `HerenciaLigadaAlSexo.php` |
| `Models/` | Consultas a MySQL con PDO y consultas preparadas. Las de datos de un criadero (canarios, jaulas, cruces, ubicaciones, bajas y portadores) **siempre** filtran por el `criadero_id` de la sesión; las de cuentas y catálogo no tienen criadero y las protege la sesión y el rol. | `CanarioModel.php` |
| `database/migrations/` | Cambios de tablas en SQL numerado. Una migración integrada no se edita nunca. | `001_crear_usuario.sql` |
| `database/seeds/` | Catálogo (880 variedades, factores, composición) y datos de prueba inventados. | `01_variedades.sql` |
| `database/migrar.php` | Aplica migraciones y seeds (`docs/base-de-datos.md` §6). | `php backend/database/migrar.php --seeds` |
| `tests/` | Pruebas PHPUnit, con nombre `*Test.php`. Usan la base `tucriadero_pruebas`. | `HerenciaLigadaAlSexoTest.php` |

Los nombres de clases de la columna "Ejemplo" son orientativos; los fija la tarea de estructura base.

## Antes de programar

1. [`docs/backend.md`](../docs/backend.md): modelo de datos, reglas, genética y seguridad.
2. [`docs/base-de-datos.md`](../docs/base-de-datos.md): instala MySQL y PHP y crea tu base local.
3. [`docs/contrato-back-front.md`](../docs/contrato-back-front.md): qué datos le entregas a cada vista y qué campos recibes de cada formulario.

## Reglas que no se negocian

- El `criadero_id` sale **siempre de la sesión**, nunca del formulario ni de la URL. Un registro de otro criadero responde 404, igual que si no existiera.
- Solo consultas preparadas: nunca se concatenan datos del usuario en el SQL.
- La base cambia solo con una migración nueva, en el mismo pull request que el código que la usa.
- Al terminar un POST con éxito, se redirige (POST → redirección → GET). Si hay errores, se vuelve a mostrar la vista con `$errores` y `$viejo`.
- Ninguna credencial en el código: todo sale del `.env`.

## Sesión autenticada

La base entrega `TuCriadero\Core\Sesion` para que RF001 y RF002 no implementen sesiones distintas. Después de registrar o validar las credenciales, el controlador llama a `Sesion::iniciarAutenticada($usuarioId, $rol, $criaderoId, $nombre)`: regenera el identificador de sesión y guarda `usuario_id`, `rol` y `criadero_id`. El cuarto argumento solo permite mostrar el nombre en las vistas.

Al salir, el controlador llama a `Sesion::cerrarAutenticada()`. Invalida los datos autenticados, elimina la cookie anterior y genera otro identificador y token CSRF. La sesión usa cookie `HttpOnly`, `SameSite=Lax`, `Secure` cuando la petición llega por HTTPS y expira tras siete días de inactividad: cada petición renueva la cookie y PHP conserva los datos de la sesión durante ese plazo. HTTPS se termina en AWS; HTTP se usa solo en el entorno local.

## Conexión a la base

Los modelos piden la conexión con `TuCriadero\Core\Conexion::obtener()`: devuelve un `PDO` con errores como excepciones, resultados como arreglos asociativos y consultas preparadas reales. Es la misma conexión durante toda la petición y se abre la primera vez que alguien la pide; una página que no usa la base funciona aunque MySQL esté detenido. No crees otra conexión con `new PDO`.

En las pruebas, `Conexion::usar($pdo)` cambia la conexión (por ejemplo, a la base `tucriadero_pruebas`) y `Conexion::usar(null)` vuelve a la normal.

## Formularios y token CSRF

La base valida el token de **todas** las peticiones que no son GET antes de llegar al controlador. Si el campo `_csrf` falta o no coincide con el de la sesión, responde 419 con la vista `errores/sesion` (contrato §7). Los controladores no tienen que validarlo; las vistas solo incluyen el campo oculto del contrato §4.

## Errores

Para responder un error desde un controlador, lanza `HttpException::noEncontrado()` (404), `prohibido()` (403), `sesionExpirada()` (419) o `mantenimiento()` (503). Cualquier otro error responde 500 sin detalles: el navegador nunca ve el mensaje técnico. El detalle (clase, mensaje, archivo y línea) queda en el registro del servidor; con Docker se ve con `docker compose logs app`. Con `APP_ENV=local`, PHP además muestra en pantalla los avisos que ocurran fuera de esa captura.

## Auditoría: `auditar()`

Toda operación que guarda datos llama a `auditar($accion, $entidad, $entidadId)` (SEG-003), definida en `backend/Models/AuditoriaHelper.php` y cargada por `backend/autoload.php`. Inserta en la tabla `auditoria` el usuario de la sesión, la acción, la entidad, su id y la fecha y hora. El `$entidadId` puede ser un id numérico (un canario) o un código (una variedad del catálogo, `D.049`; migración `016`).

```php
// En el servicio, dentro de la misma transacción que el cambio:
auditar('crear', 'canario', $canarioId);
```

- Usa `Conexion::obtener()`: si la transacción se deshace, el registro de auditoría también.
- Sin sesión iniciada (por ejemplo, en el registro), guarda la acción con `usuario_id` NULL.
- No oculta errores: si no puede guardar, la operación falla.

## Aislamiento entre criaderos y control de rol (RF003)

Todo controlador de criador o de administración hereda de `TuCriadero\Controllers\BaseController`, y todo modelo de datos de criadero (`canario`, `jaula`, `cruce`, `ubicacion`, `baja` y `factor_portado`) hereda de `TuCriadero\Models\ModeloCriadero`.

### 1. En los controladores (`BaseController`)

- `$sesion = $this->exigirCriador()`: comprueba sesión activa con rol `criador` y `criadero_id` válido. Redirige a `/ingresar` si no hay sesión y responde **403** si entra un administrador.
- `$criaderoId = $this->criaderoIdDeSesion()`: devuelve el `criadero_id` **de la sesión** (nunca de `$_GET`, `$_POST` ni de la URL).
- `$sesion = $this->exigirAdmin()`: comprueba sesión activa con rol `admin`. Responde **403** si entra un criador.
- `$id = $this->exigirIdPositivo($parametro)`: valida el `{id}` de la ruta o responde **404**.
- `$registro = $this->exigirRegistroPropio($resultado)`: si el modelo devolvió `null` (no existe o es de otro criadero), responde **404** sin mostrar datos.

```php
final class JaulaController extends BaseController
{
    public function ficha(string $id): void
    {
        $criaderoId = $this->criaderoIdDeSesion();
        $jaulaId = $this->exigirIdPositivo($id);
        $jaula = $this->exigirRegistroPropio((new JaulaModel())->buscarPorIdEnCriadero($jaulaId, $criaderoId));

        render('jaulas/ficha', ['jaula' => $jaula]);
    }
}
```

### 2. En los modelos de criadero (`ModeloCriadero`)

Usa `buscarUnoDelCriadero()`, `buscarTodosDelCriadero()`, `ejecutarEnCriadero()` y `exigirDelCriadero()`. Toda consulta debe incluir `criadero_id = :criadero_id` (en `ubicacion`, `baja` y `factor_portado`, mediante `INNER JOIN canario c ON c.id = ... WHERE c.criadero_id = :criadero_id`); el parámetro `:criadero_id` siempre lo vincula `ModeloCriadero` con el `$criaderoId` de la sesión.

```php
$fila = $this->buscarUnoDelCriadero(
    'SELECT id, criadero_id, numero FROM jaula WHERE criadero_id = :criadero_id AND id = :id LIMIT 1',
    $criaderoId,
    [':id' => $id]
);
```

### 3. En los servicios (`AislamientoService`)

Antes de guardar relaciones enviadas desde un formulario (`canario_id`, `jaula_id`, `cruce_id`, `padre_id`, `madre_id`), valida pertenencia con `TuCriadero\Services\AislamientoService`:

- `exigirCanarioPropio()` / `exigirJaulaPropia()` / `exigirCrucePropio()` → responden **404** si el registro es de otro criadero.
- `validarCanarioYJaulaDelCriadero()` → rechaza canario o jaula ajena sin guardar nada.
- `validarCruceActivoDelCriadero()` → rechaza cruce terminado o de otro criadero.
- `validarPadresDelCriadero()` → rechaza padre o madre de otro criadero.
- `validarCanarioParaBajaEnCriadero()` → rechaza canario de otro criadero o ya dado de baja.

### Escrituras genealógicas de RF007

`CanarioService::registrar()` y `actualizar()` abren la transacción y bloquean la fila del criadero con
`CanarioModel::bloquearGenealogiaEnCriadero()` antes de leer y validar padres e hijos. El bloqueo dura hasta
confirmar o deshacer la operación; las consultas siguen filtradas por el criadero de la sesión. Las futuras
operaciones que cambien sexo, año o relaciones genealógicas deben respetar este mismo orden.

Al editar un progenitor se preservan también el sexo y la precedencia del año respecto de sus hijos ya registrados.
La regresión de dos sesiones está en `CanarioServiceTest`, con un trabajador CLI exclusivo de la base de pruebas:
la primera sesión espera antes de abrir la transacción y vuelve a validar el estado confirmado por la segunda.
