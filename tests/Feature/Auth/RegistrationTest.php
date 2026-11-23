<?php

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * Carrera activa mínima para el registro de estudiantes.
 */
function carreraDePrueba(): Carrera
{
    return Carrera::create([
        'codigo' => 'SIS',
        'nombre' => 'Ingeniería de Sistemas',
        'facultad' => 'Facultad de Ingeniería',
        'activo' => true,
    ]);
}

test('registration screen can be rendered', function () {
    carreraDePrueba();

    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertSee('Carrera a la que te inscribes')
        ->assertSee('Modalidad');
});

test('new users can register as estudiante', function () {
    $carrera = carreraDePrueba();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'ci' => '12345678',
        'email' => 'test@example.com',
        'rol' => Rol::Estudiante->value,
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user->hasRole(Rol::Estudiante->value))->toBeTrue()
        ->and($user->carrera_id)->toBe($carrera->id)
        ->and($user->modalidad)->toBe(Modalidad::Semestral);
});

test('el registro de estudiante exige carrera y modalidad', function () {
    $response = $this->post('/register', [
        'name' => 'Sin Datos Academicos',
        'email' => 'sin.datos@example.com',
        'rol' => Rol::Estudiante->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['carrera_id', 'modalidad']);
    $this->assertGuest();
    expect(User::where('email', 'sin.datos@example.com')->exists())->toBeFalse();
});

test('el registro rechaza una carrera inexistente', function () {
    $response = $this->post('/register', [
        'name' => 'Carrera Fantasma',
        'email' => 'fantasma@example.com',
        'rol' => Rol::Estudiante->value,
        'carrera_id' => 9999,
        'modalidad' => Modalidad::Modular->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('carrera_id');
    $this->assertGuest();
});

test('new users can register as docente', function () {
    $this->post('/register', [
        'name' => 'Docente Demo',
        'email' => 'docente@example.com',
        'rol' => Rol::Docente->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'docente@example.com')->first();
    expect($user->hasRole(Rol::Docente->value))->toBeTrue()
        ->and($user->carrera_id)->toBeNull()
        ->and($user->modalidad)->toBeNull();
});

test('registration rejects the administrador role', function () {
    $response = $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@example.com',
        'rol' => Rol::Administrador->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('rol');
    $this->assertGuest();
    expect(User::where('email', 'intruso@example.com')->exists())->toBeFalse();
});
