<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodoRequest;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PeriodoController extends Controller
{
    public function index(): View
    {
        return view('admin.periodos.index', [
            'periodos' => Periodo::withCount('cursos')->orderByDesc('fecha_inicio')->paginate(10),
        ]);
    }

    /**
     * RF09: detalle del periodo con los cursos que se imparten en él.
     */
    public function show(Periodo $periodo): View
    {
        $periodo->load(['cursos' => fn ($cursos) => $cursos
            ->with(['carrera', 'materia', 'docente'])
            ->orderBy('nombre')]);

        return view('admin.periodos.show', ['periodo' => $periodo]);
    }

    public function create(): View
    {
        return view('admin.periodos.create');
    }

    public function store(StorePeriodoRequest $request): RedirectResponse
    {
        $periodo = Periodo::create($this->datos($request));

        if ($periodo->activo) {
            $this->desactivarOtros($periodo);
        }

        return redirect()
            ->route('admin.periodos.index')
            ->with('status', 'Periodo registrado correctamente.');
    }

    public function edit(Periodo $periodo): View
    {
        return view('admin.periodos.edit', ['periodo' => $periodo]);
    }

    public function update(StorePeriodoRequest $request, Periodo $periodo): RedirectResponse
    {
        $periodo->update($this->datos($request));

        if ($periodo->activo) {
            $this->desactivarOtros($periodo);
        }

        return redirect()
            ->route('admin.periodos.index')
            ->with('status', 'Periodo actualizado correctamente.');
    }

    public function destroy(Periodo $periodo): RedirectResponse
    {
        if ($periodo->cursos()->exists()) {
            return back()->withErrors([
                'periodo' => 'No se puede eliminar un periodo que tiene cursos registrados.',
            ]);
        }

        $periodo->delete();

        return redirect()
            ->route('admin.periodos.index')
            ->with('status', 'Periodo eliminado.');
    }

    /**
     * Marca un periodo como el vigente y desactiva el resto.
     */
    public function activar(Periodo $periodo): RedirectResponse
    {
        DB::transaction(function () use ($periodo) {
            Periodo::query()->update(['activo' => false]);
            $periodo->update(['activo' => true]);
        });

        return redirect()
            ->route('admin.periodos.index')
            ->with('status', "El periodo {$periodo->nombre} es ahora el vigente.");
    }

    private function desactivarOtros(Periodo $periodo): void
    {
        Periodo::whereKeyNot($periodo->id)->update(['activo' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(StorePeriodoRequest $request): array
    {
        return [
            'nombre' => $request->validated('nombre'),
            'fecha_inicio' => $request->validated('fecha_inicio'),
            'fecha_fin' => $request->validated('fecha_fin'),
            'activo' => $request->boolean('activo'),
        ];
    }
}
