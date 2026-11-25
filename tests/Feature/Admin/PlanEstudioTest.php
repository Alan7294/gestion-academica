<?php

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\PlanEstudio;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * Crea en el catálogo las materias de una carrera y devuelve los datos que espera el plan.
 *
 * @param  array<int, array{codigo: string, nombre: string, semestre: int, creditos: int, requisitos?: array<int, string|int>}>  $definiciones
 * @return array<int, array{id: int, semestre: int, creditos: int, requisitos?: array<int, int|string>}>
 */
function catalogoParaPlan(Carrera $carrera, array $definiciones): array
{
    $porCodigo = [];

    foreach ($definiciones as $definicion) {
        $porCodigo[$definicion['codigo']] = Materia::firstOrCreate(
            [
                'carrera_id' => $carrera->id,
                'codigo' => $definicion['codigo'],
            ],
            ['nombre' => $definicion['nombre']],
        );
    }

    return collect($definiciones)->map(function (array $definicion) use ($porCodigo) {
        $fila = [
            'id' => $porCodigo[$definicion['codigo']]->id,
            'semestre' => $definicion['semestre'],
            'creditos' => $definicion['creditos'],
        ];

        if (array_key_exists('requisitos', $definicion)) {
            $fila['requisitos'] = collect($definicion['requisitos'])
                ->map(fn ($codigo) => $porCodigo[$codigo]->id ?? $codigo)
                ->all();
        }

        return $fila;
    })->all();
}

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
        'materias' => catalogoParaPlan($carrera, [
            ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
            ['codigo' => 'PRO-101', 'nombre' => 'Programación I', 'semestre' => 1, 'creditos' => 5],
            ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4],
        ]),
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
        ->and($plan->materias()->pluck('materias.codigo')->all())
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

    materiaEnPlan($plan, ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4]);
    materiaEnPlan($plan, ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4]);

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
            'materias' => catalogoParaPlan($carrera, [
                ['codigo' => 'MAT-301', 'nombre' => 'Cálculo III', 'semestre' => 3, 'creditos' => 4],
            ]),
        ]))
        ->assertSessionHasErrors('materias.0.semestre');

    expect(PlanEstudio::count())->toBe(0);
});

it('rechaza una materia repetida dentro del plan', function () {
    $carrera = Carrera::factory()->create();
    $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'materias' => [
                ['id' => $materia->id, 'semestre' => 1, 'creditos' => 4],
                ['id' => $materia->id, 'semestre' => 1, 'creditos' => 4],
            ],
        ]))
        ->assertSessionHasErrors('materias.1.id');
});

it('rechaza materias de otra carrera', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $materia = Materia::factory()->create(['carrera_id' => $otraCarrera->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'materias' => [['id' => $materia->id, 'semestre' => 1, 'creditos' => 4]],
        ]))
        ->assertSessionHasErrors('materias.0.id');

    expect(PlanEstudio::count())->toBe(0);
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

it('actualiza el plan y reemplaza sus materias sin borrar las del catálogo', function () {
    $carrera = Carrera::factory()->create();

    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan anterior',
        'semestres' => 2,
    ]);

    $anterior = materiaEnPlan($plan, [
        'codigo' => 'OLD-101', 'nombre' => 'Materia anterior', 'semestre' => 1, 'creditos' => 3,
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
        ->and($plan->materias()->where('materias.id', $anterior->id)->exists())->toBeFalse()
        ->and(Materia::find($anterior->id))->not->toBeNull();
});

it('permite cambiar la carrera de un plan sin matrículas ni cursos asociados', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($otraCarrera, [
            'nombre' => 'Plan trasladado',
        ]))
        ->assertRedirect("/admin/planes/{$plan->id}");

    expect($plan->fresh()->carrera_id)->toBe($otraCarrera->id);
});

