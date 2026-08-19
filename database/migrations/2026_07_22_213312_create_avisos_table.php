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
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('texto');
            $table->string('imagen')->nullable(); // ruta en el disco 'public'
            // Administrador o Recepcionista que lo creó. Nullable + nullOnDelete
            // (mismo criterio que qr_pagos.id_usuario): se conserva el aviso
            // aunque el usuario que lo mandó se borre después.
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
