<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un teléfono solo puede pertenecer a un usuario; los valores NULL pueden repetirse.
     */
    public function up(): void
    {
        $this->deduplicarTelefonos();

        Schema::table('users', function (Blueprint $table) {
            $table->unique('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telefono']);
        });
    }

    /**
     * Conserva el teléfono del usuario más antiguo; los duplicados y los valores vacíos quedan sin teléfono.
     */
    private function deduplicarTelefonos(): void
    {
        DB::table('users')
            ->whereRaw("TRIM(telefono) = ''")
            ->update(['telefono' => null]);

        $idsConservar = DB::table('users')
            ->selectRaw('MIN(id) as id')
            ->whereNotNull('telefono')
            ->groupBy('telefono')
            ->pluck('id')
            ->all();

        DB::table('users')
            ->whereNotNull('telefono')
            ->whereNotIn('id', $idsConservar)
            ->update(['telefono' => null]);
    }
};
