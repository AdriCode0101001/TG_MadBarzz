<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Vincula el pago a la ASIGNACIÓN concreta del cliente
            // (membresia_cliente), no solo al tipo de membresía.
            // Nullable: los pagos históricos no se tocan.
            $table->unsignedBigInteger('id_membresia_cliente')->nullable()->after('id_membresia');
            $table->foreign('id_membresia_cliente')->references('id')->on('membresia_cliente')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['id_membresia_cliente']);
            $table->dropColumn('id_membresia_cliente');
        });
    }
};
