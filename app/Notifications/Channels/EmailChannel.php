<?php

namespace App\Notifications\Channels;

use App\Contracts\CanalNotificacion;
use App\Support\Redactor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements CanalNotificacion
{
    public function enviar(string $destino, string $mensaje, ?string $idCliente = null): bool
    {
        try {
            Mail::raw($mensaje, fn ($m) => $m
                ->to($destino)
                ->subject('MadBarzz — Recordatorio de membresía')
            );
            return true;
        } catch (\Exception $e) {
            $identificador = Redactor::identificarParaLog($idCliente, $destino);
            Log::warning("EmailChannel: fallo enviando a {$identificador}: {$e->getMessage()}");
            return false;
        }
    }
}
