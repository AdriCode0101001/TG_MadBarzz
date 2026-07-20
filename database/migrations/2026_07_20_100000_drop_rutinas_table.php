<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retira el feature viejo de "rutinas" (subir un PDF/imagen suelta con
 * título y descripción, sin fecha ni grupo muscular). Tenía 0 filas en
 * producción/desarrollo — no hay datos reales que perder. Lo reemplaza la
 * rutina estructurada (fecha + grupo muscular + ejercicios con series y
 * repeticiones) de create_rutinas_table de esta misma tanda de migraciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('rutinas');
    }

    public function down(): void
    {
        // Recrea el esquema viejo (sin datos) por si se revierte la migración.
        Schema::create('rutinas', function (Blueprint $table) {
            $table->id('id_rutina');
            $table->unsignedBigInteger('id_entrenador');
            $table->string('titulo', 255);
            $table->text('descripcion')->nullable();
            $table->string('archivo')->nullable();
            $table->timestamp('fecha_subida')->nullable();
            $table->timestamps();

            $table->foreign('id_entrenador')
                  ->references('id_entrenador')
                  ->on('entrenadores')
                  ->onDelete('cascade');
        });
    }
};
