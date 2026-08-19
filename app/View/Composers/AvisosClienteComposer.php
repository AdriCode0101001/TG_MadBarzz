<?php

namespace App\View\Composers;

use App\Models\Aviso;
use App\Models\AvisoCliente;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Datos de la campanita de Avisos para layouts/private.blade.php — SOLO para
 * el rol Cliente. Para cualquier otro rol (o invitado) no corre ninguna
 * consulta de avisos: se corta apenas falla el chequeo de rol, así que a
 * Admin/Recepcionista/Entrenador esto no les cuesta nada.
 */
class AvisosClienteComposer
{
    public function compose(View $view): void
    {
        $noLeidos = 0;
        $avisos   = collect();

        $user = Auth::user();

        if ($user && $user->hasRole('Cliente')) {
            $cliente = Cliente::where('id_usuario', $user->id)->first();

            if ($cliente) {
                $noLeidos = Aviso::whereDoesntHave('lecturas', function ($q) use ($cliente) {
                    $q->where('id_cliente', $cliente->id_cliente);
                })->count();

                $idsLeidos = AvisoCliente::where('id_cliente', $cliente->id_cliente)
                    ->pluck('id_aviso')
                    ->all();

                $avisos = Aviso::orderByDesc('created_at')
                    ->limit(10)
                    ->get()
                    ->map(function (Aviso $aviso) use ($idsLeidos) {
                        $aviso->yaLeido = in_array($aviso->id, $idsLeidos, true);
                        return $aviso;
                    });
            }
        }

        $view->with('avisosNoLeidosCount', $noLeidos);
        $view->with('avisosParaCampanita', $avisos);
    }
}