it('no permite cambiar la carrera de un plan con matrículas asociadas', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral,
    ]);
    Matricula::factory()->create([
        'carrera_id' => $carrera->id,
        'plan_estudio_id' => $plan->id,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($otraCarrera, [
            'nombre' => 'Plan trasladado',
        ]))
        ->assertSessionHasErrors(['carrera_id' => 'No puedes cambiar la carrera de un plan que ya tiene matrículas o cursos asociados. Crea un plan nuevo y reasigna las matrículas.']);

    expect($plan->fresh()->carrera_id)->toBe($carrera->id);
});

it('no permite cambiar la modalidad de un plan con cursos asociados', function () {
    $carrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral,
    ]);
    $materia = materiaEnPlan($plan, [
        'codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4,
    ]);
    Curso::factory()->conMateria($materia)->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($carrera, [
            'nombre' => 'Plan editado',
            'modalidad' => Modalidad::Modular->value,
            'materias' => [['id' => $materia->id, 'semestre' => 1, 'creditos' => 4]],
        ]))
        ->assertSessionHasErrors(['modalidad' => 'No puedes cambiar la modalidad de un plan que ya tiene matrículas o cursos asociados. Crea un plan nuevo y reasigna las matrículas.']);

    expect($plan->fresh()->modalidad)->toBe(Modalidad::Semestral);
});

it('elimina el plan y sus vínculos sin borrar las materias del catálogo', function () {
    $plan = PlanEstudio::factory()->create();
    $materia = materiaEnPlan($plan, [
        'codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/planes/{$plan->id}")
        ->assertRedirect('/admin/planes');

    expect(PlanEstudio::find($plan->id))->toBeNull()
        ->and(Materia::find($materia->id))->not->toBeNull();

    $this->assertDatabaseMissing('materia_plan_estudio', ['plan_estudio_id' => $plan->id]);
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
            'materias' => catalogoParaPlan($carrera, [
                ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
                ['codigo' => 'PRO-101', 'nombre' => 'Programación I', 'semestre' => 1, 'creditos' => 5],
                ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4,
                    'requisitos' => ['MAT-101', 'PRO-101']],
            ]),
        ]))
        ->assertRedirect();

    $plan = PlanEstudio::firstOrFail();
    $materia = $plan->materias()->where('materias.codigo', 'BD-201')->firstOrFail();

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
            'materias' => catalogoParaPlan($carrera, [
                ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4],
                ['codigo' => 'PAR-201', 'nombre' => 'Paralelo', 'semestre' => 2, 'creditos' => 4],
                ['codigo' => 'NIV-301', 'nombre' => 'Nivel III', 'semestre' => 3, 'creditos' => 4],
                ['codigo' => 'BD-201', 'nombre' => 'Base de Datos', 'semestre' => 2, 'creditos' => 4,
                    'requisitos' => $requisitos],
            ]),
        ]))
        ->assertSessionHasErrors(['materias.3.requisitos' => $mensaje]);

    expect(PlanEstudio::count())->toBe(0);
})->with([
    'código inexistente' => [[999999], "La materia '999999' no existe en este plan."],
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

    $previa = materiaEnPlan($plan, ['codigo' => 'PRE-101', 'nombre' => 'Previo', 'semestre' => 1, 'creditos' => 4]);
    $posterior = materiaEnPlan($plan, ['codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2, 'creditos' => 4]);
    $otra = materiaEnPlan($plan, ['codigo' => 'OTR-101', 'nombre' => 'Otra', 'semestre' => 1, 'creditos' => 4]);
    $posterior->requisitosDelPlan($plan)->attach($previa);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($carrera, [
            'nombre' => 'Plan actualizado',
            'materias' => [
                ['id' => $previa->id, 'semestre' => 1, 'creditos' => 4],
                ['id' => $otra->id, 'semestre' => 1, 'creditos' => 4],
                ['id' => $posterior->id, 'semestre' => 2, 'creditos' => 4, 'requisitos' => [$otra->id]],
            ],
        ]))
        ->assertRedirect("/admin/planes/{$plan->id}");

    expect($plan->materias()->pluck('materias.id')->sort()->values()->all())
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

    materiaEnPlan($plan, ['codigo' => 'PRE-101', 'nombre' => 'Previo', 'semestre' => 1, 'creditos' => 4]);
    $posterior = materiaEnPlan($plan, ['codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2, 'creditos' => 4]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", datosPlanEstudio($carrera, [
            'materias' => [
                ['id' => $posterior->id, 'semestre' => 2, 'creditos' => 4],
            ],
        ]))
        ->assertRedirect();

    expect($plan->materias()->count())->toBe(1)
        ->and($posterior->fresh()->requisitos()->count())->toBe(0);

    $this->assertDatabaseMissing('materia_requisitos', ['materia_id' => $posterior->id]);
});

