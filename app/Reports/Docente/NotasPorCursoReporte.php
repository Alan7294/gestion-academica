<?php

namespace App\Reports\Docente;

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Reports\Reporte;

/**
 * RF12: notas por curso de todos los alumnos (docente).
 */
class NotasPorCursoReporte implements Reporte
{
    public function __construct(private readonly Curso $curso) {}

    public function titulo(): string
    {
        return "Notas por curso - {$this->curso->codigo}";
    }

    public function descripcion(): string
    {
        return 'Resumen de calificaciones y nota final de los alumnos inscritos en el curso.';
    }

    public function encabezados(): array
    {
        return [
            'Estudiante',
            'CI',
            'Evaluaciones',
            'Nota final',
            'Resultado',
        ];
    }

    public function filas(): array
    {
        return $this->curso->alumnosInscritos()
            ->with(['estudiante', 'calificaciones'])
            ->get()
            ->sortBy(fn (Inscripcion $inscripcion) => $inscripcion->estudiante->name)
            ->map(fn (Inscripcion $inscripcion) => [
                $inscripcion->estudiante->name,
                $inscripcion->estudiante->ci ?? '—',
                $inscripcion->calificaciones->count(),
                $this->nota($inscripcion->nota_final),
                $this->resultado($inscripcion->nota_final),
            ])
            ->values()
            ->all();
    }

    public function contexto(): array
    {
        return [
            'Curso' => "{$this->curso->codigo} - {$this->curso->nombre}",
            'Carrera' => $this->curso->carrera->nombre,
            'Periodo' => $this->curso->periodo->nombre,
            'Docente' => $this->curso->docente?->name ?? 'Sin asignar',
        ];
    }

    private function nota(string|float|null $nota): string
    {
        return $nota === null ? '—' : number_format((float) $nota, 2);
    }

    private function resultado(string|float|null $nota): string
    {
        if ($nota === null) {
            return 'Pendiente';
        }

        return (float) $nota >= 51 ? 'Aprobado' : 'Reprobado';
    }
}
