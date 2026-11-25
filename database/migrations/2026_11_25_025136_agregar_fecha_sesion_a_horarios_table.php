<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            // Fecha concreta de una sesión modular; nula en los horarios semanales.
            $table->date('fecha_sesion')->nullable()->after('fecha_fin');
        });

        DB::statement('ALTER TABLE horarios DROP CONSTRAINT horarios_sin_choque_docente');
        DB::statement('ALTER TABLE horarios DROP COLUMN vigencia');

        // Semestral: vigencia de todo el periodo. Modular: solo el día de esa sesión.
        // Así las sesiones de un mismo módulo no chocan entre sí, pero dos módulos
        // que se solapan en un día y franja sí son rechazados por la base de datos.
        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD COLUMN vigencia daterange
            GENERATED ALWAYS AS (
                daterange(
                    COALESCE(fecha_sesion, fecha_inicio),
                    COALESCE(fecha_sesion + 1, fecha_fin + 1),
                    '[)'
                )
            ) STORED
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD CONSTRAINT horarios_sin_choque_docente
            EXCLUDE USING gist (docente_id WITH =, dia_semana WITH =, rango WITH &&, vigencia WITH &&)
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE horarios DROP CONSTRAINT horarios_sin_choque_docente');
        DB::statement('ALTER TABLE horarios DROP COLUMN vigencia');

        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD COLUMN vigencia daterange
            GENERATED ALWAYS AS (daterange(fecha_inicio, fecha_fin, '[]')) STORED
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD CONSTRAINT horarios_sin_choque_docente
            EXCLUDE USING gist (docente_id WITH =, dia_semana WITH =, rango WITH &&, vigencia WITH &&)
        SQL);

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn('fecha_sesion');
        });
    }
};
