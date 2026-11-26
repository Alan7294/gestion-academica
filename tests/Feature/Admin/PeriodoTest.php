<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Periodo;
use Database\Seeders\RolSeeder;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('ofrece solo la opción Ver en la lista de periodos', function () {
    Periodo::factory()->create(['nombre' => '2026-1']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get('/admin/periodos')
        ->assertOk()
        ->assertSee('2026-1')
        ->assertSee('Ver')
        ->assertDontSee('Editar')
        ->assertDontSee('Eliminar')
        ->assertDontSee('Marcar vigente');
});

it('muestra el detalle del periodo con sus cursos', function () {
    $periodo = Periodo::factory()->create(['nombre' => '2026-2']);
    Curso::factory()->create([
        'periodo_id' => $periodo->id,
        'nombre' => 'Curso del periodo',
    ]);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/periodos/{$periodo->id}")
        ->assertOk()
        ->assertSee('2026-2')
        ->assertSee('Curso del periodo')
        ->assertSee('Marcar vigente')
        ->assertSee('Editar')
        ->assertSee('Eliminar');
});

it('muestra el periodo vigente sin la opción de marcarlo', function () {
    $periodo = Periodo::factory()->vigente()->create(['nombre' => '2026-3']);

    $this->actingAs(usuarioConRol(Rol::Administrador))
        ->get("/admin/periodos/{$periodo->id}")
        ->assertOk()
        ->assertSee('Vigente')
        ->assertDontSee('Marcar vigente');
});

it('impide que un no administrador vea el detalle de un periodo', function (Rol $rol) {
    $periodo = Periodo::factory()->create();

    $this->actingAs(usuarioConRol($rol))
        ->get("/admin/periodos/{$periodo->id}")
        ->assertForbidden();
})->with([
    'docente' => Rol::Docente,
    'estudiante' => Rol::Estudiante,
]);
