<?php

use App\Enums\EstadoMatricula;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Calificacion;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\CursoEvaluacion;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
    $this->travelTo('2026-01-06 10:00:00');
});

function cursoDisponible(array $atributos = [], ?Materia $materia = null, ?Periodo $periodo = null): Curso
{
    $materia ??= Materia::factory()->create();

    return Curso::factory()->conMateria($materia)->create(array_merge([
        'periodo_id' => $periodo?->id ?? Periodo::factory()->vigente()->create()->id,
        'docente_id' => usuarioConRol(Rol::Docente)->id,
        'cupo' => 30,
        'activo' => true,
    ], $atributos));
}

/**
 * @param  array<int, string>  $fechasSesion
 */
function horariosDeModulo(Curso $curso, array $fechasSesion): void
{
    foreach ($fechasSesion as $fechaSesion) {
        $curso->horarios()->create([
            'docente_id' => $curso->docente_id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-01-30',
            'fecha_sesion' => $fechaSesion,
        ]);
    }
}

function horarioEntre(Curso $curso, string $fechaInicio, string $fechaFin): void
{
    $curso->horarios()->create([
        'docente_id' => $curso->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
    ]);
}

function horarioHasta(Curso $curso, string $fechaFin): void
{
    horarioEntre($curso, $fechaFin, $fechaFin);
}

/**
 * @return array{0: Materia, 1: Materia}
 */
function materiaConRequisito(): array
{
    $plan = PlanEstudio::factory()->create(['semestres' => 3]);

    $previa = materiaEnPlan($plan, [
        'codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4,
    ]);
    $posterior = materiaEnPlan($plan, [
        'codigo' => 'MAT-201', 'nombre' => 'Cálculo II', 'semestre' => 2, 'creditos' => 4,
    ]);
    $posterior->requisitosDelPlan($plan)->attach($previa);

    return [$previa, $posterior];
}

it('permite al estudiante inscribirse en un curso disponible', function () {
    $curso = cursoDisponible();
    $estudiante = estudianteMatriculado($curso);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->where('curso_id', $curso->id)->exists())->toBeTrue();
});

it('permite inscribirse hasta el tercer día del ciclo contando el inicio', function (int $dias) {
    $curso = cursoDisponible();
    $estudiante = estudianteMatriculado($curso);

    $this->travelTo(today()->addDays($dias)->toDateString());

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->where('curso_id', $curso->id)->exists())->toBeTrue();
})->with([
    'día del inicio' => 0,
    'segundo día' => 1,
    'tercer día' => 2,
]);

it('rechaza inscribirse desde el cuarto día del ciclo', function () {
    $curso = cursoDisponible();
    $estudiante = estudianteMatriculado($curso);

    $this->travelTo(today()->addDays(3)->toDateString());

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'No puedes inscribirte: el periodo de inscripción del curso ya cerró.']);

    expect(Inscripcion::where('user_id', $estudiante->id)->where('curso_id', $curso->id)->exists())->toBeFalse();
});

it('rechaza inscribirse sin horarios cuando el periodo empezó hace más de dos días', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => today()->subDays(3)->toDateString(),
        'fecha_fin' => today()->addMonth()->toDateString(),
    ]);
    $curso = cursoDisponible([], null, $periodo);
    $estudiante = estudianteMatriculado($curso);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'No puedes inscribirte: el periodo de inscripción del curso ya cerró.']);

    expect(Inscripcion::where('user_id', $estudiante->id)->where('curso_id', $curso->id)->exists())->toBeFalse();
});

