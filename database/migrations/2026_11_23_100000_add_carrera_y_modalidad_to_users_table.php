<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF04: la carrera y la modalidad solo aplican a las cuentas de estudiante.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('carrera_id')
                ->nullable()
                ->after('activo')
                ->constrained('carreras')
                ->nullOnDelete();

            $table->string('modalidad', 20)->nullable()->after('carrera_id');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_modalidad_valida CHECK (modalidad IS NULL OR modalidad IN ('modular', 'semestral'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_modalidad_valida');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carrera_id');
            $table->dropColumn('modalidad');
        });
    }
};
