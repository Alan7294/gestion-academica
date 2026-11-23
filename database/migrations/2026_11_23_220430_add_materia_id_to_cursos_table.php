<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula cada curso con la materia del plan que imparte y hace el backfill
     * solo cuando la coincidencia por carrera y código es inequívoca.
     */
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->foreignId('materia_id')
                ->nullable()
                ->after('carrera_id')
                ->constrained('materias')
                ->nullOnDelete();

            $table->index('materia_id');
        });

        DB::statement(<<<'SQL'
            UPDATE cursos c
            SET materia_id = m.id
            FROM materias m
            JOIN planes_estudio p ON p.id = m.plan_estudio_id
            WHERE c.materia_id IS NULL
              AND c.carrera_id = p.carrera_id
              AND p.activo = true
              AND c.codigo = m.codigo
              AND NOT EXISTS (
                  SELECT 1
                  FROM materias otra
                  JOIN planes_estudio otro_plan ON otro_plan.id = otra.plan_estudio_id
                  WHERE otra.codigo = c.codigo
                    AND otro_plan.carrera_id = c.carrera_id
                    AND otro_plan.activo = true
                    AND otra.id <> m.id
              )
            SQL);
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropForeign(['materia_id']);
            $table->dropColumn('materia_id');
        });
    }
};
