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
            // Se copia el periodo del curso, igual que ya se hace con el docente.
            $table->foreignId('periodo_id')->nullable()->after('curso_id')->constrained('periodos')->restrictOnDelete();
            $table->date('fecha_inicio')->nullable()->after('hora_fin');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });

        // Los horarios existentes son semanales y cubren todo su periodo.
        DB::statement(<<<'SQL'
            UPDATE horarios h
            SET periodo_id = c.periodo_id,
                fecha_inicio = p.fecha_inicio,
                fecha_fin = p.fecha_fin
            FROM cursos c
            JOIN periodos p ON p.id = c.periodo_id
            WHERE h.curso_id = c.id
        SQL);

        DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_vigencia_requerida CHECK (periodo_id IS NOT NULL AND fecha_inicio IS NOT NULL AND fecha_fin IS NOT NULL AND fecha_fin >= fecha_inicio)');

        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD COLUMN vigencia daterange
            GENERATED ALWAYS AS (daterange(fecha_inicio, fecha_fin, '[]')) STORED
        SQL);

        DB::statement('ALTER TABLE horarios DROP CONSTRAINT horarios_sin_choque_docente');

        // El choque del docente ahora se evalúa también por vigencia: la misma
        // franja sí puede repetirse en otro periodo o en módulos distintos.
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
        DB::statement('ALTER TABLE horarios DROP CONSTRAINT horarios_vigencia_requerida');

        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD CONSTRAINT horarios_sin_choque_docente
            EXCLUDE USING gist (docente_id WITH =, dia_semana WITH =, rango WITH &&)
        SQL);

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('periodo_id');
            $table->dropColumn(['fecha_inicio', 'fecha_fin']);
        });
    }
};
