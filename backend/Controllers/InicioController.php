<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use TuCriadero\Services\PdfService;

final class InicioController
{
    public function mostrar(): void
    {
        render('inicio/index');
    }

    public function descargarPdf(): void
    {
        $html = render('inicio/pdf', [], true);
        PdfService::descargar((string) $html, 'tucriadero-prueba.pdf');
    }
}
