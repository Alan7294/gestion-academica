<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Horario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horario>
 */
class HorarioFactory extends Factory
{
    protected $model = Horario::class;

    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'docente_id' => User::factory(),
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'aula' => 'A-101',
        ];
    }
}
