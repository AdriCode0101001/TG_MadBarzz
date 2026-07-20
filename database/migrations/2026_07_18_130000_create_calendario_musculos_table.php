<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_musculos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('id_grupo_muscular')
                  ->constrained('grupos_musculares')
                  ->restrictOnDelete();

            // Multi-sede: igual patrón que el resto de tablas operativas
            // (clientes, asistencias, pagos, ...): default a la Sede Principal.
            $table->unsignedBigInteger('id_sede')->default(1);
            $table->foreign('id_sede')->references('id_sede')->on('sedes')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Una sola asignación (viva) por fecha y sede.
            $table->unique(['fecha', 'id_sede']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_musculos');
    }
};
