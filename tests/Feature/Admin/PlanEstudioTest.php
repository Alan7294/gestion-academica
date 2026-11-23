<?php

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\PlanEstudio;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * Datos válidos para crear un plan de estudios.
 *
 * @return array<string, mixed>
 */
function datosPlanEstudio(Carrera $carrera, array $overrides = []): array
{
    return array_merge([
        'nombre' => 'Plan de estudios 2026',
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral->value,
        'semestres' => 4,
        'activo' => '1',
        'materias' => [
            ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
            ['codigo' => 'PRO-101', 'nombre' => 'Programación I', 'semestre' => 1, 'creditos' => 5],
            ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4],
        ],
    ], $overrides);
}

it('permite al administrador crear un plan de estudios con sus materias', function () {
    $carrera = Carrera::factory()->create();

    $response = $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera));

    $plan = PlanEstudio::where('nombre', 'Plan de estudios 2026')->firstOrFail();

    $response->assertRedirect("/admin/planes/{$plan->id}");

    expect($plan->carrera_id)->toBe($carrera->id)
        ->and($plan->modalidad)->toBe(Modalidad::Semestral)
        ->and($plan->semestres)->toBe(4)
        ->and($plan->activo)->toBeTrue()
        ->and($plan->materias()->count())->toBe(3)
        ->and($plan->materias()->pluck('codigo')->all())
        ->toBe(['MAT-101', 'PRO-101', 'BD-201']);
});

it('lista y filtra los planes de estudio', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();

    PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan Modular',
        'modalidad' => Modalidad::Modular,
    ]);

    PlanEstudio::factory()->create([
        'carrera_id' => $otraCarrera->id,
        'nombre' => 'Plan Semestral',
        'modalidad' => Modalidad::Semestral,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/planes?modalidad=modular')
        ->assertOk()
        ->assertSee('Plan Modular')
        ->assertDontSee('Plan Semestral');
});

it('muestra el detalle del plan agrupado por semestre', function () {
    $plan = PlanEstudio::factory()->create(['semestres' => 3]);

    $plan->materias()->createMany([
        ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
        ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4],
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/planes/{$plan->id}")
        ->assertOk()
        ->assertSee('Cálculo I')
        ->assertSee('Base de Datos')
        ->assertSee('Semestre 2')
        ->assertSee('Sin materias registradas en este semestre.');
});

it('exige al menos una materia en el plan', function () {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, ['materias' => []]))
        ->assertSessionHasErrors('materias');

    expect(PlanEstudio::count())->toBe(0);
});

it('rechaza una materia ubicada fuera de la duración del plan', function () {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'semestres' => 2,
            'materias' => [
                ['codigo' => 'MAT-301', 'nombre' => 'Cálculo III', 'semestre' => 3, 'creditos' => 4],
            ],
        ]))
        ->assertSessionHasErrors('materias.0.semestre');

    expect(PlanEstudio::count())->toBe(0);
});

it('rechaza códigos de materia repetidos dentro del plan', function () {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'materias' => [
                ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
                ['codigo' => 'MAT-101', 'nombre' => 'Álgebra', 'semestre' => 1, 'creditos' => 4],
            ],
        ]))
        ->assertSessionHasErrors('materias.1.codigo');
});

it('rechaza un plan con el mismo nombre dentro de la carrera', function () {
    $carrera = Carrera::factory()->create();

    PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan de estudios 2026',
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera))
        ->assertSessionHasErrors('nombre');
});

it('actualiza el plan y reemplaza sus materias', function () {
    $carrera = Carrera::factory()->create();

    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan anterior',
        'semestres' => 2,
    ]);

    $plan->materias()->create([
        'codigo' => 'OLD-101',
        'nombre' => 'Materia anterior',
        'semestre' => 1,
        'creditos' => 3,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($carrera, [
            'nombre' => 'Plan actualizado',
            'modalidad' => Modalidad::Modular->value,
        ]))
        ->assertRedirect("/admin/planes/{$plan->id}");

    $plan->refresh();

    expect($plan->nombre)->toBe('Plan actualizado')
        ->and($plan->modalidad)->toBe(Modalidad::Modular)
        ->and($plan->materias()->count())->toBe(3)
        ->and($plan->materias()->where('codigo', 'OLD-101')->exists())->toBeFalse();
});

it('elimina el plan de estudios y sus materias', function () {
    $plan = PlanEstudio::factory()->create();

    $plan->materias()->create([
        'codigo' => 'MAT-101',
        'nombre' => 'Cálculo I',
        'semestre' => 1,
        'creditos' => 4,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/planes/{$plan->id}")
        ->assertRedirect('/admin/planes');

    expect(PlanEstudio::find($plan->id))->toBeNull();
    $this->assertDatabaseMissing('materias', ['plan_estudio_id' => $plan->id]);
});

it('impide que un docente gestione planes de estudio', function () {
    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get('/admin/planes')
        ->assertForbidden();
});
