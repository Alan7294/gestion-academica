<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CursoEvaluacionService
{
    /**
     * Sincroniza las actividades del curso y exige que la ponderación total sea 100%.
     *
     * @param  array<int, array{id?: int|string|null, nombre: string, ponderacion: int|float|string}>  $evaluaciones
     *
     * @throws AuthorizationException|ValidationException
     */
    public function guardar(Curso $curso, array $evaluaciones, User $docente): void
    {
        $this->verificarDocente($curso, $docente);
        $this->verificarSuma($evaluaciones);

        DB::transaction(function () use ($curso, $evaluaciones): void {
            $idsConservados = collect($evaluaciones)
                ->pluck('id')
                ->filter()
                ->map(fn (int|string $id) => (int) $id)
                ->all();

            $curso->evaluaciones()->whereNotIn('id', $idsConservados)->delete();

            foreach ($evaluaciones as $datos) {
                $atributos = [
                    'nombre' => $datos['nombre'],
                    'ponderacion' => round((float) $datos['ponderacion'], 2),
                ];

                if (! empty($datos['id'])) {
                    $curso->evaluaciones()
                        ->whereKey((int) $datos['id'])
                        ->firstOrFail()
                        ->update($atributos);

                    continue;
                }

                $curso->evaluaciones()->create($atributos);
            }

            $this->recalcularNotas($curso);
        });
    }

    /**
     * @param  array<int, array{id?: int|string|null, nombre: string, ponderacion: int|float|string}>  $evaluaciones
     *
     * @throws ValidationException
     */
    private function verificarSuma(array $evaluaciones): void
    {
        $suma = round(collect($evaluaciones)->sum(
            fn (array $evaluacion) => (float) ($evaluacion['ponderacion'] ?? 0),
        ), 2);

        if (abs($suma - 100) > 0.001) {
            throw ValidationException::withMessages([
                'evaluaciones' => 'La suma de las ponderaciones debe ser exactamente 100.00%.',
            ]);
        }
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
            throw new AuthorizationException('Solo el docente del curso puede gestionar sus actividades.');
        }
    }

    private function recalcularNotas(Curso $curso): void
    {
        $curso->inscripciones()
            ->with('calificaciones.evaluacion')
            ->get()
            ->each(function (Inscripcion $inscripcion): void {
                $inscripcion->update([
                    'nota_final' => $inscripcion->promedioPonderado(),
                ]);
            });
    }
}
