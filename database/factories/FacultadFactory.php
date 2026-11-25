<?php

namespace Database\Factories;

use App\Models\Facultad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facultad>
 */
class FacultadFactory extends Factory
{
    protected $model = Facultad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Facultad de '.$this->faker->unique()->word(),
            'activo' => true,
        ];
    }
}
