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

it('crea un plan con varias materias requisito y las muestra en el detalle', function () {
    $carrera = Carrera::factory()->create();
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'materias' => [
                ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
                ['codigo' => 'PRO-101', 'nombre' => 'Programación I', 'semestre' => 1, 'creditos' => 5],
                ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4,
                    'requisitos' => ['MAT-101', 'PRO-101']],
            ],
        ]))
        ->assertRedirect();

    $plan = PlanEstudio::firstOrFail();
    $materia = $plan->materias()->where('codigo', 'BD-201')->firstOrFail();

    expect($materia->requisitos()->pluck('codigo')->sort()->values()->all())
        ->toBe(['MAT-101', 'PRO-101']);

    $this->actingAs($admin)
        ->get("/admin/planes/{$plan->id}")
        ->assertOk()
        ->assertSee('MAT-101 - Cálculo I')
        ->assertSee('PRO-101 - Programación I');
});

it('rechaza requisitos inválidos en las materias del plan', function (array $requisitos, string $mensaje) {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'materias' => [
                ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
                ['codigo' => 'PAR-201', 'nombre' => 'Paralelo', 'semestre' => 2, 'creditos' => 4],
                ['codigo' => 'NIV-301', 'nombre' => 'Nivel III', 'semestre' => 3, 'creditos' => 4],
                ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4,
                    'requisitos' => $requisitos],
            ],
        ]))
        ->assertSessionHasErrors(['materias.3.requisitos' => $mensaje]);

    expect(PlanEstudio::count())->toBe(0);
})->with([
    'código inexistente' => [['NO-101'], "La materia 'NO-101' no existe en este plan."],
    'autorreferencia' => [['BD-201'], 'Una materia no puede ser requisito de sí misma.'],
    'semestre posterior' => [['NIV-301'], "El requisito 'NIV-301' debe pertenecer a un semestre anterior."],
    'semestre igual' => [['PAR-201'], "El requisito 'PAR-201' debe pertenecer a un semestre anterior."],
    'requisito repetido' => [['MAT-101', 'MAT-101'], 'No repitas una materia en la lista de requisitos.'],
]);

it('actualiza los requisitos al editar el plan sin perder las materias', function () {
    $carrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan anterior',
        'semestres' => 3,
    ]);

    $previa = $plan->materias()->create(['codigo' => 'PRE-101', 'nombre' => 'Previo', 'semestre' => 1, 'creditos' => 4]);
    $posterior = $plan->materias()->create(['codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2, 'creditos' => 4]);
    $otra = $plan->materias()->create(['codigo' => 'OTR-101', 'nombre' => 'Otra', 'semestre' => 1, 'creditos' => 4]);
    $posterior->requisitos()->attach($previa);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($carrera, [
            'nombre' => 'Plan actualizado',
            'materias' => [
                ['id' => $previa->id, 'codigo' => 'PRE-101', 'nombre' => 'Previo', 'semestre' => 1, 'creditos' => 4],
                ['id' => $otra->id, 'codigo' => 'OTR-101', 'nombre' => 'Otra', 'semestre' => 1, 'creditos' => 4],
                ['id' => $posterior->id, 'codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2,
                    'creditos' => 4, 'requisitos' => ['OTR-101']],
            ],
        ]))
        ->assertRedirect("/admin/planes/{$plan->id}");

    expect($plan->materias()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$previa->id, $posterior->id, $otra->id])->sort()->values()->all())
        ->and($posterior->fresh()->requisitos()->pluck('codigo')->all())->toBe(['OTR-101']);

    $this->assertDatabaseMissing('materia_requisitos', [
        'materia_id' => $posterior->id,
        'requisito_id' => $previa->id,
    ]);
});

it('elimina los vínculos de requisitos al quitar una materia del plan', function () {
    $carrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create(['carrera_id' => $carrera->id, 'semestres' => 2]);

    $previa = $plan->materias()->create(['codigo' => 'PRE-101', 'nombre' => 'Previo', 'semestre' => 1, 'creditos' => 4]);
    $posterior = $plan->materias()->create(['codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2, 'creditos' => 4]);
    $posterior->requisitos()->attach($previa);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($carrera, [
            'materias' => [
                ['id' => $posterior->id, 'codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2, 'creditos' => 4],
            ],
        ]))
        ->assertRedirect();

    expect($plan->materias()->count())->toBe(1)
        ->and($posterior->fresh()->requisitos()->count())->toBe(0);

    $this->assertDatabaseMissing('materia_requisitos', ['materia_id' => $posterior->id]);
});
