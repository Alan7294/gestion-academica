<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Reports\Docente\AlumnosInscritosReporte;
use App\Reports\Docente\MateriasDocenteReporte;
use App\Reports\Docente\NotasPorAlumnoReporte;
use App\Reports\Docente\NotasPorCursoReporte;
use App\Reports\FiltrosReporte;
use App\Reports\FormatoFactory;
use App\Reports\VistaPreviaReporte;
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

    public function previewMaterias(Request $request): View
    {
        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new MateriasDocenteReporte($request->user(), $filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('docente.reportes.materias', ['formato' => 'pdf'] + $filtros->query()),
                'excel' => route('docente.reportes.materias', ['formato' => 'excel'] + $filtros->query()),
            ],
            route('docente.reportes.index'),
        );
    }

    public function previewNotasCurso(Request $request, Curso $curso): View
    {
        Gate::authorize('view', $curso);

        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new NotasPorCursoReporte($curso, $filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('docente.reportes.notas-curso', ['curso' => $curso, 'formato' => 'pdf'] + $filtros->query()),
                'excel' => route('docente.reportes.notas-curso', ['curso' => $curso, 'formato' => 'excel'] + $filtros->query()),
            ],
            route('docente.reportes.index'),
        );
    }

    public function previewAlumnosInscritos(Request $request, Curso $curso): View
    {
        Gate::authorize('view', $curso);

        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new AlumnosInscritosReporte($curso, $filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('docente.reportes.alumnos', ['curso' => $curso, 'formato' => 'pdf'] + $filtros->query()),
                'excel' => route('docente.reportes.alumnos', ['curso' => $curso, 'formato' => 'excel'] + $filtros->query()),
            ],
            route('docente.reportes.index'),
        );
    }

    public function previewNotasAlumno(Request $request, Curso $curso, Inscripcion $inscripcion): View
    {
        Gate::authorize('view', $curso);

        abort_unless($inscripcion->curso_id === $curso->id, 404);

        $filtros = FiltrosReporte::desdeRequest($request);
        $reporte = new NotasPorAlumnoReporte($inscripcion, $filtros);

        return VistaPreviaReporte::render(
            $reporte,
            $filtros,
            $request->user(),
            [
                'pdf' => route('docente.reportes.notas-alumno', [
                    'curso' => $curso,
                    'inscripcion' => $inscripcion,
                    'formato' => 'pdf',
                ] + $filtros->query()),
                'excel' => route('docente.reportes.notas-alumno', [
                    'curso' => $curso,
                    'inscripcion' => $inscripcion,
                    'formato' => 'excel',
                ] + $filtros->query()),
            ],
            route('docente.reportes.index'),
        );
    }

    public function materias(Request $request, string $formato, FormatoFactory $formatos): Response
    {
        return $formatos->crear($formato)->generar(
            new MateriasDocenteReporte($request->user(), FiltrosReporte::desdeRequest($request)),
        );
    }

    public function notasCurso(Request $request, Curso $curso, string $formato, FormatoFactory $formatos): Response
    {
        Gate::authorize('view', $curso);

        return $formatos->crear($formato)->generar(
            new NotasPorCursoReporte($curso, FiltrosReporte::desdeRequest($request)),
        );
    }

    public function alumnosInscritos(Request $request, Curso $curso, string $formato, FormatoFactory $formatos): Response
    {
        Gate::authorize('view', $curso);

        return $formatos->crear($formato)->generar(
            new AlumnosInscritosReporte($curso, FiltrosReporte::desdeRequest($request)),
        );
    }

    public function notasAlumno(
        Request $request,
        Curso $curso,
        Inscripcion $inscripcion,
        string $formato,
        FormatoFactory $formatos,
    ): Response {
        Gate::authorize('view', $curso);

        abort_unless($inscripcion->curso_id === $curso->id, 404);

        return $formatos->crear($formato)->generar(
            new NotasPorAlumnoReporte($inscripcion, FiltrosReporte::desdeRequest($request)),
        );
    }
}
