<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use Throwable;
use TuCriadero\Core\HttpException;
use TuCriadero\Core\Sesion;
use TuCriadero\Core\Vista;
use TuCriadero\Services\CuentaService;

/**
 * Administración de cuentas de criadores (SRS-RF005, SRS-RF006).
 * Solo accesible para el rol `admin`; un criador recibe 403 y nunca se exponen datos de criaderos.
 */
final class AdminCuentaController
{
    public function estado(string $id): void
    {
        $sesion = $this->exigirAdmin();

        if (!ctype_digit($id) || (int) $id <= 0) {
            throw HttpException::noEncontrado();
        }

        $cuentaId = (int) $id;
        $datos = [
            'estado' => $_POST['estado'] ?? '',
        ];

        $servicio = new CuentaService();
        $resultado = $servicio->cambiarEstadoCuenta($cuentaId, $datos, $sesion['usuario_id']);

        if ($resultado['errores'] !== []) {
            if (($resultado['no_encontrado'] ?? false) === true) {
                throw HttpException::noEncontrado();
            }

            render('admin/cuentas', [
                'cuentas' => $servicio->listarCuentasParaAdmin(),
                'errores' => $resultado['errores'],
                'viejo' => [
                    'estado' => trim((string) $datos['estado']),
                ],
            ]);
            return;
        }

        $mensajes = [
            'activa' => 'La cuenta fue activada correctamente.',
            'desactivada' => 'La cuenta fue desactivada y sus datos se conservan.',
            'cerrada' => 'La cuenta fue cerrada y se registró la fecha de cierre.',
        ];
        $estadoFinal = $resultado['cuenta']['estado'] ?? '';

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => $mensajes[$estadoFinal] ?? 'Estado de la cuenta actualizado correctamente.',
        ]);

        header('Location: ' . $this->urlAdminCuentas(), true, 303);
        exit;
    }

    /** @return array{usuario_id: int, nombre: string, rol: string} */
    private function exigirAdmin(): array
    {
        $sesion = Sesion::paraVista();
        if ($sesion === null) {
            header('Location: ' . ruta('ingreso'), true, 303);
            exit;
        }

        if ($sesion['rol'] !== 'admin') {
            throw HttpException::prohibido();
        }

        return $sesion;
    }

    private function urlAdminCuentas(): string
    {
        try {
            return ruta('admin.cuentas');
        } catch (Throwable) {
            return '/admin/cuentas';
        }
    }
}
