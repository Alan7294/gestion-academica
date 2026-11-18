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
        $admin = User::firstOrCreate(
            ['email' => 'admin@academica.test'],
            [
                'name' => 'Administrador del Sistema',
                'password' => Hash::make('Admin1234!'),
                'activo' => true,
            ],
        );

        $admin->syncRoles([Rol::Administrador->value]);
    }
}
