<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF04: materias que abarca un plan de estudios, ubicadas por semestre.
     */
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_estudio_id')->constrained('planes_estudio')->cascadeOnDelete();
            $table->string('codigo', 20);
            $table->string('nombre');
            $table->unsignedSmallInteger('semestre')->default(1);
            $table->unsignedSmallInteger('creditos')->default(0);
            $table->timestamps();

            // Un código de materia no puede repetirse dentro del mismo plan.
            $table->unique(['plan_estudio_id', 'codigo']);
            $table->index(['plan_estudio_id', 'semestre']);
        });

        DB::statement('ALTER TABLE materias ADD CONSTRAINT materias_semestre_positivo CHECK (semestre >= 1)');
        DB::statement('ALTER TABLE materias ADD CONSTRAINT materias_creditos_no_negativos CHECK (creditos >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
