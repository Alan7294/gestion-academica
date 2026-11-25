<?php

namespace Database\Factories;

use App\Enums\Modalidad;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\PlanEstudio;
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

    public function configure(): static
    {
        return $this->afterCreating(function (Curso $curso): void {
            if ($curso->plan_estudio_id === null) {
                $plan = PlanEstudio::factory()->create(['carrera_id' => $curso->carrera_id]);

                if ($curso->materia_id !== null) {
                    $plan->vincularMateria(
                        Materia::findOrFail($curso->materia_id),
                        $curso->semestre,
                        $curso->creditos,
                    );
                }

                $curso->update(['plan_estudio_id' => $plan->id]);
            }

            $curso->planesEstudio()->syncWithoutDetaching([$curso->plan_estudio_id]);
        });
    }

    public function conMateria(Materia $materia): static
    {
        $carreraId = $materia->planesEstudio()->first()
            ?->carrera_id
            ?? Carrera::factory()->create()->id;
        $planId = $materia->planesEstudio()->first()?->id;

        return $this->state(fn () => [
            'carrera_id' => $carreraId,
            'plan_estudio_id' => $planId,
            'materia_id' => $materia->id,
            'nombre' => $materia->nombre,
        ]);
    }
}
