<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotaController extends Controller
{
    /**
     * RF07/RF12: rendimiento del estudiante en sus cursos.
     */
    public function index(Request $request): View
    {
        $inscripciones = Inscripcion::query()
            ->with(['curso.carrera', 'curso.docente', 'calificaciones'])
            ->where('user_id', $request->user()->id)
            ->activas()
            ->get()
            ->sortBy(fn ($inscripcion) => $inscripcion->curso->nombre);

        return view('estudiante.notas.index', [
            'inscripciones' => $inscripciones,
        ]);
    }
}
