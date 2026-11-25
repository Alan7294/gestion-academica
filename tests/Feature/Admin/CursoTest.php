<?php

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\CursoEvaluacion;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

function datosCurso(array $sobrescribir = []): array
{
    $carreraId = $sobrescribir['carrera_id'] ?? Carrera::factory()->create()->id;
    $periodoId = $sobrescribir['periodo_id'] ?? Periodo::factory()->create()->id;
    $docenteId = $sobrescribir['docente_id'] ?? usuarioConRol(Rol::Docente)->id;
    $modalidad = $sobrescribir['modalidad'] ?? Modalidad::Semestral->value;
    $modalidad = $modalidad instanceof Modalidad ? $modalidad : Modalidad::from($modalidad);

    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carreraId,
        'nombre' => 'Plan '.fake()->unique()->word(),
        'modalidad' => $modalidad,
    ]);
    $materia = materiaEnPlan($plan, [
        'codigo' => 'SIS-101',
        'nombre' => 'Programación IV',
        'semestre' => 4,
        'creditos' => 4,
    ]);

    return array_merge([
        'carrera_id' => $carreraId,
        'plan_estudio_id' => $plan->id,
        'modalidad' => $modalidad->value,
        'materia_id' => $materia->id,
        'periodo_id' => $periodoId,
        'docente_id' => $docenteId,
        'cupo' => 30,
        'activo' => '1',
        'aula' => 'Aula 101',
        'horarios' => [
            ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
        ],
    ], $sobrescribir);
}

function cursoDePrueba(array $datos = []): Curso
{
    // Los campos de horario pertenecen al formulario, no a la tabla cursos.
    return Curso::factory()->create(Arr::except($datos, [
        'aula',
        'horarios',
        'fecha_inicio',
        'hora_inicio',
        'hora_fin',
    ]));
}

it('permite al administrador registrar un curso', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso(['docente_id' => $docente->id]))
        ->assertRedirect();

    $curso = Curso::firstOrFail();
    expect($curso->nombre)->toBe('Programación IV')
        ->and($curso->docente_id)->toBe($docente->id)
        ->and($curso->materia_id)->not->toBeNull();
});

it('crea el curso con varios días y horarios semestrales', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $datos = datosCurso([
        'docente_id' => $docente->id,
        'horarios' => [
            ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
            ['dia_semana' => 3, 'hora_inicio' => '14:00', 'hora_fin' => '16:00'],
        ],
    ]);

    $this->actingAs($admin)
        ->post('/admin/cursos', $datos)
        ->assertRedirect()
        ->assertSessionHas('status', 'Curso y horario registrados correctamente.');

    $curso = Curso::firstOrFail();
    $horarios = $curso->horarios()->get();

    expect($curso->modalidad)->toBe(Modalidad::Semestral)
        ->and($horarios)->toHaveCount(2)
        ->and($horarios->pluck('dia_semana')->map(fn ($dia) => $dia->value)->all())->toBe([1, 3])
        ->and($horarios->every(fn ($horario) => $horario->aula === 'Aula 101'))->toBeTrue()
        ->and($horarios->every(fn ($horario) => $horario->docente_id === $docente->id))->toBeTrue()
        ->and($horarios->every(fn ($horario) => $horario->fecha_inicio->toDateString() === $curso->periodo->fecha_inicio->toDateString()))->toBeTrue()
        ->and($horarios->every(fn ($horario) => $horario->fecha_fin->toDateString() === $curso->periodo->fecha_fin->toDateString()))->toBeTrue();
});

it('crea el curso modular con veinte sesiones de lunes a viernes', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $datos = datosCurso([
        'docente_id' => $docente->id,
        'periodo_id' => $periodo->id,
        'modalidad' => Modalidad::Modular->value,
    ]);
    unset($datos['horarios']);
    $datos['fecha_inicio'] = '2026-01-05';
    $datos['hora_inicio'] = '08:00';
    $datos['hora_fin'] = '10:00';

    $this->actingAs($admin)
        ->post('/admin/cursos', $datos)
        ->assertRedirect()
        ->assertSessionHas('status', 'Curso y horario registrados correctamente.');

    $curso = Curso::firstOrFail();

    $horarios = $curso->horarios()->get();

    expect($curso->modalidad)->toBe(Modalidad::Modular)
        ->and($horarios)->toHaveCount(20)
        ->and($horarios->first()->fecha_fin->toDateString())->toBe('2026-01-30')
        ->and($horarios->every(fn ($horario) => $horario->aula === 'Aula 101'))->toBeTrue();
});

