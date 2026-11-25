<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

test('el panel es accesible sin verificar el correo', function () {
    $usuario = usuarioConRol(Rol::Administrador);

    $this->actingAs($usuario)
        ->get('/dashboard')
        ->assertRedirect(route('admin.dashboard', absolute: false));
});

test('las rutas de verificación de correo ya no están disponibles', function (string $metodo, string $uri) {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->{$metodo}($uri)->assertNotFound();
})->with([
    'aviso de verificación' => ['get', '/verify-email'],
    'reenvío del correo' => ['post', '/email/verification-notification'],
]);
