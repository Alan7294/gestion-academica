<?php

namespace Database\Seeders;

use App\Enums\Modalidad;
use App\Models\Carrera;
use App\Models\PlanEstudio;
use Illuminate\Database\Seeder;

class PlanEstudioSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            'SIS' => [
                'nombre' => 'Plan de estudios 2026',
                'modalidad' => Modalidad::Semestral,
                'semestres' => 4,
                'materias' => [
                    ['codigo' => 'SIS-101', 'nombre' => 'Programación I', 'semestre' => 1, 'creditos' => 5],
                    ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
                    ['codigo' => 'SIS-201', 'nombre' => 'Estructura de Datos', 'semestre' => 2, 'creditos' => 5],
                    ['codigo' => 'BD-201', 'nombre' => 'Base de Datos I', 'semestre' => 2, 'creditos' => 4],
                    ['codigo' => 'BD-301', 'nombre' => 'Base de Datos II', 'semestre' => 3, 'creditos' => 4],
                    ['codigo' => 'SIS-302', 'nombre' => 'Redes I', 'semestre' => 3, 'creditos' => 4],
                    ['codigo' => 'SIS-401', 'nombre' => 'Ingeniería de Software', 'semestre' => 4, 'creditos' => 5],
                    ['codigo' => 'SIS-402', 'nombre' => 'Sistemas Operativos', 'semestre' => 4, 'creditos' => 4],
                ],
            ],
            'ADM' => [
                'nombre' => 'Plan de estudios 2026',
                'modalidad' => Modalidad::Modular,
                'semestres' => 4,
                'materias' => [
                    ['codigo' => 'ADM-101', 'nombre' => 'Contabilidad General', 'semestre' => 1, 'creditos' => 4],
                    ['codigo' => 'ECO-101', 'nombre' => 'Economía I', 'semestre' => 1, 'creditos' => 4],
                    ['codigo' => 'ADM-201', 'nombre' => 'Administración I', 'semestre' => 2, 'creditos' => 4],
                    ['codigo' => 'EST-101', 'nombre' => 'Estadística I', 'semestre' => 2, 'creditos' => 4],
                    ['codigo' => 'ADM-301', 'nombre' => 'Marketing', 'semestre' => 3, 'creditos' => 4],
                    ['codigo' => 'ADM-302', 'nombre' => 'Finanzas I', 'semestre' => 3, 'creditos' => 4],
                    ['codigo' => 'ADM-401', 'nombre' => 'Gestión del Talento Humano', 'semestre' => 4, 'creditos' => 4],
                    ['codigo' => 'ADM-402', 'nombre' => 'Derecho Empresarial', 'semestre' => 4, 'creditos' => 3],
                ],
            ],
            'DER' => [
                'nombre' => 'Plan de estudios 2026',
                'modalidad' => Modalidad::Semestral,
                'semestres' => 5,
                'materias' => [
                    ['codigo' => 'DER-101', 'nombre' => 'Derecho Civil I', 'semestre' => 1, 'creditos' => 5],
                    ['codigo' => 'DER-102', 'nombre' => 'Introducción al Derecho', 'semestre' => 1, 'creditos' => 4],
                    ['codigo' => 'DER-201', 'nombre' => 'Derecho Penal I', 'semestre' => 2, 'creditos' => 4],
                    ['codigo' => 'DER-202', 'nombre' => 'Derecho Constitucional', 'semestre' => 2, 'creditos' => 4],
                    ['codigo' => 'DER-301', 'nombre' => 'Derecho Civil II', 'semestre' => 3, 'creditos' => 5],
                    ['codigo' => 'DER-302', 'nombre' => 'Derecho Administrativo', 'semestre' => 3, 'creditos' => 4],
                    ['codigo' => 'DER-401', 'nombre' => 'Derecho Penal II', 'semestre' => 4, 'creditos' => 4],
                    ['codigo' => 'DER-402', 'nombre' => 'Derecho Laboral', 'semestre' => 4, 'creditos' => 4],
                    ['codigo' => 'DER-501', 'nombre' => 'Derecho Procesal Civil', 'semestre' => 5, 'creditos' => 5],
                    ['codigo' => 'DER-502', 'nombre' => 'Derecho Comercial', 'semestre' => 5, 'creditos' => 4],
                ],
            ],
        ];

        foreach ($planes as $codigo => $definicion) {
            $carrera = Carrera::where('codigo', $codigo)->first();

            if (! $carrera) {
                continue;
            }

            $plan = PlanEstudio::updateOrCreate(
                ['carrera_id' => $carrera->id, 'nombre' => $definicion['nombre']],
                [
                    'modalidad' => $definicion['modalidad']->value,
                    'semestres' => $definicion['semestres'],
                    'activo' => true,
                ],
            );

            foreach ($definicion['materias'] as $materia) {
                $plan->materias()->updateOrCreate(
                    ['codigo' => $materia['codigo']],
                    $materia,
                );
            }
        }
    }
}
