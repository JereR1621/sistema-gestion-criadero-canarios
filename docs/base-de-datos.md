# Base de datos: cómo trabajar con MySQL

Guía para el equipo de backend. Explica cómo instalar MySQL y PHP en tu computador, cómo se crea la base, qué se sube a GitHub y cómo se cambia una tabla. El modelo de datos (tablas, columnas y reglas) está en [`backend.md`](backend.md) §3.

## 1. Las cinco reglas

1. **Cada integrante tiene su propio MySQL en su computador.** Nadie programa contra la base del entorno de pruebas de AWS: es compartida y no se rompe.
2. **La base se cambia solo con una migración nueva, por pull request.** Nadie crea ni modifica tablas a mano. Si tu RF necesita una columna, escribes una migración (sección 5).
3. **Una migración que ya está en `develop` no se edita nunca.** Si tiene un error, se corrige con otra migración nueva.
4. **`backend.md` §3 y las migraciones dicen lo mismo.** Si cambias una tabla, actualizas las dos en el mismo pull request.
5. **Las credenciales nunca van al repositorio.** Viven en tu archivo `.env`, que Git ignora.

## 2. Qué va en GitHub y qué no

| Va en GitHub | Nunca va en GitHub |
|---|---|
| Migraciones: `database/migrations/NNN_descripcion.sql` | `.env` con usuario y contraseña reales |
| Seeds del catálogo: variedades, factores y composición (sin datos personales) | El Excel del cliente: trae datos personales de un tercero |
| Seed de datos de prueba **inventados** | Volcados o respaldos de una base con datos (`mysqldump`) |
| `.env.example`: los nombres de las variables, sin valores secretos | Datos reales de criadores de la beta |
| `database/migrar.php`, el script que aplica migraciones y seeds | Credenciales de la base de AWS: van en AWS o en los *secrets* de GitHub |

Antes de cada commit, revisa con `git status` qué vas a subir. Si aparece `.env`, un `.xlsx` o un volcado, **no lo agregues**. El `.gitignore` bloquea los casos conocidos, pero la revisión es tuya.

## 3. Instalar las herramientas (una sola vez)

Versiones: **MySQL 8.4 LTS** (sirve cualquier 8.0.16 o superior; las anteriores ignoran las restricciones `CHECK`) y **PHP 8.2 o superior** (recomendado 8.3).

### MySQL

1. Descarga **MySQL Community Server 8.4 LTS** para Windows (instalador MSI) desde <https://dev.mysql.com/downloads/mysql/>.
2. Instálalo con las opciones por defecto. Al configurarlo: puerto **3306**, una contraseña para `root` que **anotas solo para ti**, y "iniciar MySQL como servicio de Windows".
3. Agrega `C:\Program Files\MySQL\MySQL Server 8.4\bin` a la variable de entorno `PATH`.
4. Comprueba en una terminal nueva:
   ```
   mysql --version
   ```
   Debe decir `8.4` (o una 8.0.16 o superior).

### PHP

1. Descarga **PHP 8.3, VS16 x64 Thread Safe** (archivo .zip) desde <https://windows.php.net/download/>.
2. Descomprímelo en `C:\php`.
3. Copia `C:\php\php.ini-development` como `C:\php\php.ini` y, en ese archivo, quita el `;` del inicio de estas líneas:
   ```
   extension_dir = "ext"
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   ```
4. Agrega `C:\php` a la variable `PATH`.
5. Comprueba en una terminal nueva:
   ```
   php -v
   php -m
   ```
   `php -v` debe mostrar la versión, y la lista de `php -m` debe incluir `pdo_mysql`.

En macOS o Linux, instala las mismas versiones con el gestor de paquetes del sistema y comprueba lo mismo.

## 4. Crear tu base local (una sola vez)

1. Entra a MySQL como `root`:
   ```
   mysql -u root -p
   ```
2. Crea la base de trabajo, la base para las pruebas automáticas y un usuario solo para el proyecto. Cambia `clave-local` por una contraseña tuya:
   ```sql
   CREATE DATABASE tucriadero CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
   CREATE DATABASE tucriadero_pruebas CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
   CREATE USER 'tucriadero'@'localhost' IDENTIFIED BY 'clave-local';
   GRANT ALL PRIVILEGES ON tucriadero.* TO 'tucriadero'@'localhost';
   GRANT ALL PRIVILEGES ON tucriadero_pruebas.* TO 'tucriadero'@'localhost';
   EXIT;
   ```
3. En la carpeta del repositorio, copia `.env.example` como `.env` y completa `DB_PASS` (y `ADMIN_CONTRASENA`) con tus valores locales.
4. Crea las tablas y carga el catálogo y los datos de prueba:
   ```
   php database/migrar.php --seeds
   ```
