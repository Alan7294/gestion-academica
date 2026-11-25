<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Modalidad;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanEstudioRequest;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\PlanEstudio;
use App\Services\PlanEstudioService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanEstudioController extends Controller
{
    public function __construct(private readonly PlanEstudioService $planes) {}

    public function index(Request $request): View
    {
        $filtros = [
            'busqueda' => trim((string) $request->query('busqueda')),
            'carrera_id' => (string) $request->query('carrera_id'),
            'modalidad' => (string) $request->query('modalidad'),
            'activo' => (string) $request->query('activo'),
        ];

        if (! in_array($filtros['modalidad'], Modalidad::valores(), true)) {
            $filtros['modalidad'] = '';
        }

        if (! in_array($filtros['activo'], ['', '1', '0'], true)) {
            $filtros['activo'] = '';
        }

        $planes = PlanEstudio::query()
            ->with(['carrera', 'materias'])
            ->withCount('materias')
            ->when($filtros['busqueda'] !== '', function ($query) use ($filtros) {
                $query->where(function ($sub) use ($filtros) {
                    $sub->where('nombre', 'ilike', "%{$filtros['busqueda']}%")
                        ->orWhereHas('carrera', fn ($carrera) => $carrera->where('nombre', 'ilike', "%{$filtros['busqueda']}%"));
                });
            })
            ->when($filtros['carrera_id'] !== '', fn ($query) => $query->where('carrera_id', $filtros['carrera_id']))
            ->when($filtros['modalidad'] !== '', fn ($query) => $query->where('modalidad', $filtros['modalidad']))
            ->when($filtros['activo'] !== '', fn ($query) => $query->where('activo', $filtros['activo'] === '1'))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('admin.planes.index', [
            'planes' => $planes,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'modalidades' => Modalidad::cases(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('admin.planes.create', $this->opciones());
    }

    public function store(StorePlanEstudioRequest $request): RedirectResponse
    {
        $plan = $this->planes->crear($this->datos($request));

        return redirect()
            ->route('admin.planes.show', $plan)
            ->with('status', 'Plan de estudios registrado correctamente.');
    }

    public function show(PlanEstudio $planEstudio): View
    {
        $this->cargarPlanConRequisitos($planEstudio);

        return view('admin.planes.show', ['plan' => $planEstudio]);
    }

    public function edit(PlanEstudio $planEstudio): View
    {
        $this->cargarPlanConRequisitos($planEstudio);

        return view('admin.planes.edit', ['plan' => $planEstudio] + $this->opciones($planEstudio));
    }

    public function update(StorePlanEstudioRequest $request, PlanEstudio $planEstudio): RedirectResponse
    {
        $this->planes->actualizar($planEstudio, $this->datos($request));

        return redirect()
            ->route('admin.planes.show', $planEstudio)
            ->with('status', 'Plan de estudios actualizado correctamente.');
    }

    public function destroy(PlanEstudio $planEstudio): RedirectResponse
    {
        $this->planes->eliminar($planEstudio);

        return redirect()
            ->route('admin.planes.index')
            ->with('status', 'Plan de estudios eliminado.');
    }

    /**
     * Carga las materias del plan con los requisitos declarados en ese plan.
     */
    private function cargarPlanConRequisitos(PlanEstudio $plan): void
    {
        $plan->load([
            'carrera',
            'materias' => fn (BelongsToMany $materias) => $materias->with([
                'requisitos' => fn (BelongsToMany $requisitos) => $requisitos
                    ->withPivotValue('plan_estudio_id', $plan->id),
            ]),
        ]);
    }

    /**
     * Opciones del formulario. Solo se carga el catálogo de la carrera elegida;
     * el resto se consulta al cambiar de carrera para no traer todo el catálogo.
     *
     * @return array<string, mixed>
     */
    private function opciones(?PlanEstudio $plan = null): array
    {
        $carreraId = (int) old('carrera_id', $plan?->carrera_id ?? 0);

        return [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'modalidades' => Modalidad::cases(),
            'materias' => $carreraId > 0
                ? Materia::query()
                    ->where('carrera_id', $carreraId)
                    ->select(['id', 'carrera_id', 'codigo', 'nombre'])
                    ->orderBy('codigo')
                    ->get()
                    ->map(fn (Materia $materia) => [
                        'id' => $materia->id,
                        'carrera_id' => $materia->carrera_id,
                        'codigo' => $materia->codigo,
                        'nombre' => $materia->nombre,
                    ])
                    ->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(StorePlanEstudioRequest $request): array
    {
        return [
            'carrera_id' => (int) $request->validated('carrera_id'),
            'nombre' => $request->validated('nombre'),
            'modalidad' => $request->validated('modalidad'),
            'semestres' => (int) $request->validated('semestres'),
            'activo' => $request->boolean('activo'),
            'materias' => $request->validated('materias'),
        ];
    }
}
