<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrera>
 */
class CarreraFactory extends Factory
{
    protected $model = Carrera::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper($this->faker->unique()->bothify('???')),
            'nombre' => 'Carrera de '.$this->faker->words(2, true),
            'facultad_id' => Facultad::factory(),
            'activo' => true,
        ];
    }
}
