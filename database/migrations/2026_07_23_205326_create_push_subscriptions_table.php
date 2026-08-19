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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            // Un cliente puede tener VARIAS suscripciones (celular, tablet,
            // reinstaló el navegador...) — por eso no es unique por sí solo.
            $table->unsignedBigInteger('id_cliente');
            // string(500), no text: los endpoints reales (FCM, Mozilla
            // autopush) rondan 150-200 caracteres, pero un TEXT no admite
            // índice único en MySQL sin especificar largo de prefijo.
            $table->string('endpoint', 500)->unique();
            $table->string('p256dh');
            $table->string('auth');
            $table->timestamps();

            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
