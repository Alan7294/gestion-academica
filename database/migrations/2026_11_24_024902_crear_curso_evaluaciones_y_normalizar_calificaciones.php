<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las ponderaciones pasan a definirse una sola vez por curso.
     */
    public function up(): void
    {
        Schema::create('curso_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->decimal('ponderacion', 5, 2);
            $table->timestamps();

            $table->unique(['curso_id', 'nombre']);
        });

        DB::statement('ALTER TABLE curso_evaluaciones ADD CONSTRAINT curso_evaluaciones_ponderacion_valida CHECK (ponderacion > 0 AND ponderacion <= 100)');

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->foreignId('curso_evaluacion_id')
                ->nullable()
                ->after('inscripcion_id')
                ->constrained('curso_evaluaciones')
                ->cascadeOnDelete();
        });

        $this->migrarCalificacionesExistentes();

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropUnique('calificaciones_inscripcion_id_nombre_unique');
        });

        DB::statement('ALTER TABLE calificaciones DROP CONSTRAINT IF EXISTS calificaciones_ponderacion_valida');

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'ponderacion']);
        });

        DB::statement('ALTER TABLE calificaciones ALTER COLUMN curso_evaluacion_id SET NOT NULL');

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->unique(['inscripcion_id', 'curso_evaluacion_id']);
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->string('nombre', 100)->nullable();
            $table->unsignedTinyInteger('ponderacion')->nullable();
        });

        DB::statement('UPDATE calificaciones SET nombre = curso_evaluaciones.nombre, ponderacion = curso_evaluaciones.ponderacion FROM curso_evaluaciones WHERE curso_evaluaciones.id = calificaciones.curso_evaluacion_id');

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropUnique(['inscripcion_id', 'curso_evaluacion_id']);
            $table->dropConstrainedForeignId('curso_evaluacion_id');
        });

        DB::statement('ALTER TABLE calificaciones ALTER COLUMN nombre SET NOT NULL');
        DB::statement('ALTER TABLE calificaciones ALTER COLUMN ponderacion SET NOT NULL');

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->unique(['inscripcion_id', 'nombre']);
        });

        DB::statement('ALTER TABLE calificaciones ADD CONSTRAINT calificaciones_ponderacion_valida CHECK (ponderacion >= 0 AND ponderacion <= 100)');

        Schema::dropIfExists('curso_evaluaciones');
    }

    /**
     * Crea una actividad por curso y nombre, usando la ponderación más frecuente.
     * Así las calificaciones históricas quedan vinculadas sin perder su nota.
     */
    private function migrarCalificacionesExistentes(): void
    {
        $filas = DB::table('calificaciones')
            ->join('inscripciones', 'inscripciones.id', '=', 'calificaciones.inscripcion_id')
            ->select(
                'calificaciones.id',
                'calificaciones.nombre',
                'calificaciones.ponderacion',
                'inscripciones.curso_id',
            )
            ->get();

        foreach ($filas->groupBy(fn (object $fila) => "{$fila->curso_id}|{$fila->nombre}") as $grupo) {
            $primera = $grupo->first();
            $ponderacion = $grupo
                ->groupBy('ponderacion')
                ->sortByDesc(fn ($ponderaciones) => $ponderaciones->count())
                ->keys()
                ->first();

            $evaluacionId = DB::table('curso_evaluaciones')->insertGetId([
                'curso_id' => $primera->curso_id,
                'nombre' => $primera->nombre,
                'ponderacion' => $ponderacion,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('calificaciones')
                ->whereIn('id', $grupo->pluck('id'))
                ->update(['curso_evaluacion_id' => $evaluacionId]);
        }
    }
};
