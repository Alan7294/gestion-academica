<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCursoEvaluacionRequest;
use App\Models\Curso;
use App\Services\CursoEvaluacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CursoEvaluacionController extends Controller
{
    public function __construct(private readonly CursoEvaluacionService $evaluaciones) {}

    public function update(StoreCursoEvaluacionRequest $request, Curso $curso): RedirectResponse
    {
        Gate::authorize('view', $curso);

        $this->evaluaciones->guardar(
            $curso,
            $request->validated('evaluaciones'),
            $request->user(),
        );

        return redirect()
            ->route('docente.cursos.calificaciones', $curso)
            ->with('status', 'Actividades y ponderaciones actualizadas correctamente.');
    }
}
