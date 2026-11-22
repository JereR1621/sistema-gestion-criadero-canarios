<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use Throwable;
use TuCriadero\Core\Sesion;
use TuCriadero\Services\PdfService;

final class InicioController extends BaseController
{
    public function raiz(): void
    {
        $sesion = Sesion::paraVista();
        if ($sesion === null) {
            $this->redirigir(ruta('ingreso'));
        }

        if ($sesion['rol'] === 'admin') {
            $this->redirigir($this->urlAdminCuentas());
        }

        $this->redirigir(ruta('inicio'));
    }

    public function mostrar(): void
    {
        $sesion = Sesion::paraVista();
        if ($sesion === null) {
            $this->redirigir(ruta('ingreso'));
        }

        if ($sesion['rol'] === 'admin') {
            $this->redirigir($this->urlAdminCuentas());
        }

        render('inicio/index');
    }

    public function descargarPdf(): void
    {
        $this->exigirCriador();
        $html = render('inicio/pdf', [], true);
        PdfService::descargar((string) $html, 'tucriadero-prueba.pdf');
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
