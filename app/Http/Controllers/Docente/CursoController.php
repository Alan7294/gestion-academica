<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CursoController extends Controller
{
    /**
     * RF06: cursos asignados al docente.
     */
    public function index(Request $request): View
    {
        $cursos = Curso::query()
            ->with(['carrera', 'periodo'])
            ->withCount('alumnosInscritos')
            ->delDocente($request->user())
            ->orderBy('nombre')
            ->paginate(10);

        return view('docente.cursos.index', ['cursos' => $cursos]);
    }

    /**
     * RF12: alumnos inscritos al curso (base del listado descargable).
     */
    public function show(Curso $curso): View
    {
        Gate::authorize('view', $curso);

        $curso->load(['carrera', 'periodo', 'horarios', 'alumnosInscritos.estudiante']);

        return view('docente.cursos.show', ['curso' => $curso]);
    }
}
