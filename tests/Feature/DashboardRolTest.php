<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('redirige al administrador a su panel', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertRedirect('/admin/dashboard');
});

it('redirige al docente a su panel', function () {
    $docente = usuarioConRol(Rol::Docente);

    $this->actingAs($docente)
        ->get('/dashboard')
        ->assertRedirect('/docente/dashboard');
});

it('redirige al estudiante a su panel', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);

    $this->actingAs($estudiante)
        ->get('/dashboard')
        ->assertRedirect('/estudiante/dashboard');
});

it('muestra el panel de administración solo al administrador', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Panel de administración');
});

it('impide que un docente entre al panel de administración', function () {
    $docente = usuarioConRol(Rol::Docente);

    $this->actingAs($docente)
        ->get('/admin/dashboard')
        ->assertForbidden();
});

it('impide que un estudiante entre al panel del docente', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);

    $this->actingAs($estudiante)
        ->get('/docente/dashboard')
        ->assertForbidden();
});

it('rechaza a un usuario sin rol asignado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertForbidden();
});
