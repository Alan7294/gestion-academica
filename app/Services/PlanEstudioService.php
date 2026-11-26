<?php

namespace App\Services;

use App\Models\Materia;
use App\Models\PlanEstudio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanEstudioService
{
    /**
     * Crea un plan de estudios, vincula materias del catálogo y registra requisitos.
     *
     * @param  array{carrera_id: int, nombre: string, modalidad: string, semestres: int, activo?: bool, materias: array<int, array{id: int, semestre: int, requisitos?: array<int, int>}>}  $datos
     */
    public function crear(array $datos): PlanEstudio
    {
        return DB::transaction(function () use ($datos) {
            $plan = PlanEstudio::create($this->atributos($datos));

            $porId = $this->sincronizarMaterias($plan, $datos['materias']);
            $this->sincronizarRequisitos($plan, $porId, $datos['materias']);

            return $plan->load('materias.requisitos');
        });
    }

    /**
     * Actualiza el plan y sincroniza sus vínculos sin cambiar los identificadores del catálogo.
     *
     * @param  array{carrera_id: int, nombre: string, modalidad: string, semestres: int, activo?: bool, materias: array<int, array{id: int, semestre: int, requisitos?: array<int, int>}>}  $datos
     */
    public function actualizar(PlanEstudio $plan, array $datos): PlanEstudio
    {
        return DB::transaction(function () use ($plan, $datos) {
            $plan->update($this->atributos($datos));

            $porId = $this->sincronizarMaterias($plan, $datos['materias']);
            $this->sincronizarRequisitos($plan, $porId, $datos['materias']);

            return $plan->load('materias.requisitos');
        });
    }

    public function eliminar(PlanEstudio $plan): void
    {
        // Los vínculos con el catálogo y los requisitos del plan se eliminan en cascada.
        $plan->delete();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function atributos(array $datos): array
    {
        return [
            'carrera_id' => $datos['carrera_id'],
            'nombre' => $datos['nombre'],
            'modalidad' => $datos['modalidad'],
            'semestres' => $datos['semestres'],
            'activo' => $datos['activo'] ?? true,
        ];
    }

    /**
     * @param  array<int, array{id: int, semestre: int, requisitos?: array<int, int>}>  $materias
     * @return Collection<int, Materia>
     */
    private function sincronizarMaterias(PlanEstudio $plan, array $materias): Collection
    {
        $valores = collect($materias)->mapWithKeys(fn (array $materia) => [
            (int) $materia['id'] => [
                'semestre' => (int) $materia['semestre'],
            ],
        ]);

        $plan->materias()->sync($valores->all());

        return $plan->materias()
            ->whereIn('materias.id', $valores->keys()->all())
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, Materia>  $porId
     * @param  array<int, array{id: int, semestre: int, requisitos?: array<int, int>}>  $materias
     */
    private function sincronizarRequisitos(PlanEstudio $plan, Collection $porId, array $materias): void
    {
        foreach ($materias as $materia) {
            $idsRequisitos = collect($materia['requisitos'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $porId->get((int) $materia['id'])->requisitosDelPlan($plan)->sync($idsRequisitos);
        }

        // Las materias que salieron del plan no pueden conservar requisitos dentro de él.
        $idsDelPlan = $porId->keys()->map(fn ($id) => (int) $id)->all();

        DB::table('materia_requisitos')
            ->where('plan_estudio_id', $plan->id)
            ->where(function ($query) use ($idsDelPlan) {
                $query->whereNotIn('materia_id', $idsDelPlan)
                    ->orWhereNotIn('requisito_id', $idsDelPlan);
            })
            ->delete();
    }
}
