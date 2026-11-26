<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los créditos dejan de definirse por plan: cada curso guarda los suyos.
     */
    public function up(): void
    {
        Schema::table('materia_plan_estudio', function (Blueprint $table) {
            $table->dropColumn('creditos');
        });
    }

    public function down(): void
    {
        Schema::table('materia_plan_estudio', function (Blueprint $table) {
            $table->unsignedSmallInteger('creditos')->default(0);
        });

        // Se recupera el último curso del plan que cursó la materia, si lo hubo.
        DB::statement('
            UPDATE materia_plan_estudio AS vinculo
            SET creditos = COALESCE((
                SELECT curso.creditos
                FROM cursos AS curso
                WHERE curso.plan_estudio_id = vinculo.plan_estudio_id
                  AND curso.materia_id = vinculo.materia_id
                ORDER BY curso.id DESC
                LIMIT 1
            ), 0)
        ');

        DB::statement('ALTER TABLE materia_plan_estudio ADD CONSTRAINT materia_plan_estudio_creditos_no_negativos CHECK (creditos >= 0)');
    }
};
