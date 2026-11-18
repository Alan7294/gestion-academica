<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('codigo', 20);
            $table->string('nombre');
            $table->unsignedSmallInteger('creditos')->default(0);
            $table->unsignedSmallInteger('semestre')->default(1);
            $table->unsignedSmallInteger('cupo')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // RF04: un mismo código de materia no puede repetirse dentro del periodo.
            $table->unique(['periodo_id', 'codigo']);
            $table->index(['carrera_id', 'periodo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
