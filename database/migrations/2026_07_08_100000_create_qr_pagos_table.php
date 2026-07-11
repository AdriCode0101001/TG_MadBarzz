<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('imagen'); // ruta en el disco privado (nunca pública)
            $table->date('fecha_vencimiento');
            $table->unsignedBigInteger('id_usuario')->nullable(); // Administrador que lo subió
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_pagos');
    }
};
