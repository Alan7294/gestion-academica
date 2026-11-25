<?php

namespace App\Reports\Docente;

use App\Enums\EstadoInscripcion;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Reports\FiltrosReporte;
use App\Reports\Reporte;

/**
 * RF12: notas por curso de todos los alumnos (docente).
 */
class NotasPorCursoReporte implements Reporte
{
    public function __construct(
        private readonly Curso $curso,
        private readonly FiltrosReporte $filtros = new FiltrosReporte,
    ) {}

    public function titulo(): string
    {
        return 'Notas por curso - '.$this->codigoMateria();
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
        $inscripciones = Inscripcion::query()
            ->where('curso_id', $this->curso->id)
            ->with(['estudiante', 'calificaciones']);

        $this->filtros->aplicarAInscripciones($inscripciones, EstadoInscripcion::Inscrito->value);

        return $inscripciones->get()
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
        return array_merge($this->filtros->contexto(), [
            'Curso' => $this->codigoMateria()." - {$this->curso->nombre}",
            'Carrera' => $this->curso->carrera->nombre,
            'Periodo' => $this->curso->periodo->nombre,
            'Docente' => $this->curso->docente?->name ?? 'Sin asignar',
        ]);
    }

    private function codigoMateria(): string
    {
        return $this->curso->loadMissing('materia')->materia?->codigo ?? '—';
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
