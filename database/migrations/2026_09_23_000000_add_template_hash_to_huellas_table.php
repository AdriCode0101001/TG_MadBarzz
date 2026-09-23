<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grupo 2 / Etapa 1 de la auditoría de privacidad: al cifrar `huellas.template`
 * (cast 'encrypted'), el chequeo anti-duplicado de BiometriaController::enrolar
 * ya no puede comparar la columna cifrada directamente (IV aleatorio en cada
 * cifrado). `template_hash` guarda un SHA-256 determinístico del template EN
 * CLARO, calculado solo al guardar (ver App\Models\Huella::booted()), para
 * poder seguir detectando "esta misma huella ya está en otro cliente" con una
 * búsqueda indexada — mismo comportamiento que antes, sin exponer el dato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('huellas', function (Blueprint $table) {
            $table->string('template_hash', 64)->nullable()->after('template');
            $table->index('template_hash');
        });
    }

    public function down(): void
    {
        Schema::table('huellas', function (Blueprint $table) {
            $table->dropIndex(['template_hash']);
            $table->dropColumn('template_hash');
        });
    }
};
