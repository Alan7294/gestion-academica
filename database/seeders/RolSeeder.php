<?php

namespace Database\Seeders;

use App\Enums\Rol;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolSeeder extends Seeder
{
    /**
     * Crea los tres roles del sistema (RF02, RF09).
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Rol::cases() as $rol) {
            Role::findOrCreate($rol->value, 'web');
        }
    }
}
