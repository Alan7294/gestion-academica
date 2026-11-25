<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacultadRequest;
use App\Models\Facultad;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FacultadController extends Controller
{
    public function index(): View
    {
        return view('admin.facultades.index', [
            'facultades' => Facultad::withCount('carreras')->orderBy('nombre')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.facultades.create');
    }

    public function store(StoreFacultadRequest $request): RedirectResponse
    {
        Facultad::create($this->datos($request));

        return redirect()
            ->route('admin.facultades.index')
            ->with('status', 'Facultad registrada correctamente.');
    }

    public function edit(Facultad $facultad): View
    {
        return view('admin.facultades.edit', ['facultad' => $facultad]);
    }

    public function update(StoreFacultadRequest $request, Facultad $facultad): RedirectResponse
    {
        $facultad->update($this->datos($request));

        return redirect()
            ->route('admin.facultades.index')
            ->with('status', 'Facultad actualizada correctamente.');
    }

    public function destroy(Facultad $facultad): RedirectResponse
    {
        if ($facultad->carreras()->exists()) {
            return back()->withErrors([
                'facultad' => 'No se puede eliminar una facultad que tiene carreras registradas.',
            ]);
        }

        $facultad->delete();

        return redirect()
            ->route('admin.facultades.index')
            ->with('status', 'Facultad eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(StoreFacultadRequest $request): array
    {
        return [
            'nombre' => $request->validated('nombre'),
            'activo' => $request->boolean('activo'),
        ];
    }
}
