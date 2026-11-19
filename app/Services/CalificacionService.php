<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\Inscripcion;
use App\Models\User;
use App\Notifications\NotaRegistrada;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalificacionService
{
    /**
     * RF07: registra una evaluación y recalcula el promedio del estudiante.
     *
     * @param  array{nombre: string, ponderacion: int, nota: string|float, fecha: string}  $datos
     *
     * @throws ValidationException|AuthorizationException
     */
    public function registrar(Inscripcion $inscripcion, array $datos, User $docente): Calificacion
    {
        $inscripcion->loadMissing(['curso', 'estudiante']);

        $this->verificarDocente($inscripcion, $docente);
        $this->verificarDuplicado($inscripcion, $datos['nombre']);
        $this->verificarPonderacion($inscripcion, (int) $datos['ponderacion']);

        $calificacion = DB::transaction(function () use ($inscripcion, $datos, $docente) {
            $calificacion = $inscripcion->calificaciones()->create([
                'registrado_por' => $docente->id,
                'nombre' => $datos['nombre'],
                'ponderacion' => (int) $datos['ponderacion'],
                'nota' => $datos['nota'],
                'fecha' => $datos['fecha'],
            ]);

            $this->recalcular($inscripcion);

            return $calificacion;
        });

        $inscripcion->estudiante->notify(new NotaRegistrada($calificacion));

        return $calificacion;
    }

    /**
     * RF07: edita una calificación existente.
     *
     * @param  array{nombre: string, ponderacion: int, nota: string|float, fecha: string}  $datos
     *
     * @throws ValidationException|AuthorizationException
     */
    public function actualizar(Calificacion $calificacion, array $datos, User $docente): Calificacion
    {
        $inscripcion = $calificacion->inscripcion;
        $inscripcion->loadMissing(['curso', 'estudiante']);

        $this->verificarDocente($inscripcion, $docente);

        $this->verificarPonderacion(
            $inscripcion,
            (int) $datos['ponderacion'],
            $calificacion->id,
        );

        DB::transaction(function () use ($calificacion, $inscripcion, $datos) {
            $calificacion->update([
                'nombre' => $datos['nombre'],
                'ponderacion' => (int) $datos['ponderacion'],
                'nota' => $datos['nota'],
                'fecha' => $datos['fecha'],
            ]);

            $this->recalcular($inscripcion);
        });

        return $calificacion->refresh();
    }

    /**
     * @throws AuthorizationException
     */
    public function eliminar(Calificacion $calificacion, User $docente): void
    {
        $inscripcion = $calificacion->inscripcion;
        $inscripcion->loadMissing('curso');

        $this->verificarDocente($inscripcion, $docente);

        DB::transaction(function () use ($calificacion, $inscripcion) {
            $calificacion->delete();
            $this->recalcular($inscripcion);
        });
    }

    /**
     * Guarda en la inscripción el promedio ponderado vigente.
     */
    private function recalcular(Inscripcion $inscripcion): void
    {
        $inscripcion->unsetRelation('calificaciones');

        $inscripcion->update([
            'nota_final' => $inscripcion->promedioPonderado(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function verificarDocente(Inscripcion $inscripcion, User $docente): void
    {
        if ($docente->esAdministrador()) {
            return;
        }

        if ($inscripcion->curso->docente_id !== $docente->id) {
            throw new AuthorizationException('Solo el docente del curso puede gestionar sus calificaciones.');
        }
    }

    /**
     * @throws ValidationException
     */
    private function verificarDuplicado(Inscripcion $inscripcion, string $nombre): void
    {
        $existe = $inscripcion->calificaciones()->where('nombre', $nombre)->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe una evaluación con ese nombre en este curso.',
            ]);
        }
    }

    /**
     * La suma de ponderaciones de una inscripción no puede superar 100%.
     *
     * @throws ValidationException
     */
    private function verificarPonderacion(Inscripcion $inscripcion, int $ponderacion, ?int $excluirId = null): void
    {
        $suma = $inscripcion->calificaciones()
            ->when($excluirId, fn ($query, $id) => $query->whereKeyNot($id))
            ->sum('ponderacion');

        if ($suma + $ponderacion > 100) {
            throw ValidationException::withMessages([
                'ponderacion' => 'La suma de ponderaciones del curso no puede superar el 100%.',
            ]);
        }
    }
}
