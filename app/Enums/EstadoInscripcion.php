<?php

namespace App\Enums;

enum EstadoInscripcion: string
{
    case Inscrito = 'inscrito';
    case Retirado = 'retirado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Inscrito => 'Inscrito',
            self::Retirado => 'Retirado',
        };
    }
}
