<?php

use App\Http\Controllers\Admin\CarreraController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\PeriodoController;
use App\Http\Controllers\DashboardController;
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
    });

Route::middleware(['auth', 'verified', 'role:estudiante'])
    ->prefix('estudiante')
    ->name('estudiante.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'estudiante'])->name('dashboard');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
