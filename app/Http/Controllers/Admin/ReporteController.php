<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Reports\Admin\MateriasPorCarreraReporte;
use App\Reports\FiltrosReporte;
use App\Reports\FormatoFactory;
use App\Reports\VistaPreviaReporte;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    /**
     * RF12: catálogo de reportes del administrador.
     */
    public function index(): View
    {
        return view('admin.reportes.index');
    }

    public function preview(Request $request): View
    {
        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new MateriasPorCarreraReporte($filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('admin.reportes.materias', ['formato' => 'pdf'] + $filtros->query()),
                'excel' => route('admin.reportes.materias', ['formato' => 'excel'] + $filtros->query()),
            ],
            route('admin.reportes.index'),
        );
    }

    public function materiasPorCarrera(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new MateriasPorCarreraReporte(FiltrosReporte::desdeRequest($request)),
        );
    }
}
