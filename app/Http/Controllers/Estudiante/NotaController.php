<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Services\ConvalidacionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotaController extends Controller
{
    public function __construct(private readonly ConvalidacionService $convalidaciones) {}

    /**
     * RF07/RF12: rendimiento del estudiante en sus cursos.
     */
    public function index(Request $request): View
    {
        $inscripciones = Inscripcion::query()
            ->with(['curso.carrera', 'curso.docente', 'calificaciones.evaluacion'])
            ->where('user_id', $request->user()->id)
            ->activas()
            ->get()
            ->sortBy(fn ($inscripcion) => $inscripcion->curso->nombre);

        return view('estudiante.notas.index', [
            'inscripciones' => $inscripciones,
            'convalidaciones' => $this->convalidaciones->paraEstudiante($request->user()),
        ]);
    }
}
