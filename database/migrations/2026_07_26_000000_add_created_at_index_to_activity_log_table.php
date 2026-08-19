<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de Auditoría — Etapa 1: la fecha es el filtro más común en una
 * pantalla de auditoría (por rango de fecha), y activity_log no tenía
 * índice propio ahí — solo los que Spatie ya crea en subject/causer/
 * log_name (nullableMorphs los agrega solo). Confirmado con SHOW INDEX
 * sobre la base real: subject, causer y log_name ya estaban indexados;
 * created_at no.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
