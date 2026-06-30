<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Tokens de autenticación para los agentes C# (uno por sede).
     * El token se almacena HASHEADO (SHA-256). El texto plano solo se muestra
     * una vez al generarlo con `php artisan agente:token`.
     */
    public function up(): void
    {
        Schema::create('agent_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sede');
            $table->string('nombre');              // etiqueta descriptiva, ej. "Agente Sede Central"
            $table->string('token', 64)->unique(); // SHA-256 del token plano (64 chars hex)
            $table->timestamp('ultimo_uso')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('id_sede')
                  ->references('id_sede')
                  ->on('sedes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tokens');
    }
};
