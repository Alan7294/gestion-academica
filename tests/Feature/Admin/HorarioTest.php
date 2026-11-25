<?php

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use App\Services\HorarioService;
use Database\Seeders\RolSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

function cursoConDocente(array $atributos = []): Curso
{
    $docente = usuarioConRol(Rol::Docente);

    return Curso::factory()->create(array_merge([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->create()->id,
    ], $atributos));
}

function cursoModular(array $atributos = []): Curso
{
    $plan = PlanEstudio::factory()->create([
        'nombre' => 'Plan modular '.fake()->unique()->word(),
        'modalidad' => Modalidad::Modular,
    ]);
    $materia = materiaEnPlan($plan, [
        'codigo' => 'MOD-'.fake()->unique()->numerify('###'),
        'nombre' => 'Módulo de prueba',
    ]);

    return cursoConDocente(array_merge([
        'carrera_id' => $plan->carrera_id,
        'plan_estudio_id' => $plan->id,
        'modalidad' => Modalidad::Modular,
        'materia_id' => $materia->id,
        'nombre' => $materia->nombre,
    ], $atributos));
}

it('registra un horario válido con la vigencia del periodo', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-06-30',
    ]);
    $curso = cursoConDocente(['periodo_id' => $periodo->id]);

    app(HorarioService::class)->agregar($curso, [
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'aula' => 'A-101',
    ]);

    $horario = $curso->horarios()->firstOrFail();
    expect($curso->horarios()->count())->toBe(1)
        ->and($horario->periodo_id)->toBe($periodo->id)
        ->and($horario->fecha_inicio->toDateString())->toBe('2026-01-05')
        ->and($horario->fecha_fin->toDateString())->toBe('2026-06-30');
});

