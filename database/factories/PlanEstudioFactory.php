<?php

namespace Database\Factories;

use App\Enums\Modalidad;
use App\Models\Carrera;
use App\Models\PlanEstudio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanEstudio>
 */
class PlanEstudioFactory extends Factory
{
    protected $model = PlanEstudio::class;

    public function definition(): array
    {
        return [
            'carrera_id' => Carrera::factory(),
            'nombre' => 'Plan de estudios '.$this->faker->year(),
            'modalidad' => $this->faker->randomElement(Modalidad::cases()),
            'semestres' => $this->faker->numberBetween(2, 10),
            'activo' => true,
        ];
    }
}
