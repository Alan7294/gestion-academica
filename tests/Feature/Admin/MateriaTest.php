<?php

use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\PlanEstudio;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

function datosMateria(array $sobrescribir = []): array
{
    $planId = $sobrescribir['plan_estudio_id'] ?? PlanEstudio::factory()->create()->id;

    return array_merge([
        'codigo' => 'MAT-101',
        'nombre' => 'Cálculo I',
        'plan_estudio_id' => $planId,
        'semestre' => 1,
        'creditos' => 4,
    ], $sobrescribir);
}

it('permite al administrador crear una materia sin elegir carrera', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', datosMateria())
        ->assertRedirect('/admin/materias');

    $this->assertDatabaseHas('materias', [
        'codigo' => 'MAT-101',
        'nombre' => 'Cálculo I',
    ]);
});

it('crea una materia vía AJAX para usarla dentro del formulario de planes', function () {
    $respuesta = $this->actingAs(usuarioConRol(Rol::Administrador))
        ->postJson('/admin/materias', [
            'codigo' => 'PRO-101',
            'nombre' => 'Programación I',
        ])
        ->assertCreated()
        ->assertJsonFragment([
            'codigo' => 'PRO-101',
            'nombre' => 'Programación I',
        ]);

    expect(Materia::find($respuesta->json('id')))->not->toBeNull();
});

it('rechaza códigos repetidos en el catálogo', function () {
    Materia::factory()->create(['codigo' => 'MAT-101']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', datosMateria([
            'codigo' => 'MAT-101',
            'nombre' => 'Cálculo II',
        ]))
        ->assertSessionHasErrors('codigo');

    expect(Materia::where('codigo', 'MAT-101')->count())->toBe(1);
});

it('rechaza el mismo código aunque se envíe una carrera distinta', function () {
    $otraCarrera = Carrera::factory()->create();
    Materia::factory()->create(['codigo' => 'GEN-101']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', datosMateria([
            'carrera_id' => $otraCarrera->id,
            'codigo' => 'GEN-101',
            'nombre' => 'Materia general',
        ]))
        ->assertSessionHasErrors('codigo');

    expect(Materia::where('codigo', 'GEN-101')->count())->toBe(1);
});

it('vincula la materia al plan con el semestre y los créditos elegidos al crearla', function () {
    $plan = PlanEstudio::factory()->create(['semestres' => 6]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', datosMateria([
            'plan_estudio_id' => $plan->id,
            'codigo' => 'DER-301',
            'nombre' => 'Derecho Procesal',
            'semestre' => 3,
            'creditos' => 5,
        ]))
        ->assertRedirect('/admin/materias');

    $materia = Materia::where('codigo', 'DER-301')->firstOrFail();
    $vinculo = $plan->fresh()->materias->firstOrFail();

    expect($vinculo->id)->toBe($materia->id)
        ->and($vinculo->pivot->semestre)->toBe(3)
        ->and($vinculo->pivot->creditos)->toBe(5);
});

it('exige plan, semestre y créditos al crear la materia desde el formulario', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', [
            'codigo' => 'MAT-102',
            'nombre' => 'Cálculo II',
        ])
        ->assertSessionHasErrors(['plan_estudio_id', 'semestre', 'creditos']);

    expect(Materia::where('codigo', 'MAT-102')->exists())->toBeFalse();
});

it('rechaza un semestre mayor que la duración del plan al crear la materia', function () {
    $plan = PlanEstudio::factory()->create(['semestres' => 2]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', datosMateria([
            'plan_estudio_id' => $plan->id,
            'codigo' => 'MAT-501',
            'semestre' => 5,
        ]))
        ->assertSessionHasErrors([
            'semestre' => 'El semestre no puede superar la duración del plan (2 semestres).',
        ]);

    expect(Materia::where('codigo', 'MAT-501')->exists())->toBeFalse();
});

