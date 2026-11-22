<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use TuCriadero\Core\Sesion;
use TuCriadero\Core\Vista;
use TuCriadero\Services\CuentaService;

final class CuentaController extends BaseController
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
                    'nombre' => trim(texto_de($datos['nombre'])),
                    'correo' => trim(texto_de($datos['correo'])),
                ],
            ]);
            return;
        }

        Sesion::iniciarAutenticada(
            (int) $resultado['usuario_id'],
            'criador',
            (int) $resultado['criadero_id'],
            trim(texto_de($datos['nombre']))
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
                    'correo' => trim(texto_de($datos['correo'])),
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
        $sesion = $this->exigirCriador();

        $perfil = $this->exigirRegistroPropio(
            (new CuentaService())->obtenerPerfil($sesion['usuario_id'])
        );

        render('cuentas/perfil', [
            'perfil' => $perfil,
            'errores' => [],
            'viejo' => [],
        ]);
    }

    public function perfilGuardar(): void
    {
        $sesion = $this->exigirCriador();

        $datos = [
            'numero_socio' => $_POST['numero_socio'] ?? '',
            'club' => $_POST['club'] ?? '',
        ];

        $servicio = new CuentaService();
        $resultado = $servicio->actualizarPerfil($sesion['usuario_id'], $datos);

        if ($resultado['errores'] !== []) {
            $perfil = $this->exigirRegistroPropio(
                $servicio->obtenerPerfil($sesion['usuario_id'])
            );

            render('cuentas/perfil', [
                'perfil' => $perfil,
                'errores' => $resultado['errores'],
                'viejo' => [
                    'numero_socio' => trim(texto_de($datos['numero_socio'])),
                    'club' => trim(texto_de($datos['club'])),
                ],
            ]);
            return;
        }

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Perfil actualizado correctamente.',
        ]);

        $this->redirigir(ruta('perfil'));
    }
}
