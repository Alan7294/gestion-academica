<?php

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use App\Models\Matricula;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

it('siembra el demo con cursos vinculados a materias e inscripciones válidas', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DemoSeeder::class);

    $materia = Materia::where('codigo', 'SIS-201')->firstOrFail();

    expect(Curso::whereNull('materia_id')->count())->toBe(0)
        ->and(Curso::count())->toBeGreaterThan(0)
        ->and(Inscripcion::count())->toBeGreaterThan(0)
        ->and(User::whereHas('matriculas', fn ($query) => $query->activas(), '>=', 2)->exists())->toBeTrue()
        ->and(Matricula::activas()->count())->toBeGreaterThan(0)
        ->and(Matricula::activas()->whereNull('plan_estudio_id')->count())->toBe(0)
        ->and(Matricula::activas()->whereHas('planEstudio', fn ($query) => $query
            ->whereColumn('planes_estudio.carrera_id', 'carrera_user.carrera_id')
            ->whereColumn('planes_estudio.modalidad', 'carrera_user.modalidad'))->count())
        ->toBe(Matricula::activas()->count())
        ->and($materia->requisitos()->pluck('codigo')->all())->toBe(['SIS-101'])
        ->and(MateriaEquivalencia::whereHas('materia', fn ($query) => $query->where('codigo', 'DER-201'))
            ->whereHas('equivalente', fn ($query) => $query->where('codigo', 'SIS-201'))
            ->exists())->toBeTrue();
});
