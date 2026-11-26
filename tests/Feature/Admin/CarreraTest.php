<?php

use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Facultad;
use App\Models\Matricula;
use App\Models\PlanEstudio;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('permite al administrador listar las carreras', function () {
    Carrera::factory()->count(2)->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/carreras')
        ->assertOk()
        ->assertSee('Carreras');
});

it('permite crear una carrera con una facultad del catálogo', function () {
    $facultad = Facultad::factory()->create(['nombre' => 'Facultad de Ingeniería']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/carreras', [
            'codigo' => 'SIS',
            'nombre' => 'Ingeniería de Sistemas',
            'facultad_id' => $facultad->id,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/carreras');

    $carrera = Carrera::where('codigo', 'SIS')->firstOrFail();

    expect($carrera->activo)->toBeTrue()
        ->and($carrera->facultad_id)->toBe($facultad->id)
        ->and($carrera->facultad->nombre)->toBe('Facultad de Ingeniería');
});

it('muestra las facultades registradas en el formulario de carreras', function () {
    Facultad::factory()->create(['nombre' => 'Facultad de Ciencias Humanas']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/carreras/create')
        ->assertOk()
        ->assertSee('name="facultad_id"', false)
        ->assertSee('Facultad de Ciencias Humanas');
});

it('rechaza una carrera con una facultad inexistente', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/carreras', [
            'codigo' => 'SIS',
            'nombre' => 'Ingeniería de Sistemas',
            'facultad_id' => 999999,
            'activo' => '1',
        ])
        ->assertSessionHasErrors('facultad_id');

    expect(Carrera::where('codigo', 'SIS')->exists())->toBeFalse();
});

it('mantiene el estado inactivo al corregir errores del formulario', function () {
    $facultad = Facultad::factory()->create();
    Carrera::factory()->create(['codigo' => 'SIS']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->from('/admin/carreras/create')
        ->post('/admin/carreras', [
            'codigo' => 'SIS',
            'nombre' => 'Carrera duplicada',
            'facultad_id' => $facultad->id,
            'activo' => '0',
        ])
        ->assertRedirect('/admin/carreras/create');

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/carreras/create')
        ->assertOk()
        ->assertSee('<input type="hidden" name="activo" value="0">', false)
        ->assertDontSee('checked', false);
});

it('rechaza un código de carrera duplicado', function () {
    $facultad = Facultad::factory()->create();
    Carrera::factory()->create(['codigo' => 'SIS']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/carreras', [
            'codigo' => 'SIS',
            'nombre' => 'Otra carrera',
            'facultad_id' => $facultad->id,
        ])
        ->assertSessionHasErrors('codigo');
});

it('permite actualizar una carrera y desactivarla', function () {
    $facultad = Facultad::factory()->create(['nombre' => 'Facultad de Ingeniería actualizada']);
    $carrera = Carrera::factory()->create(['activo' => true]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/carreras/{$carrera->id}", [
            'codigo' => 'SIS',
            'nombre' => 'Ingeniería de Sistemas actualizada',
            'facultad_id' => $facultad->id,
            'activo' => '0',
        ])
        ->assertRedirect('/admin/carreras');

    $carrera->refresh();

    expect($carrera->codigo)->toBe('SIS')
        ->and($carrera->nombre)->toBe('Ingeniería de Sistemas actualizada')
        ->and($carrera->facultad_id)->toBe($facultad->id)
        ->and($carrera->activo)->toBeFalse();
});

it('impide que un docente gestione carreras', function () {
    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get('/admin/carreras')
        ->assertForbidden();
});

it('no elimina una carrera que tiene cursos', function () {
    $carrera = Carrera::factory()->create();
    Curso::factory()->create(['carrera_id' => $carrera->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/carreras/{$carrera->id}")
        ->assertSessionHasErrors('carrera');

    expect(Carrera::find($carrera->id))->not->toBeNull();
});

it('no elimina una carrera que tiene planes de estudio', function () {
    $carrera = Carrera::factory()->create();
    PlanEstudio::factory()->create(['carrera_id' => $carrera->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/carreras/{$carrera->id}")
        ->assertSessionHasErrors('carrera');

    expect(Carrera::find($carrera->id))->not->toBeNull();
});

it('no elimina una carrera que tiene matrículas', function () {
    $carrera = Carrera::factory()->create();
    Matricula::factory()->create(['carrera_id' => $carrera->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/carreras/{$carrera->id}")
        ->assertSessionHasErrors(['carrera' => 'No se puede eliminar una carrera que tiene matrículas registradas.']);

    expect(Carrera::find($carrera->id))->not->toBeNull();
});

it('elimina una carrera sin cursos', function () {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/carreras/{$carrera->id}")
        ->assertRedirect('/admin/carreras');

    expect(Carrera::find($carrera->id))->toBeNull();
});

it('ofrece solo la opción Ver en la lista de carreras', function () {
    Carrera::factory()->create(['nombre' => 'Carrera Listado']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/carreras')
        ->assertOk()
        ->assertSee('Carrera Listado')
        ->assertSee('Ver')
        ->assertDontSee('Editar')
        ->assertDontSee('Eliminar');
});

it('muestra el detalle de la carrera con sus planes y cursos', function () {
    $facultad = Facultad::factory()->create(['nombre' => 'Facultad Detalle']);
    $carrera = Carrera::factory()->create([
        'facultad_id' => $facultad->id,
        'nombre' => 'Carrera Detalle',
    ]);
    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan Detalle',
    ]);
    Curso::factory()->create([
        'carrera_id' => $carrera->id,
        'plan_estudio_id' => $plan->id,
        'nombre' => 'Curso Detalle',
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/carreras/{$carrera->id}")
        ->assertOk()
        ->assertSee('Carrera Detalle')
        ->assertSee('Facultad Detalle')
        ->assertSee('Plan Detalle')
        ->assertSee('Curso Detalle')
        ->assertSee('Editar')
        ->assertSee('Eliminar');
});

it('impide que un no administrador vea el detalle de una carrera', function (Rol $rol) {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol($rol))
        ->get("/admin/carreras/{$carrera->id}")
        ->assertForbidden();
})->with([
    'docente' => Rol::Docente,
    'estudiante' => Rol::Estudiante,
]);
