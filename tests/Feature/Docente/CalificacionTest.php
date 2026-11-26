<?php

use App\Enums\Rol;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\CursoEvaluacion;
use App\Models\Inscripcion;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * Crea un curso con docente y un estudiante inscrito.
 *
 * @return array{docente: User, curso: Curso, estudiante: User, inscripcion: Inscripcion}
 */
function contextoDeCalificaciones(): array
{
    $docente = usuarioConRol(Rol::Docente);

    $curso = Curso::factory()->create([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
        'activo' => true,
    ]);

    $estudiante = usuarioConRol(Rol::Estudiante);

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    return compact('docente', 'curso', 'estudiante', 'inscripcion');
}

/**
 * Define las actividades del curso con una ponderación que suma 100%.
 *
 * @return array{0: CursoEvaluacion, 1: CursoEvaluacion}
 */
function evaluacionesDelCurso(Curso $curso): array
{
    return [
        $curso->evaluaciones()->create(['nombre' => 'Primer parcial', 'ponderacion' => 40]),
        $curso->evaluaciones()->create(['nombre' => 'Examen final', 'ponderacion' => 60]),
    ];
}

it('registra las notas de la grilla y recalcula el promedio', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial, $final] = evaluacionesDelCurso($curso);

    $this->actingAs($docente)
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $parcial->id => 70,
                    $final->id => 90,
                ],
            ],
        ])
        ->assertRedirect(route('docente.cursos.calificaciones', $curso));

    // (70 * 40 + 90 * 60) / 100 = 82
    expect(Calificacion::where('inscripcion_id', $inscripcion->id)->count())->toBe(2)
        ->and((float) $inscripcion->fresh()->nota_final)->toBe(82.0);
});

it('actualiza una nota existente y recalcula el promedio', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial, $final] = evaluacionesDelCurso($curso);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $parcial->id,
        'registrado_por' => $docente->id,
        'nota' => 70,
    ]);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $final->id,
        'registrado_por' => $docente->id,
        'nota' => 90,
    ]);

    $inscripcion->update(['nota_final' => 82]);

    $this->actingAs($docente)
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $parcial->id => 100,
                    $final->id => 90,
                ],
            ],
        ])
        ->assertRedirect(route('docente.cursos.calificaciones', $curso));

    // (100 * 40 + 90 * 60) / 100 = 94
    expect((float) $inscripcion->fresh()->nota_final)->toBe(94.0);
});

it('elimina la calificación cuando la celda queda vacía', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial, $final] = evaluacionesDelCurso($curso);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $parcial->id,
        'registrado_por' => $docente->id,
        'nota' => 70,
    ]);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $final->id,
        'registrado_por' => $docente->id,
        'nota' => 90,
    ]);

    $this->actingAs($docente)
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $parcial->id => '',
                    $final->id => 90,
                ],
            ],
        ])
        ->assertRedirect(route('docente.cursos.calificaciones', $curso));

    expect(Calificacion::where('inscripcion_id', $inscripcion->id)->count())->toBe(1)
        ->and((float) $inscripcion->fresh()->nota_final)->toBe(90.0);
});

it('rechaza una nota fuera del rango 0 a 100', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial] = evaluacionesDelCurso($curso);

    $this->actingAs($docente)
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $parcial->id => 120,
                ],
            ],
        ])
        ->assertSessionHasErrors("notas.{$inscripcion->id}.{$parcial->id}");

    expect(Calificacion::count())->toBe(0);
});

it('rechaza una nota con más de dos decimales', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial] = evaluacionesDelCurso($curso);

    $this->actingAs($docente)
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $parcial->id => 70.555,
                ],
            ],
        ])
        ->assertSessionHasErrors("notas.{$inscripcion->id}.{$parcial->id}");

    expect(Calificacion::count())->toBe(0);
});

it('exige que las ponderaciones del curso sumen exactamente 100', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->put(route('docente.evaluaciones.update', $curso), [
            'evaluaciones' => [
                ['nombre' => 'Primer parcial', 'ponderacion' => 50],
                ['nombre' => 'Examen final', 'ponderacion' => 40],
            ],
        ])
        ->assertSessionHasErrors('evaluaciones');

    expect($curso->evaluaciones()->count())->toBe(0);
});

it('guarda las actividades del curso con ponderaciones decimales', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->put(route('docente.evaluaciones.update', $curso), [
            'evaluaciones' => [
                ['nombre' => 'Parcial 1', 'ponderacion' => 33.33],
                ['nombre' => 'Parcial 2', 'ponderacion' => 33.33],
                ['nombre' => 'Examen final', 'ponderacion' => 33.34],
            ],
        ])
        ->assertRedirect(route('docente.cursos.calificaciones', $curso));

    $evaluaciones = $curso->evaluaciones()->get();

    expect($evaluaciones)->toHaveCount(3)
        ->and((float) $evaluaciones->sum('ponderacion'))->toBe(100.0);
});

