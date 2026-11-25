<?php

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('guarda los certificados al crear un docente', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            'name' => 'Docente Certificado',
            'ci' => '8765499',
            'telefono' => null,
            'email' => 'docente.certificado@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'certificados' => [
                [
                    'titulo' => 'Magíster en Sistemas',
                    'institucion' => 'Universidad Nacional',
                    'codigo' => 'CERT-001',
                ],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'docente.certificado@academica.test')->firstOrFail();

    expect($usuario->certificadosDocentes)->toHaveCount(1)
        ->and($usuario->certificadosDocentes->first()->titulo)->toBe('Magíster en Sistemas')
        ->and($usuario->certificadosDocentes->first()->institucion)->toBe('Universidad Nacional')
        ->and($usuario->certificadosDocentes->first()->codigo)->toBe('CERT-001');
});

it('actualiza los certificados de un docente', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $docente->certificadosDocentes()->create([
        'titulo' => 'Título anterior',
        'institucion' => 'Institución anterior',
        'codigo' => 'ANT-001',
    ]);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$docente->id}", [
            'name' => $docente->name,
            'ci' => $docente->ci ?? '8765500',
            'telefono' => $docente->telefono,
            'email' => $docente->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'certificados' => [
                [
                    'titulo' => 'Doctorado en Informática',
                    'institucion' => 'Universidad Central',
                    'codigo' => 'DOC-2026',
                ],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($docente->certificadosDocentes()->count())->toBe(1)
        ->and($docente->certificadosDocentes()->first()->titulo)->toBe('Doctorado en Informática')
        ->and($docente->certificadosDocentes()->first()->codigo)->toBe('DOC-2026');
});

it('exige los tres datos cuando se captura un certificado', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            'name' => 'Docente Incompleto',
            'ci' => '8765501',
            'telefono' => null,
            'email' => 'docente.incompleto@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'certificados' => [
                ['titulo' => 'Título sin datos', 'institucion' => '', 'codigo' => ''],
            ],
            'activo' => '1',
        ])
        ->assertSessionHasErrors([
            'certificados.0.institucion',
            'certificados.0.codigo',
        ]);

    expect(User::where('email', 'docente.incompleto@academica.test')->exists())->toBeFalse();
});

it('exige el título aunque se llene otro dato del certificado', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            'name' => 'Docente Sin Título',
            'ci' => '8765503',
            'telefono' => null,
            'email' => 'docente.sin.titulo@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'certificados' => [
                ['titulo' => '', 'institucion' => 'Universidad Nacional', 'codigo' => 'CERT-002'],
            ],
            'activo' => '1',
        ])
        ->assertSessionHasErrors('certificados.0.titulo');

    expect(User::where('email', 'docente.sin.titulo@academica.test')->exists())->toBeFalse();
});

it('elimina los certificados cuando el usuario deja de ser docente', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $carrera = Carrera::factory()->create();
    $docente->certificadosDocentes()->create([
        'titulo' => 'Título',
        'institucion' => 'Institución',
        'codigo' => 'CERT-123',
    ]);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$docente->id}", [
            'name' => $docente->name,
            'ci' => $docente->ci ?? '8765502',
            'telefono' => $docente->telefono,
            'email' => $docente->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                [
                    'carrera_id' => $carrera->id,
                    'modalidad' => Modalidad::Semestral->value,
                    'es_principal' => '1',
                ],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($docente->certificadosDocentes()->count())->toBe(0);
});

it('muestra los certificados al editar un docente', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $docente->certificadosDocentes()->create([
        'titulo' => 'Especialidad en Base de Datos',
        'institucion' => 'Instituto Tecnológico',
        'codigo' => 'ESP-456',
    ]);

    $this->actingAs($admin)
        ->get("/admin/usuarios/{$docente->id}/edit")
        ->assertOk()
        ->assertSee('Certificados del docente')
        ->assertSee('Especialidad en Base de Datos')
        ->assertSee('Instituto Tecnológico')
        ->assertSee('ESP-456');
});