it('rechaza un horario que se cruza con otra clase del docente', function () {
    $periodo = Periodo::factory()->create();
    $curso = cursoConDocente(['periodo_id' => $periodo->id]);
    $otroCurso = cursoConDocente([
        'docente_id' => $curso->docente_id,
        'periodo_id' => $periodo->id,
    ]);

    $servicio = app(HorarioService::class);

    $servicio->agregar($curso, [
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    expect(fn () => $servicio->agregar($otroCurso, [
        'dia_semana' => 1,
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
    ]))->toThrow(ValidationException::class, 'El docente ya tiene otra clase en ese día y horario.');
});

it('permite el mismo día y franja en periodos distintos', function () {
    $docente = usuarioConRol(Rol::Docente);
    $primero = cursoConDocente([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->create([
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-04-30',
        ])->id,
    ]);
    $segundo = cursoConDocente([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->create([
            'fecha_inicio' => '2026-05-04',
            'fecha_fin' => '2026-08-28',
        ])->id,
    ]);

    $servicio = app(HorarioService::class);
    $servicio->agregar($primero, ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $servicio->agregar($segundo, ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);

    expect(Horario::count())->toBe(2);
});

it('permite horarios consecutivos sin superposición', function () {
    $periodo = Periodo::factory()->create();
    $curso = cursoConDocente(['periodo_id' => $periodo->id]);
    $otroCurso = cursoConDocente([
        'docente_id' => $curso->docente_id,
        'periodo_id' => $periodo->id,
    ]);

    $servicio = app(HorarioService::class);

    $servicio->agregar($curso, ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $servicio->agregar($otroCurso, ['dia_semana' => 1, 'hora_inicio' => '10:00', 'hora_fin' => '12:00']);

    expect(Horario::count())->toBe(2);
});

it('permite el mismo horario en días distintos', function () {
    $periodo = Periodo::factory()->create();
    $curso = cursoConDocente(['periodo_id' => $periodo->id]);
    $otroCurso = cursoConDocente([
        'docente_id' => $curso->docente_id,
        'periodo_id' => $periodo->id,
    ]);

    $servicio = app(HorarioService::class);

    $servicio->agregar($curso, ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $servicio->agregar($otroCurso, ['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);

    expect(Horario::count())->toBe(2);
});

it('la base de datos impide el choque aunque se salte el servicio', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-06-30',
    ]);
    $curso = cursoConDocente(['periodo_id' => $periodo->id]);

    $datos = [
        'curso_id' => $curso->id,
        'docente_id' => $curso->docente_id,
        'periodo_id' => $periodo->id,
        'dia_semana' => 3,
        'hora_inicio' => '14:00',
        'hora_fin' => '16:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-06-30',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('horarios')->insert($datos);

    expect(fn () => DB::table('horarios')->insert(array_merge($datos, [
        'hora_inicio' => '15:00',
        'hora_fin' => '17:00',
    ])))->toThrow(QueryException::class);
});

it('la base de datos impide dos sesiones modulares el mismo día y franja', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $curso = cursoModular(['periodo_id' => $periodo->id]);

    $datos = [
        'curso_id' => $curso->id,
        'docente_id' => $curso->docente_id,
        'periodo_id' => $periodo->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-30',
        'fecha_sesion' => '2026-01-05',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('horarios')->insert($datos);

    expect(fn () => DB::table('horarios')->insert(array_merge($datos, [
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
    ])))->toThrow(QueryException::class);
});

it('rechaza un horario con hora de fin anterior al inicio', function () {
    $curso = cursoConDocente();

    expect(fn () => app(HorarioService::class)->agregar($curso, [
        'dia_semana' => 1,
        'hora_inicio' => '10:00',
        'hora_fin' => '09:00',
    ]))->toThrow(ValidationException::class, 'La hora de fin debe ser posterior a la hora de inicio.');
});

it('exige un docente antes de registrar horarios', function () {
    $curso = Curso::factory()->create(['docente_id' => null]);

    expect(fn () => app(HorarioService::class)->agregar($curso, [
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]))->toThrow(ValidationException::class, 'El curso debe tener un docente asignado antes de registrar horarios.');
});

it('crea veinte sesiones de lunes a viernes para un módulo de veinte días hábiles', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $curso = cursoModular(['periodo_id' => $periodo->id]);

    app(HorarioService::class)->agregar($curso, [
        'fecha_inicio' => '2026-01-05',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $horarios = $curso->horarios()->get();
    expect($horarios)->toHaveCount(20)
        ->and($horarios->pluck('dia_semana')->map(fn ($dia) => $dia->value)->unique()->values()->all())->toBe([1, 2, 3, 4, 5])
        ->and($horarios->every(fn ($horario) => $horario->fecha_inicio->toDateString() === '2026-01-05'))->toBeTrue()
        ->and($horarios->every(fn ($horario) => $horario->fecha_fin->toDateString() === '2026-01-30'))->toBeTrue();
});

it('rechaza un módulo que inicia en fin de semana', function () {
    $curso = cursoModular();

    expect(fn () => app(HorarioService::class)->agregar($curso, [
        'fecha_inicio' => '2026-01-10',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]))->toThrow(ValidationException::class, 'El módulo debe iniciar en un día hábil (lunes a viernes).');

    expect(Horario::count())->toBe(0);
});

it('rechaza un módulo que no cabe dentro del periodo', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-05',
        'fecha_fin' => '2026-01-20',
    ]);
    $curso = cursoModular(['periodo_id' => $periodo->id]);

    expect(fn () => app(HorarioService::class)->agregar($curso, [
        'fecha_inicio' => '2026-01-05',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]))->toThrow(ValidationException::class, 'Los 20 días hábiles del módulo deben estar dentro del periodo.');
});

it('revierte todas las sesiones si el módulo choca con otra clase del docente', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $docente = usuarioConRol(Rol::Docente);
    $primero = cursoModular(['docente_id' => $docente->id, 'periodo_id' => $periodo->id]);
    $segundo = cursoModular(['docente_id' => $docente->id, 'periodo_id' => $periodo->id]);

    $servicio = app(HorarioService::class);
    $servicio->agregar($primero, [
        'fecha_inicio' => '2026-01-05',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    expect(fn () => $servicio->agregar($segundo, [
        'fecha_inicio' => '2026-01-12',
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
    ]))->toThrow(ValidationException::class, 'El docente ya tiene otra clase en ese día y horario.');

    expect($segundo->horarios()->count())->toBe(0)
        ->and($primero->horarios()->count())->toBe(20);
});

it('permite módulos del mismo docente que no se solapan', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $docente = usuarioConRol(Rol::Docente);
    $primero = cursoModular(['docente_id' => $docente->id, 'periodo_id' => $periodo->id]);
    $segundo = cursoModular(['docente_id' => $docente->id, 'periodo_id' => $periodo->id]);

    $servicio = app(HorarioService::class);
    $servicio->agregar($primero, ['fecha_inicio' => '2026-01-05', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $servicio->agregar($segundo, ['fecha_inicio' => '2026-02-09', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);

    expect(Horario::count())->toBe(40);
});

it('exige la fecha de inicio en el formulario de un curso modular', function () {
    $curso = cursoModular();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post("/admin/cursos/{$curso->id}/horarios", [
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ])
        ->assertSessionHasErrors(['fecha_inicio', 'dia_semana']);

    expect(Horario::count())->toBe(0);
});

it('registra un módulo desde el formulario administrativo', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $curso = cursoModular(['periodo_id' => $periodo->id]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post("/admin/cursos/{$curso->id}/horarios", [
            'fecha_inicio' => '2026-01-05',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Módulo registrado correctamente.');

    expect($curso->horarios()->count())->toBe(20);
});

it('exige el día de la semana en el formulario de un curso semestral', function () {
    $curso = cursoConDocente();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->post("/admin/cursos/{$curso->id}/horarios", [
            'fecha_inicio' => '2026-01-05',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ])
        ->assertSessionHasErrors(['dia_semana', 'fecha_inicio']);

    expect(Horario::count())->toBe(0);
});

it('elimina un módulo completo desde el detalle del curso', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $curso = cursoModular(['periodo_id' => $periodo->id]);

    app(HorarioService::class)->agregar($curso, [
        'fecha_inicio' => '2026-01-05',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->delete("/admin/cursos/{$curso->id}/horarios/{$curso->horarios()->firstOrFail()->id}")
        ->assertRedirect()
        ->assertSessionHas('status', 'Módulo eliminado correctamente.');

    expect(Horario::count())->toBe(0);
});
