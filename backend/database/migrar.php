<?php

declare(strict_types=1);

/**
 * Aplica las migraciones y, con --seeds, los datos iniciales (docs/base-de-datos.md §6).
 *
 *   php backend/database/migrar.php [--seeds] [--desde-cero] [--pruebas]
 */

use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;

require dirname(__DIR__) . '/autoload.php';

$opciones = array_slice($argv, 1);
$desdeCero = in_array('--desde-cero', $opciones, true);
$conSeeds = in_array('--seeds', $opciones, true);
$pruebas = in_array('--pruebas', $opciones, true);

function salirConError(string $mensaje): never
{
    fwrite(STDERR, "Error: {$mensaje}\n");
    exit(1);
}

/**
 * Ejecuta una sentencia a la vez: con varias en un solo exec(), PDO no informa
 * los errores de la segunda en adelante. Cada sentencia termina en ";" al final de una línea.
 */
function ejecutarArchivo(PDO $pdo, string $ruta): void
{
    foreach (preg_split('/;\s*$/m', (string) file_get_contents($ruta)) as $sentencia) {
        $sinComentarios = trim(preg_replace('/^\s*--.*$/m', '', $sentencia));
        if ($sinComentarios !== '') {
            $pdo->exec($sinComentarios);
        }
    }
}

Entorno::cargar(dirname(__DIR__, 2) . '/.env');

// Solo "local" exacto: si APP_ENV falta o está vacío, no se borra nada.
if ($desdeCero && Entorno::obtener('APP_ENV') !== 'local') {
    salirConError('--desde-cero solo funciona con APP_ENV=local.');
}

if ($pruebas) {
    $_ENV['DB_NAME'] = Entorno::obtener('DB_NAME_PRUEBAS') ?? '';
}

try {
    $pdo = Conexion::crearDesdeEntorno();
} catch (Throwable $error) {
    salirConError('no se pudo conectar a MySQL: ' . $error->getMessage());
}

if ($desdeCero) {
    echo 'Borrando las tablas de ' . Entorno::obtener('DB_NAME') . "...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
        $pdo->exec("DROP TABLE `{$tabla}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$pdo->exec('CREATE TABLE IF NOT EXISTS migracion (
    archivo VARCHAR(255) PRIMARY KEY,
    fecha_aplicada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$aplicadas = $pdo->query('SELECT archivo FROM migracion')->fetchAll(PDO::FETCH_COLUMN);
$anotar = $pdo->prepare('INSERT INTO migracion (archivo) VALUES (?)');

$migraciones = glob(__DIR__ . '/migrations/*.sql');
sort($migraciones, SORT_STRING);
$nuevas = 0;

foreach ($migraciones as $ruta) {
    $archivo = basename($ruta);
    if (in_array($archivo, $aplicadas, true)) {
        continue;
    }

    echo "Aplicando {$archivo}...\n";
    try {
        ejecutarArchivo($pdo, $ruta);
        $anotar->execute([$archivo]);
    } catch (Throwable $error) {
        salirConError("la migración {$archivo} falló: " . $error->getMessage());
    }
    $nuevas++;
}

echo $nuevas === 0 ? "La base ya estaba al día.\n" : "Migraciones aplicadas: {$nuevas}.\n";

if (!$conSeeds) {
    exit(0);
}

$seeds = glob(__DIR__ . '/seeds/*.sql');
sort($seeds, SORT_STRING);

foreach ($seeds as $ruta) {
    $archivo = basename($ruta);
    // Los seeds 9x traen cuentas inventadas con contraseña conocida: solo en el computador de cada uno.
    if (str_starts_with($archivo, '9') && Entorno::obtener('APP_ENV') !== 'local') {
        echo "Omitido {$archivo}: los datos de prueba solo se cargan con APP_ENV=local.\n";
        continue;
    }
    echo "Cargando {$archivo}...\n";
    try {
        ejecutarArchivo($pdo, $ruta);
    } catch (Throwable $error) {
        salirConError("el seed {$archivo} falló: " . $error->getMessage());
    }
}

$correo = Entorno::obtener('ADMIN_CORREO', '');
$contrasena = Entorno::obtener('ADMIN_CONTRASENA', '');
if ($correo === '' || $contrasena === '') {
    salirConError('completa ADMIN_CORREO y ADMIN_CONTRASENA en el .env para crear la cuenta de administrador.');
}

// Volver a correr --seeds deja la contraseña del administrador igual a la del .env.
$pdo->prepare(
    "INSERT INTO usuario (nombre, correo, contrasena_hash, rol) VALUES ('Administrador', ?, ?, 'admin') AS nuevo
     ON DUPLICATE KEY UPDATE contrasena_hash = nuevo.contrasena_hash"
)->execute([$correo, password_hash($contrasena, PASSWORD_DEFAULT)]);

echo "Cuenta de administrador lista: {$correo}\n";
