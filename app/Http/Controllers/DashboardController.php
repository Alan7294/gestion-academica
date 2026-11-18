<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * RF08: envía a cada usuario al panel que corresponde a su rol.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return match (true) {
            $request->user()->esAdministrador() => redirect()->route('admin.dashboard'),
            $request->user()->esDocente() => redirect()->route('docente.dashboard'),
            $request->user()->esEstudiante() => redirect()->route('estudiante.dashboard'),
            default => abort(403, 'Tu usuario no tiene un rol asignado.'),
        };
    }

    public function admin(): View
    {
        return view('admin.dashboard', [
            'totalUsuarios' => User::count(),
            'totalEstudiantes' => User::role(Rol::Estudiante->value)->count(),
            'totalDocentes' => User::role(Rol::Docente->value)->count(),
        ]);
    }

    public function docente(Request $request): View
    {
        $cursos = Curso::query()
            ->with('carrera')
            ->withCount('alumnosInscritos')
            ->delDocente($request->user())
            ->orderBy('nombre')
            ->get();

        return view('docente.dashboard', [
            'cursos' => $cursos,
            'totalAlumnos' => $cursos->sum('alumnos_inscritos_count'),
        ]);
    }

    public function estudiante(Request $request): View
    {
        $inscripciones = Inscripcion::query()
            ->with(['curso.carrera', 'curso.docente'])
            ->where('user_id', $request->user()->id)
            ->activas()
            ->get();

        return view('estudiante.dashboard', [
            'inscripciones' => $inscripciones,
        ]);
    }
}
