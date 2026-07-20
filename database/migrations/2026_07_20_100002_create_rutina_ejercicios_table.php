<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutina_ejercicios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_rutina')
                  ->constrained('rutinas')
                  ->cascadeOnDelete();

            $table->foreignId('id_ejercicio')
                  ->constrained('ejercicios')
                  ->restrictOnDelete();

            $table->unsignedInteger('series');
            // String (no int): admite "8-12" además de un número simple.
            $table->string('repeticiones', 20);
            $table->unsignedInteger('orden')->default(0);
            $table->string('nota', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutina_ejercicios');
    }
};
