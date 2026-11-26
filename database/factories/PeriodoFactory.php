<?php

namespace Database\Factories;

use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Periodo>
 */
class PeriodoFactory extends Factory
{
    protected $model = Periodo::class;

    public function definition(): array
    {
        $inicio = $this->faker->dateTimeBetween('-1 year', 'now');
        $fin = (clone $inicio)->modify('+4 months');

        return [
            'nombre' => $this->faker->unique()->numerify('20##-#'),
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin->format('Y-m-d'),
            'activo' => false,
        ];
    }

    public function vigente(): static
    {
        return $this->state(fn () => [
            'activo' => true,
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->addMonths(4)->toDateString(),
        ]);
    }
}
