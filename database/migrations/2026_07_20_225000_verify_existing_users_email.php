<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Etapa 3 del auto-registro (verificación de email): App\Models\User pasa a
 * implementar MustVerifyEmail, así que a partir de ahora un usuario sin
 * `email_verified_at` queda bloqueado por el middleware 'verified' en las
 * rutas del portal del cliente.
 *
 * Todo usuario que existe HOY (personal y clientes por igual) fue dado de
 * alta por el personal — el registro público estuvo bloqueado (Etapa 0)
 * desde antes de que existiera ningún usuario en esta base. Nadie de los que
 * ya existen debe quedar trabado por un requisito que no existía cuando se
 * creó su cuenta: se marcan todos como verificados de una vez. Solo los
 * clientes que se auto-registren DESPUÉS de este cambio empiezan sin
 * verificar.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * No reversible a propósito: no hay forma de saber cuáles de los
     * usuarios verificados por esta migración ya lo estaban antes vs. cuáles
     * se marcaron recién acá.
     */
    public function down(): void
    {
        //
    }
};