it('permite a un estudiante de otra carrera inscribirse en un curso compartido', function () {
    $carreraAnfitriona = Carrera::factory()->create();
    $carreraCompartida = Carrera::factory()->create();
    $planAnfitrion = PlanEstudio::factory()->create([
        'carrera_id' => $carreraAnfitriona->id,
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);
    $planCompartido = PlanEstudio::factory()->create([
        'carrera_id' => $carreraCompartida->id,
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);

    $materia = Materia::factory()->create(['codigo' => 'COM-101', 'nombre' => 'Materia compartida']);
    $planAnfitrion->vincularMateria($materia, 2, 5);
    $planCompartido->vincularMateria($materia, 3, 3);

    $curso = cursoDisponible(['creditos' => 5, 'semestre' => 2], $materia);
    $curso->planesEstudio()->sync([$planAnfitrion->id, $planCompartido->id]);

    $estudiante = usuarioConRol(Rol::Estudiante);
    $estudiante->matriculas()->create([
        'carrera_id' => $carreraCompartida->id,
        'plan_estudio_id' => $planCompartido->id,
        'estado' => EstadoMatricula::Activa,
        'es_principal' => true,
        'modalidad' => Modalidad::Semestral,
        'fecha_inicio' => now()->toDateString(),
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    $inscripcion = Inscripcion::where('user_id', $estudiante->id)
        ->where('curso_id', $curso->id)
        ->firstOrFail();

    expect($inscripcion->plan_estudio_id)->toBe($planCompartido->id)
        ->and($inscripcion->creditosSegunPlan())->toBe(3)
        ->and((int) $curso->creditos)->toBe(5);
});

it('impide inscribirse dos veces al mismo curso', function () {
    $curso = cursoDisponible();
    $estudiante = estudianteMatriculado($curso);

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
    $otroEstudiante = estudianteMatriculado($curso);
    $estudiante = estudianteMatriculado($curso);

    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $otroEstudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::where('curso_id', $curso->id)->count())->toBe(1);
});

it('rechaza la inscripción si el horario se cruza con otro curso', function () {
    $docente = usuarioConRol(Rol::Docente);
    $periodo = Periodo::factory()->vigente()->create();

    $primero = cursoDisponible(['docente_id' => $docente->id], null, $periodo);
    $primero->horarios()->create([
        'docente_id' => $docente->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $segundo = cursoDisponible(['docente_id' => usuarioConRol(Rol::Docente)->id], null, $periodo);
    $segundo->horarios()->create([
        'docente_id' => $segundo->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
    ]);

    $estudiante = estudianteMatriculado($primero, $segundo);

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

    $estudiante = estudianteMatriculado($primero, $segundo);

    Inscripcion::factory()->create([
        'curso_id' => $primero->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$segundo->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(2);
});

it('permite inscribirse si dos cursos comparten franja pero sus vigencias no se solapan', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $primero = cursoDisponible(['periodo_id' => $periodo->id]);
    $segundo = cursoDisponible(['periodo_id' => $periodo->id]);

    $primero->horarios()->create([
        'docente_id' => $primero->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-30',
    ]);
    $segundo->horarios()->create([
        'docente_id' => $segundo->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-02-02',
        'fecha_fin' => '2026-02-27',
    ]);

    $estudiante = estudianteMatriculado($primero, $segundo);
    Inscripcion::factory()->create([
        'curso_id' => $primero->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$segundo->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(2);
});

it('rechaza un segundo módulo activo aunque tenga otro horario', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $primero = cursoDisponible([
        'modalidad' => Modalidad::Modular,
        'periodo_id' => $periodo->id,
        'nombre' => 'Módulo de Sistemas',
    ]);
    $segundo = cursoDisponible([
        'modalidad' => Modalidad::Modular,
        'periodo_id' => $periodo->id,
        'nombre' => 'Módulo de Base de Datos',
    ]);

    $primero->horarios()->create([
        'docente_id' => $primero->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-30',
    ]);
    $segundo->horarios()->create([
        'docente_id' => $segundo->docente_id,
        'dia_semana' => 2,
        'hora_inicio' => '10:00',
        'hora_fin' => '12:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-30',
    ]);

    $estudiante = estudianteMatriculado($primero, $segundo);
    Inscripcion::factory()->create([
        'curso_id' => $primero->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$segundo->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'No puedes inscribirte: en modalidad modular solo puedes cursar un módulo a la vez. Retírate de «Módulo de Sistemas» antes de inscribirte a otro.']);

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(1);
});

it('permite otro módulo cuando el primero ya terminó', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $primero = cursoDisponible([
        'modalidad' => Modalidad::Modular,
        'periodo_id' => $periodo->id,
    ]);
    $segundo = cursoDisponible([
        'modalidad' => Modalidad::Modular,
        'periodo_id' => $periodo->id,
    ]);

    $primero->horarios()->create([
        'docente_id' => $primero->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-30',
    ]);
    $segundo->horarios()->create([
        'docente_id' => $segundo->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-02-02',
        'fecha_fin' => '2026-02-27',
    ]);

    $estudiante = estudianteMatriculado($primero, $segundo);
    Inscripcion::factory()->create([
        'curso_id' => $primero->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$segundo->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(2);
});

it('permite inscribirse a otro módulo cuando el anterior fue retirado', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $primero = cursoDisponible([
        'modalidad' => Modalidad::Modular,
        'periodo_id' => $periodo->id,
    ]);
    $segundo = cursoDisponible([
        'modalidad' => Modalidad::Modular,
        'periodo_id' => $periodo->id,
    ]);

    $primero->horarios()->create([
        'docente_id' => $primero->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-30',
    ]);
    $segundo->horarios()->create([
        'docente_id' => $segundo->docente_id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-30',
    ]);

    $estudiante = estudianteMatriculado($primero, $segundo);
    Inscripcion::factory()->retirada()->create([
        'curso_id' => $primero->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$segundo->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(2);
});

it('no permite inscribirse a un curso de un periodo cerrado', function () {
    $curso = cursoDisponible([
        'periodo_id' => Periodo::factory()->create()->id,
    ]);
    $estudiante = estudianteMatriculado($curso);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::count())->toBe(0);
});

it('permite retirarse y volver a inscribirse', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => today()->toDateString(),
        'fecha_fin' => today()->addMonth()->toDateString(),
    ]);
    $curso = cursoDisponible(['periodo_id' => $periodo->id]);
    $estudiante = estudianteMatriculado($curso);

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

it('oculta el retiro cuando el ciclo del curso ya terminó', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => today()->subMonth()->toDateString(),
        'fecha_fin' => today()->addMonth()->toDateString(),
    ]);
    $curso = cursoDisponible(['periodo_id' => $periodo->id]);
    horarioHasta($curso, today()->subDay()->toDateString());
    $estudiante = estudianteMatriculado($curso);
    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->get('/estudiante/inscripciones')
        ->assertOk()
        ->assertSee('Ciclo finalizado')
        ->assertDontSee('Retirarme');
});

it('rechaza retirarse por DELETE cuando el ciclo del curso ya terminó', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => today()->subMonth()->toDateString(),
        'fecha_fin' => today()->addMonth()->toDateString(),
    ]);
    $curso = cursoDisponible(['periodo_id' => $periodo->id]);
    horarioHasta($curso, today()->subDay()->toDateString());
    $estudiante = estudianteMatriculado($curso);
    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->delete("/estudiante/inscripciones/{$inscripcion->id}")
        ->assertSessionHasErrors(['inscripcion' => 'No puedes retirarte: el ciclo del curso ya terminó.']);

    expect($inscripcion->fresh()->estado->value)->toBe('inscrito');
});

it('rechaza retirarse cuando el periodo terminó y el curso no tiene horarios', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => today()->subMonths(3)->toDateString(),
        'fecha_fin' => today()->subDay()->toDateString(),
    ]);
    $curso = cursoDisponible(['periodo_id' => $periodo->id]);
    $estudiante = estudianteMatriculado($curso);
    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->delete("/estudiante/inscripciones/{$inscripcion->id}")
        ->assertSessionHasErrors(['inscripcion' => 'No puedes retirarte: el ciclo del curso ya terminó.']);

    expect($inscripcion->fresh()->estado->value)->toBe('inscrito');
});

