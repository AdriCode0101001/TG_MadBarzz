<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grupo 3 legal, parte 1: constancia de que el cliente aceptó los Términos
 * y la Política de Privacidad al auto-registrarse desde la web (checkbox
 * obligatorio, ver RegisteredUserController::store()). Nullable porque los
 * clientes dados de alta por el personal (ClienteController, presencial)
 * no pasan por este checkbox — ahí la aceptación, si corresponde, es en
 * papel/verbal con el personal, fuera del sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->timestamp('terminos_aceptados_en')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('terminos_aceptados_en');
        });
    }
};
