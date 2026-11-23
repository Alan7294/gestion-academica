<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF04: requisitos de aprobación entre materias de un mismo plan.
     */
    public function up(): void
    {
        Schema::create('materia_requisitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('requisito_id')->constrained('materias')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['materia_id', 'requisito_id']);
            $table->index('requisito_id');
        });

        DB::statement('ALTER TABLE materia_requisitos ADD CONSTRAINT materia_requisitos_no_autorreferencia CHECK (materia_id <> requisito_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_requisitos');
    }
};
