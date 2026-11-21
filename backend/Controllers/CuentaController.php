<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

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
}