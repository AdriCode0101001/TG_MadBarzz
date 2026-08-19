<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pago en 2 Cuotas — Etapa 5: notificaciones.tipo era un ENUM cerrado
 * ('recordatorio_vencimiento', 'bienvenida', 'promocion') que no admitía el
 * nuevo tipo 'recordatorio_saldo_cuota' (en SQLite esto es un CHECK
 * constraint que directamente rechaza el insert). Se amplía a string(50),
 * sin restricción cerrada — mismo criterio que ya se usó para
 * membresia_cliente.estado: más flexible para sumar tipos futuros sin
 * volver a tocar el esquema cada vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->string('tipo', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->enum('tipo', ['recordatorio_vencimiento', 'bienvenida', 'promocion'])->change();
        });
    }
};
