<?php

use App\Enums\Rol;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use App\Reports\Admin\MateriasPorCarreraReporte;
use App\Reports\Docente\NotasPorAlumnoReporte;
use App\Reports\FiltrosReporte;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

/**
 * Contexto académico completo para generar los reportes.
 *
 * @return array{docente: User, curso: Curso, estudiante: User, inscripcion: Inscripcion}
 */
function contextoDeReportes(): array
{
    $docente = usuarioConRol(Rol::Docente);

    $materia = Materia::factory()->create([
        'codigo' => 'BD-201',
        'nombre' => 'Base de Datos II',
    ]);

    $curso = Curso::factory()->conMateria($materia)->create([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
        'nombre' => 'Base de Datos II',
    ]);

    $estudiante = usuarioConRol(Rol::Estudiante);
    $estudiante->update(['name' => 'Ana Reportes']);

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $estudiante->id,
    ]);

    $evaluacion = $curso->evaluaciones()->create(['nombre' => 'Primer parcial', 'ponderacion' => 100]);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $evaluacion->id,
        'registrado_por' => $docente->id,
        'nota' => 88,
    ]);

    $inscripcion->update(['nota_final' => 88]);

    return compact('docente', 'curso', 'estudiante', 'inscripcion');
}

it('muestra el catálogo de reportes del administrador', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/reportes')
        ->assertOk()
        ->assertSee('Materias por carrera')
        ->assertSee('Descargar PDF')
        ->assertSee('Descargar Excel');
});

it('genera el reporte de materias por carrera en PDF y Excel', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $pdf = $this->actingAs($admin)->get('/admin/reportes/materias-por-carrera/pdf');

    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf')
        ->and($pdf->getContent())->toStartWith('%PDF');

    $excel = $this->actingAs($admin)->get('/admin/reportes/materias-por-carrera/excel');

    $excel->assertOk();
    expect($excel->headers->get('content-type'))->toContain('spreadsheetml')
        ->and($excel->streamedContent())->toStartWith('PK');
});

it('muestra al docente sus cursos y accesos a reportes', function () {
    ['docente' => $docente, 'curso' => $curso, 'estudiante' => $estudiante] = contextoDeReportes();

    $this->actingAs($docente)
        ->get('/docente/reportes')
        ->assertOk()
        ->assertSee('Base de Datos II')
        ->assertSee('Notas por curso')
        ->assertSee($estudiante->name);
});

