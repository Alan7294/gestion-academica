<?php

namespace Database\Seeders;

use App\Enums\EstadoInscripcion;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Periodo;
use App\Models\User;
use App\Services\CalificacionService;
use App\Services\InscripcionService;
use Illuminate\Database\Seeder;
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
        $estudiantes = $this->crearEstudiantes();

        $definiciones = [
            ['codigo' => 'SIS-101', 'carrera' => 'SIS', 'nombre' => 'Programación I', 'creditos' => 5, 'semestre' => 1, 'docente' => 0, 'dia' => 1, 'inicio' => '08:00', 'fin' => '10:00', 'aula' => 'Lab. 1'],
            ['codigo' => 'SIS-201', 'carrera' => 'SIS', 'nombre' => 'Base de Datos II', 'creditos' => 4, 'semestre' => 3, 'docente' => 0, 'dia' => 3, 'inicio' => '08:00', 'fin' => '10:00', 'aula' => 'Lab. 2'],
            ['codigo' => 'ADM-101', 'carrera' => 'ADM', 'nombre' => 'Contabilidad General', 'creditos' => 4, 'semestre' => 1, 'docente' => 1, 'dia' => 2, 'inicio' => '10:00', 'fin' => '12:00', 'aula' => 'Aula 204'],
            ['codigo' => 'ADM-201', 'carrera' => 'ADM', 'nombre' => 'Gestión del Talento Humano', 'creditos' => 3, 'semestre' => 3, 'docente' => 1, 'dia' => 4, 'inicio' => '10:00', 'fin' => '12:00', 'aula' => 'Aula 205'],
            ['codigo' => 'DER-101', 'carrera' => 'DER', 'nombre' => 'Derecho Civil I', 'creditos' => 5, 'semestre' => 1, 'docente' => 2, 'dia' => 1, 'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'Aula 301'],
            ['codigo' => 'DER-201', 'carrera' => 'DER', 'nombre' => 'Derecho Penal I', 'creditos' => 4, 'semestre' => 3, 'docente' => 2, 'dia' => 3, 'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'Aula 302'],
        ];

        $cursos = [];

        foreach ($definiciones as $definicion) {
            $carrera = $carreras->get($definicion['carrera']);
            $docente = $docentes[$definicion['docente']];

            $curso = Curso::firstOrCreate(
                ['periodo_id' => $periodo->id, 'codigo' => $definicion['codigo']],
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

            $curso->update(['docente_id' => $docente->id]);

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

        $matriculas = [
            'estudiante1@academica.test' => ['SIS-101', 'ADM-101'],
            'estudiante2@academica.test' => ['SIS-201', 'DER-101'],
            'estudiante3@academica.test' => ['ADM-201', 'SIS-101'],
            'estudiante4@academica.test' => ['DER-201', 'ADM-101'],
            'estudiante5@academica.test' => ['SIS-101', 'SIS-201'],
            'estudiante6@academica.test' => ['ADM-101', 'ADM-201'],
        ];

        $inscripciones = app(InscripcionService::class);

        foreach ($matriculas as $correo => $codigos) {
            $estudiante = $estudiantes[$correo];

            foreach ($codigos as $codigo) {
                $curso = $cursos[$codigo];

                $yaInscrito = Inscripcion::where('curso_id', $curso->id)
                    ->where('user_id', $estudiante->id)
                    ->where('estado', EstadoInscripcion::Inscrito->value)
                    ->exists();

                if (! $yaInscrito) {
                    $inscripciones->inscribir($estudiante, $curso);
                }
            }
        }

        $calificaciones = app(CalificacionService::class);
        $evaluaciones = [
            ['nombre' => 'Primer parcial', 'ponderacion' => 30, 'dias' => 45],
            ['nombre' => 'Segundo parcial', 'ponderacion' => 30, 'dias' => 20],
            ['nombre' => 'Examen final', 'ponderacion' => 40, 'dias' => 5],
        ];

        $indice = 0;

        foreach ($estudiantes as $estudiante) {
            $inscripcionesAlumno = Inscripcion::where('user_id', $estudiante->id)
                ->where('estado', EstadoInscripcion::Inscrito->value)
                ->with(['curso.docente', 'calificaciones'])
                ->get();

            foreach ($inscripcionesAlumno as $inscripcion) {
                if ($inscripcion->calificaciones->isNotEmpty()) {
                    continue;
                }

                foreach ($evaluaciones as $posicion => $evaluacion) {
                    $nota = 62 + (($indice * 7 + $posicion * 11) % 33);

                    $calificaciones->registrar($inscripcion, [
                        'nombre' => $evaluacion['nombre'],
                        'ponderacion' => $evaluacion['ponderacion'],
                        'nota' => $nota,
                        'fecha' => now()->subDays($evaluacion['dias'])->toDateString(),
                    ], $inscripcion->curso->docente);
                }

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
            ['name' => 'Carlos Mamani', 'email' => 'docente1@academica.test', 'ci' => '7010001', 'telefono' => '70010001'],
            ['name' => 'Lucía Fernández', 'email' => 'docente2@academica.test', 'ci' => '7010002', 'telefono' => '70010002'],
            ['name' => 'Jorge Rojas', 'email' => 'docente3@academica.test', 'ci' => '7010003', 'telefono' => '70010003'],
        ];

        $docentes = [];

        foreach ($definiciones as $definicion) {
            $docente = $this->crearUsuario($definicion, Rol::Docente);
            $docentes[] = $docente;
        }

        return $docentes;
    }

    /**
     * @return array<string, User>
     */
    private function crearEstudiantes(): array
    {
        $definiciones = [
            ['name' => 'Ana Quispe', 'email' => 'estudiante1@academica.test', 'ci' => '7020001', 'telefono' => '70020001'],
            ['name' => 'Luis Condori', 'email' => 'estudiante2@academica.test', 'ci' => '7020002', 'telefono' => '70020002'],
            ['name' => 'María Choque', 'email' => 'estudiante3@academica.test', 'ci' => '7020003', 'telefono' => '70020003'],
            ['name' => 'Diego Vargas', 'email' => 'estudiante4@academica.test', 'ci' => '7020004', 'telefono' => '70020004'],
            ['name' => 'Sofía Limachi', 'email' => 'estudiante5@academica.test', 'ci' => '7020005', 'telefono' => '70020005'],
            ['name' => 'Pedro Céspedes', 'email' => 'estudiante6@academica.test', 'ci' => '7020006', 'telefono' => '70020006'],
        ];

        $estudiantes = [];

        foreach ($definiciones as $definicion) {
            $estudiantes[$definicion['email']] = $this->crearUsuario($definicion, Rol::Estudiante);
        }

        return $estudiantes;
    }

    /**
     * @param  array{name: string, email: string, ci: string, telefono: string}  $datos
     */
    private function crearUsuario(array $datos, Rol $rol): User
    {
        $usuario = User::firstOrCreate(
            ['email' => $datos['email']],
            [
                'name' => $datos['name'],
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'],
                'password' => Hash::make('Demo1234!'),
                'activo' => true,
            ],
        );

        if ($usuario->wasRecentlyCreated) {
            $usuario->forceFill(['email_verified_at' => now()])->save();
        }

        $usuario->syncRoles([$rol->value]);

        return $usuario;
    }
}
