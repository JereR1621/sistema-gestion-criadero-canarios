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

    /**
     * Busca una cuenta de usuario por su identificador (incluye datos de perfil y estado).
     *
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nombre, correo, rol, estado, fecha_registro, fecha_cierre, numero_socio, club
             FROM usuario
             WHERE id = ?
             LIMIT 1'
        );
        $consulta->execute([$id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Actualiza el número de socio y el club de la cuenta indicada (RF004).
     */
    public function actualizarPerfil(int $id, ?string $numeroSocio, ?string $club): void
    {
        $consulta = $this->pdo->prepare(
            'UPDATE usuario
             SET numero_socio = ?, club = ?
             WHERE id = ?'
        );
        $consulta->execute([$numeroSocio, $club, $id]);
    }

    /**
     * Actualiza el estado de una cuenta (RF005, POL-001, DAT-002).
     * Respeta `chk_usuario_cierre`: al pasar a `cerrada` registra `fecha_cierre`;
     * en `activa` o `desactivada` deja `fecha_cierre` en NULL.
     */
    public function actualizarEstado(int $id, string $estado): void
    {
        if ($estado === 'cerrada') {
            $consulta = $this->pdo->prepare(
                "UPDATE usuario
                 SET estado = 'cerrada',
                     fecha_cierre = COALESCE(fecha_cierre, CURRENT_TIMESTAMP)
                 WHERE id = ?"
            );
            $consulta->execute([$id]);
            return;
        }

        $consulta = $this->pdo->prepare(
            'UPDATE usuario
             SET estado = ?,
                 fecha_cierre = NULL
             WHERE id = ?'
        );
        $consulta->execute([$estado, $id]);
    }

    /**
     * Lista los datos de registro y estado de las cuentas de criadores para administración (RF005 / RF006).
     * Nunca incluye datos de criaderos (canarios, jaulas, cruces ni criadero_id).
     *
     * @return list<array{id: int, nombre: string, correo: string, estado: string, fecha_registro: string}>
     */
    public function listarCuentasParaAdmin(): array
    {
        $filas = $this->pdo->query(
            "SELECT id, nombre, correo, estado, fecha_registro
             FROM usuario
             WHERE rol = 'criador'
             ORDER BY fecha_registro DESC, id DESC"
        )->fetchAll();

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'correo' => (string) $fila['correo'],
            'estado' => (string) $fila['estado'],
            'fecha_registro' => (string) $fila['fecha_registro'],
        ], $filas);
    }
}
