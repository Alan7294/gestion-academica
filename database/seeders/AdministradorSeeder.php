<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministradorSeeder extends Seeder
{
    /**
     * Usuario administrador inicial del sistema.
     */
    public function run(): void
    {
        $partesDelNombre = [
            'name' => 'Administrador del Sistema',
            'nombres' => 'Administrador',
            'primer_apellido' => 'del Sistema',
        ];

        $admin = User::firstOrCreate(
            ['email' => 'admin@academica.test'],
            $partesDelNombre + [
                'password' => Hash::make('Admin1234!'),
                'activo' => true,
            ],
        );

        if ($admin->nombres === null) {
            $admin->update($partesDelNombre);
        }

        $admin->syncRoles([Rol::Administrador->value]);
    }
}
