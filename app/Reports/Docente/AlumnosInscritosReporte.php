<?php

namespace App\Reports\Docente;

use App\Enums\EstadoInscripcion;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Reports\FiltrosReporte;
use App\Reports\Reporte;
use Illuminate\Database\Eloquent\Builder;

/**
 * RF12: listado de alumnos inscritos a un curso (docente).
 */
class AlumnosInscritosReporte implements Reporte
{
    public function __construct(
        private readonly Curso $curso,
        private readonly FiltrosReporte $filtros = new FiltrosReporte,
    ) {}

    public function titulo(): string
    {
        return "Alumnos inscritos - {$this->curso->codigo}";
    }

    public function descripcion(): string
    {
        return 'Listado de estudiantes con inscripción vigente en el curso.';
    }

    public function encabezados(): array
    {
        return [
            'Estudiante',
            'CI',
            'Correo',
            'Teléfono',
            'Inscrito el',
            'Estado',
        ];
    }

    public function filas(): array
    {
        return $this->inscripcionesFiltradas()
            ->with('estudiante')
            ->orderBy('inscrito_en')
            ->get()
            ->map(fn (Inscripcion $inscripcion) => [
                $inscripcion->estudiante->name,
                $inscripcion->estudiante->ci ?? '—',
                $inscripcion->estudiante->email,
                $inscripcion->estudiante->telefono ?? '—',
                $inscripcion->inscrito_en?->format('d/m/Y') ?? '—',
                $inscripcion->estado->etiqueta(),
            ])
            ->all();
    }

    public function contexto(): array
    {
        return array_merge($this->filtros->contexto(), [
            'Curso' => "{$this->curso->codigo} - {$this->curso->nombre}",
            'Carrera' => $this->curso->carrera->nombre,
            'Periodo' => $this->curso->periodo->nombre,
            'Total inscritos' => (string) $this->inscripcionesFiltradas()->count(),
        ]);
    }

    private function inscripcionesFiltradas(): Builder
    {
        $inscripciones = Inscripcion::query()->where('curso_id', $this->curso->id);

        return $this->filtros->aplicarAInscripciones(
            $inscripciones,
            EstadoInscripcion::Inscrito->value,
        );
    }
}
