<?php

namespace App\Reports;

use App\Enums\EstadoInscripcion;
use App\Enums\Rol;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Catálogos disponibles en los selectores de la vista previa (RF12).
 */
class OpcionesFiltrosReporte
{
    /**
     * @return array{
     *     periodos: EloquentCollection<int, Periodo>,
     *     carreras: EloquentCollection<int, Carrera>,
     *     cursos: EloquentCollection<int, Curso>,
     *     docentes: EloquentCollection<int, User>,
     *     estados: array<int, EstadoInscripcion>
     * }
     */
    public static function para(?User $usuario = null): array
    {
        $cursos = Curso::query()->with('carrera')->orderBy('nombre');
        $docentes = User::role(Rol::Docente->value)->orderBy('name');

        if ($usuario?->hasRole(Rol::Docente->value)) {
            $cursos->delDocente($usuario);
            $docentes->whereKey($usuario->id);
        } elseif ($usuario?->hasRole(Rol::Estudiante->value)) {
            $cursosIds = Inscripcion::query()
                ->where('user_id', $usuario->id)
                ->pluck('curso_id')
                ->filter()
                ->values()
                ->all();

            $cursos->whereIn('id', $cursosIds);
            $docentes->whereIn('id', Curso::query()
                ->whereIn('id', $cursosIds)
                ->pluck('docente_id')
                ->filter()
                ->values()
                ->all());
        }

        return [
            'periodos' => Periodo::query()->orderByDesc('fecha_inicio')->get(),
            'carreras' => Carrera::query()->orderBy('nombre')->get(),
            'cursos' => $cursos->get(),
            'docentes' => $docentes->get(),
            'estados' => EstadoInscripcion::cases(),
        ];
    }
}
