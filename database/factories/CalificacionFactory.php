<?php

namespace Database\Factories;

use App\Models\Calificacion;
use App\Models\Inscripcion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Calificacion>
 */
class CalificacionFactory extends Factory
{
    protected $model = Calificacion::class;

    public function definition(): array
    {
        return [
            'inscripcion_id' => Inscripcion::factory(),
            'registrado_por' => User::factory(),
            'nombre' => $this->faker->unique()->randomElement([
                'Primer parcial',
                'Segundo parcial',
                'Examen final',
                'Trabajo práctico',
            ]),
            'ponderacion' => 25,
            'nota' => $this->faker->numberBetween(40, 100),
            'fecha' => now()->toDateString(),
        ];
    }
}
