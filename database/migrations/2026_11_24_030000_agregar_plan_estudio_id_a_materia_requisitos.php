<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF04: los requisitos pasan a pertenecer al plan de estudios y no solo a la materia.
     */
    public function up(): void
    {
        Schema::table('materia_requisitos', function (Blueprint $table) {
            $table->dropUnique(['materia_id', 'requisito_id']);
            $table->foreignId('plan_estudio_id')
                ->nullable()
                ->after('id')
                ->constrained('planes_estudio')
                ->cascadeOnDelete();
        });

        // Cada requisito existente se asigna al primer plan que contiene ambas materias.
        DB::statement(<<<'SQL'
            UPDATE materia_requisitos AS requisito
            SET plan_estudio_id = (
                SELECT MIN(materia_plan.plan_estudio_id)
                FROM materia_plan_estudio AS materia_plan
                JOIN materia_plan_estudio AS requisito_plan
                  ON requisito_plan.plan_estudio_id = materia_plan.plan_estudio_id
                 AND requisito_plan.materia_id = requisito.requisito_id
                WHERE materia_plan.materia_id = requisito.materia_id
            )
        SQL);

        // Un requisito entre materias que no comparten plan ya no tiene sentido.
        DB::table('materia_requisitos')->whereNull('plan_estudio_id')->delete();

        DB::statement('ALTER TABLE materia_requisitos ALTER COLUMN plan_estudio_id SET NOT NULL');

        Schema::table('materia_requisitos', function (Blueprint $table) {
            $table->unique(
                ['plan_estudio_id', 'materia_id', 'requisito_id'],
                'materia_requisitos_plan_materia_requisito_unique',
            );
        });
    }

    public function down(): void
    {
        // Al volver al esquema anterior solo puede quedar un requisito por par de materias.
        DB::statement(<<<'SQL'
            DELETE FROM materia_requisitos AS sobrante
            USING materia_requisitos AS conservado
            WHERE sobrante.id > conservado.id
              AND sobrante.materia_id = conservado.materia_id
              AND sobrante.requisito_id = conservado.requisito_id
        SQL);

        Schema::table('materia_requisitos', function (Blueprint $table) {
            $table->dropUnique('materia_requisitos_plan_materia_requisito_unique');
            $table->dropConstrainedForeignId('plan_estudio_id');
        });

        Schema::table('materia_requisitos', function (Blueprint $table) {
            $table->unique(['materia_id', 'requisito_id']);
        });
    }
};
