<?php

use App\Enums\EstadoInscripcion;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use App\Reports\Estudiante\HistorialAcademicoReporte;
use App\Reports\FiltrosReporte;
use App\Services\ConvalidacionService;
use Database\Seeders\RolSeeder;

function cursoDeMateria(Materia $materia): Curso
{
    $periodo = Periodo::actual() ?? Periodo::factory()->vigente()->create();

    return Curso::factory()->conMateria($materia)->create([
        'periodo_id' => $periodo->id,
        'docente_id' => usuarioConRol(Rol::Docente)->id,
        'cupo' => 30,
        'activo' => true,
    ]);
}

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('acepta un requisito aprobado en otra carrera declarado como equivalente', function () {
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();
    $planB = PlanEstudio::factory()->create(['carrera_id' => $carreraB->id]);

    $origen = materiaEnCarrera($carreraA);
    $destino = materiaEnPlan($planB, ['codigo' => 'DES-101', 'nombre' => 'Materia destino', 'semestre' => 1]);
    $posterior = materiaEnPlan($planB, ['codigo' => 'POS-201', 'nombre' => 'Materia posterior', 'semestre' => 2]);
    $posterior->requisitosDelPlan($planB)->attach($destino);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoPosterior = cursoDeMateria($posterior);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoPosterior);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 75,
    ]);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoPosterior->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('curso_id', $cursoPosterior->id)->exists())->toBeTrue();
});

it('muestra el aviso sin bloquear la inscripción en una materia ya convalidada', function () {
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraA, [
        'codigo' => 'ORI-101',
        'nombre' => 'Materia origen',
    ]);
    $destino = materiaEnCarrera($carreraB, [
        'codigo' => 'DES-201',
        'nombre' => 'Materia destino',
    ]);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoDestino = cursoDeMateria($destino);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoDestino);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 80,
    ]);

    $this->actingAs($estudiante)
        ->get(route('estudiante.cursos.index'))
        ->assertOk()
        ->assertSee('Ya tienes esta materia convalidada por equivalencia.');

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoDestino->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('curso_id', $cursoDestino->id)->exists())->toBeTrue();
});

it('muestra la convalidación con la nota y la carrera de origen en mis calificaciones', function () {
    $carreraA = Carrera::factory()->create(['nombre' => 'Derecho']);
    $carreraB = Carrera::factory()->create(['nombre' => 'Ingeniería Civil']);
    $origen = materiaEnCarrera($carreraA, [
        'codigo' => 'ORI-101',
        'nombre' => 'Cálculo aplicado',
    ]);
    $destino = materiaEnCarrera($carreraB, [
        'codigo' => 'DES-201',
        'nombre' => 'Matemáticas II',
    ]);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoDestino = cursoDeMateria($destino);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoDestino);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 88,
    ]);

    $this->actingAs($estudiante)
        ->get(route('estudiante.notas.index'))
        ->assertOk()
        ->assertSee('Convalidada')
        ->assertSee('ORI-101 - Cálculo aplicado (Derecho)')
        ->assertSee('88.00');
});

it('no muestra la convalidación si el estudiante cursó la materia destino', function () {
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraA);
    $destino = materiaEnCarrera($carreraB);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoDestino = cursoDeMateria($destino);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoDestino);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 88,
    ]);
    Inscripcion::factory()->create([
        'curso_id' => $cursoDestino->id,
        'user_id' => $estudiante->id,
        'estado' => EstadoInscripcion::Inscrito,
        'nota_final' => null,
    ]);

    expect(app(ConvalidacionService::class)->paraEstudiante($estudiante))->toBeEmpty();
});

it('registra la convalidación en la vista previa del historial académico', function () {
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraA, [
        'codigo' => 'ORI-101',
        'nombre' => 'Materia origen',
    ]);
    $destino = materiaEnCarrera($carreraB, [
        'codigo' => 'DES-201',
        'nombre' => 'Materia destino',
    ]);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoDestino = cursoDeMateria($destino);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoDestino);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 90,
    ]);

    $this->actingAs($estudiante)
        ->get(route('estudiante.reportes.historial.preview'))
        ->assertOk()
        ->assertSee('Convalidada')
        ->assertSee('Aprobado por equivalencia: ORI-101 - Materia origen');
});

it('rechaza equivalencias entre materias de la misma carrera', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carrera = Carrera::factory()->create();
    $materia = materiaEnCarrera($carrera);
    $otra = materiaEnCarrera($carrera);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carrera->id,
            'carrera_origen_id' => $carrera->id,
            'materia_id' => $materia->id,
            'equivalente_id' => $otra->id,
        ])
        ->assertSessionHasErrors(['carrera_origen_id' => 'La carrera origen debe ser distinta de la carrera destino.']);

    expect(MateriaEquivalencia::count())->toBe(0);
});

