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
 * Crea un estudiante con matrícula activa y plan asignado en las carreras de los cursos dados.
 */
function estudianteMatriculado(Curso ...$cursos): User
{
    $estudiante = usuarioConRol(Rol::Estudiante);

    collect($cursos)
        ->map(fn (Curso $curso) => $curso->carrera)
        ->filter()
        ->unique('id')
        ->values()
        ->each(function (Carrera $carrera, int $indice) use ($estudiante, $cursos): void {
            $materias = collect($cursos)
                ->filter(fn (Curso $curso) => $curso->carrera_id === $carrera->id && $curso->materia_id !== null)
                ->map(fn (Curso $curso) => $curso->materia)
                ->unique('id')
                ->values()
                ->all();

            $estudiante->matriculas()->create([
                'carrera_id' => $carrera->id,
                'plan_estudio_id' => planDePruebaPara($carrera, $materias)->id,
                'estado' => EstadoMatricula::Activa,
                'es_principal' => $indice === 0,
                'modalidad' => Modalidad::Semestral,
                'fecha_inicio' => now()->toDateString(),
            ]);
        });

    return $estudiante;
}

/**
 * Reutiliza un plan que ya contenga las materias o crea uno de pruebas para la carrera.
 *
 * @param  array<int, Materia>  $materias
 */
function planDePruebaPara(Carrera $carrera, array $materias): PlanEstudio
{
    $idsMaterias = collect($materias)->pluck('id')->filter()->unique()->values()->all();

    if ($idsMaterias !== []) {
        $plan = PlanEstudio::where('carrera_id', $carrera->id)
            ->get()
            ->first(fn (PlanEstudio $plan) => collect($idsMaterias)->every(
                fn (int $id) => $plan->materias->contains($id)
            ));

        if ($plan) {
            return $plan;
        }
    }

    $plan = PlanEstudio::firstOrCreate(
        ['carrera_id' => $carrera->id, 'nombre' => "Plan de pruebas {$carrera->id}"],
        ['modalidad' => Modalidad::Semestral, 'semestres' => 12, 'activo' => false],
    );

    foreach ($idsMaterias as $id) {
        $plan->vincularMateria(Materia::findOrFail($id), 1, 4);
    }

    return $plan;
}

/**
 * Crea una materia del catálogo y la vincula al plan con sus datos propios.
 *
 * @param  array{codigo: string, nombre: string, semestre?: int, creditos?: int, carrera_id?: int}  $atributos
 */
function materiaEnPlan(PlanEstudio $plan, array $atributos): Materia
{
    $materia = Materia::factory()->create([
        'carrera_id' => $atributos['carrera_id'] ?? $plan->carrera_id,
        'codigo' => $atributos['codigo'],
        'nombre' => $atributos['nombre'],
    ]);

    $plan->vincularMateria(
        $materia,
        (int) ($atributos['semestre'] ?? 1),
        (int) ($atributos['creditos'] ?? 0),
    );

    return $materia;
}
