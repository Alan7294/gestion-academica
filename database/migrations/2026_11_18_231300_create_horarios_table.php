<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            // Se copia el docente del curso para poder garantizar el choque de horario en la BD.
            $table->foreignId('docente_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('aula', 50)->nullable();
            $table->timestamps();

            $table->index(['curso_id', 'dia_semana']);
        });

        DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_dia_valido CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_rango_valido CHECK (hora_fin > hora_inicio)');

        // btree_gist habilita el operador de igualdad dentro de un índice GiST.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        // Columna calculada que convierte el horario semanal en un rango de tiempo comparable.
        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD COLUMN rango tsrange
            GENERATED ALWAYS AS (
                tsrange(
                    date '2000-01-01' + hora_inicio,
                    date '2000-01-01' + hora_fin
                )
            ) STORED
        SQL);

        // Regla de negocio: un docente no puede tener dos clases superpuestas el mismo día.
        DB::statement(<<<'SQL'
            ALTER TABLE horarios
            ADD CONSTRAINT horarios_sin_choque_docente
            EXCLUDE USING gist (docente_id WITH =, dia_semana WITH =, rango WITH &&)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
