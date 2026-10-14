<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un usuario con rol Administrador puede no tener fila en `administradores`
 * (ej. el admin@example.com del seeder de demo). Crear una promoción no
 * debe depender de esa fila: el autor queda igual en la bitácora (causer =
 * usuario logueado), y la columna pasa a ser opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promociones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_administrador')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No se revierte a NOT NULL: podría haber promociones sin administrador.
    }
};
