<?php

namespace App\Services;

use App\Enums\EstadoInscripcion;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\User;
use App\Notifications\InscripcionRegistrada;
use App\Notifications\NuevoAlumnoInscrito;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InscripcionService
{
    /**
     * RF05: inscribe a un estudiante validando cupo, duplicidad y choque de horario.
     *
     * @throws ValidationException
     */
    public function inscribir(User $estudiante, Curso $curso): Inscripcion
    {
        return DB::transaction(function () use ($estudiante, $curso) {
            // Bloquea el curso para que dos inscripciones simultáneas no superen el cupo.
            $curso = Curso::whereKey($curso->id)->lockForUpdate()->firstOrFail();

            if (! $estudiante->esEstudiante()) {
                throw ValidationException::withMessages([
                    'curso' => 'Solo los usuarios con rol de estudiante pueden inscribirse a cursos.',
                ]);
            }

            if (! $curso->activo) {
                throw ValidationException::withMessages([
                    'curso' => 'El curso no está habilitado para inscripciones.',
                ]);
            }

            if (! $curso->periodo->activo) {
                throw ValidationException::withMessages([
                    'curso' => 'El curso pertenece a un periodo que ya no está vigente.',
                ]);
            }

            $existente = Inscripcion::where('curso_id', $curso->id)
                ->where('user_id', $estudiante->id)
                ->first();

            if ($existente?->estado === EstadoInscripcion::Inscrito) {
                throw ValidationException::withMessages([
                    'curso' => 'Ya estás inscrito en este curso.',
                ]);
            }

            $this->verificarRequisitos($estudiante, $curso);

            if (! $curso->tieneCupoDisponible()) {
                throw ValidationException::withMessages([
                    'curso' => 'El curso no tiene cupo disponible.',
                ]);
            }

            $this->verificarChoqueHorario($estudiante, $curso);

            if ($existente) {
                $existente->update([
                    'estado' => EstadoInscripcion::Inscrito,
                    'inscrito_en' => now(),
                ]);

                $existente->setRelation('curso', $curso);
                $this->notificar($existente, $curso, $estudiante);

                return $existente;
            }

            $inscripcion = Inscripcion::create([
                'curso_id' => $curso->id,
                'user_id' => $estudiante->id,
                'estado' => EstadoInscripcion::Inscrito,
                'inscrito_en' => now(),
            ]);

            $inscripcion->setRelation('curso', $curso);
            $this->notificar($inscripcion, $curso, $estudiante);

            return $inscripcion;
        });
    }

    /**
     * RF04: exige aprobar todas las materias requisito antes de inscribirse.
     *
     * @throws ValidationException
     */
    private function verificarRequisitos(User $estudiante, Curso $curso): void
    {
        if ($curso->materia_id === null) {
            throw ValidationException::withMessages([
                'curso' => 'Este curso aún no tiene una materia del plan asignada. Corrígelo antes de inscribirte.',
            ]);
        }

        $requisitos = $curso->materia->requisitos;

        if ($requisitos->isEmpty()) {
            return;
        }

        $cursosRequisito = Curso::whereIn('materia_id', $requisitos->pluck('id'))->pluck('id');

        $aprobadas = Inscripcion::with('curso')
            ->where('user_id', $estudiante->id)
            ->whereIn('curso_id', $cursosRequisito)
            ->get()
            ->filter(fn (Inscripcion $inscripcion) => $inscripcion->estaAprobada())
            ->map(fn (Inscripcion $inscripcion) => $inscripcion->curso->materia_id);

        $faltantes = $requisitos->reject(fn (Materia $requisito) => $aprobadas->contains($requisito->id));

        if ($faltantes->isNotEmpty()) {
            $detalle = $faltantes
                ->map(fn (Materia $materia) => "{$materia->codigo} - {$materia->nombre}")
                ->join(', ');

            throw ValidationException::withMessages([
                'curso' => "No puedes inscribirte: aún no has aprobado los requisitos: {$detalle}.",
            ]);
        }
    }

    /**
     * RF10: avisa al estudiante y al docente de la nueva inscripción.
     */
    private function notificar(Inscripcion $inscripcion, Curso $curso, User $estudiante): void
    {
        $estudiante->notify(new InscripcionRegistrada($inscripcion));

        $curso->docente?->notify(new NuevoAlumnoInscrito($curso, $estudiante));
    }

    /**
     * Retira al estudiante del curso conservando el historial.
     */
    public function retirar(Inscripcion $inscripcion): Inscripcion
    {
        $inscripcion->update(['estado' => EstadoInscripcion::Retirado]);

        return $inscripcion;
    }

    /**
     * Regla de negocio: un alumno no puede tener dos materias en el mismo día y franja.
     *
     * @throws ValidationException
     */
    private function verificarChoqueHorario(User $estudiante, Curso $curso): void
    {
        $horarios = $curso->horarios;

        if ($horarios->isEmpty()) {
            return;
        }

        $choque = Horario::query()
            ->where('curso_id', '!=', $curso->id)
            ->whereHas('curso.inscripciones', function ($query) use ($estudiante) {
                $query->where('user_id', $estudiante->id)
                    ->where('estado', EstadoInscripcion::Inscrito->value);
            })
            ->where(function ($query) use ($horarios) {
                foreach ($horarios as $horario) {
                    $query->orWhere(function ($sub) use ($horario) {
                        $sub->where('dia_semana', $horario->dia_semana->value)
                            ->where('hora_inicio', '<', $horario->hora_fin)
                            ->where('hora_fin', '>', $horario->hora_inicio);
                    });
                }
            })
            ->exists();

        if ($choque) {
            throw ValidationException::withMessages([
                'curso' => 'No puedes inscribirte: el horario se cruza con otro curso que ya tienes inscrito.',
            ]);
        }
    }
}
