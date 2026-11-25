<?php

namespace App\Reports\Admin;

use App\Models\Curso;
use App\Models\Periodo;
use App\Reports\FiltrosReporte;
use App\Reports\Reporte;

/**
 * RF12: listado de materias por carrera (administrador).
 */
class MateriasPorCarreraReporte implements Reporte
{
    public function __construct(private readonly FiltrosReporte $filtros = new FiltrosReporte) {}

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
        $cursos = Curso::query()
            ->with(['carrera', 'materia', 'docente', 'periodo'])
            ->withCount('alumnosInscritos');

        $this->filtros->aplicarACursos($cursos);

        return $cursos->get()
            ->sortBy([
                fn (Curso $a, Curso $b) => strcmp($a->carrera->nombre, $b->carrera->nombre),
                fn (Curso $a, Curso $b) => $a->semestre <=> $b->semestre,
                fn (Curso $a, Curso $b) => strcmp($a->nombre, $b->nombre),
            ])
            ->map(fn (Curso $curso) => [
                $curso->carrera->nombre,
                $curso->materia?->codigo ?? '—',
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
        return array_merge($this->filtros->contexto(), [
            'Periodo vigente' => Periodo::actual()?->nombre ?? 'Sin periodo activo',
        ]);
    }
}