it('permite quitar una materia desde el formulario del plan', function () {
    $plan = PlanEstudio::factory()->create();

    materiaEnPlan($plan, ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/planes/{$plan->id}/edit")
        ->assertOk()
        ->assertSee('quitarMateria(indice)')
        ->assertSee('Quitar del plan');
});

it('recupera el código y el nombre de las materias cuando falla la validación', function () {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, ['semestres' => 1]))
        ->assertSessionHasErrors('materias.2.semestre');

    $calculo = Materia::where('codigo', 'MAT-101')->firstOrFail();

    // El formulario solo envía los ids, así que el código y el nombre deben
    // volver a tomarse del catálogo al rehidratar las materias enviadas.
    // @js escapa las comillas del JSON como \u0022 dentro del atributo.
    $this->get('/admin/planes/create')
        ->assertOk()
        ->assertSee('\u0022id\u0022:'.$calculo->id.',\u0022codigo\u0022:\u0022MAT-101\u0022,\u0022nombre\u0022:\u0022Cálculo I\u0022');
});

it('respeta que se quitaron todas las materias aunque falle otra validación', function () {
    $carrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create(['carrera_id' => $carrera->id]);

    materiaEnPlan($plan, [
        'codigo' => 'OLD-101', 'nombre' => 'Materia anterior', 'semestre' => 1, 'creditos' => 4,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$plan->id}", [
            'nombre' => '',
            'carrera_id' => $carrera->id,
            'modalidad' => Modalidad::Semestral->value,
            'semestres' => 4,
            'activo' => '1',
        ])
        ->assertSessionHasErrors('nombre');

    $this->get("/admin/planes/{$plan->id}/edit")
        ->assertOk()
        ->assertSee('materias: [],', false);
});

it('mantiene los requisitos separados por plan aunque compartan materias', function () {
    $carrera = Carrera::factory()->create();

    $planSemestral = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan semestral',
        'modalidad' => Modalidad::Semestral,
    ]);
    $planModular = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan modular',
        'modalidad' => Modalidad::Modular,
    ]);

    $previa = Materia::factory()->create(['carrera_id' => $carrera->id, 'codigo' => 'PRE-101', 'nombre' => 'Previa']);
    $otra = Materia::factory()->create(['carrera_id' => $carrera->id, 'codigo' => 'OTR-101', 'nombre' => 'Otra']);
    $posterior = Materia::factory()->create(['carrera_id' => $carrera->id, 'codigo' => 'POS-201', 'nombre' => 'Posterior']);

    foreach ([$planSemestral, $planModular] as $plan) {
        $plan->vincularMateria($previa, 1, 4);
        $plan->vincularMateria($otra, 1, 4);
        $plan->vincularMateria($posterior, 2, 4);
    }

    $posterior->requisitosDelPlan($planSemestral)->attach($previa);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$planModular->id}", [
            'nombre' => 'Plan modular',
            'carrera_id' => $carrera->id,
            'modalidad' => Modalidad::Modular->value,
            'semestres' => 2,
            'activo' => '1',
            'materias' => [
                ['id' => $previa->id, 'semestre' => 1, 'creditos' => 4],
                ['id' => $otra->id, 'semestre' => 1, 'creditos' => 4],
                ['id' => $posterior->id, 'semestre' => 2, 'creditos' => 4, 'requisitos' => [$otra->id]],
            ],
        ])
        ->assertRedirect("/admin/planes/{$planModular->id}");

    expect($posterior->requisitosDelPlan($planSemestral)->pluck('codigo')->all())->toBe(['PRE-101'])
        ->and($posterior->requisitosDelPlan($planModular)->pluck('codigo')->all())->toBe(['OTR-101']);
});

