<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use TuCriadero\Core\HttpException;
use TuCriadero\Core\Vista;
use TuCriadero\Services\CanarioService;

final class CanarioController extends BaseController
{
    public function index(): void
    {
        $sesion = $this->exigirCriador();

        $paginaCruda = (string) ($_GET['pagina'] ?? '1');
        $pagina = ctype_digit($paginaCruda) && (int) $paginaCruda > 0 ? (int) $paginaCruda : 1;

        $listado = (new CanarioService())->listarActivos($sesion['criadero_id'], $pagina);

        render('canarios/lista', [
            'canarios' => $listado['canarios'],
            'paginacion' => $listado['paginacion'],
        ]);
    }

    public function nuevo(): void
    {
        $sesion = $this->exigirCriador();

        $datosFormulario = (new CanarioService())->prepararDatosFormulario(
            $sesion['usuario_id'],
            $sesion['criadero_id']
        );

        render('canarios/formulario', array_merge($datosFormulario, [
            'errores' => [],
            'viejo' => [],
        ]));
    }

    public function guardar(): void
    {
        $sesion = $this->exigirCriador();
        $entrada = $this->leerCamposFormulario();

        $servicio = new CanarioService();
        $resultado = $servicio->registrar($sesion['criadero_id'], $entrada);

        if ($resultado['errores'] !== []) {
            $datosFormulario = $servicio->prepararDatosFormulario(
                $sesion['usuario_id'],
                $sesion['criadero_id']
            );

            render('canarios/formulario', array_merge($datosFormulario, [
                'errores' => $resultado['errores'],
                'viejo' => $entrada,
            ]));
            return;
        }

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Canario registrado correctamente.',
        ]);

        $this->redirigir(ruta('canarios.ficha', ['id' => (int) $resultado['canario_id']]));
    }

    public function ficha(string $id): void
    {
        $sesion = $this->exigirCriador();
        $canarioId = $this->exigirIdPositivo($id);

        $canario = $this->exigirRegistroPropio(
            (new CanarioService())->obtenerFicha($canarioId, $sesion['criadero_id'])
        );

        render('canarios/ficha', [
            'canario' => $canario,
        ]);
    }

    public function editar(string $id): void
    {
        $sesion = $this->exigirCriador();
        $canarioId = $this->exigirIdPositivo($id);

        $servicio = new CanarioService();
        $canario = $this->exigirRegistroPropio(
            $servicio->obtenerFicha($canarioId, $sesion['criadero_id'])
        );

        $datosFormulario = $servicio->prepararDatosFormulario(
            $sesion['usuario_id'],
            $sesion['criadero_id'],
            $canarioId
        );

        render('canarios/formulario', array_merge($datosFormulario, [
            'canario' => $canario,
            'errores' => [],
            'viejo' => [],
        ]));
    }

    public function actualizar(string $id): void
    {
        $sesion = $this->exigirCriador();
        $canarioId = $this->exigirIdPositivo($id);

        $servicio = new CanarioService();
        $canario = $this->exigirRegistroPropio(
            $servicio->obtenerFicha($canarioId, $sesion['criadero_id'])
        );

        $entrada = $this->leerCamposFormulario();
        $resultado = $servicio->actualizar($canarioId, $sesion['criadero_id'], $entrada);

        if (($resultado['no_encontrado'] ?? false) === true) {
            throw HttpException::noEncontrado();
        }

        if ($resultado['errores'] !== []) {
            $datosFormulario = $servicio->prepararDatosFormulario(
                $sesion['usuario_id'],
                $sesion['criadero_id'],
                $canarioId
            );

            render('canarios/formulario', array_merge($datosFormulario, [
                'canario' => $canario,
                'errores' => $resultado['errores'],
                'viejo' => $entrada,
            ]));
            return;
        }

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'Canario actualizado correctamente.',
        ]);

        $this->redirigir(ruta('canarios.ficha', ['id' => $canarioId]));
    }

    /**
     * Lee los campos del contrato §6 para alta y edición de canarios.
     *
     * @return array{
     *     anillo_socio: string,
     *     anillo_anio: string,
     *     anillo_correlativo: string,
     *     anillo_club: string,
     *     sexo: string,
     *     variedad_codigo: string,
     *     fecha_anillado: string,
     *     fecha_nacimiento: string,
     *     padre_id: string,
     *     madre_id: string,
     *     procedencia: string,
     *     portadores: array<mixed>
     * }
     */
    private function leerCamposFormulario(): array
    {
        return [
            'anillo_socio' => trim((string) ($_POST['anillo_socio'] ?? '')),
            'anillo_anio' => trim((string) ($_POST['anillo_anio'] ?? '')),
            'anillo_correlativo' => trim((string) ($_POST['anillo_correlativo'] ?? '')),
            'anillo_club' => trim((string) ($_POST['anillo_club'] ?? '')),
            'sexo' => trim((string) ($_POST['sexo'] ?? '')),
            'variedad_codigo' => trim((string) ($_POST['variedad_codigo'] ?? '')),
            'fecha_anillado' => trim((string) ($_POST['fecha_anillado'] ?? '')),
            'fecha_nacimiento' => trim((string) ($_POST['fecha_nacimiento'] ?? '')),
            'padre_id' => trim((string) ($_POST['padre_id'] ?? '')),
            'madre_id' => trim((string) ($_POST['madre_id'] ?? '')),
            'procedencia' => trim((string) ($_POST['procedencia'] ?? '')),
            'portadores' => is_array($_POST['portadores'] ?? null) ? $_POST['portadores'] : [],
        ];
    }
}