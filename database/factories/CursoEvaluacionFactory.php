<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\CursoEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CursoEvaluacion>
 */
class CursoEvaluacionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'nombre' => $this->faker->unique()->randomElement([
                'Primer parcial',
                'Segundo parcial',
                'Examen final',
                'Trabajo práctico',
                'Exposición',
            ]),
            'ponderacion' => 25,
        ];
    }
}
