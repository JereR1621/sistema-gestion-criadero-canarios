<?php

declare(strict_types=1);

namespace TuCriadero\Services;

use Throwable;
use TuCriadero\Core\Conexion;
use TuCriadero\Models\CriaderoModel;
use TuCriadero\Models\UsuarioModel;

final class CuentaService
{
    private UsuarioModel $usuarios;
    private CriaderoModel $criaderos;

    public function __construct(?UsuarioModel $usuarios = null, ?CriaderoModel $criaderos = null)
    {
        $this->usuarios = $usuarios ?? new UsuarioModel();
        $this->criaderos = $criaderos ?? new CriaderoModel();
    }

    /**
     * Valida y crea una cuenta de criador junto con su criadero vacío.
     *
     * @param array<string, mixed> $datos
     * @return array{errores: array<string, string>, usuario_id?: int, criadero_id?: int}
     */
    public function registrar(array $datos): array
    {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $correo = mb_strtolower(trim((string) ($datos['correo'] ?? '')));
        $contrasena = (string) ($datos['contrasena'] ?? '');
        $consentimiento = $datos['consentimiento'] ?? null;

        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'El nombre no puede superar los 100 caracteres.';
        }

        if ($correo === '') {
            $errores['correo'] = 'El correo electrónico es obligatorio.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
            $errores['correo'] = 'Introduce un correo electrónico válido.';
        } elseif ($this->usuarios->existeCorreo($correo)) {
            $errores['correo'] = 'Este correo electrónico ya está registrado.';
        }

        if ($contrasena === '') {
            $errores['contrasena'] = 'La contraseña es obligatoria.';
        } elseif (mb_strlen($contrasena) < 8) {
            $errores['contrasena'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!in_array($consentimiento, ['1', 1, true, 'on'], true)) {
            $errores['consentimiento'] = 'Para crear la cuenta debes aceptar el uso de tus datos';
        }

        if ($errores !== []) {
            return ['errores' => $errores];
        }

        $pdo = Conexion::obtener();
        $transaccionPropia = !$pdo->inTransaction();

        try {
            if ($transaccionPropia) {
                $pdo->beginTransaction();
            }

            if ($this->usuarios->existeCorreo($correo)) {
                if ($transaccionPropia && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                return [
                    'errores' => [
                        'correo' => 'Este correo electrónico ya está registrado.',
                    ],
                ];
            }

            $usuarioId = $this->usuarios->crear([
                'nombre' => $nombre,
                'correo' => $correo,
                'contrasena_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
            ]);

            $criaderoId = $this->criaderos->crear($usuarioId);

            \auditar('crear', 'usuario', $usuarioId);
            \auditar('crear', 'criadero', $criaderoId);

            if ($transaccionPropia) {
                $pdo->commit();
            }

            return [
                'errores' => [],
                'usuario_id' => $usuarioId,
                'criadero_id' => $criaderoId,
            ];
        } catch (Throwable $error) {
            if ($transaccionPropia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }

        try {
            $pdo->beginTransaction();

            if ($this->usuarios->existeCorreo($correo)) {
                $pdo->rollBack();
                return [
                    'errores' => [
                        'correo' => 'Este correo electrónico ya está registrado.',
                    ],
                ];
            }

            $usuarioId = $this->usuarios->crear([
                'nombre' => $nombre,
                'correo' => $correo,
                'contrasena_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
            ]);

            $criaderoId = $this->criaderos->crear($usuarioId);

            \auditar('crear', 'usuario', $usuarioId);
            \auditar('crear', 'criadero', $criaderoId);

            $pdo->commit();

            return [
                'errores' => [],
                'usuario_id' => $usuarioId,
                'criadero_id' => $criaderoId,
            ];
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }
    /**
     * Valida credenciales de inicio de sesión y devuelve los datos de sesión.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     errores: array<string, string>,
     *     usuario?: array{id: int, nombre: string, rol: string, criadero_id: ?int}
     * }
     */
    public function autenticar(array $datos): array
    {
        $correo = mb_strtolower(trim((string) ($datos['correo'] ?? '')));
        $contrasena = (string) ($datos['contrasena'] ?? '');

        if ($correo === '' || $contrasena === '') {
            return [
                'errores' => [
                    '_general' => 'Correo o contraseña incorrectos',
                ],
            ];
        }

        $usuario = $this->usuarios->buscarPorCorreo($correo);

        if ($usuario === null || !password_verify($contrasena, (string) $usuario['contrasena_hash'])) {
            return [
                'errores' => [
                    '_general' => 'Correo o contraseña incorrectos',
                ],
            ];
        }

        if (($usuario['estado'] ?? '') !== 'activa') {
            return [
                'errores' => [
                    '_general' => 'Tu cuenta está desactivada o cerrada. Contacta al administrador.',
                ],
            ];
        }

        $usuarioId = (int) $usuario['id'];
        $rol = (string) $usuario['rol'];
        $criaderoId = $rol === 'criador'
            ? $this->criaderos->obtenerIdPorUsuario($usuarioId)
            : null;

        return [
            'errores' => [],
            'usuario' => [
                'id' => $usuarioId,
                'nombre' => (string) $usuario['nombre'],
                'rol' => $rol,
                'criadero_id' => $criaderoId,
            ],
        ];
    }
}