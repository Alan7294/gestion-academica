<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotasCursoRequest;
use App\Models\Curso;
use App\Services\CalificacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CalificacionController extends Controller
{
    public function __construct(private readonly CalificacionService $calificaciones) {}

    /**
     * RF07: planilla de notas del curso.
     */
    public function index(Curso $curso): View
    {
        Gate::authorize('view', $curso);

        $curso->load([
            'carrera',
            'periodo',
            'evaluaciones',
            'alumnosInscritos.estudiante',
            'alumnosInscritos.calificaciones',
        ]);

        return view('docente.calificaciones.index', ['curso' => $curso]);
    }

    public function actualizar(StoreNotasCursoRequest $request, Curso $curso): RedirectResponse
    {
        Gate::authorize('view', $curso);

        $this->calificaciones->guardarNotas(
            $curso,
            $request->validated('notas') ?? [],
            $request->user(),
        );

        return redirect()
            ->route('docente.cursos.calificaciones', $curso)
            ->with('status', 'Calificaciones guardadas correctamente.');
    }
}
