<?php

namespace Database\Factories;

use App\Models\CertificadoDocente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificadoDocente>
 */
class CertificadoDocenteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'titulo' => fake()->sentence(3),
            'institucion' => fake()->company(),
            'codigo' => strtoupper(fake()->bothify('CERT-####??')),
        ];
    }
}
