<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use App\Models\PlanEstudio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MateriaEquivalencia>
 */
class MateriaEquivalenciaFactory extends Factory
{
    protected $model = MateriaEquivalencia::class;

    public function definition(): array
    {
        $planDestino = PlanEstudio::factory()->create(['carrera_id' => Carrera::factory()]);
        $planOrigen = PlanEstudio::factory()->create(['carrera_id' => Carrera::factory()]);

        $destino = Materia::factory()->create();
        $origen = Materia::factory()->create();

        $planDestino->vincularMateria($destino, 1);
        $planOrigen->vincularMateria($origen, 1);

        return [
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
        ];
    }
}