it('rechaza una modalidad que no coincide con el plan elegido', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso();

    $this->actingAs($admin)
        ->post('/admin/cursos', array_merge($datos, [
            'modalidad' => Modalidad::Modular->value,
        ]))
        ->assertSessionHasErrors(['modalidad' => 'La modalidad debe coincidir con la del plan seleccionado.']);

    expect(Curso::count())->toBe(0);
});

it('rechaza el curso sin docente, aula ni horario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso();
    unset($datos['docente_id'], $datos['aula'], $datos['horarios']);

    $this->actingAs($admin)
        ->post('/admin/cursos', $datos)
        ->assertSessionHasErrors(['docente_id', 'aula', 'horarios']);

    expect(Curso::count())->toBe(0);
});

it('rechaza días repetidos en el horario semestral', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso([
        'horarios' => [
            ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
            ['dia_semana' => 1, 'hora_inicio' => '14:00', 'hora_fin' => '16:00'],
        ],
    ]);

    $this->actingAs($admin)
        ->post('/admin/cursos', $datos)
        ->assertSessionHasErrors('horarios.1.dia_semana');

    expect(Curso::count())->toBe(0);
});

it('revierte el curso cuando su horario inicial choca con otra clase del docente', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    $otroCurso = Curso::factory()->create([
        'periodo_id' => $periodo->id,
        'docente_id' => $docente->id,
    ]);
    $otroCurso->horarios()->create([
        'docente_id' => $docente->id,
        'dia_semana' => 1,
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
    ]);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso([
            'periodo_id' => $periodo->id,
            'docente_id' => $docente->id,
        ]))
        ->assertSessionHasErrors('hora_inicio');

    expect(Curso::count())->toBe(1)
        ->and(Horario::count())->toBe(1);
});

it('deriva el nombre, el semestre y los créditos del plan de estudios', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso();

    $this->actingAs($admin)
        ->post('/admin/cursos', $datos)
        ->assertRedirect();

    $curso = Curso::firstOrFail();
    $materia = Materia::findOrFail($datos['materia_id']);
    $pivote = DB::table('materia_plan_estudio')
        ->where('plan_estudio_id', $datos['plan_estudio_id'])
        ->where('materia_id', $materia->id)
        ->firstOrFail();

    expect($curso->plan_estudio_id)->toBe($datos['plan_estudio_id'])
        ->and($curso->nombre)->toBe($materia->nombre)
        ->and($curso->semestre)->toBe((int) $pivote->semestre)
        ->and($curso->creditos)->toBe((int) $pivote->creditos);
});

it('exige que el docente asignado tenga el rol docente', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $estudiante = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso(['docente_id' => $estudiante->id]))
        ->assertSessionHasErrors('docente_id');

    expect(Curso::count())->toBe(0);
});

it('permite varios cursos de la misma materia en un mismo periodo', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso(['docente_id' => usuarioConRol(Rol::Docente)->id]);

    $this->actingAs($admin)
        ->post('/admin/cursos', $datos)
        ->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/cursos', array_merge($datos, [
            'docente_id' => usuarioConRol(Rol::Docente)->id,
        ]))
        ->assertRedirect();

    expect(Curso::count())->toBe(2)
        ->and(Curso::pluck('materia_id')->unique())->toHaveCount(1);
});

it('mantiene la materia del curso en periodos distintos', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso();
    $anterior = Periodo::factory()->create([
        'fecha_inicio' => '2025-01-01',
        'fecha_fin' => '2025-12-31',
    ]);
    $actual = Periodo::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);

    $this->actingAs($admin)
        ->post('/admin/cursos', array_merge($datos, ['periodo_id' => $anterior->id]))
        ->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/cursos', array_merge($datos, ['periodo_id' => $actual->id]))
        ->assertRedirect();

    expect(Curso::count())->toBe(2)
        ->and(Curso::pluck('materia_id')->unique())->toHaveCount(1);
});

it('busca los cursos por el código de la materia', function () {
    $admin = usuarioConRol(Rol::Administrador);
    cursoDePrueba(datosCurso());

    $this->actingAs($admin)
        ->get('/admin/cursos?busqueda=SIS-101')
        ->assertOk()
        ->assertSee('SIS-101');
});

