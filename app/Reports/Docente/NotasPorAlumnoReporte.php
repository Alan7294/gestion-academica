<?php

namespace App\Reports\Docente;

use App\Models\Inscripcion;
use App\Models\User;
use App\Reports\Reporte;

/**
 * RF12: notas por alumno de todos sus cursos (docente).
 */
class NotasPorAlumnoReporte implements Reporte
{
    public function __construct(private readonly User $estudiante) {}

    public function titulo(): string
    {
        return "Notas por alumno - {$this->estudiante->name}";
    }

    public function descripcion(): string
    {
        return 'Rendimiento del estudiante en todas las materias en las que estuvo inscrito.';
    }

    public function encabezados(): array
    {
        return [
            'Materia',
            'Código',
            'Periodo',
            'Docente',
            'Evaluaciones',
            'Nota final',
            'Resultado',
        ];
    }

    public function filas(): array
    {
        return Inscripcion::query()
            ->where('user_id', $this->estudiante->id)
            ->with(['curso.periodo', 'curso.docente', 'calificaciones'])
            ->get()
            ->sortBy(fn (Inscripcion $inscripcion) => $inscripcion->curso->nombre)
            ->map(fn (Inscripcion $inscripcion) => [
                $inscripcion->curso->nombre,
                $inscripcion->curso->codigo,
                $inscripcion->curso->periodo->nombre,
                $inscripcion->curso->docente?->name ?? 'Sin asignar',
                $inscripcion->calificaciones->count(),
                $inscripcion->nota_final === null ? '—' : number_format((float) $inscripcion->nota_final, 2),
                $this->resultado($inscripcion->nota_final),
            ])
            ->values()
            ->all();
    }

    public function contexto(): array
    {
        return [
            'Estudiante' => $this->estudiante->name,
            'CI' => $this->estudiante->ci ?? '—',
            'Correo' => $this->estudiante->email,
        ];
    }

    private function resultado(string|float|null $nota): string
    {
        if ($nota === null) {
            return 'Pendiente';
        }

        return (float) $nota >= 51 ? 'Aprobado' : 'Reprobado';
    }
}
