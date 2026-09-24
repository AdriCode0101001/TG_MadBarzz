<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grupo 3 legal, parte 2: consentimiento para el registro de la huella
 * dactilar (adultos) y consentimiento del tutor (menores) — la protección
 * real la aplica BiometriaController::enrolar(), este es solo el dato.
 *
 * Timestamps nullable en vez de booleanos: NULL = sin consentimiento,
 * fecha = consentimiento otorgado ese momento (mismo patrón que
 * `clientes.terminos_aceptados_en`, Grupo 3 parte 1) — sirve como bandera Y
 * como constancia de cuándo, sin necesitar una columna aparte para la fecha.
 * Todo nullable: no afecta a ningún cliente existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->timestamp('consentimiento_huella_en')->nullable()->after('terminos_aceptados_en');
            $table->string('tutor_nombre')->nullable()->after('consentimiento_huella_en');
            $table->string('tutor_ci')->nullable()->after('tutor_nombre');
            $table->timestamp('consentimiento_tutor_en')->nullable()->after('tutor_ci');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['consentimiento_huella_en', 'tutor_nombre', 'tutor_ci', 'consentimiento_tutor_en']);
        });
    }
};
