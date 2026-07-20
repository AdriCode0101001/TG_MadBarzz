<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutina_ejercicios', function (Blueprint $table) {
            // Gif/foto/video que el entrenador sube para SU rutina, propio de
            // esta fila (id_rutina + id_ejercicio): no toca ni pisa el media
            // del catálogo (ejercicios.gif/imagen), ni el de otro entrenador.
            // Nullable: si no sube nada, se muestra el media del catálogo.
            $table->string('media_propio')->nullable()->after('nota');
        });
    }

    public function down(): void
    {
        Schema::table('rutina_ejercicios', function (Blueprint $table) {
            $table->dropColumn('media_propio');
        });
    }
};