it('muestra los créditos del plan en la convalidación del historial', function () {
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $planDestino = PlanEstudio::factory()->create(['carrera_id' => $carreraDestino->id]);

    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnPlan($planDestino, [
        'codigo' => 'DES-201',
        'nombre' => 'Materia destino',
        'semestre' => 1,
        'creditos' => 4,
    ]);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoDestino = cursoDeMateria($destino);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoDestino);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 88,
    ]);

    $convalidacion = app(ConvalidacionService::class)->paraEstudiante($estudiante)->first();
    $filaConvalidada = collect((new HistorialAcademicoReporte($estudiante))->filas())
        ->first(fn (array $fila) => $fila[5] === 'Convalidada');

    expect($convalidacion->creditos)->toBe(4)
        ->and($filaConvalidada[4])->toBe(4);
});

it('no convalida una materia por una equivalencia transitiva', function () {
    $carreraOrigen = Carrera::factory()->create();
    $carreraIntermedia = Carrera::factory()->create();
    $carreraFinal = Carrera::factory()->create();

    $origen = materiaEnCarrera($carreraOrigen);
    $intermedia = materiaEnCarrera($carreraIntermedia);
    $final = materiaEnCarrera($carreraFinal);

    MateriaEquivalencia::create([
        'materia_id' => $intermedia->id,
        'equivalente_id' => $origen->id,
    ]);
    MateriaEquivalencia::create([
        'materia_id' => $final->id,
        'equivalente_id' => $intermedia->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoIntermedia = cursoDeMateria($intermedia);
    $cursoFinal = cursoDeMateria($final);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoIntermedia, $cursoFinal);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 80,
    ]);

    $servicio = app(ConvalidacionService::class);
    $convalidadas = $servicio->idsMateriasConvalidadas($estudiante);
    $convalidaciones = $servicio->paraEstudiante($estudiante);

    expect($convalidadas)->toContain($intermedia->id)->not->toContain($final->id)
        ->and($convalidaciones->pluck('materia.id')->all())->toBe([$intermedia->id]);
});

it('no muestra convalidaciones de carreras en las que el estudiante no está matriculado', function () {
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $estudiante = estudianteMatriculado($cursoOrigen);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 80,
    ]);

    $servicio = app(ConvalidacionService::class);

    expect($servicio->paraEstudiante($estudiante))->toBeEmpty()
        ->and($servicio->idsMateriasConvalidadas($estudiante))->toBeEmpty();

    $this->actingAs($estudiante)
        ->get(route('estudiante.notas.index'))
        ->assertOk()
        ->assertDontSee('Convalidada');
});

it('registra una equivalencia dirigida y confirma el cambio al administrador', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
        ])
        ->assertRedirect(route('admin.equivalencias.index'))
        ->assertSessionHas('status', 'Equivalencia registrada correctamente.');

    $this->assertDatabaseHas('materia_equivalencias', [
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    expect(MateriaEquivalencia::count())->toBe(1);

    $this->actingAs($administrador)
        ->get(route('admin.equivalencias.index'))
        ->assertOk()
        ->assertSee('Carrera destino')
        ->assertSee('Carrera origen')
        ->assertSee('Registrar también la equivalencia inversa')
        ->assertSee($destino->codigo)
        ->assertSee($origen->codigo);
});

it('rechaza una equivalencia duplicada', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
        ])
        ->assertSessionHasErrors(['equivalente_id' => 'Esta equivalencia ya está registrada para la materia.']);

    expect(MateriaEquivalencia::count())->toBe(1);
});

it('rechaza una equivalencia de una materia consigo misma', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraDestino = Carrera::factory()->create();
    $carreraOrigen = Carrera::factory()->create();
    $materia = materiaEnCarrera($carreraDestino);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $materia->id,
            'equivalente_id' => $materia->id,
        ])
        ->assertSessionHasErrors(['equivalente_id' => 'La materia no puede equivaler a sí misma.']);

    expect(MateriaEquivalencia::count())->toBe(0);
});

it('elimina una equivalencia registrada', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $equivalencia = MateriaEquivalencia::factory()->create();

    $this->actingAs($administrador)
        ->delete(route('admin.equivalencias.destroy', $equivalencia))
        ->assertRedirect(route('admin.equivalencias.index'))
        ->assertSessionHas('status', 'Equivalencia eliminada.');

    $this->assertModelMissing($equivalencia);
});

it('impide que un estudiante registre equivalencias', function () {
    $estudiante = usuarioConRol(Rol::Estudiante);
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);

    $this->actingAs($estudiante)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
        ])
        ->assertForbidden();

    expect(MateriaEquivalencia::count())->toBe(0);
});

