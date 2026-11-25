<?php

namespace Database\Factories;

use App\Enums\EstadoMatricula;
use App\Enums\Modalidad;
use App\Models\Carrera;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Matricula>
 */
class MatriculaFactory extends Factory
{
    protected $model = Matricula::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'carrera_id' => Carrera::factory(),
            'plan_estudio_id' => null,
            'estado' => EstadoMatricula::Activa,
            'es_principal' => false,
            'modalidad' => Modalidad::Semestral,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
        ];
    }

    public function principal(): static
    {
        return $this->state(fn () => ['es_principal' => true]);
    }

    public function baja(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoMatricula::Baja,
            'es_principal' => false,
            'fecha_fin' => now()->toDateString(),
        ]);
    }
}
