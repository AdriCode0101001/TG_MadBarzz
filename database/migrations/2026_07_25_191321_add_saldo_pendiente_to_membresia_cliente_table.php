<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etapa 1 de Pago en 2 Cuotas: solo el terreno de datos, sin cambiar ningún
 * comportamiento. Ambas columnas nacen NULL para toda fila existente y
 * nueva — null/0 sigue significando "pagada completa", exactamente como hoy
 * (nada las lee ni las escribe todavía).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            $table->decimal('saldo_pendiente', 8, 2)->nullable()->after('estado');
            $table->date('fecha_limite_saldo')->nullable()->after('saldo_pendiente');
        });
    }

    public function down(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            $table->dropColumn(['saldo_pendiente', 'fecha_limite_saldo']);
        });
    }
};
