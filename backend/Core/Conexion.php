<?php

declare(strict_types=1);

namespace TuCriadero\Core;

use PDO;
use RuntimeException;

final class Conexion
{
    public static function crearDesdeEntorno(): PDO
    {
        $host = Entorno::obtener('DB_HOST');
        $puerto = Entorno::obtener('DB_PORT', '3306');
        $base = Entorno::obtener('DB_NAME');
        $usuario = Entorno::obtener('DB_USER');
        $contrasena = Entorno::obtener('DB_PASS', '');

        foreach (['DB_HOST' => $host, 'DB_PORT' => $puerto, 'DB_NAME' => $base, 'DB_USER' => $usuario] as $nombre => $valor) {
            if ($valor === null || $valor === '') {
                throw new RuntimeException("Falta la variable de entorno {$nombre}.");
            }
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $puerto, $base);

        return new PDO($dsn, $usuario, $contrasena, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
