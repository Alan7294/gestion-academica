<?php

namespace App\Enums;

enum EstadoMatricula: string
{
    case Activa = 'activa';
    case Baja = 'baja';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Baja => 'De baja',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $estado) => $estado->value, self::cases());
    }
}
