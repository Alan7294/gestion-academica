<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte el texto libre de carreras.facultad en un catálogo compartido.
     */
    public function up(): void
    {
        Schema::create('facultades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $nombres = DB::table('carreras')
            ->whereNotNull('facultad')
            ->pluck('facultad')
            ->map(fn (string $nombre) => trim($nombre))
            ->filter()
            ->unique();

        foreach ($nombres as $nombre) {
            DB::table('facultades')->insertOrIgnore([
                'nombre' => $nombre,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('carreras', function (Blueprint $table) {
            $table->foreignId('facultad_id')
                ->nullable()
                ->after('activo')
                ->constrained('facultades')
                ->nullOnDelete();
        });

        DB::table('carreras')
            ->whereNotNull('facultad')
            ->orderBy('id')
            ->chunkById(100, function ($carreras) {
                foreach ($carreras as $carrera) {
                    $nombre = trim((string) $carrera->facultad);

                    if ($nombre === '') {
                        continue;
                    }

                    $facultadId = DB::table('facultades')->where('nombre', $nombre)->value('id');

                    DB::table('carreras')->where('id', $carrera->id)->update(['facultad_id' => $facultadId]);
                }
            });

        Schema::table('carreras', function (Blueprint $table) {
            $table->dropColumn('facultad');
        });
    }

    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->string('facultad')->nullable()->after('activo');
        });

        DB::table('carreras')
            ->whereNotNull('facultad_id')
            ->orderBy('id')
            ->chunkById(100, function ($carreras) {
                foreach ($carreras as $carrera) {
                    $nombre = DB::table('facultades')->where('id', $carrera->facultad_id)->value('nombre');

                    DB::table('carreras')->where('id', $carrera->id)->update(['facultad' => $nombre]);
                }
            });

        Schema::table('carreras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facultad_id');
        });

        Schema::dropIfExists('facultades');
    }
};
