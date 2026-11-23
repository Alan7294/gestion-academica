<?php

namespace App\Enums;

enum Rol: string
{
    case Estudiante = 'estudiante';
    case Docente = 'docente';
    case Administrador = 'administrador';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Estudiante => 'Estudiante',
            self::Docente => 'Docente',
            self::Administrador => 'Administrador',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $rol) => $rol->value, self::cases());
    }
}
