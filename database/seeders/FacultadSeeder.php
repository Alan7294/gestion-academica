<?php

namespace Database\Seeders;

use App\Models\Facultad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacultadSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $nombres = [
            'Facultad de Ingeniería',
            'Facultad de Ciencias Económicas',
            'Facultad de Ciencias Jurídicas',
        ];

        foreach ($nombres as $nombre) {
            Facultad::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
