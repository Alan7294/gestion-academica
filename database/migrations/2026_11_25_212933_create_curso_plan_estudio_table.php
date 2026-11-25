<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los planes autorizados comparten un mismo curso y, por tanto, su cupo.
     * El plan del curso se conserva como anfitrión para los reportes administrativos.
     */
    public function up(): void
    {
        Schema::create('curso_plan_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_estudio_id')->constrained('planes_estudio')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['curso_id', 'plan_estudio_id']);
        });

        DB::table('cursos')
            ->whereNotNull('plan_estudio_id')
            ->orderBy('id')
            ->eachById(fn (object $curso) => DB::table('curso_plan_estudio')->insert([
                'curso_id' => $curso->id,
                'plan_estudio_id' => $curso->plan_estudio_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_plan_estudio');
    }
};
