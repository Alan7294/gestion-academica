<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->unsignedTinyInteger('ponderacion')->default(0);
            $table->decimal('nota', 5, 2);
            $table->date('fecha');
            $table->timestamps();

            // Un mismo tipo de evaluación no se repite dentro de la inscripción.
            $table->unique(['inscripcion_id', 'nombre']);
            $table->index(['inscripcion_id', 'fecha']);
        });

        DB::statement('ALTER TABLE calificaciones ADD CONSTRAINT calificaciones_nota_valida CHECK (nota >= 0 AND nota <= 100)');
        DB::statement('ALTER TABLE calificaciones ADD CONSTRAINT calificaciones_ponderacion_valida CHECK (ponderacion >= 0 AND ponderacion <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
