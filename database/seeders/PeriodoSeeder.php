<?php

namespace Database\Seeders;

use App\Models\Periodo;
use Illuminate\Database\Seeder;

class PeriodoSeeder extends Seeder
{
    public function run(): void
    {
        Periodo::firstOrCreate(
            ['nombre' => '2026-II'],
            [
                'fecha_inicio' => today()->toDateString(),
                'fecha_fin' => today()->addMonths(4)->toDateString(),
                'activo' => true,
            ],
        );

        Periodo::firstOrCreate(
            ['nombre' => '2026-I'],
            [
                'fecha_inicio' => '2026-02-02',
                'fecha_fin' => '2026-06-26',
                'activo' => false,
            ],
        );
    }
}
