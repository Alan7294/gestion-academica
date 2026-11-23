<?php

namespace Database\Factories;

use App\Models\Materia;
use App\Models\PlanEstudio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materia>
 */
class MateriaFactory extends Factory
{
    protected $model = Materia::class;

    public function definition(): array
    {
        return [
            'plan_estudio_id' => PlanEstudio::factory(),
            'codigo' => strtoupper($this->faker->unique()->bothify('???-###')),
            'nombre' => 'Materia de '.$this->faker->words(2, true),
            'semestre' => 1,
            'creditos' => $this->faker->numberBetween(1, 6),
        ];
    }
}
