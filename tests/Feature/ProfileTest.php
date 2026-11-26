<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk()
        ->assertSee('name="nombres"', false)
        ->assertSee('name="primer_apellido"', false)
        ->assertSee('name="segundo_apellido"', false);
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            ...datosNombre('Ana María', 'Quispe', 'Mamani'),
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Ana María Quispe Mamani', $user->name);
    $this->assertSame('Ana María', $user->nombres);
    $this->assertSame('Quispe', $user->primer_apellido);
    $this->assertSame('Mamani', $user->segundo_apellido);
    $this->assertSame('test@example.com', $user->email);
});

test('el correo electrónico se conserva cuando el formulario envía el mismo valor', function () {
    $user = User::factory()->create();
    $correoOriginal = $user->email;

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            ...datosNombre('Ana', 'Quispe'),
            'email' => $correoOriginal,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertSame('Ana Quispe', $user->refresh()->name);
    $this->assertSame($correoOriginal, $user->email);
});

test('el perfil exige nombres y primer apellido', function (array $nombre, string $campo) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', $nombre + ['email' => $user->email])
        ->assertSessionHasErrors($campo);
})->with([
    'sin nombres' => [['primer_apellido' => 'Quispe'], 'nombres'],
    'sin primer apellido' => [['nombres' => 'Ana'], 'primer_apellido'],
]);

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
