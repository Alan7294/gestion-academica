<?php

namespace App\Notifications;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Notifications\Notification;

class NuevoAlumnoInscrito extends Notification
{
    public function __construct(
        private readonly Curso $curso,
        private readonly User $estudiante,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Nuevo alumno inscrito',
            'mensaje' => "{$this->estudiante->name} se inscribió al curso {$this->curso->nombre}.",
            'url' => route('docente.cursos.show', $this->curso),
            'tipo' => 'inscripcion',
        ];
    }
}
