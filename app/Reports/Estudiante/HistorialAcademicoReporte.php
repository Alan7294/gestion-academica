<?php

namespace App\Reports\Estudiante;

use App\Enums\EstadoInscripcion;
use App\Models\Inscripcion;
use App\Models\User;
use App\Reports\FiltrosReporte;
use App\Reports\Reporte;
use App\Services\ConvalidacionService;

/**
 * RF12: historial académico del alumno.
 */
class HistorialAcademicoReporte implements Reporte
{
    public function __construct(
        private readonly User $estudiante,
        private readonly FiltrosReporte $filtros = new FiltrosReporte,
        private readonly ConvalidacionService $convalidaciones = new ConvalidacionService,
    ) {}

    public function titulo(): string
    {
        return "Historial académico - {$this->estudiante->name}";
    }

    public function descripcion(): string
    {
        return 'Historial de todas las materias cursadas por el estudiante, con su resultado final.';
    }

    public function encabezados(): array
    {
        return [
            'Periodo',
            'Código',
            'Materia',
            'Docente',
            'Créditos',
            'Estado',
            'Nota final',
            'Resultado',
        ];
    }

    public function filas(): array
    {
        $inscripciones = Inscripcion::query()
            ->where('user_id', $this->estudiante->id)
            ->with(['curso.periodo', 'curso.docente']);

        $this->filtros->aplicarAInscripciones($inscripciones);

        $filas = $inscripciones->get()
            ->sortByDesc(fn (Inscripcion $inscripcion) => $inscripcion->curso->periodo->fecha_inicio)
            ->map(fn (Inscripcion $inscripcion) => [
                $inscripcion->curso->periodo->nombre,
                $inscripcion->curso->codigo,
                $inscripcion->curso->nombre,
                $inscripcion->curso->docente?->name ?? 'Sin asignar',
                $inscripcion->curso->creditos,
                $inscripcion->estado->etiqueta(),
                $inscripcion->nota_final === null ? '—' : number_format((float) $inscripcion->nota_final, 2),
                $this->resultado($inscripcion->nota_final),
            ])
            ->values()
            ->all();

        $convalidaciones = $this->convalidaciones->paraEstudiante($this->estudiante);

        foreach ($convalidaciones as $convalidacion) {
            if ($this->filtros->carreraId !== null && $convalidacion->materia->carrera_id !== $this->filtros->carreraId) {
                continue;
            }

            if ($this->filtros->notaDesde !== null && $convalidacion->nota < $this->filtros->notaDesde) {
                continue;
            }

            if ($this->filtros->notaHasta !== null && $convalidacion->nota > $this->filtros->notaHasta) {
                continue;
            }

            if ($this->filtros->estado !== null && $this->filtros->estado !== EstadoInscripcion::Inscrito->value) {
                continue;
            }

            $filas[] = [
                'Convalidación',
                $convalidacion->materia->codigo,
                $convalidacion->materia->nombre,
                '—',
                $convalidacion->creditos ?? '—',
                'Convalidada',
                number_format($convalidacion->nota, 2),
                'Aprobado por equivalencia: '.$convalidacion->origen(),
            ];
        }

        return $filas;
    }

    public function contexto(): array
    {
        return array_merge($this->filtros->contexto(), [
            'Estudiante' => $this->estudiante->name,
            'CI' => $this->estudiante->ci ?? '—',
            'Correo' => $this->estudiante->email,
        ]);
    }

    private function resultado(string|float|null $nota): string
    {
        if ($nota === null) {
            return 'Pendiente';
        }

        return (float) $nota >= 51 ? 'Aprobado' : 'Reprobado';
    }
}
