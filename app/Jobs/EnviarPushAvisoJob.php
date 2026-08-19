<?php

namespace App\Jobs;

use App\Models\Aviso;
use App\Models\PushSubscription;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Push de un Aviso (Etapa 3 de Web Push) a todos los clientes con
 * suscripción activa. Los Avisos no pasan por Notificacion/
 * NotificacionService (son un aviso masivo "a todos", no una notificación
 * por cliente) — este job es el único disparador de su push.
 *
 * En cola (ShouldQueue) para no bloquear AvisoController::store(): el aviso
 * ya quedó guardado y visible en la campanita antes de que esto corra.
 */
class EnviarPushAvisoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly Aviso $aviso) {}

    public function handle(WebPushChannel $canal): void
    {
        $idsClientes = PushSubscription::query()->distinct()->pluck('id_cliente');

        if ($idsClientes->isEmpty()) {
            return; // nadie suscripto todavía — nada que hacer
        }

        $extracto = Str::limit($this->aviso->texto, 120);

        foreach ($idsClientes as $idCliente) {
            // Un cliente sin suscripción ni siquiera aparece acá (la query
            // es sobre push_subscriptions); si el envío a UNO falla, no debe
            // impedir que se intente con el resto.
            try {
                $canal->enviarAviso((int) $idCliente, $this->aviso->titulo, $extracto);
            } catch (\Throwable $e) {
                Log::warning("EnviarPushAvisoJob: fallo enviando push del aviso #{$this->aviso->id} a cliente #{$idCliente}: {$e->getMessage()}");
            }
        }
    }

    /** Se llama cuando se agotan todos los reintentos. */
    public function failed(\Throwable $e): void
    {
        Log::error("EnviarPushAvisoJob: agotados los reintentos para el aviso #{$this->aviso->id}: {$e->getMessage()}");
    }
}
