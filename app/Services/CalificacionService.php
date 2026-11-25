<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\User;
use App\Notifications\NotaRegistrada;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalificacionService
{
    /**
     * Guarda la grilla de notas de un curso y recalcula el promedio de cada alumno afectado.
     *
     * @param  array<int|string, array<int|string, int|float|string|null>>  $notas
     *
     * @throws AuthorizationException|ValidationException
     */
    public function guardarNotas(Curso $curso, array $notas, User $docente): void
    {
        $this->verificarDocente($curso, $docente);

        $evaluaciones = $curso->evaluaciones()->get()->keyBy('id');

        if ($evaluaciones->isEmpty()) {
            throw ValidationException::withMessages([
                'notas' => 'Primero debes definir las actividades y sus ponderaciones.',
            ]);
        }

        $inscripcionesValidas = $curso->inscripciones()->pluck('id')->all();
        $cambios = collect();

        DB::transaction(function () use ($notas, $docente, $evaluaciones, $inscripcionesValidas, &$cambios): void {
            foreach ($notas as $inscripcionId => $valores) {
                if (! is_array($valores) || ! in_array((int) $inscripcionId, $inscripcionesValidas, true)) {
                    continue;
                }

                $inscripcion = Inscripcion::query()
                    ->whereKey($inscripcionId)
                    ->with('calificaciones')
                    ->firstOrFail();

                foreach ($valores as $evaluacionId => $valor) {
                    $evaluacion = $evaluaciones->get((int) $evaluacionId);

                    if ($evaluacion === null) {
                        continue;
                    }

                    $existente = $inscripcion->calificaciones
                        ->firstWhere('curso_evaluacion_id', $evaluacion->id);

                    if ($valor === null || $valor === '') {
                        $existente?->delete();

                        continue;
                    }

                    if ($existente !== null) {
                        if ((float) $existente->nota !== (float) $valor) {
                            $existente->update([
                                'nota' => $valor,
                                'registrado_por' => $docente->id,
                            ]);

                            $cambios->push($existente);
                        }

                        continue;
                    }

                    $cambios->push($inscripcion->calificaciones()->create([
                        'curso_evaluacion_id' => $evaluacion->id,
                        'registrado_por' => $docente->id,
                        'nota' => $valor,
                        'fecha' => now()->toDateString(),
                    ]));
                }

                $this->recalcular($inscripcion);
            }
        });

        $cambios->each(function (Calificacion $calificacion): void {
            $calificacion->loadMissing(['inscripcion.curso', 'evaluacion']);
            $calificacion->inscripcion->estudiante->notify(new NotaRegistrada($calificacion));
        });
    }

    /**
     * Guarda en la inscripción el promedio ponderado vigente.
     */
    private function recalcular(Inscripcion $inscripcion): void
    {
        $inscripcion->unsetRelation('calificaciones');

        $inscripcion->update([
            'nota_final' => $inscripcion->promedioPonderado(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function verificarDocente(Curso $curso, User $docente): void
    {
        if ($docente->esAdministrador()) {
            return;
        }

        if ($curso->docente_id !== $docente->id) {
            throw new AuthorizationException('Solo el docente del curso puede gestionar sus calificaciones.');
        }
    }
}
