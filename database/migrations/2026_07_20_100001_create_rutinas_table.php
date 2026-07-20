<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rutina estructurada (Etapa 3 del módulo de rutinas): el entrenador arma
 * su rutina para UN día del calendario (calendario_musculos), eligiendo
 * ejercicios del grupo muscular que toca ese día.
 *
 * id_usuario (no id_entrenador de la tabla `entrenadores`): tanto el
 * Entrenador como el Administrador pueden armar SU propia rutina, y un
 * Administrador no tiene fila en `entrenadores`. `users.id` es el único
 * identificador que ambos roles siempre tienen, y User::nombreCompleto()
 * ya sabe resolver el nombre real buscando en administradores/entrenadores/
 * recepcionistas/clientes — por eso el "dueño" de la rutina se guarda así.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutinas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');

            // Músculo del día, copiado de calendario_musculos al crear la
            // rutina (si el admin cambia el calendario después, la rutina
            // ya armada conserva el músculo con el que se creó).
            $table->foreignId('id_grupo_muscular')
                  ->constrained('grupos_musculares')
                  ->restrictOnDelete();

            $table->foreignId('id_usuario')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Multi-sede: mismo patrón que calendario_musculos.
            $table->unsignedBigInteger('id_sede')->default(1);
            $table->foreign('id_sede')->references('id_sede')->on('sedes')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Una sola rutina (viva) por entrenador/admin y fecha.
            $table->unique(['id_usuario', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutinas');
    }
};
