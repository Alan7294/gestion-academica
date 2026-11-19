<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalificacionRequest;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Inscripcion;
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
            'alumnosInscritos.estudiante',
            'alumnosInscritos.calificaciones',
        ]);

        return view('docente.calificaciones.index', ['curso' => $curso]);
    }

    public function store(StoreCalificacionRequest $request, Inscripcion $inscripcion): RedirectResponse
    {
        $this->calificaciones->registrar($inscripcion, $request->validated(), $request->user());

        return redirect()
            ->route('docente.cursos.calificaciones', $inscripcion->curso_id)
            ->with('status', 'Calificación registrada correctamente.');
    }

    public function update(StoreCalificacionRequest $request, Calificacion $calificacion): RedirectResponse
    {
        $this->calificaciones->actualizar($calificacion, $request->validated(), $request->user());

        return redirect()
            ->route('docente.cursos.calificaciones', $calificacion->inscripcion->curso_id)
            ->with('status', 'Calificación actualizada correctamente.');
    }

    public function destroy(Calificacion $calificacion): RedirectResponse
    {
        $cursoId = $calificacion->inscripcion->curso_id;

        $this->calificaciones->eliminar($calificacion, request()->user());

        return redirect()
            ->route('docente.cursos.calificaciones', $cursoId)
            ->with('status', 'Calificación eliminada.');
    }
}
