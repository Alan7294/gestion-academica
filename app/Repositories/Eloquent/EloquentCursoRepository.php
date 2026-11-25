<?php

namespace App\Repositories\Eloquent;

use App\Models\Curso;
use App\Repositories\Contracts\CursoRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCursoRepository implements CursoRepository
{
    public function paginar(array $filtros = [], int $porPagina = 10): LengthAwarePaginator
    {
        return Curso::query()
            ->with(['carrera', 'periodo', 'docente', 'materia', 'horarios'])
            ->when($filtros['carrera_id'] ?? null, fn ($q, $id) => $q->where('carrera_id', $id))
            ->when($filtros['periodo_id'] ?? null, fn ($q, $id) => $q->where('periodo_id', $id))
            ->when($filtros['docente_id'] ?? null, fn ($q, $id) => $q->where('docente_id', $id))
            ->when($filtros['busqueda'] ?? null, function ($q, $texto) {
                $q->where(function ($sub) use ($texto) {
                    $sub->where('nombre', 'ilike', "%{$texto}%")
                        ->orWhereHas('materia', fn ($q) => $q->where('codigo', 'ilike', "%{$texto}%"));
                });
            })
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function crear(array $datos): Curso
    {
        return Curso::create($datos);
    }

    public function actualizar(Curso $curso, array $datos): Curso
    {
        $curso->update($datos);

        return $curso->refresh();
    }

    public function eliminar(Curso $curso): void
    {
        $curso->delete();
    }
}
