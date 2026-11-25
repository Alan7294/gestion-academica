<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Modalidad;
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

        $mensaje = $curso->modalidad === Modalidad::Modular
            ? 'Módulo registrado correctamente.'
            : 'Horario agregado correctamente.';

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', $mensaje);
    }

    public function destroy(Curso $curso, Horario $horario): RedirectResponse
    {
        abort_unless($horario->curso_id === $curso->id, 404);

        $mensaje = 'Horario eliminado.';

        if ($curso->modalidad === Modalidad::Modular) {
            // Un módulo se administra como un todo: sus veinte sesiones comparten vigencia.
            $curso->horarios()
                ->where('fecha_inicio', $horario->fecha_inicio->toDateString())
                ->where('fecha_fin', $horario->fecha_fin->toDateString())
                ->delete();

            $mensaje = 'Módulo eliminado correctamente.';
        } else {
            $horario->delete();
        }

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', $mensaje);
    }
}
