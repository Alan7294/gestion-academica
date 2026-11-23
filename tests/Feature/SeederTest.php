<?php

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Materia;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

it('siembra el demo con cursos vinculados a materias e inscripciones válidas', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DemoSeeder::class);

    $materia = Materia::where('codigo', 'SIS-201')->firstOrFail();

    expect(Curso::whereNull('materia_id')->count())->toBe(0)
        ->and(Curso::count())->toBeGreaterThan(0)
        ->and(Inscripcion::count())->toBeGreaterThan(0)
        ->and($materia->requisitos()->pluck('codigo')->all())->toBe(['SIS-101']);
});
