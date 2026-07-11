<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            // Nullable: las asignaciones existentes no se modifican.
            $table->unsignedBigInteger('id_descuento')->nullable()->after('descuento');
            $table->foreign('id_descuento')->references('id')->on('descuentos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            $table->dropForeign(['id_descuento']);
            $table->dropColumn('id_descuento');
        });
    }
};
