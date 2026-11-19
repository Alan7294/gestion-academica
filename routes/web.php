<?php

use App\Http\Controllers\Admin\CarreraController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\PeriodoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Docente\CalificacionController;
use App\Http\Controllers\Docente\CursoController as CursoDocenteController;
use App\Http\Controllers\Estudiante\CursoController as CursoEstudianteController;
use App\Http\Controllers\Estudiante\InscripcionController;
use App\Http\Controllers\Estudiante\NotaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:administrador'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        Route::resource('carreras', CarreraController::class)->except('show');

        Route::resource('periodos', PeriodoController::class)->except('show');
        Route::post('periodos/{periodo}/activar', [PeriodoController::class, 'activar'])->name('periodos.activar');

        Route::resource('cursos', CursoController::class);
        Route::post('cursos/{curso}/horarios', [HorarioController::class, 'store'])->name('horarios.store');
        Route::delete('cursos/{curso}/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
    });

Route::middleware(['auth', 'verified', 'role:docente'])
    ->prefix('docente')
    ->name('docente.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'docente'])->name('dashboard');

        Route::get('cursos', [CursoDocenteController::class, 'index'])->name('cursos.index');
        Route::get('cursos/{curso}', [CursoDocenteController::class, 'show'])->name('cursos.show');

        Route::get('cursos/{curso}/calificaciones', [CalificacionController::class, 'index'])->name('cursos.calificaciones');
        Route::post('inscripciones/{inscripcion}/calificaciones', [CalificacionController::class, 'store'])->name('calificaciones.store');
        Route::put('calificaciones/{calificacion}', [CalificacionController::class, 'update'])->name('calificaciones.update');
        Route::delete('calificaciones/{calificacion}', [CalificacionController::class, 'destroy'])->name('calificaciones.destroy');
    });

Route::middleware(['auth', 'verified', 'role:estudiante'])
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