it('rechaza una ponderación con más de dos decimales', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->put(route('docente.evaluaciones.update', $curso), [
            'evaluaciones' => [
                ['nombre' => 'Primer parcial', 'ponderacion' => 33.333],
                ['nombre' => 'Examen final', 'ponderacion' => 66.667],
            ],
        ])
        ->assertSessionHasErrors('evaluaciones.0.ponderacion');

    expect($curso->evaluaciones()->count())->toBe(0);
});

it('rechaza actividades con el mismo nombre en el curso', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->put(route('docente.evaluaciones.update', $curso), [
            'evaluaciones' => [
                ['nombre' => 'Primer parcial', 'ponderacion' => 50],
                ['nombre' => 'Primer parcial', 'ponderacion' => 50],
            ],
        ])
        ->assertSessionHasErrors('evaluaciones.1.nombre');

    expect($curso->evaluaciones()->count())->toBe(0);
});

it('recalcula las notas del curso al cambiar las ponderaciones', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial, $final] = evaluacionesDelCurso($curso);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $parcial->id,
        'registrado_por' => $docente->id,
        'nota' => 70,
    ]);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $final->id,
        'registrado_por' => $docente->id,
        'nota' => 90,
    ]);

    $inscripcion->update(['nota_final' => 82]);

    $this->actingAs($docente)
        ->put(route('docente.evaluaciones.update', $curso), [
            'evaluaciones' => [
                ['id' => $parcial->id, 'nombre' => 'Primer parcial', 'ponderacion' => 50],
                ['id' => $final->id, 'nombre' => 'Examen final', 'ponderacion' => 50],
            ],
        ])
        ->assertRedirect(route('docente.cursos.calificaciones', $curso));

    // (70 * 50 + 90 * 50) / 100 = 80
    expect((float) $inscripcion->fresh()->nota_final)->toBe(80.0);
});

it('impide que otro docente gestione las actividades de un curso ajeno', function () {
    ['curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs(usuarioConRol(Rol::Docente))
        ->put(route('docente.evaluaciones.update', $curso), [
            'evaluaciones' => [
                ['nombre' => 'Primer parcial', 'ponderacion' => 100],
            ],
        ])
        ->assertForbidden();

    expect($curso->evaluaciones()->count())->toBe(0);
});

it('impide que otro docente cargue notas en un curso ajeno', function () {
    ['curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial] = evaluacionesDelCurso($curso);

    $this->actingAs(usuarioConRol(Rol::Docente))
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $parcial->id => 70,
                ],
            ],
        ])
        ->assertForbidden();

    expect(Calificacion::count())->toBe(0);
});

it('impide que un estudiante cargue notas', function () {
    ['curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();
    [$parcial] = evaluacionesDelCurso($curso);

    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $parcial->id => 70,
                ],
            ],
        ])
        ->assertForbidden();

    expect(Calificacion::count())->toBe(0);
});

it('enlaza la planilla de calificaciones desde el detalle del curso', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->get(route('docente.cursos.show', $curso))
        ->assertOk()
        ->assertSee(route('docente.cursos.calificaciones', $curso), false);
});

it('enlaza la planilla de calificaciones desde mis cursos', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->get(route('docente.cursos.index'))
        ->assertOk()
        ->assertSee(route('docente.cursos.calificaciones', $curso), false);
});

it('muestra la planilla de notas con los alumnos inscritos', function () {
    ['docente' => $docente, 'curso' => $curso, 'estudiante' => $estudiante] = contextoDeCalificaciones();

    $estudiante->update(['name' => 'María Condori']);
    evaluacionesDelCurso($curso);

    $this->actingAs($docente)
        ->get(route('docente.cursos.calificaciones', $curso))
        ->assertOk()
        ->assertSee('María Condori')
        ->assertSee('Actividades y ponderaciones')
        ->assertSee('Primer parcial')
        ->assertSee('Guardar calificaciones');
});

it('pide definir las actividades antes de cargar notas', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->get(route('docente.cursos.calificaciones', $curso))
        ->assertOk()
        ->assertSee('Primero define las actividades del curso');
});

it('muestra al estudiante sus calificaciones y su promedio', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion, 'estudiante' => $estudiante] = contextoDeCalificaciones();
    [$parcial] = evaluacionesDelCurso($curso);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $parcial->id,
        'registrado_por' => $docente->id,
        'nota' => 78,
    ]);

    $inscripcion->update(['nota_final' => 78]);

    $this->actingAs($estudiante)
        ->get('/estudiante/notas')
        ->assertOk()
        ->assertSee('Ver calificaciones')
        ->assertSee('78.00');

    $this->actingAs($estudiante)
        ->get(route('estudiante.inscripciones.show', $inscripcion))
        ->assertOk()
        ->assertSee('Primer parcial')
        ->assertSee('Promedio final')
        ->assertSee('78.00');
});
