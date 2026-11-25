<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula cada curso al plan que define el semestre y los créditos de su materia.
     *
     * No se eliminan nombre, créditos ni semestre de cursos: siguen siendo columnas
     * internas derivadas del plan para no romper reportes ni notificaciones.
     */
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->foreignId('plan_estudio_id')
                ->nullable()
                ->after('materia_id')
                ->constrained('planes_estudio')
                ->restrictOnDelete();

            $table->index('plan_estudio_id');
        });

        // Se elige un solo plan por curso, priorizando el activo y el que ya
        // coincidía con el semestre y los créditos registrados en el curso.
        DB::statement(<<<'SQL'
            UPDATE cursos c
            SET plan_estudio_id = candidato.plan_estudio_id
            FROM (
                SELECT DISTINCT ON (cur.id)
                    cur.id AS curso_id,
                    mp.plan_estudio_id
                FROM cursos cur
                JOIN materia_plan_estudio mp ON mp.materia_id = cur.materia_id
                JOIN planes_estudio pe ON pe.id = mp.plan_estudio_id AND pe.carrera_id = cur.carrera_id
                WHERE cur.plan_estudio_id IS NULL
                ORDER BY cur.id,
                         pe.activo DESC,
                         (mp.semestre = cur.semestre) DESC,
                         (mp.creditos = cur.creditos) DESC,
                         pe.id DESC
            ) candidato
            WHERE c.id = candidato.curso_id
            SQL);

        DB::statement(<<<'SQL'
            UPDATE cursos c
            SET nombre = m.nombre,
                semestre = mp.semestre,
                creditos = mp.creditos
            FROM materia_plan_estudio mp
            JOIN materias m ON m.id = mp.materia_id
            WHERE c.plan_estudio_id = mp.plan_estudio_id
              AND c.materia_id = mp.materia_id
            SQL);
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropForeign(['plan_estudio_id']);
            $table->dropIndex(['plan_estudio_id']);
            $table->dropColumn('plan_estudio_id');
        });
    }
};
