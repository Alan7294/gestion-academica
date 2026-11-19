<?php

use App\Enums\Rol;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('permite al administrador crear un usuario con rol', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            'name' => 'Nuevo Docente',
            'ci' => '8765432',
            'telefono' => '70012345',
            'email' => 'nuevo.docente@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'nuevo.docente@academica.test')->firstOrFail();

    expect($usuario->hasRole(Rol::Docente->value))->toBeTrue()
        ->and($usuario->activo)->toBeTrue()
        ->and($usuario->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Password123', $usuario->password))->toBeTrue();
});

it('valida el correo y el carnet duplicados al crear un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $existente = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            'name' => 'Duplicado',
            'ci' => $existente->ci ?? '1234567',
            'telefono' => null,
            'email' => $existente->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Estudiante->value,
        ])
        ->assertSessionHasErrors(['email']);
});

it('permite al administrador cambiar el rol de un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            'name' => $usuario->name,
            'ci' => $usuario->ci ?? '7654321',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($usuario->fresh()->hasRole(Rol::Docente->value))->toBeTrue()
        ->and($usuario->fresh()->hasRole(Rol::Estudiante->value))->toBeFalse();
});

it('impide quitar el rol al último administrador', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$admin->id}", [
            'name' => $admin->name,
            'ci' => $admin->ci ?? '1111111',
            'telefono' => $admin->telefono,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors('rol');

    expect($admin->fresh()->esAdministrador())->toBeTrue();
});

it('impide desactivar al último administrador', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$admin->id}", [
            'name' => $admin->name,
            'ci' => $admin->ci ?? '2222222',
            'telefono' => $admin->telefono,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Administrador->value,
        ])
        ->assertSessionHasErrors('activo');

    expect($admin->fresh()->activo)->toBeTrue();
});

it('impide que un administrador elimine su propia cuenta', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->delete("/admin/usuarios/{$admin->id}")
        ->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});

it('elimina un usuario sin historial académico', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->delete("/admin/usuarios/{$usuario->id}")
        ->assertRedirect('/admin/usuarios');

    expect(User::find($usuario->id))->toBeNull();
});

it('impide eliminar un docente con calificaciones registradas', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);

    $curso = Curso::factory()->create([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
    ]);

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => usuarioConRol(Rol::Estudiante)->id,
    ]);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'registrado_por' => $docente->id,
    ]);

    $this->actingAs($admin)
        ->delete("/admin/usuarios/{$docente->id}")
        ->assertSessionHasErrors('usuario');

    expect(User::find($docente->id))->not->toBeNull();
});

it('lista y filtra usuarios por rol', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $docente->update(['name' => 'Docente Filtrado']);
    $estudiante = usuarioConRol(Rol::Estudiante);
    $estudiante->update(['name' => 'Estudiante Oculto']);

    $this->actingAs($admin)
        ->get('/admin/usuarios?rol=docente')
        ->assertOk()
        ->assertSee('Docente Filtrado')
        ->assertDontSee('Estudiante Oculto');
});

it('impide que un no administrador gestione usuarios', function () {
    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get('/admin/usuarios')
        ->assertForbidden();

    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->post('/admin/usuarios', [])
        ->assertForbidden();
});

it('impide iniciar sesión a un usuario desactivado', function () {
    $usuario = usuarioConRol(Rol::Estudiante);
    $usuario->update(['activo' => false]);

    $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
