<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Reports\Estudiante\HistorialAcademicoReporte;
use App\Reports\Estudiante\MateriasAlumnoReporte;
use App\Reports\Estudiante\NotasTodasMateriasReporte;
use App\Reports\FormatoFactory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    /**
     * RF12: catálogo de reportes del estudiante.
     */
    public function index(): View
    {
        return view('estudiante.reportes.index');
    }

    public function historial(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new HistorialAcademicoReporte($request->user()),
        );
    }

    public function notas(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new NotasTodasMateriasReporte($request->user()),
        );
    }

    public function materias(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new MateriasAlumnoReporte($request->user()),
        );
    }
}
