<?php

use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

function datosCurso(array $sobrescribir = []): array
{
    $carrera = Carrera::factory()->create();
    $periodo = Periodo::factory()->create();

    return array_merge([
        'carrera_id' => $carrera->id,
        'periodo_id' => $periodo->id,
        'codigo' => 'SIS-101',
        'nombre' => 'Programación IV',
        'creditos' => 4,
        'semestre' => 4,
        'cupo' => 30,
        'activo' => '1',
    ], $sobrescribir);
}

it('permite al administrador registrar un curso', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso(['docente_id' => $docente->id]))
        ->assertRedirect();

    $curso = Curso::first();
    expect($curso->codigo)->toBe('SIS-101')
        ->and($curso->docente_id)->toBe($docente->id);
});

it('exige que el docente asignado tenga el rol docente', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $estudiante = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso(['docente_id' => $estudiante->id]))
        ->assertSessionHasErrors('docente_id');

    expect(Curso::count())->toBe(0);
});

it('rechaza el mismo código dentro del periodo', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $periodo = Periodo::factory()->create();

    Curso::factory()->create(['periodo_id' => $periodo->id, 'codigo' => 'SIS-101']);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso(['periodo_id' => $periodo->id]))
        ->assertSessionHasErrors('codigo');
});

it('permite el mismo código en periodos distintos', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carrera = Carrera::factory()->create();
    $anterior = Periodo::factory()->create();
    $actual = Periodo::factory()->create();

    Curso::factory()->create([
        'carrera_id' => $carrera->id,
        'periodo_id' => $anterior->id,
        'codigo' => 'SIS-101',
    ]);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso([
            'carrera_id' => $carrera->id,
            'periodo_id' => $actual->id,
        ]))
        ->assertRedirect();

    expect(Curso::where('codigo', 'SIS-101')->count())->toBe(2);
});

it('impide que un docente registre cursos', function () {
    $this->actingAs(usuarioConRol(Rol::Docente))
        ->post('/admin/cursos', datosCurso())
        ->assertForbidden();
});

it('impide cambiar el docente si el curso ya tiene horarios', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $otroDocente = usuarioConRol(Rol::Docente);
    $datos = datosCurso(['docente_id' => $docente->id]);

    $curso = Curso::factory()->create($datos);
    $curso->horarios()->create([
        'docente_id' => $docente->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $this->actingAs($admin)
        ->put("/admin/cursos/{$curso->id}", array_merge($datos, ['docente_id' => $otroDocente->id]))
        ->assertSessionHasErrors('docente_id');

    expect($curso->fresh()->docente_id)->toBe($docente->id);
});

it('muestra el detalle del curso con su horario', function () {
    $curso = Curso::factory()->create(['nombre' => 'Base de Datos II']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Base de Datos II')
        ->assertSee('Horario del curso');
});
