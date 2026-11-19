<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Reports\Admin\MateriasPorCarreraReporte;
use App\Reports\FormatoFactory;
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

    public function materiasPorCarrera(string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(new MateriasPorCarreraReporte);
    }
}
