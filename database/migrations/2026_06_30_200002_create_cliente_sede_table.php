<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Tabla pivote para la política de acceso por sede.
     *
     * Semántica:
     *   - Si un cliente NO tiene filas aquí → acceso LIBRE a todas las sedes.
     *   - Si tiene una o más filas → acceso RESTRINGIDO solo a las sedes listadas.
     *
     * Esto permite escalar la política sin cambiar código: basta con insertar
     * o borrar filas en esta tabla.
     */
    public function up(): void
    {
        Schema::create('cliente_sede', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_sede');
            $table->timestamps();

            $table->foreign('id_cliente')
                  ->references('id_cliente')
                  ->on('clientes')
                  ->onDelete('cascade');

            $table->foreign('id_sede')
                  ->references('id_sede')
                  ->on('sedes')
                  ->onDelete('cascade');

            $table->unique(['id_cliente', 'id_sede']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_sede');
    }
};