it('filtra el catálogo de materias por el semestre asignado en los planes', function () {
    $plan = PlanEstudio::factory()->create(['semestres' => 6]);
    materiaEnPlan($plan, [
        'codigo' => 'SEM-301',
        'nombre' => 'Materia de tercero',
        'semestre' => 3,
        'creditos' => 4,
    ]);
    materiaEnPlan($plan, [
        'codigo' => 'SEM-101',
        'nombre' => 'Materia de primero',
        'semestre' => 1,
        'creditos' => 4,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/materias?semestre=3')
        ->assertOk()
        ->assertSee('SEM-301')
        ->assertDontSee('SEM-101');

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/materias?semestre=2')
        ->assertOk()
        ->assertDontSee('SEM-301')
        ->assertDontSee('SEM-101');
});

it('aplica carrera y semestre al mismo plan al filtrar las materias', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create(['carrera_id' => $carrera->id, 'semestres' => 6]);
    $otroPlan = PlanEstudio::factory()->create(['carrera_id' => $otraCarrera->id, 'semestres' => 6]);
    $materia = Materia::factory()->create(['codigo' => 'COM-201', 'nombre' => 'Materia compartida']);
    $plan->vincularMateria($materia, 1, 4);
    $otroPlan->vincularMateria($materia, 3, 4);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/materias?carrera_id={$carrera->id}&semestre=3")
        ->assertOk()
        ->assertDontSee('COM-201');

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/materias?carrera_id={$carrera->id}&semestre=1")
        ->assertOk()
        ->assertSee('COM-201');
});

it('permite editar el catálogo sin alterar los planes que usan la materia', function () {
    $plan = PlanEstudio::factory()->create();
    $materia = materiaEnPlan($plan, [
        'codigo' => 'ORI-101',
        'nombre' => 'Materia original',
        'creditos' => 4,
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/materias/{$materia->id}", [
            'codigo' => 'ACT-201',
            'nombre' => 'Materia actualizada',
        ])
        ->assertRedirect('/admin/materias');

    expect($materia->fresh()->codigo)->toBe('ACT-201')
        ->and($materia->fresh()->nombre)->toBe('Materia actualizada')
        ->and($plan->fresh()->materias->pluck('id')->all())->toBe([$materia->id])
        ->and($plan->fresh()->materias->first()->pivot->creditos)->toBe(4);
});

it('impide eliminar una materia que tiene cursos registrados', function () {
    $materia = Materia::factory()->create();
    Curso::factory()->create(['materia_id' => $materia->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/materias/{$materia->id}")
        ->assertSessionHasErrors('materia');

    expect(Materia::find($materia->id))->not->toBeNull();
});

it('elimina una materia sin cursos y limpia sus vínculos con planes y requisitos', function () {
    $carrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create(['carrera_id' => $carrera->id]);
    $materia = Materia::factory()->create();
    $otra = Materia::factory()->create();
    $plan->vincularMateria($materia, 1, 4);
    $otra->requisitosDelPlan($plan)->attach($materia);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/materias/{$materia->id}")
        ->assertRedirect('/admin/materias');

    expect(Materia::find($materia->id))->toBeNull()
        ->and($plan->materias()->count())->toBe(0)
        ->and($otra->fresh()->requisitos()->count())->toBe(0);
});

it('impide que un docente gestione materias', function () {
    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get('/admin/materias')
        ->assertForbidden();
});

it('devuelve todo el catálogo al formulario de planes', function () {
    $materia = Materia::factory()->create(['codigo' => 'MAT-101']);
    $otra = Materia::factory()->create(['codigo' => 'OTR-101', 'nombre' => 'Otra materia']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->getJson('/admin/materias/catalogo')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['id' => $materia->id, 'codigo' => 'MAT-101'])
        ->assertJsonFragment(['id' => $otra->id, 'codigo' => 'OTR-101']);
});

it('muestra en el listado las carreras que incluyen la materia en sus planes', function () {
    $carrera = Carrera::factory()->create(['nombre' => 'Ingeniería']);
    $otraCarrera = Carrera::factory()->create(['nombre' => 'Administración']);
    $materia = Materia::factory()->create(['codigo' => 'COM-101']);
    PlanEstudio::factory()->create(['carrera_id' => $carrera->id])
        ->vincularMateria($materia, 1, 4);
    PlanEstudio::factory()->create(['carrera_id' => $otraCarrera->id])
        ->vincularMateria($materia, 2, 3);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/materias')
        ->assertOk()
        ->assertSee('Carreras')
        ->assertSee('Ingeniería')
        ->assertSee('Administración');
});

it('filtra el catálogo por las carreras derivadas de los planes', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $materia = Materia::factory()->create(['codigo' => 'COM-101']);
    PlanEstudio::factory()->create(['carrera_id' => $carrera->id])
        ->vincularMateria($materia, 1, 4);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/materias?carrera_id={$carrera->id}")
        ->assertOk()
        ->assertSee('COM-101');

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/materias?carrera_id={$otraCarrera->id}")
        ->assertOk()
        ->assertDontSee('COM-101');
});

it('ofrece solo la opción Ver en la lista de materias', function () {
    Materia::factory()->create(['nombre' => 'Materia Listado']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/materias')
        ->assertOk()
        ->assertSee('Materia Listado')
        ->assertSee('Ver')
        ->assertDontSee('Editar')
        ->assertDontSee('Eliminar');
});

it('muestra el detalle de la materia con sus planes y cursos', function () {
    $materia = Materia::factory()->create([
        'codigo' => 'DET-101',
        'nombre' => 'Materia Detalle',
    ]);
    $carrera = Carrera::factory()->create(['nombre' => 'Carrera Detalle']);
    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan Detalle',
    ]);
    $plan->vincularMateria($materia, 2, 3);
    Curso::factory()->conMateria($materia)->create(['nombre' => 'Curso Detalle']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/materias/{$materia->id}")
        ->assertOk()
        ->assertSee('Materia Detalle')
        ->assertSee('DET-101')
        ->assertSee('Carrera Detalle')
        ->assertSee('Plan Detalle')
        ->assertSee('Curso Detalle')
        ->assertSee('Editar')
        ->assertSee('Eliminar');
});

it('impide que un no administrador vea el detalle de una materia', function (Rol $rol) {
    $materia = Materia::factory()->create();

    $this->actingAs(usuarioConRol($rol))
        ->get("/admin/materias/{$materia->id}")
        ->assertForbidden();
})->with([
    'docente' => Rol::Docente,
    'estudiante' => Rol::Estudiante,
]);
