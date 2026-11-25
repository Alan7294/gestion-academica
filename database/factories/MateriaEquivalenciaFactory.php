<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MateriaEquivalencia>
 */
class MateriaEquivalenciaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $carreraDestino = Carrera::factory();
        $carreraOrigen = Carrera::factory();

        return [
            'materia_id' => Materia::factory()->for($carreraDestino),
            'equivalente_id' => Materia::factory()->for($carreraOrigen),
        ];
    }
}
