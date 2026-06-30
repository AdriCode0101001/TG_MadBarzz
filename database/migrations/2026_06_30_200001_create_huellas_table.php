<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('huellas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->string('dedo', 50);           // ej. 'indice_derecho', 'pulgar_izquierdo'
            $table->longText('template');          // plantilla biométrica en base64
            $table->string('version_formato', 50)->nullable(); // versión del SDK ZKTeco
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_cliente')
                  ->references('id_cliente')
                  ->on('clientes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huellas');
    }
};
