<?php

use App\Enums\Rol;
use App\Models\User;

test('la pantalla de registro público ya no está disponible', function () {
    $this->get('/register')
        ->assertNotFound();
});

test('el registro público no crea cuentas', function (Rol $rol) {
    $correo = $rol->value.'@ejemplo.test';

    $this->post('/register', [
        'name' => 'Visitante',
        'email' => $correo,
        'rol' => $rol->value,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ])->assertNotFound();

    $this->assertGuest();

    expect(User::where('email', $correo)->exists())->toBeFalse();
})->with([
    'estudiante' => Rol::Estudiante,
    'docente' => Rol::Docente,
    'administrador' => Rol::Administrador,
]);

test('la página de inicio de sesión no ofrece registro público', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('Regístrate')
        ->assertSee('Solicítala al administrador del sistema');
});

test('la portada no ofrece registro público', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('Crear cuenta')
        ->assertDontSee('Registrarme')
        ->assertSee('Las cuentas las crea el administrador del sistema.');
});
