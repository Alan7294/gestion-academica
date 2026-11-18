<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('estado', 20)->default('inscrito');
            $table->timestamp('inscrito_en')->useCurrent();
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->timestamps();

            // RF05: un estudiante no puede inscribirse dos veces al mismo curso.
            $table->unique(['curso_id', 'user_id']);
            $table->index(['user_id', 'estado']);
        });

        DB::statement("ALTER TABLE inscripciones ADD CONSTRAINT inscripciones_estado_valido CHECK (estado IN ('inscrito', 'retirado'))");
        DB::statement('ALTER TABLE inscripciones ADD CONSTRAINT inscripciones_nota_valida CHECK (nota_final IS NULL OR (nota_final >= 0 AND nota_final <= 100))');
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
