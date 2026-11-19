<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Reports\Docente\AlumnosInscritosReporte;
use App\Reports\Docente\MateriasDocenteReporte;
use App\Reports\Docente\NotasPorAlumnoReporte;
use App\Reports\Docente\NotasPorCursoReporte;
use App\Reports\FormatoFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    /**
     * RF12: catálogo de reportes del docente con sus cursos y alumnos.
     */
    public function index(Request $request): View
    {
        $cursos = Curso::query()
            ->with(['carrera', 'periodo', 'horarios', 'alumnosInscritos.estudiante'])
            ->withCount('alumnosInscritos')
            ->delDocente($request->user())
            ->orderBy('nombre')
            ->get();

        return view('docente.reportes.index', ['cursos' => $cursos]);
    }

    public function materias(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new MateriasDocenteReporte($request->user()),
        );
    }

    public function notasCurso(Curso $curso, string $formato, FormatoFactory $formatos): Response
    {
        Gate::authorize('view', $curso);

        return $formatos->crear($formato)->generar(new NotasPorCursoReporte($curso));
    }

    public function alumnosInscritos(Curso $curso, string $formato, FormatoFactory $formatos): Response
    {
        Gate::authorize('view', $curso);

        return $formatos->crear($formato)->generar(new AlumnosInscritosReporte($curso));
    }

    public function notasAlumno(
        Curso $curso,
        Inscripcion $inscripcion,
        string $formato,
        FormatoFactory $formatos,
    ): Response {
        Gate::authorize('view', $curso);

        abort_unless($inscripcion->curso_id === $curso->id, 404);

        return $formatos->crear($formato)->generar(
            new NotasPorAlumnoReporte($inscripcion->estudiante),
        );
    }
}
