<?php

namespace App\Policies;

use App\Models\Inscripcion;
use App\Models\User;

class InscripcionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Inscripcion $inscripcion): bool
    {
        return $inscripcion->user_id === $user->id || $user->esAdministrador();
    }

    /**
     * Solo el propio estudiante puede retirarse de un curso.
     */
    public function delete(User $user, Inscripcion $inscripcion): bool
    {
        return $inscripcion->user_id === $user->id;
    }
}