it('genera los reportes del docente en PDF y Excel', function () {
    ['docente' => $docente, 'curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeReportes();

    $notas = $this->actingAs($docente)->get("/docente/reportes/cursos/{$curso->id}/notas/pdf");
    $notas->assertOk();
    expect($notas->getContent())->toStartWith('%PDF');

    $alumnos = $this->actingAs($docente)->get("/docente/reportes/cursos/{$curso->id}/alumnos/excel");
    $alumnos->assertOk();
    expect($alumnos->streamedContent())->toStartWith('PK');

    $alumno = $this->actingAs($docente)->get(
        "/docente/reportes/cursos/{$curso->id}/inscripciones/{$inscripcion->id}/notas/pdf",
    );
    $alumno->assertOk();
    expect($alumno->getContent())->toStartWith('%PDF');

    $materias = $this->actingAs($docente)->get('/docente/reportes/materias/excel');
    $materias->assertOk();
    expect($materias->streamedContent())->toStartWith('PK');
});

it('impide descargar reportes de cursos que el docente no dicta', function () {
    ['curso' => $curso, 'inscripcion' => $inscripcion] = contextoDeReportes();

    $otroDocente = usuarioConRol(Rol::Docente);

    $this->actingAs($otroDocente)
        ->get("/docente/reportes/cursos/{$curso->id}/notas/pdf")
        ->assertForbidden();

    $this->actingAs($otroDocente)
        ->get("/docente/reportes/cursos/{$curso->id}/inscripciones/{$inscripcion->id}/notas/pdf")
        ->assertForbidden();
});

it('rechaza una inscripción que no pertenece al curso del reporte', function () {
    ['docente' => $docente, 'curso' => $curso] = contextoDeReportes();

    $otroCurso = Curso::factory()->create(['docente_id' => $docente->id]);
    $otraInscripcion = Inscripcion::factory()->create(['curso_id' => $otroCurso->id]);

    $this->actingAs($docente)
        ->get("/docente/reportes/cursos/{$curso->id}/inscripciones/{$otraInscripcion->id}/notas/pdf")
        ->assertNotFound();
});

it('limita el reporte de notas por alumno a la inscripción del curso', function () {
    ['curso' => $curso, 'estudiante' => $estudiante, 'inscripcion' => $inscripcion] = contextoDeReportes();

    $otroCurso = Curso::factory()
        ->conMateria(Materia::factory()->create([
            'codigo' => 'RD-202',
            'nombre' => 'Redes II',
        ]))
        ->create([
            'docente_id' => $curso->docente_id,
            'periodo_id' => $curso->periodo_id,
            'nombre' => 'Redes II',
        ]);

    Inscripcion::factory()->create([
        'curso_id' => $otroCurso->id,
        'user_id' => $estudiante->id,
    ]);

    $filas = (new NotasPorAlumnoReporte($inscripcion))->filas();

    expect($filas)->toHaveCount(1)
        ->and($filas[0][0])->toBe('Base de Datos II')
        ->and($filas[0][1])->toBe('BD-201');
});

it('muestra y genera los reportes del estudiante', function () {
    ['estudiante' => $estudiante] = contextoDeReportes();

    $this->actingAs($estudiante)
        ->get('/estudiante/reportes')
        ->assertOk()
        ->assertSee('Historial académico')
        ->assertSee('Notas de todas las materias')
        ->assertSee('Materias del alumno');

    $historial = $this->actingAs($estudiante)->get('/estudiante/reportes/historial/pdf');
    $historial->assertOk();
    expect($historial->headers->get('content-type'))->toContain('application/pdf')
        ->and($historial->getContent())->toStartWith('%PDF');

    $notas = $this->actingAs($estudiante)->get('/estudiante/reportes/notas/excel');
    $notas->assertOk();
    expect($notas->streamedContent())->toStartWith('PK');

    $materias = $this->actingAs($estudiante)->get('/estudiante/reportes/materias/pdf');
    $materias->assertOk();
    expect($materias->getContent())->toStartWith('%PDF');
});

it('restringe los reportes según el rol', function () {
    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->get('/docente/reportes')
        ->assertForbidden();

    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get('/admin/reportes')
        ->assertForbidden();

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/estudiante/reportes')
        ->assertForbidden();
});

it('rechaza un formato de reporte no soportado', function () {
    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->get('/estudiante/reportes/historial/csv')
        ->assertNotFound();
});

it('muestra la vista previa parametrizada del administrador', function () {
    ['curso' => $curso] = contextoDeReportes();

    $otroPeriodo = Periodo::factory()->create(['nombre' => 'Periodo Lee']);
    Curso::factory()
        ->conMateria(Materia::factory()->create([
            'codigo' => 'HA-101',
            'nombre' => 'Historia del Arte',
        ]))
        ->create([
            'periodo_id' => $otroPeriodo->id,
            'nombre' => 'Historia del Arte',
        ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/reportes/materias-por-carrera/preview?periodo_id={$curso->periodo_id}")
        ->assertOk()
        ->assertSee('Vista previa')
        ->assertSee('Parámetros del reporte')
        ->assertSee('Base de Datos II')
        ->assertSee('1 registro')
        ->assertDontSee('2 registros')
        ->assertSee('Filtro: Periodo')
        ->assertSee('periodo_id='.$curso->periodo_id, false);
});

it('aplica los filtros también a las descargas', function () {
    $otroPeriodo = Periodo::factory()->create(['nombre' => 'Periodo Lee']);
    Curso::factory()
        ->conMateria(Materia::factory()->create([
            'codigo' => 'HA-101',
            'nombre' => 'Historia del Arte',
        ]))
        ->create([
            'periodo_id' => $otroPeriodo->id,
            'nombre' => 'Historia del Arte',
        ]);

    $filas = (new MateriasPorCarreraReporte(
        new FiltrosReporte(periodoId: $otroPeriodo->id),
    ))->filas();

    expect($filas)->toHaveCount(1)
        ->and($filas[0][2])->toBe('Historia del Arte');
});

it('aplica los filtros de nota en la vista previa del docente', function () {
    ['curso' => $curso, 'docente' => $docente] = contextoDeReportes();

    $otroEstudiante = usuarioConRol(Rol::Estudiante);
    $otroEstudiante->update(['name' => 'Luis Notas Bajas']);

    Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => $otroEstudiante->id,
        'nota_final' => 30,
    ]);

    $this->actingAs($docente)
        ->get("/docente/reportes/cursos/{$curso->id}/notas/preview?nota_desde=51")
        ->assertOk()
        ->assertSee('Ana Reportes')
        ->assertDontSee('Luis Notas Bajas')
        ->assertSee('Filtro: Nota final');
});

it('impide la vista previa de cursos que el docente no dicta', function () {
    ['curso' => $curso] = contextoDeReportes();

    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get("/docente/reportes/cursos/{$curso->id}/notas/preview")
        ->assertForbidden();
});

it('muestra la vista previa del estudiante y conserva los filtros al descargar', function () {
    ['curso' => $curso, 'estudiante' => $estudiante] = contextoDeReportes();

    $otroPeriodo = Periodo::factory()->create(['nombre' => 'Periodo Pasado']);

    $this->actingAs($estudiante)
        ->get("/estudiante/reportes/historial/preview?periodo_id={$curso->periodo_id}")
        ->assertOk()
        ->assertSee('Base de Datos II')
        ->assertSee('Filtro: Periodo')
        ->assertSee('periodo_id='.$curso->periodo_id, false);

    $this->actingAs($estudiante)
        ->get("/estudiante/reportes/historial/preview?periodo_id={$otroPeriodo->id}")
        ->assertOk()
        ->assertSee('0 registros')
        ->assertSee('No hay datos para los filtros seleccionados.');
});

it('rechaza parámetros de filtro inválidos en la vista previa', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/reportes/materias-por-carrera/preview?periodo_id=999999')
        ->assertSessionHasErrors('periodo_id');
});

it('rechaza un rango de notas invertido en la vista previa', function () {
    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/reportes/materias-por-carrera/preview?nota_desde=90&nota_hasta=50')
        ->assertSessionHasErrors('nota_desde');
});
