<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

it('cierra la sesión de una cuenta desactivada en la siguiente petición', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $user->update(['activo' => false]);

    $this->get('/dashboard')
        ->assertRedirect(route('login', absolute: false));

    $this->assertGuest();
});

it('explica el motivo al cerrar la sesión por desactivación', function () {
    $user = User::factory()->create(['activo' => false]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors([
            'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
        ]);
});

it('rechaza las peticiones JSON de cuentas desactivadas', function () {
    $user = User::factory()->create(['activo' => false]);

    $this->actingAs($user)
        ->getJson('/dashboard')
        ->assertForbidden();
});
