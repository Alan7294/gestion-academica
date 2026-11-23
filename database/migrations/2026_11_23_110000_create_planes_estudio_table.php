<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF04: plan de estudios de una carrera con su modalidad y duración.
     */
    public function up(): void
    {
        Schema::create('planes_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete();
            $table->string('nombre');
            $table->string('modalidad', 20);
            $table->unsignedSmallInteger('semestres')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Un mismo nombre de plan no puede repetirse dentro de la carrera.
            $table->unique(['carrera_id', 'nombre']);
        });

        DB::statement("ALTER TABLE planes_estudio ADD CONSTRAINT planes_estudio_modalidad_valida CHECK (modalidad IN ('modular', 'semestral'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_estudio');
    }
};
