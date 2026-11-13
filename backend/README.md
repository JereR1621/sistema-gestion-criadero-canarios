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

Al salir, el controlador llama a `Sesion::cerrarAutenticada()`. Invalida los datos autenticados, elimina la cookie anterior y genera otro identificador y token CSRF. La sesión usa cookie `HttpOnly`, `SameSite=Lax`, `Secure` cuando la petición llega por HTTPS y expira tras siete días de inactividad. El término HTTPS se termina en AWS; HTTP se mantiene únicamente para el entorno local.
