<?php

namespace App\Services;

use App\Enums\DiaSemana;
use App\Enums\Modalidad;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Periodo;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HorarioService
{
    private const DIAS_HABILES_MODULO = 20;

    /**
     * Semestral: una sesión semanal con la vigencia completa del periodo.
     * Modular: veinte sesiones (lunes a viernes durante cuatro semanas).
     *
     * @param  array{dia_semana?: int, fecha_inicio?: string, hora_inicio: string, hora_fin: string, aula?: string|null}  $datos
     * @return Collection<int, Horario>
     *
     * @throws ValidationException
     */
    public function agregar(Curso $curso, array $datos): Collection
    {
        if (! $curso->docente_id) {
            throw ValidationException::withMessages([
                'docente_id' => 'El curso debe tener un docente asignado antes de registrar horarios.',
            ]);
        }

        $periodo = $curso->periodo;

        if (! $periodo) {
            throw ValidationException::withMessages([
                'periodo_id' => 'El curso debe tener un periodo asignado antes de registrar horarios.',
            ]);
        }

        $inicio = substr($datos['hora_inicio'], 0, 5);
        $fin = substr($datos['hora_fin'], 0, 5);

        if ($fin <= $inicio) {
            throw ValidationException::withMessages([
                'hora_fin' => 'La hora de fin debe ser posterior a la hora de inicio.',
            ]);
        }

        if ($curso->modalidad === Modalidad::Modular) {
            if (! isset($datos['fecha_inicio'])) {
                throw ValidationException::withMessages([
                    'fecha_inicio' => 'Indica la fecha de inicio del módulo.',
                ]);
            }

            return $this->agregarModulo(
                $curso,
                $periodo,
                Carbon::parse($datos['fecha_inicio'])->startOfDay(),
                $inicio,
                $fin,
                $datos['aula'] ?? null,
            );
        }

        if (! isset($datos['dia_semana'])) {
            throw ValidationException::withMessages([
                'dia_semana' => 'Selecciona el día de la semana.',
            ]);
        }

        $dia = DiaSemana::from((int) $datos['dia_semana']);
        $this->verificarChoqueDocente($curso, $dia, $inicio, $fin, $periodo->fecha_inicio, $periodo->fecha_fin);

        return collect([
            $this->insertarSesion(
                $curso,
                $dia,
                $inicio,
                $fin,
                $periodo->fecha_inicio,
                $periodo->fecha_fin,
                $datos['aula'] ?? null,
            ),
        ]);
    }

    /**
     * Horario inicial del alta de un curso. La transacción que lo envuelve
     * también cubre la creación del curso, así que un choque revierte todo.
     *
     * @param  array{horarios?: array<int, array{dia_semana: int, hora_inicio: string, hora_fin: string}>, fecha_inicio?: string, hora_inicio?: string, hora_fin?: string, aula?: string|null}  $datos
     * @return Collection<int, Horario>
     *
     * @throws ValidationException
     */
    public function registrarInicial(Curso $curso, array $datos): Collection
    {
        $aula = $datos['aula'] ?? null;

        if ($curso->modalidad === Modalidad::Modular) {
            return $this->agregar($curso, [
                'fecha_inicio' => $datos['fecha_inicio'] ?? null,
                'hora_inicio' => $datos['hora_inicio'] ?? '',
                'hora_fin' => $datos['hora_fin'] ?? '',
                'aula' => $aula,
            ]);
        }

        if (! isset($datos['horarios']) || $datos['horarios'] === []) {
            throw ValidationException::withMessages([
                'horarios' => 'Indica al menos un día con su horario.',
            ]);
        }

        return DB::transaction(function () use ($curso, $datos, $aula): Collection {
            return collect($datos['horarios'])->flatMap(fn (array $sesion) => $this->agregar($curso, [
                'dia_semana' => $sesion['dia_semana'],
                'hora_inicio' => $sesion['hora_inicio'],
                'hora_fin' => $sesion['hora_fin'],
                'aula' => $aula,
            ]));
        });
    }

    /**
     * Devuelve la fecha del vigésimo día hábil, contando la fecha de inicio.
     */
    public static function finDelModulo(Carbon $inicio): Carbon
    {
        $fin = $inicio->copy()->startOfDay();
        $diasHabiles = 0;

        while ($diasHabiles < self::DIAS_HABILES_MODULO) {
            if ($fin->isWeekday()) {
                $diasHabiles++;
            }

            if ($diasHabiles < self::DIAS_HABILES_MODULO) {
                $fin->addDay();
            }
        }

        return $fin;
    }

    /**
     * @return Collection<int, Horario>
     *
     * @throws ValidationException
     */
    private function agregarModulo(
        Curso $curso,
        Periodo $periodo,
        Carbon $inicio,
        string $horaInicio,
        string $horaFin,
        ?string $aula,
    ): Collection {
        if ($inicio->isWeekend()) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'El módulo debe iniciar en un día hábil (lunes a viernes).',
            ]);
        }

        $fin = self::finDelModulo($inicio);

        if ($inicio->lt($periodo->fecha_inicio) || $fin->gt($periodo->fecha_fin)) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Los 20 días hábiles del módulo deben estar dentro del periodo.',
            ]);
        }

        // Se validan las veinte franjas antes de insertar para que una sesión
        // del mismo módulo no se considere choque consigo misma.
        $sesiones = [];

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            if (! $fecha->isWeekday()) {
                continue;
            }

            $dia = DiaSemana::from($fecha->dayOfWeekIso);
            $this->verificarChoqueDocente($curso, $dia, $horaInicio, $horaFin, $inicio, $fin);
            $sesiones[] = ['dia' => $dia, 'fecha' => $fecha->copy()];
        }

        // Todas las sesiones se crean juntas: si alguna choca, no queda un módulo a medias.
        return DB::transaction(function () use ($curso, $sesiones, $horaInicio, $horaFin, $inicio, $fin, $aula): Collection {
            return collect($sesiones)->map(fn (array $sesion) => $this->insertarSesion(
                $curso,
                $sesion['dia'],
                $horaInicio,
                $horaFin,
                $inicio,
                $fin,
                $aula,
                $sesion['fecha'],
            ));
        });
    }

    /**
     * @throws ValidationException
     */
    private function insertarSesion(
        Curso $curso,
        DiaSemana $dia,
        string $inicio,
        string $fin,
        Carbon $fechaInicio,
        Carbon $fechaFin,
        ?string $aula,
        ?Carbon $fechaSesion = null,
    ): Horario {
        try {
            return $curso->horarios()->create([
                'periodo_id' => $curso->periodo_id,
                'docente_id' => $curso->docente_id,
                'dia_semana' => $dia->value,
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'fecha_sesion' => $fechaSesion?->toDateString(),
                'aula' => $aula,
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
    private function verificarChoqueDocente(
        Curso $curso,
        DiaSemana $dia,
        string $inicio,
        string $fin,
        Carbon $fechaInicio,
        Carbon $fechaFin,
    ): void {
        $choque = Horario::query()
            ->where('docente_id', $curso->docente_id)
            ->where('dia_semana', $dia->value)
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            ->where('fecha_inicio', '<=', $fechaFin->toDateString())
            ->where('fecha_fin', '>=', $fechaInicio->toDateString())
            ->exists();

        if ($choque) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El docente ya tiene otra clase en ese día y horario.',
            ]);
        }
    }
}
