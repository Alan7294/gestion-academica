<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHorarioRequest;
use App\Models\Curso;
use App\Models\Horario;
use App\Services\HorarioService;
use Illuminate\Http\RedirectResponse;

class HorarioController extends Controller
{
    public function __construct(private readonly HorarioService $horarios) {}

    public function store(StoreHorarioRequest $request, Curso $curso): RedirectResponse
    {
        $this->horarios->agregar($curso, $request->validated());

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Horario agregado correctamente.');
    }

    public function destroy(Curso $curso, Horario $horario): RedirectResponse
    {
        abort_unless($horario->curso_id === $curso->id, 404);

        $horario->delete();

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Horario eliminado.');
    }
}
