<?php

declare(strict_types=1);

namespace TuCriadero\Services;

use PDOException;
use Throwable;
use TuCriadero\Core\Conexion;
use TuCriadero\Models\CriaderoModel;
use TuCriadero\Models\UsuarioModel;

final class CuentaService
{
    /** Límite de bcrypt (PASSWORD_DEFAULT): se cuentan bytes, y una letra con tilde ocupa 2. */
    public const CONTRASENA_MAX_BYTES = 72;
    public const ESTADOS_CUENTA = ['activa', 'desactivada', 'cerrada'];

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
        } elseif (strlen($contrasena) > self::CONTRASENA_MAX_BYTES) {
            // bcrypt solo usa los primeros 72 bytes: más allá, el resto se ignoraría sin avisar.
            $errores['contrasena'] = 'La contraseña es demasiado larga: máximo 72 caracteres (la ñ y las letras con tilde cuentan doble).';
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

            // Dos registros simultáneos con el mismo correo: la clave única rechaza el segundo.
            if ($error instanceof PDOException && $error->getCode() === '23000') {
                return ['errores' => ['correo' => 'Este correo electrónico ya está registrado.']];
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

        $estado = (string) ($usuario['estado'] ?? '');
        if ($estado === 'desactivada') {
            return [
                'errores' => [
                    '_general' => 'Tu cuenta está desactivada. Contacta al administrador.',
                ],
            ];
        }

        if ($estado === 'cerrada') {
            return [
                'errores' => [
                    '_general' => 'Tu cuenta está cerrada. Contacta al administrador.',
                ],
            ];
        }

        if ($estado !== 'activa') {
            return [
                'errores' => [
                    '_general' => 'Tu cuenta no está activa. Contacta al administrador.',
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

    /**
     * Devuelve los datos del perfil de la cuenta para la vista `cuentas/perfil` (contrato §6, RF004).
     *
     * @return array{nombre: string, correo: string, numero_socio: ?string, club: ?string}|null
     */
    public function obtenerPerfil(int $usuarioId): ?array
    {
        $usuario = $this->usuarios->buscarPorId($usuarioId);
        if ($usuario === null) {
            return null;
        }

        return [
            'nombre' => (string) $usuario['nombre'],
            'correo' => (string) $usuario['correo'],
            'numero_socio' => $usuario['numero_socio'] !== null ? (string) $usuario['numero_socio'] : null,
            'club' => $usuario['club'] !== null ? (string) $usuario['club'] : null,
        ];
    }

    /**
     * Valores por defecto para los anillos de pichones propios (SRS-RF004, contrato §6: canarios.nuevo y pichones.nuevo).
     *
     * @return array{anillo_socio: ?string, anillo_club: ?string}
     */
    public function obtenerValoresPorDefectoAnillo(int $usuarioId): array
    {
        $perfil = $this->obtenerPerfil($usuarioId);

        return [
            'anillo_socio' => $perfil['numero_socio'] ?? null,
            'anillo_club' => $perfil['club'] ?? null,
        ];
    }

    /**
     * Valida y guarda el número de socio y el club del criador autenticado (SRS-RF004).
     * Solo opera sobre `$usuarioId` recibido de la sesión; ignora cualquier identificador en `$datos`.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     errores: array<string, string>,
     *     perfil?: array{nombre: string, correo: string, numero_socio: ?string, club: ?string}
     * }
     */
    public function actualizarPerfil(int $usuarioId, array $datos): array
    {
        $usuario = $this->usuarios->buscarPorId($usuarioId);
        if ($usuario === null || ($usuario['estado'] ?? '') !== 'activa') {
            return [
                'errores' => [
                    '_general' => 'La cuenta no está disponible.',
                ],
            ];
        }

        if (($usuario['rol'] ?? '') !== 'criador') {
            return [
                'errores' => [
                    '_general' => 'Solo las cuentas de criador pueden registrar número de socio y club.',
                ],
            ];
        }

        $numeroSocio = trim((string) ($datos['numero_socio'] ?? ''));
        $club = trim((string) ($datos['club'] ?? ''));

        $errores = [];

        if (mb_strlen($numeroSocio) > 50) {
            $errores['numero_socio'] = 'El número de socio no puede superar los 50 caracteres.';
        }

        // 50, como canario.anillo_club: el club se propone al anillar (RF007, RF021) y tiene que caber en el anillo.
        if (mb_strlen($club) > 50) {
            $errores['club'] = 'El club no puede superar los 50 caracteres.';
        }

        if ($errores !== []) {
            return ['errores' => $errores];
        }

        $socioGuardar = $numeroSocio !== '' ? $numeroSocio : null;
        $clubGuardar = $club !== '' ? $club : null;

        $pdo = Conexion::obtener();
        $transaccionPropia = !$pdo->inTransaction();

        try {
            if ($transaccionPropia) {
                $pdo->beginTransaction();
            }

            $this->usuarios->actualizarPerfil($usuarioId, $socioGuardar, $clubGuardar);
            \auditar('actualizar', 'usuario', $usuarioId);

            if ($transaccionPropia) {
                $pdo->commit();
            }

            return [
                'errores' => [],
                'perfil' => [
                    'nombre' => (string) $usuario['nombre'],
                    'correo' => (string) $usuario['correo'],
                    'numero_socio' => $socioGuardar,
                    'club' => $clubGuardar,
                ],
            ];
        } catch (Throwable $error) {
            if ($transaccionPropia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }

    /**
     * Lista las cuentas de criadores para las vistas de administración (contrato §6: admin.cuentas).
     * Solo devuelve datos de registro y estado; nunca datos de criaderos.
     *
     * @return list<array{id: int, nombre: string, correo: string, estado: string, fecha_registro: string}>
     */
    public function listarCuentasParaAdmin(): array
    {
        return $this->usuarios->listarCuentasParaAdmin();
    }

    /**
     * Cambia el estado de la cuenta de un criador (SRS-RF005, POL-001, DAT-002).
     * Desactivar o cerrar impide el inicio de sesión y conserva todos los datos del criadero.
     * Al cerrar (`estado = cerrada`), guarda `fecha_cierre`.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     errores: array<string, string>,
     *     no_encontrado?: bool,
     *     cuenta?: array{
     *         id: int,
     *         nombre: string,
     *         correo: string,
     *         estado: string,
     *         fecha_registro: string,
     *         fecha_cierre: ?string
     *     }
     * }
     */
    public function cambiarEstadoCuenta(int $cuentaId, array $datos, ?int $adminId = null): array
    {
        $nuevoEstado = trim((string) ($datos['estado'] ?? ''));

        if (!in_array($nuevoEstado, self::ESTADOS_CUENTA, true)) {
            return [
                'errores' => [
                    'estado' => 'El estado indicado no es válido. Debe ser activa, desactivada o cerrada.',
                ],
            ];
        }

        $usuario = $this->usuarios->buscarPorId($cuentaId);
        if ($usuario === null) {
            return [
                'errores' => [
                    '_general' => 'La cuenta indicada no existe.',
                ],
                'no_encontrado' => true,
            ];
        }

        if (($usuario['rol'] ?? '') !== 'criador' || ($adminId !== null && $adminId === $cuentaId)) {
            return [
                'errores' => [
                    '_general' => 'Solo se puede cambiar el estado de cuentas de criadores.',
                ],
            ];
        }

        $accionAuditoria = match ($nuevoEstado) {
            'desactivada' => 'desactivar',
            'cerrada' => 'cerrar',
            default => 'activar',
        };

        $pdo = Conexion::obtener();
        $transaccionPropia = !$pdo->inTransaction();

        try {
            if ($transaccionPropia) {
                $pdo->beginTransaction();
            }

            $this->usuarios->actualizarEstado($cuentaId, $nuevoEstado);
            \auditar($accionAuditoria, 'usuario', $cuentaId);

            $actualizada = $this->usuarios->buscarPorId($cuentaId);

            if ($transaccionPropia) {
                $pdo->commit();
            }

            return [
                'errores' => [],
                'cuenta' => [
                    'id' => (int) ($actualizada['id'] ?? $cuentaId),
                    'nombre' => (string) ($actualizada['nombre'] ?? $usuario['nombre']),
                    'correo' => (string) ($actualizada['correo'] ?? $usuario['correo']),
                    'estado' => (string) ($actualizada['estado'] ?? $nuevoEstado),
                    'fecha_registro' => (string) ($actualizada['fecha_registro'] ?? $usuario['fecha_registro']),
                    'fecha_cierre' => ($actualizada['fecha_cierre'] ?? null) !== null
                        ? (string) $actualizada['fecha_cierre']
                        : null,
                ],
            ];
        } catch (Throwable $error) {
            if ($transaccionPropia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }
}
