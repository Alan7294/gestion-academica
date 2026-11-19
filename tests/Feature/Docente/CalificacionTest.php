<?php

use App\Enums\Rol;
use App\Models\Calificacion;
use App\Models\Curso;
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

it('registra una calificación y recalcula el promedio', function () {
    ['docente' => $docente, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Primer parcial',
            'ponderacion' => 50,
            'nota' => 80,
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect("/docente/cursos/{$inscripcion->curso_id}/calificaciones");

    expect(Calificacion::where('inscripcion_id', $inscripcion->id)->count())->toBe(1)
        ->and((float) $inscripcion->fresh()->nota_final)->toBe(80.0);
});

it('calcula el promedio ponderado de varias evaluaciones', function () {
    ['docente' => $docente, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Primer parcial',
            'ponderacion' => 40,
            'nota' => 70,
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->actingAs($docente)
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Examen final',
            'ponderacion' => 60,
            'nota' => 90,
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect();

    // (70 * 40 + 90 * 60) / 100 = 82
    expect((float) $inscripcion->fresh()->nota_final)->toBe(82.0);
});

it('rechaza una ponderación que supera el 100 por ciento', function () {
    ['docente' => $docente, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'registrado_por' => $docente->id,
        'nombre' => 'Primer parcial',
        'ponderacion' => 60,
        'nota' => 70,
        'fecha' => now()->toDateString(),
    ]);

    $this->actingAs($docente)
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Examen final',
            'ponderacion' => 50,
            'nota' => 80,
            'fecha' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('ponderacion');

    expect(Calificacion::where('inscripcion_id', $inscripcion->id)->count())->toBe(1);
});

it('rechaza una nota fuera del rango 0 a 100', function () {
    ['docente' => $docente, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();

    $this->actingAs($docente)
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Primer parcial',
            'ponderacion' => 30,
            'nota' => 120,
            'fecha' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('nota');

    expect(Calificacion::count())->toBe(0);
});

it('impide que otro docente registre calificaciones en un curso ajeno', function () {
    ['inscripcion' => $inscripcion] = contextoDeCalificaciones();

    $otroDocente = usuarioConRol(Rol::Docente);

    $this->actingAs($otroDocente)
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Primer parcial',
            'ponderacion' => 30,
            'nota' => 70,
            'fecha' => now()->toDateString(),
        ])
        ->assertForbidden();

    expect(Calificacion::count())->toBe(0);
});

it('actualiza una calificación y recalcula el promedio', function () {
    ['docente' => $docente, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();

    $calificacion = Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'registrado_por' => $docente->id,
        'nombre' => 'Primer parcial',
        'ponderacion' => 100,
        'nota' => 60,
        'fecha' => now()->toDateString(),
    ]);

    $inscripcion->update(['nota_final' => 60]);

    $this->actingAs($docente)
        ->put("/docente/calificaciones/{$calificacion->id}", [
            'nombre' => 'Primer parcial',
            'ponderacion' => 100,
            'nota' => 91,
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect("/docente/cursos/{$inscripcion->curso_id}/calificaciones");

    expect((float) $inscripcion->fresh()->nota_final)->toBe(91.0);
});

it('elimina una calificación y recalcula el promedio', function () {
    ['docente' => $docente, 'inscripcion' => $inscripcion] = contextoDeCalificaciones();

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'registrado_por' => $docente->id,
        'nombre' => 'Primer parcial',
        'ponderacion' => 40,
        'nota' => 70,
        'fecha' => now()->toDateString(),
    ]);

    $examen = Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'registrado_por' => $docente->id,
        'nombre' => 'Examen final',
        'ponderacion' => 60,
        'nota' => 90,
        'fecha' => now()->toDateString(),
    ]);

    $inscripcion->update(['nota_final' => 82]);

    $this->actingAs($docente)
        ->delete("/docente/calificaciones/{$examen->id}")
        ->assertRedirect("/docente/cursos/{$inscripcion->curso_id}/calificaciones");

    expect(Calificacion::count())->toBe(1)
        ->and((float) $inscripcion->fresh()->nota_final)->toBe(70.0);
});

it('impide que un estudiante registre calificaciones', function () {
    ['inscripcion' => $inscripcion] = contextoDeCalificaciones();

    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Primer parcial',
            'ponderacion' => 30,
            'nota' => 70,
            'fecha' => now()->toDateString(),
        ])
        ->assertForbidden();

    expect(Calificacion::count())->toBe(0);
});

it('muestra la planilla de notas con los alumnos inscritos', function () {
    ['docente' => $docente, 'curso' => $curso, 'estudiante' => $estudiante] = contextoDeCalificaciones();

    $estudiante->update(['name' => 'María Condori']);

    $this->actingAs($docente)
        ->get("/docente/cursos/{$curso->id}/calificaciones")
        ->assertOk()
        ->assertSee('María Condori')
        ->assertSee('Nueva evaluación');
});

it('muestra al estudiante sus calificaciones y su promedio', function () {
    ['docente' => $docente, 'inscripcion' => $inscripcion, 'estudiante' => $estudiante] = contextoDeCalificaciones();

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'registrado_por' => $docente->id,
        'nombre' => 'Primer parcial',
        'ponderacion' => 100,
        'nota' => 78,
        'fecha' => now()->toDateString(),
    ]);

    $inscripcion->update(['nota_final' => 78]);

    $this->actingAs($estudiante)
        ->get('/estudiante/notas')
        ->assertOk()
        ->assertSee('Primer parcial')
        ->assertSee('78');
});
