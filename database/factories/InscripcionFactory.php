<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscripcion>
 */
class InscripcionFactory extends Factory
{
    protected $model = Inscripcion::class;

    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'user_id' => User::factory(),
            'plan_estudio_id' => null,
            'estado' => 'inscrito',
            'inscrito_en' => now(),
            'nota_final' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Inscripcion $inscripcion): void {
            if ($inscripcion->plan_estudio_id === null) {
                $inscripcion->update([
                    'plan_estudio_id' => $inscripcion->curso->plan_estudio_id,
                ]);
            }
        });
    }

    public function retirada(): static
    {
        return $this->state(fn () => ['estado' => 'retirado']);
    }
}
