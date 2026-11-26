<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarreraRequest;
use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CarreraController extends Controller
{
    public function index(): View
    {
        return view('admin.carreras.index', [
            'carreras' => Carrera::with('facultad')
                ->withCount(['cursos', 'planesEstudio'])
                ->orderBy('nombre')
                ->paginate(10),
        ]);
    }

    /**
     * RF09: detalle de la carrera con sus planes y cursos.
     */
    public function show(Carrera $carrera): View
    {
        $carrera->load([
            'facultad',
            'planesEstudio' => fn ($planes) => $planes->orderBy('nombre'),
            'cursos' => fn ($cursos) => $cursos
                ->with(['materia', 'periodo', 'docente'])
                ->orderBy('nombre'),
        ]);

        return view('admin.carreras.show', ['carrera' => $carrera]);
    }

    public function create(): View
    {
        return view('admin.carreras.create', $this->opciones());
    }

    public function store(StoreCarreraRequest $request): RedirectResponse
    {
        Carrera::create($this->datos($request));

        return redirect()
            ->route('admin.carreras.index')
            ->with('status', 'Carrera registrada correctamente.');
    }

    public function edit(Carrera $carrera): View
    {
        return view('admin.carreras.edit', ['carrera' => $carrera] + $this->opciones());
    }

    public function update(StoreCarreraRequest $request, Carrera $carrera): RedirectResponse
    {
        $carrera->update($this->datos($request));

        return redirect()
            ->route('admin.carreras.index')
            ->with('status', 'Carrera actualizada correctamente.');
    }

    public function destroy(Carrera $carrera): RedirectResponse
    {
        if ($carrera->cursos()->exists()) {
            return back()->withErrors([
                'carrera' => 'No se puede eliminar una carrera que tiene cursos registrados.',
            ]);
        }

        if ($carrera->planesEstudio()->exists()) {
            return back()->withErrors([
                'carrera' => 'No se puede eliminar una carrera que tiene planes de estudio registrados.',
            ]);
        }

        if ($carrera->matriculas()->exists()) {
            return back()->withErrors([
                'carrera' => 'No se puede eliminar una carrera que tiene matrículas registradas.',
            ]);
        }

        $carrera->delete();

        return redirect()
            ->route('admin.carreras.index')
            ->with('status', 'Carrera eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opciones(): array
    {
        return [
            'facultades' => Facultad::orderBy('nombre')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(StoreCarreraRequest $request): array
    {
        return [
            'codigo' => $request->validated('codigo'),
            'nombre' => $request->validated('nombre'),
            'facultad_id' => $request->validated('facultad_id'),
            'activo' => $request->boolean('activo'),
        ];
    }
}
