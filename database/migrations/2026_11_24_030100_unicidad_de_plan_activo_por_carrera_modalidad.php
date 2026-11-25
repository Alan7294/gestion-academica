<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Solo puede existir un plan activo por carrera y modalidad.
     *
     * El índice parcial de PostgreSQL respalda la validación del formulario y
     * evita que dos procesos concurrentes activen dos planes a la vez.
     */
    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX planes_estudio_activo_por_carrera_modalidad
             ON planes_estudio (carrera_id, modalidad)
             WHERE activo = true'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS planes_estudio_activo_por_carrera_modalidad');
    }
};
