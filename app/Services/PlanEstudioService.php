<?php

namespace App\Services;

use App\Models\Materia;
use App\Models\PlanEstudio;
use Illuminate\Support\Facades\DB;

class PlanEstudioService
{
    /**
     * Crea un plan de estudios junto con sus materias y requisitos.
     *
     * @param  array{carrera_id: int, nombre: string, modalidad: string, semestres: int, activo?: bool, materias: array<int, array{id?: int|null, codigo: string, nombre: string, semestre: int, creditos: int, requisitos?: array<int, string>}>}  $datos
     */
    public function crear(array $datos): PlanEstudio
    {
        return DB::transaction(function () use ($datos) {
            $plan = PlanEstudio::create($this->atributos($datos));

            $porCodigo = $this->sincronizarMaterias($plan, $datos['materias']);
            $this->sincronizarRequisitos($porCodigo, $datos['materias']);

            return $plan->load('materias.requisitos');
        });
    }

    /**
     * Actualiza el plan y sincroniza sus materias y requisitos sin perder los identificadores.
     *
     * @param  array{carrera_id: int, nombre: string, modalidad: string, semestres: int, activo?: bool, materias: array<int, array{id?: int|null, codigo: string, nombre: string, semestre: int, creditos: int, requisitos?: array<int, string>}>}  $datos
     */
    public function actualizar(PlanEstudio $plan, array $datos): PlanEstudio
    {
        return DB::transaction(function () use ($plan, $datos) {
            $plan->update($this->atributos($datos));

            $porCodigo = $this->sincronizarMaterias($plan, $datos['materias']);
            $this->sincronizarRequisitos($porCodigo, $datos['materias']);

            return $plan->load('materias.requisitos');
        });
    }

    public function eliminar(PlanEstudio $plan): void
    {
        // Las materias y sus requisitos se eliminan en cascada por las llaves foráneas.
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
     * @param  array<int, array{id?: int|null, codigo: string, nombre: string, semestre: int, creditos: int, requisitos?: array<int, string>}>  $materias
     * @return array<string, Materia>
     */
    private function sincronizarMaterias(PlanEstudio $plan, array $materias): array
    {
        $ids = collect($materias)->pluck('id')->filter()->values();

        $consulta = $plan->materias();

        if ($ids->isNotEmpty()) {
            $consulta->whereNotIn('id', $ids->all());
        }

        // Los requisitos de una materia eliminada desaparecen en cascada.
        $consulta->delete();

        $porCodigo = [];

        foreach ($materias as $materia) {
            $atributos = [
                'codigo' => $materia['codigo'],
                'nombre' => $materia['nombre'],
                'semestre' => $materia['semestre'],
                'creditos' => $materia['creditos'],
            ];

            if (! empty($materia['id'])) {
                $registro = $plan->materias()->findOrFail($materia['id']);
                $registro->update($atributos);
            } else {
                $registro = $plan->materias()->create($atributos);
            }

            $porCodigo[$materia['codigo']] = $registro;
        }

        return $porCodigo;
    }

    /**
     * @param  array<string, Materia>  $porCodigo
     * @param  array<int, array{id?: int|null, codigo: string, nombre: string, semestre: int, creditos: int, requisitos?: array<int, string>}>  $materias
     */
    private function sincronizarRequisitos(array $porCodigo, array $materias): void
    {
        foreach ($materias as $materia) {
            $idsRequisitos = collect($materia['requisitos'] ?? [])
                ->map(fn (string $codigo) => $porCodigo[$codigo]->id)
                ->all();

            $porCodigo[$materia['codigo']]->requisitos()->sync($idsRequisitos);
        }
    }
}
