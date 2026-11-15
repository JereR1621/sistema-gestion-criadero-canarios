<?php
// backend/database/migrar.php

require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno sencillas si no usa vlucas/phpdotenv global
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name && $value) {
            if (!getenv(trim($name))) {
                putenv(trim($name) . '=' . trim($value));
                $_ENV[trim($name)] = trim($value);
            }
        }
    }
}

$appEnv = getenv('APP_ENV') ?: 'local';
$isPruebas = in_array('--pruebas', $argv);
$desdeCero = in_array('--desde-cero', $argv);
$ejecutarSeeds = in_array('--seeds', $argv);

if ($desdeCero && $appEnv !== 'local') {
    fwrite(STDERR, "Error: --desde-cero solo se permite en entorno local.\n");
    exit(1);
}

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbUser = getenv('DB_USER') ?: 'tucriadero';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = $isPruebas ? (getenv('DB_NAME_PRUEBAS') ?: 'tucriadero_pruebas') : (getenv('DB_NAME') ?: 'tucriadero');

try {
    // Conexión inicial sin seleccionar base de datos para --desde-cero
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($desdeCero) {
        echo "Reiniciando base de datos ($dbName) desde cero...\n";
        $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    // Conectar a la base de datos específica
    $pdo->exec("USE `$dbName`");

    // Crear tabla de control 'migracion' si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS migracion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        archivo VARCHAR(255) NOT NULL UNIQUE,
        aplicado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Obtener migraciones ya aplicadas
    $stmt = $pdo->query("SELECT archivo FROM migracion");
    $aplicadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Leer archivos de migrations/
    $migrationsDir = __DIR__ . '/migrations/';
    $archivos = glob($migrationsDir . '*.sql');
    sort($archivos);

    foreach ($archivos as $rutaArchivo) {
        $nombreArchivo = basename($rutaArchivo);
        if (in_array($nombreArchivo, $aplicadas)) {
            continue;
        }

        echo "Aplicando migración: $nombreArchivo...\n";
        $sql = file_get_contents($rutaArchivo);
        
        try {
            // Ejecutar el script SQL directamente (las sentencias CREATE TABLE hacen commit implícito en MySQL)
            $pdo->exec($sql);
            
            // Registrar que la migración fue aplicada con éxito
            $stmtInsert = $pdo->prepare("INSERT INTO migracion (archivo) VALUES (?)");
            $stmtInsert->execute([$nombreArchivo]);
            
        } catch (Exception $e) {
            fwrite(STDERR, "Error en migración $nombreArchivo: " . $e->getMessage() . "\n");
            exit(1);
        }
    }

    echo "Migraciones completadas exitosamente.\n";

    // Ejecutar seeds si se solicita
    if ($ejecutarSeeds) {
        echo "Ejecutando seeds...\n";
        
        // 1. Crear Administrador inicial desde .env
        $adminCorreo = getenv('ADMIN_CORREO') ?: 'admin@tucriadero.local';
        $adminPass = getenv('ADMIN_CONTRASENA') ?: 'Admin123*';
        $hash = password_hash($adminPass, PASSWORD_BCRYPT);

        $stmtAdmin = $pdo->prepare("INSERT INTO usuario (nombre, correo_electronico, contrasena_hash, rol, estado_cuenta) 
                                    VALUES ('Administrador Global', ?, ?, 'administrador', 'activa')
                                    ON DUPLICATE KEY UPDATE contrasena_hash = VALUES(contrasena_hash)");
        $stmtAdmin->execute([$adminCorreo, $hash]);

        // 2. Ejecutar archivos en seeds/
        $seedsDir = __DIR__ . '/seeds/';
        if (is_dir($seedsDir)) {
            $archivosSeeds = glob($seedsDir . '*.sql');
            sort($archivosSeeds);
            foreach ($archivosSeeds as $rutaSeed) {
                $nombreSeed = basename($rutaSeed);
                echo "Ejecutando seed: $nombreSeed...\n";
                $sqlSeed = file_get_contents($rutaSeed);
                $pdo->exec($sqlSeed);
            }
        }
        echo "Seeds ejecutados correctamente.\n";
    }

} catch (PDOException $e) {
    fwrite(STDERR, "Error de conexión PDO: " . $e->getMessage() . "\n");
    exit(1);
}