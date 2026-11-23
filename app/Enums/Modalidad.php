<?php

namespace App\Enums;

enum Modalidad: string
{
    case Modular = 'modular';
    case Semestral = 'semestral';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Modular => 'Modular',
            self::Semestral => 'Semestral',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Modular => 'Avanzas aprobando módulos completos.',
            self::Semestral => 'Avanzas por semestres académicos.',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $modalidad) => $modalidad->value, self::cases());
    }
}
