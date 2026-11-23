<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Modalidad;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanEstudioRequest;
use App\Models\Carrera;
use App\Models\PlanEstudio;
use App\Services\PlanEstudioService;
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
        ];

        if (! in_array($filtros['modalidad'], Modalidad::valores(), true)) {
            $filtros['modalidad'] = '';
        }

        $planes = PlanEstudio::query()
            ->with('carrera')
            ->withCount('materias')
            ->withSum('materias', 'creditos')
            ->when($filtros['busqueda'] !== '', function ($query) use ($filtros) {
                $query->where(function ($sub) use ($filtros) {
                    $sub->where('nombre', 'ilike', "%{$filtros['busqueda']}%")
                        ->orWhereHas('carrera', fn ($carrera) => $carrera->where('nombre', 'ilike', "%{$filtros['busqueda']}%"));
                });
            })
            ->when($filtros['carrera_id'] !== '', fn ($query) => $query->where('carrera_id', $filtros['carrera_id']))
            ->when($filtros['modalidad'] !== '', fn ($query) => $query->where('modalidad', $filtros['modalidad']))
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
        $planEstudio->load(['carrera', 'materias.requisitos']);

        return view('admin.planes.show', ['plan' => $planEstudio]);
    }

    public function edit(PlanEstudio $planEstudio): View
    {
        $planEstudio->load('materias.requisitos');

        return view('admin.planes.edit', ['plan' => $planEstudio] + $this->opciones());
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
     * @return array<string, mixed>
     */
    private function opciones(): array
    {
        return [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'modalidades' => Modalidad::cases(),
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
