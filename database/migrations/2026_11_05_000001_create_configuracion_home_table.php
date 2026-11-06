<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_home', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // ver ContenidoHomeService::DEFAULTS
            $table->text('valor')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable(); // Administrador que lo editó
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_home');
    }
};
