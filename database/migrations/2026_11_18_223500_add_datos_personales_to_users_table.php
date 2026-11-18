<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos personales que exige RF03 (gestión de perfiles).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ci', 20)->nullable()->unique()->after('name');
            $table->string('telefono', 30)->nullable()->after('ci');
            $table->boolean('activo')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ci']);
            $table->dropColumn(['ci', 'telefono', 'activo']);
        });
    }
};
