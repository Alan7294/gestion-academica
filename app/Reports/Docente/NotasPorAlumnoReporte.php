<?php

namespace App\Reports\Docente;

use App\Models\Inscripcion;
use App\Reports\Reporte;

/**
 * RF12: notas de un estudiante en la inscripción seleccionada (docente).
 */
class NotasPorAlumnoReporte implements Reporte
{
    public function __construct(private readonly Inscripcion $inscripcion) {}

    public function titulo(): string
    {
        return "Notas por alumno - {$this->inscripcion->estudiante->name}";
    }

    public function descripcion(): string
    {
        return 'Rendimiento del estudiante en el curso seleccionado.';
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
        $inscripcion = $this->inscripcion->loadMissing([
            'curso.periodo',
            'curso.docente',
            'calificaciones',
        ]);

        return [[
            $inscripcion->curso->nombre,
            $inscripcion->curso->codigo,
            $inscripcion->curso->periodo->nombre,
            $inscripcion->curso->docente?->name ?? 'Sin asignar',
            $inscripcion->calificaciones->count(),
            $inscripcion->nota_final === null ? '—' : number_format((float) $inscripcion->nota_final, 2),
            $this->resultado($inscripcion->nota_final),
        ]];
    }

    public function contexto(): array
    {
        $estudiante = $this->inscripcion->estudiante;
        $curso = $this->inscripcion->curso->loadMissing('periodo');

        return [
            'Estudiante' => $estudiante->name,
            'CI' => $estudiante->ci ?? '—',
            'Correo' => $estudiante->email,
            'Curso' => "{$curso->codigo} - {$curso->nombre}",
            'Periodo' => $curso->periodo->nombre,
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
