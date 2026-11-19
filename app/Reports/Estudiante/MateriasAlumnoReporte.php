<?php

namespace App\Reports\Estudiante;

use App\Models\Inscripcion;
use App\Models\User;
use App\Reports\Reporte;

/**
 * RF12: listado de materias del alumno.
 */
class MateriasAlumnoReporte implements Reporte
{
    public function __construct(private readonly User $estudiante) {}

    public function titulo(): string
    {
        return "Materias del alumno - {$this->estudiante->name}";
    }

    public function descripcion(): string
    {
        return 'Materias en las que el estudiante mantiene una inscripción vigente.';
    }

    public function encabezados(): array
    {
        return [
            'Código',
            'Materia',
            'Carrera',
            'Docente',
            'Periodo',
            'Créditos',
            'Horario',
            'Estado',
        ];
    }

    public function filas(): array
    {
        return Inscripcion::query()
            ->where('user_id', $this->estudiante->id)
            ->activas()
            ->with(['curso.carrera', 'curso.docente', 'curso.periodo', 'curso.horarios'])
            ->get()
            ->sortBy(fn (Inscripcion $inscripcion) => $inscripcion->curso->nombre)
            ->map(fn (Inscripcion $inscripcion) => [
                $inscripcion->curso->codigo,
                $inscripcion->curso->nombre,
                $inscripcion->curso->carrera->nombre,
                $inscripcion->curso->docente?->name ?? 'Sin asignar',
                $inscripcion->curso->periodo->nombre,
                $inscripcion->curso->creditos,
                $inscripcion->curso->horarios
                    ->map(fn ($horario) => $horario->dia_semana->etiqueta().' '.$horario->rango_texto)
                    ->implode('; ') ?: 'Sin horario',
                $inscripcion->estado->etiqueta(),
            ])
            ->values()
            ->all();
    }

    public function contexto(): array
    {
        $inscripciones = Inscripcion::query()
            ->where('user_id', $this->estudiante->id)
            ->activas()
            ->with('curso')
            ->get();

        return [
            'Estudiante' => $this->estudiante->name,
            'CI' => $this->estudiante->ci ?? '—',
            'Materias inscritas' => (string) $inscripciones->count(),
            'Créditos' => (string) $inscripciones->sum(fn (Inscripcion $i) => $i->curso->creditos),
        ];
    }
}
