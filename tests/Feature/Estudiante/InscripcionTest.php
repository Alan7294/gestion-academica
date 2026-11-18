<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

function cursoDisponible(array $atributos = []): Curso
{
    return Curso::factory()->create(array_merge([
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
        'docente_id' => usuarioConRol(Rol::Docente)->id,
        'cupo' => 30,
        'activo' => true,
    ], $atributos));
}

it('permite al estudiante inscribirse en un curso disponible', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $curso = cursoDisponible();

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->where('curso_id', $curso->id)->exists())->toBeTrue();
});

it('impide inscribirse dos veces al mismo curso', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $curso = cursoDisponible();

    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::where('curso_id', $curso->id)->count())->toBe(1);
});

it('rechaza la inscripción cuando el curso no tiene cupo', function () {
    $curso = cursoDisponible(['cupo' => 1]);

    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => usuarioConRol(Rol::Estudiante)->id,
    ]);

    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::where('curso_id', $curso->id)->count())->toBe(1);
});

it('rechaza la inscripción si el horario se cruza con otro curso', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $docente = usuarioConRol(Rol::Docente);

    $primero = cursoDisponible(['docente_id' => $docente->id]);
    $primero->horarios()->create([
        'docente_id' => $docente->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $segundo = cursoDisponible(['docente_id' => usuarioConRol(Rol::Docente)->id]);
    $segundo->horarios()->create([
        'docente_id' => $segundo->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
    ]);

    Inscripcion::factory()->create([
        'curso_id' => $primero->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$segundo->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(1);
});

it('permite inscribirse si los horarios son en días distintos', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $docente = usuarioConRol(Rol::Docente);

    $primero = cursoDisponible(['docente_id' => $docente->id]);
    $primero->horarios()->create([
        'docente_id' => $docente->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $segundo = cursoDisponible(['docente_id' => usuarioConRol(Rol::Docente)->id]);
    $segundo->horarios()->create([
        'docente_id' => $segundo->docente_id,
        'dia_semana' => 2,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    Inscripcion::factory()->create([
        'curso_id' => $primero->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$segundo->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(2);
});

it('no permite inscribirse a un curso de un periodo cerrado', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $curso = cursoDisponible([
        'periodo_id' => Periodo::factory()->create()->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::count())->toBe(0);
});

it('permite retirarse y volver a inscribirse', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $curso = cursoDisponible();

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->delete("/estudiante/inscripciones/{$inscripcion->id}")
        ->assertRedirect('/estudiante/inscripciones');

    expect($inscripcion->fresh()->estado->value)->toBe('retirado');

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('curso_id', $curso->id)->count())->toBe(1)
        ->and($inscripcion->fresh()->estado->value)->toBe('inscrito');
});

it('impide retirar la inscripción de otro estudiante', function () {
    $inscripcion = Inscripcion::factory()->create([
        'user_id' => usuarioConRol(Rol::Estudiante)->id,
    ]);

    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->delete("/estudiante/inscripciones/{$inscripcion->id}")
        ->assertForbidden();

    expect($inscripcion->fresh()->estado->value)->toBe('inscrito');
});

it('impide que un docente se inscriba a cursos', function () {
    $curso = cursoDisponible();

    $this->actingAs(usuarioConRol(Rol::Docente))
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertForbidden();
});

it('muestra la oferta académica al estudiante', function () {
    $curso = cursoDisponible(['nombre' => 'Inteligencia Artificial']);

    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->get('/estudiante/cursos')
        ->assertOk()
        ->assertSee('Inteligencia Artificial')
        ->assertSee('Inscribirme');
});
