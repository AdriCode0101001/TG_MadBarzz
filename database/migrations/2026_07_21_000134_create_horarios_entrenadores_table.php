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
        Schema::create('horarios_entrenadores', function (Blueprint $table) {
            $table->id();

            // ISO: 1=lunes ... 6=sábado. El domingo (7) nunca se usa acá —
            // el gimnasio cierra ese día (ver HorarioEntrenadorController).
            $table->unsignedTinyInteger('dia_semana');

            // Franja de 1 hora, de 07:00 a 21:00 (14 franjas/día). Se guardan
            // como time (no datetime): esto es un horario BASE semanal, no
            // ligado a una fecha calendario concreta.
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // Entrenador que cubre la franja. Nullable: una franja puede
            // quedar sin asignar. FK manual (no foreignId) porque la PK de
            // "entrenadores" es "id_entrenador", no "id".
            $table->unsignedBigInteger('id_entrenador')->nullable();
            $table->foreign('id_entrenador')->references('id_entrenador')->on('entrenadores')->nullOnDelete();

            // Multi-sede: mismo patrón que calendario_musculos y el resto de
            // tablas operativas (default a la Sede Principal).
            $table->unsignedBigInteger('id_sede')->default(1);
            $table->foreign('id_sede')->references('id_sede')->on('sedes')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Una sola fila (viva) por día + franja + sede.
            $table->unique(['dia_semana', 'hora_inicio', 'id_sede']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_entrenadores');
    }
};
