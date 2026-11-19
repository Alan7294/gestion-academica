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

    /**
     * Roles que un visitante puede elegir al registrarse.
     * El administrador solo se asigna desde el panel de gestión de usuarios (RF09).
     *
     * @return array<int, self>
     */
    public static function autoregistrables(): array
    {
        return [self::Estudiante, self::Docente];
    }

    /**
     * @return array<int, string>
     */
    public static function valoresAutoregistrables(): array
    {
        return array_map(fn (self $rol) => $rol->value, self::autoregistrables());
    }
}
