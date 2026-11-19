<?php

namespace App\Reports\Docente;

use App\Models\Curso;
use App\Models\User;
use App\Reports\Reporte;

/**
 * RF12: listado de materias del docente.
 */
class MateriasDocenteReporte implements Reporte
{
    public function __construct(private readonly User $docente) {}

    public function titulo(): string
    {
        return "Materias del docente - {$this->docente->name}";
    }

    public function descripcion(): string
    {
        return 'Materias asignadas al docente con su carga académica y horario.';
    }

    public function encabezados(): array
    {
        return [
            'Código',
            'Materia',
            'Carrera',
            'Periodo',
            'Semestre',
            'Créditos',
            'Inscritos',
            'Horario',
        ];
    }

    public function filas(): array
    {
        return Curso::query()
            ->where('docente_id', $this->docente->id)
            ->with(['carrera', 'periodo', 'horarios'])
            ->withCount('alumnosInscritos')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Curso $curso) => [
                $curso->codigo,
                $curso->nombre,
                $curso->carrera->nombre,
                $curso->periodo->nombre,
                $curso->semestre,
                $curso->creditos,
                $curso->alumnos_inscritos_count,
                $curso->horarios
                    ->map(fn ($horario) => $horario->dia_semana->etiqueta().' '.$horario->rango_texto)
                    ->implode('; ') ?: 'Sin horario',
            ])
            ->all();
    }

    public function contexto(): array
    {
        return [
            'Docente' => $this->docente->name,
            'CI' => $this->docente->ci ?? '—',
            'Correo' => $this->docente->email,
        ];
    }
}
