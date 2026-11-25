<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El curso se identifica por su materia, que ya aporta el código visible
     * en listados y reportes; no se necesita un código propio ni su unicidad por periodo.
     */
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropUnique(['periodo_id', 'codigo']);
            $table->dropColumn('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->after('docente_id');
            $table->unique(['periodo_id', 'codigo']);
        });
    }
};
