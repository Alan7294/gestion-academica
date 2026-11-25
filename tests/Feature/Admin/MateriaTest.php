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

it('permite al administrador crear una materia sin elegir carrera', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', [
            'codigo' => 'MAT-101',
            'nombre' => 'Cálculo I',
        ])
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
        ->post('/admin/materias', [
            'codigo' => 'MAT-101',
            'nombre' => 'Cálculo II',
        ])
        ->assertSessionHasErrors('codigo');

    expect(Materia::where('codigo', 'MAT-101')->count())->toBe(1);
});

it('rechaza el mismo código aunque se envíe una carrera distinta', function () {
    $otraCarrera = Carrera::factory()->create();
    Materia::factory()->create(['codigo' => 'GEN-101']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', [
            'carrera_id' => $otraCarrera->id,
            'codigo' => 'GEN-101',
            'nombre' => 'Materia general',
        ])
        ->assertSessionHasErrors('codigo');

    expect(Materia::where('codigo', 'GEN-101')->count())->toBe(1);
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
