<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use TuCriadero\Core\Vista;
use TuCriadero\Services\JaulaService;

/**
 * Gestión de jaulas numeradas del criadero autenticado (SRS-RF014).
 * El `criadero_id` se obtiene exclusivamente desde la sesión vía `BaseController::criaderoIdDeSesion()`.
 */
final class JaulaController extends BaseController
{
    public function index(): void
    {
        $criaderoId = $this->criaderoIdDeSesion();
        $jaulas = (new JaulaService())->listarResumenes($criaderoId);

        render('jaulas/lista', [
            'jaulas' => $jaulas,
            'errores' => [],
            'viejo' => [],
        ]);
    }

    public function guardar(): void
    {
        $criaderoId = $this->criaderoIdDeSesion();
        $datos = [
            'numero' => $_POST['numero'] ?? '',
        ];

        $servicio = new JaulaService();
        $resultado = $servicio->registrar($criaderoId, $datos);

        if ($resultado['errores'] !== []) {
            render('jaulas/lista', [
                'jaulas' => $servicio->listarResumenes($criaderoId),
                'errores' => $resultado['errores'],
                'viejo' => [
                    'numero' => trim(texto_de($datos['numero'])),
                ],
            ]);
            return;
        }

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Jaula registrada correctamente.',
        ]);

        $this->redirigir(ruta('jaulas'));
    }
}
