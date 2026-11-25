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

it('permite al administrador crear una materia desde el formulario', function () {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', [
            'carrera_id' => $carrera->id,
            'codigo' => 'MAT-101',
            'nombre' => 'Cálculo I',
        ])
        ->assertRedirect('/admin/materias');

    $this->assertDatabaseHas('materias', [
        'carrera_id' => $carrera->id,
        'codigo' => 'MAT-101',
        'nombre' => 'Cálculo I',
    ]);
});

it('crea una materia vía AJAX para usarla dentro del formulario de planes', function () {
    $carrera = Carrera::factory()->create();

    $respuesta = $this->actingAs(usuarioConRol(Rol::Administrador))
        ->postJson('/admin/materias', [
            'carrera_id' => $carrera->id,
            'codigo' => 'PRO-101',
            'nombre' => 'Programación I',
        ])
        ->assertCreated()
        ->assertJsonFragment([
            'carrera_id' => $carrera->id,
            'codigo' => 'PRO-101',
            'nombre' => 'Programación I',
        ]);

    expect(Materia::find($respuesta->json('id')))->not->toBeNull();
});

it('rechaza códigos repetidos dentro de una misma carrera', function () {
    $carrera = Carrera::factory()->create();
    Materia::factory()->create(['carrera_id' => $carrera->id, 'codigo' => 'MAT-101']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', [
            'carrera_id' => $carrera->id,
            'codigo' => 'MAT-101',
            'nombre' => 'Cálculo II',
        ])
        ->assertSessionHasErrors('codigo');

    expect(Materia::where('carrera_id', $carrera->id)->count())->toBe(1);
});

it('permite el mismo código en carreras distintas', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    Materia::factory()->create(['carrera_id' => $carrera->id, 'codigo' => 'GEN-101']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/materias', [
            'carrera_id' => $otraCarrera->id,
            'codigo' => 'GEN-101',
            'nombre' => 'Materia general',
        ])
        ->assertRedirect('/admin/materias');

    expect(Materia::where('codigo', 'GEN-101')->count())->toBe(2);
});

it('permite al administrador editar una materia', function () {
    $materia = Materia::factory()->create();
    $nuevaCarrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/materias/{$materia->id}", [
            'carrera_id' => $nuevaCarrera->id,
            'codigo' => 'ACT-201',
            'nombre' => 'Materia actualizada',
        ])
        ->assertRedirect('/admin/materias');

    expect($materia->fresh()->carrera_id)->toBe($nuevaCarrera->id)
        ->and($materia->fresh()->codigo)->toBe('ACT-201')
        ->and($materia->fresh()->nombre)->toBe('Materia actualizada');
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
    $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
    $otra = Materia::factory()->create(['carrera_id' => $carrera->id]);
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

it('devuelve solo las materias de la carrera pedida para el formulario de planes', function () {
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'codigo' => 'MAT-101']);
    Materia::factory()->create(['carrera_id' => $otraCarrera->id, 'codigo' => 'OTR-101']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->getJson("/admin/materias/catalogo?carrera_id={$carrera->id}")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $materia->id, 'codigo' => 'MAT-101'])
        ->assertJsonMissing(['codigo' => 'OTR-101']);
});
