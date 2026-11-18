<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = [
            ['codigo' => 'SIS', 'nombre' => 'Ingeniería de Sistemas', 'facultad' => 'Facultad de Ingeniería'],
            ['codigo' => 'ADM', 'nombre' => 'Administración de Empresas', 'facultad' => 'Facultad de Ciencias Económicas'],
            ['codigo' => 'DER', 'nombre' => 'Derecho', 'facultad' => 'Facultad de Ciencias Jurídicas'],
        ];

        foreach ($carreras as $carrera) {
            Carrera::firstOrCreate(['codigo' => $carrera['codigo']], $carrera + ['activo' => true]);
        }
    }
}
