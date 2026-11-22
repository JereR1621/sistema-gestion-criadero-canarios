<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use Throwable;
use TuCriadero\Core\HttpException;
use TuCriadero\Core\Vista;
use TuCriadero\Services\CuentaService;

/**
 * Administración de cuentas de criadores (SRS-RF005, SRS-RF006).
 * Solo accesible para el rol `admin`; un criador recibe 403 y nunca se exponen datos de criaderos.
 */
final class AdminCuentaController extends BaseController
{
    public function estado(string $id): void
    {
        $sesion = $this->exigirAdmin();
        $cuentaId = $this->exigirIdPositivo($id);

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
                    'estado' => trim(texto_de($datos['estado'])),
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

        $this->redirigir($this->urlAdminCuentas());
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
