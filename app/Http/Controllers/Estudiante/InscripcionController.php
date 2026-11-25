<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Services\InscripcionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InscripcionController extends Controller
{
    public function __construct(private readonly InscripcionService $inscripciones) {}

    /**
     * RF06: cursos en los que el estudiante está inscrito.
     */
    public function index(Request $request): View
    {
        $inscripciones = Inscripcion::query()
            ->with(['curso.carrera', 'curso.docente', 'curso.materia', 'curso.horarios', 'curso.periodo'])
            ->where('user_id', $request->user()->id)
            ->activas()
            ->join('cursos', 'cursos.id', '=', 'inscripciones.curso_id')
            ->orderBy('cursos.nombre')
            ->select('inscripciones.*')
            ->paginate(10);

        return view('estudiante.inscripciones.index', [
            'inscripciones' => $inscripciones,
        ]);
    }

    /**
     * RF05: inscribirse a un curso.
     */
    public function store(Request $request, Curso $curso): RedirectResponse
    {
        Gate::authorize('inscribirse', $curso);

        $this->inscripciones->inscribir($request->user(), $curso);

        return redirect()
            ->route('estudiante.inscripciones.index')
            ->with('status', "Te inscribiste al curso {$curso->nombre}.");
    }

    /**
     * RF05: retirarse de un curso.
     */
    public function destroy(Inscripcion $inscripcion): RedirectResponse
    {
        Gate::authorize('delete', $inscripcion);

        $this->inscripciones->retirar($inscripcion);

        return redirect()
            ->route('estudiante.inscripciones.index')
            ->with('status', 'Te retiraste del curso correctamente.');
    }
}
