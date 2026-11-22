<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use TuCriadero\Core\HttpException;
use TuCriadero\Core\Vista;
use TuCriadero\Services\CatalogoService;

/**
 * Mantenimiento del catálogo de variedades y factores para el administrador (SRS-RF032).
 *
 * Solo rol `admin`: un criador recibe 403. El catálogo es global, así que estas operaciones
 * usan datos globales, no se filtran por `criadero_id` y nunca entregan canarios, jaulas ni cruces.
 * Recibe las peticiones, llama al servicio y elige la vista o la redirección (sin reglas de
 * negocio ni SQL). Los errores del servicio se muestran con `$errores` y `$viejo` (contrato §2).
 */
final class AdminCatalogoController extends BaseController
{
    public function variedades(): void
    {
        $this->exigirAdmin();

        $q = trim((string) ($_GET['q'] ?? ''));

        render('admin/variedades', [
            'variedades' => (new CatalogoService())->listarVariedadesParaAdmin($q),
            'q' => $q,
            'errores' => [],
            'viejo' => [],
        ]);
    }

    public function variedadesEditar(string $codigo): void
    {
        $this->exigirAdmin();

        $servicio = new CatalogoService();
        $variedad = $this->exigirRegistroPropio($servicio->obtenerVariedad($codigo));

        render('admin/variedad', [
            'variedad' => $variedad,
            'factores' => $servicio->listarFactores(),
            'errores' => [],
            'viejo' => $this->viejoDeVariedad($variedad),
        ]);
    }

    public function variedadesGuardar(string $codigo): void
    {
        $this->exigirAdmin();

        $servicio = new CatalogoService();
        $variedadActual = $this->exigirRegistroPropio($servicio->obtenerVariedad($codigo));

        // `tipo` y `rasgo` vienen del formulario cuando la vista los pide (nota de la issue #86);
        // si el formulario no los envía, se conservan los de la variedad.
        $datos = [
            'nombre' => is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : '',
            'sexo' => is_string($_POST['sexo'] ?? null) ? $_POST['sexo'] : '',
            'tipo' => is_string($_POST['tipo'] ?? null) ? $_POST['tipo'] : $variedadActual['tipo'],
            'rasgo' => is_string($_POST['rasgo'] ?? null) ? $_POST['rasgo'] : ($variedadActual['rasgo'] ?? ''),
            'factores' => $_POST['factores'] ?? [],
        ];

        $resultado = $servicio->actualizarVariedad($codigo, $datos);

        if ($resultado['errores'] !== []) {
            render('admin/variedad', [
                'variedad' => $variedadActual,
                'factores' => $servicio->listarFactores(),
                'errores' => $resultado['errores'],
                'viejo' => [
                    'nombre' => trim($datos['nombre']),
                    'sexo' => $datos['sexo'],
                    'tipo' => $datos['tipo'],
                    'rasgo' => $datos['rasgo'],
                    'factores' => is_array($datos['factores']) ? $datos['factores'] : [],
                ],
            ]);
            return;
        }

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'La variedad se ha actualizado correctamente y aplica para todos los criadores.',
        ]);

        $this->redirigir(ruta('admin.variedades'));
    }

    public function factores(): void
    {
        $this->exigirAdmin();

        render('admin/factores', [
            'factores' => (new CatalogoService())->listarFactores(),
            'errores' => [],
            'viejo' => [],
        ]);
    }

    public function factoresGuardar(): void
    {
        $this->exigirAdmin();

        $datos = [
            'nombre' => is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : '',
            'tipo_herencia' => is_string($_POST['tipo_herencia'] ?? null) ? $_POST['tipo_herencia'] : null,
        ];

        $servicio = new CatalogoService();
        $resultado = $servicio->crearFactor($datos['nombre'], $datos['tipo_herencia']);

        if ($resultado['errores'] !== []) {
            render('admin/factores', [
                'factores' => $servicio->listarFactores(),
                'errores' => $resultado['errores'],
                'viejo' => [
                    'nombre' => trim($datos['nombre']),
                    'tipo_herencia' => $datos['tipo_herencia'] ?? '',
                ],
            ]);
            return;
        }

        Vista::guardarFlash([
            'tipo' => 'exito',
            'mensaje' => 'El factor se ha creado correctamente y aplica para todos los criadores.',
        ]);

        $this->redirigir(ruta('admin.factores'));
    }

    /**
     * Valores con los que se llena el formulario de una variedad (contrato §2, `$viejo`).
     *
     * @param array{codigo: string, nombre: string, sexo: ?string, tipo: string, rasgo: ?string, factores: list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>} $variedad
     * @return array<string, mixed>
     */
    private function viejoDeVariedad(array $variedad): array
    {
        return [
            'nombre' => $variedad['nombre'],
            'sexo' => $variedad['sexo'] ?? '',
            'tipo' => $variedad['tipo'],
            'rasgo' => $variedad['rasgo'] ?? '',
            'factores' => array_column($variedad['factores'], 'id'),
        ];
    }
}
