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
        $matriculas = $this->matriculasConPlan($estudiante);

        if ($matriculas->isEmpty()) {
            return collect();
        }

        return $this->aprobacionesAplicables($estudiante, $matriculas)
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
        $matriculas = $this->matriculasConPlan($estudiante);

        if ($matriculas->isEmpty()) {
            return collect();
        }

        $materiasCursadas = Inscripcion::query()
            ->where('user_id', $estudiante->id)
            ->with('curso')
            ->get()
            ->pluck('curso.materia_id')
            ->filter()
            ->all();

        $aprobaciones = $this->aprobacionesAplicables($estudiante, $matriculas)
            ->filter(fn (array $aprobacion) => ! in_array($aprobacion['equivalencia']->materia_id, $materiasCursadas, true))
            ->groupBy(fn (array $aprobacion) => $aprobacion['equivalencia']->materia_id)
            ->map(fn (Collection $grupo) => $grupo->sortByDesc(fn (array $item) => $item['inscripcion']->updated_at)->first())
            ->values();

        return $aprobaciones
            ->map(fn (array $aprobacion) => $this->aConvalidacion($aprobacion))
            ->sortBy(fn (Convalidacion $convalidacion) => $convalidacion->materia->codigo)
            ->values();
    }

    /**
     * Matrículas activas con plan asignado. La principal va primero para
     * resolver las materias compartidas por varias carreras.
     *
     * @return Collection<int, Matricula>
     */
    private function matriculasConPlan(User $estudiante): Collection
    {
        return $estudiante->matriculas()
            ->activas()
            ->whereNotNull('plan_estudio_id')
            ->with('carrera')
            ->orderByDesc('es_principal')
            ->orderBy('id')
            ->get();
    }

    /**
     * Una equivalencia solo se aplica si la materia destino está en el plan de
     * alguna matrícula activa. Si está en varios, se toma la matrícula principal.
     *
     * @param  Collection<int, Matricula>  $matriculas
     * @return Collection<int, array{equivalencia: MateriaEquivalencia, inscripcion: Inscripcion, matricula: Matricula, creditos: int|null}>
     */
    private function aprobacionesAplicables(User $estudiante, Collection $matriculas): Collection
    {
        $planes = PlanEstudio::query()
            ->with('materias')
            ->whereIn('id', $matriculas->pluck('plan_estudio_id')->unique()->values())
            ->get()
            ->keyBy('id');

        return $this->aprobacionesPorEquivalencia($estudiante)
            ->map(function (array $aprobacion) use ($matriculas, $planes): ?array {
                foreach ($matriculas as $matricula) {
                    $materiaDelPlan = $planes->get($matricula->plan_estudio_id)
                        ?->materias
                        ->firstWhere('id', $aprobacion['equivalencia']->materia_id);

                    if ($materiaDelPlan === null) {
                        continue;
                    }

                    $creditos = $materiaDelPlan->pivot->creditos;

                    return $aprobacion + [
                        'matricula' => $matricula,
                        'creditos' => $creditos === null ? null : (int) $creditos,
                    ];
                }

                return null;
            })
            ->filter(fn (?array $aprobacion) => $aprobacion !== null)
            ->values();
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
            ->with(['curso.carrera', 'planEstudio.carrera'])
            ->orderByDesc('updated_at')
            ->get()
            ->filter(fn (Inscripcion $inscripcion) => $inscripcion->estaAprobada() && $inscripcion->curso->materia_id !== null)
            ->groupBy(fn (Inscripcion $inscripcion) => $inscripcion->curso->materia_id)
            ->map(fn (Collection $grupo) => $grupo->first());

        if ($aprobadasPorMateria->isEmpty()) {
            return collect();
        }

        return MateriaEquivalencia::query()
            ->with(['materia', 'equivalente'])
            ->whereIn('equivalente_id', $aprobadasPorMateria->keys())
            ->get()
            ->map(fn (MateriaEquivalencia $equivalencia) => [
                'equivalencia' => $equivalencia,
                'inscripcion' => $aprobadasPorMateria->get($equivalencia->equivalente_id),
            ]);
    }

    /**
     * @param  array{equivalencia: MateriaEquivalencia, inscripcion: Inscripcion, matricula: Matricula, creditos: int|null}  $aprobacion
     */
    private function aConvalidacion(array $aprobacion): Convalidacion
    {
        return new Convalidacion(
            materia: $aprobacion['equivalencia']->materia,
            equivalente: $aprobacion['equivalencia']->equivalente,
            carreraDestino: $aprobacion['matricula']->carrera,
            carreraOrigen: $aprobacion['inscripcion']->planEstudio?->carrera ?? $aprobacion['inscripcion']->curso->carrera,
            nota: (float) $aprobacion['inscripcion']->nota_final,
            fecha: $aprobacion['inscripcion']->updated_at,
            creditos: $aprobacion['creditos'],
        );
    }
}
