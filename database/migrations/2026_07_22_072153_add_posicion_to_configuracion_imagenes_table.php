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
        Schema::table('configuracion_imagenes', function (Blueprint $table) {
            // Reencuadre del recorte (arriba/centro/abajo) para las claves
            // cuya imagen SÍ tiene un object-fit:cover/background-size:cover
            // real en la home (ver ImagenHomeService::POSICION_POR_DEFECTO).
            // Null = usar el default de esa clave.
            $table->string('posicion', 10)->nullable()->after('ruta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_imagenes', function (Blueprint $table) {
            $table->dropColumn('posicion');
        });
    }
};
