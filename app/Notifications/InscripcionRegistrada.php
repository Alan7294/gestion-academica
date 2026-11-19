<?php

namespace App\Notifications;

use App\Models\Inscripcion;
use Illuminate\Notifications\Notification;

class InscripcionRegistrada extends Notification
{
    public function __construct(private readonly Inscripcion $inscripcion) {}

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
            'titulo' => 'Inscripción confirmada',
            'mensaje' => "Te inscribiste al curso {$this->inscripcion->curso->nombre}.",
            'url' => route('estudiante.inscripciones.index'),
            'tipo' => 'inscripcion',
        ];
    }
}
