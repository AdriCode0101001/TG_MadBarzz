<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloqueo progresivo de login (por email/usuario + IP). Esta tabla NO es el
 * mecanismo que efectivamente bloquea el login — eso lo sigue haciendo
 * Illuminate\Support\Facades\RateLimiter (ver LoginLockoutService), igual
 * que ya lo hacía LoginRequest. Esta tabla es solo la "memoria" que
 * RateLimiter no tiene: cuántos intentos lleva y en qué escalón de la
 * progresión está cada combinación email+IP, para poder calcular el
 * próximo tiempo de bloqueo (3 -> 6 -> 12 -> 15 min tope) y para que el
 * Admin pueda ver/desbloquear manualmente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_lockouts', function (Blueprint $table) {
            $table->id();
            $table->string('login'); // lo que el usuario escribió: email o username
            $table->string('ip', 45); // IPv4 o IPv6
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('lockout_level')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->unique(['login', 'ip']);
            $table->index('locked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_lockouts');
    }
};
