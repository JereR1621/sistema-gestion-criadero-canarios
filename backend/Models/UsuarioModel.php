<?php

declare(strict_types=1);

namespace TuCriadero\Models;

use PDO;
use TuCriadero\Core\Conexion;

final class UsuarioModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::obtener();
    }

    public function existeCorreo(string $correo): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT id FROM usuario WHERE correo = ? LIMIT 1'
        );

        $consulta->execute([$correo]);

        return $consulta->fetch() !== false;
    }

    /**
     * Busca una cuenta de usuario por su correo electrónico.
     *
     * @return array<string, mixed>|null
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        $stmt = Conexion::obtener()->prepare(
            'SELECT id, nombre, correo, contrasena_hash, rol, estado
            FROM usuario
            WHERE correo = :correo
            LIMIT 1'
        );
        $stmt->execute([':correo' => $correo]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    /** @param array{nombre:string,correo:string,contrasena_hash:string} $datos */
    public function crear(array $datos): int
    {
        $consulta = $this->pdo->prepare(
            "INSERT INTO usuario
            (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento)
            VALUES (?, ?, ?, 'criador', 'activa', CURRENT_TIMESTAMP)"
        );

        $consulta->execute([
            $datos['nombre'],
            $datos['correo'],
            $datos['contrasena_hash'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}