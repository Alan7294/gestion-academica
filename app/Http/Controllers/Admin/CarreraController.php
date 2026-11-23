<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarreraRequest;
use App\Models\Carrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CarreraController extends Controller
{
    public function index(): View
    {
        return view('admin.carreras.index', [
            'carreras' => Carrera::withCount(['cursos', 'planesEstudio'])->orderBy('nombre')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.carreras.create');
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
        return view('admin.carreras.edit', ['carrera' => $carrera]);
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

        $carrera->delete();

        return redirect()
            ->route('admin.carreras.index')
            ->with('status', 'Carrera eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(StoreCarreraRequest $request): array
    {
        return [
            'codigo' => $request->validated('codigo'),
            'nombre' => $request->validated('nombre'),
            'facultad' => $request->validated('facultad'),
            'activo' => $request->boolean('activo'),
        ];
    }
}
