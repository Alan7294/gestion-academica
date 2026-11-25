<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquivalenciaRequest;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EquivalenciaController extends Controller
{
    public function index(): View
    {
        $equivalencias = MateriaEquivalencia::query()
            ->with([
                'materia.planesEstudio.carrera',
                'equivalente.planesEstudio.carrera',
            ])
            ->orderBy('materia_id')
            ->get();

        $materias = Materia::with('planesEstudio.carrera')->orderBy('codigo')->get();

        return view('admin.equivalencias.index', [
            'equivalencias' => $equivalencias,
            'materias' => $materias,
            'carrerasPorMateria' => $materias
                ->mapWithKeys(fn (Materia $materia) => [
                    $materia->id => $materia->planesEstudio
                        ->pluck('carrera.id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ])
                ->all(),
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreEquivalenciaRequest $request): RedirectResponse
    {
        $materiaId = (int) $request->validated('materia_id');
        $equivalenteId = (int) $request->validated('equivalente_id');
        $esBilateral = $request->boolean('bilateral');

        DB::transaction(function () use ($materiaId, $equivalenteId, $esBilateral) {
            MateriaEquivalencia::create([
                'materia_id' => $materiaId,
                'equivalente_id' => $equivalenteId,
            ]);

            if ($esBilateral) {
                MateriaEquivalencia::create([
                    'materia_id' => $equivalenteId,
                    'equivalente_id' => $materiaId,
                ]);
            }
        });

        return redirect()
            ->route('admin.equivalencias.index')
            ->with('status', $esBilateral
                ? 'Equivalencia registrada en ambos sentidos.'
                : 'Equivalencia registrada correctamente.');
    }

    public function destroy(MateriaEquivalencia $equivalencia): RedirectResponse
    {
        $equivalencia->delete();

        return redirect()
            ->route('admin.equivalencias.index')
            ->with('status', 'Equivalencia eliminada.');
    }
}
