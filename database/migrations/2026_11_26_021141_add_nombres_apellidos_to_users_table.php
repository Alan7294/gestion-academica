<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los campos quedan opcionales en la base para no alterar el historial de nombres ya registrados.
     * El formulario exige nombres y primer apellido desde ahora.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nombres', 80)->nullable()->after('name');
            $table->string('primer_apellido', 80)->nullable()->after('nombres');
            $table->string('segundo_apellido', 80)->nullable()->after('primer_apellido');
        });
    }

    /**
     * La reversión descarta los nombres separados; el nombre completo ya registrado se conserva.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['nombres', 'primer_apellido', 'segundo_apellido']);
        });
    }
};
