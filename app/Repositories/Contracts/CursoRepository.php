<?php

namespace App\Repositories\Contracts;

use App\Models\Curso;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CursoRepository
{
    /**
     * @param  array{carrera_id?: int, periodo_id?: int, docente_id?: int, busqueda?: string}  $filtros
     */
    public function paginar(array $filtros = [], int $porPagina = 10): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos): Curso;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(Curso $curso, array $datos): Curso;

    public function eliminar(Curso $curso): void;
}
