<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCursoRequest;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Periodo;
use App\Repositories\Contracts\CursoRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CursoController extends Controller
{
    public function __construct(private readonly CursoRepository $cursos) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['carrera_id', 'periodo_id', 'docente_id', 'busqueda']);

        return view('admin.cursos.index', [
            'cursos' => $this->cursos->paginar($filtros),
            'carreras' => Carrera::orderBy('nombre')->get(),
            'periodos' => Periodo::orderByDesc('fecha_inicio')->get(),
            'docentes' => Curso::docentesDisponibles(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('admin.cursos.create', $this->opciones());
    }

    public function store(StoreCursoRequest $request): RedirectResponse
    {
        $curso = $this->cursos->crear($this->datos($request));

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Curso registrado. Ahora puedes asignar su horario.');
    }

    public function show(Curso $curso): View
    {
        $curso->load(['carrera', 'materia.planEstudio', 'periodo', 'docente', 'horarios']);

        return view('admin.cursos.show', ['curso' => $curso]);
    }

    public function edit(Curso $curso): View
    {
        return view('admin.cursos.edit', ['curso' => $curso] + $this->opciones());
    }

    public function update(StoreCursoRequest $request, Curso $curso): RedirectResponse
    {
        $nuevoDocente = $request->validated('docente_id');

        if ($curso->horarios()->exists() && (int) $nuevoDocente !== (int) $curso->docente_id) {
            return back()
                ->withInput()
                ->withErrors([
                    'docente_id' => 'No puedes cambiar el docente mientras el curso tenga horarios asignados. Elimínalos primero.',
                ]);
        }

        $this->cursos->actualizar($curso, $this->datos($request));

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Curso actualizado correctamente.');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        $this->cursos->eliminar($curso);

        return redirect()
            ->route('admin.cursos.index')
            ->with('status', 'Curso eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opciones(): array
    {
        return [
            'carreras' => Carrera::where('activo', true)->orderBy('nombre')->get(),
            'materias' => Materia::with('planEstudio.carrera')
                ->get()
                ->map(fn (Materia $materia) => [
                    'id' => $materia->id,
                    'carrera_id' => $materia->planEstudio->carrera_id,
                    'etiqueta' => "{$materia->planEstudio->carrera->nombre} · {$materia->planEstudio->nombre} · {$materia->codigo} - {$materia->nombre}",
                ])
                ->sortBy('etiqueta')
                ->values()
                ->all(),
            'periodos' => Periodo::orderByDesc('fecha_inicio')->get(),
            'docentes' => Curso::docentesDisponibles(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(StoreCursoRequest $request): array
    {
        return [
            'carrera_id' => $request->validated('carrera_id'),
            'materia_id' => (int) $request->validated('materia_id'),
            'periodo_id' => $request->validated('periodo_id'),
            'docente_id' => $request->validated('docente_id'),
            'codigo' => $request->validated('codigo'),
            'nombre' => $request->validated('nombre'),
            'creditos' => $request->validated('creditos'),
            'semestre' => $request->validated('semestre'),
            'cupo' => $request->validated('cupo'),
            'activo' => $request->boolean('activo'),
        ];
    }
}
