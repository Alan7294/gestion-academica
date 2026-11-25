<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use App\Models\Matricula;
use App\Models\PlanEstudio;
use App\Models\User;
use Illuminate\Support\Collection;

class ConvalidacionService
{
    /**
     * IDs de materias que el estudiante tiene aprobadas por equivalencia.
     * Se usa tanto para requisitos como para avisos en el catálogo.
     *
     * @return Collection<int, int>
     */
    public function idsMateriasConvalidadas(User $estudiante): Collection
    {
        return $this->aprobacionesAplicables($estudiante, $this->planesPorCarreraActiva($estudiante))
            ->pluck('equivalencia.materia_id')
            ->unique()
            ->values();
    }

    /**
     * Convalidaciones que deben aparecer en el historial del estudiante.
     * Si el alumno cursó la materia destino, prevalece su inscripción real.
     *
     * @return Collection<int, Convalidacion>
     */
    public function paraEstudiante(User $estudiante): Collection
    {
        $planesPorCarrera = $this->planesPorCarreraActiva($estudiante);

        if ($planesPorCarrera->isEmpty()) {
            return collect();
        }

        $materiasCursadas = Inscripcion::query()
            ->where('user_id', $estudiante->id)
            ->with('curso')
            ->get()
            ->pluck('curso.materia_id')
            ->filter()
            ->all();

        $aprobaciones = $this->aprobacionesAplicables($estudiante, $planesPorCarrera)
            ->filter(fn (array $aprobacion) => ! in_array($aprobacion['equivalencia']->materia_id, $materiasCursadas, true))
            ->groupBy(fn (array $aprobacion) => $aprobacion['equivalencia']->materia_id)
            ->map(fn (Collection $grupo) => $grupo->sortByDesc(fn (array $item) => $item['inscripcion']->updated_at)->first())
            ->values();

        if ($aprobaciones->isEmpty()) {
            return collect();
        }

        $creditosPorMateria = $this->creditosPorMateria($aprobaciones, $planesPorCarrera);

        return $aprobaciones
            ->map(fn (array $aprobacion) => $this->aConvalidacion(
                $aprobacion,
                $creditosPorMateria->get($aprobacion['equivalencia']->materia_id),
            ))
            ->sortBy(fn (Convalidacion $convalidacion) => $convalidacion->materia->codigo)
            ->values();
    }

    /**
     * Planes asignados a las matrículas activas, indexados por carrera.
     *
     * @return Collection<int, int>
     */
    private function planesPorCarreraActiva(User $estudiante): Collection
    {
        return $estudiante->matriculas()
            ->activas()
            ->get(['carrera_id', 'plan_estudio_id'])
            ->mapWithKeys(fn (Matricula $matricula) => [$matricula->carrera_id => $matricula->plan_estudio_id])
            ->filter();
    }

    /**
     * Una equivalencia solo se aplica en carreras donde el estudiante está matriculado
     * y tiene un plan asignado; sin plan no hay requisitos que validar contra él.
     *
     * @param  Collection<int, int>  $planesPorCarrera
     * @return Collection<int, array{equivalencia: MateriaEquivalencia, inscripcion: Inscripcion}>
     */
    private function aprobacionesAplicables(User $estudiante, Collection $planesPorCarrera): Collection
    {
        return $this->aprobacionesPorEquivalencia($estudiante)
            ->filter(fn (array $aprobacion) => $planesPorCarrera->has($aprobacion['equivalencia']->materia->carrera_id));
    }

    /**
     * Créditos de cada materia destino según el plan de la matrícula activa.
     *
     * @param  Collection<int, array{equivalencia: MateriaEquivalencia, inscripcion: Inscripcion}>  $aprobaciones
     * @param  Collection<int, int>  $planesPorCarrera
     * @return Collection<int, int|null>
     */
    private function creditosPorMateria(Collection $aprobaciones, Collection $planesPorCarrera): Collection
    {
        $destinos = $aprobaciones
            ->map(fn (array $aprobacion) => $aprobacion['equivalencia']->materia)
            ->unique('id');

        $planes = PlanEstudio::query()
            ->with(['materias' => fn ($query) => $query->whereIn('materias.id', $destinos->pluck('id'))])
            ->whereIn('id', $planesPorCarrera->unique()->values())
            ->get()
            ->keyBy('id');

        return $destinos->mapWithKeys(function (Materia $materia) use ($planes, $planesPorCarrera) {
            $plan = $planes->get($planesPorCarrera->get($materia->carrera_id));
            $creditos = $plan?->materias->firstWhere('id', $materia->id)?->pivot->creditos;

            return [$materia->id => $creditos === null ? null : (int) $creditos];
        });
    }

    /**
     * Aprobaciones de materias origen que tienen una equivalencia configurada.
     *
     * @return Collection<int, array{equivalencia: MateriaEquivalencia, inscripcion: Inscripcion}>
     */
    private function aprobacionesPorEquivalencia(User $estudiante): Collection
    {
        $aprobadasPorMateria = Inscripcion::query()
            ->where('user_id', $estudiante->id)
            ->whereNotNull('nota_final')
            ->where('nota_final', '>=', Inscripcion::NOTA_MINIMA_APROBATORIA)
            ->with('curso')
            ->orderByDesc('updated_at')
            ->get()
            ->filter(fn (Inscripcion $inscripcion) => $inscripcion->estaAprobada() && $inscripcion->curso->materia_id !== null)
            ->groupBy(fn (Inscripcion $inscripcion) => $inscripcion->curso->materia_id)
            ->map(fn (Collection $grupo) => $grupo->first());

        if ($aprobadasPorMateria->isEmpty()) {
            return collect();
        }

        return MateriaEquivalencia::query()
            ->with(['materia.carrera', 'equivalente.carrera'])
            ->whereIn('equivalente_id', $aprobadasPorMateria->keys())
            ->get()
            ->map(fn (MateriaEquivalencia $equivalencia) => [
                'equivalencia' => $equivalencia,
                'inscripcion' => $aprobadasPorMateria->get($equivalencia->equivalente_id),
            ]);
    }

    /**
     * @param  array{equivalencia: MateriaEquivalencia, inscripcion: Inscripcion}  $aprobacion
     */
    private function aConvalidacion(array $aprobacion, ?int $creditos): Convalidacion
    {
        return new Convalidacion(
            materia: $aprobacion['equivalencia']->materia,
            equivalente: $aprobacion['equivalencia']->equivalente,
            nota: (float) $aprobacion['inscripcion']->nota_final,
            fecha: $aprobacion['inscripcion']->updated_at,
            creditos: $creditos,
        );
    }
}
