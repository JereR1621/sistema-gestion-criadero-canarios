<?php

declare(strict_types=1);

namespace TuCriadero\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

final class PdfService
{
    public static function descargar(string $html, string $nombreArchivo): never
    {
        if (!class_exists(Dompdf::class)) {
            throw new RuntimeException('La librería de PDF no está instalada. Ejecuta composer install.');
        }

        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        $pdf->stream($nombreArchivo, ['Attachment' => true]);
        exit;
    }
}
