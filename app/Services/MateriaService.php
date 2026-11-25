<?php

namespace App\Services;

use App\Models\Materia;
use Illuminate\Validation\ValidationException;

class MateriaService
{
    /**
     * @param  array{carrera_id: int, codigo: string, nombre: string}  $datos
     */
    public function crear(array $datos): Materia
    {
        return Materia::create($datos);
    }

    /**
     * @param  array{carrera_id: int, codigo: string, nombre: string}  $datos
     */
    public function actualizar(Materia $materia, array $datos): Materia
    {
        $materia->update($datos);

        return $materia;
    }

    /**
     * @throws ValidationException
     */
    public function eliminar(Materia $materia): void
    {
        if ($materia->cursos()->exists()) {
            throw ValidationException::withMessages([
                'materia' => 'No se puede eliminar una materia que tiene cursos registrados.',
            ]);
        }

        $materia->requeridaPor()->detach();
        $materia->requisitos()->detach();
        $materia->planesEstudio()->detach();

        $materia->delete();
    }
}