it('permite retirarse el último día del ciclo', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => today()->subMonth()->toDateString(),
        'fecha_fin' => today()->addMonth()->toDateString(),
    ]);
    $curso = cursoDisponible(['periodo_id' => $periodo->id]);
    horarioHasta($curso, today()->toDateString());
    $estudiante = estudianteMatriculado($curso);
    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->delete("/estudiante/inscripciones/{$inscripcion->id}")
        ->assertRedirect('/estudiante/inscripciones');

    expect($inscripcion->fresh()->estado->value)->toBe('retirado');
});

it('impide retirar la inscripción de otro estudiante', function () {
    $curso = Curso::factory()->create();
    $dueno = estudianteMatriculado($curso);
    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $dueno->id,
    ]);

    $this->actingAs(estudianteMatriculado($curso))
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

it('muestra la oferta académica al estudiante sin créditos', function () {
    $curso = cursoDisponible(['nombre' => 'Inteligencia Artificial']);

    $this->actingAs(estudianteMatriculado($curso))
        ->get('/estudiante/cursos')
        ->assertOk()
        ->assertSee('Inteligencia Artificial')
        ->assertSee('Inscribirme')
        ->assertDontSee('Créditos');
});

it('oculta de la oferta los cursos cuya inscripción ya cerró', function () {
    $abierto = cursoDisponible(['nombre' => 'Curso con inscripción abierta']);
    $cerrado = cursoDisponible(['nombre' => 'Curso con inscripción cerrada']);
    horarioEntre($cerrado, today()->subDays(3)->toDateString(), today()->addMonth()->toDateString());
    $estudiante = estudianteMatriculado($abierto, $cerrado);

    $this->actingAs($estudiante)
        ->get('/estudiante/cursos')
        ->assertOk()
        ->assertSee('Curso con inscripción abierta')
        ->assertDontSee('Curso con inscripción cerrada');
});

