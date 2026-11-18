<?php

use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
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

it('permite crear una carrera', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/carreras', [
            'codigo' => 'SIS',
            'nombre' => 'Ingeniería de Sistemas',
            'facultad' => 'Facultad de Ingeniería',
            'activo' => '1',
        ])
        ->assertRedirect('/admin/carreras');

    expect(Carrera::where('codigo', 'SIS')->where('activo', true)->exists())->toBeTrue();
});

it('rechaza un código de carrera duplicado', function () {
    Carrera::factory()->create(['codigo' => 'SIS']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/carreras', [
            'codigo' => 'SIS',
            'nombre' => 'Otra carrera',
        ])
        ->assertSessionHasErrors('codigo');
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

it('elimina una carrera sin cursos', function () {
    $carrera = Carrera::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/carreras/{$carrera->id}")
        ->assertRedirect('/admin/carreras');

    expect(Carrera::find($carrera->id))->toBeNull();
});
