#!/bin/bash
# Crea la base de pruebas de PHPUnit (DB_NAME_PRUEBAS) y le da permisos al usuario del proyecto.
# MySQL lo ejecuta solo la primera vez que se crea el volumen de datos.
# No crea tablas: las tablas las crea siempre backend/database/migrar.php (docs/base-de-datos.md).
set -e
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME_PRUEBAS}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL PRIVILEGES ON \`${DB_NAME_PRUEBAS}\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
