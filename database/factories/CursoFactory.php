<?php

namespace Database\Factories;

use App\Enums\Modalidad;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return [
            'carrera_id' => Carrera::factory(),
            'modalidad' => Modalidad::Semestral,
            'materia_id' => null,
            'periodo_id' => Periodo::factory(),
            'docente_id' => null,
            'nombre' => ucfirst($this->faker->words(3, true)),
            'creditos' => $this->faker->numberBetween(2, 6),
            'semestre' => $this->faker->numberBetween(1, 8),
            'cupo' => $this->faker->numberBetween(20, 60),
            'activo' => true,
        ];
    }

    public function conMateria(Materia $materia): static
    {
        return $this->state(fn (array $atributos) => [
            'carrera_id' => $materia->carrera_id,
            'materia_id' => $materia->id,
        ]);
    }
}
