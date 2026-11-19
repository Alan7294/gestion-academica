<?php

namespace App\Reports\Admin;

use App\Models\Curso;
use App\Models\Periodo;
use App\Reports\Reporte;

/**
 * RF12: listado de materias por carrera (administrador).
 */
class MateriasPorCarreraReporte implements Reporte
{
    public function titulo(): string
    {
        return 'Materias por carrera';
    }

    public function descripcion(): string
    {
        return 'Listado de materias registradas, ordenadas por carrera y semestre.';
    }

    public function encabezados(): array
    {
        return [
            'Carrera',
            'Código',
            'Materia',
            'Semestre',
            'Créditos',
            'Docente',
            'Periodo',
            'Inscritos',
        ];
    }

    public function filas(): array
    {
        return Curso::query()
            ->with(['carrera', 'docente', 'periodo'])
            ->withCount('alumnosInscritos')
            ->get()
            ->sortBy([
                fn (Curso $a, Curso $b) => strcmp($a->carrera->nombre, $b->carrera->nombre),
                fn (Curso $a, Curso $b) => $a->semestre <=> $b->semestre,
                fn (Curso $a, Curso $b) => strcmp($a->nombre, $b->nombre),
            ])
            ->map(fn (Curso $curso) => [
                $curso->carrera->nombre,
                $curso->codigo,
                $curso->nombre,
                $curso->semestre,
                $curso->creditos,
                $curso->docente?->name ?? 'Sin asignar',
                $curso->periodo->nombre,
                $curso->alumnos_inscritos_count,
            ])
            ->values()
            ->all();
    }

    public function contexto(): array
    {
        return [
            'Periodo vigente' => Periodo::actual()?->nombre ?? 'Sin periodo activo',
        ];
    }
}