it('exige elegir la carrera antes de las materias', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
        ])
        ->assertSessionHasErrors(['carrera_destino_id', 'carrera_origen_id']);

    expect(MateriaEquivalencia::count())->toBe(0);
});

it('rechaza una materia que no pertenece a la carrera seleccionada', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraDestino = Carrera::factory()->create();
    $carreraOrigen = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($otraCarrera);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
        ])
        ->assertSessionHasErrors(['materia_id' => 'La materia destino debe pertenecer a un plan de la carrera destino seleccionada.']);

    expect(MateriaEquivalencia::count())->toBe(0);
});

it('acepta una materia destino compartida por varias carreras', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);
    PlanEstudio::factory()->create(['carrera_id' => $otraCarrera->id])
        ->vincularMateria($destino, 3, 2);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.equivalencias.index'));

    $this->assertDatabaseHas('materia_equivalencias', [
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);
});

it('registra la equivalencia inversa cuando se solicita bilateral', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
            'bilateral' => 1,
        ])
        ->assertRedirect(route('admin.equivalencias.index'))
        ->assertSessionHas('status', 'Equivalencia registrada en ambos sentidos.');

    $this->assertDatabaseHas('materia_equivalencias', [
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ])->assertDatabaseHas('materia_equivalencias', [
        'materia_id' => $origen->id,
        'equivalente_id' => $destino->id,
    ]);

    expect(MateriaEquivalencia::count())->toBe(2);
});

it('rechaza una equivalencia bilateral cuya inversa ya existe', function () {
    $administrador = usuarioConRol(Rol::Administrador);
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);
    MateriaEquivalencia::create([
        'materia_id' => $origen->id,
        'equivalente_id' => $destino->id,
    ]);

    $this->actingAs($administrador)
        ->post(route('admin.equivalencias.store'), [
            'carrera_destino_id' => $carreraDestino->id,
            'carrera_origen_id' => $carreraOrigen->id,
            'materia_id' => $destino->id,
            'equivalente_id' => $origen->id,
            'bilateral' => 1,
        ])
        ->assertSessionHasErrors(['equivalente_id' => 'La equivalencia inversa ya está registrada.']);

    expect(MateriaEquivalencia::count())->toBe(1);
});

it('aplica la convalidación con el plan principal cuando la materia destino es compartida', function () {
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();
    $carreraSecundaria = Carrera::factory()->create();

    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino, ['creditos' => 5]);
    $planSecundario = PlanEstudio::factory()->create(['carrera_id' => $carreraSecundaria->id]);
    $planSecundario->vincularMateria($destino, 2, 3);

    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoDestino = cursoDeMateria($destino);
    $estudiante = estudianteMatriculado($cursoDestino, $cursoOrigen);

    Matricula::factory()->create([
        'user_id' => $estudiante->id,
        'carrera_id' => $carreraSecundaria->id,
        'plan_estudio_id' => $planSecundario->id,
    ]);

    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 88,
    ]);

    $convalidacion = app(ConvalidacionService::class)->paraEstudiante($estudiante)->first();

    expect($convalidacion)->not->toBeNull()
        ->and($convalidacion->creditos)->toBe(5)
        ->and($convalidacion->carreraDestino->id)->toBe($carreraDestino->id);
});

it('filtra el historial por la carrera destino de la convalidación', function () {
    $carreraOrigen = Carrera::factory()->create();
    $carreraDestino = Carrera::factory()->create();

    $origen = materiaEnCarrera($carreraOrigen);
    $destino = materiaEnCarrera($carreraDestino);
    MateriaEquivalencia::create([
        'materia_id' => $destino->id,
        'equivalente_id' => $origen->id,
    ]);

    $cursoOrigen = cursoDeMateria($origen);
    $cursoDestino = cursoDeMateria($destino);
    $estudiante = estudianteMatriculado($cursoOrigen, $cursoDestino);
    Inscripcion::factory()->create([
        'curso_id' => $cursoOrigen->id,
        'user_id' => $estudiante->id,
        'nota_final' => 90,
    ]);

    $filasDestino = collect((new HistorialAcademicoReporte(
        $estudiante,
        new FiltrosReporte(carreraId: $carreraDestino->id),
    ))->filas())->filter(fn (array $fila) => $fila[5] === 'Convalidada');

    $filasOrigen = collect((new HistorialAcademicoReporte(
        $estudiante,
        new FiltrosReporte(carreraId: $carreraOrigen->id),
    ))->filas())->filter(fn (array $fila) => $fila[5] === 'Convalidada');

    expect($filasDestino)->toHaveCount(1)
        ->and($filasOrigen)->toBeEmpty();
});
