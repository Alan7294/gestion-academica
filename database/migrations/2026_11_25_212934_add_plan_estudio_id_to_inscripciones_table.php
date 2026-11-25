<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El plan usado al inscribirse queda congelado para calcular créditos y
     * reportes aunque después cambie la malla del estudiante.
     */
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->foreignId('plan_estudio_id')
                ->nullable()
                ->after('user_id')
                ->constrained('planes_estudio')
                ->nullOnDelete();
        });

        DB::table('inscripciones')
            ->select('id', 'curso_id')
            ->orderBy('id')
            ->eachById(function (object $inscripcion): void {
                $planId = DB::table('cursos')->where('id', $inscripcion->curso_id)->value('plan_estudio_id');

                if ($planId !== null) {
                    DB::table('inscripciones')->where('id', $inscripcion->id)
                        ->update(['plan_estudio_id' => $planId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropForeign(['plan_estudio_id']);
            $table->dropColumn('plan_estudio_id');
        });
    }
};
