<?php

namespace App\Reports;

use App\Models\User;
use Illuminate\Contracts\View\View;

/**
 * Arma la vista previa HTML compartida por todos los reportes (RF12).
 */
class VistaPreviaReporte
{
    /**
     * @param  array{pdf: string, excel: string}  $descargas
     */
    public static function render(
        Reporte $reporte,
        FiltrosReporte $filtros,
        ?User $usuario,
        array $descargas,
        string $volver,
    ): View {
        return view('reportes.preview', [
            'titulo' => $reporte->titulo(),
            'reporte' => $reporte,
            'filtros' => $filtros,
            'opciones' => OpcionesFiltrosReporte::para($usuario),
            'descargas' => $descargas,
            'volver' => $volver,
        ]);
    }
}
