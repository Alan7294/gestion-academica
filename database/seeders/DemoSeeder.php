<?php

namespace Database\Seeders;

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoMatricula;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use App\Models\User;
use App\Services\CalificacionService;
use App\Services\InscripcionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración para probar el sistema y la defensa del proyecto.
 *
 * Ejecutar con: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = Periodo::where('activo', true)->first()
            ?? Periodo::firstOrCreate(
                ['nombre' => '2026-II'],
                [
                    'fecha_inicio' => now()->startOfYear(),
                    'fecha_fin' => now()->endOfYear(),
                    'activo' => true,
                ],
            );

        $carreras = Carrera::orderBy('id')->get()->keyBy('codigo');

        $docentes = $this->crearDocentes();
        $estudiantes = $this->crearEstudiantes($carreras);

        $definiciones = [
            ['codigo' => 'SIS-101', 'carrera' => 'SIS', 'nombre' => 'Programación I', 'creditos' => 5, 'semestre' => 1, 'docente' => 0, 'dia' => 1, 'inicio' => '08:00', 'fin' => '10:00', 'aula' => 'Lab. 1'],
            ['codigo' => 'SIS-201', 'carrera' => 'SIS', 'nombre' => 'Estructura de Datos', 'creditos' => 5, 'semestre' => 2, 'docente' => 0, 'dia' => 3, 'inicio' => '08:00', 'fin' => '10:00', 'aula' => 'Lab. 2'],
            ['codigo' => 'ADM-101', 'carrera' => 'ADM', 'nombre' => 'Contabilidad General', 'creditos' => 4, 'semestre' => 1, 'docente' => 1, 'dia' => 2, 'inicio' => '10:00', 'fin' => '12:00', 'aula' => 'Aula 204'],
            ['codigo' => 'ADM-201', 'carrera' => 'ADM', 'nombre' => 'Administración I', 'creditos' => 4, 'semestre' => 2, 'docente' => 1, 'dia' => 4, 'inicio' => '10:00', 'fin' => '12:00', 'aula' => 'Aula 205'],
            ['codigo' => 'DER-101', 'carrera' => 'DER', 'nombre' => 'Derecho Civil I', 'creditos' => 5, 'semestre' => 1, 'docente' => 2, 'dia' => 1, 'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'Aula 301'],
            ['codigo' => 'DER-201', 'carrera' => 'DER', 'nombre' => 'Derecho Penal I', 'creditos' => 4, 'semestre' => 2, 'docente' => 2, 'dia' => 3, 'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'Aula 302'],
        ];

        $cursos = [];
        $materias = [];

        foreach ($definiciones as $definicion) {
            $carrera = $carreras->get($definicion['carrera']);
            $docente = $docentes[$definicion['docente']];

            $materia = Materia::query()
                ->where('codigo', $definicion['codigo'])
                ->first();

            $materias[$definicion['codigo']] = $materia;

            $claves = ['periodo_id' => $periodo->id, 'materia_id' => $materia?->id];

            if ($materia === null) {
                $claves['nombre'] = $definicion['nombre'];
            }

            $curso = Curso::firstOrCreate(
                $claves,
                [
                    'carrera_id' => $carrera->id,
                    'docente_id' => $docente->id,
                    'nombre' => $definicion['nombre'],
                    'creditos' => $definicion['creditos'],
                    'semestre' => $definicion['semestre'],
                    'cupo' => 30,
                    'activo' => true,
                ],
            );

            $plan = $materia
                ? PlanEstudio::where('carrera_id', $carrera->id)
                    ->whereHas('materias', fn ($query) => $query->whereKey($materia->id))
                    ->orderByDesc('activo')
                    ->orderBy('id')
                    ->first()
                : null;

            $pivote = $plan?->materias->firstWhere('id', $materia->id)?->pivot;

            $curso->update([
                'docente_id' => $docente->id,
                'modalidad' => $plan?->modalidad ?? Modalidad::Semestral,
                'plan_estudio_id' => $plan?->id,
                'materia_id' => $materia?->id,
                'nombre' => $materia?->nombre ?? $definicion['nombre'],
                'semestre' => (int) ($pivote?->semestre ?? $definicion['semestre']),
                'creditos' => (int) ($pivote?->creditos ?? $definicion['creditos']),
            ]);

            if ($plan) {
                $curso->planesEstudio()->syncWithoutDetaching([$plan->id]);
            }

            $curso->horarios()->firstOrCreate(
                [
                    'dia_semana' => $definicion['dia'],
                    'hora_inicio' => $definicion['inicio'],
                    'hora_fin' => $definicion['fin'],
                ],
                [
                    'docente_id' => $docente->id,
                    'aula' => $definicion['aula'],
                ],
            );

            $cursos[$definicion['codigo']] = $curso;
        }

        // Derecho Penal I reconoce Estructura de Datos cursada en Sistemas.
        MateriaEquivalencia::firstOrCreate([
            'materia_id' => $materias['DER-201']->id,
            'equivalente_id' => $materias['SIS-201']->id,
        ]);

        // El orden de cada lista respeta los requisitos: primero la materia previa y su nota.
        $matriculas = [
            'estudiante1@academica.test' => ['SIS-101', 'ADM-101'],
            'estudiante2@academica.test' => ['SIS-101', 'SIS-201', 'DER-101'],
            'estudiante3@academica.test' => ['ADM-101', 'SIS-101', 'SIS-201'],
            'estudiante4@academica.test' => ['DER-101', 'DER-201', 'ADM-101'],
            'estudiante5@academica.test' => ['SIS-101', 'SIS-201'],
            'estudiante6@academica.test' => ['ADM-101', 'DER-101'],
        ];

        $inscripciones = app(InscripcionService::class);
        $calificaciones = app(CalificacionService::class);
        $definicionEvaluaciones = [
            ['nombre' => 'Primer parcial', 'ponderacion' => 30],
            ['nombre' => 'Segundo parcial', 'ponderacion' => 30],
            ['nombre' => 'Examen final', 'ponderacion' => 40],
        ];

        foreach ($cursos as $curso) {
            if ($curso->evaluaciones()->exists()) {
                continue;
            }

            foreach ($definicionEvaluaciones as $definicion) {
                $curso->evaluaciones()->create($definicion);
            }
        }

        $indice = 0;

        foreach ($matriculas as $correo => $codigos) {
            $estudiante = $estudiantes[$correo];

            foreach ($codigos as $codigo) {
                $curso = $cursos[$codigo];

                $inscripcion = Inscripcion::where('curso_id', $curso->id)
                    ->where('user_id', $estudiante->id)
                    ->where('estado', EstadoInscripcion::Inscrito->value)
                    ->first();

                if (! $inscripcion) {
                    $inscripcion = $inscripciones->inscribir($estudiante, $curso);
                }

                if ($inscripcion->calificaciones()->exists()) {
                    continue;
                }

                $notas = [];

                foreach ($curso->evaluaciones as $posicion => $evaluacion) {
                    $notas[$evaluacion->id] = 62 + (($indice * 7 + $posicion * 11) % 33);
                }

                $calificaciones->guardarNotas($curso, [$inscripcion->id => $notas], $curso->docente);

                $indice++;
            }
        }
    }

    /**
     * @return array<int, User>
     */
    private function crearDocentes(): array
    {
        $definiciones = [
            ['nombres' => 'Carlos', 'primer_apellido' => 'Mamani', 'email' => 'docente1@academica.test', 'ci' => '7010001', 'telefono' => '70010001'],
            ['nombres' => 'Lucía', 'primer_apellido' => 'Fernández', 'email' => 'docente2@academica.test', 'ci' => '7010002', 'telefono' => '70010002'],
            ['nombres' => 'Jorge', 'primer_apellido' => 'Rojas', 'email' => 'docente3@academica.test', 'ci' => '7010003', 'telefono' => '70010003'],
        ];

        $docentes = [];

        foreach ($definiciones as $definicion) {
            $docente = $this->crearUsuario($definicion, Rol::Docente);
            $docentes[] = $docente;
        }

        return $docentes;
    }

    /**
     * @param  Collection<string, Carrera>  $carreras
     * @return array<string, User>
     */
    private function crearEstudiantes($carreras): array
    {
        $definiciones = [
            ['nombres' => 'Ana', 'primer_apellido' => 'Quispe', 'email' => 'estudiante1@academica.test', 'ci' => '7020001', 'telefono' => '70020001', 'carreras' => ['SIS' => Modalidad::Semestral, 'ADM' => Modalidad::Modular]],
            ['nombres' => 'Luis', 'primer_apellido' => 'Condori', 'email' => 'estudiante2@academica.test', 'ci' => '7020002', 'telefono' => '70020002', 'carreras' => ['SIS' => Modalidad::Semestral, 'DER' => Modalidad::Semestral]],
            ['nombres' => 'María', 'primer_apellido' => 'Choque', 'email' => 'estudiante3@academica.test', 'ci' => '7020003', 'telefono' => '70020003', 'carreras' => ['ADM' => Modalidad::Modular, 'SIS' => Modalidad::Semestral]],
            ['nombres' => 'Diego', 'primer_apellido' => 'Vargas', 'email' => 'estudiante4@academica.test', 'ci' => '7020004', 'telefono' => '70020004', 'carreras' => ['ADM' => Modalidad::Modular, 'DER' => Modalidad::Semestral]],
            ['nombres' => 'Sofía', 'primer_apellido' => 'Limachi', 'email' => 'estudiante5@academica.test', 'ci' => '7020005', 'telefono' => '70020005', 'carreras' => ['DER' => Modalidad::Semestral, 'SIS' => Modalidad::Semestral]],
            ['nombres' => 'Pedro', 'primer_apellido' => 'Céspedes', 'email' => 'estudiante6@academica.test', 'ci' => '7020006', 'telefono' => '70020006', 'carreras' => ['DER' => Modalidad::Semestral, 'ADM' => Modalidad::Modular]],
        ];

        $estudiantes = [];

        foreach ($definiciones as $definicion) {
            $estudiante = $this->crearUsuario($definicion, Rol::Estudiante);
            $esPrincipal = true;

            foreach ($definicion['carreras'] as $codigo => $modalidad) {
                $plan = PlanEstudio::where('carrera_id', $carreras[$codigo]->id)
                    ->where('modalidad', $modalidad->value)
                    ->where('activo', true)
                    ->firstOrFail();

                Matricula::updateOrCreate(
                    ['user_id' => $estudiante->id, 'carrera_id' => $carreras[$codigo]->id],
                    [
                        'plan_estudio_id' => $plan->id,
                        'estado' => EstadoMatricula::Activa,
                        'es_principal' => $esPrincipal,
                        'modalidad' => $modalidad,
                        'fecha_inicio' => now()->toDateString(),
                    ],
                );

                $esPrincipal = false;
            }

            $estudiantes[$definicion['email']] = $estudiante;
        }

        return $estudiantes;
    }

    /**
     * @param  array{nombres: string, primer_apellido: string, segundo_apellido?: string|null, email: string, ci: string, telefono: string}  $datos
     */
    private function crearUsuario(array $datos, Rol $rol): User
    {
        $segundoApellido = $datos['segundo_apellido'] ?? null;

        $partesDelNombre = [
            'name' => trim(implode(' ', array_filter([
                $datos['nombres'],
                $datos['primer_apellido'],
                $segundoApellido,
            ]))),
            'nombres' => $datos['nombres'],
            'primer_apellido' => $datos['primer_apellido'],
            'segundo_apellido' => $segundoApellido,
        ];

        $usuario = User::firstOrCreate(
            ['email' => $datos['email']],
            $partesDelNombre + [
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'],
                'password' => Hash::make('Demo1234!'),
                'activo' => true,
            ],
        );

        // Los usuarios de demostración creados antes del cambio conservan el nombre íntegro.
        if ($usuario->nombres === null) {
            $usuario->update($partesDelNombre);
        }

        $usuario->syncRoles([$rol->value]);

        return $usuario;
    }
}
