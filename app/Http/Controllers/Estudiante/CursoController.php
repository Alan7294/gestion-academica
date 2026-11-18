<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CursoController extends Controller
{
    /**
     * RF06: catálogo de cursos disponibles para el estudiante.
     */
    public function index(Request $request): View
    {
        $estudiante = $request->user();
        $periodo = Periodo::actual();
        $busqueda = trim((string) $request->query('busqueda'));
        $carreraId = $request->integer('carrera_id') ?: null;

        $cursos = Curso::query()
            ->with(['carrera', 'docente', 'horarios'])
            ->withCount('alumnosInscritos')
            ->where('activo', true)
            ->when($periodo, fn ($query) => $query->where('periodo_id', $periodo->id))
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('nombre', 'ilike', "%{$busqueda}%")
                        ->orWhere('codigo', 'ilike', "%{$busqueda}%");
                });
            })
            ->when($carreraId, fn ($query, $id) => $query->where('carrera_id', $id))
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        $inscritos = Inscripcion::query()
            ->where('user_id', $estudiante->id)
            ->activas()
            ->pluck('curso_id')
            ->all();

        return view('estudiante.cursos.index', [
            'cursos' => $cursos,
            'carreras' => Carrera::where('activo', true)->orderBy('nombre')->get(),
            'periodo' => $periodo,
            'inscritos' => $inscritos,
            'filtros' => ['busqueda' => $busqueda, 'carrera_id' => $carreraId],
        ]);
    }

    public function show(Curso $curso): View
    {
        Gate::authorize('view', $curso);

        $curso->load(['carrera', 'periodo', 'docente', 'horarios'])
            ->loadCount('alumnosInscritos');

        return view('estudiante.cursos.show', ['curso' => $curso]);
    }
}
