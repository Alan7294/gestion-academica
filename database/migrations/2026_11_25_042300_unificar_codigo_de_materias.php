<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El código pasa a identificar la materia en todo el catálogo: una misma
     * materia puede pertenecer a los planes de varias carreras. carrera_id se
     * conserva temporalmente como dato heredado hasta que se revise el resto
     * de las reglas académicas.
     */
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropUnique(['carrera_id', 'codigo']);
            $table->foreignId('carrera_id')->nullable()->change();
        });

        DB::transaction(fn () => $this->unificarCodigosRepetidos());

        Schema::table('materias', function (Blueprint $table) {
            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
        });

        DB::transaction(function () {
            $sinCarrera = DB::table('materias')->whereNull('carrera_id')->pluck('id');

            foreach ($sinCarrera as $materiaId) {
                $carreraId = DB::table('materia_plan_estudio')
                    ->join('planes_estudio', 'planes_estudio.id', '=', 'materia_plan_estudio.plan_estudio_id')
                    ->where('materia_plan_estudio.materia_id', $materiaId)
                    ->orderByDesc('planes_estudio.activo')
                    ->orderBy('planes_estudio.id')
                    ->value('planes_estudio.carrera_id')
                    ?? DB::table('carreras')->orderBy('id')->value('id');

                DB::table('materias')->where('id', $materiaId)->update(['carrera_id' => $carreraId]);
            }
        });

        Schema::table('materias', function (Blueprint $table) {
            $table->foreignId('carrera_id')->nullable(false)->change();
            $table->unique(['carrera_id', 'codigo']);
        });
    }

    /**
     * Conserva la materia de menor id y traslada a ella las referencias de sus duplicados.
     */
    private function unificarCodigosRepetidos(): void
    {
        $codigosRepetidos = DB::table('materias')
            ->select('codigo')
            ->groupBy('codigo')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('codigo');

        foreach ($codigosRepetidos as $codigo) {
            $duplicados = DB::table('materias')
                ->where('codigo', $codigo)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $canonico = (int) array_shift($duplicados);

            $this->trasladarCursos($duplicados, $canonico);
            $this->trasladarPivotes($duplicados, $canonico);

            DB::table('materias')->whereIn('id', $duplicados)->delete();
        }
    }

    /**
     * @param  array<int, int>  $duplicados
     */
    private function trasladarCursos(array $duplicados, int $canonico): void
    {
        DB::table('cursos')->whereIn('materia_id', $duplicados)->update(['materia_id' => $canonico]);
    }

    /**
     * @param  array<int, int>  $duplicados
     */
    private function trasladarPivotes(array $duplicados, int $canonico): void
    {
        $planes = DB::table('materia_plan_estudio')
            ->whereIn('materia_id', $duplicados)
            ->get(['plan_estudio_id', 'materia_id', 'semestre', 'creditos', 'created_at', 'updated_at']);

        foreach ($planes as $fila) {
            DB::table('materia_plan_estudio')->insertOrIgnore([
                'plan_estudio_id' => $fila->plan_estudio_id,
                'materia_id' => $canonico,
                'semestre' => $fila->semestre,
                'creditos' => $fila->creditos,
                'created_at' => $fila->created_at,
                'updated_at' => $fila->updated_at,
            ]);
        }

        DB::table('materia_plan_estudio')->whereIn('materia_id', $duplicados)->delete();

        $requisitos = DB::table('materia_requisitos')
            ->whereIn('materia_id', $duplicados)
            ->orWhereIn('requisito_id', $duplicados)
            ->get(['plan_estudio_id', 'materia_id', 'requisito_id', 'created_at', 'updated_at']);

        foreach ($requisitos as $fila) {
            $materiaId = in_array($fila->materia_id, $duplicados, true) ? $canonico : $fila->materia_id;
            $requisitoId = in_array($fila->requisito_id, $duplicados, true) ? $canonico : $fila->requisito_id;

            if ($materiaId === $requisitoId) {
                continue;
            }

            DB::table('materia_requisitos')->insertOrIgnore([
                'plan_estudio_id' => $fila->plan_estudio_id,
                'materia_id' => $materiaId,
                'requisito_id' => $requisitoId,
                'created_at' => $fila->created_at,
                'updated_at' => $fila->updated_at,
            ]);
        }

        DB::table('materia_requisitos')
            ->whereIn('materia_id', $duplicados)
            ->orWhereIn('requisito_id', $duplicados)
            ->delete();

        $equivalencias = DB::table('materia_equivalencias')
            ->whereIn('materia_id', $duplicados)
            ->orWhereIn('equivalente_id', $duplicados)
            ->get(['materia_id', 'equivalente_id', 'created_at', 'updated_at']);

        foreach ($equivalencias as $fila) {
            $materiaId = in_array($fila->materia_id, $duplicados, true) ? $canonico : $fila->materia_id;
            $equivalenteId = in_array($fila->equivalente_id, $duplicados, true) ? $canonico : $fila->equivalente_id;

            if ($materiaId === $equivalenteId) {
                continue;
            }

            DB::table('materia_equivalencias')->insertOrIgnore([
                'materia_id' => $materiaId,
                'equivalente_id' => $equivalenteId,
                'created_at' => $fila->created_at,
                'updated_at' => $fila->updated_at,
            ]);
        }

        DB::table('materia_equivalencias')
            ->whereIn('materia_id', $duplicados)
            ->orWhereIn('equivalente_id', $duplicados)
            ->delete();
    }
};
