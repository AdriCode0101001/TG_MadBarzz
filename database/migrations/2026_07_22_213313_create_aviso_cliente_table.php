<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aviso_cliente', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_aviso');
            $table->unsignedBigInteger('id_cliente');
            // Existe la fila = el cliente lo leyó (created_at = cuándo). Se
            // crea SOLO al leer (firstOrCreate), nunca en fan-out al enviar
            // el aviso — así enviar a "todos" es una sola fila en "avisos".
            $table->timestamps();

            $table->foreign('id_aviso')->references('id')->on('avisos')->cascadeOnDelete();
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->cascadeOnDelete();

            $table->unique(['id_aviso', 'id_cliente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviso_cliente');
    }
};