5. Comprueba que funcionó:
   ```
   mysql -u tucriadero -p tucriadero -e "SELECT COUNT(*) FROM variedad;"
   ```
   Debe responder **762**.

Si rompes tu base, la vuelves a crear desde cero con `php database/migrar.php --desde-cero --seeds`. Solo funciona con `APP_ENV=local`: así nadie borra por error la base del entorno de pruebas.

## 5. Cambiar una tabla: crear una migración

Ejemplo: tu RF necesita una tabla o una columna nueva.

1. Actualiza `develop` y crea tu rama de trabajo (ver `CONTRIBUTING.md`).
2. Mira el número más alto en `database/migrations/` y crea el siguiente, con un nombre que diga qué hace:
   ```
   database/migrations/014_agregar_observacion_a_baja.sql
   ```
3. Escribe solo SQL de MySQL, un cambio por archivo:
   ```sql
   -- RF011: observación opcional en la baja (ejemplo)
   ALTER TABLE baja ADD COLUMN observacion VARCHAR(255) NULL;
   ```
4. Aplícala en tu base: `php database/migrar.php`. El script corre solo las migraciones que tu base todavía no tiene.
5. Actualiza `backend.md` §3 si cambió una tabla.
6. Sube la migración y el código del RF en el **mismo pull request**. Quien revisa comprueba que la migración corre sobre una base limpia.

Si dos personas crean el mismo número al mismo tiempo, la segunda en integrar renumera la suya antes de unir a `develop`.

### Convenciones de las migraciones

- Nombre: tres dígitos, guion bajo y descripción en minúsculas: `001_crear_usuario.sql`.
- Tablas y columnas con los nombres de `backend.md` §3, en minúsculas y *snake_case*.
- `ENGINE=InnoDB`, `utf8mb4` y claves foráneas declaradas con `FOREIGN KEY`.
- Las restricciones que pide el modelo (`UNIQUE`, `CHECK`, `NOT NULL`) van en la migración, no solo en el código.
- Las reglas que no se pueden expresar en SQL van en los servicios (`backend.md` §3, "Reglas que no alcanzan a cubrir FK ni UNIQUE").

## 6. Qué hace `database/migrar.php`

Lo programa la tarea "Esquema de base de datos" de la épica E0. Su comportamiento es fijo, para que todos lo usen igual:

| Comando | Qué hace |
|---|---|
| `php database/migrar.php` | Lee `.env`, se conecta con PDO y aplica en orden numérico las migraciones de `database/migrations/` que no estén en la tabla `migracion`. Anota cada una con su fecha. Si una falla, se detiene y muestra el archivo y el error de MySQL. |
| `… --seeds` | Después de migrar, carga en orden los archivos de `database/seeds/` y crea la cuenta de administrador con `ADMIN_CORREO` y `ADMIN_CONTRASENA` del `.env` (contraseña con `password_hash`). |
| `… --desde-cero` | Borra todas las tablas y vuelve a migrar. Se niega a correr si `APP_ENV` no es `local`. |
| `… --pruebas` | Hace lo mismo sobre `DB_NAME_PRUEBAS`, para PHPUnit. |

Orden de los seeds: `01_variedades.sql`, `02_factores.sql`, `03_composicion.sql` (catálogo) y `90_datos_prueba.sql` (criadores, jaulas y canarios inventados, para probar en tu computador).

## 7. Entornos

| Entorno | Base | Quién la usa | Credenciales |
|---|---|---|---|
| Tu computador | `tucriadero` en tu MySQL | Tú | Tu `.env` |
| Pruebas automáticas | `tucriadero_pruebas` en tu MySQL | PHPUnit | Tu `.env` |
| Pruebas en AWS (`develop`) | Base del entorno de pruebas | Despliegue de `develop` | Variables de entorno de AWS |
| Producción (`main`) | Base de producción | Despliegue de la beta | Variables de entorno de AWS |

En AWS, las migraciones se aplican con el mismo script al desplegar. Nadie modifica a mano las bases de AWS.

## 8. Problemas frecuentes

| Problema | Solución |
|---|---|
| `could not find driver` | Falta `extension=pdo_mysql` en `php.ini` (sección 3). |
| `Access denied for user` | La contraseña de `.env` no coincide con la del usuario `tucriadero`. |
| `Unknown database 'tucriadero'` | Falta el paso 2 de la sección 4. |
| `mysql` no se reconoce como comando | Falta la carpeta `bin` de MySQL en el `PATH`; abre una terminal nueva después de agregarla. |
| Una migración falla en tu base pero no en la de otro | Tu base quedó distinta: créala de nuevo con `--desde-cero --seeds`. |
