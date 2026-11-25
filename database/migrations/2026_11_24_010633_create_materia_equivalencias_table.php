<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las equivalencias son dirigidas: la materia destino reconoce a la materia origen.
     */
    public function up(): void
    {
        Schema::create('materia_equivalencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('equivalente_id')->constrained('materias')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['materia_id', 'equivalente_id']);
            $table->index('equivalente_id');
        });

        DB::statement('ALTER TABLE materia_equivalencias ADD CONSTRAINT materia_equivalencias_no_autorreferencia CHECK (materia_id <> equivalente_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_equivalencias');
    }
};
