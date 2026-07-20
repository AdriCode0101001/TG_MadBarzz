<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Full body" es un caso especial en "Armar rutina": ese día el catálogo no
 * tiene ejercicios propios (el dataset no trae esa categoría), así que el
 * entrenador debe poder elegir de TODOS los grupos. Un flag explícito es más
 * sólido que comparar el nombre "Full body" (mayúsculas, tildes, o si el
 * nombre cambia) en cada lugar del código que necesite esta regla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos_musculares', function (Blueprint $table) {
            $table->boolean('es_full_body')->default(false)->after('descripcion');
        });

        DB::table('grupos_musculares')
            ->whereRaw('LOWER(nombre) = ?', ['full body'])
            ->update(['es_full_body' => true]);
    }

    public function down(): void
    {
        Schema::table('grupos_musculares', function (Blueprint $table) {
            $table->dropColumn('es_full_body');
        });
    }
};
