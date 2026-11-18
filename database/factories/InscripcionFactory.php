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
            'estado' => 'inscrito',
            'inscrito_en' => now(),
            'nota_final' => null,
        ];
    }

    public function retirada(): static
    {
        return $this->state(fn () => ['estado' => 'retirado']);
    }
}
