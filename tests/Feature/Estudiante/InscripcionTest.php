<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

function cursoDisponible(array $atributos = [], ?Materia $materia = null): Curso
{
    $materia ??= Materia::factory()->create([
        'plan_estudio_id' => PlanEstudio::factory()->create()->id,
        'semestre' => 1,
    ]);

    return Curso::factory()->conMateria($materia)->create(array_merge([
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
        'docente_id' => usuarioConRol(Rol::Docente)->id,
        'cupo' => 30,
        'activo' => true,
    ], $atributos));
}

/**
 * @return array{0: Materia, 1: Materia}
 */
function materiaConRequisito(): array
{
    $plan = PlanEstudio::factory()->create(['semestres' => 3]);

    $previa = $plan->materias()->create([
        'codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4,
    ]);
    $posterior = $plan->materias()->create([
        'codigo' => 'MAT-201', 'nombre' => 'Cálculo II', 'semestre' => 2, 'creditos' => 4,
    ]);
    $posterior->requisitos()->attach($previa);

    return [$previa, $posterior];
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

it('permite inscribirse cuando el requisito tiene nota final 51', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);

    Inscripcion::factory()->create([
        'curso_id' => $cursoPrevia->id,
        'user_id' => $estudiante->id,
        'nota_final' => 51,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoPosterior->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('curso_id', $cursoPosterior->id)
        ->where('user_id', $estudiante->id)
        ->exists())->toBeTrue();
});

it('bloquea la inscripción cuando el requisito aún no tiene nota final', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);

    Inscripcion::factory()->create([
        'curso_id' => $cursoPrevia->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoPosterior->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'No puedes inscribirte: aún no has aprobado los requisitos: MAT-101 - Cálculo I.']);

    expect(Inscripcion::where('curso_id', $cursoPosterior->id)->exists())->toBeFalse();
});

it('bloquea la inscripción cuando el requisito tiene nota final 50', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);

    Inscripcion::factory()->create([
        'curso_id' => $cursoPrevia->id,
        'user_id' => $estudiante->id,
        'nota_final' => 50,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoPosterior->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::where('curso_id', $cursoPosterior->id)->exists())->toBeFalse();
});

it('exige que estén aprobados todos los requisitos de la materia', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $plan = PlanEstudio::factory()->create(['semestres' => 3]);

    $primera = $plan->materias()->create(['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4]);
    $segunda = $plan->materias()->create(['codigo' => 'OTR-101', 'nombre' => 'Otra previa', 'semestre' => 1, 'creditos' => 4]);
    $posterior = $plan->materias()->create(['codigo' => 'MAT-201', 'nombre' => 'Cálculo II', 'semestre' => 2, 'creditos' => 4]);
    $posterior->requisitos()->attach([$primera->id, $segunda->id]);

    $cursoPrimera = cursoDisponible([], $primera);
    $cursoSegunda = cursoDisponible([], $segunda);
    $cursoPosterior = cursoDisponible([], $posterior);

    Inscripcion::factory()->create([
        'curso_id' => $cursoPrimera->id,
        'user_id' => $estudiante->id,
        'nota_final' => 80,
    ]);
    Inscripcion::factory()->create([
        'curso_id' => $cursoSegunda->id,
        'user_id' => $estudiante->id,
        'nota_final' => null,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoPosterior->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'No puedes inscribirte: aún no has aprobado los requisitos: OTR-101 - Otra previa.']);

    expect(Inscripcion::where('curso_id', $cursoPosterior->id)->exists())->toBeFalse();
});

it('considera aprobado un requisito retirado con nota final suficiente', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);

    Inscripcion::factory()->retirada()->create([
        'curso_id' => $cursoPrevia->id,
        'user_id' => $estudiante->id,
        'nota_final' => 75,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoPosterior->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('curso_id', $cursoPosterior->id)->exists())->toBeTrue();
});

it('bloquea la inscripción si el curso no tiene materia asignada', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $curso = cursoDisponible();
    $curso->update(['materia_id' => null]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'Este curso aún no tiene una materia del plan asignada. Corrígelo antes de inscribirte.']);

    expect(Inscripcion::where('curso_id', $curso->id)->exists())->toBeFalse();
});
