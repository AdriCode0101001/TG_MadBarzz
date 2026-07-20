<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejercicios', function (Blueprint $table) {
            $table->id();

            // Id del ejercicio en el dataset de origen (ej. "0001"). Es lo que
            // permite reimportar sin duplicar (updateOrCreate por dataset_id).
            // Null para ejercicios propios cargados a mano en el futuro.
            $table->string('dataset_id', 20)->nullable()->unique();

            $table->string('nombre');
            $table->string('category', 50)->nullable();
            $table->string('target', 50)->nullable();
            $table->string('muscle_group', 50)->nullable();
            $table->json('secondary_muscles')->nullable();
            $table->string('equipment', 50)->nullable();
            $table->text('instrucciones')->nullable();

            // Rutas relativas al disco "public" (storage/app/public/...)
            $table->string('imagen')->nullable();
            $table->string('gif')->nullable();

            // Atribución de derechos del medio (ej. "© Gym visual — https://gymvisual.com/")
            $table->string('atribucion')->nullable();

            // Mapeo al catálogo propio de MadBarzz. Nullable: "Full body" y los
            // que no encajan (abdomen, cardio, cuello, antebrazo) quedan sin
            // grupo hasta que se curen a mano.
            $table->foreignId('id_grupo_muscular')->nullable()
                  ->constrained('grupos_musculares')->nullOnDelete();

            // 'dataset' = importado del catálogo externo; 'propio' = cargado a mano.
            $table->string('origen', 20)->default('propio');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicios');
    }
};
