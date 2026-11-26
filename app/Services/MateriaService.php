<?php

namespace App\Services;

use App\Models\Materia;
use App\Models\PlanEstudio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MateriaService
{
    /**
     * @param  array{codigo: string, nombre: string, plan_estudio_id?: int, semestre?: int, creditos?: int}  $datos
     */
    public function crear(array $datos): Materia
    {
        return DB::transaction(function () use ($datos): Materia {
            $materia = Materia::create([
                'codigo' => $datos['codigo'],
                'nombre' => $datos['nombre'],
            ]);

            if (isset($datos['plan_estudio_id'], $datos['semestre'], $datos['creditos'])) {
                PlanEstudio::findOrFail($datos['plan_estudio_id'])
                    ->vincularMateria($materia, $datos['semestre'], $datos['creditos']);
            }

            return $materia;
        });
    }

    /**
     * @param  array{codigo: string, nombre: string}  $datos
     */
    public function actualizar(Materia $materia, array $datos): Materia
    {
        $materia->update($datos);

        return $materia;
    }

    /**
     * @throws ValidationException
     */
    public function eliminar(Materia $materia): void
    {
        if ($materia->cursos()->exists()) {
            throw ValidationException::withMessages([
                'materia' => 'No se puede eliminar una materia que tiene cursos registrados.',
            ]);
        }

        $materia->requeridaPor()->detach();
        $materia->requisitos()->detach();
        $materia->planesEstudio()->detach();

        $materia->delete();
    }
}