it('oculta de la oferta un curso sin horarios cuando el periodo empezó hace más de dos días', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => today()->subDays(3)->toDateString(),
        'fecha_fin' => today()->addMonth()->toDateString(),
    ]);
    $curso = cursoDisponible(['nombre' => 'Curso sin horarios'], null, $periodo);
    $estudiante = estudianteMatriculado($curso);

    $this->actingAs($estudiante)
        ->get('/estudiante/cursos')
        ->assertOk()
        ->assertDontSee('Curso sin horarios');
});

it('muestra inscripción cerrada en el detalle desde el cuarto día del ciclo', function () {
    $curso = cursoDisponible(['nombre' => 'Curso con detalle cerrado']);
    horarioEntre($curso, today()->subDays(3)->toDateString(), today()->addMonth()->toDateString());

    $this->actingAs(estudianteMatriculado($curso))
        ->get("/estudiante/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Inscripción cerrada')
        ->assertDontSee('Inscribirme');
});

it('muestra el rango de fechas y la hora sin los días de la semana en la oferta', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $curso = cursoDisponible([], null, $periodo);
    horariosDeModulo($curso, ['2026-01-05', '2026-01-12']);

    $this->actingAs(estudianteMatriculado($curso))
        ->get('/estudiante/cursos')
        ->assertOk()
        ->assertSee('Fecha de Inicio: 05/01/2026')
        ->assertSee('Fecha de Fin: 30/01/2026')
        ->assertSee('Horario: 08:00 - 10:00')
        ->assertDontSee('Lunes 08:00 - 10:00');
});

it('muestra el rango de fechas y la hora sin los días de la semana en mis cursos', function () {
    $periodo = Periodo::factory()->vigente()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $curso = cursoDisponible([], null, $periodo);
    horariosDeModulo($curso, ['2026-01-05', '2026-01-12']);
    $estudiante = estudianteMatriculado($curso);
    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->get('/estudiante/inscripciones')
        ->assertOk()
        ->assertSee('Fecha de Inicio: 05/01/2026')
        ->assertSee('Fecha de Fin: 30/01/2026')
        ->assertSee('Horario: 08:00 - 10:00')
        ->assertDontSee('Lunes 08:00 - 10:00');
});

