<?php

namespace Database\Factories;

use App\Models\Calificacion;
use App\Models\CursoEvaluacion;
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
            'curso_evaluacion_id' => function (array $attributes): int {
                $inscripcion = Inscripcion::findOrFail($attributes['inscripcion_id']);

                return CursoEvaluacion::factory()
                    ->create(['curso_id' => $inscripcion->curso_id])
                    ->id;
            },
            'registrado_por' => User::factory(),
            'nota' => $this->faker->numberBetween(40, 100),
            'fecha' => now()->toDateString(),
        ];
    }
}
