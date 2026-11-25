<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('modalidad', 20)->nullable()->after('plan_estudio_id');
        });

        // Los cursos existentes conservan la modalidad que antes se derivaba del plan.
        DB::statement('UPDATE cursos SET modalidad = planes_estudio.modalidad FROM planes_estudio WHERE planes_estudio.id = cursos.plan_estudio_id');
        DB::table('cursos')->whereNull('modalidad')->update(['modalidad' => 'semestral']);

        Schema::table('cursos', function (Blueprint $table) {
            $table->string('modalidad', 20)->default('semestral')->nullable(false)->change();
        });

        DB::statement("ALTER TABLE cursos ADD CONSTRAINT cursos_modalidad_valida CHECK (modalidad IN ('modular', 'semestral'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cursos DROP CONSTRAINT cursos_modalidad_valida');

        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }
};
