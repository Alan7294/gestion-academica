<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La pertenencia de una materia ya se deriva de materia_plan_estudio.
     * El primer plan que la incluyó conserva la carrera al revertir este cambio.
     */
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropForeign(['carrera_id']);
            $table->dropIndex(['carrera_id']);
            $table->dropColumn('carrera_id');
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->foreignId('carrera_id')
                ->nullable()
                ->after('id')
                ->constrained('carreras')
                ->cascadeOnDelete();
        });

        DB::transaction(function () {
            $carreras = DB::table('materia_plan_estudio')
                ->join('planes_estudio', 'planes_estudio.id', '=', 'materia_plan_estudio.plan_estudio_id')
                ->orderBy('materia_plan_estudio.id')
                ->get(['materia_plan_estudio.materia_id', 'planes_estudio.carrera_id'])
                ->unique('materia_id');

            foreach ($carreras as $vinculo) {
                DB::table('materias')->where('id', $vinculo->materia_id)
                    ->update(['carrera_id' => $vinculo->carrera_id]);
            }
        });

        Schema::table('materias', function (Blueprint $table) {
            $table->index('carrera_id');
        });
    }
};