it('muestra los días, el horario y el aula de cada curso en el listado', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $curso = cursoDePrueba(datosCurso(['docente_id' => $docente->id]));

    foreach ([1 => ['08:00', '10:00'], 3 => ['14:00', '16:00']] as $dia => [$inicio, $fin]) {
        $curso->horarios()->create([
            'docente_id' => $docente->id,
            'dia_semana' => $dia,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'aula' => 'Aula 101',
        ]);
    }

    $this->actingAs($admin)
        ->get('/admin/cursos')
        ->assertOk()
        ->assertSee('Lunes, Miércoles')
        ->assertSee('08:00 - 10:00, 14:00 - 16:00')
        ->assertSee('Aula 101');
});

it('rechaza un plan que no pertenece a la carrera del curso', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $otraCarrera = Carrera::factory()->create();
    $otroPlan = PlanEstudio::factory()->create(['carrera_id' => $otraCarrera->id]);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso(['plan_estudio_id' => $otroPlan->id]))
        ->assertSessionHasErrors('plan_estudio_id');

    expect(Curso::count())->toBe(0);
});

it('rechaza una materia que no pertenece al plan elegido', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso();
    $materiaFueraDelPlan = Materia::factory()->create([
        'carrera_id' => $datos['carrera_id'],
        'codigo' => 'SIS-999',
        'nombre' => 'Materia fuera del plan',
    ]);

    $this->actingAs($admin)
        ->post('/admin/cursos', array_merge($datos, ['materia_id' => $materiaFueraDelPlan->id]))
        ->assertSessionHasErrors('materia_id');

    expect(Curso::count())->toBe(0);
});

it('rechaza una materia de otra carrera', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $otraCarrera = Carrera::factory()->create();
    $otroPlan = PlanEstudio::factory()->create(['carrera_id' => $otraCarrera->id]);
    $otraMateria = materiaEnPlan($otroPlan, ['codigo' => 'OTR-101', 'nombre' => 'Otra materia']);

    $this->actingAs($admin)
        ->post('/admin/cursos', datosCurso(['materia_id' => $otraMateria->id]))
        ->assertSessionHasErrors('materia_id');

    expect(Curso::count())->toBe(0);
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

    $curso = cursoDePrueba($datos);
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

it('congela el plan y el periodo de un curso que ya tiene horarios', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $datos = datosCurso(['docente_id' => $docente->id]);
    $curso = cursoDePrueba($datos);
    $curso->horarios()->create([
        'docente_id' => $docente->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $otroPlan = PlanEstudio::factory()->create([
        'carrera_id' => $datos['carrera_id'],
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);
    $otraMateria = materiaEnPlan($otroPlan, [
        'codigo' => 'SIS-303',
        'nombre' => 'Otra materia del plan',
    ]);

    $this->actingAs($admin)
        ->put("/admin/cursos/{$curso->id}", array_merge($datos, [
            'plan_estudio_id' => $otroPlan->id,
            'materia_id' => $otraMateria->id,
            'periodo_id' => Periodo::factory()->create()->id,
        ]))
        ->assertSessionHasErrors(['plan_estudio_id', 'periodo_id']);

    $curso->refresh();
    expect($curso->plan_estudio_id)->toBe($datos['plan_estudio_id'])
        ->and($curso->periodo_id)->toBe($datos['periodo_id']);
});

it('congela la modalidad de un curso que ya tiene horarios', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $datos = datosCurso(['docente_id' => $docente->id]);
    $curso = cursoDePrueba($datos);
    $curso->horarios()->create([
        'docente_id' => $docente->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $planModular = PlanEstudio::factory()->create([
        'carrera_id' => $datos['carrera_id'],
        'modalidad' => Modalidad::Modular,
    ]);
    $materiaModular = materiaEnPlan($planModular, [
        'codigo' => 'SIS-303',
        'nombre' => 'Otra materia del plan modular',
    ]);

    $this->actingAs($admin)
        ->put("/admin/cursos/{$curso->id}", array_merge($datos, [
            'modalidad' => Modalidad::Modular->value,
            'plan_estudio_id' => $planModular->id,
            'materia_id' => $materiaModular->id,
        ]))
        ->assertSessionHasErrors(['modalidad' => 'No puedes cambiar la modalidad de un curso que ya tiene horarios. Elimínalos primero.']);

    expect($curso->fresh()->modalidad)->toBe(Modalidad::Semestral);
});

it('actualiza los datos derivados al cambiar el plan y la materia', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso();
    $curso = cursoDePrueba($datos);

    $otroPlan = PlanEstudio::factory()->create([
        'carrera_id' => $datos['carrera_id'],
        'modalidad' => Modalidad::Semestral,
        'activo' => false,
    ]);
    $otraMateria = materiaEnPlan($otroPlan, [
        'codigo' => 'SIS-202',
        'nombre' => 'Base de Datos',
        'semestre' => 3,
        'creditos' => 2,
    ]);

    $this->actingAs($admin)
        ->put("/admin/cursos/{$curso->id}", array_merge($datos, [
            'plan_estudio_id' => $otroPlan->id,
            'materia_id' => $otraMateria->id,
        ]))
        ->assertRedirect();

    $curso->refresh();
    expect($curso->plan_estudio_id)->toBe($otroPlan->id)
        ->and($curso->nombre)->toBe('Base de Datos')
        ->and($curso->semestre)->toBe(3)
        ->and($curso->creditos)->toBe(2);
});

it('impide cambiar la identidad de un curso que ya tiene inscripciones', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $datos = datosCurso();
    $curso = cursoDePrueba($datos);
    $curso->inscripciones()->create(['user_id' => usuarioConRol(Rol::Estudiante)->id]);

    $otraMateria = materiaEnPlan(PlanEstudio::findOrFail($datos['plan_estudio_id']), [
        'codigo' => 'SIS-102',
        'nombre' => 'Otra materia del plan',
        'semestre' => 4,
        'creditos' => 4,
    ]);

    $this->actingAs($admin)
        ->put("/admin/cursos/{$curso->id}", array_merge($datos, [
            'materia_id' => $otraMateria->id,
        ]))
        ->assertSessionHasErrors('materia_id');

    $curso->refresh();
    expect($curso->materia_id)->toBe($datos['materia_id']);
});

it('impide eliminar un curso que ya tiene inscripciones', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $curso = cursoDePrueba(datosCurso());
    $curso->inscripciones()->create(['user_id' => usuarioConRol(Rol::Estudiante)->id]);

    $this->actingAs($admin)
        ->delete("/admin/cursos/{$curso->id}")
        ->assertSessionHasErrors('curso');

    expect(Curso::count())->toBe(1);
});

it('muestra el formulario sin los campos derivados del plan', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/cursos/create')
        ->assertOk()
        ->assertSee('Plan de estudios')
        ->assertSee('Modalidad del curso')
        ->assertSee('Agregar otro día')
        ->assertDontSee('name="nombre"', false)
        ->assertDontSee('name="semestre"', false)
        ->assertDontSee('name="creditos"', false)
        ->assertDontSee('name="codigo"', false);
});

