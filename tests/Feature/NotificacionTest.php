<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Inscripcion;
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

    $curso = Curso::factory()->create([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
        'activo' => true,
    ]);

    $estudiante = usuarioConRol(Rol::Estudiante);

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

    $this->actingAs($docente)
        ->post("/docente/inscripciones/{$inscripcion->id}/calificaciones", [
            'nombre' => 'Primer parcial',
            'ponderacion' => 50,
            'nota' => 85,
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($estudiante->notifications()->count())->toBe(1)
        ->and($estudiante->notifications()->first()->type)->toBe(NotaRegistrada::class);
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
    $estudiante->notify(new NotaRegistrada(
        $inscripcion->calificaciones()->create([
            'registrado_por' => $curso->docente_id,
            'nombre' => 'Primer parcial',
            'ponderacion' => 100,
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
