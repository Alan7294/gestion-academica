<?php

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Inscripcion;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * @param  array<int, string>  $fechasSesion
 */
function horariosModular(Curso $curso, array $fechasSesion): void
{
    foreach ($fechasSesion as $fechaSesion) {
        $curso->horarios()->create([
            'docente_id' => $curso->docente_id,
            'dia_semana' => 1,
            'hora_inicio' => '08:30',
            'hora_fin' => '11:45',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-01-30',
            'fecha_sesion' => $fechaSesion,
        ]);
    }
}

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

it('muestra el rango de fechas y la hora sin los días en los cursos del docente', function () {
    $docente = usuarioConRol(Rol::Docente);
    $curso = Curso::factory()->create(['docente_id' => $docente->id, 'modalidad' => Modalidad::Modular]);
    horariosModular($curso, ['2026-01-05', '2026-01-12']);

    $this->actingAs($docente)
        ->get('/docente/cursos')
        ->assertOk()
        ->assertSee('Fecha de Inicio: 05/01/2026')
        ->assertSee('Fecha de Fin: 30/01/2026')
        ->assertSee('Horario: 08:30 - 11:45')
        ->assertDontSee('Lunes: 08:30 - 11:45');
});

it('muestra el mismo resumen de horario en el detalle del curso del docente', function () {
    $docente = usuarioConRol(Rol::Docente);
    $curso = Curso::factory()->create(['docente_id' => $docente->id, 'modalidad' => Modalidad::Modular]);
    horariosModular($curso, ['2026-01-05', '2026-01-12']);

    $this->actingAs($docente)
        ->get("/docente/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Fecha de Inicio: 05/01/2026')
        ->assertSee('Fecha de Fin: 30/01/2026')
        ->assertSee('Horario: 08:30 - 11:45')
        ->assertDontSee('Lunes: 08:30 - 11:45');
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
