<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotaController extends Controller
{
    public function __construct(private readonly InscripcionController $inscripciones) {}

    /**
     * RF07: alias histórico de «Mis cursos», donde viven las calificaciones.
     */
    public function index(Request $request): View
    {
        return $this->inscripciones->index($request);
    }
}
