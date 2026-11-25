<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use App\Notifications\InscripcionRegistrada;
use App\Notifications\NotaRegistrada;
use App\Notifications\NuevoAlumnoInscrito;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * Curso activo con docente listo para recibir inscripciones.
 *
 * @return array{docente: User, curso: Curso, estudiante: User}
 */
function contextoDeAvisos(): array
{
    $docente = usuarioConRol(Rol::Docente);

    $materia = Materia::factory()->create();

    $curso = Curso::factory()->conMateria($materia)->create([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
        'activo' => true,
    ]);

    $estudiante = estudianteMatriculado($curso);

    return compact('docente', 'curso', 'estudiante');
}

it('notifica al estudiante y al docente cuando hay una nueva inscripción', function () {
    ['docente' => $docente, 'curso' => $curso, 'estudiante' => $estudiante] = contextoDeAvisos();

    $this->actingAs($estudiante)
        ->post("/estudiante/cursos/{$curso->id}/inscribirse")
        ->assertRedirect('/estudiante/inscripciones');

    expect($estudiante->notifications()->count())->toBe(1)
        ->and($docente->notifications()->count())->toBe(1);

    expect($estudiante->notifications()->first()->type)->toBe(InscripcionRegistrada::class)
        ->and($docente->notifications()->first()->type)->toBe(NuevoAlumnoInscrito::class);

    expect($docente->notifications()->first()->data['mensaje'])->toContain($curso->nombre);
});

it('notifica al estudiante cuando el docente registra una nota', function () {
    ['docente' => $docente, 'curso' => $curso, 'estudiante' => $estudiante] = contextoDeAvisos();

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $evaluacion = $curso->evaluaciones()->create(['nombre' => 'Primer parcial', 'ponderacion' => 100]);

    $this->actingAs($docente)
        ->put(route('docente.calificaciones.actualizar', $curso), [
            'notas' => [
                $inscripcion->id => [
                    $evaluacion->id => 85,
                ],
            ],
        ])
        ->assertRedirect();

    expect($estudiante->notifications()->count())->toBe(1)
        ->and($estudiante->notifications()->first()->type)->toBe(NotaRegistrada::class)
        ->and($estudiante->notifications()->first()->data['mensaje'])->toContain('Primer parcial');
});

it('muestra los avisos y la actividad reciente en el panel del estudiante', function () {
    ['curso' => $curso, 'estudiante' => $estudiante] = contextoDeAvisos();

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $estudiante->notify(new InscripcionRegistrada($inscripcion->load('curso')));

    $this->actingAs($estudiante)
        ->get('/notificaciones')
        ->assertOk()
        ->assertSee('Inscripción confirmada')
        ->assertSee('Nueva');

    $this->actingAs($estudiante)
        ->get('/estudiante/dashboard')
        ->assertOk()
        ->assertSee('Actividad reciente')
        ->assertSee('Inscripción confirmada');
});

it('marca una notificación propia como leída', function () {
    ['curso' => $curso, 'estudiante' => $estudiante] = contextoDeAvisos();

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $estudiante->notify(new InscripcionRegistrada($inscripcion->load('curso')));
    $notificacion = $estudiante->notifications()->first();

    $this->actingAs($estudiante)
        ->post("/notificaciones/{$notificacion->id}/leer")
        ->assertRedirect('/estudiante/inscripciones');

    expect($notificacion->fresh()->read_at)->not->toBeNull();
});

it('impide marcar como leída la notificación de otro usuario', function () {
    ['curso' => $curso, 'estudiante' => $estudiante] = contextoDeAvisos();

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $estudiante->notify(new InscripcionRegistrada($inscripcion->load('curso')));
    $notificacion = $estudiante->notifications()->first();

    $otro = usuarioConRol(Rol::Estudiante);

    $this->actingAs($otro)
        ->post("/notificaciones/{$notificacion->id}/leer")
        ->assertForbidden();

    expect($notificacion->fresh()->read_at)->toBeNull();
});

it('marca todas las notificaciones como leídas', function () {
    ['curso' => $curso, 'estudiante' => $estudiante] = contextoDeAvisos();

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $estudiante->notify(new InscripcionRegistrada($inscripcion->load('curso')));

    $evaluacion = $curso->evaluaciones()->create(['nombre' => 'Primer parcial', 'ponderacion' => 100]);

    $estudiante->notify(new NotaRegistrada(
        $inscripcion->calificaciones()->create([
            'curso_evaluacion_id' => $evaluacion->id,
            'registrado_por' => $curso->docente_id,
            'nota' => 70,
            'fecha' => now()->toDateString(),
        ]),
    ));

    expect($estudiante->unreadNotifications()->count())->toBe(2);

    $this->actingAs($estudiante)
        ->post('/notificaciones/leer-todas')
        ->assertRedirect();

    expect($estudiante->unreadNotifications()->count())->toBe(0);
});
