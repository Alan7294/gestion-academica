<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Inscripcion;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('muestra al docente solo sus propios cursos', function () {
    $docente = usuarioConRol(Rol::Docente);
    $otroDocente = usuarioConRol(Rol::Docente);

    Curso::factory()->create(['docente_id' => $docente->id, 'nombre' => 'Mi Curso']);
    Curso::factory()->create(['docente_id' => $otroDocente->id, 'nombre' => 'Curso Ajeno']);

    $this->actingAs($docente)
        ->get('/docente/cursos')
        ->assertOk()
        ->assertSee('Mi Curso')
        ->assertDontSee('Curso Ajeno');
});

it('impide que un docente abra un curso que no dicta', function () {
    $docente = usuarioConRol(Rol::Docente);
    $curso = Curso::factory()->create(['docente_id' => usuarioConRol(Rol::Docente)->id]);

    $this->actingAs($docente)
        ->get("/docente/cursos/{$curso->id}")
        ->assertForbidden();
});

it('lista los alumnos inscritos de un curso', function () {
    $docente = usuarioConRol(Rol::Docente);
    $curso = Curso::factory()->create(['docente_id' => $docente->id]);

    $estudiante = usuarioConRol(Rol::Estudiante);
    $estudiante->update(['name' => 'Ana Quispe']);

    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($docente)
        ->get("/docente/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Ana Quispe');
});

it('no cuenta a los alumnos retirados como inscritos', function () {
    $docente = usuarioConRol(Rol::Docente);
    $curso = Curso::factory()->create(['docente_id' => $docente->id]);

    $estudiante = usuarioConRol(Rol::Estudiante);
    $estudiante->update(['name' => 'Luis Retirado']);

    Inscripcion::factory()->retirada()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($docente)
        ->get("/docente/cursos/{$curso->id}")
        ->assertOk()
        ->assertDontSee('Luis Retirado');
});

it('impide que un estudiante acceda al panel del docente', function () {
    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->get('/docente/cursos')
        ->assertForbidden();
});
