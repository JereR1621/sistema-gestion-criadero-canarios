<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use TuCriadero\Core\HttpException;
use TuCriadero\Core\Sesion;
use TuCriadero\Core\Vista;
use TuCriadero\Services\CuentaService;

final class CuentaController
{
    public function registro(): void
    {
        render('cuentas/registro', [
            'errores' => [],
            'viejo' => [],
        ]);
    }

    public function registroGuardar(): void
    {
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'correo' => $_POST['correo'] ?? '',
            'contrasena' => $_POST['contrasena'] ?? '',
            'consentimiento' => $_POST['consentimiento'] ?? null,
        ];

        $resultado = (new CuentaService())->registrar($datos);

        if ($resultado['errores'] !== []) {
            render('cuentas/registro', [
                'errores' => $resultado['errores'],
                'viejo' => [
                    'nombre' => trim((string) $datos['nombre']),
                    'correo' => trim((string) $datos['correo']),
                ],
            ]);
            return;
        }

        Sesion::iniciarAutenticada(
            (int) $resultado['usuario_id'],
            'criador',
            (int) $resultado['criadero_id'],
            trim((string) $datos['nombre'])
        );

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Tu cuenta se ha creado correctamente.',
        ]);

        header('Location: ' . ruta('inicio'), true, 303);
        exit;
    }
    public function ingreso(): void
    {
        render('cuentas/ingresar', [
            'errores' => [],
            'viejo' => [],
        ]);
    }

    public function ingresoEnviar(): void
    {
        $datos = [
            'correo' => $_POST['correo'] ?? '',
            'contrasena' => $_POST['contrasena'] ?? '',
        ];

        $resultado = (new CuentaService())->autenticar($datos);

        if ($resultado['errores'] !== []) {
            render('cuentas/ingresar', [
                'errores' => $resultado['errores'],
                'viejo' => [
                    'correo' => trim((string) $datos['correo']),
                ],
            ]);
            return;
        }

        $usuario = $resultado['usuario'];

        Sesion::iniciarAutenticada(
            $usuario['id'],
            $usuario['rol'],
            $usuario['criadero_id'],
            $usuario['nombre']
        );

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Has iniciado sesión correctamente.',
        ]);

        header('Location: ' . ruta('inicio'), true, 303);
        exit;
    }

    public function salida(): void
    {
        if (Sesion::paraVista() === null) {
            header('Location: ' . ruta('ingreso'), true, 303);
            exit;
        }

        Sesion::cerrarAutenticada();

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Has cerrado sesión.',
        ]);

        header('Location: ' . ruta('ingreso'), true, 303);
        exit;
    }

    public function perfil(): void
    {
        $sesion = $this->exigirCriadorAutenticado();

        $perfil = (new CuentaService())->obtenerPerfil($sesion['usuario_id']);
        if ($perfil === null) {
            throw HttpException::noEncontrado();
        }

        render('cuentas/perfil', [
            'perfil' => $perfil,
            'errores' => [],
            'viejo' => [],
        ]);
    }

    public function perfilGuardar(): void
    {
        $sesion = $this->exigirCriadorAutenticado();

        $datos = [
            'numero_socio' => $_POST['numero_socio'] ?? '',
            'club' => $_POST['club'] ?? '',
        ];

        $servicio = new CuentaService();
        $resultado = $servicio->actualizarPerfil($sesion['usuario_id'], $datos);

        if ($resultado['errores'] !== []) {
            $perfil = $servicio->obtenerPerfil($sesion['usuario_id']);
            if ($perfil === null) {
                throw HttpException::noEncontrado();
            }

            render('cuentas/perfil', [
                'perfil' => $perfil,
                'errores' => $resultado['errores'],
                'viejo' => [
                    'numero_socio' => trim((string) $datos['numero_socio']),
                    'club' => trim((string) $datos['club']),
                ],
            ]);
            return;
        }

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Perfil actualizado correctamente.',
        ]);

        header('Location: ' . ruta('perfil'), true, 303);
        exit;
    }

    /** @return array{usuario_id: int, nombre: string, rol: string} */
    private function exigirCriadorAutenticado(): array
    {
        $sesion = Sesion::paraVista();
        if ($sesion === null) {
            header('Location: ' . ruta('ingreso'), true, 303);
            exit;
        }

        if ($sesion['rol'] !== 'criador') {
            throw HttpException::prohibido();
        }

        return $sesion;
    }
}
