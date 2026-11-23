<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Reports\Estudiante\HistorialAcademicoReporte;
use App\Reports\Estudiante\MateriasAlumnoReporte;
use App\Reports\Estudiante\NotasTodasMateriasReporte;
use App\Reports\FiltrosReporte;
use App\Reports\FormatoFactory;
use App\Reports\VistaPreviaReporte;
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

    public function previewHistorial(Request $request): View
    {
        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new HistorialAcademicoReporte($request->user(), $filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('estudiante.reportes.historial', ['formato' => 'pdf'] + $filtros->query()),
                'excel' => route('estudiante.reportes.historial', ['formato' => 'excel'] + $filtros->query()),
            ],
            route('estudiante.reportes.index'),
        );
    }

    public function previewNotas(Request $request): View
    {
        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new NotasTodasMateriasReporte($request->user(), $filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('estudiante.reportes.notas', ['formato' => 'pdf'] + $filtros->query()),
                'excel' => route('estudiante.reportes.notas', ['formato' => 'excel'] + $filtros->query()),
            ],
            route('estudiante.reportes.index'),
        );
    }

    public function previewMaterias(Request $request): View
    {
        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new MateriasAlumnoReporte($request->user(), $filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('estudiante.reportes.materias', ['formato' => 'pdf'] + $filtros->query()),
                'excel' => route('estudiante.reportes.materias', ['formato' => 'excel'] + $filtros->query()),
            ],
            route('estudiante.reportes.index'),
        );
    }

    public function historial(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new HistorialAcademicoReporte($request->user(), FiltrosReporte::desdeRequest($request)),
        );
    }

    public function notas(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new NotasTodasMateriasReporte($request->user(), FiltrosReporte::desdeRequest($request)),
        );
    }

    public function materias(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new MateriasAlumnoReporte($request->user(), FiltrosReporte::desdeRequest($request)),
        );
    }
}
