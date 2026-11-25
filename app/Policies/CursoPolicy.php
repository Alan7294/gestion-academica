<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\User;

class CursoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Un docente solo puede ver los cursos que dicta; un estudiante, los activos.
     */
    public function view(User $user, Curso $curso): bool
    {
        if ($user->esAdministrador()) {
            return true;
        }

        if ($user->esDocente()) {
            return $curso->docente_id === $user->id;
        }

        return $user->esEstudiante() && $curso->activo;
    }

    /**
     * RF05: solo estudiantes, en cursos activos.
     * La matrícula activa en la carrera y la vigencia del periodo se validan en el servicio
     * para dar un mensaje claro al estudiante.
     */
    public function inscribirse(User $user, Curso $curso): bool
    {
        return $user->esEstudiante() && $curso->activo;
    }

    public function gestionar(User $user): bool
    {
        return $user->esAdministrador();
    }
}
