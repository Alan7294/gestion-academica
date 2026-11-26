<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMateriaRequest;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\PlanEstudio;
use App\Services\MateriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MateriaController extends Controller
{
    public function __construct(private readonly MateriaService $materias) {}

    public function index(Request $request): View
    {
        $filtros = [
            'busqueda' => trim((string) $request->query('busqueda')),
            'carrera_id' => (string) $request->query('carrera_id'),
            'semestre' => (string) $request->query('semestre'),
        ];

        $carreraId = $filtros['carrera_id'] !== '' ? (int) $filtros['carrera_id'] : null;
        $semestre = $filtros['semestre'] !== '' ? (int) $filtros['semestre'] : null;

        $materias = Materia::query()
            ->with('planesEstudio.carrera')
            ->withCount(['cursos', 'planesEstudio'])
            ->when($filtros['busqueda'] !== '', function ($query) use ($filtros) {
                $query->where(function ($sub) use ($filtros) {
                    $sub->where('codigo', 'ilike', "%{$filtros['busqueda']}%")
                        ->orWhere('nombre', 'ilike', "%{$filtros['busqueda']}%");
                });
            })
            ->when($carreraId !== null || $semestre !== null, fn ($query) => $query->enPlanes($carreraId, $semestre))
            ->orderBy('codigo')
            ->paginate(10)
            ->withQueryString();

        return view('admin.materias.index', [
            'materias' => $materias,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'semestres' => DB::table('materia_plan_estudio')
                ->distinct()
                ->orderBy('semestre')
                ->pluck('semestre')
                ->all(),
            'filtros' => $filtros,
        ]);
    }

    /**
     * RF09: detalle de la materia con sus planes, cursos y requisitos.
     */
    public function show(Materia $materia): View
    {
        $materia->load([
            'planesEstudio.carrera',
            'cursos.periodo',
            'cursos.docente',
            'requisitos',
        ]);

        return view('admin.materias.show', ['materia' => $materia]);
    }

    public function create(): View
    {
        return view('admin.materias.create', [
            'planes' => PlanEstudio::with('carrera')->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Catálogo compartido que usa el formulario de planes. Cada plan decide
     * en qué semestre y con cuántos créditos incluye cada materia.
     */
    public function catalogo(): JsonResponse
    {
        return response()->json(
            Materia::query()
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']),
        );
    }

    public function store(StoreMateriaRequest $request): RedirectResponse|JsonResponse
    {
        $materia = $this->materias->crear($this->datos($request));

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $materia->id,
                'codigo' => $materia->codigo,
                'nombre' => $materia->nombre,
            ], 201);
        }

        return redirect()
            ->route('admin.materias.index')
            ->with('status', 'Materia registrada correctamente.');
    }

    public function edit(Materia $materia): View
    {
        return view('admin.materias.edit', ['materia' => $materia]);
    }

    public function update(StoreMateriaRequest $request, Materia $materia): RedirectResponse
    {
        $this->materias->actualizar($materia, $this->datos($request));

        return redirect()
            ->route('admin.materias.index')
            ->with('status', 'Materia actualizada correctamente.');
    }

    public function destroy(Materia $materia): RedirectResponse
    {
        try {
            $this->materias->eliminar($materia);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.materias.index')
            ->with('status', 'Materia eliminada.');
    }

    /**
     * @return array{codigo: string, nombre: string, plan_estudio_id?: int, semestre?: int, creditos?: int}
     */
    private function datos(StoreMateriaRequest $request): array
    {
        $datos = [
            'codigo' => $request->validated('codigo'),
            'nombre' => $request->validated('nombre'),
        ];

        if ($request->isMethod('POST') && $request->validated('plan_estudio_id') !== null) {
            $datos['plan_estudio_id'] = (int) $request->validated('plan_estudio_id');
            $datos['semestre'] = (int) $request->validated('semestre');
            $datos['creditos'] = (int) $request->validated('creditos');
        }

        return $datos;
    }
}