it('oculta los créditos en el detalle del curso para el estudiante', function () {
    $curso = cursoDisponible(['nombre' => 'Base de Datos III']);

    $this->actingAs(estudianteMatriculado($curso))
        ->get("/estudiante/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Base de Datos III')
        ->assertDontSee('Créditos');
});

it('oculta los créditos en el panel del estudiante', function () {
    $curso = cursoDisponible();
    $estudiante = estudianteMatriculado($curso);
    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $this->actingAs($estudiante)
        ->get('/estudiante/dashboard')
        ->assertOk()
        ->assertSee('Cursos inscritos')
        ->assertDontSee('Créditos');
});

it('permite inscribirse cuando el requisito tiene nota final 51', function () {
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);
    $estudiante = estudianteMatriculado($cursoPrevia, $cursoPosterior);

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
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);
    $estudiante = estudianteMatriculado($cursoPrevia, $cursoPosterior);

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
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);
    $estudiante = estudianteMatriculado($cursoPrevia, $cursoPosterior);

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
    $plan = PlanEstudio::factory()->create(['semestres' => 3]);

    $primera = materiaEnPlan($plan, ['codigo' => 'MAT-101', 'nombre' => 'Cálculo I', 'semestre' => 1, 'creditos' => 4]);
    $segunda = materiaEnPlan($plan, ['codigo' => 'OTR-101', 'nombre' => 'Otra previa', 'semestre' => 1, 'creditos' => 4]);
    $posterior = materiaEnPlan($plan, ['codigo' => 'MAT-201', 'nombre' => 'Cálculo II', 'semestre' => 2, 'creditos' => 4]);
    $posterior->requisitosDelPlan($plan)->attach([$primera->id, $segunda->id]);

    $cursoPrimera = cursoDisponible([], $primera);
    $cursoSegunda = cursoDisponible([], $segunda);
    $cursoPosterior = cursoDisponible([], $posterior);
    $estudiante = estudianteMatriculado($cursoPrimera, $cursoSegunda, $cursoPosterior);

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
    [$previa, $posterior] = materiaConRequisito();

    $cursoPrevia = cursoDisponible([], $previa);
    $cursoPosterior = cursoDisponible([], $posterior);
    $estudiante = estudianteMatriculado($cursoPrevia, $cursoPosterior);

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
    $curso = cursoDisponible();
    $curso->update(['materia_id' => null]);
    $estudiante = estudianteMatriculado($curso);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'Este curso aún no tiene una materia del plan asignada. Corrígelo antes de inscribirte.']);

    expect(Inscripcion::where('curso_id', $curso->id)->exists())->toBeFalse();
});

it('valida los requisitos según el plan asignado al estudiante', function () {
    $carrera = Carrera::factory()->create();

    $planExigente = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan exigente',
        'modalidad' => Modalidad::Semestral,
    ]);
    $planFlexible = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Plan flexible',
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);

    $previa = Materia::factory()->create(['codigo' => 'PRE-101', 'nombre' => 'Previa']);
    $posterior = Materia::factory()->create(['codigo' => 'POS-201', 'nombre' => 'Posterior']);

    foreach ([$planExigente, $planFlexible] as $plan) {
        $plan->vincularMateria($previa, 1, 4);
        $plan->vincularMateria($posterior, 2, 4);
    }

    // Solo el plan exigente declara que la materia previa es requisito.
    $posterior->requisitosDelPlan($planExigente)->attach($previa);

    $curso = cursoDisponible(['carrera_id' => $carrera->id], $posterior);
    $curso->planesEstudio()->sync([$planExigente->id, $planFlexible->id]);

    $estudianteExigente = estudianteMatriculado($curso);
    $estudianteExigente->matriculas()
        ->where('carrera_id', $carrera->id)
        ->update(['plan_estudio_id' => $planExigente->id]);

    $this->actingAs($estudianteExigente)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors('curso');

    expect(Inscripcion::where('curso_id', $curso->id)->exists())->toBeFalse();

    $estudianteFlexible = estudianteMatriculado($curso);
    $estudianteFlexible->matriculas()
        ->where('carrera_id', $carrera->id)
        ->update(['plan_estudio_id' => $planFlexible->id]);

    $this->actingAs($estudianteFlexible)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('curso_id', $curso->id)->where('user_id', $estudianteFlexible->id)->exists())->toBeTrue();
});

