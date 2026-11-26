<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Matricula;
use App\Models\PlanEstudio;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatriculaService
{
    /**
     * Ajusta las matrículas del estudiante sin borrar su historial.
     * Las carreras que dejan de enviarse pasan a estado de baja.
     *
     * @param  array<int, array{carrera_id: int, modalidad?: string|null, es_principal?: bool, plan_estudio_id?: int|null}>  $matriculas
     */
    public function sincronizar(User $usuario, array $matriculas): void
    {
        DB::transaction(function () use ($usuario, $matriculas) {
            $carrerasEnviadas = collect($matriculas)->pluck('carrera_id')->map(fn ($id) => (int) $id)->all();

            $usuario->matriculas()
                ->activas()
                ->when($carrerasEnviadas !== [], fn ($query) => $query->whereNotIn('carrera_id', $carrerasEnviadas))
                ->get()
                ->each(fn (Matricula $matricula) => $this->darDeBaja($matricula));

            if ($matriculas === []) {
                return;
            }

            // Se limpia antes para no violar el índice único de matrícula principal.
            $usuario->matriculas()->activas()->where('es_principal', true)->update(['es_principal' => false]);

            $principal = collect($matriculas)->first(fn ($matricula) => (bool) ($matricula['es_principal'] ?? false))
                ?? $matriculas[0];
            $carreraPrincipal = (int) $principal['carrera_id'];

            foreach ($matriculas as $matricula) {
                $carreraId = (int) $matricula['carrera_id'];
                $modalidad = $matricula['modalidad'] ?? null;

                $atributos = [
                    'estado' => EstadoMatricula::Activa,
                    'es_principal' => $carreraId === $carreraPrincipal,
                    'modalidad' => $modalidad,
                    'plan_estudio_id' => $this->planEstudioId($carreraId, $matricula['plan_estudio_id'] ?? null, $modalidad),
                    'fecha_fin' => null,
                ];

                $matriculaActual = $usuario->matriculas()->where('carrera_id', $carreraId)->first();

                if ($matriculaActual) {
                    if (! $matriculaActual->estaActiva()) {
                        $atributos['fecha_inicio'] = now()->toDateString();
                    }

                    $matriculaActual->update($atributos);

                    continue;
                }

                $usuario->matriculas()->create($atributos + [
                    'carrera_id' => $carreraId,
                    'fecha_inicio' => now()->toDateString(),
                ]);
            }
        });
    }

    /**
     * Da de baja una matrícula y promueve a otra activa cuando era la principal.
     */
    public function darDeBaja(Matricula $matricula): void
    {
        if (! $matricula->estaActiva()) {
            return;
        }

        DB::transaction(function () use ($matricula) {
            $eraPrincipal = $matricula->es_principal;

            $matricula->update([
                'estado' => EstadoMatricula::Baja,
                'es_principal' => false,
                'fecha_fin' => now()->toDateString(),
            ]);

            if (! $eraPrincipal) {
                return;
            }

            $sucesora = $matricula->estudiante
                ->matriculas()
                ->activas()
                ->whereKeyNot($matricula->id)
                ->orderBy('id')
                ->first();

            $sucesora?->update(['es_principal' => true]);
        });
    }

    /**
     * Resuelve el plan de la carrera, priorizando la modalidad elegida.
     */
    private function planEstudioId(int $carreraId, ?int $planEstudioId, ?string $modalidad): ?int
    {
        if ($planEstudioId !== null && $this->planEsValido($planEstudioId, $carreraId, $modalidad)) {
            return $planEstudioId;
        }

        return PlanEstudio::query()
            ->where('carrera_id', $carreraId)
            ->where('activo', true)
            ->when($modalidad, fn ($query) => $query->where('modalidad', $modalidad))
            ->orderBy('id')
            ->value('id')
            ?? PlanEstudio::query()
                ->where('carrera_id', $carreraId)
                ->when($modalidad, fn ($query) => $query->where('modalidad', $modalidad))
                ->orderBy('id')
                ->value('id');
    }

    /**
     * Un plan solo puede asignarse a una matrícula de su carrera y modalidad.
     */
    private function planEsValido(int $planEstudioId, int $carreraId, ?string $modalidad): bool
    {
        return PlanEstudio::query()
            ->whereKey($planEstudioId)
            ->where('carrera_id', $carreraId)
            ->when($modalidad, fn ($query) => $query->where('modalidad', $modalidad))
            ->exists();
    }
}