it('permite eliminar un curso sin inscripciones junto con sus horarios y evaluaciones', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $curso = cursoDePrueba(datosCurso());
    $curso->horarios()->create([
        'docente_id' => usuarioConRol(Rol::Docente)->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);
    $curso->evaluaciones()->create(['nombre' => 'Primer parcial', 'ponderacion' => 100]);

    $this->actingAs($admin)
        ->delete("/admin/cursos/{$curso->id}")
        ->assertRedirect(route('admin.cursos.index'));

    expect(Curso::count())->toBe(0)
        ->and(Horario::count())->toBe(0)
        ->and(CursoEvaluacion::count())->toBe(0);
});

it('ofrece eliminar el curso solo cuando no tiene inscripciones', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $curso = cursoDePrueba(datosCurso());

    $this->actingAs($admin)
        ->get("/admin/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Eliminar curso')
        ->assertSee('¿Eliminar este curso?');

    $curso->inscripciones()->create(['user_id' => usuarioConRol(Rol::Estudiante)->id]);

    $this->actingAs($admin)
        ->get("/admin/cursos/{$curso->id}")
        ->assertOk()
        ->assertDontSee('Eliminar curso')
        ->assertSee('No se puede eliminar: tiene inscripciones');
});

it('muestra el plan de estudios en el detalle del curso', function () {
    $datos = datosCurso();
    $curso = cursoDePrueba($datos);
    $plan = PlanEstudio::findOrFail($datos['plan_estudio_id']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Plan de estudios')
        ->assertSee($plan->nombre);
});

it('muestra el detalle del curso con su horario', function () {
    $curso = Curso::factory()->create(['nombre' => 'Base de Datos II']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/cursos/{$curso->id}")
        ->assertOk()
        ->assertSee('Base de Datos II')
        ->assertSee('Horario del curso');
});
