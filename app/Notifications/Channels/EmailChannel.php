<?php

namespace App\Notifications\Channels;

use App\Contracts\CanalNotificacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements CanalNotificacion
{
    public function enviar(string $destino, string $mensaje): bool
    {
        try {
            Mail::raw($mensaje, fn ($m) => $m
                ->to($destino)
                ->subject('MadBarzz — Recordatorio de membresía')
            );
            return true;
        } catch (\Exception $e) {
            Log::warning("EmailChannel: fallo enviando a {$destino}: {$e->getMessage()}");
            return false;
        }
    }
}
