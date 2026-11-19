<?php

namespace App\Reports\Estudiante;

use App\Models\Inscripcion;
use App\Models\User;
use App\Reports\Reporte;

/**
 * RF12: notas de todas las materias del alumno, con el detalle de cada evaluación.
 */
class NotasTodasMateriasReporte implements Reporte
{
    public function __construct(private readonly User $estudiante) {}

    public function titulo(): string
    {
        return "Notas de todas las materias - {$this->estudiante->name}";
    }

    public function descripcion(): string
    {
        return 'Detalle de cada evaluación registrada en las materias del estudiante.';
    }

    public function encabezados(): array
    {
        return [
            'Materia',
            'Periodo',
            'Evaluación',
            'Ponderación %',
            'Nota',
            'Fecha',
        ];
    }

    public function filas(): array
    {
        return Inscripcion::query()
            ->where('user_id', $this->estudiante->id)
            ->with(['curso.periodo', 'calificaciones'])
            ->get()
            ->flatMap(fn (Inscripcion $inscripcion) => $inscripcion->calificaciones->map(fn ($calificacion) => [
                $inscripcion->curso->nombre,
                $inscripcion->curso->periodo->nombre,
                $calificacion->nombre,
                $calificacion->ponderacion,
                number_format((float) $calificacion->nota, 2),
                $calificacion->fecha->format('d/m/Y'),
            ]))
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
}