it('bloquea la inscripción si la matrícula no tiene plan asignado', function () {
    $curso = cursoDisponible();
    $estudiante = estudianteMatriculado($curso);
    $estudiante->matriculas()
        ->where('carrera_id', $curso->carrera_id)
        ->update(['plan_estudio_id' => null]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'Tu matrícula no tiene un plan de estudios asignado. Solicita al administrador que lo asigne antes de inscribirte.']);

    expect(Inscripcion::where('curso_id', $curso->id)->exists())->toBeFalse();
});

it('bloquea la inscripción si el plan asignado no está habilitado en el curso', function () {
    $curso = cursoDisponible();
    $estudiante = estudianteMatriculado($curso);
    $planAjeno = PlanEstudio::factory()->create(['carrera_id' => Carrera::factory()->create()->id]);
    $estudiante->matriculas()
        ->where('carrera_id', $curso->carrera_id)
        ->update(['plan_estudio_id' => $planAjeno->id]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'Tu plan de estudios no está habilitado para este curso.']);

    expect(Inscripcion::where('curso_id', $curso->id)->exists())->toBeFalse();
});

it('muestra el promedio y el enlace a calificaciones en mis cursos', function () {
    $curso = cursoDisponible(['nombre' => 'Diseño de sistemas']);
    $estudiante = estudianteMatriculado($curso);
    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
        'nota_final' => 88,
    ]);

    $this->actingAs($estudiante)
        ->get('/estudiante/inscripciones')
        ->assertOk()
        ->assertSee('Ver calificaciones')
        ->assertSee('href="'.route('estudiante.inscripciones.show', $inscripcion).'"', false)
        ->assertSee('88.00')
        ->assertDontSee('Mis notas');
});

it('muestra las evaluaciones y el promedio final del curso', function () {
    $curso = cursoDisponible(['nombre' => 'Diseño de sistemas']);
    $estudiante = estudianteMatriculado($curso);
    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
        'nota_final' => 88,
    ]);
    $evaluacion = CursoEvaluacion::factory()->create([
        'curso_id' => $curso->id,
        'nombre' => 'Primer parcial',
        'ponderacion' => 40,
    ]);
    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $evaluacion->id,
        'nota' => 90,
        'fecha' => '2026-01-15',
    ]);

    $this->actingAs($estudiante)
        ->get("/estudiante/inscripciones/{$inscripcion->id}")
        ->assertOk()
        ->assertSee('Calificaciones de Diseño de sistemas')
        ->assertSee('Promedio final')
        ->assertSee('88.00')
        ->assertSee('Primer parcial')
        ->assertSee('40.00%')
        ->assertSee('90.00')
        ->assertSee('15/01/2026')
        ->assertDontSee('Créditos');
});

it('impide ver las calificaciones de otro estudiante', function () {
    $curso = cursoDisponible();
    $dueno = estudianteMatriculado($curso);
    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $dueno->id,
    ]);

    $this->actingAs(estudianteMatriculado($curso))
        ->get("/estudiante/inscripciones/{$inscripcion->id}")
        ->assertForbidden();
});

it('no usa un plan alternativo cuando el asignado no está habilitado en el curso', function () {
    $carrera = Carrera::factory()->create();
    $planAsignado = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral,
    ]);
    $planHabilitado = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);

    $previa = materiaEnPlan($planHabilitado, [
        'codigo' => 'PRE-101', 'nombre' => 'Previa', 'semestre' => 1, 'creditos' => 4,
    ]);
    $posterior = materiaEnPlan($planHabilitado, [
        'codigo' => 'POS-201', 'nombre' => 'Posterior', 'semestre' => 2, 'creditos' => 4,
    ]);
    $posterior->requisitosDelPlan($planHabilitado)->attach($previa);

    $curso = cursoDisponible(['carrera_id' => $carrera->id], $posterior);
    $curso->planesEstudio()->syncWithoutDetaching([$planHabilitado->id]);
    $estudiante = estudianteMatriculado($curso);
    $estudiante->matriculas()
        ->where('carrera_id', $carrera->id)
        ->update(['plan_estudio_id' => $planAsignado->id]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'Tu plan de estudios no está habilitado para este curso.']);

    expect(Inscripcion::where('curso_id', $curso->id)->exists())->toBeFalse();
});
