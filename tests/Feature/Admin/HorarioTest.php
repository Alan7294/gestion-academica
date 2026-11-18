<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Horario;
use App\Services\HorarioService;
use Database\Seeders\RolSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

function cursoConDocente(): Curso
{
    $docente = usuarioConRol(Rol::Docente);

    return Curso::factory()->create(['docente_id' => $docente->id]);
}

it('registra un horario válido', function () {
    $curso = cursoConDocente();

    app(HorarioService::class)->agregar($curso, [
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'aula' => 'A-101',
    ]);

    expect($curso->horarios()->count())->toBe(1);
});

it('rechaza un horario que se cruza con otra clase del docente', function () {
    $curso = cursoConDocente();
    $otroCurso = Curso::factory()->create(['docente_id' => $curso->docente_id]);

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
    ]))->toThrow(Illuminate\Validation\ValidationException::class);
});

it('permite horarios consecutivos sin superposición', function () {
    $curso = cursoConDocente();
    $otroCurso = Curso::factory()->create(['docente_id' => $curso->docente_id]);

    $servicio = app(HorarioService::class);

    $servicio->agregar($curso, ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $servicio->agregar($otroCurso, ['dia_semana' => 1, 'hora_inicio' => '10:00', 'hora_fin' => '12:00']);

    expect(Horario::count())->toBe(2);
});

it('permite el mismo horario en días distintos', function () {
    $curso = cursoConDocente();
    $otroCurso = Curso::factory()->create(['docente_id' => $curso->docente_id]);

    $servicio = app(HorarioService::class);

    $servicio->agregar($curso, ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $servicio->agregar($otroCurso, ['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);

    expect(Horario::count())->toBe(2);
});

it('la base de datos impide el choque aunque se salte el servicio', function () {
    $curso = cursoConDocente();

    DB::table('horarios')->insert([
        'curso_id' => $curso->id,
        'docente_id' => $curso->docente_id,
        'dia_semana' => 3,
        'hora_inicio' => '14:00',
        'hora_fin' => '16:00',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('horarios')->insert([
        'curso_id' => $curso->id,
        'docente_id' => $curso->docente_id,
        'dia_semana' => 3,
        'hora_inicio' => '15:00',
        'hora_fin' => '17:00',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rechaza un horario con hora de fin anterior al inicio', function () {
    $curso = cursoConDocente();

    expect(fn () => app(HorarioService::class)->agregar($curso, [
        'dia_semana' => 1,
        'hora_inicio' => '10:00',
        'hora_fin' => '09:00',
    ]))->toThrow(Illuminate\Validation\ValidationException::class);
});

it('exige un docente antes de registrar horarios', function () {
    $curso = Curso::factory()->create(['docente_id' => null]);

    expect(fn () => app(HorarioService::class)->agregar($curso, [
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]))->toThrow(Illuminate\Validation\ValidationException::class);
});
