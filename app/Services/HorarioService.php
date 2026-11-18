<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\Horario;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class HorarioService
{
    /**
     * RF04: agrega una sesión semanal al curso validando que el docente no tenga choque.
     *
     * @param  array{dia_semana: int, hora_inicio: string, hora_fin: string, aula?: string|null}  $datos
     *
     * @throws ValidationException
     */
    public function agregar(Curso $curso, array $datos): Horario
    {
        if (! $curso->docente_id) {
            throw ValidationException::withMessages([
                'docente_id' => 'El curso debe tener un docente asignado antes de registrar horarios.',
            ]);
        }

        $inicio = substr($datos['hora_inicio'], 0, 5);
        $fin = substr($datos['hora_fin'], 0, 5);

        if ($fin <= $inicio) {
            throw ValidationException::withMessages([
                'hora_fin' => 'La hora de fin debe ser posterior a la hora de inicio.',
            ]);
        }

        $this->verificarChoqueDocente($curso, (int) $datos['dia_semana'], $inicio, $fin);

        try {
            return $curso->horarios()->create([
                'docente_id' => $curso->docente_id,
                'dia_semana' => (int) $datos['dia_semana'],
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
                'aula' => $datos['aula'] ?? null,
            ]);
        } catch (QueryException $e) {
            // La restricción EXCLUDE de PostgreSQL actúa como última línea de defensa.
            if ($e->getCode() === '23P01') {
                throw ValidationException::withMessages([
                    'hora_inicio' => 'El docente ya tiene otra clase en ese día y horario.',
                ]);
            }

            throw $e;
        }
    }

    /**
     * @throws ValidationException
     */
    private function verificarChoqueDocente(Curso $curso, int $dia, string $inicio, string $fin): void
    {
        $choque = Horario::query()
            ->where('docente_id', $curso->docente_id)
            ->where('dia_semana', $dia)
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            ->exists();

        if ($choque) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El docente ya tiene otra clase en ese día y horario.',
            ]);
        }
    }
}
