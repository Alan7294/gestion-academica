<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un estudiante puede tener matrículas activas en varias carreras.
     * La baja es lógica para conservar el historial académico.
     */
    public function up(): void
    {
        Schema::create('carrera_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete();
            $table->foreignId('plan_estudio_id')->nullable()->constrained('planes_estudio')->nullOnDelete();
            $table->string('estado', 20)->default('activa');
            $table->boolean('es_principal')->default(false);
            $table->string('modalidad', 20)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'carrera_id']);
            $table->index('carrera_id');
            $table->index('estado');
        });

        DB::statement("ALTER TABLE carrera_user ADD CONSTRAINT carrera_user_estado_valido CHECK (estado IN ('activa', 'baja'))");
        DB::statement("ALTER TABLE carrera_user ADD CONSTRAINT carrera_user_modalidad_valida CHECK (modalidad IS NULL OR modalidad IN ('modular', 'semestral'))");
        DB::statement('CREATE UNIQUE INDEX carrera_user_principal_unico ON carrera_user (user_id) WHERE es_principal = true AND estado = \'activa\'');

        $this->backfill();

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['carrera_id']);
            $table->dropColumn(['carrera_id', 'modalidad']);
        });

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_modalidad_valida');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('carrera_id')->nullable()->after('activo')->constrained('carreras')->nullOnDelete();
            $table->string('modalidad', 20)->nullable()->after('carrera_id');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_modalidad_valida CHECK (modalidad IS NULL OR modalidad IN ('modular', 'semestral'))");

        DB::table('carrera_user')
            ->where('estado', 'activa')
            ->where('es_principal', true)
            ->orderBy('id')
            ->chunkById(100, function ($matriculas) {
                foreach ($matriculas as $matricula) {
                    DB::table('users')
                        ->where('id', $matricula->user_id)
                        ->update([
                            'carrera_id' => $matricula->carrera_id,
                            'modalidad' => $matricula->modalidad,
                        ]);
                }
            });

        DB::statement('DROP INDEX IF EXISTS carrera_user_principal_unico');
        Schema::dropIfExists('carrera_user');
    }

    /**
     * Traslada la carrera única que tenían las cuentas a su primera matrícula.
     */
    private function backfill(): void
    {
        DB::table('users')
            ->whereNotNull('carrera_id')
            ->orderBy('id')
            ->chunkById(100, function ($usuarios) {
                $filas = collect($usuarios)->map(fn ($usuario) => [
                    'user_id' => $usuario->id,
                    'carrera_id' => $usuario->carrera_id,
                    'plan_estudio_id' => null,
                    'estado' => 'activa',
                    'es_principal' => true,
                    'modalidad' => $usuario->modalidad,
                    'fecha_inicio' => $usuario->created_at
                        ? Carbon::parse($usuario->created_at)->toDateString()
                        : now()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                DB::table('carrera_user')->insertOrIgnore($filas);
            });
    }
};
