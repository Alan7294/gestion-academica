<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Materia;
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
            'carrera_id' => Carrera::factory(),
            'codigo' => strtoupper($this->faker->unique()->bothify('???-###')),
            'nombre' => 'Materia de '.$this->faker->words(2, true),
        ];
    }
}
