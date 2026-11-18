<?php

namespace App\Services;

use App\Enums\EstadoInscripcion;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\User;
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

                return $existente;
            }

            return Inscripcion::create([
                'curso_id' => $curso->id,
                'user_id' => $estudiante->id,
                'estado' => EstadoInscripcion::Inscrito,
                'inscrito_en' => now(),
            ]);
        });
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