it('elimina los requisitos del plan al borrar el plan', function () {
    $plan = PlanEstudio::factory()->create(['semestres' => 2]);

    $previa = materiaEnPlan($plan, ['codigo' => 'PRE-101', 'nombre' => 'Previa', 'semestre' => 1, 'creditos' => 4]);
    $posterior = materiaEnPlan($plan, ['codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2, 'creditos' => 4]);
    $posterior->requisitosDelPlan($plan)->attach($previa);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/planes/{$plan->id}")
        ->assertRedirect('/admin/planes');

    $this->assertDatabaseMissing('materia_requisitos', ['plan_estudio_id' => $plan->id]);
});

it('rechaza un segundo plan activo para la misma carrera y modalidad', function () {
    $carrera = Carrera::factory()->create();

    PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan vigente',
        'modalidad' => Modalidad::Semestral,
        'activo' => true,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'nombre' => 'Plan nuevo',
            'modalidad' => Modalidad::Semestral->value,
            'activo' => '1',
        ]))
        ->assertSessionHasErrors('activo');

    expect(PlanEstudio::where('nombre', 'Plan nuevo')->exists())->toBeFalse();
});

it('rechaza activar un plan existente si ya hay otro activo para su modalidad', function () {
    $carrera = Carrera::factory()->create();

    PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan vigente',
        'modalidad' => Modalidad::Semestral,
        'activo' => true,
    ]);

    $inactivo = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan anterior',
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/planes/{$inactivo->id}", datosPlanEstudio($carrera, [
            'nombre' => 'Plan anterior',
            'modalidad' => Modalidad::Semestral->value,
            'activo' => '1',
        ]))
        ->assertSessionHasErrors('activo');

    expect($inactivo->fresh()->activo)->toBeFalse();
});

it('permite activar un plan cuando el anterior de la misma modalidad está inactivo', function () {
    $carrera = Carrera::factory()->create();

    PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan anterior',
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'nombre' => 'Plan nuevo',
            'modalidad' => Modalidad::Semestral->value,
            'activo' => '1',
        ]))
        ->assertRedirect();

    expect(PlanEstudio::where('nombre', 'Plan nuevo')->where('activo', true)->exists())->toBeTrue();
});

it('rechaza materias con cero créditos', function () {
    $carrera = Carrera::factory()->create();
    $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/planes', datosPlanEstudio($carrera, [
            'materias' => [
                ['id' => $materia->id, 'semestre' => 1, 'creditos' => 0],
            ],
        ]))
        ->assertSessionHasErrors(['materias.0.creditos' => 'Cada materia debe tener al menos 1 crédito.']);

    expect(PlanEstudio::count())->toBe(0);
});

it('muestra las materias que quedaron fuera de la duración del plan', function () {
    $plan = PlanEstudio::factory()->create(['semestres' => 2]);

    materiaEnPlan($plan, [
        'codigo' => 'FUE-301',
        'nombre' => 'Materia fuera de duración',
        'semestre' => 3,
        'creditos' => 4,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/planes/{$plan->id}")
        ->assertOk()
        ->assertSee('Materias fuera de la duración del plan')
        ->assertSee('FUE-301')
        ->assertSee('Materia fuera de duración');
});

it('filtra los planes por estado activo', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();

    PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan vigente',
        'activo' => true,
    ]);

    PlanEstudio::factory()->create([
        'carrera_id' => $otraCarrera->id,
        'nombre' => 'Plan archivado',
        'activo' => false,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/planes?activo=1')
        ->assertOk()
        ->assertSee('Plan vigente')
        ->assertDontSee('Plan archivado');

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/planes?activo=0')
        ->assertOk()
        ->assertSee('Plan archivado')
        ->assertDontSee('Plan vigente');
});
