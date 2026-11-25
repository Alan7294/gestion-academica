<?php

use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Facultad;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('permite al administrador listar las facultades', function () {
    Facultad::factory()->count(2)->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/facultades')
        ->assertOk()
        ->assertSee('Facultades');
});

it('impide que un docente gestione facultades', function () {
    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get('/admin/facultades')
        ->assertForbidden();
});

it('permite crear una facultad', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/facultades', [
            'nombre' => 'Facultad de Prueba',
            'activo' => '1',
        ])
        ->assertRedirect('/admin/facultades');

    $facultad = Facultad::where('nombre', 'Facultad de Prueba')->firstOrFail();

    expect($facultad->activo)->toBeTrue();
});

it('rechaza un nombre de facultad duplicado', function () {
    Facultad::factory()->create(['nombre' => 'Facultad de Prueba']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post('/admin/facultades', [
            'nombre' => 'Facultad de Prueba',
            'activo' => '1',
        ])
        ->assertSessionHasErrors('nombre');

    expect(Facultad::where('nombre', 'Facultad de Prueba')->count())->toBe(1);
});

it('mantiene el estado inactivo al corregir errores del formulario', function () {
    Facultad::factory()->create(['nombre' => 'Facultad de Prueba']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->from('/admin/facultades/create')
        ->post('/admin/facultades', [
            'nombre' => 'Facultad de Prueba',
            'activo' => '0',
        ])
        ->assertRedirect('/admin/facultades/create');

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/facultades/create')
        ->assertOk()
        ->assertSee('<input type="hidden" name="activo" value="0">', false)
        ->assertDontSee('checked', false);
});

it('permite actualizar una facultad y desactivarla', function () {
    $facultad = Facultad::factory()->create(['activo' => true]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->put("/admin/facultades/{$facultad->id}", [
            'nombre' => 'Facultad actualizada',
            'activo' => '0',
        ])
        ->assertRedirect('/admin/facultades');

    $facultad->refresh();

    expect($facultad->nombre)->toBe('Facultad actualizada')
        ->and($facultad->activo)->toBeFalse();
});

it('no elimina una facultad que tiene carreras', function () {
    $facultad = Facultad::factory()->create();
    Carrera::factory()->create(['facultad_id' => $facultad->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/facultades/{$facultad->id}")
        ->assertSessionHasErrors(['facultad' => 'No se puede eliminar una facultad que tiene carreras registradas.']);

    expect(Facultad::find($facultad->id))->not->toBeNull();
});

it('elimina una facultad sin carreras', function () {
    $facultad = Facultad::factory()->create();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/facultades/{$facultad->id}")
        ->assertRedirect('/admin/facultades');

    expect(Facultad::find($facultad->id))->toBeNull();
});
