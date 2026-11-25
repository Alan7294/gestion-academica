<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte las materias en un catálogo por carrera y deja el semestre y
     * los créditos en la relación con el plan de estudios.
     */
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->foreignId('carrera_id')
                ->nullable()
                ->after('id')
                ->constrained('carreras')
                ->cascadeOnDelete();
        });

        Schema::create('materia_plan_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_estudio_id')->constrained('planes_estudio')->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->unsignedSmallInteger('semestre')->default(1);
            $table->unsignedSmallInteger('creditos')->default(0);
            $table->timestamps();

            $table->unique(['plan_estudio_id', 'materia_id']);
            $table->index(['materia_id', 'semestre']);
        });

        DB::statement('ALTER TABLE materia_plan_estudio ADD CONSTRAINT materia_plan_estudio_semestre_positivo CHECK (semestre >= 1)');
        DB::statement('ALTER TABLE materia_plan_estudio ADD CONSTRAINT materia_plan_estudio_creditos_no_negativos CHECK (creditos >= 0)');

        DB::statement(<<<'SQL'
            UPDATE materias m
            SET carrera_id = p.carrera_id
            FROM planes_estudio p
            WHERE p.id = m.plan_estudio_id
            SQL);

        $grupos = DB::table('materias')
            ->join('planes_estudio', 'planes_estudio.id', '=', 'materias.plan_estudio_id')
            ->select([
                'materias.id',
                'materias.carrera_id',
                'materias.codigo',
                'planes_estudio.activo',
                'planes_estudio.id as plan_id',
            ])
            ->orderByDesc('planes_estudio.activo')
            ->orderByDesc('planes_estudio.id')
            ->orderByDesc('materias.id')
            ->get()
            ->groupBy(fn ($fila) => $fila->carrera_id.'|'.$fila->codigo);

        $mapa = [];

        foreach ($grupos as $filas) {
            $canonico = (int) $filas->first()->id;

            foreach ($filas as $fila) {
                $mapa[(int) $fila->id] = $canonico;
            }
        }

        $materias = DB::table('materias')
            ->select(['id', 'plan_estudio_id', 'semestre', 'creditos'])
            ->get();

        $filasPivote = $materias
            ->map(fn ($materia) => [
                'plan_estudio_id' => $materia->plan_estudio_id,
                'materia_id' => $mapa[(int) $materia->id],
                'semestre' => $materia->semestre,
                'creditos' => $materia->creditos,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->unique(fn ($fila) => $fila['plan_estudio_id'].'|'.$fila['materia_id'])
            ->values();

        foreach ($filasPivote->chunk(500) as $lote) {
            DB::table('materia_plan_estudio')->insertOrIgnore($lote->all());
        }

        foreach ($mapa as $anterior => $canonico) {
            if ($anterior !== $canonico) {
                DB::table('cursos')->where('materia_id', $anterior)->update(['materia_id' => $canonico]);
            }
        }

        $requisitos = DB::table('materia_requisitos')
            ->select(['materia_id', 'requisito_id'])
            ->get()
            ->map(fn ($requisito) => [
                'materia_id' => $mapa[(int) $requisito->materia_id],
                'requisito_id' => $mapa[(int) $requisito->requisito_id],
            ])
            ->filter(fn ($requisito) => $requisito['materia_id'] !== $requisito['requisito_id'])
            ->unique(fn ($requisito) => $requisito['materia_id'].'|'.$requisito['requisito_id'])
            ->values()
            ->map(fn ($requisito) => $requisito + ['created_at' => now(), 'updated_at' => now()]);

        DB::table('materia_requisitos')->delete();

        foreach ($requisitos->chunk(500) as $lote) {
            DB::table('materia_requisitos')->insertOrIgnore($lote->all());
        }

        DB::table('materias')->whereNotIn('id', array_values($mapa))->delete();

        DB::statement('ALTER TABLE materias DROP CONSTRAINT IF EXISTS materias_semestre_positivo');
        DB::statement('ALTER TABLE materias DROP CONSTRAINT IF EXISTS materias_creditos_no_negativos');

        Schema::table('materias', function (Blueprint $table) {
            $table->dropForeign(['plan_estudio_id']);
            $table->dropUnique(['plan_estudio_id', 'codigo']);
            $table->dropIndex(['plan_estudio_id', 'semestre']);
            $table->dropColumn(['plan_estudio_id', 'semestre', 'creditos']);
        });

        Schema::table('materias', function (Blueprint $table) {
            $table->foreignId('carrera_id')->nullable(false)->change();
            $table->unique(['carrera_id', 'codigo']);
            $table->index('carrera_id');
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->foreignId('plan_estudio_id')->nullable()->after('id');
            $table->unsignedSmallInteger('semestre')->default(1);
            $table->unsignedSmallInteger('creditos')->default(0);
        });

        $planPorCarrera = DB::table('planes_estudio')
            ->orderByDesc('activo')
            ->orderByDesc('id')
            ->get()
            ->groupBy('carrera_id')
            ->map(fn ($planes) => (int) $planes->first()->id);

        $planPorDefecto = (int) DB::table('planes_estudio')->orderByDesc('activo')->orderByDesc('id')->value('id');

        DB::table('materia_plan_estudio')
            ->orderBy('plan_estudio_id')
            ->get()
            ->groupBy('materia_id')
            ->each(function ($filas, int $materiaId) {
                $fila = $filas->first();

                DB::table('materias')->where('id', $materiaId)->update([
                    'plan_estudio_id' => $fila->plan_estudio_id,
                    'semestre' => $fila->semestre,
                    'creditos' => $fila->creditos,
                ]);
            });

        $materias = DB::table('materias')->select(['id', 'carrera_id'])->get();

        foreach ($materias as $materia) {
            if (DB::table('materias')->where('id', $materia->id)->value('plan_estudio_id') !== null) {
                continue;
            }

            DB::table('materias')->where('id', $materia->id)->update([
                'plan_estudio_id' => $planPorCarrera->get($materia->carrera_id, $planPorDefecto),
            ]);
        }

        Schema::table('materias', function (Blueprint $table) {
            $table->foreign('plan_estudio_id')->references('id')->on('planes_estudio')->cascadeOnDelete();
            $table->unique(['plan_estudio_id', 'codigo']);
            $table->index(['plan_estudio_id', 'semestre']);
        });

        DB::statement('ALTER TABLE materias ADD CONSTRAINT materias_semestre_positivo CHECK (semestre >= 1)');
        DB::statement('ALTER TABLE materias ADD CONSTRAINT materias_creditos_no_negativos CHECK (creditos >= 0)');

        Schema::table('materias', function (Blueprint $table) {
            $table->dropForeign(['carrera_id']);
            $table->dropUnique(['carrera_id', 'codigo']);
            $table->dropIndex(['carrera_id']);
            $table->dropColumn('carrera_id');
        });

        Schema::dropIfExists('materia_plan_estudio');
    }
};
