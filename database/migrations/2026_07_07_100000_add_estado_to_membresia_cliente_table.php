<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            // Las asignaciones NUEVAS nacen pendientes hasta registrar su pago
            $table->string('estado', 20)->default('pendiente_pago')->after('precio_final');
        });

        // Backfill: el histórico existente queda ACTIVO (no romper accesos actuales)
        DB::table('membresia_cliente')->update(['estado' => 'activa']);
    }

    public function down(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
