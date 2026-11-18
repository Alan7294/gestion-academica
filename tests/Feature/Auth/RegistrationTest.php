<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register as estudiante', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'ci' => '12345678',
        'email' => 'test@example.com',
        'rol' => Rol::Estudiante->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user->hasRole(Rol::Estudiante->value))->toBeTrue();
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
    expect($user->hasRole(Rol::Docente->value))->toBeTrue();
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
