<?php

namespace App\Services;

use App\Models\PlanEstudio;
use Illuminate\Support\Facades\DB;

class PlanEstudioService
{
    /**
     * Crea un plan de estudios junto con sus materias.
     *
     * @param  array{carrera_id: int, nombre: string, modalidad: string, semestres: int, activo?: bool, materias: array<int, array{codigo: string, nombre: string, semestre: int, creditos: int}>}  $datos
     */
    public function crear(array $datos): PlanEstudio
    {
        return DB::transaction(function () use ($datos) {
            $plan = PlanEstudio::create($this->atributos($datos));

            $this->registrarMaterias($plan, $datos['materias']);

            return $plan->load('materias');
        });
    }

    /**
     * Actualiza el plan y reemplaza por completo su lista de materias.
     *
     * @param  array{carrera_id: int, nombre: string, modalidad: string, semestres: int, activo?: bool, materias: array<int, array{codigo: string, nombre: string, semestre: int, creditos: int}>}  $datos
     */
    public function actualizar(PlanEstudio $plan, array $datos): PlanEstudio
    {
        return DB::transaction(function () use ($plan, $datos) {
            $plan->update($this->atributos($datos));

            // Las materias se envían completas desde el formulario, así que se reemplazan.
            $plan->materias()->delete();
            $this->registrarMaterias($plan, $datos['materias']);

            return $plan->load('materias');
        });
    }

    public function eliminar(PlanEstudio $plan): void
    {
        // Las materias se eliminan en cascada por la llave foránea.
        $plan->delete();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function atributos(array $datos): array
    {
        return [
            'carrera_id' => $datos['carrera_id'],
            'nombre' => $datos['nombre'],
            'modalidad' => $datos['modalidad'],
            'semestres' => $datos['semestres'],
            'activo' => $datos['activo'] ?? true,
        ];
    }

    /**
     * @param  array<int, array{codigo: string, nombre: string, semestre: int, creditos: int}>  $materias
     */
    private function registrarMaterias(PlanEstudio $plan, array $materias): void
    {
        foreach ($materias as $materia) {
            $plan->materias()->create([
                'codigo' => $materia['codigo'],
                'nombre' => $materia['nombre'],
                'semestre' => $materia['semestre'],
                'creditos' => $materia['creditos'],
            ]);
        }
    }
}
