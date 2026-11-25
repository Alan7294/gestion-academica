<?php

use App\Http\Controllers\Admin\CarreraController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\EquivalenciaController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\MateriaController;
use App\Http\Controllers\Admin\PeriodoController;
use App\Http\Controllers\Admin\PlanEstudioController;
use App\Http\Controllers\Admin\ReporteController as ReporteAdminController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Docente\CalificacionController;
use App\Http\Controllers\Docente\CursoController as CursoDocenteController;
use App\Http\Controllers\Docente\CursoEvaluacionController;
use App\Http\Controllers\Docente\ReporteController as ReporteDocenteController;
use App\Http\Controllers\Estudiante\CursoController as CursoEstudianteController;
use App\Http\Controllers\Estudiante\InscripcionController;
use App\Http\Controllers\Estudiante\NotaController;
use App\Http\Controllers\Estudiante\ReporteController as ReporteEstudianteController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// RF12: los reportes solo se generan en PDF o Excel.
Route::pattern('formato', 'pdf|excel');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'role:administrador'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        Route::resource('usuarios', UsuarioController::class)->except('show');

        Route::get('reportes', [ReporteAdminController::class, 'index'])->name('reportes.index');
        Route::get('reportes/materias-por-carrera/preview', [ReporteAdminController::class, 'preview'])
            ->name('reportes.materias.preview');
        Route::get('reportes/materias-por-carrera/{formato}', [ReporteAdminController::class, 'materiasPorCarrera'])
            ->name('reportes.materias');

        Route::resource('carreras', CarreraController::class)->except('show');

        Route::get('materias/catalogo', [MateriaController::class, 'catalogo'])->name('materias.catalogo');

        Route::resource('materias', MateriaController::class)->except('show');

        Route::get('equivalencias', [EquivalenciaController::class, 'index'])->name('equivalencias.index');
        Route::post('equivalencias', [EquivalenciaController::class, 'store'])->name('equivalencias.store');
        Route::delete('equivalencias/{equivalencia}', [EquivalenciaController::class, 'destroy'])->name('equivalencias.destroy');

        Route::resource('planes', PlanEstudioController::class)
            ->parameters(['planes' => 'planEstudio']);

        Route::resource('periodos', PeriodoController::class)->except('show');
        Route::post('periodos/{periodo}/activar', [PeriodoController::class, 'activar'])->name('periodos.activar');

        Route::resource('cursos', CursoController::class);
        Route::post('cursos/{curso}/horarios', [HorarioController::class, 'store'])->name('horarios.store');
        Route::delete('cursos/{curso}/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
    });

Route::middleware(['auth', 'role:docente'])
    ->prefix('docente')
    ->name('docente.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'docente'])->name('dashboard');

        Route::get('cursos', [CursoDocenteController::class, 'index'])->name('cursos.index');
        Route::get('cursos/{curso}', [CursoDocenteController::class, 'show'])->name('cursos.show');

        Route::get('reportes', [ReporteDocenteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/materias/preview', [ReporteDocenteController::class, 'previewMaterias'])->name('reportes.materias.preview');
        Route::get('reportes/cursos/{curso}/notas/preview', [ReporteDocenteController::class, 'previewNotasCurso'])->name('reportes.notas-curso.preview');
        Route::get('reportes/cursos/{curso}/alumnos/preview', [ReporteDocenteController::class, 'previewAlumnosInscritos'])->name('reportes.alumnos.preview');
        Route::get('reportes/cursos/{curso}/inscripciones/{inscripcion}/notas/preview', [ReporteDocenteController::class, 'previewNotasAlumno'])->name('reportes.notas-alumno.preview');
        Route::get('reportes/materias/{formato}', [ReporteDocenteController::class, 'materias'])->name('reportes.materias');
        Route::get('reportes/cursos/{curso}/notas/{formato}', [ReporteDocenteController::class, 'notasCurso'])->name('reportes.notas-curso');
        Route::get('reportes/cursos/{curso}/alumnos/{formato}', [ReporteDocenteController::class, 'alumnosInscritos'])->name('reportes.alumnos');
        Route::get('reportes/cursos/{curso}/inscripciones/{inscripcion}/notas/{formato}', [ReporteDocenteController::class, 'notasAlumno'])->name('reportes.notas-alumno');

        Route::get('cursos/{curso}/calificaciones', [CalificacionController::class, 'index'])->name('cursos.calificaciones');
        Route::put('cursos/{curso}/calificaciones', [CalificacionController::class, 'actualizar'])->name('calificaciones.actualizar');
        Route::put('cursos/{curso}/evaluaciones', [CursoEvaluacionController::class, 'update'])->name('evaluaciones.update');
    });

Route::middleware(['auth', 'role:estudiante'])
    ->prefix('estudiante')
    ->name('estudiante.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'estudiante'])->name('dashboard');

        Route::get('cursos', [CursoEstudianteController::class, 'index'])->name('cursos.index');
        Route::get('cursos/{curso}', [CursoEstudianteController::class, 'show'])->name('cursos.show');

        Route::get('inscripciones', [InscripcionController::class, 'index'])->name('inscripciones.index');
        Route::post('cursos/{curso}/inscribirse', [InscripcionController::class, 'store'])->name('inscripciones.store');
        Route::delete('inscripciones/{inscripcion}', [InscripcionController::class, 'destroy'])->name('inscripciones.destroy');

        Route::get('notas', [NotaController::class, 'index'])->name('notas.index');

        Route::get('reportes', [ReporteEstudianteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/historial/preview', [ReporteEstudianteController::class, 'previewHistorial'])->name('reportes.historial.preview');
        Route::get('reportes/notas/preview', [ReporteEstudianteController::class, 'previewNotas'])->name('reportes.notas.preview');
        Route::get('reportes/materias/preview', [ReporteEstudianteController::class, 'previewMaterias'])->name('reportes.materias.preview');
        Route::get('reportes/historial/{formato}', [ReporteEstudianteController::class, 'historial'])->name('reportes.historial');
        Route::get('reportes/notas/{formato}', [ReporteEstudianteController::class, 'notas'])->name('reportes.notas');
        Route::get('reportes/materias/{formato}', [ReporteEstudianteController::class, 'materias'])->name('reportes.materias');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'leerTodas'])->name('notificaciones.leer-todas');
    Route::post('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'leer'])->name('notificaciones.leer');
});

require __DIR__.'/auth.php';
