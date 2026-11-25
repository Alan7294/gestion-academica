<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF09: registra los certificados de un docente como datos textuales.
     */
    public function up(): void
    {
        Schema::create('certificados_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo', 255);
            $table->string('institucion', 255);
            $table->string('codigo', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados_docentes');
    }
};
