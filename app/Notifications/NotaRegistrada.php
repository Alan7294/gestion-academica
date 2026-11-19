<?php

namespace App\Notifications;

use App\Models\Calificacion;
use Illuminate\Notifications\Notification;

class NotaRegistrada extends Notification
{
    public function __construct(private readonly Calificacion $calificacion) {}

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
        $curso = $this->calificacion->inscripcion->curso;

        return [
            'titulo' => 'Nueva calificación',
            'mensaje' => "Se publicó {$this->calificacion->nombre} ({$this->calificacion->nota}) en {$curso->nombre}.",
            'url' => route('estudiante.notas.index'),
            'tipo' => 'calificacion',
        ];
    }
}
