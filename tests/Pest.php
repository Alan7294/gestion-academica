<?php

use App\Enums\EstadoMatricula;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\PlanEstudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function usuarioConRol(Rol $rol): User
{
    $user = User::factory()->create();
    $user->assignRole($rol->value);

    return $user;
}

/**
 * @return array{nombres: string, primer_apellido: string, segundo_apellido: string|null}
 */
function datosNombre(string $nombres = 'Ana', string $primerApellido = 'Quispe', ?string $segundoApellido = null): array
{
    return [
        'nombres' => $nombres,
        'primer_apellido' => $primerApellido,
        'segundo_apellido' => $segundoApellido,
    ];
}

/**
 * Crea un estudiante con matrícula activa en los planes autorizados de los cursos dados.
 */
function estudianteMatriculado(Curso ...$cursos): User
{
    $estudiante = usuarioConRol(Rol::Estudiante);

    $planesPorCarrera = collect($cursos)
        ->flatMap(fn (Curso $curso) => $curso->planesEstudio->map(fn (PlanEstudio $plan) => [
            'plan' => $plan,
            'es_anfitrion' => $plan->id === $curso->plan_estudio_id,
        ]))
        ->groupBy(fn (array $item) => $item['plan']->carrera_id);

    $indice = 0;

    foreach ($planesPorCarrera as $carreraId => $items) {
        $item = $items->firstWhere('es_anfitrion') ?? $items->first();
        $plan = $item['plan'];

        $estudiante->matriculas()->create([
            'carrera_id' => $carreraId,
            'plan_estudio_id' => $plan->id,
            'estado' => EstadoMatricula::Activa,
            'es_principal' => $indice === 0,
            'modalidad' => $plan->modalidad,
            'fecha_inicio' => now()->toDateString(),
        ]);

        $indice++;
    }

    return $estudiante;
}

/**
 * Crea una materia del catálogo y la vincula al plan en el semestre indicado.
 *
 * @param  array{codigo: string, nombre: string, semestre?: int}  $atributos
 */
function materiaEnPlan(PlanEstudio $plan, array $atributos): Materia
{
    $materia = Materia::factory()->create([
        'codigo' => $atributos['codigo'],
        'nombre' => $atributos['nombre'],
    ]);

    $plan->vincularMateria($materia, (int) ($atributos['semestre'] ?? 1));

    return $materia;
}

/**
 * Crea una materia en el plan de pruebas de la carrera indicada.
 *
 * @param  array{codigo?: string, nombre?: string, semestre?: int}  $atributos
 */
function materiaEnCarrera(Carrera $carrera, array $atributos = []): Materia
{
    $plan = PlanEstudio::firstOrCreate(
        ['carrera_id' => $carrera->id, 'nombre' => "Plan de pruebas {$carrera->id}"],
        ['modalidad' => Modalidad::Semestral, 'semestres' => 12, 'activo' => false],
    );

    return materiaEnPlan($plan, $atributos + [
        'codigo' => strtoupper(fake()->unique()->bothify('???-###')),
        'nombre' => 'Materia de '.$carrera->nombre,
    ]);
}
