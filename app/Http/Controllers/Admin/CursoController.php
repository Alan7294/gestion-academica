<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Modalidad;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCursoRequest;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use App\Repositories\Contracts\CursoRepository;
use App\Services\HorarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CursoController extends Controller
{
    public function __construct(
        private readonly CursoRepository $cursos,
        private readonly HorarioService $horarios,
    ) {}

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
        $datos = $this->datos($request);
        $horario = $request->safe()->only([
            'modalidad',
            'horarios',
            'fecha_inicio',
            'hora_inicio',
            'hora_fin',
            'aula',
        ]);

        // Curso y horario inicial se guardan juntos: si una sesión choca, no queda el curso creado.
        $curso = DB::transaction(function () use ($datos, $horario): Curso {
            $curso = $this->cursos->crear($datos);
            $this->horarios->registrarInicial($curso, $horario);

            return $curso;
        });

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Curso y horario registrados correctamente.');
    }

    public function show(Curso $curso): View
    {
        $curso->load(['carrera', 'planEstudio', 'materia.carrera', 'periodo', 'docente', 'horarios']);

        return view('admin.cursos.show', [
            'curso' => $curso,
            'puedeEliminarse' => ! $curso->inscripciones()->exists(),
        ]);
    }

    public function edit(Curso $curso): View
    {
        return view('admin.cursos.edit', ['curso' => $curso] + $this->opciones());
    }

    public function update(StoreCursoRequest $request, Curso $curso): RedirectResponse
    {
        $datos = $this->datos($request);

        $this->validarCambios($curso, $datos);

        $this->cursos->actualizar($curso, $datos);

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Curso actualizado correctamente.');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        if ($curso->inscripciones()->exists()) {
            throw ValidationException::withMessages([
                'curso' => 'No se puede eliminar un curso que ya tiene inscripciones. El historial de los estudiantes se perdería.',
            ]);
        }

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
            'modalidades' => Modalidad::cases(),
            'carreras' => Carrera::where('activo', true)->orderBy('nombre')->get(),
            'planes' => PlanEstudio::query()
                ->with('materias')
                ->orderByDesc('activo')
                ->orderBy('nombre')
                ->get()
                ->map(fn (PlanEstudio $plan) => [
                    'id' => $plan->id,
                    'carrera_id' => $plan->carrera_id,
                    'modalidad' => $plan->modalidad->value,
                    'etiqueta' => "{$plan->nombre} ({$plan->modalidad->etiqueta()})".($plan->activo ? '' : ' · inactivo'),
                    'materias' => $plan->materias
                        ->map(fn (Materia $materia) => [
                            'id' => $materia->id,
                            'etiqueta' => "{$materia->codigo} - {$materia->nombre}",
                            'semestre' => (int) $materia->pivot->semestre,
                            'creditos' => (int) $materia->pivot->creditos,
                        ])
                        ->values()
                        ->all(),
                ])
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
        $planId = (int) $request->validated('plan_estudio_id');
        $materiaId = (int) $request->validated('materia_id');

        return array_merge([
            'carrera_id' => $request->validated('carrera_id'),
            'plan_estudio_id' => $planId,
            'materia_id' => $materiaId,
            'periodo_id' => $request->validated('periodo_id'),
            'docente_id' => $request->validated('docente_id'),
            'codigo' => $request->validated('codigo'),
            'cupo' => $request->validated('cupo'),
            'activo' => $request->boolean('activo'),
        ], $this->datosAcademicos($planId, $materiaId));
    }

    /**
     * El nombre, el semestre y los créditos no se piden: se derivan del plan.
     *
     * @return array{nombre: string, modalidad: Modalidad, creditos: int, semestre: int}
     */
    private function datosAcademicos(int $planId, int $materiaId): array
    {
        $plan = PlanEstudio::whereKey($planId)->firstOrFail();
        $materia = $plan->materias()->findOrFail($materiaId);

        return [
            'nombre' => $materia->nombre,
            'modalidad' => $plan->modalidad,
            'creditos' => (int) $materia->pivot->creditos,
            'semestre' => (int) $materia->pivot->semestre,
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws ValidationException
     */
    private function validarCambios(Curso $curso, array $datos): void
    {
        $errores = [];

        if ($curso->inscripciones()->exists()) {
            $identidad = [
                'carrera_id' => 'No puedes cambiar la carrera de un curso que ya tiene inscripciones.',
                'plan_estudio_id' => 'No puedes cambiar el plan de estudios de un curso que ya tiene inscripciones.',
                'materia_id' => 'No puedes cambiar la materia de un curso que ya tiene inscripciones.',
                'periodo_id' => 'No puedes cambiar el periodo de un curso que ya tiene inscripciones.',
                'codigo' => 'No puedes cambiar el código de un curso que ya tiene inscripciones.',
            ];

            foreach ($identidad as $campo => $mensaje) {
                if ($curso->{$campo} !== null && (string) $curso->{$campo} !== (string) ($datos[$campo] ?? '')) {
                    $errores[$campo] = $mensaje;
                }
            }
        }

        if ($curso->horarios()->exists()) {
            if ($curso->modalidad !== $datos['modalidad']) {
                $errores['modalidad'] = 'No puedes cambiar la modalidad de un curso que ya tiene horarios. Elimínalos primero.';
            }

            if ((int) $curso->periodo_id !== (int) ($datos['periodo_id'] ?? 0)) {
                $errores['periodo_id'] = 'No puedes cambiar el periodo de un curso que ya tiene horarios. Elimínalos primero.';
            }

            if ($curso->plan_estudio_id !== null
                && (int) $curso->plan_estudio_id !== (int) ($datos['plan_estudio_id'] ?? 0)) {
                $errores['plan_estudio_id'] = 'No puedes cambiar el plan de estudios de un curso que ya tiene horarios. Elimínalos primero.';
            }

            if ((int) $curso->docente_id !== (int) ($datos['docente_id'] ?? 0)) {
                $errores['docente_id'] = 'No puedes cambiar el docente mientras el curso tenga horarios asignados. Elimínalos primero.';
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }
}
