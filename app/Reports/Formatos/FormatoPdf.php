<?php

namespace App\Reports\Formatos;

use App\Reports\Reporte;
use Dompdf\Dompdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Estrategia PDF: renderiza la vista Blade y la convierte con Dompdf.
 */
class FormatoPdf implements FormatoReporte
{
    public function extension(): string
    {
        return 'pdf';
    }

    public function mime(): string
    {
        return 'application/pdf';
    }

    public function generar(Reporte $reporte): Response
    {
        $dompdf = new Dompdf;
        $dompdf->loadHtml(view('reportes.pdf', [
            'reporte' => $reporte,
            'generadoEn' => now(),
        ])->render());
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        $nombre = Str::slug($reporte->titulo()).'-'.now()->format('Ymd-His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => $this->mime(),
            'Content-Disposition' => "attachment; filename=\"{$nombre}\"",
        ]);
    }
}
