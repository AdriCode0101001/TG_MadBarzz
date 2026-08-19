<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracion_imagenes', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // ej. "home_hero_1", "home_cta"...
            $table->string('ruta')->nullable(); // path en el disco 'public'; null = usar el placeholder
            $table->unsignedBigInteger('id_usuario')->nullable(); // Administrador que la subió
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_imagenes');
    }
};
