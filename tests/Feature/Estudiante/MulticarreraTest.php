<?php

use App\Enums\EstadoMatricula;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Periodo;
use App\Services\MatriculaService;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * Crea un curso activo de una carrera concreta para las pruebas de matrícula.
 *
 * @param  array<string, mixed>  $atributos
 */
function cursoDeCarrera(Carrera $carrera, string $codigo, array $atributos = []): Curso
{
    $materia = materiaEnCarrera($carrera, ['codigo' => $codigo]);

    $periodoId = $atributos['periodo_id'] ?? Periodo::factory()->vigente()->create()->id;

    return Curso::factory()->conMateria($materia)->create(array_merge([
        'carrera_id' => $carrera->id,
        'periodo_id' => $periodoId,
        'docente_id' => usuarioConRol(Rol::Docente)->id,
        'cupo' => 30,
        'activo' => true,
    ], $atributos));
}

it('bloquea la inscripción cuando el estudiante no tiene matrícula activa', function () {
    $curso = cursoDeCarrera(Carrera::factory()->create(), 'SIS-101');
    $estudiante = usuarioConRol(Rol::Estudiante);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'No tienes una matrícula activa en la carrera de este curso.']);

    expect(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(0);
});

it('permite inscribirse en un curso de otra carrera cuando el estudiante tiene matrícula activa en ambas', function () {
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();
    $cursoA = cursoDeCarrera($carreraA, 'SIS-101');
    $cursoB = cursoDeCarrera($carreraB, 'ADM-101');
    $estudiante = estudianteMatriculado($cursoA, $cursoB);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$cursoB->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect(Inscripcion::where('user_id', $estudiante->id)->where('curso_id', $cursoB->id)->exists())->toBeTrue()
        ->and($estudiante->matriculas()->activas()->count())->toBe(2);
});

it('vuelve a bloquear la inscripción después de dar de baja la matrícula de la carrera', function () {
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();
    $cursoA = cursoDeCarrera($carreraA, 'SIS-101');
    $cursoB = cursoDeCarrera($carreraB, 'ADM-101');
    $otroCursoB = cursoDeCarrera($carreraB, 'ADM-201');
    $estudiante = estudianteMatriculado($cursoA, $cursoB);
    $matriculaB = $estudiante->matriculas()->where('carrera_id', $carreraB->id)->firstOrFail();

    app(MatriculaService::class)->darDeBaja($matriculaB);

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$otroCursoB->id}/inscribirse")
        ->assertSessionHasErrors(['curso' => 'No tienes una matrícula activa en la carrera de este curso.']);

    expect($matriculaB->fresh()->estado)->toBe(EstadoMatricula::Baja)
        ->and(Inscripcion::where('user_id', $estudiante->id)->count())->toBe(0);
});

it('muestra solo los cursos de las carreras con matrícula activa en el catálogo', function () {
    $carreraA = Carrera::factory()->create(['nombre' => 'Sistemas']);
    $carreraB = Carrera::factory()->create(['nombre' => 'Administración']);
    $periodo = Periodo::factory()->vigente()->create();
    $cursoA = cursoDeCarrera($carreraA, 'SIS-101', ['nombre' => 'Curso de sistemas', 'periodo_id' => $periodo->id]);
    $cursoB = cursoDeCarrera($carreraB, 'ADM-101', ['nombre' => 'Curso de administración', 'periodo_id' => $periodo->id]);
    $estudiante = estudianteMatriculado($cursoA);

    $this->actingAs($estudiante)
        ->get('/estudiante/cursos')
        ->assertOk()
        ->assertSee('Curso de sistemas')
        ->assertDontSee('Curso de administración');

    $this->actingAs($estudiante)
        ->get("/estudiante/cursos?carrera_id={$carreraB->id}")
        ->assertOk()
        ->assertDontSee('Curso de administración');

    $estudiante->matriculas()->create([
        'carrera_id' => $carreraB->id,
        'plan_estudio_id' => $cursoB->planesEstudio()->first()->id,
        'estado' => EstadoMatricula::Activa,
        'es_principal' => false,
        'modalidad' => Modalidad::Semestral,
        'fecha_inicio' => now()->toDateString(),
    ]);

    $this->actingAs($estudiante)
        ->get('/estudiante/cursos')
        ->assertOk()
        ->assertSee('Curso de administración');
});

it('muestra la carrera principal y las secundarias en el panel del estudiante', function () {
    $carreraA = Carrera::factory()->create(['nombre' => 'Ingeniería de Sistemas']);
    $carreraB = Carrera::factory()->create(['nombre' => 'Administración de Empresas']);
    $estudiante = usuarioConRol(Rol::Estudiante);
    $estudiante->matriculas()->create([
        'carrera_id' => $carreraA->id,
        'estado' => EstadoMatricula::Activa,
        'es_principal' => true,
        'modalidad' => Modalidad::Semestral,
        'fecha_inicio' => now()->toDateString(),
    ]);
    $estudiante->matriculas()->create([
        'carrera_id' => $carreraB->id,
        'estado' => EstadoMatricula::Activa,
        'es_principal' => false,
        'modalidad' => Modalidad::Modular,
        'fecha_inicio' => now()->toDateString(),
    ]);

    $this->actingAs($estudiante)
        ->get('/estudiante/dashboard')
        ->assertOk()
        ->assertSee('Ingeniería de Sistemas')
        ->assertSee('También matriculado en')
        ->assertSee('Administración de Empresas');
});

it('promueve otra matrícula activa cuando se da de baja la principal', function () {
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();
    $estudiante = usuarioConRol(Rol::Estudiante);
    $principal = $estudiante->matriculas()->create([
        'carrera_id' => $carreraA->id,
        'estado' => EstadoMatricula::Activa,
        'es_principal' => true,
        'modalidad' => Modalidad::Semestral,
        'fecha_inicio' => now()->toDateString(),
    ]);
    $secundaria = $estudiante->matriculas()->create([
        'carrera_id' => $carreraB->id,
        'estado' => EstadoMatricula::Activa,
        'es_principal' => false,
        'modalidad' => Modalidad::Modular,
        'fecha_inicio' => now()->toDateString(),
    ]);

    app(MatriculaService::class)->darDeBaja($principal);

    expect($principal->fresh()->estado)->toBe(EstadoMatricula::Baja)
        ->and($secundaria->fresh()->es_principal)->toBeTrue()
        ->and($estudiante->carreraPrincipal()->is($carreraB))->toBeTrue();
});
